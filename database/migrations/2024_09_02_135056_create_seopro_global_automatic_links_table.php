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
        Schema::create('seopro_global_automatic_links', function (Blueprint $table) {
            $table->id();
            $table->string('site')->nullable()->index();
            $table->boolean('is_active')->index();
            $table->string('link_text');
            $table->string('entry_id')->nullable()->index();
            $table->string('link_target');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seopro_global_automatic_links');
    }
};
