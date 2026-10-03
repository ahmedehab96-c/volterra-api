<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // exterior_color | wheels | interior
            $table->string('code', 32); // id used by the front end, e.g. "blu"
            $table->string('name', 100);
            $table->unsignedInteger('price')->default(0);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['type', 'code']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_options');
    }
};
