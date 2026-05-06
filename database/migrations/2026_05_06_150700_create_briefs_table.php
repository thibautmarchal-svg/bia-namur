<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');         // 1..53
            $table->unsignedSmallInteger('year');                // 2026...
            $table->string('slug', 60);                          // ex: 2026-W19
            $table->string('title');
            $table->text('intro_text')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->string('status', 30)->default('draft_ai')->index();   // draft_ai / pending_review / published / archived
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('selected_event_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['city_id', 'year', 'week_number']);
            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefs');
    }
};
