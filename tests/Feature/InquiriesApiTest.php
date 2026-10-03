<?php

namespace Tests\Feature;

use Database\Seeders\CarModelSeeder;
use Database\Seeders\ConfigurationOptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiriesApiTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Alex Rivera',
        'email' => 'alex@example.com',
        'phone' => '+39 333 123 4567',
        'country' => 'Italy',
        'model' => 'volterra-s',
        'exterior_color' => 'signature',
        'wheels' => 'performance',
        'interior' => 'red',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CarModelSeeder::class, ConfigurationOptionSeeder::class]);
    }

    public function test_it_stores_an_inquiry(): void
    {
        $this->postJson('/api/inquiries', $this->valid)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'VT-000001')
            ->assertJsonPath('data.estimated_price', 364000 + 6900 + 3400)
            ->assertJsonMissingPath('data.email');

        $this->assertDatabaseHas('inquiries', ['email' => 'alex@example.com', 'model' => 'volterra-s', 'country' => 'Italy']);
    }

    public function test_required_contact_fields_are_validated(): void
    {
        $this->postJson('/api/inquiries', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors'])
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'country', 'model', 'exterior_color', 'wheels', 'interior']);
    }

    public function test_field_formats_and_lengths_are_validated(): void
    {
        $this->postJson('/api/inquiries', [...$this->valid, 'email' => 'not-an-email', 'phone' => str_repeat('1', 31), 'country' => str_repeat('a', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone', 'country'])
            ->assertJsonMissingValidationErrors(['name', 'model']);

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_cors_allows_the_configured_front_end_origin(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $this->withHeaders(['Origin' => 'http://localhost:5173', 'Access-Control-Request-Method' => 'POST'])
            ->options('/api/inquiries')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
