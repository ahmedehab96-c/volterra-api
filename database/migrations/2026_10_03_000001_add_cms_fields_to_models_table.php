<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->string('model_3d')->nullable()->after('hero_image'); // optional .glb/.gltf URL or path
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('model_3d');
        });
    }
};
