<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60)->index();              // brief_weekly / story_generation / contribution_moderation / event_categorization
            $table->string('model_used', 60);                  // claude-sonnet-4-6 / claude-opus-4-7
            $table->string('prompt_template_version', 30)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 30)->default('pending')->index();   // pending / success / failed / timeout
            $table->text('error_message')->nullable();

            // Relation polymorphique vers le model concerné (Brief, Story, Contribution...)
            $table->nullableMorphs('related');

            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
