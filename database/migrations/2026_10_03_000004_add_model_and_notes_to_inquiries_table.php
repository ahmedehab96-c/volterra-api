<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Inquiries are business records: the link is cleared, never cascaded, if a model is ever removed for good
            $table->foreignId('model_id')->nullable()->after('country')->constrained('models')->nullOnDelete();
            $table->text('internal_notes')->nullable()->after('status');
        });

        // Backfill the link for existing inquiries from their model slug
        DB::table('inquiries')->whereNull('model_id')->orderBy('id')->each(function ($inquiry) {
            $modelId = DB::table('models')->where('slug', $inquiry->model)->value('id');
            if ($modelId) {
                DB::table('inquiries')->where('id', $inquiry->id)->update(['model_id' => $modelId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('model_id');
            $table->dropColumn('internal_notes');
        });
    }
};
