<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('member')->index()->after('email');
            $table->string('locale', 10)->default('fr')->after('role');
            $table->json('preferences')->nullable()->after('locale');
            $table->string('subscription_tier', 20)->default('free')->index()->after('preferences');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_tier');
            $table->timestamp('subscription_renews_at')->nullable()->after('subscription_started_at');
            $table->string('stripe_customer_id', 100)->nullable()->after('subscription_renews_at');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'role',
                'locale',
                'preferences',
                'subscription_tier',
                'subscription_started_at',
                'subscription_renews_at',
                'stripe_customer_id',
            ]);
        });
    }
};
