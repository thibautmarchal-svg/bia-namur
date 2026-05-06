<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50)->index();   // place_suggestion / photo / correction / story_proposal
            $table->json('payload')->nullable();
            $table->foreignId('target_place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->foreignId('target_story_id')->nullable()->constrained('stories')->nullOnDelete();

            $table->unsignedTinyInteger('ai_score')->nullable();   // 0..100
            $table->json('ai_reasoning')->nullable();
            $table->string('ai_model', 60)->nullable();
            $table->string('ai_prompt_version', 30)->nullable();

            $table->string('status', 30)->default('pending')->index();   // pending / auto_approved / manual_review / rejected / merged
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();

            $table->ipAddress('submitted_ip')->nullable();
            $table->string('submitted_user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
