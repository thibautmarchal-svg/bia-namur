<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('viewable_type', 32);
            $table->unsignedBigInteger('viewable_id');
            $table->string('slug', 191);
            $table->string('ip_hash', 64)->nullable();
            $table->string('referrer_host', 191)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['viewable_type', 'viewable_id']);
            $table->index('viewed_at');
            $table->index(['ip_hash', 'viewable_type', 'viewable_id', 'viewed_at'], 'page_views_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
