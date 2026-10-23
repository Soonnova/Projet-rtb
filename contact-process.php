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

require __DIR__ . '/contact-mail.php';

$mail = rtb_build_lead_mail([
    'nomComplet' => $nomComplet,
    'telephone' => $telephone,
    'email' => $emailValide ? $email : '',
    'ville' => $ville,
    'projet' => $projet,
    'details' => $message,
    'creneau' => $creneau,
    'dateEnvoi' => $dateEnvoi,
    'sourceFormulaire' => $sourceFormulaire,
]);

$destinataire = 'contact@rtb.fr';
$sujet = $mail['subject'];
$corpsTexte = $mail['text'];
$corpsHtml = $mail['html'];

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

$enTetes = [
    'From: RTB <' . $expediteur . '>',
    'Reply-To: RTB <' . $expediteur . '>',
];
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
