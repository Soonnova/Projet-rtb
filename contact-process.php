<?php
/**
 * RTB - Traitement sécurisé des formulaires de contact
 * Compatible PHP 7.4 / 8.x
 */

// Empêcher l'accès direct en GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// 1. Protection Anti-Spam (Honeypot) : si le champ invisible est rempli, on bloque silencieusement
if (!empty($_POST['website_url'])) {
    header('Location: index.html?status=success');
    exit;
}

// 2. Nettoyage et assainissement strict des données
$nom       = isset($_POST['nom']) ? trim(strip_tags($_POST['nom'])) : '';
$prenom    = isset($_POST['prenom']) ? trim(strip_tags($_POST['prenom'])) : '';
$telephone = isset($_POST['telephone']) ? trim(strip_tags($_POST['telephone'])) : '';
$email     = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$ville     = isset($_POST['ville']) ? trim(strip_tags($_POST['ville'])) : '';
$projet    = isset($_POST['projet']) ? trim(strip_tags($_POST['projet'])) : '';
$message   = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// 3. Validation des champs obligatoires
if (empty($nom) || empty($telephone) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=error');
    exit;
}

// 4. Construction de l'e-mail
$destinataire = 'contact@rtb.fr';
$sujet        = 'Nouvelle demande de devis sur le site RTB - ' . $nom;

$corpsMessage = "Vous avez reçu une nouvelle demande de devis depuis le site RTB :\n\n";
$corpsMessage .= "Nom : " . $nom . ($prenom ? " " . $prenom : "") . "\n";
$corpsMessage .= "Téléphone : " . $telephone . "\n";
$corpsMessage .= "E-mail : " . $email . "\n";
if (!empty($ville)) {
    $corpsMessage .= "Commune : " . $ville . "\n";
}
if (!empty($projet)) {
    $corpsMessage .= "Nature des travaux : " . $projet . "\n";
}
$corpsMessage .= "\nMessage / Détails :\n" . (!empty($message) ? $message : 'Aucun détail précisé.') . "\n\n";
$corpsMessage .= "Date de soumission : " . date('d/m/Y H:i:s') . "\n";
$corpsMessage .= "Adresse IP : " . $_SERVER['REMOTE_ADDR'] . "\n";

// 5. En-têtes sécurisés (protection contre l'injection CRLF)
$cleanEmail = str_replace(array("\r", "\n"), '', $email);
$headers    = array(
    'From'         => 'no-reply@rehabilitation-transformation-batiment.fr',
    'Reply-To'     => $cleanEmail,
    'X-Mailer'     => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8'
);

// 6. Expédition
$envoi = @mail($destinataire, $sujet, $corpsMessage, $headers);

if ($envoi) {
    header('Location: index.html?status=success');
} else {
    header('Location: index.html?status=server_error');
}
exit;<?php
/**
 * RTB - Traitement sécurisé des formulaires de contact
 * Compatible PHP 7.4 / 8.x
 */

// Empêcher l'accès direct en GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// 1. Protection Anti-Spam (Honeypot) : si le champ invisible est rempli, on bloque silencieusement
if (!empty($_POST['website_url'])) {
    header('Location: index.html?status=success');
    exit;
}

// 2. Nettoyage et assainissement strict des données
$nom       = isset($_POST['nom']) ? trim(strip_tags($_POST['nom'])) : '';
$prenom    = isset($_POST['prenom']) ? trim(strip_tags($_POST['prenom'])) : '';
$telephone = isset($_POST['telephone']) ? trim(strip_tags($_POST['telephone'])) : '';
$email     = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$ville     = isset($_POST['ville']) ? trim(strip_tags($_POST['ville'])) : '';
$projet    = isset($_POST['projet']) ? trim(strip_tags($_POST['projet'])) : '';
$message   = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// 3. Validation des champs obligatoires
if (empty($nom) || empty($telephone) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=error');
    exit;
}

// 4. Construction de l'e-mail
$destinataire = 'contact@rtb.fr';
$sujet        = 'Nouvelle demande de devis sur le site RTB - ' . $nom;

$corpsMessage = "Vous avez reçu une nouvelle demande de devis depuis le site RTB :\n\n";
$corpsMessage .= "Nom : " . $nom . ($prenom ? " " . $prenom : "") . "\n";
$corpsMessage .= "Téléphone : " . $telephone . "\n";
$corpsMessage .= "E-mail : " . $email . "\n";
if (!empty($ville)) {
    $corpsMessage .= "Commune : " . $ville . "\n";
}
if (!empty($projet)) {
    $corpsMessage .= "Nature des travaux : " . $projet . "\n";
}
$corpsMessage .= "\nMessage / Détails :\n" . (!empty($message) ? $message : 'Aucun détail précisé.') . "\n\n";
$corpsMessage .= "Date de soumission : " . date('d/m/Y H:i:s') . "\n";
$corpsMessage .= "Adresse IP : " . $_SERVER['REMOTE_ADDR'] . "\n";

// 5. En-têtes sécurisés (protection contre l'injection CRLF)
$cleanEmail = str_replace(array("\r", "\n"), '', $email);
$headers    = array(
    'From'         => 'no-reply@rehabilitation-transformation-batiment.fr',
    'Reply-To'     => $cleanEmail,
    'X-Mailer'     => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8'
);

// 6. Expédition
$envoi = @mail($destinataire, $sujet, $corpsMessage, $headers);

if ($envoi) {
    header('Location: index.html?status=success');
} else {
    header('Location: index.html?status=server_error');
}
exit;