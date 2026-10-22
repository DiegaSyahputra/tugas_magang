<?php

namespace Tests\Integration;

use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_library_and_filter_by_category(): void
    {
        $bookWeb = Library::factory()->create([
            'title' => 'Panduan Mahir Vue.js 3',
            'category' => 'Web Development',
        ]);

        $bookDesign = Library::factory()->create([
            'title' => 'Design System with Figma',
            'category' => 'UI/UX Design',
        ]);

        $response = $this->actingAs($this->user)->get(route('library.index'));
        $response->assertOk();
        $response->assertSee('Panduan Mahir Vue.js 3');
        $response->assertSee('Design System with Figma');

        // Filter by category
        $filteredResponse = $this->actingAs($this->user)->get(route('library.index', ['category' => 'Web Development']));
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Panduan Mahir Vue.js 3');
        $filteredResponse->assertDontSee('Design System with Figma');
    }

    public function test_user_can_upload_digital_resource(): void
    {
        Storage::fake('public');

        $fakePdf = UploadedFile::fake()->create('ebook-laravel.pdf', 1024, 'application/pdf');

        $payload = [
            'title' => 'Buku Pintar PHP Modern 8.2',
            'category' => 'Web Development',
            'file_format' => 'PDF E-Book',
            'cover_icon' => '📖',
            'file' => $fakePdf,
        ];

        $response = $this->actingAs($this->user)->post(route('library.store'), $payload);

        $response->assertRedirect(route('library.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('libraries', [
            'title' => 'Buku Pintar PHP Modern 8.2',
            'category' => 'Web Development',
            'file_format' => 'PDF E-Book',
            'cover_icon' => '📖',
        ]);

        $library = Library::where('title', 'Buku Pintar PHP Modern 8.2')->first();
        $this->assertNotNull($library->file_path);
        Storage::disk('public')->assertExists($library->file_path);
    }

    public function test_user_can_download_resource_and_increments_download_counter(): void
    {
        Storage::fake('public');

        $fakeFilePath = 'library_files/test_resource.pdf';
        Storage::disk('public')->put($fakeFilePath, 'Fake PDF Content');

        $library = Library::factory()->create([
            'file_path' => $fakeFilePath,
            'downloads' => 5,
        ]);

        $response = $this->actingAs($this->user)->get(route('library.download', $library));

        $response->assertOk();

        $library->refresh();
        $this->assertEquals(6, $library->downloads);
    }
}
