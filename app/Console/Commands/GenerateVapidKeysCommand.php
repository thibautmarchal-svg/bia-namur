<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Genere une paire de cles VAPID a coller dans .env :
 *   BIA_VAPID_PUBLIC_KEY=...
 *   BIA_VAPID_PRIVATE_KEY=...
 *   BIA_VAPID_SUBJECT=mailto:contact@bianamur.be
 *
 * Une seule paire est genere PAR projet (pas par environnement) :
 * la cle publique est exposee aux navigateurs au moment du subscribe,
 * et chaque endpoint push est lie a cette cle. Si on regenere les cles,
 * tous les abonnes existants doivent re-souscrire.
 */
class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'bia:vapid:generate';

    protected $description = 'Genere une paire de cles VAPID pour les push notifications PWA';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\RuntimeException $e) {
            $this->error('OpenSSL EC key generation failed.');
            $this->newLine();
            $this->line('Sur Windows + Laragon, definir OPENSSL_CONF avant la commande :');
            $this->line('  $env:OPENSSL_CONF = "C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\extras\\ssl\\openssl.cnf"');
            $this->line('Sur Linux prod : verifier que openssl-dev est installe et openssl.cnf accessible.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Paire VAPID generee. A coller dans .env :');
        $this->newLine();
        $this->line('BIA_VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('BIA_VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('BIA_VAPID_SUBJECT=mailto:contact@bianamur.be');
        $this->newLine();
        $this->warn('ATTENTION : ne regenere pas ces cles si des utilisateurs sont deja abonnes.');
        $this->warn('Une regeneration invalide tous les abonnements existants.');

        return self::SUCCESS;
    }
}
