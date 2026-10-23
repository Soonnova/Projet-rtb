<?php
declare(strict_types=1);

/**
 * RTB - Traitement securise des formulaires de contact
 * Compatible PHP 7.4 / 8.x
 */

// Heure française pour la date affichée dans le courriel et l'en-tête Date (indépendant du réglage du serveur).
date_default_timezone_set('Europe/Paris');

$origine = isset($_POST['origine']) && $_POST['origine'] === 'contact' ? 'contact' : 'top';

// Le formulaire de l'accueil envoie la demande sans recharger la page (fetch) : il attend alors du JSON.
$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

$redirect = static function (string $status) use ($origine, $wantsJson): void {
    if ($wantsJson) {
        http_response_code($status === 'success' ? 200 : ($status === 'error' ? 422 : 500));
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $status === 'success', 'error' => $status === 'success' ? null : $status]);
        exit;
    }

    // Le formulaire de devis de l'accueil porte l'ancre #devis.
    header('Location: index.html?status=' . $status . '#' . ($origine === 'contact' ? 'contact' : 'devis'));
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
$creneau = isset($_POST['creneau']) ? trim(strip_tags((string) $_POST['creneau'])) : '';

// Creneaux de rappel proposes par le formulaire : toute autre valeur est ignoree.
$creneauxAutorises = ['Le matin', 'Le midi', "L'apr\u{E8}s-midi", "En fin de journ\u{E9}e"];
if (!in_array($creneau, $creneauxAutorises, true)) {
    $creneau = '';
}

// Il faut un nom et au moins un moyen de contact valide : telephone OU e-mail.
$telephoneValide = $telephone !== '' && preg_match('/^\+?[0-9 .\-()]+$/', $telephone) === 1
    && strlen((string) preg_replace('/\D/', '', $telephone)) >= 9
    && strlen((string) preg_replace('/\D/', '', $telephone)) <= 15;
$emailValide = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

if (
    $nom === ''
    || ($telephone === '' && $email === '')
    || ($telephone !== '' && !$telephoneValide)
    || ($email !== '' && !$emailValide)
) {
    $redirect('error');
}

$nomComplet = trim($nom . ($prenom !== '' ? ' ' . $prenom : ''));
$dateEnvoi = date('d/m/Y') . ' - ' . date('H:i');
$sourceFormulaire = $origine === 'contact' ? 'Formulaire de contact' : 'Formulaire d\'accueil';
$details = $message !== '' ? $message : 'Aucun détail précisé.';

$destinataire = 'contact@rtb.fr';
$nomSujet = trim((string) preg_replace('/\s+/u', ' ', $nomComplet));
if (preg_match('/^.{0,40}/us', $nomSujet, $coupe) === 1) {
    $nomSujet = rtrim($coupe[0]);
}
$sujet = 'Demande de devis RTB - ' . $nomSujet;

$lignesTexte = [
    'RTB - Réhabilitation & Transformation du Bâtiment',
    'Nouvelle demande de devis',
    '',
    'Reçue le ' . $dateEnvoi,
    'Source : ' . $sourceFormulaire,
    '',
    'Coordonnées',
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
$lignesTexte[] = $email !== ''
    ? 'Répondez directement à cet e-mail pour contacter le demandeur.'
    : 'Pas d\'e-mail laissé : rappelez le demandeur au ' . $telephone . '.';

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
    . $ligneHtml('Téléphone', $telephone !== '' ? $telephone : 'Non renseigné')
    . $ligneHtml('E-mail', $email !== '' ? $email : 'Non renseigné');

if ($creneau !== '') {
    $blocCoordonnees .= $ligneHtml('Rappel souhaite', $creneau);
}

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
                            <p style="margin:0;color:#c9d4dc;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Réhabilitation &amp; Transformation du Bâtiment</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px 10px;">
                            <p style="margin:0 0 6px;color:#d07c0c;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;font-family:Arial,Helvetica,sans-serif;font-weight:700;">Nouvelle demande de devis</p>
                            <h1 style="margin:0 0 12px;color:#1f2b35;font-size:24px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;">Demande reçue depuis le site</h1>
                            <p style="margin:0 0 18px;color:#69757f;font-size:14px;font-family:Arial,Helvetica,sans-serif;">
                                Reçue le ' . $e($dateEnvoi) . ' &nbsp;|&nbsp; ' . $e($sourceFormulaire) . '
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
                            ' . ($email !== '' ? 'Vous pouvez répondre directement à cet e-mail pour contacter ' . $e($nomComplet) . '.' : 'Cette personne n\'a pas laissé d\'e-mail : rappelez-la au ' . $e($telephone) . '.') . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

$domaineExpediteur = 'rtb.fr';
$expediteur = 'contact@' . $domaineExpediteur;

// Les lignes de courriel doivent se terminer par CRLF ; le contenu UTF-8 est encodé en quoted-printable
// (lignes courtes, accepté par tous les serveurs, contrairement à 8bit qui dépend du relais).
$versCrlf = static function (string $texte): string {
    return str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $texte));
};

// Encode un en-tête non ASCII en mots RFC 2047 de moins de 75 caractères, sans couper un caractère UTF-8.
$enTeteEncode = static function (string $valeur): string {
    $valeur = trim((string) preg_replace('/[\r\n]+/', ' ', $valeur));
    if (preg_match('/^[\x20-\x7E]*$/', $valeur) === 1) {
        return $valeur;
    }
    $mots = [];
    $courant = '';
    foreach ((array) preg_split('//u', $valeur, -1, PREG_SPLIT_NO_EMPTY) as $caractere) {
        if (strlen($courant . $caractere) > 42) {
            $mots[] = '=?UTF-8?B?' . base64_encode($courant) . '?=';
            $courant = '';
        }
        $courant .= $caractere;
    }
    if ($courant !== '') {
        $mots[] = '=?UTF-8?B?' . base64_encode($courant) . '?=';
    }
    return implode("\r\n ", $mots);
};

$frontiere = 'rtb_' . bin2hex(random_bytes(12));
$cleanEmail = str_replace(["\r", "\n"], '', $email);

$enTetes = [
    'From: RTB <' . $expediteur . '>',
];
if ($cleanEmail !== '') {
    $enTetes[] = 'Reply-To: ' . $cleanEmail;
}
$enTetes[] = 'Date: ' . date('r');
$enTetes[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domaineExpediteur . '>';
$enTetes[] = 'MIME-Version: 1.0';
$enTetes[] = 'Content-Type: multipart/alternative; boundary="' . $frontiere . '"';
$headers = implode("\r\n", $enTetes);

$corpsMessage = '--' . $frontiere . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
    . quoted_printable_encode($versCrlf($corpsTexte)) . "\r\n\r\n"
    . '--' . $frontiere . "\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
    . quoted_printable_encode($versCrlf($corpsHtml)) . "\r\n\r\n"
    . '--' . $frontiere . '--';

$sujetEncode = $enTeteEncode($sujet);

// 5e paramètre : adresse d'enveloppe (-f), celle du domaine, pour que SPF/DKIM et les retours d'erreur
// portent sur rtb.fr. Si l'hébergeur refuse ce paramètre, on réessaie une fois sans lui.
$envoi = @mail($destinataire, $sujetEncode, $corpsMessage, $headers, '-f' . $expediteur);
if (!$envoi) {
    $envoi = @mail($destinataire, $sujetEncode, $corpsMessage, $headers);
}

if ($envoi) {
    $redirect('success');
}

$redirect('server_error');
