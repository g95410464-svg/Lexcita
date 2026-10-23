<?php

namespace App\Http\Controllers;

use App\Models\SliLibraryDocument;
use App\Models\SliLibraryAnnotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SliLibraryController extends Controller
{
    private function ensureSli(): void
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);
        abort_unless(DB::getSchemaBuilder()->hasTable('sli_library_documents'), 503,
            'La biblioteca de SLI aún requiere su migración.');
    }

    public function index(Request $request)
    {
        $this->ensureSli();
        $query = SliLibraryDocument::query()->orderBy('category')->orderBy('title');

        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('categoria', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(category) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(jurisdiction) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE LOWER(?)', ["%{$search}%"]);
            });
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $documents = $query->paginate(15)->withQueryString();
        $categories = SliLibraryDocument::query()->distinct()->orderBy('category')->pluck('category');

        return view('sli.abogado.biblioteca', compact('documents', 'categories', 'search', 'category'));
    }

    public function manage()
    {
        $this->ensureSli();
        $documents = SliLibraryDocument::query()->latest()->paginate(20);
        return view('sli.interno.biblioteca', compact('documents'));
    }

    public function store(Request $request)
    {
        $this->ensureSli();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:80'],
            'jurisdiction' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1200'],
            'source_url' => ['nullable', 'url:https', 'max:2048'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:25600'],
        ], [
            'source_url.url' => 'La fuente debe ser una URL HTTPS válida.',
            'document.mimes' => 'Solo puedes cargar documentos PDF.',
            'document.max' => 'El PDF no puede superar 25 MB.',
        ]);

        if (!$request->hasFile('document') && empty($data['source_url'])) {
            return back()->withInput()->withErrors(['document' => 'Agrega un PDF o un enlace HTTPS a la fuente.']);
        }

        $storagePath = null;
        if ($request->hasFile('document')) {
            abort_unless(config('filesystems.disks.sli-assets.key') && config('filesystems.disks.sli-assets.secret')
                && config('filesystems.disks.sli-assets.bucket'), 503,
                'El almacenamiento privado de documentos aún no está conectado.');
            $file = $request->file('document');
            $storagePath = 'library/'.Str::uuid().'.pdf';
            $stream = fopen($file->getRealPath(), 'rb');
            abort_unless(is_resource($stream), 422, 'No se pudo leer el PDF seleccionado.');
            try {
                Storage::disk('sli-assets')->put($storagePath, $stream, ['visibility' => 'private']);
            } finally {
                fclose($stream);
            }
        }

        SliLibraryDocument::create([
            ...collect($data)->except('document')->all(),
            'storage_path' => $storagePath,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'El recurso se agregó a la biblioteca de abogados.');
    }

    private function ensureReadable(SliLibraryDocument $document): void
    {
        $this->ensureSli();
        abort_unless($document->storage_path, 404);
        abort_unless(DB::getSchemaBuilder()->hasTable('sli_library_annotations'), 503,
            'La biblioteca no está disponible temporalmente. Contacta con g95410464@proton.me.');
    }

    public function read(SliLibraryDocument $document)
    {
        $this->ensureReadable($document);
        return view('sli.abogado.lector', compact('document'));
    }

    public function content(SliLibraryDocument $document)
    {
        $this->ensureReadable($document);
        $disk = Storage::disk('sli-assets');
        abort_unless($disk->exists($document->storage_path), 404);

        return response()->stream(
            function () use ($disk, $document): void {
                $stream = $disk->readStream($document->storage_path);
                abort_unless(is_resource($stream), 404);
                fpassthru($stream);
                fclose($stream);
            },
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
        );
    }

    public function annotations(Request $request, SliLibraryDocument $document)
    {
        $this->ensureReadable($document);
        return response()->json(SliLibraryAnnotation::where('document_id', $document->id)
            ->where('usuario_id', $request->user()->id)->orderBy('page')->orderBy('id')
            ->get(['id', 'page', 'kind', 'quote', 'note', 'rects']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function annotate(Request $request, SliLibraryDocument $document)
    {
        $this->ensureReadable($document);
        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'kind' => ['required', 'in:highlight,underline,bookmark'],
            'quote' => ['nullable', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'rects' => ['required_unless:kind,bookmark', 'array', 'max:200'],
            'rects.*' => ['array:x,y,width,height'],
            'rects.*.x' => ['required', 'numeric', 'between:0,1'],
            'rects.*.y' => ['required', 'numeric', 'between:0,1'],
            'rects.*.width' => ['required', 'numeric', 'gt:0', 'max:1'],
            'rects.*.height' => ['required', 'numeric', 'gt:0', 'max:1'],
        ]);
        foreach ($data['rects'] ?? [] as $rect) {
            abort_if($rect['x'] + $rect['width'] > 1.001 || $rect['y'] + $rect['height'] > 1.001, 422);
        }
        if ($data['kind'] !== 'bookmark' && empty($data['rects'])) {
            return response()->json(['message' => 'Selecciona texto del documento para marcarlo.'], 422);
        }
        $annotation = SliLibraryAnnotation::create([
            ...$data, 'document_id' => $document->id, 'usuario_id' => $request->user()->id,
        ]);
        return response()->json($annotation->only(['id', 'page', 'kind', 'quote', 'note', 'rects']), 201);
    }

    public function removeAnnotation(Request $request, SliLibraryDocument $document, SliLibraryAnnotation $annotation)
    {
        $this->ensureReadable($document);
        abort_unless((int) $annotation->document_id === (int) $document->id
            && (int) $annotation->usuario_id === (int) $request->user()->id, 404);
        $annotation->delete();
        return response()->noContent();
    }

    public function destroy(SliLibraryDocument $document)
    {
        $this->ensureSli();
        if ($document->storage_path) {
            Storage::disk('sli-assets')->delete($document->storage_path);
        }
        $document->delete();

        return back()->with('success', 'El recurso se quitó de la biblioteca.');
    }
}
