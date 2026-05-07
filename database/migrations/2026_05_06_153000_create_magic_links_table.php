<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->index();              // toujours present, normalise lowercase
            $table->string('token_hash', 64)->unique();    // sha256 du token brut
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('requested_ip')->nullable();
            $table->string('requested_user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['email', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_links');
    }
};
