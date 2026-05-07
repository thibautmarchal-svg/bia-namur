@php
$headlines = [
    'approved' => 'Ta contribution est en ligne',
    'needs_changes' => 'Une petite précision sur ta contribution',
    'rejected' => 'On revient vers toi',
];
$intros = [
    'approved' => "Bonne nouvelle : ta suggestion <strong>{$placeName}</strong> a passé la modération et figure désormais dans le carnet.",
    'needs_changes' => "Merci pour ta suggestion <strong>{$placeName}</strong>. Avant de la publier, on aurait besoin d'une précision.",
    'rejected' => "Merci d'avoir suggéré <strong>{$placeName}</strong>. Cette fois, on n'a pas pu la retenir telle quelle.",
];
$headline = $headlines[$decision];
$intro = $intros[$decision];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }} — Bia Namur</title>
</head>
<body style="margin:0;padding:0;background:#F5EDDC;font-family:Georgia,serif;color:#1A1410;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F5EDDC;padding:48px 24px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;background:#ffffff;border-radius:12px;padding:40px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 28px;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#C77F2C;">Bia Namur</p>
                            <h1 style="margin:0 0 24px;font-family:Georgia,serif;font-size:28px;font-weight:500;line-height:1.2;color:#1A1410;">
                                Salut {{ $contributorName }},
                            </h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                {!! $intro !!}
                            </p>

                            @if ($decision === 'approved' && $placeUrl)
                                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                    Le lieu est public, n'hésite pas à le partager autour de toi.
                                </p>
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:32px 0;">
                                    <tr>
                                        <td style="background:#C77F2C;border-radius:8px;">
                                            <a href="{{ $placeUrl }}"
                                               style="display:inline-block;padding:14px 28px;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:500;color:#F5EDDC;text-decoration:none;">
                                                Voir la fiche
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @elseif ($decision === 'approved')
                                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                    On finalise quelques détails de mise en page avant la publication. Tu recevras un message quand
                                    ce sera en ligne.
                                </p>
                            @endif

                            @if ($reviewerNote)
                                <div style="background:rgba(199,127,44,0.05);border:1px solid rgba(199,127,44,0.2);border-radius:8px;padding:20px 24px;margin:24px 0;">
                                    <p style="margin:0 0 8px;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#C77F2C;">
                                        Mot de l'équipe
                                    </p>
                                    <p style="margin:0;font-size:15px;line-height:1.7;color:#4A3F35;font-style:italic;">
                                        {{ $reviewerNote }}
                                    </p>
                                </div>
                            @endif

                            @if ($decision === 'needs_changes')
                                <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                    Tu peux soumettre une nouvelle version
                                    <a href="{{ url('/contribuer') }}" style="color:#C77F2C;">via le formulaire de contribution</a>
                                    en précisant que c'est une mise à jour de ta proposition précédente.
                                </p>
                            @endif

                            @if ($decision === 'rejected')
                                <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                    Pas de souci, ça arrive. Tu peux <a href="{{ url('/contribuer') }}" style="color:#C77F2C;">en proposer une autre</a> quand tu veux.
                                </p>
                            @endif

                            <hr style="border:0;border-top:1px solid #E8DDC5;margin:32px 0;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#8B7E72;font-style:italic;">
                                Merci d'avoir pris le temps. Ce sont les contributions des Namurois qui font vivre le carnet.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0;font-size:12px;color:#8B7E72;font-style:italic;">
                    Le carnet vivant des namurois.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
