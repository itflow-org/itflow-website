<?php
/*
 * itflow.org - sign-ups as tickets in our ITFlow, through its API. With
 * $signup['itflow_url'] and $signup['itflow_api_key'] set (config.local.php),
 * a sign-up the Hosting add-on isn't taking becomes:
 *
 *   - the company as a lead, with its address and contacts - or, when the
 *     primary contact's email is already a contact there, that client
 *   - a ticket for each thing asked for (hosted ITFlow, a sponsorship), raised
 *     from the ticket template in $signup['ticket_templates'] when there is
 *     one: its tasks, agent, priority and due time, and its fill-ins answered
 *     from the form - so the steps to work through are on the ticket
 *   - their own note from the form, as an internal note on the ticket
 *
 * ITFlow tells the contact (its Ticket Created email - they reply to it to
 * add anything), the new-ticket address and the agent, as for a ticket raised
 * there. The API key's user needs Modify on Clients and on Support.
 *
 * With the products in $signup['itflow_products'] as well (and Modify on
 * Sales), it bills them too: a client portal invite for the primary contact
 * (and the billing contact, when someone else), and for each thing asked for
 * a monthly recurring invoice made from its products, whose first invoice is
 * made and emailed straight away. Its ticket waits on that invoice, On Hold:
 * once it is paid in full ITFlow opens the ticket again, tells its agent and
 * - with its setting on - makes the lead a client. The prices shown here then
 * come from those products (itflowProductPrices()).
 *
 * What can't be opened - ITFlow down, a key or template that's wrong - is
 * emailed as before, saying why, so no sign-up is lost. The same form sent
 * twice opens nothing more: what it opened is kept for a day by its
 * signature, in the system's temp folder.
 */

if (!defined('ITFLOW_SITE')) {
    http_response_code(404);
    exit;
}

/** Do sign-ups go to ITFlow as tickets? */
function itflowTicketsOn() {
    global $signup;
    return trim((string) ($signup['itflow_url'] ?? '')) !== '' && trim((string) ($signup['itflow_api_key'] ?? '')) !== '';
}

/* The product a thing is billed with - 'hosting', 'seat' or a tier's key - its id, or 0 when it isn't billed. */
function itflowProductId($key) {
    global $signup;
    return itflowTicketsOn() ? (int) (($signup['itflow_products'] ?? [])[$key] ?? 0) : 0;
}

/*
 * The prices of the products in $signup['itflow_products'], from ITFlow -
 * [key => price], kept 10 minutes in the system's temp folder, so the prices
 * shown are the ones invoiced. One that can't be read is left out (its
 * config.php price stands); ITFlow out of reach keeps the last ones known.
 */
function itflowProductPrices() {
    global $signup;
    $ids = array_filter(array_map('intval', itflowTicketsOn() ? (array) ($signup['itflow_products'] ?? []) : []));
    if (!$ids) {
        return [];
    }
    $cache = rtrim(sys_get_temp_dir(), '/') . '/itflow-site-products-' . sha1((string) $signup['itflow_url'] . '|' . json_encode($ids)) . '.json';
    $cached = is_file($cache) ? json_decode((string) @file_get_contents($cache), true) : null;
    if (is_array($cached) && ($cached['until'] ?? 0) >= time()) {
        return (array) $cached['prices'];
    }
    $prices = [];
    $reached = false;
    foreach ($ids as $key => $id) {
        $answer = itflowApi('products/read', ['product_id' => $id], $status, true, 4);
        if ($status === 0) {
            break; // out of reach - not worth waiting on for each of the rest
        }
        $reached = true;
        $price = $answer['data'][0]['product_price'] ?? null;
        if ($status === 200 && is_numeric($price) && empty($answer['data'][0]['product_archived_at'])) {
            $prices[$key] = $price + 0;
        }
    }
    if (!$reached && is_array($cached)) {
        $prices = (array) $cached['prices'];
    }
    @file_put_contents($cache, json_encode(['until' => time() + ($reached ? 600 : 120), 'prices' => $prices]), LOCK_EX);
    return $prices;
}

/* $hosting and $sponsor_tiers with ITFlow's product prices in place of config.php's. */
function itflowApplyProductPrices(array &$hosting, array &$sponsor_tiers) {
    $prices = itflowProductPrices();
    if (isset($prices['hosting'])) {
        $hosting['base_price'] = $prices['hosting'];
    }
    if (isset($prices['seat'])) {
        $hosting['seat_price'] = $prices['seat'];
    }
    foreach ($sponsor_tiers as $key => $tier) {
        if (isset($prices[$key])) {
            $sponsor_tiers[$key]['price'] = $prices[$key];
        }
    }
}

/*
 * One API call - $endpoint such as 'tickets/create', POSTed as JSON, or read
 * with GET when $get. The answer decoded (null when there was none to read),
 * its HTTP status in $status (0 when ITFlow couldn't be reached).
 */
function itflowApi($endpoint, array $data, &$status = 0, $get = false, $timeout = 15) {
    global $signup;
    $status = 0;
    $data['api_key'] = (string) $signup['itflow_api_key'];
    $url = rtrim((string) $signup['itflow_url'], '/') . '/api/v1/' . $endpoint . '.php';
    if ($get) {
        $url .= '?' . http_build_query($data);
    }
    $body = $get ? '' : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $headers = ['Accept: application/json', 'User-Agent: itflow.org sign-up'];
    if (!$get) {
        $headers[] = 'Content-Type: application/json';
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false,
        ] + ($get ? [] : [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body]));
        $response = curl_exec($ch);
        $status = $response === false ? 0 : (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create(['http' => [
            'method' => $get ? 'GET' : 'POST', 'header' => implode("\r\n", $headers), 'content' => $body,
            'timeout' => $timeout, 'ignore_errors' => true, 'follow_location' => 0,
        ]]);
        $response = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
    }
    $answer = is_string($response) ? json_decode($response, true) : null;
    return is_array($answer) ? $answer : null;
}

/* A create call's new id, or 0 - with why in $error. */
function itflowApiCreate($endpoint, array $data, &$error = '') {
    $answer = itflowApi($endpoint, $data, $status);
    $id = (int) ($answer['data'][0]['insert_id'] ?? 0);
    if ($status === 200 && ($answer['success'] ?? '') === 'True' && $id > 0) {
        return $id;
    }
    $error = itflowApiWhy($endpoint, $status, $answer);
    return 0;
}

/* Why a call didn't work, for the email to us. */
function itflowApiWhy($endpoint, $status, $answer) {
    if ($status === 0) {
        return "ITFlow could not be reached ($endpoint)";
    }
    $message = rtrim(trim((string) ($answer['message'] ?? '')), '.');
    return "ITFlow said no to $endpoint (HTTP $status)" . ($message !== '' ? ": $message" : '');
}

/*
 * Open the tickets for a sign-up. $in:
 *   ref        the form's own reference - the same form sent again gets what it opened the first time
 *   company    name, address, city, state, postal, country, website
 *   contacts   primary, billing, technical - each name, email, title, phone, mobile
 *   comments   their note, raw
 *   tickets    [kind => ['subject', 'details' (HTML), 'fill_ins' => [name => answer], 'scope', 'items']], kind
 *              'hosting' or 'sponsor'; items, the recurring invoice's lines ([product_id, qty, price?]) - none, no invoice
 *
 * Returns ['client_id', 'client_made' (a new lead), 'invited' => [emails], 'invoices' => [kind => ['recurring',
 * 'invoice_id', 'reference', 'amount', 'currency', 'url']], 'tickets' => [kind => ['id', 'reference', 'url', 'tasks']],
 * 'errors' => [kind, kind-invoice or 'client' => why]]. A kind missing from 'tickets' wasn't opened.
 */
function itflowSignupTickets(array $in) {
    global $signup;
    $result = ['client_id' => 0, 'client_made' => false, 'invited' => null, 'invoices' => [], 'tickets' => [], 'errors' => []];

    // Sent before? What it opened then - and one at a time, so a double click opens nothing twice
    $marker = rtrim(sys_get_temp_dir(), '/') . '/itflow-site-signup-' . sha1((string) $signup['itflow_url'] . '|' . $in['ref']) . '.json';
    $lock = @fopen($marker . '.lock', 'c');
    if ($lock) {
        flock($lock, LOCK_EX);
    }
    $before = is_file($marker) && filemtime($marker) > time() - 86400 ? json_decode((string) @file_get_contents($marker), true) : null;
    if (is_array($before)) {
        $result = array_merge($result, $before);
        $result['errors'] = [];
    }

    $primary = $in['contacts']['primary'];

    // Their client - the one the primary contact's email is already at, or a new lead
    if (!$result['client_id']) {
        $found = itflowApi('contacts/read', ['contact_email' => $primary['email']], $status, true, 8);
        foreach ((array) ($found['data'] ?? []) as $contact) {
            if (empty($contact['contact_archived_at']) && (int) ($contact['contact_client_id'] ?? 0) > 0) {
                $client = itflowApi('clients/read', ['client_id' => (int) $contact['contact_client_id']], $status, true, 8);
                if (!empty($client['data'][0]) && empty($client['data'][0]['client_archived_at'])) {
                    $result['client_id'] = (int) $contact['contact_client_id'];
                    $result['contact_id'] = (int) $contact['contact_id'];
                    break;
                }
            }
        }
    }
    if (!$result['client_id']) {
        $error = '';
        $company = $in['company'];
        $client_id = itflowApiCreate('clients/create', [
            'client_name' => $company['name'],
            'client_lead' => 1,
            'client_website' => $company['website'],
            'client_referral' => 'itflow.org',
        ], $error);
        if (!$client_id) {
            $result['errors']['client'] = $error;
            itflowSignupKeep($marker, $lock, $result);
            return $result;
        }
        $result['client_id'] = $client_id;
        $result['client_made'] = true;

        // Its address, when they gave one
        $location_id = 0;
        if (trim($company['address'] . $company['city'] . $company['state'] . $company['postal']) !== '') {
            $location_id = itflowApiCreate('locations/create', [
                'client_id' => $client_id, 'location_name' => 'Main', 'location_primary' => 1,
                'location_address' => $company['address'], 'location_city' => $company['city'], 'location_state' => $company['state'],
                'location_zip' => $company['postal'], 'location_country' => $company['country'],
            ], $error);
        }

        // Its contacts - the primary, then billing and technical when they're someone else (the same person once)
        $people = [];
        foreach (['primary', 'billing', 'technical'] as $role) {
            $person = $in['contacts'][$role];
            $key = strtolower(trim((string) $person['email']));
            if ($key === '' || ($person['name'] ?? '') === '') {
                continue;
            }
            $people[$key] = ($people[$key] ?? $person + ['roles' => []]);
            $people[$key]['roles'][] = $role;
        }
        foreach ($people as $person) {
            $contact_id = itflowApiCreate('contacts/create', [
                'client_id' => $client_id, 'contact_name' => $person['name'], 'contact_title' => $person['title'] ?? '',
                'contact_email' => $person['email'], 'contact_phone' => $person['phone'] ?? '', 'contact_mobile' => $person['mobile'] ?? '',
                'contact_primary' => in_array('primary', $person['roles'], true) ? 1 : 0,
                'contact_billing' => in_array('billing', $person['roles'], true) ? 1 : 0,
                'contact_technical' => in_array('technical', $person['roles'], true) ? 1 : 0,
                'contact_location_id' => $location_id,
            ], $error);
            if (in_array('primary', $person['roles'], true)) {
                $result['contact_id'] = $contact_id;
            }
        }
        itflowSignupKeep($marker, null, $result);
    }

    $billed = (bool) array_filter(array_column($in['tickets'], 'items'));

    // Client portal access when they're billed - where their invoices are, a card can be saved and their requests followed
    if ($billed && $result['invited'] === null) {
        $result['invited'] = [];
        $invite = [];
        foreach (['primary', 'billing'] as $role) {
            $person = $in['contacts'][$role];
            $key = strtolower(trim((string) $person['email']));
            if ($key !== '' && !isset($invite[$key])) {
                $invite[$key] = ['email' => $person['email'], 'name' => $person['name'], 'billing' => 0, 'technical' => 0];
            }
        }
        foreach (['billing', 'technical'] as $role) {
            $key = strtolower(trim((string) $in['contacts'][$role]['email']));
            if (isset($invite[$key])) {
                $invite[$key][$role] = 1;
            }
        }
        foreach ($invite as $person) {
            $answer = itflowApi('portal_invites/create', ['client_id' => $result['client_id']] + $person, $status);
            if ($status === 200 && in_array($answer['invite'] ?? '', ['invited', 'resent'], true)) {
                $result['invited'][] = $person['email'];
            }
        }
        itflowSignupKeep($marker, null, $result);
    }

    // A ticket for each thing they asked for, from its template - after its recurring invoice and first invoice, when it's billed
    $templates = (array) ($signup['ticket_templates'] ?? []);
    foreach ($in['tickets'] as $kind => $ticket) {
        if (isset($result['tickets'][$kind])) {
            continue;
        }
        if (!empty($ticket['items']) && !isset($result['invoices'][$kind])) {
            $answer = itflowApi('recurring_invoices/create', [
                'client_id' => $result['client_id'],
                'scope' => $ticket['scope'] ?? $ticket['subject'],
                'frequency' => 'month',
                'items' => $ticket['items'],
                'invoice_now' => 1,
            ], $status, false, 30);
            if ($status === 200 && ($answer['success'] ?? '') === 'True' && !empty($answer['invoice']['invoice_id'])) {
                $result['invoices'][$kind] = [
                    'recurring' => (string) ($answer['recurring_invoice_reference'] ?? ''),
                    'invoice_id' => (int) $answer['invoice']['invoice_id'],
                    'reference' => (string) $answer['invoice']['reference'],
                    'amount' => (float) $answer['invoice']['amount'],
                    'currency' => (string) $answer['invoice']['currency'],
                    'url' => (string) $answer['invoice']['url'],
                ];
                itflowSignupKeep($marker, null, $result);
            } else {
                $result['errors']["$kind-invoice"] = itflowApiWhy('recurring_invoices/create', $status, $answer);
            }
        }
        $ask = [
            'client_id' => $result['client_id'],
            'ticket_subject' => $ticket['subject'],
            'ticket_details' => $ticket['details'],
            'ticket_notify' => 1,
        ];
        // Waiting on its first invoice: On Hold until it's paid (ITFlow opens it again then). They have the
        // invoice's email and the portal invite already, so only our team hears about the ticket.
        if (isset($result['invoices'][$kind])) {
            $ask['ticket_invoice_id'] = $result['invoices'][$kind]['invoice_id'];
            $ask['ticket_status'] = 3;
            $ask['ticket_notify'] = 'agents';
        }
        if (!empty($result['contact_id'])) {
            $ask['ticket_contact_id'] = (int) $result['contact_id'];
        }
        if ((int) ($templates[$kind] ?? 0) > 0) {
            $ask['ticket_template_id'] = (int) $templates[$kind];
            $ask['ticket_fill_ins'] = (object) $ticket['fill_ins'];
        }
        $answer = itflowApi('tickets/create', $ask, $status);
        $ticket_id = (int) ($answer['data'][0]['insert_id'] ?? 0);
        if ($status !== 200 || ($answer['success'] ?? '') !== 'True' || !$ticket_id) {
            $result['errors'][$kind] = itflowApiWhy('tickets/create', $status, $answer);
            continue;
        }
        $result['tickets'][$kind] = [
            'id' => $ticket_id,
            'reference' => (string) ($answer['ticket_reference'] ?? "#$ticket_id"),
            'url' => (string) ($answer['ticket_url'] ?? ''),
            'tasks' => (int) ($answer['ticket_tasks'] ?? 0),
        ];
        itflowSignupKeep($marker, null, $result);

        // Their own words go on it as a note for us - not into the emails ITFlow sends out
        if (trim((string) $in['comments']) !== '') {
            itflowApi('ticket_replies/create', [
                'client_id' => $result['client_id'],
                'ticket_id' => $ticket_id,
                'ticket_reply_type' => 'Internal',
                'ticket_reply' => '<p><strong>' . e($in['contacts']['primary']['name']) . ' added on the sign-up form:</strong></p><p>' . nl2br(e($in['comments'])) . '</p>',
            ], $status);
        }
    }

    itflowSignupKeep($marker, $lock, $result);
    return $result;
}

/* Keep what a sign-up has opened so far, by its reference - and let go of the lock when given it. */
function itflowSignupKeep($marker, $lock, array $result) {
    $keep = $result;
    unset($keep['errors']);
    @file_put_contents($marker, json_encode($keep), LOCK_EX);
    if ($lock) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/* A sign-up's summary for its ticket - [label => value] rows, as HTML. Empty values are left out. */
function itflowTicketDetails($heading, array $rows) {
    $html = '<p><strong>' . e($heading) . '</strong></p><table>';
    foreach ($rows as $label => $value) {
        if (trim((string) $value) === '') {
            continue;
        }
        $html .= '<tr><td style="padding-right: 16px; vertical-align: top">' . e($label) . '</td><td>' . nl2br(e($value)) . '</td></tr>';
    }
    return $html . '</table>';
}

/* The line in the email to us about a thing's invoice - made and sent, or not made and why. '' when it isn't billed. */
function itflowInvoiceLine(?array $ticketed, $kind) {
    if (isset($ticketed['invoices'][$kind])) {
        $invoice = $ticketed['invoices'][$kind];
        return "  Invoice:      {$invoice['reference']} for " . money($invoice['amount']) . " sent, from recurring invoice {$invoice['recurring']} - the ticket waits On Hold until it is paid\n";
    }
    if (isset($ticketed['errors']["$kind-invoice"])) {
        return "  Invoice:      NOT made - " . $ticketed['errors']["$kind-invoice"] . ". Make it by hand.\n";
    }
    return '';
}
