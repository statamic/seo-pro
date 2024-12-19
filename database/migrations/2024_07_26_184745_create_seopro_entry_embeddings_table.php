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
        Schema::create('seopro_entry_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id')->index();
            $table->string('site')->index();
            $table->string('collection')->index();
            $table->string('blueprint');
            $table->string('content_hash');
            $table->string('configuration_hash');
            $table->json('embedding');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seopro_entry_embeddings');
    }
};
