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
        Schema::create('seo_pro_reports', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('status');
            $table->float('score')->nullable();
            $table->integer('pages_crawled')->nullable();
            $table->integer('pages_actionable')->nullable();
            $table->json('results')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_pro_report_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('page_id');
            $table->json('data');
            $table->json('results')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'page_id']);
        });

        Schema::create('seo_pro_report_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->integer('chunk_id');
            $table->json('content_ids');
            $table->timestamps();

            $table->index('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pro_report_chunks');
        Schema::dropIfExists('seo_pro_report_pages');
        Schema::dropIfExists('seo_pro_reports');
    }
};
