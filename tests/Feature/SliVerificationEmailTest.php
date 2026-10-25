<?php

namespace Tests\Feature;

use App\Services\RegistrationOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Http};
use Tests\TestCase;

class SliVerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['organization.id'=>'servicios-legales-integrados']);
        if (!\Illuminate\Support\Facades\Schema::hasTable('registration_verifications')) {
            (require database_path('migrations/2026_10_19_000002_create_sli_registration_verifications.php'))->up();
        }
    }

    public function test_brevo_receives_brand_html_and_plain_text_with_same_working_code(): void
    {
        config(['services.brevo.api_key'=>'testing-key','mail.from.address'=>'verified@example.test']);
        Http::preventStrayRequests(); Http::fake(['api.brevo.com/*'=>Http::response(['messageId'=>'test'],201)]);
        $service=app(RegistrationOtpService::class);
        $service->begin('email-design-test',['nombre'=>'Prueba','email'=>'recipient@example.test','password'=>Hash::make('Testing123!')]);
        $code=null;
        Http::assertSent(function ($request) use (&$code) {
            preg_match('/\b\d{6}\b/',$request['textContent'],$match);
            $code=$match[0]??null;
            return $code && str_contains($request['htmlContent'],$code)
                && str_contains($request['htmlContent'],'G.C')
                && str_contains($request['htmlContent'],'10 minutos')
                && !str_contains($request['htmlContent'],'testing-key');
        });
        $user=$service->verify('email-design-test',$code);
        $this->assertSame('recipient@example.test',$user->email);
        $this->assertNotNull($user->email_verified_at);
        Http::assertSentCount(1);
    }

    public function test_email_template_escapes_its_dynamic_content(): void
    {
        $html=view('emails.sli-verification',['code'=>'<script>bad</script>'])->render();
        $this->assertStringNotContainsString('<script>',$html);
        $this->assertStringContainsString('&lt;script&gt;',$html);
    }
}
