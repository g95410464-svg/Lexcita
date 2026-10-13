<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class SliPublicController extends Controller
{
    private function ensureSli(): void
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);
    }

    public function about()
    {
        $this->ensureSli();
        return view('sli.public.about');
    }

    public function how()
    {
        $this->ensureSli();
        return view('sli.public.how');
    }

    public function lawyers()
    {
        $this->ensureSli();
        $abogados = Usuario::abogadosActivos()->orderBy('nombre')->orderBy('id')
            ->paginate(12, ['id', 'nombre', 'especialidad', 'perfil_biografia', 'perfil_imagen']);
        return view('sli.public.lawyers', compact('abogados'));
    }

    public function profile(int $id)
    {
        $this->ensureSli();
        $abogado = Usuario::abogadosActivos()->select('id', 'nombre', 'especialidad', 'perfil_biografia', 'perfil_imagen')->findOrFail($id);
        return view('sli.public.profile', compact('abogado'));
    }

    public function book(Request $request, ?int $id = null)
    {
        $this->ensureSli();
        $abogado = $id ? Usuario::abogadosActivos()->select('id')->findOrFail($id) : null;
        if (!$request->user()) {
            $request->session()->put('sli.booking_requested', true);
            $request->session()->put('sli.booking_lawyer', $abogado?->id);
            return redirect()->route('registro');
        }
        if ($request->user()->rol !== 'cliente') {
            return redirect()->route($request->user()->rol === 'admin' ? 'interno.dashboard' : 'abogado.dashboard');
        }
        return redirect()->route('cliente.nueva-cita', ['abogado' => $abogado?->id]);
    }
}
