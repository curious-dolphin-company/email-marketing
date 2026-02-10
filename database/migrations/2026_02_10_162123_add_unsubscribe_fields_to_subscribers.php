<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token')->nullable()->unique();
        });

        /*
         * Backfill unsubscribe tokens for existing subscribers
         * Use chunking to avoid memory issues
         */
        DB::table('subscribers')
            ->whereNull('unsubscribe_token')
            ->orderBy('id')
            ->chunkById(500, function ($subscribers) {
                foreach ($subscribers as $subscriber) {
                    DB::table('subscribers')
                        ->where('id', $subscriber->id)
                        ->update([
                            'unsubscribe_token' => (string) Str::uuid(),
                        ]);
                }
            });

        // Make token non-nullable after backfill
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('unsubscribe_token')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('unsubscribed_at');
            $table->dropColumn('unsubscribe_token');
        });
    }
};
