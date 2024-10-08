<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use PHPUnit\Framework\Attributes\Test; // Pastikan ini ditambahkan

class ImageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_all_images()
    {
        Image::factory()->count(3)->create();

        $response = $this->getJson('/api/images');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    #[Test]
    public function it_can_upload_an_image_and_generate_link()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->postJson('/api/upload', [
            'image' => $file
        ]);

        $response->assertStatus(201);
        $this->assertTrue(Storage::disk('local')->exists('images/' . $file->hashName()));

        $this->assertDatabaseHas('images', [
            'path' => 'images/' . $file->hashName(),
        ]);

        $url = Storage::url('images/' . $file->hashName());
        $response->assertJson(['url' => $url]);
    }

    #[Test]
    public function it_can_show_an_image_by_id()
    {
        $image = Image::factory()->create();

        $response = $this->getJson("/api/images/{$image->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $image->id,
            'path' => $image->path,
        ]);
    }

    #[Test]
    public function it_returns_404_if_image_not_found()
    {
        $response = $this->getJson('/api/images/9999');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Image not found',
        ]);
    }

    #[Test]
    public function it_can_update_an_image()
    {
        Storage::fake('local');

        $image = Image::factory()->create([
            'path' => 'images/old-image.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new-image.jpg');

        $response = $this->putJson("/api/images/{$image->id}", [
            'image' => $newFile
        ]);

        $response->assertStatus(200);
        Storage::disk('local')->assertMissing($image->path);
        $this->assertTrue(Storage::disk('local')->exists('images/' . $newFile->hashName()));

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'path' => 'images/' . $newFile->hashName(),
        ]);
    }

    #[Test]
    public function it_can_delete_an_image()
    {
        Storage::fake('local');

        $image = Image::factory()->create([
            'path' => 'images/test-image.jpg',
        ]);

        $response = $this->deleteJson("/api/images/{$image->id}");

        $response->assertStatus(200);
        Storage::disk('local')->assertMissing($image->path);
        $this->assertDatabaseMissing('images', [
            'id' => $image->id,
        ]);
    }
}
