<?php
declare(strict_types=1);

/**
 * Construction du courriel interne envoyé à contact@rtb.fr.
 * Répondre à ce message doit rester dans la boîte RTB : pas de Reply-To client.
 */

function rtb_mail_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rtb_mail_tel_href(string $telephone): string
{
    $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';
    return $chiffres !== '' ? 'tel:' . $chiffres : '';
}

function rtb_mail_mailto_client(string $email): string
{
    return 'mailto:' . $email . '?subject=' . rawurlencode('RTB - Suite à votre demande de devis');
}

/**
 * @param array{
 *   nomComplet: string,
 *   telephone?: string,
 *   email?: string,
 *   ville?: string,
 *   projet?: string,
 *   details?: string,
 *   creneau?: string,
 *   dateEnvoi: string,
 *   sourceFormulaire: string
 * } $d
 * @return array{subject: string, text: string, html: string}
 */
function rtb_build_lead_mail(array $d): array
{
    $nomComplet = trim((string) ($d['nomComplet'] ?? ''));
    $telephone = trim((string) ($d['telephone'] ?? ''));
    $email = trim((string) ($d['email'] ?? ''));
    $ville = trim((string) ($d['ville'] ?? ''));
    $projet = trim((string) ($d['projet'] ?? ''));
    $details = trim((string) ($d['details'] ?? ''));
    $creneau = trim((string) ($d['creneau'] ?? ''));
    $dateEnvoi = (string) ($d['dateEnvoi'] ?? '');
    $sourceFormulaire = (string) ($d['sourceFormulaire'] ?? 'Formulaire du site');

    if ($details === '') {
        $details = 'Aucun détail précisé.';
    }

    $nomSujet = trim((string) preg_replace('/\s+/u', ' ', $nomComplet));
    if (preg_match('/^.{0,40}/us', $nomSujet, $coupe) === 1) {
        $nomSujet = rtrim($coupe[0]);
    }
    $sujet = 'Demande de devis RTB - ' . $nomSujet;

    $lignesTexte = [
        'MAIL INTERNE RTB',
        'La touche Répondre écrit à contact@rtb.fr, pas au client.',
        '',
        'Nouvelle demande de devis',
        'Reçue le ' . $dateEnvoi,
        'Source : ' . $sourceFormulaire,
        '',
        'Nom : ' . $nomComplet,
        'Téléphone : ' . ($telephone !== '' ? $telephone : 'Non renseigné'),
        'E-mail : ' . ($email !== '' ? $email : 'Non renseigné'),
    ];

    if ($creneau !== '') {
        $lignesTexte[] = 'Créneau de rappel souhaité : ' . $creneau;
    }
    if ($ville !== '') {
        $lignesTexte[] = 'Commune : ' . $ville;
    }
    if ($projet !== '') {
        $lignesTexte[] = 'Travaux : ' . $projet;
    }

    $lignesTexte[] = '';
    $lignesTexte[] = 'Message';
    $lignesTexte[] = $details;
    $lignesTexte[] = '';

    if ($telephone !== '') {
        $lignesTexte[] = 'Pour appeler : ' . $telephone;
    }
    if ($email !== '') {
        $lignesTexte[] = 'Pour écrire au client, ouvrez un nouveau message vers : ' . $email;
    }
    if ($telephone === '' && $email === '') {
        $lignesTexte[] = 'Aucun moyen de contact renseigné.';
    }

    $e = 'rtb_mail_escape';
    $telHref = $telephone !== '' ? rtb_mail_tel_href($telephone) : '';
    $mailHref = $email !== '' ? rtb_mail_mailto_client($email) : '';

    $ligne = static function (string $libelle, string $valeurHtml) use ($e): string {
        return '<tr>'
            . '<td style="padding:11px 0;width:150px;color:#5d6b75;font-size:13px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;vertical-align:top;border-bottom:1px solid #eee6d8;">'
            . $e($libelle)
            . '</td>'
            . '<td style="padding:11px 0;color:#1f2b35;font-size:16px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;font-weight:700;border-bottom:1px solid #eee6d8;">'
            . $valeurHtml
            . '</td>'
            . '</tr>';
    };

    $telCellule = $telephone !== ''
        ? '<a href="' . $e($telHref) . '" style="color:#0a6590;text-decoration:underline;">' . $e($telephone) . '</a>'
        : $e('Non renseigné');
    $emailCellule = $email !== ''
        ? '<a href="' . $e($mailHref) . '" style="color:#0a6590;text-decoration:underline;">' . $e($email) . '</a>'
        : $e('Non renseigné');

    $blocCoordonnees = $ligne('Nom', $e($nomComplet))
        . $ligne('Téléphone', $telCellule)
        . $ligne('E-mail', $emailCellule);

    if ($creneau !== '') {
        $blocCoordonnees .= $ligne('Rappel souhaité', $e($creneau));
    }
    if ($ville !== '') {
        $blocCoordonnees .= $ligne('Commune', $e($ville));
    }
    if ($projet !== '') {
        $blocCoordonnees .= $ligne('Travaux', $e($projet));
    }

    $boutons = '';
    if ($telHref !== '') {
        $boutons .= '<td style="padding:0 8px 10px 0;" valign="top">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="background:#f29a1f;border-radius:4px;">'
            . '<a href="' . $e($telHref) . '" style="display:inline-block;padding:13px 18px;color:#1f1f1f;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;">Appeler ' . $e($telephone) . '</a>'
            . '</td></tr></table></td>';
    }
    if ($mailHref !== '') {
        $boutons .= '<td style="padding:0 0 10px;" valign="top">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="background:#1495c9;border-radius:4px;">'
            . '<a href="' . $e($mailHref) . '" style="display:inline-block;padding:13px 18px;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;">Écrire au client</a>'
            . '</td></tr></table></td>';
    }

    $blocBoutons = $boutons !== ''
        ? '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:6px 0 4px;"><tr>' . $boutons . '</tr></table>'
        : '';

    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $e($sujet) . '</title>
</head>
<body style="margin:0;padding:0;background:#ece7de;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ece7de;padding:24px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d5cfc3;">
                    <tr>
                        <td style="background:#1f2b35;padding:20px 28px 18px;border-bottom:4px solid #f29a1f;">
                            <p style="margin:0 0 6px;color:#f29a1f;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;font-family:Arial,Helvetica,sans-serif;font-weight:700;">Mail interne</p>
                            <p style="margin:0 0 4px;color:#ffffff;font-size:22px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;font-weight:700;">RTB</p>
                            <p style="margin:0;color:#c5d0d8;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Réhabilitation &amp; Transformation du Bâtiment</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 8px;">
                            <p style="margin:0 0 8px;color:#8a5200;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;font-family:Arial,Helvetica,sans-serif;font-weight:700;">Nouvelle demande de devis</p>
                            <h1 style="margin:0 0 8px;color:#1f2b35;font-size:26px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;">' . $e($nomComplet) . '</h1>
                            <p style="margin:0 0 16px;color:#5d6b75;font-size:14px;font-family:Arial,Helvetica,sans-serif;">
                                Reçue le ' . $e($dateEnvoi) . ' &nbsp;·&nbsp; ' . $e($sourceFormulaire) . '
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff6e8;border:1px solid #f0d19a;">
                                <tr>
                                    <td style="padding:12px 14px;color:#6a4308;font-size:13px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;">
                                        La touche <strong>Répondre</strong> de votre messagerie écrit à <strong>contact@rtb.fr</strong>, pas au client.
                                        Pour le joindre, utilisez les boutons ci-dessous.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 6px;">
                            ' . $blocBoutons . '
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 6px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                ' . $blocCoordonnees . '
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 28px;">
                            <p style="margin:0 0 8px;color:#5d6b75;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Message du client</p>
                            <div style="padding:16px 18px;background:#f4fafc;border-left:4px solid #1495c9;color:#1f2b35;font-size:16px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;">
                                ' . nl2br($e($details)) . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px;background:#1f2b35;color:#c5d0d8;font-size:12px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;">
                            Notification automatique du site rtb.fr. Ce message ne doit pas être transféré au client tel quel.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return [
        'subject' => $sujet,
        'text' => implode("\n", $lignesTexte),
        'html' => $html,
    ];
}
