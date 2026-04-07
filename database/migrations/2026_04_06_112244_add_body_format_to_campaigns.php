<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Campaign;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('template')->nullable();
        });

        /*
         * Backfill template for existing campaigns
         * Use chunking to avoid memory issues
         */
        DB::table('campaigns')
            ->whereNull('template')
            ->orderBy('id')
            ->chunkById(500, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    DB::table('campaigns')
                        ->where('id', $campaign->id)
                        ->update([
                            'template' => Campaign::TEMPLATE_TEXT_ONLY,
                        ]);
                }
            });

        // Make token non-nullable after backfill
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('template')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('template');
        });
    }
};
