<?php
/*
 * itflow.org - the link to our billing ITFlow's Hosting add-on. With
 * $signup['itflow_url'] and $signup['itflow_secret'] set (config.local.php,
 * copied from Hosting > Settings there), a hosted sign-up or a sponsorship
 * goes straight into ITFlow - client, recurring invoice, first invoice with
 * its Pay button. The instance is built, or the sponsorship starts (with its
 * white-label key), as soon as that is paid. Prices, regions and the paying
 * sponsors come from there too, so they are kept in one place.
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
 * Ask ITFlow something the pages show, kept for 10 minutes in the system's
 * temp folder - the whole answer, or just its $key. When ITFlow can't be
 * reached, the last answer is used (or null when there never was one) and it
 * is asked again in 2 minutes.
 */
function itflowCached($what, array $payload, $key = null) {
    global $signup;
    $cache = rtrim(sys_get_temp_dir(), '/') . "/itflow-site-$what-" . sha1((string) $signup['itflow_url']) . '.json';
    $cached = is_file($cache) ? json_decode((string) @file_get_contents($cache), true) : null;
    if (!is_array($cached) || ($cached['until'] ?? 0) < time()) {
        $answer = itflowAsk($payload, $status, 4);
        $cached = ($status === 200 && !empty($answer['ok']) && ($key === null || array_key_exists($key, $answer)))
            ? ['until' => time() + 600, 'answer' => $key === null ? $answer : $answer[$key]]
            : ['until' => time() + 120, 'answer' => $cached['answer'] ?? null];
        @file_put_contents($cache, json_encode($cached), LOCK_EX);
    }
    return $cached['answer'] ?? null;
}

/* What ITFlow says about sign-ups - open or not, prices, regions, sponsorship tiers. Null when not linked or never reached. */
function itflowInfo() {
    if (!itflowLinked()) {
        return null;
    }
    $info = itflowCached('hosting', ['info' => true]);
    return is_array($info) ? $info : null;
}

/*
 * The prices, regions and app domain from ITFlow, over $hosting from
 * config.php. When ITFlow can't be reached the config's are used.
 */
function itflowHostingInfo(array $hosting) {
    $hosting += ['app_domain' => 'itflow.app', 'open' => true];
    $info = itflowInfo();
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

/*
 * Is ITFlow taking sponsorships - do they go straight in, with a Pay button?
 * (An older add-on, or one with sponsorships switched off, says no: they are
 * emailed as before.)
 */
function itflowSponsorOpen() {
    $info = itflowInfo();
    return is_array($info) && !empty($info['sponsor_open']) && !empty($info['sponsor_tiers']);
}

/* The sponsorship tiers' prices from ITFlow, over $sponsor_tiers from config.php - tiers ITFlow knows by the same key. */
function itflowSponsorTiers(array $sponsor_tiers) {
    $info = itflowInfo();
    foreach ((array) ($info['sponsor_tiers'] ?? []) as $key => $tier) {
        if (isset($sponsor_tiers[$key]) && is_numeric($tier['price'] ?? null)) {
            $sponsor_tiers[$key]['price'] = $tier['price'] + 0;
        }
    }
    return $sponsor_tiers;
}

/*
 * The paying sponsors ITFlow puts on the website - each name, url, logo (a
 * data: image), featured - added after the ones in config.php, leaving out
 * any already named there. ITFlow keeps who is paying; this page only shows them.
 */
function itflowSponsors(array $sponsors) {
    if (!itflowLinked()) {
        return $sponsors;
    }
    $listed = itflowCached('sponsors', ['sponsors' => true], 'sponsors');
    if (!is_array($listed)) {
        return $sponsors;
    }
    $have = array_map('mb_strtolower', array_map('trim', array_column($sponsors, 'name')));
    foreach ($listed as $s) {
        $name = trim((string) ($s['name'] ?? ''));
        if ($name === '' || in_array(mb_strtolower($name), $have, true)) {
            continue;
        }
        $logo = (string) ($s['logo'] ?? '');
        $url = (string) ($s['url'] ?? '');
        $sponsors[] = [
            'name' => $name,
            'url' => preg_match('#^https?://#i', $url) ? $url : '',
            'logo_data' => preg_match('#^data:image/(png|jpeg|webp|svg\+xml);base64,[A-Za-z0-9+/=]+$#', $logo) ? $logo : '',
            'featured' => !empty($s['featured']),
        ];
        $have[] = mb_strtolower($name);
    }
    return $sponsors;
}
