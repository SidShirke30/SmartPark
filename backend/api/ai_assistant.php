<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'POST request required.']);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'PHP cURL extension is required.']);
    exit;
}

$input=json_decode(file_get_contents('php://input'), true) ?: [];
$message=trim((string)($input['message'] ?? ''));
if ($message==='') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'Please enter a message.']);
    exit;
}
if (mb_strlen($message)>2000) $message=mb_substr($message,0,2000);

$key=GEMINI_API_KEY;
$model=GEMINI_MODEL;
if ($key==='') {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Gemini API key is not configured.']);
    exit;
}

$payload=[
  'model'=>$model,
  'input'=>"You are SmartPark AI, a helpful assistant for a smart parking reservation website in Maharashtra, India. Answer concisely and safely. Help users with parking, reservations, payments, navigation, and website usage. If asked about live parking availability, explain that the live map/database is the source of truth rather than inventing availability. User message: ".$message
];

$ch=curl_init('https://generativelanguage.googleapis.com/v1beta/interactions');
curl_setopt_array($ch,[
  CURLOPT_POST=>true,
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-goog-api-key: '.$key],
  CURLOPT_POSTFIELDS=>json_encode($payload),
  CURLOPT_TIMEOUT=>45,
]);
$response=curl_exec($ch);
$curlError=curl_error($ch);
$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response===false) {
    http_response_code(502);
    echo json_encode(['ok'=>false,'message'=>'Gemini request failed: '.$curlError]);
    exit;
}
$data=json_decode($response,true);
if ($status<200 || $status>=300) {
    http_response_code(502);
    $detail=$data['error']['message'] ?? 'Gemini API returned an error.';
    echo json_encode(['ok'=>false,'message'=>$detail]);
    exit;
}
$text='';
if (!empty($data['output_text'])) $text=$data['output_text'];
if ($text==='' && !empty($data['steps'])) {
    foreach ($data['steps'] as $step) {
        foreach (($step['content'] ?? []) as $part) {
            if (($part['type'] ?? '')==='text') $text.=$part['text'] ?? '';
        }
    }
}
if ($text==='') {
    foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) $text.=$part['text'] ?? '';
}
if ($text==='') $text='I could not generate a response right now. Please try again.';
echo json_encode(['ok'=>true,'message'=>$text,'model'=>$model]);
