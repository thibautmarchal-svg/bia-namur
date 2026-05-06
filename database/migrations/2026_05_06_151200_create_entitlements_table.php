<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capabilities débloquées par tier d'abonnement (cf. brief §15.3).
 * EntitlementService::can($user, $code) lira cette table en M6+.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();        // unlimited_favorites / proximity_notifs / offline_stories...
            $table->string('label');
            $table->string('description', 500)->nullable();
            $table->string('tier_required', 20)->default('free')->index();   // free / plus / patron
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
