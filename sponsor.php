<?php
/*
 * itflow.org/sponsor.php - the short link to sponsoring ITFlow, to share on the
 * forum, in the ITFlow README and anywhere else. sponsor.php?tier=premier picks
 * a tier too.
 *
 * While sponsorships are taken here it goes to the sign-up with sponsorship
 * ticked (signup.php?want=sponsor). Otherwise it goes where the "Your logo here"
 * tile does ($sponsors_section['join']['url']), or to the sponsors on the homepage.
 * Not a permanent redirect, so browsers follow the config as it changes.
 */

require_once __DIR__ . '/includes/bootstrap.php';

if (sectionOn('sponsorship') && signupOn()) {
    $to = 'signup.php?want=sponsor';
    $tier = (string) ($_GET['tier'] ?? '');
    if (isset($sponsor_tiers[$tier])) {
        $to .= '&tier=' . rawurlencode($tier);
    }
} else {
    $to = trim((string) ($sponsors_section['join']['url'] ?? ''));
    if ($to === '') {
        $to = '/#sponsors';
    }
}

header('Location: ' . $to, true, 302);
exit;
