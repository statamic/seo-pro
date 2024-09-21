<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seopro_site_link_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site')->index();
            $table->json('ignored_phrases');
            $table->float('keyword_threshold');
            $table->integer('min_internal_links');
            $table->integer('max_internal_links');
            $table->integer('min_external_links');
            $table->integer('max_external_links');
            $table->boolean('prevent_circular_links')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seopro_site_link_settings');
    }
};
