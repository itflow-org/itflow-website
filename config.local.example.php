<?php
/*
 * Copy this to config.local.php on the server (it is kept out of git) and
 * put the real secrets in it. Anything else set here overrides config.php
 * on this server only.
 */

// A long random string - signs the sign-up form so bots can't post it
$signup['form_secret'] = 'replace-with-a-long-random-string';

// The mail server's password
$mail['password'] = '';

// Hosted sign-ups straight into our billing ITFlow (its Hosting add-on) - both
// from Hosting > Settings there. Left empty, hosted sign-ups are emailed instead.
$signup['itflow_url']    = '';   // e.g. 'https://billing.pittpc.com'
$signup['itflow_secret'] = '';

// For example, to hide hosting on a staging copy:
// $sections['hosting'] = false;
