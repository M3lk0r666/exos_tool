<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaptureUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->engineer = User::factory()->create();
        $this->engineer->assignRole('engineer');

        $this->client = Client::factory()->create();

        Storage::fake('local');
        Queue::fake();
    }

    private function fakeTechSupport(string $sysname = 'SW-TEST'): UploadedFile
    {
        $content = "->show switch\nSysName:          {$sysname}\n".
            "System Type:      X440G2-24t\n".
            "System MAC:       00:04:96:AA:BB:CC\n".
            "Current Time:     Mon Jan  5 10:00:00 2026\n";

        return UploadedFile::fake()->createWithContent("{$sysname}.txt", $content);
    }

    public function test_engineer_can_upload_and_job_is_dispatched(): void
    {
        $response = $this->actingAs($this->engineer)->post(route('admin.captures.store'), [
            'client_id' => $this->client->id,
            'analysis_type' => 'tech_support',
            'files' => [$this->fakeTechSupport()],
        ]);

        $response->assertRedirect(route('admin.captures.index', ['client' => $this->client->id]));

        $capture = Capture::first();
        $this->assertNotNull($capture);
        $this->assertSame(CaptureStatus::Pending, $capture->status);
        $this->assertSame($this->client->id, $capture->client_id);
        $this->assertSame(64, strlen($capture->file_hash));

        Storage::disk('local')->assertExists($capture->file_path);
        Queue::assertPushed(ProcessCaptureJob::class, 1);
    }

    public function test_duplicate_file_is_skipped_by_hash(): void
    {
        $file = $this->fakeTechSupport();

        $this->actingAs($this->engineer)->post(route('admin.captures.store'), [
            'client_id' => $this->client->id,
            'analysis_type' => 'tech_support',
            'files' => [$file],
        ]);

        // Mismo contenido => mismo SHA-256 => se omite
        $this->actingAs($this->engineer)->post(route('admin.captures.store'), [
            'client_id' => $this->client->id,
            'analysis_type' => 'tech_support',
            'files' => [$this->fakeTechSupport()],
        ]);

        $this->assertSame(1, Capture::count());
        Queue::assertPushed(ProcessCaptureJob::class, 1);
    }

    public function test_rejects_invalid_extension(): void
    {
        $response = $this->actingAs($this->engineer)
            ->from(route('admin.captures.create'))
            ->post(route('admin.captures.store'), [
                'client_id' => $this->client->id,
            'analysis_type' => 'tech_support',
                'files' => [UploadedFile::fake()->create('reporte.pdf', 10)],
            ]);

        $response->assertSessionHasErrors('files.0');
        $this->assertSame(0, Capture::count());
    }

    public function test_reader_cannot_upload(): void
    {
        $reader = User::factory()->create();
        $reader->assignRole('reader');

        $response = $this->actingAs($reader)->post(route('admin.captures.store'), [
            'client_id' => $this->client->id,
            'analysis_type' => 'tech_support',
            'files' => [$this->fakeTechSupport()],
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Capture::count());
    }
}
