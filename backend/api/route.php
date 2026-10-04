<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_customer();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false, 'message'=>'POST request required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$origin = $input['origin'] ?? null;
$destination = $input['destination'] ?? null;

function valid_point($point) {
    return is_array($point)
        && isset($point['lat'], $point['lng'])
        && is_numeric($point['lat'])
        && is_numeric($point['lng'])
        && (float)$point['lat'] >= -90 && (float)$point['lat'] <= 90
        && (float)$point['lng'] >= -180 && (float)$point['lng'] <= 180;
}

if (!valid_point($origin) || !valid_point($destination)) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>'Valid origin and destination coordinates are required.']);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'message'=>'PHP cURL extension is required for routing.']);
    exit;
}

function normalize_route($distanceMeters, $durationSeconds, $coordinates, $label='Recommended') {
    $clean = [];
    foreach ((array)$coordinates as $pair) {
        if (is_array($pair) && count($pair) >= 2) {
            $clean[] = [round((float)$pair[1], 7), round((float)$pair[0], 7)]; // Leaflet = [lat,lng]
        }
    }
    return [
        'distance_m' => (float)$distanceMeters,
        'duration_seconds' => (float)$durationSeconds,
        'label' => $label,
        'coordinates' => $clean
    ];
}

function request_json($url, $payload, $headers=[], $timeout=20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 8
    ]);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) return ['ok'=>false, 'status'=>$status, 'error'=>$error ?: 'Network request failed.'];
    $data = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        return ['ok'=>false, 'status'=>$status, 'error'=>$data['error']['message'] ?? 'Routing provider returned an error.'];
    }
    return ['ok'=>true, 'status'=>$status, 'data'=>$data];
}

function google_routes($origin, $destination) {
    if (!GOOGLE_MAPS_API_KEY) return ['ok'=>false, 'error'=>'Google Routes API key is not configured.'];

    $payload = [
        'origin' => ['location' => ['latLng' => ['latitude'=>(float)$origin['lat'], 'longitude'=>(float)$origin['lng']]]],
        'destination' => ['location' => ['latLng' => ['latitude'=>(float)$destination['lat'], 'longitude'=>(float)$destination['lng']]]],
        'travelMode' => 'DRIVE',
        'routingPreference' => 'TRAFFIC_AWARE',
        'computeAlternativeRoutes' => true,
        'languageCode' => 'en-US',
        'units' => 'METRIC'
    ];
    $fieldMask = 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline,routes.routeLabels';
    $response = request_json(
        'https://routes.googleapis.com/directions/v2:computeRoutes',
        $payload,
        ['X-Goog-Api-Key: '.GOOGLE_MAPS_API_KEY, 'X-Goog-FieldMask: '.$fieldMask]
    );
    if (!$response['ok']) return $response;

    $routes = [];
    foreach (($response['data']['routes'] ?? []) as $index=>$route) {
        $encoded = $route['polyline']['encodedPolyline'] ?? '';
        $coords = $encoded ? decode_polyline($encoded) : [];
        $duration = parse_google_duration($route['duration'] ?? '');
        $routes[] = normalize_route($route['distanceMeters'] ?? 0, $duration, $coords, $index===0 ? 'Recommended' : 'Alternative');
    }
    return ['ok'=>true, 'provider'=>'Google Routes API', 'routes'=>$routes];
}

function parse_google_duration($duration) {
    if (preg_match('/([0-9.]+)s$/', (string)$duration, $m)) return (float)$m[1];
    return 0;
}

function decode_polyline($encoded) {
    $index=0; $lat=0; $lng=0; $len=strlen($encoded); $points=[];
    while ($index < $len) {
        $shift=0; $result=0;
        do { $b=ord($encoded[$index++])-63; $result |= ($b & 0x1f) << $shift; $shift += 5; } while ($b >= 0x20 && $index < $len);
        $deltaLat = ($result & 1) ? ~($result >> 1) : ($result >> 1); $lat += $deltaLat;
        $shift=0; $result=0;
        do { $b=ord($encoded[$index++])-63; $result |= ($b & 0x1f) << $shift; $shift += 5; } while ($b >= 0x20 && $index < $len);
        $deltaLng = ($result & 1) ? ~($result >> 1) : ($result >> 1); $lng += $deltaLng;
        $points[] = [round($lng / 1e5, 7), round($lat / 1e5, 7)];
    }
    // convert to [lng,lat] pairs; normalize_route flips to Leaflet order.
    return $points;
}

function osrm_routes($origin, $destination) {
    $base = rtrim(OSRM_BASE_URL, '/');
    $url = $base . '/route/v1/driving/'
        . rawurlencode((float)$origin['lng']) . ',' . rawurlencode((float)$origin['lat']) . ';'
        . rawurlencode((float)$destination['lng']) . ',' . rawurlencode((float)$destination['lat'])
        . '?alternatives=true&overview=full&geometries=geojson&steps=false';

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20, CURLOPT_CONNECTTIMEOUT=>8]);
    $body = curl_exec($ch); $error = curl_error($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($body === false || $status < 200 || $status >= 300) {
        return [
            'ok' => false,
            'error' =>  'OSRM failed. HTTP: ' . $status .
                        ' | cURL: ' . ($error ?: 'No cURL error') .
                        ' | Response: ' . substr((string)$body, 0, 300)
        ];
    }
    $data = json_decode($body, true);
    if (!is_array($data) || ($data['code'] ?? '') !== 'Ok') return ['ok'=>false,'error'=>$data['message'] ?? 'No route found.'];

    $routes=[];
    foreach (($data['routes'] ?? []) as $index=>$route) {
        $coords = $route['geometry']['coordinates'] ?? [];
        $routes[] = normalize_route($route['distance'] ?? 0, $route['duration'] ?? 0, $coords, $index===0 ? 'Recommended' : 'Alternative');
    }
    return ['ok'=>true,'provider'=>'OSRM / OpenStreetMap','routes'=>$routes];
}

$provider = strtolower(trim(ROUTING_PROVIDER));
$result = ['ok'=>false,'error'=>'Routing provider is unavailable.'];
if ($provider === 'google') {
    $result = google_routes($origin, $destination);
    if (!$result['ok']) {
        $fallback = osrm_routes($origin, $destination);
        if ($fallback['ok']) $result = $fallback;
    }
} else {
    $result = osrm_routes($origin, $destination);
}

if (!$result['ok']) {
    http_response_code(502);
    echo json_encode(['ok'=>false, 'message'=>$result['error'] ?? 'No route could be calculated.']);
    exit;
}

echo json_encode([
    'ok'=>true,
    'provider'=>$result['provider'],
    'routes'=>$result['routes'] ?? []
]);
