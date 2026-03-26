<?php
header('Content-Type: application/json; charset=utf-8');


$to_email = "theodor.letal@gmail.com";
$google_script_url = "https://script.google.com/macros/s/AKfycbzezgA7h-4W5A_ABSxSyaEHii-weKaYG1oiujTTzhoZjbkiS9f-78JxOidd8x5gJkeb/exec";


$subject_prefix = "Nová poptávka: ";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda není povolena']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná data']);
    exit;
}

$name = strip_tags($data['name'] ?? 'Neuvedeno');
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = strip_tags($data['phone'] ?? 'Neuvedeno');
$message = strip_tags($data['message'] ?? '');

$config = $data['config'] ?? [];
$type = strip_tags($config['type'] ?? '-');
$size = strip_tags($config['size'] ?? '-');
$color = strip_tags($config['color'] ?? '-');
$fill = strip_tags($config['fill'] ?? '-');
$print = strip_tags($config['print'] ?? '-');
$qty = strip_tags($config['qty'] ?? '-');
$total = strip_tags($config['total'] ?? '-');

$services = [];
if (isset($data['services']) && is_array($data['services'])) {
    $services = array_map('strip_tags', $data['services']);
}
$services_str = implode(", ", $services);

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Chybí nebo je neplatný e-mail']);
    exit;
}


if (!empty($google_script_url)) {
    $ch = curl_init($google_script_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_close($ch);
}


$email_body = "Obdrželi jste novou poptávku z konfigurátoru.\n\n";
$email_body .= "--- KONTAKTNĚ ÚDAJE ---\n";
$email_body .= "Jméno: $name\n";
$email_body .= "E-mail: $email\n";
$email_body .= "Telefon: $phone\n";
if ($services_str) {
    $email_body .= "Doplňkové služby: $services_str\n";
}
$email_body .= "\n--- KONFIGURACE PRODUKTU ---\n";
$email_body .= "Typ: $type\n";
$email_body .= "Rozměr: $size\n";
$email_body .= "Barva: $color\n";
$email_body .= "Výplň: $fill\n";
$email_body .= "Potisk: $print\n";
$email_body .= "Počet kusů: $qty\n";
$email_body .= "Odhadovaná cena bez DPH: $total\n";

if ($message) {
    $email_body .= "\n--- ZPRÁVA ---\n$message\n";
}

$domain = $_SERVER['HTTP_HOST'];
$from_email = "no-reply@" . $domain;
$headers = "From: Konfigurátor <$from_email>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$success = mail($to_email, $subject_prefix . " " . $type . " (" . $name . ")", $email_body, $headers);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Poptávka byla úspěšně zpracována.']);
}
else {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru při odesílání e-mailu.']);
}
