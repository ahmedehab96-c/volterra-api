<?php

namespace Database\Seeders;

use App\Models\ConfigurationOption;
use Illuminate\Database\Seeder;

/** Configurator options and prices (EUR), matching the front-end ids. */
class ConfigurationOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            'exterior_color' => [
                'signature' => ['Signature', 0],
                'rosso' => ['Rosso Corsa', 6800],
                'arancio' => ['Arancio Borealis', 4200],
                'giallo' => ['Giallo Modena', 4200],
                'verde' => ['Verde Mantis', 5400],
                'blu' => ['Blu Le Mans', 5400],
                'viola' => ['Viola Parsifae', 6800],
            ],
            'wheels' => [
                'sport' => ['Sport', 0],
                'performance' => ['Performance', 6900],
                'carbon' => ['Carbon', 14500],
            ],
            'interior' => [
                'black' => ['Black', 0],
                'tan' => ['Tan', 2800],
                'red' => ['Red', 3400],
            ],
        ];

        foreach ($options as $type => $items) {
            foreach ($items as $code => [$name, $price]) {
                ConfigurationOption::updateOrCreate(['type' => $type, 'code' => $code], ['name' => $name, 'price' => $price]);
            }
        }
    }
}
