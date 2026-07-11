<?php

namespace Tests\Feature;

use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
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
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/captures')->assertUnauthorized();
    }

    public function test_can_upload_and_query_capture(): void
    {
        Storage::fake('local');
        Queue::fake();
        Sanctum::actingAs($this->engineer);

        $file = UploadedFile::fake()->createWithContent('ts.txt', "->show switch\nSysName: SW-API\n");

        $response = $this->postJson('/api/v1/captures', [
            'client_id' => $this->client->id,
            'file' => $file,
        ])->assertCreated();

        $captureId = $response->json('capture_id');
        Queue::assertPushed(ProcessCaptureJob::class, 1);

        // Duplicado => 409
        $this->postJson('/api/v1/captures', [
            'client_id' => $this->client->id,
            'file' => UploadedFile::fake()->createWithContent('ts2.txt', "->show switch\nSysName: SW-API\n"),
        ])->assertStatus(409);

        $this->getJson("/api/v1/captures/{$captureId}")
            ->assertOk()
            ->assertJsonPath('id', $captureId)
            ->assertJsonPath('status', 'pending');

        $this->getJson('/api/v1/captures?client_id='.$this->client->id)
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_findings_and_metrics_endpoints(): void
    {
        Sanctum::actingAs($this->engineer);

        $device = Device::factory()->create(['client_id' => $this->client->id]);
        $capture = Capture::factory()->create([
            'client_id' => $this->client->id,
            'device_id' => $device->id,
        ]);

        Finding::factory()->create([
            'capture_id' => $capture->id,
            'device_id' => $device->id,
            'rule_code' => 'PORT-CRC',
            'level' => 'high',
            'entity' => '32',
        ]);
        $capture->metrics()->create([
            'category' => 'ports', 'entity' => '32', 'metric' => 'crc_errors', 'value' => 500,
        ]);

        $this->getJson("/api/v1/captures/{$capture->id}/findings")
            ->assertOk()
            ->assertJsonPath('findings.0.rule_code', 'PORT-CRC');

        $this->getJson("/api/v1/captures/{$capture->id}/metrics?category=ports")
            ->assertOk()
            ->assertJsonPath('metrics.0.metric', 'crc_errors');

        $this->getJson('/api/v1/clients')->assertOk();
        $this->getJson('/api/v1/devices?client_id='.$this->client->id)
            ->assertOk()
            ->assertJsonPath('0.sysname', $device->sysname);
    }
}
