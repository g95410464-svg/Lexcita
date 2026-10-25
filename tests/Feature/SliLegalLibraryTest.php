<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\SliLibraryDocument;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SliLegalLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix = ''): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($role).' de prueba', 'email' => $role.$suffix.'@example.test',
            'password' => 'ClaveLocal123', 'rol' => $role, 'activo' => true,
        ]);
    }

    private function enableSliViews(): void
    {
        config(['organization.id' => 'servicios-legales-integrados']);
        app(AppServiceProvider::class, ['app' => $this->app])->boot();
        app('view')->getFinder()->flush();
    }

    public function test_lawyer_role_gets_library_navigation_and_curated_public_sources(): void
    {
        $this->enableSliViews();
        $this->actingAs($this->user('abogado'));

        $this->get(route('abogado.biblioteca'))->assertOk()
            ->assertSee('Biblioteca legal')
            ->assertSee('Buscador oficial de legislación')
            ->assertSee('Constitución de la República')
            ->assertSee('Consultar fuente oficial');
        $this->get(route('abogado.dashboard'))->assertOk()
            ->assertSee('href="'.route('abogado.biblioteca').'"', false);
    }

    public function test_admin_can_publish_https_resource_and_clients_cannot_open_library(): void
    {
        $this->enableSliViews();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('interno.biblioteca.store'), [
            'title' => 'Código de prueba', 'category' => 'Civil', 'jurisdiction' => 'El Salvador',
            'source_url' => 'https://www.asamblea.gob.sv/leyes-y-decretos/decretos-por-anios',
            'description' => 'Fuente de consulta',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('sli_library_documents', [
            'title' => 'Código de prueba', 'created_by' => $admin->id,
        ]);
        $this->actingAs($this->user('cliente'))->get(route('abogado.biblioteca'))->assertRedirect(route('cliente.dashboard'));
    }

    public function test_external_sources_do_not_replace_books_or_consume_their_pagination(): void
    {
        $this->enableSliViews();
        for ($i = 0; $i < 16; $i++) {
            SliLibraryDocument::create(['title' => 'Portal '.$i, 'category' => 'Civil',
                'source_url' => 'https://example.test/portal/'.$i]);
        }
        $book = SliLibraryDocument::create(['title' => 'Libro con portada', 'category' => 'Civil',
            'storage_path' => 'library/book.pdf']);
        $this->actingAs($this->user('abogado'))->get(route('abogado.biblioteca'))
            ->assertOk()->assertSee('Fuentes oficiales')->assertSee('data-library-cover', false)
            ->assertSee(route('abogado.biblioteca.read', $book), false)
            ->assertViewHas('documents', fn ($items) => $items->total() === 1 && $items->sole()->id === $book->id)
            ->assertViewHas('sources', fn ($items) => $items->count() === 20);
        $this->get(route('abogado.biblioteca', ['q' => 'Portal 15']))->assertOk()
            ->assertViewHas('documents', fn ($items) => $items->isEmpty())
            ->assertViewHas('sources', fn ($items) => $items->count() === 1)
            ->assertDontSee('data-library-cover ', false);
    }

    public function test_only_admin_can_publish_or_remove_a_book_and_all_lawyers_can_read_it(): void
    {
        $this->enableSliViews();
        Storage::fake('sli-assets');
        config(['filesystems.disks.sli-assets.key' => 'test', 'filesystems.disks.sli-assets.secret' => 'test',
            'filesystems.disks.sli-assets.bucket' => 'test']);
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('interno.biblioteca.store'), [
            'title' => 'Libro compartido', 'category' => 'Civil', 'jurisdiction' => 'El Salvador',
            'document' => UploadedFile::fake()->create('libro.pdf', 5, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $book = SliLibraryDocument::where('title', 'Libro compartido')->sole();
        Storage::disk('sli-assets')->assertExists($book->storage_path);

        foreach (['guillermo', 'juan'] as $suffix) {
            $this->actingAs($this->user('abogado', $suffix));
            $this->get(route('abogado.biblioteca'))->assertOk()->assertSee('Libro compartido')
                ->assertSee('Leer y anotar')->assertDontSee('Descargar PDF')->assertDontSee('Publicar libro');
            $this->get(route('abogado.biblioteca.read', $book))->assertOk()->assertSee('Mi cuaderno')->assertSee('Ocultar notas')->assertDontSee('Descargar PDF');
            $this->get(route('abogado.biblioteca.content', $book))->assertOk()
                ->assertHeader('Content-Disposition', 'inline');
            $this->post(route('interno.biblioteca.store'), [])->assertRedirect(route('abogado.dashboard'));
            $this->delete(route('interno.biblioteca.destroy', $book))->assertRedirect(route('abogado.dashboard'));
            $this->assertDatabaseHas('sli_library_documents', ['id' => $book->id]);
        }
        $this->get('/abogado/biblioteca/'.$book->id.'/descargar')->assertNotFound();
        $this->actingAs($this->user('cliente'))->get(route('abogado.biblioteca.content', $book))
            ->assertRedirect(route('cliente.dashboard'));
        $this->actingAs($admin)->delete(route('interno.biblioteca.destroy', $book))->assertRedirect();
        $this->assertDatabaseMissing('sli_library_documents', ['id' => $book->id]);
        Storage::disk('sli-assets')->assertMissing($book->storage_path);
    }

    public function test_annotations_are_personal_persisted_and_bound_to_their_book(): void
    {
        $this->enableSliViews();
        $book = SliLibraryDocument::create(['title' => 'Libro', 'category' => 'Civil', 'storage_path' => 'library/book.pdf']);
        $otherBook = SliLibraryDocument::create(['title' => 'Otro libro', 'category' => 'Civil', 'storage_path' => 'library/other.pdf']);
        $owner = $this->user('abogado', 'guillermo');
        $other = $this->user('abogado', 'juan');
        $this->actingAs($owner);
        $annotation = $this->postJson(route('abogado.biblioteca.annotate', $book), [
            'page' => 2, 'kind' => 'underline', 'quote' => 'Texto de una ley', 'note' => 'Revisar este artículo',
            'rects' => [['x' => .1, 'y' => .2, 'width' => .3, 'height' => .02]],
            'usuario_id' => $other->id,
        ])->assertCreated()->json('id');
        $this->assertDatabaseHas('sli_library_annotations', ['id' => $annotation, 'usuario_id' => $owner->id]);
        $this->getJson(route('abogado.biblioteca.annotations', $book))->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.note', 'Revisar este artículo');
        $this->deleteJson(route('abogado.biblioteca.annotations.destroy', [$otherBook, $annotation]))->assertNotFound();
        $this->actingAs($other)->getJson(route('abogado.biblioteca.annotations', $book))->assertOk()->assertExactJson([]);
        $this->deleteJson(route('abogado.biblioteca.annotations.destroy', [$book, $annotation]))->assertNotFound();
        $this->actingAs($owner)->deleteJson(route('abogado.biblioteca.annotations.destroy', [$book, $annotation]))->assertNoContent();
        $this->assertDatabaseMissing('sli_library_annotations', ['id' => $annotation]);
    }

    public function test_empty_highlights_and_out_of_bounds_geometry_are_rejected_but_page_notes_work(): void
    {
        $this->enableSliViews();
        $book = SliLibraryDocument::create(['title' => 'Libro', 'category' => 'Civil', 'storage_path' => 'library/book.pdf']);
        $this->actingAs($this->user('abogado'));
        $url = route('abogado.biblioteca.annotate', $book);
        $this->postJson($url, ['page' => 1, 'kind' => 'highlight', 'rects' => []])->assertUnprocessable();
        $this->postJson($url, ['page' => 1, 'kind' => 'highlight',
            'rects' => [['x' => .9, 'y' => 0, 'width' => .5, 'height' => .1]]])->assertUnprocessable();
        $this->postJson($url, ['page' => 1, 'kind' => 'bookmark', 'note' => 'Releer esta página'])->assertCreated();
    }

    public function test_library_is_not_available_outside_sli(): void
    {
        config(['organization.id' => 'otra-organizacion']);
        $this->actingAs($this->user('abogado'))->get(route('abogado.biblioteca'))->assertNotFound();
    }
}
