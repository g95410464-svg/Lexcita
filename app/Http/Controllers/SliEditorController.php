<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Support\SliSiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SliEditorController extends Controller
{
    private function ensureSli(): void
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);
    }

    private function ensureSchema(): void
    {
        abort_unless(DB::getSchemaBuilder()->hasTable('sli_site_settings'), 503,
            'El editor visual aún requiere la migración de SLI.');
    }

    public function index()
    {
        $this->ensureSli();
        $this->ensureSchema();
        $content = SliSiteContent::get();
        $abogados = Usuario::where('rol', 'abogado')->orderBy('nombre')->paginate(20);
        return view('sli.interno.personalizar', compact('content', 'abogados'));
    }

    public function update(Request $request)
    {
        $this->ensureSli();
        $this->ensureSchema();
        $data = $request->validate([
            'home.eyebrow' => ['required', 'string', 'max:90'],
            'home.title' => ['required', 'string', 'max:130'],
            'home.description' => ['required', 'string', 'max:320'],
            'about.eyebrow' => ['required', 'string', 'max:100'],
            'about.title' => ['required', 'string', 'max:130'],
            'about.summary' => ['required', 'string', 'max:240'],
            'about.heading' => ['required', 'string', 'max:130'],
            'about.paragraph_one' => ['required', 'string', 'max:600'],
            'about.paragraph_two' => ['required', 'string', 'max:600'],
            'theme.navy' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'theme.gold' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'theme.canvas' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'theme.sage' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'home_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=3200,max_height=3200'],
            'logo_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=1600,max_height=1000'],
        ], [
            'theme.*.regex' => 'Usa un color hexadecimal de seis caracteres, como #1d2a44.',
            'home_image.image' => 'El archivo debe ser una imagen válida.',
            'home_image.mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'home_image.max' => 'La imagen no puede superar 4 MB.',
        ]);

        $content = SliSiteContent::get();
        $content['home'] = array_replace($content['home'], $data['home']);
        $content['about'] = array_replace($content['about'], $data['about']);
        $content['theme'] = array_replace($content['theme'], $data['theme']);
        $newImage = null;
        if ($request->hasFile('home_image')) {
            $newImage = $this->storeImage($request->file('home_image'));
            $content['home']['image'] = $newImage;
        } elseif ($request->boolean('remove_home_image')) {
            $content['home']['image'] = null;
        }
        if ($request->hasFile('logo_image')) {
            $content['home']['logo'] = $this->storeImage($request->file('logo_image'));
        } elseif ($request->boolean('remove_logo_image')) {
            $content['home']['logo'] = null;
        }

        DB::transaction(function () use ($content, $request): void {
            foreach ($content as $key => $value) {
                DB::table('sli_site_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => json_encode($value, JSON_THROW_ON_ERROR), 'updated_by' => $request->user()->id,
                        'created_at' => now(), 'updated_at' => now()],
                );
            }
        });

        return back()->with('success', 'Cambios guardados. Ya puedes revisar la página pública.');
    }

    public function updateLawyer(Request $request, int $id)
    {
        $this->ensureSli();
        $this->ensureSchema();
        $lawyer = Usuario::where('rol', 'abogado')->findOrFail($id);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'perfil_biografia' => ['nullable', 'string', 'max:1600'],
            'perfil_imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=2400,max_height=2400'],
        ]);

        if ($request->hasFile('perfil_imagen')) {
            $data['perfil_imagen'] = $this->storeImage($request->file('perfil_imagen'));
        }
        $lawyer->update($data);
        return back()->with('success', 'Perfil de abogado guardado.');
    }

    public function media(string $file)
    {
        $this->ensureSli();
        abort_unless(preg_match('/\A[a-f0-9-]{36}\.(?:jpg|jpeg|png|webp)\z/', $file), 404);
        abort_unless(DB::getSchemaBuilder()->hasColumns('usuarios', ['perfil_imagen'])
            && DB::getSchemaBuilder()->hasTable('sli_site_settings'), 404);
        $home = SliSiteContent::get()['home'];
        $isPublicAsset = in_array($file, [$home['image'], $home['logo']], true)
            || Usuario::abogadosActivos()->where('perfil_imagen', $file)->exists();
        $isAdminPreview = request()->user()?->rol === 'admin'
            && Usuario::where('rol', 'abogado')->where('perfil_imagen', $file)->exists();
        abort_unless($isPublicAsset || $isAdminPreview, 404);
        $disk = Storage::disk('sli-assets');
        abort_unless($disk->exists('sli/'.$file), 404);
        $stream = $disk->readStream('sli/'.$file);
        abort_unless(is_resource($stream), 404);
        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', default => 'image/webp',
        };

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff']);
    }

    private function storeImage($file): string
    {
        abort_unless(config('filesystems.disks.sli-assets.key') && config('filesystems.disks.sli-assets.secret'),
            503, 'El almacenamiento de imágenes aún no está conectado.');
        $name = Str::uuid().'.'.strtolower($file->extension());
        Storage::disk('sli-assets')->putFileAs('sli', $file, $name, ['visibility' => 'private']);
        return $name;
    }
}
