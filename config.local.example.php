<?php
/*
 * itflow.org - this server's own settings
 *
 * Copy this file to config.local.php next to config.php (it is kept out of
 * git) and fill in the secrets at the top. config.php reads it last, so
 * anything set here replaces config.php's value on this server only.
 *
 * Only the secrets need filling in. Everything below them is commented out
 * and shows config.php's value - uncomment a line to change it here. Any other
 * setting in config.php can be changed the same way.
 */

// ===========================================================================
// Secrets - fill these in
// ===========================================================================

// Signs the sign-up form so bots can't post it. A long random string - make one with:
//   openssl rand -hex 32
// Required: config.php's own value is public on GitHub, so it keeps nobody out.
$signup['form_secret'] = 'replace-with-a-long-random-string';

// The password for the mail server below (the SMTP username's password)
$mail['password'] = '';

// Sign-ups straight into our billing ITFlow's Hosting add-on - both from
// Hosting > Settings there. Left empty, every sign-up is emailed instead.
$signup['itflow_url']    = '';   // e.g. 'https://billing.pittpc.com'
$signup['itflow_secret'] = '';

// ===========================================================================
// Mail - how sign-ups are emailed (and the fallback when ITFlow can't be reached)
// ===========================================================================

// $mail['transport']  = 'smtp';                  // 'smtp', or 'mail' for the server's own mail() - the rest is then ignored
// $mail['host']       = 'mail.itflow.org';
// $mail['port']       = 587;
// $mail['encryption'] = 'tls';                   // 'tls' (STARTTLS, usually 587), 'ssl' (usually 465) or '' (none, port 25)
// $mail['username']   = 'no-reply@itflow.org';
// $mail['from_email'] = 'no-reply@itflow.org';
// $mail['from_name']  = 'ITFlow Sign-up';
// $mail['timeout']    = 15;                      // seconds to wait for the mail server

// ===========================================================================
// Sign-up form
// ===========================================================================

// Who receives sign-ups - one address or more
// $signup['notify_to'] = ['sales@itflow.org'];

// Fields a customer must fill in, besides company name and the primary
// contact's name and email. 'phone' means a phone or a mobile number.
// Choices: address, city, state, postal, country, title, phone
// $signup['required'] = ['address', 'city', 'state', 'postal', 'country', 'phone'];

// ===========================================================================
// Section switches - true shows it, false hides it everywhere
// ===========================================================================

// $sections['signup']      = false;   // the sign-up page (hosting.php) itself - on to take sign-ups
// $sections['hosting']     = false;   // hosted ITFlow: its card, the line under the top buttons, and on the sign-up form
// $sections['sponsorship'] = true;    // sponsorship tiers on the homepage and the sign-up form (shown while 'signup' is on)
// $sections['sponsors']    = true;    // the sponsors on the homepage
// $sections['features']    = true;
// $sections['tour']        = true;
// $sections['start']       = true;
// $sections['about']       = true;
// $sections['team']        = true;

// ===========================================================================
// This server's address
// ===========================================================================

// The address the site gives as its own - the canonical link and the picture
// shown when a page is shared. On a staging copy, its own address:
// $site['url'] = 'https://beta.itflow.org';

// ===========================================================================
// Hosting prices and regions
// Only used while the form isn't linked to ITFlow - linked, they come from
// ITFlow's Hosted ITFlow products and its servers.
// ===========================================================================

// $hosting['base_price']       = 60;                    // monthly, with 'included_seats' technicians
// $hosting['included_seats']   = 2;
// $hosting['seat_price']       = 25;                    // monthly, per technician above 'included_seats'
// $hosting['max_seats']        = 500;
// $hosting['regions']          = ['us-east02' => 'US East (us-east02)'];
// $hosting['app_domain']       = 'itflow.app';          // their address when they don't bring a domain: name.itflow.app
// $hosting['default_timezone'] = 'America/New_York';    // when their browser doesn't say
