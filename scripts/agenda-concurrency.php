<?php

// Bounded functional concurrency test. Never point this at a production database.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{BookingHold, Cita, HorarioDisponible, Usuario};
use App\Services\CitaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Artisan, DB, Http};

if (!$app->environment('testing') || getenv('AGENDA_LOAD_CONFIRM')!=='isolated-test-database'
    || DB::connection()->getDriverName()!=='pgsql'
    || DB::connection()->getDatabaseName()!=='lexcita_sli_loadtest'
    || DB::selectOne('SELECT current_database() AS name')->name!=='lexcita_sli_loadtest'
    || !function_exists('pcntl_fork') || !function_exists('posix_kill')) {
    fwrite(STDERR,"Refused: requires Linux pcntl, APP_ENV=testing, isolated PostgreSQL lexcita_sli_loadtest and AGENDA_LOAD_CONFIRM=isolated-test-database.\n");exit(2);
}
if (DB::getSchemaBuilder()->hasTable('usuarios') && Usuario::exists()) {
    fwrite(STDERR,"Refused: test database must contain no users.\n");exit(2);
}
config(['organization.id'=>'servicios-legales-integrados','organization.isolated'=>false,
    'agenda.routing_provider'=>'manual','broadcasting.default'=>'null','mail.default'=>'array','cache.default'=>'array']);
Http::preventStrayRequests();
if (Artisan::call('migrate',['--force'=>true])!==0) { fwrite(STDERR,"Test schema migration failed.\n");exit(2); }
$prefix='load-'.bin2hex(random_bytes(6));
$directory=sys_get_temp_dir().'/'.$prefix;
mkdir($directory,0700);
$ids=[];$children=[];$ok=false;
try {
    Carbon::setTestNow('2030-01-06 08:00:00');
    $lawyer=Usuario::create(['nombre'=>'Concurrency fixture','email'=>$prefix.'-lawyer@example.test','password'=>bin2hex(random_bytes(24)), 'rol'=>'abogado','activo'=>true]);
    $ids[]=$lawyer->id;
    $lawyer->horarios()->delete();
    HorarioDisponible::create(['abogado_id'=>$lawyer->id,'dia_semana'=>'lunes','hora_inicio'=>'09:00','hora_fin'=>'12:00','activo'=>true]);
    $clients=[];
    for($i=0;$i<100;$i++) {
        $client=Usuario::create(['nombre'=>'Load fixture '.$i,'email'=>$prefix.'-'.$i.'@example.test','password'=>bin2hex(random_bytes(24)), 'rol'=>'cliente','activo'=>true]);
        $ids[]=$clients[]=$client->id;
    }
    $results=[];
    foreach (['read','compete'] as $scenario) {
        DB::disconnect(); // Never share inherited PostgreSQL sockets across forks.
        $children=[];
        for($i=0;$i<100;$i++) {
            $pid=pcntl_fork();
            if($pid===-1) throw new RuntimeException('fork failed');
            if($pid===0) {
                DB::purge();
                touch($directory.'/'.$scenario.'-ready-'.$i);
                $deadline=microtime(true)+45;
                while(!is_file($directory.'/'.$scenario.'-go') && microtime(true)<$deadline) usleep(10000);
                $start=microtime(true);$outcome='error';
                try {
                    if(!is_file($directory.'/'.$scenario.'-go')) throw new RuntimeException('barrier timeout');
                    DB::statement("SET statement_timeout = '20s'");
                    DB::statement("SET lock_timeout = '15s'");
                    if($scenario==='read') {
                        $slots=app(CitaService::class)->getSlotsDisponibles($lawyer->id,'2030-01-07');
                        $outcome=count($slots)===3?'read_ok':'invalid_read';
                    } else {
                        app(CitaService::class)->retenerHorario($clients[$i],$lawyer->id,'2030-01-07','09:00');
                        $outcome='held';
                    }
                } catch (DomainException) { $outcome='unavailable'; }
                catch (Throwable) { $outcome='error'; } // Never serialize exceptions/credentials.
                file_put_contents($directory.'/'.$scenario.'-result-'.$i,json_encode(['outcome'=>$outcome,'ms'=>round((microtime(true)-$start)*1000,2)]));
                DB::disconnect();exit($outcome==='error'?1:0);
            }
            $children[]=$pid;
        }
        $deadline=microtime(true)+45;
        while(count(glob($directory.'/'.$scenario.'-ready-*'))<100 && microtime(true)<$deadline) usleep(20000);
        if(count(glob($directory.'/'.$scenario.'-ready-*'))!==100) throw new RuntimeException('workers not ready');
        $started=microtime(true);touch($directory.'/'.$scenario.'-go');
        foreach($children as $pid) pcntl_waitpid($pid,$status);
        $children=[];$outcomes=[];$latencies=[];
        foreach(glob($directory.'/'.$scenario.'-result-*') as $file) {
            $row=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
            $outcomes[$row['outcome']]=($outcomes[$row['outcome']]??0)+1;$latencies[]=$row['ms'];
        }
        sort($latencies);
        $results[$scenario]=['workers'=>100,'results'=>$outcomes,'elapsed_ms'=>round((microtime(true)-$started)*1000,2),
            'p50_ms'=>$latencies[49]??null,'p95_ms'=>$latencies[94]??null,'max_ms'=>end($latencies)];
        DB::purge();
        if($scenario==='read' && ($outcomes['read_ok']??0)!==100) throw new RuntimeException('reads failed');
        if($scenario==='compete' && (($outcomes['held']??0)!==1 || ($outcomes['unavailable']??0)!==99 || BookingHold::where('abogado_id',$lawyer->id)->count()!==1)) throw new RuntimeException('hold invariant failed');
    }
    Carbon::setTestNow('2030-01-06 08:15:01');
    $newHold=app(CitaService::class)->retenerHorario($clients[99],$lawyer->id,'2030-01-07','09:00');
    if($newHold->cliente_id!==$clients[99]) throw new RuntimeException('expired hold not released');
    $paid=Cita::create(['codigo'=>Cita::generarCodigo(),'cliente_id'=>$clients[0],'abogado_id'=>$lawyer->id,'fecha'=>'2030-01-07',
        'hora_inicio'=>'10:00','hora_fin'=>'11:00','tipo'=>'consulta_general','modalidad'=>'virtual','estado'=>'confirmada','monto'=>35,
        'payment_status'=>'completed','paid_at'=>now(),'hold_expires_at'=>now()->subHour()]);
    Carbon::setTestNow('2030-01-06 09:00:00');
    if(in_array('10:00',array_column(app(CitaService::class)->getSlotsDisponibles($lawyer->id,'2030-01-07'),'hora'),true) || $paid->fresh()->estado!=='confirmada') throw new RuntimeException('paid booking released');
    $results['simulated_clock']=['expired_unpaid_reused'=>true,'paid_still_occupied'=>true,'waited_fifteen_minutes'=>false];
    echo json_encode(['scope'=>'isolated PostgreSQL functional concurrency, not production HTTP capacity','php'=>PHP_VERSION,'results'=>$results],JSON_PRETTY_PRINT).PHP_EOL;
    $ok=true;
} catch(Throwable) {
    echo json_encode(['scope'=>'isolated functional test','partial_results'=>$results??[],'passed'=>false],JSON_PRETTY_PRINT).PHP_EOL;
    fwrite(STDERR,"Controlled concurrency test failed. No provider was called; inspect bounded results in the test environment.\n");
} finally {
    foreach($children as $pid) { posix_kill($pid,SIGTERM);pcntl_waitpid($pid,$status); }
    DB::purge();Usuario::whereIn('id',$ids)->delete();
    foreach(glob($directory.'/*') as $file) unlink($file);
    rmdir($directory);Carbon::setTestNow();
}
exit($ok?0:1);
