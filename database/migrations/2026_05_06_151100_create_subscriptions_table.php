<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préparation freemium / Bia + (cf. brief §15.3).
 * Activation effective à M6, table créée dès M1 pour éviter migration disruptive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 50);                  // plus_monthly / plus_yearly / patron
            $table->string('status', 20)->index();       // active / canceled / past_due / paused
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('stripe_subscription_id', 255)->nullable()->unique();
            $table->string('payment_method', 50)->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
