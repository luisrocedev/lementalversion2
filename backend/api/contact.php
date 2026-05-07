<?php
/**
 * contact.php — Endpoint formulario de contacto
 *
 * POST /backend/api/contact.php
 * Recibe: name, email, company, service, message
 * Devuelve: JSON { success: bool, message: string }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function json_response(bool $success, string $message, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// ── Validación ────────────────────────────────────────────────

$name    = sanitize($_POST['name']    ?? '');
$email   = sanitize($_POST['email']   ?? '');
$company = sanitize($_POST['company'] ?? '');
$service = sanitize($_POST['service'] ?? '');
$message = sanitize($_POST['message'] ?? '');

if (empty($name) || strlen($name) < 2) {
    json_response(false, 'El nombre es obligatorio (mínimo 2 caracteres)', 422);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'El email no es válido', 422);
}

if (empty($message) || strlen($message) < 10) {
    json_response(false, 'El mensaje es obligatorio (mínimo 10 caracteres)', 422);
}

// Anti-spam básico: honeypot (si el campo oculto viene relleno, es bot)
$honeypot = $_POST['website'] ?? '';
if (!empty($honeypot)) {
    json_response(true, 'Mensaje recibido'); // silenciar al bot
}

// ── Guardar en log (temporal hasta tener DB) ─────────────────

$log_dir  = __DIR__ . '/../data';
$log_file = $log_dir . '/contacts.json';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$entry = [
    'id'        => uniqid('contact_', true),
    'timestamp' => date('c'),
    'name'      => $name,
    'email'     => $email,
    'company'   => $company,
    'service'   => $service,
    'message'   => $message,
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

$contacts = [];
if (file_exists($log_file)) {
    $raw = file_get_contents($log_file);
    $contacts = json_decode($raw, true) ?? [];
}

$contacts[] = $entry;
file_put_contents($log_file, json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ── Email (opcional — descomentar cuando haya servidor SMTP) ──
/*
$to      = 'hola@lemental.com';
$subject = "Nuevo contacto de {$name} — {$service}";
$body    = "Nombre: {$name}\nEmail: {$email}\nEmpresa: {$company}\nServicio: {$service}\n\nMensaje:\n{$message}";
$headers = "From: no-reply@lemental.com\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8";
mail($to, $subject, $body, $headers);
*/

// ── Respuesta ─────────────────────────────────────────────────

json_response(true, 'Mensaje recibido correctamente');
