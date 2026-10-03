<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30);
            $table->string('country', 100);
            // Model slug as submitted, kept as text so inquiries survive model changes
            $table->string('model', 64)->index();
            $table->string('exterior_color', 32);
            $table->string('wheels', 32);
            $table->string('interior', 32);
            $table->unsignedInteger('estimated_price')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
