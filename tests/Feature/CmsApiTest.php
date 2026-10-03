<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\ConfigurationOption;
use App\Models\Inquiry;
use App\Models\ModelImage;
use App\Models\User;
use App\Notifications\NewInquiryNotification;
use Database\Seeders\CarModelSeeder;
use Database\Seeders\ConfigurationOptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class CmsApiTest extends TestCase
{
    use RefreshDatabase;

    private CarModel $gt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutDefer(); // run the post-response notification inline
        $this->seed([CarModelSeeder::class, ConfigurationOptionSeeder::class]);
        $this->gt = CarModel::where('slug', 'volterra-gt')->first();
        Storage::fake('public');
    }

    private function actingAsStaff(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'editor'])->save();
        Sanctum::actingAs($user);
    }

    private function optionId(string $type, string $code): int
    {
        return ConfigurationOption::where(compact('type', 'code'))->value('id');
    }

    private function inquiryPayload(array $overrides = []): array
    {
        return [
            'name' => 'Alex Rivera', 'email' => 'alex@example.com', 'phone' => '+39 333 123 4567', 'country' => 'Italy',
            'model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'carbon', 'interior' => 'tan',
            ...$overrides,
        ];
    }

    // --- Uploads and gallery

    public function test_staff_can_upload_reorder_and_delete_gallery_images(): void
    {
        $this->actingAsStaff();

        $first = $this->post("/api/admin/models/{$this->gt->id}/images", ['type' => 'exterior', 'image' => UploadedFile::fake()->image('a.jpg', 1600, 900)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.type', 'exterior')->json('data');
        $second = $this->post("/api/admin/models/{$this->gt->id}/images", ['type' => 'exterior', 'image' => UploadedFile::fake()->image('b.webp', 1600, 900)], ['Accept' => 'application/json'])
            ->assertCreated()->json('data');

        $path = ModelImage::find($first['id'])->image_path;
        $this->assertStringStartsWith("vehicles/{$this->gt->id}/", $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/vehicles/', $first['url']);

        $this->putJson("/api/admin/models/{$this->gt->id}/images/order", ['ids' => [$second['id'], $first['id']]])
            ->assertOk()->assertJsonPath('data.0.id', $second['id']);

        $this->deleteJson("/api/admin/images/{$first['id']}")->assertOk();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_uploads_accept_images_only(): void
    {
        $this->actingAsStaff();
        $url = "/api/admin/models/{$this->gt->id}/images";

        $this->post($url, ['type' => 'exterior', 'image' => UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);
        $this->post($url, ['type' => 'exterior', 'image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);
        $this->post($url, ['type' => 'exterior', 'image' => UploadedFile::fake()->image('huge.jpg')->size(9000)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);
        $this->post($url, ['type' => 'poster', 'image' => UploadedFile::fake()->image('ok.png')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_image_and_model_admin_endpoints_need_a_staff_role(): void
    {
        $this->post("/api/admin/models/{$this->gt->id}/images", ['type' => 'hero', 'image' => UploadedFile::fake()->image('h.jpg')], ['Accept' => 'application/json'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create()); // no role
        $this->post("/api/admin/models/{$this->gt->id}/images", ['type' => 'hero', 'image' => UploadedFile::fake()->image('h.jpg')], ['Accept' => 'application/json'])->assertForbidden();
        $this->deleteJson('/api/admin/models/'.$this->gt->id)->assertForbidden();
    }

    public function test_public_model_api_serves_gallery_and_3d_and_refreshes_after_admin_edits(): void
    {
        $this->getJson('/api/models/volterra-gt')->assertOk()
            ->assertJsonPath('data.hero_image', '/assets/images/models/volterra-gt.webp')
            ->assertJsonPath('data.model_3d', null)
            ->assertJsonMissingPath('data.status')
            ->assertJsonMissingPath('data.deleted_at');

        $this->actingAsStaff();
        $this->post("/api/admin/models/{$this->gt->id}/images", ['type' => 'hero', 'image' => UploadedFile::fake()->image('hero.jpg', 1600, 900)], ['Accept' => 'application/json'])->assertCreated();
        $this->putJson("/api/admin/models/{$this->gt->id}", ['model_3d' => '/models/volterra-gt.glb', 'horsepower' => 640])->assertOk();

        // Cached catalog was invalidated by the edits
        $this->getJson('/api/models/volterra-gt')->assertOk()
            ->assertJsonPath('data.horsepower', 640)
            ->assertJsonPath('data.model_3d', '/models/volterra-gt.glb')
            ->assertJson(fn ($json) => $json->where('data.hero_image', fn ($url) => str_contains($url, '/storage/vehicles/'))->etc());

        $this->putJson("/api/admin/models/{$this->gt->id}", ['model_3d' => '/models/car.exe'])->assertUnprocessable()->assertJsonValidationErrors(['model_3d']);
    }

    public function test_soft_deleted_models_leave_the_public_range_but_keep_their_inquiries(): void
    {
        $inquiry = Inquiry::create([...$this->inquiryPayload(), 'model_id' => $this->gt->id, 'estimated_price' => 1]);
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/models/{$this->gt->id}")->assertOk();

        $this->getJson('/api/models')->assertJsonCount(2, 'data');
        $this->getJson('/api/models/volterra-gt')->assertNotFound();
        $this->getJson("/api/admin/inquiries/{$inquiry->id}")->assertOk()->assertJsonPath('data.model_name', 'VOLTERRA GT');
    }

    // --- Pricing

    public function test_calculate_returns_a_server_side_breakdown(): void
    {
        $this->postJson('/api/configurations/calculate', [
            'model_id' => $this->gt->id,
            'exterior_color_id' => $this->optionId('exterior_color', 'blu'),
            'wheel_id' => $this->optionId('wheels', 'carbon'),
            'interior_id' => $this->optionId('interior', 'tan'),
            'estimated_price' => 1,
        ])->assertOk()->assertExactJson(['success' => true, 'data' => ['base_price' => 289000, 'options_total' => 5400 + 14500 + 2800, 'estimated_price' => 311700]]);
    }

    public function test_calculate_rejects_invalid_configurations(): void
    {
        // Wheel id used as a colour, unknown model, inactive interior
        ConfigurationOption::where(['type' => 'interior', 'code' => 'red'])->update(['status' => 'inactive']);

        $this->postJson('/api/configurations/calculate', [
            'model_id' => 999,
            'exterior_color_id' => $this->optionId('wheels', 'carbon'),
            'wheel_id' => $this->optionId('wheels', 'sport'),
            'interior_id' => $this->optionId('interior', 'red'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['model_id', 'exterior_color_id', 'interior_id'])->assertJsonMissingValidationErrors(['wheel_id']);
    }

    public function test_admin_price_changes_drive_saved_configurations(): void
    {
        ConfigurationOption::where(['type' => 'wheels', 'code' => 'carbon'])->update(['price' => 20000]);

        $this->postJson('/api/configurations', ['model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'carbon', 'interior' => 'tan', 'estimated_price' => 1])
            ->assertCreated()
            ->assertJsonPath('data.base_price', 289000)
            ->assertJsonPath('data.options_total', 5400 + 20000 + 2800)
            ->assertJsonPath('data.estimated_price', 289000 + 28200);
    }

    // --- Inquiries

    public function test_inquiry_is_priced_and_linked_server_side_and_notifies_the_admin(): void
    {
        Notification::fake();
        config(['volterra.admin_email' => 'sales@volterra.test']);

        $id = $this->postJson('/api/inquiries', $this->inquiryPayload(['estimated_price' => 1, 'model_name' => 'Fake', 'status' => 'closed']))
            ->assertCreated()->assertJsonPath('data.estimated_price', 311700)->json('data.id');

        $this->assertDatabaseHas('inquiries', ['id' => $id, 'model_id' => $this->gt->id, 'estimated_price' => 311700, 'status' => 'new']);
        Notification::assertSentOnDemand(NewInquiryNotification::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'sales@volterra.test' && $n->inquiry->id === $id);
    }

    public function test_mail_failure_never_breaks_the_inquiry(): void
    {
        config(['volterra.admin_email' => 'sales@volterra.test']);
        Notification::shouldReceive('route')->andThrow(new RuntimeException('SMTP down'));

        $this->postJson('/api/inquiries', $this->inquiryPayload())->assertCreated();
        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_inquiry_for_an_inactive_model_is_rejected(): void
    {
        $this->gt->update(['status' => 'inactive']);

        $this->postJson('/api/inquiries', $this->inquiryPayload())->assertUnprocessable()->assertJsonValidationErrors(['model']);
    }

    public function test_staff_can_add_internal_notes(): void
    {
        $inquiry = Inquiry::create([...$this->inquiryPayload(), 'model_id' => $this->gt->id, 'estimated_price' => 311700]);
        $this->actingAsStaff();

        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", ['internal_notes' => 'Called back, wants a test drive.'])
            ->assertOk()
            ->assertJsonPath('data.internal_notes', 'Called back, wants a test drive.')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.configuration.wheels', 'Carbon');

        $this->getJson('/api/admin/inquiries')->assertJsonMissingPath('data.0.configuration'); // list stays lean
    }

    // --- Rate limiting

    public function test_public_writes_are_rate_limited(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/configurations', ['model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'sport', 'interior' => 'black'])->assertCreated();
        }

        $this->postJson('/api/configurations', ['model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'sport', 'interior' => 'black'])
            ->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertHeader('Retry-After');
    }
}
