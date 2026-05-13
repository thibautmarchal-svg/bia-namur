<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContributionRequest;
use App\Jobs\ModerateContributionJob;
use App\Models\Contribution;
use App\Services\Media\PhotoUploadService;
use App\Support\Seo\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    /** Types autorises (cf. brief §6 + Place::types). */
    public const ALLOWED_TYPES = [
        'cafe' => 'Café',
        'restaurant' => 'Restaurant',
        'bar' => 'Bar',
        'boulangerie' => 'Boulangerie',
        'librairie' => 'Librairie',
        'patrimoine' => 'Patrimoine',
        'parc' => 'Parc',
        'marche' => 'Marché',
        'culture' => 'Lieu culturel',
        'hidden_gem' => 'Hidden gem',
    ];

    public function form(): Response
    {
        View::share('seo', SeoBuilder::forContribute());

        return Inertia::render('Contribute/Form', [
            'types' => collect(self::ALLOWED_TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    public function store(StoreContributionRequest $request, PhotoUploadService $photos): RedirectResponse
    {
        // Rate limit : 3 contributions par IP par 24h (cf. agent security-namur)
        $ipKey = 'contribution-ip:' . sha1($request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            $retryAfter = RateLimiter::availableIn($ipKey);

            throw ValidationException::withMessages([
                'name' => 'Tu as atteint la limite de 3 suggestions par jour. Reviens dans ' . ceil($retryAfter / 3600) . 'h.',
            ]);
        }

        $contribution = Contribution::create([
            'user_id' => $request->user()?->id,
            'type' => Contribution::TYPE_PLACE_SUGGESTION,
            'payload' => [
                'name' => $request->string('name')->trim()->toString(),
                'type' => $request->string('type')->toString(),
                'description' => $request->string('description')->trim()->toString(),
                'address' => $request->string('address')->trim()->toString() ?: null,
                'neighborhood' => $request->string('neighborhood')->trim()->toString() ?: null,
                'why' => $request->string('why')->trim()->toString() ?: null,
                // Contact contributeur — stocke pour suivi mais NE sera PAS envoye a Claude
                // (sanitize dans ModerateContributionJob::buildUserMessage).
                'contributor_email' => $request->string('contributor_email')->toString() ?: null,
                'contributor_name' => $request->string('contributor_name')->trim()->toString() ?: null,
            ],
            'status' => Contribution::STATUS_PENDING,
            'submitted_ip' => $request->ip(),
            'submitted_user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        // Photo upload (optionnel) : strip EXIF + resize + Photo polymorphique
        if ($request->hasFile('photo')) {
            try {
                $photos->storeFor(
                    file: $request->file('photo'),
                    uploadable: $contribution,
                    uploadedBy: $request->user()?->id,
                    credit: $request->string('contributor_name')->trim()->toString() ?: null,
                );
            } catch (\Throwable $e) {
                Log::channel('moderation')->warning('contribution.photo_upload_failed', [
                    'contribution_id' => $contribution->id,
                    'error' => $e->getMessage(),
                ]);
                // Pas de blocage : la contribution reste utile sans photo
            }
        }

        RateLimiter::hit($ipKey, 86400);    // 24h

        Log::channel('moderation')->info('contribution.submitted', [
            'contribution_id' => $contribution->id,
            'type' => $contribution->type,
            'ip_hash' => sha1($request->ip() ?? 'unknown'),
            'has_photo' => $request->hasFile('photo'),
        ]);

        // Dispatch en async — le moderateur recevra le score Claude sous quelques secondes
        ModerateContributionJob::dispatch($contribution->id);

        return redirect()->route('contribute.thanks');
    }

    public function thanks(): Response
    {
        return Inertia::render('Contribute/Thanks');
    }
}
