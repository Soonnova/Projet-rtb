<?php
declare(strict_types=1);

/**
 * RTB - Traitement securise des formulaires de contact
 * Compatible PHP 7.4 / 8.x
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Honeypot anti-spam : un robot qui remplit ce champ est ignore silencieusement.
if (!empty($_POST['website_url'])) {
    header('Location: index.html?status=success');
    exit;
}

$nom = isset($_POST['nom']) ? trim(strip_tags((string) $_POST['nom'])) : '';
$prenom = isset($_POST['prenom']) ? trim(strip_tags((string) $_POST['prenom'])) : '';
$telephone = isset($_POST['telephone']) ? trim(strip_tags((string) $_POST['telephone'])) : '';
$email = isset($_POST['email']) ? filter_var(trim((string) $_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$ville = isset($_POST['ville']) ? trim(strip_tags((string) $_POST['ville'])) : '';
$projet = isset($_POST['projet']) ? trim(strip_tags((string) $_POST['projet'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags((string) $_POST['message'])) : '';

if ($nom === '' || $telephone === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=error');
    exit;
}

$destinataire = 'contact@rtb.fr';
$sujet = 'Nouvelle demande de devis sur le site RTB - ' . $nom;

$corpsMessage = "Vous avez recu une nouvelle demande de devis depuis le site RTB :\n\n";
$corpsMessage .= 'Nom : ' . $nom . ($prenom !== '' ? ' ' . $prenom : '') . "\n";
$corpsMessage .= 'Telephone : ' . $telephone . "\n";
$corpsMessage .= 'E-mail : ' . $email . "\n";

if ($ville !== '') {
    $corpsMessage .= 'Commune : ' . $ville . "\n";
}

if ($projet !== '') {
    $corpsMessage .= 'Nature des travaux : ' . $projet . "\n";
}

$corpsMessage .= "\nMessage / Details :\n" . ($message !== '' ? $message : 'Aucun detail precise.') . "\n\n";
$corpsMessage .= 'Date de soumission : ' . date('d/m/Y H:i:s') . "\n";
$corpsMessage .= 'Adresse IP : ' . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";

$cleanEmail = str_replace(["\r", "\n"], '', $email);
$headers = implode("\r\n", [
    'From: contact@rtb.fr',
    'Reply-To: ' . $cleanEmail,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8',
]);

$envoi = @mail($destinataire, $sujet, $corpsMessage, $headers);

if ($envoi) {
    header('Location: index.html?status=success');
    exit;
}

header('Location: index.html?status=server_error');
exit;