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
        Schema::create('seo_pro_errors', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->string('url');
            $table->integer('hits');
            $table->dateTime('last_hit_at')->nullable()->index();
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pro_errors');
    }
};
