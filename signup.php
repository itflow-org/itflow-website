<?php
/*
 * itflow.org - Hosted ITFlow / Sponsorship sign-up
 *
 * Shows the form, validates it, and sends it on. A hosted sign-up and a
 * sponsorship go straight into our billing ITFlow when the form is linked to
 * it (includes/itflow_link.php): the customer gets a first invoice for each
 * with a Pay button, and their ITFlow is built - or their sponsorship starts,
 * with its tier's perks - as soon as it is paid. Otherwise the submission
 * is emailed with PHPMailer. Nothing is stored here. Prices, tiers, required
 * fields, mail settings and the on/off switches live in config.php (prices
 * and regions come from ITFlow when linked).
 *
 * Links from the homepage can preselect: signup.php?want=hosting
 * or signup.php?want=sponsor&tier=premier
 * (hosting.php, its old address, and sponsor.php, the short link to
 * sponsoring, send people here)
 */

require_once __DIR__ . '/includes/bootstrap.php';

// ---------------------------------------------------------------------------
// What this page offers, from the section switches
// ---------------------------------------------------------------------------
$offer_hosting = sectionOn('hosting');
$offer_sponsor = sectionOn('sponsorship');

if (!signupOn()) {
    http_response_code(404);
    $page_title = 'Sign-ups are closed - ITFlow';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="wrap page-head">
        <h1>Sign-ups are closed</h1>
        <p>We're not taking new sign-ups right now. Check back soon.</p>
        <p><a class="button button-primary" href="/">Back to ITFlow</a></p>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$base_price     = $hosting['base_price'];
$included_seats = $hosting['included_seats'];
$seat_price     = $hosting['seat_price'];
$max_seats      = $hosting['max_seats'];
$regions        = $hosting['regions'];
$app_domain     = $hosting['app_domain'];
// Hosted sign-ups and sponsorships go into ITFlow when linked and it is taking them; otherwise they are emailed
$linked         = $offer_hosting && itflowLinked() && !empty($hosting['open']);
$linked_sponsor = $offer_sponsor && itflowLinked() && itflowSponsorOpen();
$sponsor_names  = sponsorNamesShort($sponsors);
$notify_to      = (array) $signup['notify_to'];

// ---------------------------------------------------------------------------
// Required fields (config: $signup['required']); these three are always required
// ---------------------------------------------------------------------------
$required = array_merge(['company', 'primary_name', 'primary_email'], (array) $signup['required']);
if (in_array('title', $required, true)) {
    $required[] = 'primary_title';
}

function isRequired($field) {
    global $required;
    return in_array($field, $required, true);
}
// The required attribute and the asterisk for a field's input and label
function reqAttr($field) {
    return isRequired($field) ? ' required' : '';
}
function reqStar($field) {
    return isRequired($field) ? ' <span class="req" aria-hidden="true">*</span>' : '';
}

$field_labels = [
    'address' => 'Address', 'city' => 'City', 'state' => 'State', 'postal' => 'Postal code',
    'country' => 'Country', 'primary_title' => 'Title',
];

// ---------------------------------------------------------------------------
// Form helpers
// ---------------------------------------------------------------------------
function field($key) {
    return trim((string) ($_POST[$key] ?? ''));
}
// Single-line values: newlines and tabs flattened
function line($key, $max = 200) {
    return mb_substr(preg_replace('/[\r\n\t]+/', ' ', field($key)), 0, $max);
}
function old($key, $default = '') {
    return e($_POST[$key] ?? $default);
}
function checked($key, $value = '1', $default = false) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $default ? 'checked' : '';
    }
    return (($_POST[$key] ?? null) === $value) ? 'checked' : '';
}

// First visit: what's ticked comes from the link they followed (?want=, ?tier=)
$want_param = $_GET['want'] ?? '';
$tier_param = $_GET['tier'] ?? '';
$default_sponsor = $offer_sponsor && ($want_param === 'sponsor' || !$offer_hosting || isset($sponsor_tiers[$tier_param]));
$default_hosting = $offer_hosting && !($want_param === 'sponsor' || isset($sponsor_tiers[$tier_param]));
$default_tier    = isset($sponsor_tiers[$tier_param]) ? $tier_param : '';
// ?seats= from the price slider on the homepage, kept within what can be ordered
$default_seats   = isset($_GET['seats']) && ctype_digit((string) $_GET['seats']) ? min($max_seats, max($included_seats, (int) $_GET['seats'])) : $included_seats;

// ---------------------------------------------------------------------------
// Is a name free? Asked by the form as someone types (signup.php?check=1&subdomain=acme)
// ---------------------------------------------------------------------------
if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    if (!$linked) {
        echo json_encode(['ok' => null]);
        exit;
    }
    $ask = isset($_GET['hostname']) ? ['check' => true, 'hostname' => strtolower(substr((string) $_GET['hostname'], 0, 253))]
        : ['check' => true, 'subdomain' => strtolower(substr((string) ($_GET['subdomain'] ?? ''), 0, 63))];
    $answer = itflowAsk($ask, $status, 5);
    echo json_encode($status === 200 && is_array($answer) ? ['ok' => (bool) $answer['ok'], 'error' => (string) ($answer['error'] ?? '')] : ['ok' => null]);
    exit;
}

// ---------------------------------------------------------------------------
// Handle a submission
// ---------------------------------------------------------------------------
$errors = [];
$sent = false;
$created = null;          // ITFlow's answer to a hosted sign-up: address, invoice and its Pay link
$created_sponsor = null;  // ...and to a sponsorship: tier, invoice and its Pay link

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Anti-spam: honeypot + signed render time (bots post instantly)
    $ts  = (int) ($_POST['ts'] ?? 0);
    $sig = (string) ($_POST['sig'] ?? '');
    $age = time() - $ts;
    $is_bot = field('website_url') !== ''
        || !hash_equals(hash_hmac('sha256', (string) $ts, $signup['form_secret']), $sig)
        || $age < 4 || $age > 86400;

    $want_hosting = $offer_hosting && isset($_POST['want_hosting']);
    $want_sponsor = $offer_sponsor && isset($_POST['want_sponsor']);

    if (!$want_hosting && !$want_sponsor) {
        $errors[] = ($offer_hosting && $offer_sponsor) ? 'Choose a hosted instance, a sponsorship, or both.' : 'Please choose an option.';
    }

    // Hosting
    $seats = 0;
    $hosting_total = 0;
    $region = '';
    $hostname = '';
    $access = '';
    $timezone = '';
    $timezone_from = '';
    if ($want_hosting) {
        $seats = (int) field('seats');
        if ($seats < $included_seats || $seats > $max_seats) {
            $errors[] = "Technicians must be between $included_seats and $max_seats.";
        }
        $hosting_total = $base_price + max(0, $seats - $included_seats) * $seat_price;

        $region = field('region');
        if (!isset($regions[$region])) {
            $errors[] = 'Choose a region.';
        }

        $access = field('access');
        if ($access === 'own') {
            $hostname = strtolower(line('own_hostname', 253));
            if (!preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $hostname)) {
                $errors[] = 'Enter a valid hostname, for example itflow.yourcompany.com.';
            }
        } elseif ($access === 'itflowapp') {
            $label = strtolower(line('app_subdomain', 63));
            if (!preg_match('/^[a-z0-9]([a-z0-9-]{1,61}[a-z0-9])?$/', $label)) {
                $errors[] = "Your $app_domain name can use letters, numbers and dashes (3-63 characters).";
            }
            $hostname = $label . '.' . $app_domain;
        } else {
            $errors[] = 'Choose how you want to reach your instance.';
        }

        // Their ITFlow is set up in their time zone, taken from their browser - nothing to fill in.
        // A browser that doesn't say gets the default; they can change it in ITFlow afterwards.
        $timezone = line('timezone', 64);
        $timezone_from = 'their browser';
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $timezone = (string) ($hosting['default_timezone'] ?? 'America/New_York');
            $timezone_from = 'default - their browser did not say';
        }
    }

    // Sponsorship
    $tier_key = '';
    $sponsor_total = 0;
    $sponsor_url = '';
    $sponsor_display = '';
    $sponsor_anonymous = false;
    $forum_users = [];
    if ($want_sponsor) {
        $tier_key = field('sponsor_tier');
        if (!isset($sponsor_tiers[$tier_key])) {
            $errors[] = 'Choose a sponsorship tier.';
        } else {
            $sponsor_total = $sponsor_tiers[$tier_key]['price'];
            if (!empty($sponsor_tiers[$tier_key]['custom'])) {
                $sponsor_total = (int) field('sponsor_amount');
                if ($sponsor_total < $sponsor_tiers[$tier_key]['price']) {
                    $errors[] = $sponsor_tiers[$tier_key]['name'] . ' starts at ' . money($sponsor_tiers[$tier_key]['price']) . ' a month.';
                }
            }
        }
        // Anonymous: not on itflow.org at all - so no name or website to show
        $sponsor_anonymous = isset($_POST['sponsor_anonymous']);
        if (!$sponsor_anonymous) {
            $sponsor_display = line('sponsor_display', 100);
            $sponsor_url = line('sponsor_url', 500);
            if ($sponsor_url !== '' && !preg_match('#^https?://#i', $sponsor_url)) {
                $sponsor_url = 'https://' . $sponsor_url;
            }
            if ($sponsor_url !== '' && !filter_var($sponsor_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Enter your website as an address, like https://yourcompany.com.';
            }
        }
        [$forum_users, $forum_error] = forumUsernames(mb_substr(field('sponsor_forum_users'), 0, 2000));
        if ($forum_error !== '') {
            $errors[] = $forum_error;
        }
    }

    // Company and contacts
    $company = line('company');
    if ($company === '') {
        $errors[] = 'Company name is required.';
    }
    foreach (['address', 'city', 'state', 'postal', 'country'] as $f) {
        if (isRequired($f) && line($f) === '') {
            $errors[] = $field_labels[$f] . ' is required.';
        }
    }

    $primary_name  = line('primary_name');
    $primary_email = line('primary_email');
    if ($primary_name === '') {
        $errors[] = 'Primary contact name is required.';
    }
    if (!filter_var($primary_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Primary contact needs a valid email.';
    }
    if (isRequired('primary_title') && line('primary_title') === '') {
        $errors[] = 'Primary contact title is required.';
    }
    if (isRequired('phone') && line('primary_phone') === '' && line('primary_mobile') === '') {
        $errors[] = 'Give us a phone or mobile number for the primary contact.';
    }

    $contact_info = [];
    foreach (['billing' => 'Billing', 'technical' => 'Technical'] as $key => $label) {
        if (isset($_POST[$key . '_same'])) {
            $contact_info[$key] = 'Same as primary contact';
            continue;
        }
        $n  = line($key . '_name');
        $em = line($key . '_email');
        $ph = line($key . '_phone');
        if ($n === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "$label contact needs a name and a valid email, or tick \"Same as primary contact\".";
        }
        $contact_info[$key] = trim("$n <$em>" . ($ph !== '' ? ", $ph" : ''));
    }

    // Into ITFlow - the client, and for each of hosting and the sponsorship its recurring invoice
    // and first invoice. Whatever ITFlow isn't taking (or when it can't be reached) is emailed below.
    $itflow_hosting = $want_hosting && $linked;
    $itflow_sponsor = $want_sponsor && $linked_sponsor;
    if (!$errors && !$is_bot && ($itflow_hosting || $itflow_sponsor)) {
        $person = function ($key) {
            return ['name' => line($key . '_name'), 'email' => line($key . '_email'), 'phone' => line($key . '_phone'), 'title' => line($key . '_title'), 'mobile' => line($key . '_mobile')];
        };
        $primary = $person('primary');
        $ask = [
            // The same form sent twice is one sign-up
            'ref' => substr(hash('sha256', $ts . '|' . $sig . '|' . strtolower($primary_email)), 0, 40),
            'hosting' => $itflow_hosting,
            'company' => ['name' => $company, 'address' => line('address'), 'city' => line('city'), 'state' => line('state'), 'postal' => line('postal', 20),
                'country' => line('country'), 'phone' => line('primary_phone'), 'website' => $sponsor_url],
            'contacts' => [
                'primary' => $primary,
                'billing' => isset($_POST['billing_same']) ? $primary : $person('billing'),
                'technical' => isset($_POST['technical_same']) ? $primary : $person('technical'),
            ],
            'comments' => mb_substr(field('comments'), 0, 5000),
        ];
        if ($itflow_hosting) {
            $ask += [
                'region' => $region,
                'seats' => $seats,
                'access' => $access,
                'subdomain' => $access === 'itflowapp' ? strtolower(line('app_subdomain', 63)) : '',
                'hostname' => $access === 'own' ? $hostname : '',
                'timezone' => $timezone,
            ];
        }
        if ($itflow_sponsor) {
            $ask['sponsor'] = [
                'tier' => $tier_key,
                'amount' => $sponsor_total,
                'anonymous' => $sponsor_anonymous ? 1 : 0,
                'display_name' => $sponsor_display,
                'url' => $sponsor_url,
                'forum_users' => $forum_users,
                'forum_user' => $forum_users[0] ?? '',  // the first, for an add-on that takes one
            ];
        }
        $answer = itflowAsk($ask, $status);
        if ($status === 200 && !empty($answer['ok'])) {
            $created = $itflow_hosting ? $answer : null;
            $created_sponsor = $itflow_sponsor && is_array($answer['sponsor'] ?? null) ? $answer['sponsor'] : null;
            // Done, unless part of it still has to go by email
            $sent = !($want_hosting && !$created) && !($want_sponsor && !$created_sponsor);
        } elseif ($status === 422 && is_array($answer) && !empty($answer['error'])) {
            $errors[] = $answer['error'];
        }
        // Couldn't reach ITFlow - emailed below like before, so the sign-up isn't lost
    }

    // Send
    if (!$errors && !$is_bot && !$sent) {
        $monthly = $hosting_total + $sponsor_total;
        $parts = [];
        if ($want_hosting) {
            $parts[] = "Hosted instance ($seats technicians)";
        }
        if ($want_sponsor) {
            $parts[] = 'Sponsorship';
        }

        $body  = "New sign-up from itflow.org\n";
        $body .= str_repeat('=', 40) . "\n\n";
        $body .= "Requested:      " . implode(' + ', $parts) . "\n";
        $body .= "Monthly total:  $" . number_format($monthly, 2) . "\n\n";

        if ($want_hosting) {
            $body .= "HOSTED INSTANCE\n";
            $body .= "  Technicians:  $seats ($included_seats included, " . max(0, $seats - $included_seats) . " additional)\n";
            $body .= "  Price:        $" . number_format($hosting_total, 2) . "/month\n";
            $body .= "  Region:       {$regions[$region]}\n";
            $body .= "  Hostname:     $hostname " . ($access === 'own' ? '(their own domain - needs DNS)' : "($app_domain)") . "\n";
            $body .= "  Time zone:    $timezone ($timezone_from)\n";
            $body .= $created
                ? "  In ITFlow:    signed up as {$created['address']}, invoice {$created['invoice']['number']} sent - built once it is paid\n\n"
                : ($linked ? "  In ITFlow:    NOT created - ITFlow could not be reached. Add it by hand.\n\n" : "\n");
        }
        if ($want_sponsor) {
            $body .= "SPONSORSHIP\n";
            $body .= "  Tier:         {$sponsor_tiers[$tier_key]['name']}\n";
            $body .= "  Price:        $" . number_format($sponsor_total, 2) . "/month\n";
            if ($sponsor_anonymous) {
                $body .= "  Anonymous:    yes - not shown on itflow.org\n";
            } else {
                $body .= "  Display name: " . ($sponsor_display ?: $company) . "\n";
                $body .= "  Website:      " . ($sponsor_url ?: '-') . "\n";
            }
            $body .= "  Forum users:  " . ($forum_users ? implode(', ', $forum_users) : '-') . "\n";
            $body .= $created_sponsor
                ? "  In ITFlow:    signed up, invoice {$created_sponsor['invoice']['number']} sent - starts once it is paid\n\n"
                : ($linked_sponsor ? "  In ITFlow:    NOT created - ITFlow could not be reached. Add it by hand.\n\n" : "\n");
        }

        $body .= "COMPANY\n";
        $body .= "  $company\n";
        if (line('address') !== '') {
            $body .= '  ' . line('address') . "\n";
        }
        $city_line = trim(line('city') . ', ' . line('state') . ' ' . line('postal'), ', ');
        if ($city_line !== '') {
            $body .= "  $city_line\n";
        }
        if (line('country') !== '') {
            $body .= '  ' . line('country') . "\n";
        }

        $body .= "\nPRIMARY CONTACT" . ($want_hosting ? ' (first admin login)' : '') . "\n";
        $body .= "  Name:   $primary_name\n";
        $body .= "  Title:  " . (line('primary_title') ?: '-') . "\n";
        $body .= "  Email:  $primary_email\n";
        $body .= "  Phone:  " . (line('primary_phone') ?: '-') . "\n";
        $body .= "  Mobile: " . (line('primary_mobile') ?: '-') . "\n\n";
        $body .= "BILLING CONTACT\n  {$contact_info['billing']}\n\n";
        $body .= "TECHNICAL CONTACT\n  {$contact_info['technical']}\n\n";

        $comments = mb_substr(field('comments'), 0, 5000);
        if ($comments !== '') {
            $body .= "COMMENTS\n" . $comments . "\n\n";
        }
        $body .= str_repeat('-', 40) . "\n";
        $body .= "Submitted " . date('Y-m-d H:i T') . " from " . ($_SERVER['REMOTE_ADDR'] ?? '?') . "\n";

        $subject = 'Sign-up: ' . $company . ($want_hosting ? " - $seats technicians" : '') . ($want_sponsor ? ' - sponsor' : '');

        require_once __DIR__ . '/includes/mailer.php';
        if (sendMail($notify_to, $subject, $body, $primary_email)) {
            $sent = true;
        } else {
            $errors[] = 'We could not send your request just now. Try again in a few minutes, or email us at ' . $notify_to[0] . '.';
        }
    } elseif ($is_bot && !$errors) {
        $sent = true; // look successful, send nothing
    }
}

$ts = time();
$sig = hash_hmac('sha256', (string) $ts, $signup['form_secret']);

// The heading follows what's on offer
if ($offer_hosting && $offer_sponsor) {
    $page_heading = 'Hosted ITFlow and sponsorship';
    $page_lede = $linked
        ? 'Sign up for your own hosted ITFlow, sponsor the project, or both. Hosted ITFlow is ready a few minutes after you pay your first invoice.'
        : 'Sign up for your own hosted ITFlow, sponsor the project, or both. Nothing is charged until we have confirmed everything with you.';
} elseif ($offer_hosting) {
    $page_heading = 'Hosted ITFlow';
    $page_lede = $linked
        ? 'Your own ITFlow, run, updated and backed up by the people who build it. Ready a few minutes after you pay your first invoice.'
        : 'Your own ITFlow, run, updated and backed up by the people who build it. Nothing is charged until we have confirmed everything with you.';
} else {
    $page_heading = 'Sponsor ITFlow';
    $page_lede = $linked_sponsor
        ? 'Help keep ITFlow free and open source. Your sponsorship starts as soon as you pay your first invoice.'
        : 'Help keep ITFlow free and open source. Nothing is charged until we have confirmed everything with you.';
}

// What ITFlow made, to pay now - one Pay button each
$to_pay = [];
if ($created && !empty($created['invoice']['pay_url'])) {
    $to_pay[] = ['what' => 'Hosted ITFlow', 'detail' => $created['address'] ?? '', 'icon' => 'fas fa-cloud', 'invoice' => $created['invoice']];
}
if ($created_sponsor && !empty($created_sponsor['invoice']['pay_url'])) {
    $to_pay[] = ['what' => 'Sponsorship', 'detail' => $created_sponsor['tier_name'] ?? '', 'icon' => 'fas fa-hand-holding-heart', 'invoice' => $created_sponsor['invoice']];
}
// What a new sponsorship brings once its invoice is paid, for the thank-you
$sponsor_starts = [];
if ($created_sponsor) {
    $created_tier = isset($sponsor_tiers[$created_sponsor['tier'] ?? '']) ? $created_sponsor['tier'] : $tier_key;
    $created_show = sponsorShow(['tier' => $created_tier]);
    if (tierHas($created_tier, 'whitelabel', $sponsor_tiers)) {
        $sponsor_starts[] = 'we email <strong>' . e($primary_email) . '</strong> your white-label key';
    }
    if (!$sponsor_anonymous && $created_show === 'name') {
        $sponsor_starts[] = 'your name goes up on itflow.org';
    }
    if ($forum_users) {
        $sponsor_starts[] = 'the heart goes next to ' . e(listNames($forum_users)) . ' on the forum';
    }
}

$page_title = $page_heading . ' - ITFlow';
$page_description = $page_lede;
require __DIR__ . '/includes/header.php';

// Step numbers: the script renumbers them to whatever is shown; these are the no-script defaults
$step = 0;
?>

<section class="wrap page-head">
    <h1><?= e($created ? 'Thanks for signing up' : ($created_sponsor ? 'Thanks for sponsoring ITFlow' : ($sent ? 'Thanks, we have it' : $page_heading))) ?></h1>
    <?php if (!$sent && !$created && !$created_sponsor) { ?><p><?= e($page_lede) ?></p><?php } ?>
</section>

<section class="wrap signup">

<?php if ($created || $created_sponsor) { ?>
    <div class="done">
        <span class="done-mark"><i class="fas fa-check" aria-hidden="true"></i></span>
        <h2><?= count($to_pay) > 1 ? 'One step left: pay your first invoices' : 'One step left: pay your first invoice' ?></h2>
        <?php if ($created) { ?>
            <p>Your ITFlow will be at <strong><?= e($created['app_address'] ?? $created['address'] ?? '') ?></strong>. As soon as invoice <?= e($created['invoice']['number'] ?? '') ?> is paid we build it - usually within a few minutes - and email <strong><?= e($primary_email) ?></strong> a link to set your password.</p>
            <?php if (($created['address'] ?? '') !== ($created['app_address'] ?? '')) { ?>
                <p>To use <strong><?= e($created['address']) ?></strong>, we'll email you the DNS record to add once it's built, and switch you over as soon as it's in.</p>
            <?php } ?>
        <?php } ?>
        <?php if ($created_sponsor) { ?>
            <p>Your sponsorship starts as soon as invoice <?= e($created_sponsor['invoice']['number'] ?? '') ?> is paid<?= $sponsor_starts ? ': ' . listNames($sponsor_starts) : '' ?>.<?= !$sponsor_anonymous && $created_show !== 'name' ? ' Reply to our email with your logo and we put it up on itflow.org.' : '' ?></p>
        <?php } ?>

        <?php if (count($to_pay) === 1) { ?>
            <a class="button button-primary" href="<?= e($to_pay[0]['invoice']['pay_url']) ?>">Pay <?= e(money($to_pay[0]['invoice']['amount'] ?? 0)) ?> now</a>
        <?php } elseif ($to_pay) { ?>
            <ul class="pay-list">
                <?php foreach ($to_pay as $pay) { ?>
                    <li class="pay-item">
                        <span class="pay-icon" aria-hidden="true"><i class="<?= $pay['icon'] ?>"></i></span>
                        <span class="pay-what">
                            <strong><?= e($pay['what']) ?></strong>
                            <small><?= e($pay['detail']) ?> &middot; invoice <?= e($pay['invoice']['number'] ?? '') ?></small>
                        </span>
                        <a class="button button-primary button-small" href="<?= e($pay['invoice']['pay_url']) ?>">Pay <?= e(money($pay['invoice']['amount'] ?? 0)) ?></a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
        <p class="hint" style="margin-top: 14px"><?= count($to_pay) > 1 ? 'Both invoices are' : 'The invoice is' ?> in your email too<?= $want_sponsor && !$created_sponsor ? ", and we'll be in touch about your sponsorship" : '' ?><?= $want_hosting && !$created ? ", and we'll be in touch about your hosted ITFlow" : '' ?>.</p>
    </div>
<?php } elseif ($sent) { ?>
    <div class="done">
        <span class="done-mark"><i class="fas fa-check" aria-hidden="true"></i></span>
        <h2>We'll be in touch within one business day</h2>
        <p>We'll review your request and email the primary contact with the next steps and your first invoice.</p>
        <a class="button button-primary" href="/">Back to ITFlow</a>
    </div>
<?php } else { ?>

    <?php if ($errors) { ?>
        <div class="alert" role="alert">
            <strong>Fix these and send it again:</strong>
            <ul><?php foreach ($errors as $err) { echo '<li>' . e($err) . '</li>'; } ?></ul>
        </div>
    <?php } ?>
    <div class="alert" id="clientErrors" role="alert" hidden>
        <strong>Fill in the highlighted fields.</strong> Fields marked <span class="req">*</span> are required.
    </div>

    <form method="post" action="" id="signupForm" novalidate>
        <input type="hidden" name="ts" value="<?= $ts ?>">
        <input type="hidden" name="sig" value="<?= $sig ?>">
        <div class="hp" aria-hidden="true">
            <label>Leave this empty <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="signup-grid">
        <div>

            <?php if ($offer_hosting && $offer_sponsor) { ?>
            <!-- What -->
            <div class="panel">
                <h2 class="panel-title"><span class="tour-num" data-num="what"><?= ++$step ?></span>What would you like?</h2>
                <div class="choices">
                    <label class="choice">
                        <input type="checkbox" name="want_hosting" value="1" <?= checked('want_hosting', '1', $default_hosting) ?>>
                        <strong>Hosted ITFlow</strong>
                        <small>Your own ITFlow, kept up to date and backed up nightly. <?= money($base_price) ?> a month with <?= (int) $included_seats ?> technicians.</small>
                    </label>
                    <label class="choice">
                        <input type="checkbox" name="want_sponsor" value="1" <?= checked('want_sponsor', '1', $default_sponsor) ?>>
                        <strong>Sponsor ITFlow</strong>
                        <small>Help keep ITFlow free and open source, and put your name or logo in front of the community.</small>
                    </label>
                </div>
                <p class="feedback" id="wantFeedback">Choose hosted ITFlow, a sponsorship, or both.</p>
            </div>
            <?php } elseif ($offer_hosting) { ?>
                <input type="hidden" name="want_hosting" value="1">
            <?php } else { ?>
                <input type="hidden" name="want_sponsor" value="1">
            <?php } ?>

            <?php if ($offer_hosting) { ?>
            <!-- Hosting -->
            <div class="panel" data-show="hosting">
                <h2 class="panel-title"><span class="tour-num" data-num="hosting"><?= ++$step ?></span>Your instance</h2>
                <div class="fields">
                    <div class="span-6">
                        <label class="label" for="seats">Technicians <span class="req" aria-hidden="true">*</span></label>
                        <div class="input-group stepper">
                            <button type="button" data-step="-1" aria-label="One fewer">&minus;</button>
                            <input type="number" class="input" id="seats" name="seats" min="<?= (int) $included_seats ?>" max="<?= (int) $max_seats ?>" value="<?= old('seats', $default_seats) ?>" required>
                            <button type="button" data-step="1" aria-label="One more">+</button>
                        </div>
                        <p class="hint"><?= (int) $included_seats ?> included, then <?= money($seat_price) ?> a month each. Clients and their portal contacts are free.</p>
                    </div>
                    <div class="span-6">
                        <label class="label" for="region">Region <span class="req" aria-hidden="true">*</span></label>
                        <select class="select" id="region" name="region" required>
                            <?php foreach ($regions as $k => $label) { ?>
                                <option value="<?= e($k) ?>" <?= (($_POST['region'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php } ?>
                        </select>
                        <?php if (count($regions) === 1) { ?><p class="hint">More regions are on the way.</p><?php } ?>
                    </div>

                    <div>
                        <span class="label">How will you reach it? <span class="req" aria-hidden="true">*</span></span>
                        <div class="choices">
                            <label class="choice">
                                <input type="radio" name="access" value="itflowapp" required <?= checked('access', 'itflowapp', true) ?>>
                                <strong>An <?= e($app_domain) ?> address</strong>
                                <small>Ready straight away, no DNS changes.</small>
                                <span class="input-group" data-access="itflowapp">
                                    <input type="text" class="input input-sm" name="app_subdomain" placeholder="yourcompany" value="<?= old('app_subdomain') ?>" maxlength="63" pattern="[A-Za-z0-9]([A-Za-z0-9\-]{1,61}[A-Za-z0-9])?" title="Letters, numbers and dashes, 3-63 characters" autocapitalize="off" spellcheck="false" required aria-label="Your <?= e($app_domain) ?> name" data-check="subdomain">
                                    <span class="input-addon">.<?= e($app_domain) ?></span>
                                </span>
                                <span class="hint name-check" data-access="itflowapp" id="nameCheck" aria-live="polite"></span>
                            </label>
                            <label class="choice">
                                <input type="radio" name="access" value="own" required <?= checked('access', 'own') ?>>
                                <strong>Your own domain</strong>
                                <small><?= $linked ? "You start on $app_domain; we email you the DNS record to add and switch you over once it's in." : "We'll send you the DNS record to add before we go live." ?></small>
                                <input type="text" class="input input-sm" data-access="own" name="own_hostname" placeholder="itflow.yourcompany.com" value="<?= old('own_hostname') ?>" maxlength="253" pattern="([A-Za-z0-9]([A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}" title="A hostname such as itflow.yourcompany.com" autocapitalize="off" spellcheck="false" required aria-label="Your hostname">
                            </label>
                        </div>
                    </div>
                </div>
                <!-- Their time zone, filled in from the browser: their ITFlow is set up in it -->
                <input type="hidden" id="timezone" name="timezone" value="<?= old('timezone') ?>">
            </div>
            <?php } ?>

            <?php if ($offer_sponsor) { ?>
            <!-- Sponsorship -->
            <div class="panel" data-show="sponsor">
                <h2 class="panel-title"><span class="tour-num" data-num="sponsor"><?= ++$step ?></span>Sponsorship</h2>
                <div class="sponsor-intro">
                    <i class="fas fa-hand-holding-heart" aria-hidden="true"></i>
                    <p>ITFlow is free and open source, built by a small team alongside running our own MSPs. Sponsors keep it that way.<?php if ($sponsor_names !== '') { ?> Join <strong><?= e($sponsor_names) ?></strong>.<?php } ?></p>
                </div>
                <div class="choices tier-choices">
                    <?php foreach ($sponsor_tiers as $k => $t) { ?>
                        <label class="choice tier-choice">
                            <input type="radio" class="tier-radio" name="sponsor_tier" value="<?= e($k) ?>" data-price="<?= (int) $t['price'] ?>" data-show-as="<?= e(sponsorShow(['tier' => $k])) ?>" <?= !empty($t['custom']) ? 'data-custom="1"' : '' ?> required <?= checked('sponsor_tier', $k, $k === $default_tier) ?>>
                            <?= tierHead($t) ?>
                            <?= tierPerks($t, $sponsor_tiers) ?>
                            <?php if (!empty($t['custom'])) { ?>
                                <span class="input-group" data-custom-amount>
                                    <span class="input-addon">$</span>
                                    <input type="number" class="input input-sm" name="sponsor_amount" min="<?= (int) $t['price'] ?>" step="1" value="<?= old('sponsor_amount', $t['price']) ?>" aria-label="Monthly amount" required>
                                    <span class="input-addon">/mo</span>
                                </span>
                            <?php } ?>
                        </label>
                    <?php } ?>
                </div>
                <p class="feedback" id="tierFeedback">Choose a sponsorship tier.</p>
                <?php // Name or logo in the hints below, for the tier chosen - the script keeps them in step
                $form_tier = $_SERVER['REQUEST_METHOD'] === 'POST' ? field('sponsor_tier') : $default_tier;
                $form_logo = isset($sponsor_tiers[$form_tier]) && sponsorShow(['tier' => $form_tier]) !== 'name'; ?>
                <label class="choice sponsor-anonymous-choice">
                    <input type="checkbox" name="sponsor_anonymous" value="1" <?= checked('sponsor_anonymous') ?>>
                    <strong>Sponsor anonymously</strong>
                    <small>Leave your name and logo off itflow.org - you join the many who sponsor anonymously. Everything else in your tier stays the same.</small>
                </label>
                <div class="fields" style="margin-top: 22px">
                    <div class="span-6" data-shown-name>
                        <label class="label" for="sponsor_display">Name to show <span class="label-light">if it isn't your company name</span></label>
                        <input type="text" class="input" id="sponsor_display" name="sponsor_display" value="<?= old('sponsor_display') ?>" maxlength="100">
                        <p class="hint">How we name you on itflow.org.<span data-for-logo<?= $form_logo ? '' : ' hidden' ?>> We'll ask you for your logo by email.</span></p>
                    </div>
                    <div class="span-6" data-shown-name>
                        <label class="label" for="sponsor_url">Your website <span class="label-light">optional</span></label>
                        <!-- Text, not url: people type yourcompany.com, and the https:// is added for them -->
                        <input type="text" inputmode="url" class="input" id="sponsor_url" name="sponsor_url" value="<?= old('sponsor_url') ?>" maxlength="500" placeholder="yourcompany.com" autocomplete="url" autocapitalize="off" spellcheck="false">
                        <p class="hint">Where your <span data-for-name<?= $form_logo ? ' hidden' : '' ?>>name</span><span data-for-logo<?= $form_logo ? '' : ' hidden' ?>>logo</span> on itflow.org links to.</p>
                    </div>
                    <div>
                        <label class="label" for="sponsor_forum_users">Forum usernames <span class="label-light">optional</span></label>
                        <!-- As many as they like: commas, spaces or new lines between them, an @ in front or not -->
                        <input type="text" class="input" id="sponsor_forum_users" name="sponsor_forum_users" value="<?= old('sponsor_forum_users') ?>" maxlength="2000" placeholder="jsmith, alex.r, sam_t" pattern="[@A-Za-z0-9_.,;\s\-]*" title="Usernames have only letters, numbers, dots, dashes and underscores - put commas between them" autocapitalize="off" autocomplete="off" spellcheck="false">
                        <p class="hint">Everyone on your team who's on the ITFlow forum - each gets a heart next to their name. Put commas between them.</p>
                    </div>
                </div>
            </div>
            <?php } ?>

            <!-- Company -->
            <div class="panel">
                <h2 class="panel-title"><span class="tour-num" data-num="company"><?= ++$step ?></span>Your company</h2>
                <div class="fields">
                    <div>
                        <label class="label" for="company">Company name<?= reqStar('company') ?></label>
                        <input type="text" class="input" id="company" name="company" value="<?= old('company') ?>" maxlength="200" autocomplete="organization"<?= reqAttr('company') ?>>
                    </div>
                    <div>
                        <label class="label" for="address">Address<?= reqStar('address') ?></label>
                        <input type="text" class="input" id="address" name="address" value="<?= old('address') ?>" maxlength="200" autocomplete="street-address"<?= reqAttr('address') ?>>
                    </div>
                    <div class="span-5">
                        <label class="label" for="city">City<?= reqStar('city') ?></label>
                        <input type="text" class="input" id="city" name="city" value="<?= old('city') ?>" maxlength="100" autocomplete="address-level2"<?= reqAttr('city') ?>>
                    </div>
                    <div class="span-3">
                        <label class="label" for="state">State<?= reqStar('state') ?></label>
                        <input type="text" class="input" id="state" name="state" value="<?= old('state') ?>" maxlength="100" autocomplete="address-level1"<?= reqAttr('state') ?>>
                    </div>
                    <div class="span-4">
                        <label class="label" for="postal">Postal code<?= reqStar('postal') ?></label>
                        <input type="text" class="input" id="postal" name="postal" value="<?= old('postal') ?>" maxlength="20" autocomplete="postal-code"<?= reqAttr('postal') ?>>
                    </div>
                    <div class="span-5">
                        <label class="label" for="country">Country<?= reqStar('country') ?></label>
                        <input type="text" class="input" id="country" name="country" value="<?= old('country', 'United States') ?>" maxlength="100" autocomplete="country-name"<?= reqAttr('country') ?>>
                    </div>
                </div>
            </div>

            <!-- Contacts -->
            <div class="panel">
                <h2 class="panel-title"><span class="tour-num" data-num="contacts"><?= ++$step ?></span>Contacts</h2>
                <?php if ($offer_hosting) { ?><p class="panel-note" data-show="hosting-note">The primary contact gets the first admin login for your instance.</p><?php } ?>
                <div class="fields">
                    <div class="span-6">
                        <label class="label" for="primary_name">Name<?= reqStar('primary_name') ?></label>
                        <input type="text" class="input" id="primary_name" name="primary_name" value="<?= old('primary_name') ?>" maxlength="200" autocomplete="name"<?= reqAttr('primary_name') ?>>
                    </div>
                    <div class="span-6">
                        <label class="label" for="primary_title">Title<?= reqStar('primary_title') ?></label>
                        <input type="text" class="input" id="primary_title" name="primary_title" value="<?= old('primary_title') ?>" maxlength="200" autocomplete="organization-title"<?= reqAttr('primary_title') ?>>
                    </div>
                    <div class="span-6">
                        <label class="label" for="primary_email">Email<?= reqStar('primary_email') ?></label>
                        <input type="email" class="input" id="primary_email" name="primary_email" value="<?= old('primary_email') ?>" maxlength="200" autocomplete="email"<?= reqAttr('primary_email') ?>>
                    </div>
                    <div class="span-3">
                        <label class="label" for="primary_phone">Phone<?= reqStar('phone') ?></label>
                        <input type="tel" class="input" id="primary_phone" name="primary_phone" value="<?= old('primary_phone') ?>" maxlength="50" autocomplete="tel">
                    </div>
                    <div class="span-3">
                        <label class="label" for="primary_mobile">Mobile<?= reqStar('phone') ?></label>
                        <input type="tel" class="input" id="primary_mobile" name="primary_mobile" value="<?= old('primary_mobile') ?>" maxlength="50">
                    </div>
                    <?php if (isRequired('phone')) { ?>
                        <p class="hint" id="phoneHint" style="margin-top: -8px">A phone or a mobile number is required.</p>
                    <?php } ?>
                </div>

                <?php foreach (['billing' => 'Billing contact', 'technical' => 'Technical contact'] as $k => $label) { ?>
                    <div class="contact-block">
                        <div class="contact-block-head">
                            <span class="label" style="margin: 0"><?= $label ?></span>
                            <label class="check"><input type="checkbox" id="<?= $k ?>_same" name="<?= $k ?>_same" value="1" data-same="<?= $k ?>" <?= checked($k . '_same', '1', true) ?>>Same as primary contact</label>
                        </div>
                        <div class="fields" id="<?= $k ?>_fields">
                            <div class="span-4"><input type="text" class="input" name="<?= $k ?>_name" placeholder="Name *" aria-label="<?= $label ?> name" value="<?= old($k . '_name') ?>" maxlength="200" required></div>
                            <div class="span-5"><input type="email" class="input" name="<?= $k ?>_email" placeholder="Email *" aria-label="<?= $label ?> email" value="<?= old($k . '_email') ?>" maxlength="200" required></div>
                            <div class="span-3"><input type="tel" class="input" name="<?= $k ?>_phone" placeholder="Phone" aria-label="<?= $label ?> phone" value="<?= old($k . '_phone') ?>" maxlength="50"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- Comments -->
            <div class="panel">
                <h2 class="panel-title"><span class="tour-num" data-num="comments"><?= ++$step ?></span>Anything else?</h2>
                <textarea class="textarea" name="comments" rows="4" maxlength="5000" aria-label="Anything else" placeholder="Moving from another PSA? Need your data imported? Questions? Tell us here."><?= old('comments') ?></textarea>
            </div>

        </div>

        <!-- Summary -->
        <aside class="panel signup-summary" aria-label="Monthly total">
            <h2 class="panel-title">Each month</h2>
            <div class="summary-lines" id="summaryLines"><p class="hint">Choose an option to see your price.</p></div>
            <div class="summary-total">
                <span class="label" style="margin: 0">Total</span>
                <span><span class="total" id="summaryTotal">$0</span> <span class="label-light">a month</span></span>
            </div>
            <?php if ($linked && $linked_sponsor) {
                // Which note fits what is ticked - the script swaps them as it changes
                $ticked_hosting = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['want_hosting']) : $default_hosting;
                $ticked_sponsor = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['want_sponsor']) : $default_sponsor;
                $note = $ticked_hosting && $ticked_sponsor ? 'both' : ($ticked_sponsor ? 'sponsor' : 'hosting'); ?>
                <p class="summary-note" data-linked-note data-note="hosting"<?= $note === 'hosting' ? '' : ' hidden' ?>>Hosted ITFlow: your first invoice comes next, and your ITFlow is built as soon as it is paid.</p>
                <p class="summary-note" data-linked-note data-note="sponsor"<?= $note === 'sponsor' ? '' : ' hidden' ?>>Sponsorship: your first invoice comes next, and your sponsorship starts as soon as it is paid.</p>
                <p class="summary-note" data-linked-note data-note="both"<?= $note === 'both' ? '' : ' hidden' ?>>A first invoice for each comes next. Your ITFlow is built, and your sponsorship starts, as soon as each is paid.</p>
            <?php } elseif ($linked) { ?>
                <p class="summary-note" data-linked-note>Hosted ITFlow: your first invoice comes next, and your ITFlow is built as soon as it is paid.</p>
            <?php } elseif ($linked_sponsor) { ?>
                <p class="summary-note" data-linked-note>Sponsorship: your first invoice comes next, and your sponsorship starts as soon as it is paid.</p>
            <?php } else { ?>
                <p class="summary-note">Nothing is charged now. We'll confirm everything with you and send your first invoice.</p>
            <?php } ?>
            <button type="submit" class="button button-primary button-block"><?= $linked || $linked_sponsor ? 'Sign up' : 'Send request' ?></button>
        </aside>
        </div>
    </form>
<?php } ?>

</section>

<?php ob_start(); ?>
<script>
(function () {
    'use strict';
    var form = document.getElementById('signupForm');
    if (!form) {
        return;
    }

    var BASE = <?= (int) $base_price ?>, INCLUDED = <?= (int) $included_seats ?>, SEAT = <?= (int) $seat_price ?>, MAX = <?= (int) $max_seats ?>;
    var PHONE_REQUIRED = <?= isRequired('phone') ? 'true' : 'false' ?>;
    function $(s) { return form.querySelector(s); }
    function money(n) { return '$' + n.toLocaleString('en-US'); }

    // Is hosting / sponsorship chosen? (a hidden input when only one is on offer)
    function chosen(name) {
        var el = $('[name=' + name + ']');
        return !!el && (el.type === 'hidden' || el.checked);
    }

    // The time zone, from the browser
    var tz = $('#timezone');
    if (tz && !tz.value) {
        try { tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
    }

    var stepper = $('.stepper');
    if (stepper) {
        stepper.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-step]');
            if (!b) {
                return;
            }
            var s = $('#seats');
            s.value = Math.min(MAX, Math.max(INCLUDED, (parseInt(s.value, 10) || INCLUDED) + parseInt(b.getAttribute('data-step'), 10)));
            update();
        });
    }

    // Hide a block and take its fields out of validation and the submission
    function showBlock(el, show) {
        if (!el) {
            return;
        }
        el.hidden = !show;
        el.querySelectorAll('input, select, textarea, button').forEach(function (f) { f.disabled = !show; });
    }

    function row(label, amount) {
        var d = document.createElement('div');
        d.className = 'summary-line';
        var a = document.createElement('span');
        var b = document.createElement('span');
        a.textContent = label;
        b.textContent = money(amount);
        d.appendChild(a);
        d.appendChild(b);
        return d;
    }

    function update() {
        var hosting = chosen('want_hosting');
        var sponsor = chosen('want_sponsor');
        showBlock($('[data-show=hosting]'), hosting);
        showBlock($('[data-show=sponsor]'), sponsor);
        var note = $('[data-show=hosting-note]');
        if (note) {
            note.hidden = !hosting;
        }
        // The summary's note on what happens next, for what is ticked
        var noteKey = hosting && sponsor ? 'both' : (sponsor ? 'sponsor' : 'hosting');
        form.querySelectorAll('[data-note]').forEach(function (n) {
            n.hidden = n.getAttribute('data-note') !== noteKey;
        });

        // Step numbers follow whatever is shown
        var n = 1;
        ['what', 'hosting', 'sponsor', 'company', 'contacts', 'comments'].forEach(function (k) {
            var el = $('[data-num=' + k + ']');
            if (el && !el.closest('[hidden]')) {
                el.textContent = n++;
            }
        });

        if (hosting) {
            var accessChecked = form.querySelector('[name=access]:checked');
            var access = accessChecked ? accessChecked.value : '';
            form.querySelectorAll('[data-access]').forEach(function (el) {
                var on = el.getAttribute('data-access') === access;
                el.hidden = !on;
                (el.matches('input') ? [el] : Array.prototype.slice.call(el.querySelectorAll('input'))).forEach(function (f) { f.disabled = !on; });
            });
        }

        form.querySelectorAll('[data-same]').forEach(function (cb) {
            showBlock(document.getElementById(cb.getAttribute('data-same') + '_fields'), !cb.checked);
        });

        var tier = form.querySelector('[name=sponsor_tier]:checked');
        if (sponsor) {
            showBlock($('[data-custom-amount]'), !!(tier && tier.getAttribute('data-custom')));
            // Anonymous: no name or website to ask for. The hints say name or logo, for the tier.
            var anonymous = $('[name=sponsor_anonymous]');
            form.querySelectorAll('[data-shown-name]').forEach(function (el) {
                showBlock(el, !(anonymous && anonymous.checked));
            });
            var logo = !!tier && tier.getAttribute('data-show-as') !== 'name';
            form.querySelectorAll('[data-for-logo]').forEach(function (el) { el.hidden = !logo; });
            form.querySelectorAll('[data-for-name]').forEach(function (el) { el.hidden = logo; });
        }

        // Rules the browser can't express on its own
        var want = $('[name=want_hosting][type=checkbox]');
        if (want) {
            want.setCustomValidity(hosting || sponsor ? '' : 'Choose at least one');
            document.getElementById('wantFeedback').classList.toggle('is-shown', !(hosting || sponsor));
        }
        var tierFeedback = document.getElementById('tierFeedback');
        if (tierFeedback) {
            tierFeedback.classList.toggle('is-shown', sponsor && !tier);
        }
        if (PHONE_REQUIRED) {
            var none = !$('#primary_phone').value.trim() && !$('#primary_mobile').value.trim();
            $('#primary_phone').setCustomValidity(none ? 'A phone or a mobile number is required' : '');
            $('#primary_mobile').setCustomValidity(none ? 'A phone or a mobile number is required' : '');
        }

        // The summary
        var lines = document.getElementById('summaryLines');
        var total = 0;
        lines.textContent = '';
        if (hosting) {
            var seats = Math.max(INCLUDED, parseInt($('#seats').value, 10) || INCLUDED);
            var extra = seats - INCLUDED;
            lines.appendChild(row('Hosted ITFlow, ' + INCLUDED + ' technicians', BASE));
            if (extra > 0) {
                lines.appendChild(row(extra + ' more technician' + (extra > 1 ? 's' : '') + ' at ' + money(SEAT), extra * SEAT));
            }
            total += BASE + extra * SEAT;
        }
        if (sponsor && tier) {
            var p = parseInt(tier.getAttribute('data-price'), 10);
            if (tier.getAttribute('data-custom')) {
                p = Math.max(p, parseInt($('[name=sponsor_amount]').value, 10) || p);
            }
            lines.appendChild(row(tier.closest('.choice').querySelector('.tier-name').textContent, p));
            total += p;
        }
        if (!lines.children.length) {
            var hint = document.createElement('p');
            hint.className = 'hint';
            hint.textContent = 'Choose an option to see your price.';
            lines.appendChild(hint);
        }
        document.getElementById('summaryTotal').textContent = money(total);
    }

    form.addEventListener('submit', function (ev) {
        update();
        if (!form.checkValidity()) {
            ev.preventDefault();
            form.classList.add('was-validated');
            document.getElementById('clientErrors').hidden = false;
            var first = form.querySelector(':invalid:not(fieldset)');
            if (first) {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function () { first.focus({ preventScroll: true }); }, 300);
            }
        }
    });

    // Is the name free? Asked of ITFlow as they type, when the form is linked to it
    var nameInput = $('[data-check=subdomain]');
    var nameCheck = document.getElementById('nameCheck');
    var nameTimer = null;
    if (nameInput && nameCheck && <?= $linked ? 'true' : 'false' ?>) {
        nameInput.addEventListener('input', function () {
            clearTimeout(nameTimer);
            nameInput.setCustomValidity('');
            nameCheck.textContent = '';
            nameCheck.className = 'hint name-check';
            var value = nameInput.value.trim().toLowerCase();
            if (value.length < 3) {
                return;
            }
            nameTimer = setTimeout(function () {
                fetch('signup.php?check=1&subdomain=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (a) {
                        if (nameInput.value.trim().toLowerCase() !== value || a.ok === null) {
                            return;
                        }
                        nameCheck.textContent = a.ok ? value + '.<?= e($app_domain) ?> is free' : a.error;
                        nameCheck.className = 'hint name-check ' + (a.ok ? 'is-free' : 'is-taken');
                        nameInput.setCustomValidity(a.ok ? '' : a.error);
                    })
                    .catch(function () {});
            }, 400);
        });
    }

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
})();
</script>
<?php
$page_scripts = ob_get_clean();
require __DIR__ . '/includes/footer.php';
