<?php

namespace Tests\Feature;

use Database\Seeders\CarModelSeeder;
use Database\Seeders\ConfigurationOptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CarModelSeeder::class, ConfigurationOptionSeeder::class]);
    }

    public function test_it_saves_a_configuration_with_a_server_side_estimate(): void
    {
        $this->postJson('/api/configurations', [
            'model' => 'volterra-gt',
            'exterior_color' => 'blu',
            'wheels' => 'carbon',
            'interior' => 'tan',
            'estimated_price' => 1, // ignored: the server computes the price
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.model', 'volterra-gt')
            ->assertJsonPath('data.reference', 'VC-000001')
            ->assertJsonPath('data.estimated_price', 289000 + 5400 + 14500 + 2800);

        $this->assertDatabaseHas('configurations', ['exterior_color' => 'blu', 'wheels' => 'carbon', 'interior' => 'tan', 'estimated_price' => 311700]);
    }

    public function test_it_rejects_unknown_options_and_models(): void
    {
        $this->postJson('/api/configurations', [
            'model' => 'volterra-z',
            'exterior_color' => 'neon',
            'wheels' => 'chrome',
            'interior' => 'plaid',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['model', 'exterior_color', 'wheels', 'interior']);

        $this->assertDatabaseCount('configurations', 0);
    }
}
