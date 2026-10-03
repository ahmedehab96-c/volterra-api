<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\ConfigurationOption;
use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\CarModelSeeder;
use Database\Seeders\ConfigurationOptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CarModelSeeder::class, ConfigurationOptionSeeder::class]);
    }

    private function staff(string $role = 'admin'): User
    {
        $user = User::factory()->create(['email' => "$role@volterra.test", 'password' => 'secret-pass']);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function inquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create([
            'name' => 'Alex Rivera', 'email' => 'alex@example.com', 'phone' => '+39 333 123 4567', 'country' => 'Italy',
            'model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'sport', 'interior' => 'black', 'estimated_price' => 294400,
            ...$overrides,
        ]);
    }

    // --- Authentication

    public function test_staff_can_log_in_and_receive_a_token_without_sensitive_fields(): void
    {
        $this->staff('editor');

        $this->postJson('/api/login', ['email' => 'editor@volterra.test', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'editor')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');
    }

    public function test_wrong_password_is_a_validation_error(): void
    {
        $this->staff();

        $this->postJson('/api/login', ['email' => 'admin@volterra.test', 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_accounts_without_a_staff_role_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'customer@volterra.test', 'password' => 'secret-pass']);

        $this->postJson('/api/login', ['email' => 'customer@volterra.test', 'password' => 'secret-pass'])->assertForbidden();
    }

    public function test_user_endpoint_and_logout_revoke_the_token(): void
    {
        $this->staff();
        $token = $this->postJson('/api/login', ['email' => 'admin@volterra.test', 'password' => 'secret-pass'])->json('data.token');

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('data.email', 'admin@volterra.test');
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    // --- Authorization

    public function test_admin_endpoints_require_a_token(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized()->assertJsonPath('success', false);
        $this->getJson('/api/admin/models')->assertUnauthorized();
    }

    public function test_authenticated_users_without_a_staff_role_are_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/models')->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_role_cannot_be_escalated_through_the_api(): void
    {
        $this->staff('editor');
        $token = $this->postJson('/api/login', ['email' => 'editor@volterra.test', 'password' => 'secret-pass', 'role' => 'admin'])->json('data.token');

        $this->withToken($token)->getJson('/api/user')->assertJsonPath('data.role', 'editor');
    }

    // --- Dashboard

    public function test_dashboard_statistics(): void
    {
        Sanctum::actingAs($this->staff());
        CarModel::where('slug', 'volterra-s')->update(['status' => 'inactive']);
        $this->inquiry();
        $this->inquiry(['status' => 'closed']);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertExactJson(['success' => true, 'data' => ['total_models' => 3, 'active_models' => 2, 'total_inquiries' => 2, 'new_inquiries' => 1]]);
    }

    // --- Model CRUD

    public function test_model_crud(): void
    {
        Sanctum::actingAs($this->staff());

        $payload = [
            'name' => 'VOLTERRA R', 'slug' => 'volterra-r', 'description' => 'Track special.', 'horsepower' => 800, 'torque' => 900,
            'acceleration' => 2.5, 'top_speed' => 340, 'base_price' => 450000, 'hero_image' => '/assets/images/models/volterra-r.webp', 'status' => 'inactive',
        ];

        $id = $this->postJson('/api/admin/models', $payload)->assertCreated()->assertJsonPath('data.slug', 'volterra-r')->json('data.id');
        $this->getJson("/api/admin/models/$id")->assertOk()->assertJsonPath('data.base_price', 450000);
        $this->getJson('/api/admin/models')->assertOk()->assertJsonCount(4, 'data');

        // Partial update (activate) and a field edit
        $this->putJson("/api/admin/models/$id", ['status' => 'active', 'horsepower' => 820])
            ->assertOk()->assertJsonPath('data.status', 'active')->assertJsonPath('data.horsepower', 820);

        // Inactive models disappear from the public API
        $this->putJson("/api/admin/models/$id", ['status' => 'inactive'])->assertOk();
        $this->getJson('/api/models/volterra-r')->assertNotFound();

        $this->deleteJson("/api/admin/models/$id")->assertOk();
        $this->assertSoftDeleted('models', ['id' => $id]); // business record kept
        $this->getJson("/api/admin/models/$id")->assertNotFound();
    }

    public function test_model_validation(): void
    {
        Sanctum::actingAs($this->staff());

        $this->postJson('/api/admin/models', ['slug' => 'volterra-gt', 'horsepower' => -5, 'status' => 'archived'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'description', 'horsepower', 'torque', 'acceleration', 'top_speed', 'base_price', 'hero_image', 'status']);
    }

    // --- Options

    public function test_option_crud_and_deactivated_options_are_rejected_publicly(): void
    {
        Sanctum::actingAs($this->staff('editor'));

        $id = $this->postJson('/api/admin/options', ['type' => 'wheels', 'code' => 'forged', 'name' => 'Forged', 'price' => 9900, 'status' => 'active'])
            ->assertCreated()->json('data.id');
        $this->postJson('/api/admin/options', ['type' => 'wheels', 'code' => 'forged', 'name' => 'Dup', 'price' => 1, 'status' => 'active'])
            ->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->getJson('/api/admin/options?type=wheels')->assertOk()->assertJsonCount(4, 'data');

        $this->putJson("/api/admin/options/$id", ['price' => 10500])->assertOk()->assertJsonPath('data.price', 10500);

        $blu = ConfigurationOption::where(['type' => 'exterior_color', 'code' => 'blu'])->first();
        $this->putJson("/api/admin/options/{$blu->id}", ['status' => 'inactive'])->assertOk();
        $this->postJson('/api/configurations', ['model' => 'volterra-gt', 'exterior_color' => 'blu', 'wheels' => 'forged', 'interior' => 'tan'])
            ->assertUnprocessable()->assertJsonValidationErrors(['exterior_color']);

        $this->deleteJson("/api/admin/options/$id")->assertOk();
        $this->assertDatabaseMissing('configuration_options', ['id' => $id]);
    }

    // --- Inquiries

    public function test_inquiry_listing_search_filters_and_newest_first(): void
    {
        Sanctum::actingAs($this->staff());
        $this->inquiry(['name' => 'Old Customer', 'created_at' => now()->subDay()]);
        $this->inquiry(['name' => 'Maria Rossi', 'email' => 'maria@example.com', 'model' => 'volterra-x', 'status' => 'contacted']);

        $this->getJson('/api/admin/inquiries')
            ->assertOk()->assertJsonPath('success', true)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Maria Rossi')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);

        $this->getJson('/api/admin/inquiries?q=maria')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/inquiries?status=new')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Old Customer');
        $this->getJson('/api/admin/inquiries?model=volterra-x')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/inquiries?status=lost')->assertUnprocessable();
    }

    public function test_inquiry_status_update(): void
    {
        Sanctum::actingAs($this->staff());
        $inquiry = $this->inquiry();

        $this->getJson("/api/admin/inquiries/{$inquiry->id}")->assertOk()->assertJsonPath('data.email', 'alex@example.com');
        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", ['status' => 'contacted'])->assertOk()->assertJsonPath('data.status', 'contacted');
        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", ['status' => 'archived'])->assertUnprocessable()->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id, 'status' => 'contacted']);
    }
}
