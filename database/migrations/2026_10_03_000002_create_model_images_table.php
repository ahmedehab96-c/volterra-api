<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('models')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('type', 12); // hero | exterior | interior | detail
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['model_id', 'type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_images');
    }
};
