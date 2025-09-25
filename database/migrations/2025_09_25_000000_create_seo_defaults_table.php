<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_defaults', function (Blueprint $table) {
            $table->id();
            $table->jsonb('data');
            $table->timestamps();
        });

        DB::table('seo_defaults')->insert([
            'data' => '{"image": false, "title": "@seo:title", "priority": 0.5, "site_name": "", "description": "@seo:content", "canonical_url": "@seo:permalink", "twitter_handle": "", "change_frequency": "monthly", "site_name_position": "after", "site_name_separator": "|"}',
            'created_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_defaults');
    }
};
