<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            // Nullable so existing rows stay valid
            $table->unsignedInteger('base_price')->nullable()->after('interior');
            $table->unsignedInteger('options_total')->nullable()->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'options_total']);
        });
    }
};
