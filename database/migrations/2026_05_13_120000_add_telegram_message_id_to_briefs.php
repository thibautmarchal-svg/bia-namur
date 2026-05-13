<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne telegram_message_id sur briefs.
 *
 * Stocke l'ID du message Telegram envoye a l'admin lors de la
 * notification de validation. Utilise par le webhook pour editer le
 * message apres clic sur un bouton inline ("✅ Publie", "❌ Rejete").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefs', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_message_id')->nullable()->after('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('briefs', function (Blueprint $table) {
            $table->dropColumn('telegram_message_id');
        });
    }
};
