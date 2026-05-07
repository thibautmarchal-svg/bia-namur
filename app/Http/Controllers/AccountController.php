<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Favorite;
use App\Models\MagicLink;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page personnelle utilisateur : visualisation + export RGPD + suppression
 * de compte. Conforme au brief §10 RGPD (droit d'acces et droit a l'oubli).
 *
 * - GET  /mon-compte             : page de profil
 * - PUT  /mon-compte             : update nom + locale (email immutable)
 * - GET  /me/export              : telechargement JSON de toutes les donnees
 * - POST /me/delete              : suppression avec confirmation email
 */
class AccountController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('Account/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale ?? 'fr',
                'subscription_tier' => $user->subscription_tier,
                'role' => $user->role,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'stats' => [
                'contributions' => $user->contributions()->count(),
                'favorites' => $user->favorites()->count(),
                'push_subscriptions' => PushSubscription::where('user_id', $user->id)->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'in:fr,en,nl'],
        ]);

        Auth::user()->update($data);

        return back(303)->with('flash', [
            'type' => 'success',
            'message' => 'Profil mis à jour.',
        ]);
    }

    /**
     * Export RGPD : toutes les donnees personnelles dans un JSON unique.
     * Inclut profil + contributions + favoris + push subs. Pas de bcrypt
     * du password ni de stripe_customer_id (PII sensible).
     */
    public function export(): JsonResponse
    {
        $user = Auth::user();

        $contributions = $user->contributions()
            ->select('id', 'type', 'payload', 'status', 'created_at', 'reviewed_at', 'reviewer_notes')
            ->get();

        $favorites = $user->favorites()
            ->with('favoritable:id,slug')
            ->select('id', 'favoritable_type', 'favoritable_id', 'created_at')
            ->get()
            ->map(fn ($fav) => [
                'type' => str_replace('App\\Models\\', '', $fav->favoritable_type),
                'slug' => $fav->favoritable?->slug,
                'created_at' => $fav->created_at?->toIso8601String(),
            ]);

        $pushSubscriptions = PushSubscription::where('user_id', $user->id)
            ->select('id', 'user_agent', 'last_used_at', 'created_at')
            ->get();

        $payload = [
            'export_format_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'role' => $user->role,
                'subscription_tier' => $user->subscription_tier,
                'subscription_started_at' => $user->subscription_started_at?->toIso8601String(),
                'subscription_renews_at' => $user->subscription_renews_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'contributions' => $contributions,
            'favorites' => $favorites,
            'push_subscriptions' => $pushSubscriptions,
        ];

        Log::channel('moderation')->info('account.exported', [
            'user_id' => $user->id,
            'contributions_count' => $contributions->count(),
            'favorites_count' => $favorites->count(),
        ]);

        $filename = 'bia-namur-export-'.$user->id.'-'.now()->format('Y-m-d').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Suppression compte avec confirmation par email saisi (anti-clic accidentel).
     *
     * Strategie de suppression :
     *  - Contributions : anonymisees (user_id=null, payload garde le contenu
     *    car ce sont des donnees editoriales — un lieu propose qui a ete
     *    publie ne doit pas disparaitre. Le contributor_email/name sont
     *    purges du payload.)
     *  - Favoris : delete cascade
     *  - Push subscriptions : delete cascade
     *  - Magic links pendants : delete (cascade BDD)
     *  - User : soft delete (le softDeletes est deja active sur le modele)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'confirm_email' => ['required', 'string'],
        ]);

        if (strtolower(trim($request->string('confirm_email'))) !== strtolower($user->email)) {
            throw ValidationException::withMessages([
                'confirm_email' => "L'email saisi ne correspond pas à ton adresse de compte.",
            ]);
        }

        $userId = $user->id;
        $userEmail = $user->email;

        DB::transaction(function () use ($user) {
            // Contributions : anonymisation (on garde le contenu editorial)
            Contribution::where('user_id', $user->id)->update([
                'user_id' => null,
                'payload->contributor_email' => null,
                'payload->contributor_name' => null,
            ]);

            // Favoris + push : hard delete (donnees purement perso)
            Favorite::where('user_id', $user->id)->delete();
            PushSubscription::where('user_id', $user->id)->delete();
            MagicLink::where('user_id', $user->id)->delete();

            $user->delete();    // soft delete (trait SoftDeletes sur User)
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::channel('moderation')->info('account.deleted', [
            'user_id' => $userId,
            'email_hash' => sha1($userEmail),
        ]);

        return redirect('/')->with('flash', [
            'type' => 'success',
            'message' => 'Compte supprimé. Merci d\'être passé chez Bia Namur.',
        ]);
    }
}
