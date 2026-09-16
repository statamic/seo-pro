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
        Schema::create('seo_pro_dead_links', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->string('url')->unique();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('error')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->dateTime('checked_at')->nullable();
            $table->dateTime('next_check_at')->nullable()->index();
            $table->dateTime('notified_at')->nullable();
            $table->json('references');
            $table->json('data');
            $table->timestamps();

            $table->index(['site', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pro_dead_links');
    }
};
