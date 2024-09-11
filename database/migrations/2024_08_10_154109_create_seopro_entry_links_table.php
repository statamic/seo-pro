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
        Schema::create('seopro_entry_links', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id')->index();
            $table->string('cached_title');
            $table->string('cached_uri');
            $table->string('site')->index();
            $table->string('collection')->index();
            $table->string('content_hash');
            $table->longText('analyzed_content');
            $table->json('content_mapping');
            $table->integer('external_link_count');
            $table->integer('internal_link_count');
            $table->integer('inbound_internal_link_count');

            $table->json('external_links');
            $table->json('internal_links');

            $table->json('normalized_external_links');
            $table->json('normalized_internal_links');

            $table->boolean('can_be_suggested')->default(true)->index();
            $table->boolean('include_in_reporting')->default(true)->index();

            $table->json('ignored_entries');
            $table->json('ignored_phrases');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seopro_entry_links');
    }
};
