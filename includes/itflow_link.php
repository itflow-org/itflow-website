<?php
/*
 * itflow.org - the link to our billing ITFlow's Hosting add-on. With
 * $signup['itflow_url'] and $signup['itflow_secret'] set (config.local.php,
 * copied from Hosting > Settings there), a hosted sign-up goes straight into
 * ITFlow - client, recurring invoice, first invoice with its Pay button - and
 * the instance is built as soon as that is paid. Prices and regions come from
 * there too, so they are set in one place.
 *
 * Every request is signed: X-ITFlow-Signature: t=<unix time>, sig=HMAC-SHA256(secret, "<t>.<body>").
 * Without the two settings, or when ITFlow can't be reached, the page falls
 * back to emailing the sign-up as before, so none is lost.
 */

if (!defined('ITFLOW_SITE')) {
    http_response_code(404);
    exit;
}

/** Is the sign-up form connected to ITFlow? */
function itflowLinked() {
    global $signup;
    return trim((string) ($signup['itflow_url'] ?? '')) !== '' && trim((string) ($signup['itflow_secret'] ?? '')) !== '';
}

/*
 * Ask the add-on something - a sign-up, a name check, the prices. The answer
 * decoded, with its HTTP status in $status (0 when it couldn't be reached).
 */
function itflowAsk(array $payload, &$status = 0, $timeout = 20) {
    global $signup;
    $status = 0;
    $url = rtrim((string) $signup['itflow_url'], '/') . '/api/v1/custom/hosting/signup.php';
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $t = time();
    $headers = [
        'Content-Type: application/json',
        'X-ITFlow-Signature: t=' . $t . ', sig=' . hash_hmac('sha256', "$t.$body", (string) $signup['itflow_secret']),
        'User-Agent: itflow.org sign-up',
    ];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($ch);
        $status = $response === false ? 0 : (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $body, 'timeout' => $timeout, 'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
    }
    $data = is_string($response) ? json_decode($response, true) : null;
    return is_array($data) ? $data : null;
}

/*
 * The prices, regions and app domain from ITFlow, over $hosting from
 * config.php. Kept for 10 minutes; when ITFlow can't be reached the
 * config's are used and it is asked again in 2.
 */
function itflowHostingInfo(array $hosting) {
    global $signup;
    $hosting += ['app_domain' => 'itflow.app', 'open' => true];
    if (!itflowLinked()) {
        return $hosting;
    }
    $cache = rtrim(sys_get_temp_dir(), '/') . '/itflow-site-hosting-' . sha1((string) $signup['itflow_url']) . '.json';
    $cached = is_file($cache) ? json_decode((string) @file_get_contents($cache), true) : null;
    if (!is_array($cached) || ($cached['until'] ?? 0) < time()) {
        $info = itflowAsk(['info' => true], $status, 4);
        $cached = ($status === 200 && !empty($info['ok']))
            ? ['until' => time() + 600, 'info' => $info]
            : ['until' => time() + 120, 'info' => $cached['info'] ?? null];
        @file_put_contents($cache, json_encode($cached), LOCK_EX);
    }
    $info = $cached['info'] ?? null;
    if (!is_array($info)) {
        return $hosting;
    }
    foreach (['base_price', 'seat_price', 'included_seats', 'max_seats'] as $key) {
        if (isset($info[$key]) && is_numeric($info[$key])) {
            $hosting[$key] = $info[$key] + 0;
        }
    }
    if (!empty($info['regions']) && is_array($info['regions'])) {
        $hosting['regions'] = $info['regions'];
    }
    if (!empty($info['app_domain'])) {
        $hosting['app_domain'] = (string) $info['app_domain'];
    }
    $hosting['open'] = !empty($info['open']);
    return $hosting;
}
