<?php
$ident = "consta";
$secret = "1100af88-7c11-11f0-bfe8-0242ac130002";
$channel = "peer";

$data = ["format" => "urls"];
$data_json = json_encode($data);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => $data_json,
    CURLOPT_URL => "https://global.xirsys.net/_turn/$channel",
    CURLOPT_USERPWD => "$ident:$secret",
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_CUSTOMREQUEST => "PUT",
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 10
]);

$resp = curl_exec($curl);
if ($resp === false || curl_error($curl)) {
    http_response_code(500);
    error_log('Failed to fetch ICE servers: ' . curl_error($curl));
    echo json_encode(['error' => 'Failed to fetch ICE servers']);
    curl_close($curl);
    exit;
}
curl_close($curl);

$response = json_decode($resp, true);
if (!isset($response['v']['iceServers'])) {
    http_response_code(500);
    error_log('Invalid ICE server response: ' . $resp);
    echo json_encode(['error' => 'Invalid ICE server response']);
    exit;
}

$iceServers = $response['v']['iceServers'];
if (!is_array($iceServers) || isset($iceServers['urls'])) {
    $iceServers = [$iceServers];
}

// Add fallback Google STUN server
$iceServers[] = ['urls' => 'stun:stun.l.google.com:19302'];

// Validate ICE servers
$validIceServers = array_filter($iceServers, function($server) {
    return isset($server['urls']) && (is_array($server['urls']) || is_string($server['urls']));
});

header('Content-Type: application/json');
echo json_encode(['v' => ['iceServers' => $validIceServers]]);
?>