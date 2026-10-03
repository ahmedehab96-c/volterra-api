<?php

namespace Tests\Feature;

use Database\Seeders\CarModelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CarModelSeeder::class);
    }

    public function test_it_lists_the_range(): void
    {
        $this->getJson('/api/models')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['success', 'data' => [['slug', 'name', 'tagline', 'horsepower', 'torque', 'acceleration', 'top_speed', 'price', 'description', 'hero_image']]])
            ->assertJsonPath('data.0.slug', 'volterra-x');
    }

    public function test_it_shows_one_model_by_slug(): void
    {
        $this->getJson('/api/models/volterra-gt')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'VOLTERRA GT')
            ->assertJsonPath('data.horsepower', 620)
            ->assertJsonPath('data.torque', 850)
            ->assertJsonPath('data.acceleration', 3.2)
            ->assertJsonPath('data.top_speed', 320);
    }

    public function test_unknown_model_returns_a_clean_404(): void
    {
        $this->getJson('/api/models/volterra-z')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Not found.']);
    }
}
