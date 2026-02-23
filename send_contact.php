<?php
/**
 * ══════════════════════════════════════════════
 * JASMINE DOM PIED — send_contact.php
 * Envoi du formulaire de contact via PHPMailer
 * ══════════════════════════════════════════════
 *
 * Prérequis :
 *   composer require phpmailer/phpmailer
 *   → Le dossier vendor/ doit être à la racine du projet.
 *
 * Configuration SMTP : remplace les valeurs ci-dessous
 * par celles de ton hébergeur ou de ton service SMTP
 * (ex. Gmail, Mailgun, OVH, etc.)
 * ══════════════════════════════════════════════
 */

// ─────────────────────────────────────────────
// 1. HEADERS JSON + sécurité CORS
// ─────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Autoriser uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// ─────────────────────────────────────────────
// 2. CHARGEMENT PHPMAILER
// ─────────────────────────────────────────────
// Décommente le bon chemin selon ta structure
require_once __DIR__ . '/vendor/autoload.php';
// ou si tu n'utilises pas Composer :
// require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
// require_once __DIR__ . '/PHPMailer/src/SMTP.php';
// require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ─────────────────────────────────────────────
// 3. RÉCUPÉRATION & SANITISATION DES CHAMPS
// ─────────────────────────────────────────────
function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$nom        = clean($_POST['nom']        ?? '');
$email      = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$objet      = clean($_POST['objet']      ?? 'Non précisé');
$message    = clean($_POST['message']    ?? '');
$conditions = isset($_POST['conditions']); // checkbox

// ─────────────────────────────────────────────
// 4. VALIDATION SERVEUR
// ─────────────────────────────────────────────
$errors = [];

if (empty($nom)) {
    $errors[] = 'Le prénom est requis.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Adresse email invalide.';
}

if (empty($message) || mb_strlen($message) < 10) {
    $errors[] = 'Le message doit contenir au moins 10 caractères.';
}

if (!$conditions) {
    $errors[] = 'Tu dois accepter les conditions.';
}

// Protection anti-spam simple (champ honeypot optionnel)
if (!empty($_POST['website'] ?? '')) {
    // Bot détecté — on répond OK pour ne pas le signaler
    echo json_encode(['success' => true]);
    exit;
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ─────────────────────────────────────────────
// 5. CONFIGURATION PHPMAILER
// ─────────────────────────────────────────────
$mail = new PHPMailer(true); // true = exceptions activées

try {
    // ── Serveur SMTP ──────────────────────────
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';       // ← Ton serveur SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@example.com';    // ← Identifiant SMTP
    $mail->Password   = 'TON_MOT_DE_PASSE_SMTP';  // ← Mot de passe SMTP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou SMTPS pour port 465
    $mail->Port       = 587;                      // 587 (TLS) ou 465 (SSL)
    $mail->CharSet    = 'UTF-8';

    // ── Expéditeur & Destinataire ─────────────
    $mail->setFrom('noreply@example.com', 'Site Jasmine Dom Pied');
    $mail->addAddress('jasminedompied@example.com', 'Jasmine Dom Pied'); // ← Adresse de réception
    $mail->addReplyTo($email, $nom); // Répondre directement à l'expéditeur

    // ── Contenu du mail ───────────────────────
    $mail->isHTML(true);
    $mail->Subject = "📩 Nouveau contact : {$nom} – {$objet}";

    // Corps HTML du mail
    $mail->Body = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
      <meta charset='UTF-8'>
      <style>
        body  { font-family: Georgia, serif; background:#0a0000; color:#F5F5F5; margin:0; padding:0; }
        .wrap { max-width:600px; margin:0 auto; padding:40px 30px; }
        h1    { color:#D4AF37; font-size:1.5rem; border-bottom:1px solid #8B0000; padding-bottom:12px; }
        .row  { margin:16px 0; }
        .lbl  { font-size:0.75rem; letter-spacing:0.15em; text-transform:uppercase; color:#aaa; }
        .val  { color:#F5F5F5; margin-top:4px; font-size:1rem; }
        .msg  { background:#110000; border-left:3px solid #8B0000; padding:16px; margin-top:8px; line-height:1.7; }
        .foot { margin-top:40px; font-size:0.75rem; color:#555; text-align:center; }
      </style>
    </head>
    <body>
      <div class='wrap'>
        <h1>Nouveau contact — Jasmine Dom Pied</h1>
        <div class='row'>
          <div class='lbl'>Prénom</div>
          <div class='val'>{$nom}</div>
        </div>
        <div class='row'>
          <div class='lbl'>Email</div>
          <div class='val'><a href='mailto:{$email}' style='color:#D4AF37'>{$email}</a></div>
        </div>
        <div class='row'>
          <div class='lbl'>Service demandé</div>
          <div class='val'>{$objet}</div>
        </div>
        <div class='row'>
          <div class='lbl'>Message</div>
          <div class='msg'>{$message}</div>
        </div>
        <div class='foot'>
          Message envoyé depuis jasminedompied.com · " . date('d/m/Y à H:i') . "
        </div>
      </div>
    </body>
    </html>";

    // Version texte brut (fallback)
    $mail->AltBody = "Nouveau contact depuis jasminedompied.com\n\n"
        . "Prénom : {$nom}\n"
        . "Email  : {$email}\n"
        . "Service: {$objet}\n\n"
        . "Message :\n{$message}\n\n"
        . "Reçu le " . date('d/m/Y à H:i');

    // ── Envoi ─────────────────────────────────
    $mail->send();

    // Confirmation au soumis (optionnel)
    $confirmation = new PHPMailer(true);
    $confirmation->isSMTP();
    $confirmation->Host       = 'smtp.example.com';
    $confirmation->SMTPAuth   = true;
    $confirmation->Username   = 'noreply@example.com';
    $confirmation->Password   = 'TON_MOT_DE_PASSE_SMTP';
    $confirmation->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $confirmation->Port       = 587;
    $confirmation->CharSet    = 'UTF-8';
    $confirmation->setFrom('noreply@example.com', 'Jasmine Dom Pied');
    $confirmation->addAddress($email, $nom);
    $confirmation->isHTML(true);
    $confirmation->Subject = "Ta demande a été reçue — Jasmine Dom Pied";
    $confirmation->Body = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head><meta charset='UTF-8'>
    <style>
      body { font-family:Georgia,serif; background:#0a0000; color:#F5F5F5; }
      .wrap { max-width:560px; margin:0 auto; padding:40px 30px; }
      h1   { color:#D4AF37; font-size:1.4rem; }
      p    { color:#c0b8a8; line-height:1.8; }
      .sig { margin-top:30px; color:#D4AF37; font-style:italic; }
    </style></head>
    <body><div class='wrap'>
      <h1>Demande reçue.</h1>
      <p>Ton message a été transmis. Si ton profil retient mon attention, je te contacterai.</p>
      <p>N'oublie pas : un tribute initial est requis avant toute réponse approfondie.</p>
      <div class='sig'>— Jasmine Dom Pied</div>
    </div></body></html>";
    $confirmation->AltBody = "Ta demande a été reçue. Si ton profil retient mon attention, je te contacterai.\n— Jasmine Dom Pied";
    $confirmation->send();

    // ── Réponse succès ────────────────────────
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès.']);

} catch (Exception $e) {
    // Log l'erreur serveur (ne jamais exposer les détails SMTP au client)
    error_log('[JasmineDomPied] PHPMailer error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi. Réessaie plus tard.']);
}
