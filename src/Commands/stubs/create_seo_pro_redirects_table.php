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
        Schema::create('seo_pro_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->string('source');
            $table->string('destination');
            $table->integer('response_code');
            $table->boolean('enabled');
            $table->integer('hits');
            $table->dateTime('last_hit_at')->nullable()->index();
            $table->json('data');
            $table->timestamps();

            $table->index(['site', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pro_redirects');
    }
};
