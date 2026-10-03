<?php

namespace Database\Seeders;

use App\Models\CarModel;
use Illuminate\Database\Seeder;

/** The VOLTERRA range, matching the front-end mock specifications. */
class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'VOLTERRA X',
                'slug' => 'volterra-x',
                'tagline' => 'The electric flagship',
                'horsepower' => 1020,
                'torque' => 1400,
                'acceleration' => 2.1,
                'top_speed' => 310,
                'base_price' => 412000,
                'description' => 'Four motors, instant torque, and not a single drop of fuel. X is the quickest VOLTERRA we have ever built.',
                'hero_image' => '/assets/images/models/volterra-x.webp',
            ],
            [
                'name' => 'VOLTERRA GT',
                'slug' => 'volterra-gt',
                'tagline' => 'The continent crosser',
                'horsepower' => 620,
                'torque' => 850,
                'acceleration' => 3.2,
                'top_speed' => 320,
                'base_price' => 289000,
                'description' => 'Our grand tourer pairs the full 620 HP with adaptive air suspension and a cabin made for a thousand kilometres in a single sitting.',
                'hero_image' => '/assets/images/models/volterra-gt.webp',
            ],
            [
                'name' => 'VOLTERRA S',
                'slug' => 'volterra-s',
                'tagline' => 'Built for the circuit',
                'horsepower' => 710,
                'torque' => 800,
                'acceleration' => 2.8,
                'top_speed' => 330,
                'base_price' => 364000,
                'description' => 'Two hundred kilograms lighter, with a fixed carbon aero package and a track-tuned chassis. Road legal, barely.',
                'hero_image' => '/assets/images/models/volterra-s.webp',
            ],
        ];

        foreach ($models as $model) {
            CarModel::updateOrCreate(['slug' => $model['slug']], $model);
        }
    }
}
