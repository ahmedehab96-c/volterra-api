<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->unsignedSmallInteger('horsepower');
            $table->unsignedSmallInteger('torque');
            $table->decimal('acceleration', 3, 1);
            $table->unsignedSmallInteger('top_speed');
            $table->unsignedInteger('price');
            $table->text('description');
            $table->string('hero_image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
