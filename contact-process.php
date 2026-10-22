<?php
declare(strict_types=1);

/**
 * RTB - Traitement securise des formulaires de contact
 * Compatible PHP 7.4 / 8.x
 */

$origine = isset($_POST['origine']) && $_POST['origine'] === 'contact' ? 'contact' : 'top';

$redirect = static function (string $status) use ($origine): void {
    header('Location: index.html?status=' . $status . '#' . $origine);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Honeypot anti-spam : un robot qui remplit ce champ est ignore silencieusement.
if (!empty($_POST['website_url'])) {
    $redirect('success');
}

$nom = isset($_POST['nom']) ? trim(strip_tags((string) $_POST['nom'])) : '';
$prenom = isset($_POST['prenom']) ? trim(strip_tags((string) $_POST['prenom'])) : '';
$telephone = isset($_POST['telephone']) ? trim(strip_tags((string) $_POST['telephone'])) : '';
$email = isset($_POST['email']) ? filter_var(trim((string) $_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$ville = isset($_POST['ville']) ? trim(strip_tags((string) $_POST['ville'])) : '';
$projet = isset($_POST['projet']) ? trim(strip_tags((string) $_POST['projet'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags((string) $_POST['message'])) : '';

if ($nom === '' || $telephone === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $redirect('error');
}

$nomComplet = trim($nom . ($prenom !== '' ? ' ' . $prenom : ''));
$dateEnvoi = date('d/m/Y') . ' - ' . date('H:i');
$sourceFormulaire = $origine === 'contact' ? 'Formulaire de contact' : 'Formulaire d accueil';
$details = $message !== '' ? $message : 'Aucun detail precise.';

$destinataire = 'contact@rtb.fr';
$sujet = 'Demande de devis RTB - ' . $nomComplet;

$lignesTexte = [
    'RTB - Rehabilitation & Transformation du Batiment',
    'Nouvelle demande de devis',
    '',
    'Recue le ' . $dateEnvoi,
    'Source : ' . $sourceFormulaire,
    '',
    'Coordonnees',
    'Nom : ' . $nomComplet,
    'Telephone : ' . $telephone,
    'E-mail : ' . $email,
];

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
$lignesTexte[] = 'Repondre directement a cet e-mail pour contacter le demandeur.';

$corpsTexte = implode("\n", $lignesTexte);

$e = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$ligneHtml = static function (string $libelle, string $valeur) use ($e): string {
    return '<tr>'
        . '<td style="padding:10px 0 8px;width:140px;color:#69757f;font-size:13px;font-family:Arial,Helvetica,sans-serif;vertical-align:top;">'
        . $e($libelle)
        . '</td>'
        . '<td style="padding:10px 0 8px;color:#1f2b35;font-size:15px;font-family:Arial,Helvetica,sans-serif;font-weight:700;">'
        . nl2br($e($valeur))
        . '</td>'
        . '</tr>';
};

$blocCoordonnees = $ligneHtml('Nom', $nomComplet)
    . $ligneHtml('Telephone', $telephone)
    . $ligneHtml('E-mail', $email);

if ($ville !== '') {
    $blocCoordonnees .= $ligneHtml('Commune', $ville);
}

if ($projet !== '') {
    $blocCoordonnees .= $ligneHtml('Travaux', $projet);
}

$corpsHtml = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $e($sujet) . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1eb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #d8d3ca;">
                    <tr>
                        <td style="background:#1f2b35;padding:22px 28px;border-bottom:4px solid #f29a1f;">
                            <p style="margin:0 0 4px;color:#ffffff;font-size:20px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;font-weight:700;">RTB</p>
                            <p style="margin:0;color:#c9d4dc;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Rehabilitation &amp; Transformation du Batiment</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px 10px;">
                            <p style="margin:0 0 6px;color:#d07c0c;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;font-family:Arial,Helvetica,sans-serif;font-weight:700;">Nouvelle demande de devis</p>
                            <h1 style="margin:0 0 12px;color:#1f2b35;font-size:24px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;">Demande recue depuis le site</h1>
                            <p style="margin:0 0 18px;color:#69757f;font-size:14px;font-family:Arial,Helvetica,sans-serif;">
                                Recue le ' . $e($dateEnvoi) . ' &nbsp;|&nbsp; ' . $e($sourceFormulaire) . '
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e6e1d8;">
                                ' . $blocCoordonnees . '
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;">
                            <p style="margin:16px 0 8px;color:#69757f;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Message</p>
                            <div style="padding:16px 18px;background:#f8f5ef;border-left:3px solid #1495c9;color:#1f2b35;font-size:15px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;">
                                ' . nl2br($e($details)) . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f8f5ef;border-top:1px solid #e6e1d8;color:#69757f;font-size:12px;font-family:Arial,Helvetica,sans-serif;">
                            Vous pouvez repondre directement a cet e-mail pour contacter ' . $e($nomComplet) . '.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

$frontiere = 'rtb_' . bin2hex(random_bytes(12));
$cleanEmail = str_replace(["\r", "\n"], '', $email);

$headers = implode("\r\n", [
    'From: RTB <contact@rtb.fr>',
    'Reply-To: ' . $cleanEmail,
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $frontiere . '"',
]);

$corpsMessage = '--' . $frontiere . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $corpsTexte . "\r\n\r\n"
    . '--' . $frontiere . "\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $corpsHtml . "\r\n\r\n"
    . '--' . $frontiere . '--';

$envoi = @mail($destinataire, '=?UTF-8?B?' . base64_encode($sujet) . '?=', $corpsMessage, $headers);

if ($envoi) {
    $redirect('success');
}

$redirect('server_error');
