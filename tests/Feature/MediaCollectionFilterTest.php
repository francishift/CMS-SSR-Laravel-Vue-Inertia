<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaCollectionFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');

        Storage::fake('public');
    }

    #[Test]
    public function actualizar_coleccion_de_ultimo_archivo_devuelve_lista_de_colecciones_actualizada(): void
    {
        $file = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $path = $file->store('media', 'public');

        $media = MediaFile::create([
            'filename'          => 'test1.jpg',
            'original_filename' => 'test1.jpg',
            'path'              => $path,
            'disk'              => 'public',
            'mime_type'         => 'image/jpeg',
            'type'              => 'image',
            'extension'         => 'jpg',
            'size'              => 1024,
            'collection'        => 'unica-etiqueta',
            'uploaded_by'       => $this->admin->id,
        ]);

        // Verificamos que 'unica-etiqueta' figura en el index
        $responseIndex = $this->actingAs($this->admin)->get(route('media.index'));
        $responseIndex->assertStatus(200);
        $this->assertTrue(collect($responseIndex->viewData('page')['props']['colecciones'])->contains('unica-etiqueta'));

        // Modificamos el archivo quitándole la colección
        $responseUpdate = $this->actingAs($this->admin)->put(route('media.update', $media), [
            'collection' => '',
        ]);

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJson([
            'success' => true,
        ]);

        // La respuesta JSON debe contener 'colecciones' y NO incluir 'unica-etiqueta'
        $colecciones = $responseUpdate->json('colecciones');
        $this->assertIsArray($colecciones);
        $this->assertNotContains('unica-etiqueta', $colecciones);

        // Al consultar nuevamente el index, 'unica-etiqueta' ya no existe
        $responseIndex2 = $this->actingAs($this->admin)->get(route('media.index'));
        $this->assertFalse(collect($responseIndex2->viewData('page')['props']['colecciones'])->contains('unica-etiqueta'));
    }

    #[Test]
    public function eliminar_archivo_con_coleccion_unica_devuelve_colecciones_actualizadas_sin_ella(): void
    {
        $file = UploadedFile::fake()->image('test2.jpg', 100, 100);
        $path = $file->store('media', 'public');

        $media = MediaFile::create([
            'filename'          => 'test2.jpg',
            'original_filename' => 'test2.jpg',
            'path'              => $path,
            'disk'              => 'public',
            'mime_type'         => 'image/jpeg',
            'type'              => 'image',
            'extension'         => 'jpg',
            'size'              => 1024,
            'collection'        => 'para-eliminar',
            'uploaded_by'       => $this->admin->id,
        ]);

        $responseDestroy = $this->actingAs($this->admin)->delete(route('media.destroy', $media));
        $responseDestroy->assertStatus(200);
        $responseDestroy->assertJson([
            'success' => true,
        ]);

        $colecciones = $responseDestroy->json('colecciones');
        $this->assertIsArray($colecciones);
        $this->assertNotContains('para-eliminar', $colecciones);
    }

    #[Test]
    public function eliminacion_masiva_devuelve_colecciones_actualizadas(): void
    {
        $file = UploadedFile::fake()->image('test3.jpg', 100, 100);
        $path = $file->store('media', 'public');

        $media = MediaFile::create([
            'filename'          => 'test3.jpg',
            'original_filename' => 'test3.jpg',
            'path'              => $path,
            'disk'              => 'public',
            'mime_type'         => 'image/jpeg',
            'type'              => 'image',
            'extension'         => 'jpg',
            'size'              => 1024,
            'collection'        => 'masiva-temp',
            'uploaded_by'       => $this->admin->id,
        ]);

        $responseBulk = $this->actingAs($this->admin)->post(route('media.bulk-delete'), [
            'ids' => [$media->id],
        ]);

        $responseBulk->assertStatus(200);
        $responseBulk->assertJson([
            'success' => true,
        ]);

        $colecciones = $responseBulk->json('colecciones');
        $this->assertIsArray($colecciones);
        $this->assertNotContains('masiva-temp', $colecciones);
    }
}
