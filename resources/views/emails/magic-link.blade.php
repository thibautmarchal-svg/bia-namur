<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ton lien Bia Namur</title>
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
                                Salut {{ $userName }},
                            </h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#4A3F35;">
                                Voici ton lien pour entrer dans Bia Namur. Il marche une seule fois et expire dans
                                <strong>{{ $expiresInMinutes }} minutes</strong>.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:32px 0;">
                                <tr>
                                    <td style="background:#C77F2C;border-radius:8px;">
                                        <a href="{{ $url }}"
                                           style="display:inline-block;padding:14px 28px;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:500;color:#F5EDDC;text-decoration:none;">
                                            Ouvrir Bia Namur
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#8B7E72;">
                                Si le bouton ne fonctionne pas, copie-colle ce lien dans ton navigateur :
                            </p>
                            <p style="margin:0 0 32px;font-size:13px;line-height:1.5;word-break:break-all;color:#4A3F35;">
                                <a href="{{ $url }}" style="color:#C77F2C;">{{ $url }}</a>
                            </p>
                            <hr style="border:0;border-top:1px solid #E8DDC5;margin:32px 0;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#8B7E72;font-style:italic;">
                                Tu n'as pas demandé ce lien ? Ignore simplement ce message, personne ne pourra se connecter sans cliquer.
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
