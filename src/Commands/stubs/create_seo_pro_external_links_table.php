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
        Schema::create('seo_pro_external_links', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->text('url');
            $table->char('url_hash', 64);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('error')->nullable();
            $table->dateTime('broken_since')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->dateTime('next_check_at')->nullable()->index();
            $table->dateTime('notified_at')->nullable();
            $table->json('references');
            $table->json('subjects');
            $table->timestamps();

            $table->unique(['site', 'url_hash']);
            $table->index(['site', 'broken_since']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pro_external_links');
    }
};
