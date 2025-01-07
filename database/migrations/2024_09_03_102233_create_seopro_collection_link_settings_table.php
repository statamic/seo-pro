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
        Schema::create('seopro_collection_link_settings', function (Blueprint $table) {
            $table->id();
            $table->string('collection')->index();
            $table->boolean('linking_enabled')->index();

            $table->boolean('allow_linking_across_sites');
            $table->boolean('allow_linking_to_all_collections');
            $table->json('linkable_collections');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seopro_collection_link_settings');
    }
};
