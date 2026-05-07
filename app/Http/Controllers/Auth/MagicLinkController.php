<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    public const EXPIRES_MINUTES = 15;

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'flash' => session('flash'),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = strtolower(trim($request->string('email')));

        $emailKey = 'magic-link-email:'.sha1($email);
        $ipKey = 'magic-link-ip:'.sha1($request->ip() ?? 'unknown');

        // 3 demandes par email/heure, 10 par IP/heure (cf. agent security-namur)
        if (RateLimiter::tooManyAttempts($emailKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de demandes. Réessaie dans une heure.',
            ]);
        }
        RateLimiter::hit($emailKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        // Auto-creation user si email inconnu (le magic link verifie l'email implicitement)
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => explode('@', $email)[0],
                'password' => bcrypt(\Illuminate\Support\Str::random(64)),    // jetable, jamais utilise
                'role' => User::ROLE_MEMBER,
                'locale' => 'fr',
                'subscription_tier' => User::TIER_FREE,
                'email_verified_at' => now(),
            ],
        );

        [$rawToken, $tokenHash] = MagicLink::generateToken();

        MagicLink::create([
            'user_id' => $user->id,
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            'requested_ip' => $request->ip(),
            'requested_user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        try {
            Mail::to($email)->send(new MagicLinkMail($rawToken, $user->name));
        } catch (\Throwable $e) {
            // Pas de leak d'erreur cote user (reponse identique pour valid/invalid)
            // Log channel security a creer en J5/J6
            Log::warning('magic_link.mail_failed', [
                'email_hash' => sha1($email),
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Si l'adresse existe, on t'a envoyé un lien. Il expire dans 15 minutes.",
        ]);
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $magicLink = MagicLink::where('token_hash', $tokenHash)->first();

        if (! $magicLink || ! $magicLink->isValid()) {
            return redirect()->route('login')->with('flash', [
                'type' => 'error',
                'message' => 'Ce lien est invalide ou expiré. Demande-en un nouveau.',
            ]);
        }

        $user = $magicLink->user;

        if (! $user) {
            return redirect()->route('login')->with('flash', [
                'type' => 'error',
                'message' => "Compte introuvable. Réessaie depuis l'accueil.",
            ]);
        }

        $magicLink->markUsed();

        // Invalider tous les autres magic links actifs pour cet email (1 lien actif a la fois)
        MagicLink::where('email', $magicLink->email)
            ->where('id', '!=', $magicLink->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $intended = $user->isAdmin() ? '/admin' : '/';

        return redirect()->intended($intended)->with('flash', [
            'type' => 'success',
            'message' => "Bienvenue, {$user->name}. À l'aise.",
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('flash', [
            'type' => 'success',
            'message' => 'À tantôt.',
        ]);
    }
}
