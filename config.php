<?php
/*
 * itflow.org - site content and settings
 *
 * Everything you'd normally edit lives here, top to bottom in page order:
 * section switches, the site itself, menu, homepage sections, sponsors, team,
 * footer, hosting prices, the sign-up form, and mail.
 * Pages read these arrays; they hold no content of their own.
 *
 * Screenshots: 'shot' names a set of files in assets/img/shots/ -
 * NAME-light-1280.webp, NAME-light-2160.webp, NAME-dark-1280.webp and
 * NAME-dark-2160.webp. Visitors whose device is in dark mode see the dark ones.
 * A set with one look only (like the client portal) is NAME-1280.webp and
 * NAME-2160.webp.
 *
 * Passwords and secrets go in config.local.php (kept out of git) - see the
 * bottom of this file.
 */

// ---------------------------------------------------------------------------
// Section switches - true shows it, false hides it everywhere
// (homepage section, its menu link, and its part of the sign-up form)
// ---------------------------------------------------------------------------
$sections = [
    'features'        => true,   // The feature cards
    'tour'            => true,   // "ITFlow in action" - one ticket from the inbox to the bank
    'start'           => true,   // "Get started" - host it yourself, hosted, or the demo
    'hosting'         => true,   // Hosted ITFlow: its card, the line under the top buttons, and hosting on the sign-up form
    'sponsors'        => true,   // Sponsor logo wall on the homepage
    'sponsorship'     => true,   // Accept sponsorships: tiers on the homepage and on the sign-up form
    'about'           => true,   // Timeline
    'team'            => true,   // Core team
    'signup'          => true,   // The sign-up page (hosting.php) itself
    'footer_sponsors' => true,   // "Sponsored by ..." line in the footer
];

// ---------------------------------------------------------------------------
// The site
// ---------------------------------------------------------------------------
$site = [
    'name'        => 'ITFlow',
    'url'         => 'https://itflow.org',
    'title'       => 'ITFlow - IT documentation, ticketing and billing for MSPs',
    'description' => 'ITFlow is free, open-source client IT documentation, ticketing, billing and accounting for MSPs. Host it yourself, or let us host it.',
    'demo_url'    => 'https://demo.itflow.org',
    'install_url' => 'https://docs.itflow.org/installation',
    'docs_url'    => 'https://docs.itflow.org',
    'forum_url'   => 'https://forum.itflow.org',
    'github_url'  => 'https://github.com/itflow-org/itflow',
    'docker_url'  => 'https://hub.docker.com/r/itfloworg/itflow',
    'license'     => 'GPL-3.0',
    'og_image'    => 'assets/img/og.png',   // the picture shown when the site is shared
];

// ---------------------------------------------------------------------------
// Menu
// 'href' starting with # is a homepage section; it becomes /#section on other pages.
// 'section' hides the link when that section is switched off above (one, or a list that must all be on).
// 'external' => true opens in a new tab.
// ---------------------------------------------------------------------------
$nav = [
    ['label' => 'Features', 'href' => '#features', 'section' => 'features'],
    ['label' => 'Tour',     'href' => '#tour',     'section' => 'tour'],
    ['label' => 'Hosting',  'href' => '#start',    'section' => ['start', 'hosting']],
    ['label' => 'Sponsors', 'href' => '#sponsors', 'section' => 'sponsors'],
    ['label' => 'About',    'href' => '#about',    'section' => 'about'],
    ['label' => 'Docs',     'href' => 'https://docs.itflow.org',     'external' => true],
    ['label' => 'Forum',    'href' => 'https://forum.itflow.org',    'external' => true],
    ['label' => 'Services', 'href' => 'https://services.itflow.org', 'external' => true],
];

// ---------------------------------------------------------------------------
// Homepage - the top of the page
// ---------------------------------------------------------------------------
$hero = [
    'tag'     => 'Free and open source',
    'heading' => 'Client IT documentation, ticketing and billing',
    'text'    => 'Everything about your clients, their tickets and their invoices, in one place. Built for MSPs by people who run one. Host it yourself for free, or let us host it for you.',
    'buttons' => [
        ['label' => 'Try the demo', 'href' => 'https://demo.itflow.org', 'style' => 'primary'],
        ['label' => 'Get started',  'href' => '#start',                  'style' => 'secondary'],
    ],
    // The line under the buttons; {price}, {included} and {seat} come from $hosting. Hidden when hosting is off.
    'hosting_line' => 'Or let us run it: hosted ITFlow from {price} a month, {included} technicians included.',
    'shot'    => 'dashboard',
    'shot_alt' => 'The ITFlow dashboard: tickets past their SLA, overdue invoices, expiring domains and certificates, and the day\'s schedule',
    'facts'   => [
        ['icon' => 'fas fa-code-branch',        'text' => 'Free and open source'],
        ['icon' => 'fas fa-server',             'text' => 'Your server or ours'],
        ['icon' => 'fas fa-user-check',         'text' => 'No per-technician fees to self-host'],
        ['icon' => 'fas fa-screwdriver-wrench', 'text' => 'Built by people who run MSPs'],
    ],
];

// ---------------------------------------------------------------------------
// Homepage - the features, a card each, in this order
// ---------------------------------------------------------------------------
$features = [
    'heading' => 'Everything an MSP runs on',
    'text'    => 'Documentation, tickets, billing and the books share one database, so each part knows what the others know.',
    'items'   => [
        ['icon' => 'fas fa-book',                'title' => 'Client documentation', 'text' => 'Contacts, locations, assets, networks, licenses, domains and documents, organized by client and linked together.'],
        ['icon' => 'fas fa-life-ring',           'title' => 'Support tickets',      'text' => 'Email to ticket, priorities, SLAs with business hours, templates, recurring tickets and a board view.'],
        ['icon' => 'fas fa-file-invoice-dollar', 'title' => 'Invoicing',            'text' => 'Quotes, invoices and recurring invoices, with reminders, statements and online payment through Stripe.'],
        ['icon' => 'fas fa-key',                 'title' => 'Password management',  'text' => 'An encrypted vault for client passwords, with 2FA codes, rotation reminders and secure share links.'],
        ['icon' => 'fas fa-users',               'title' => 'Client portal',        'text' => 'Clients open and follow their tickets, see invoices and quotes, pay online and look up their devices.'],
        ['icon' => 'fas fa-scale-balanced',      'title' => 'Accounting',           'text' => 'Income, expenses, bank accounts and reconciliation, budgets, and profit and loss.'],
        ['icon' => 'fas fa-calendar-days',       'title' => 'Calendar',             'text' => 'Scheduled tickets, maintenance windows and renewals, with feeds for Google, Outlook and Nextcloud.'],
        ['icon' => 'fas fa-envelope-open-text',  'title' => 'Bulk email',           'text' => 'Maintenance notices, outage updates and announcements to your clients\' contacts.'],
        ['icon' => 'fas fa-bullseye',            'title' => 'Leads',                'text' => 'Track prospects as leads, and turn them into clients when they sign.'],
        ['icon' => 'fas fa-code',                'title' => 'REST API',             'text' => 'Connect your RMM, CRM and scripts, with API keys that can be limited to one client.'],
        ['icon' => 'fas fa-wand-magic-sparkles', 'title' => 'AI, if you want it',   'text' => 'Draft replies and sum up tickets with the provider you choose, or a model on your own hardware. Off until you turn it on.'],
        ['icon' => 'fas fa-toggle-on',           'title' => 'Modular',              'text' => 'Turn off what you don\'t use. Roles, two-factor sign-in and an audit log look after the rest.'],
    ],
];

// ---------------------------------------------------------------------------
// Homepage - ITFlow in action: one piece of work, start to finish. Shown in this order.
// ---------------------------------------------------------------------------
$tour = [
    'heading' => 'ITFlow in action',
    'text'    => 'Follow one ticket from the inbox to the bank. Every step happens in the same place, so nothing gets typed twice.',
    'steps'   => [
        [
            'title' => 'An email becomes a ticket',
            'text'  => 'Mail to your support address turns into tickets, matched to the client and the contact who sent it. Work them as a list or a board, with SLA timers, priorities and who has what.',
            'shot'  => 'tickets',
            'alt'   => 'The tickets list, filtered to open tickets, with status, priority, assignee, SLA and billing for each',
        ],
        [
            'title' => 'Everything about the client is one click away',
            'text'  => 'Contacts, sites, assets, networks, passwords, licenses and documents sit under the client, with what needs attention at the top.',
            'shot'  => 'client',
            'alt'   => 'A client overview: contacts, location, what needs attention, open tickets and quick notes',
        ],
        [
            'title' => 'Work it and log the time',
            'text'  => 'Reply to the client or leave internal notes, log time as you go, and keep the asset, contact and site beside the conversation.',
            'shot'  => 'ticket',
            'alt'   => 'A ticket with its conversation, internal notes, time logged, SLA, contact and site',
        ],
        [
            'title' => 'Bill it',
            'text'  => 'Time, expenses and hardware go onto invoices at the client\'s rate. Recurring invoices count users and devices for you each month.',
            'shot'  => 'invoice',
            'alt'   => 'An invoice for managed services with per-user and per-server lines, its payment and history',
        ],
        [
            'title' => 'The client pays online',
            'text'  => 'Clients see their tickets, invoices and devices in the portal, pay by card, and can save a card for autopay.',
            'shot'  => 'portal-invoice',
            'alt'   => 'An invoice in the client portal with a button to pay it by card',
        ],
        [
            'title' => 'See where the business stands',
            'text'  => 'Income, expenses and profit by month, what each client is worth, what is owed and what is still unbilled.',
            'shot'  => 'profit',
            'alt'   => 'The Profit and Loss report: income, expenses and net profit by month with a chart',
        ],
    ],
];

// ---------------------------------------------------------------------------
// Homepage - Get started: three cards. Hosted prices come from $hosting further down;
// in its text, {price}, {included} and {seat} are filled in from there.
// ---------------------------------------------------------------------------
$start = [
    'heading' => 'Get started',
    'text'    => 'Three ways in, and every one is the whole of ITFlow.',
    'self'    => [
        'title'  => 'Host it yourself',
        'price'  => 'Free',
        'text'   => 'Install it on any Linux server with PHP and MariaDB, or run the Docker image. Every feature, as many technicians as you like.',
        'items'  => ['All of ITFlow, no paid tier', 'Your data stays on your server', 'Help from the community forum'],
        'button' => 'Install ITFlow',
        'docker' => 'Docker image',   // a link to $site['docker_url']; '' for none
    ],
    'hosted'  => [
        'title'  => 'Hosted ITFlow',
        'note'   => '{included} technicians included, then {seat} a month for each one after that.',
        'text'   => 'Your own ITFlow, run by the people who build it. We keep it updated and backed up.',
        'items'  => ['Your own instance, not a shared one', 'Nightly backups', 'Updates applied for you, security fixes straight away', 'Your own domain, or a free itflow.app address', 'Unlimited clients and portal contacts', 'Help from the ITFlow team'],
        'button' => 'Get hosted ITFlow',
    ],
    'demo'    => [
        'title'  => 'Try the demo',
        'text'   => 'Click around a live ITFlow with sample clients. Nothing to install.',
        'button' => 'Open the demo',
        'phones' => [
            ['shot' => 'phone-portal-light', 'alt' => 'The client portal on a phone'],
            ['shot' => 'phone-dashboard',    'alt' => 'The ITFlow dashboard on a phone'],
        ],
        'phones_text' => 'Works on your phone too, for you and for your clients.',
    ],
];

// ---------------------------------------------------------------------------
// Sponsors
// Shown as a logo wall on the homepage, in the footer line, and on the sign-up page.
// 'logo' is optional: a path under assets/img/sponsors/. Until the file exists,
// the sponsor's name is shown as a text tile instead, so nothing ever breaks.
// 'featured' => true gives the sponsor a larger tile (e.g. Website tier sponsors).
// ---------------------------------------------------------------------------
$sponsors = [
    ['name' => 'PittPC',      'logo' => 'assets/img/sponsors/pittpc.png',      'url' => 'https://pittpc.com', 'featured' => true],
    ['name' => 'CompuMatter', 'logo' => 'assets/img/sponsors/compumatter.png', 'url' => ''],
    ['name' => 'F1 for HELP', 'logo' => 'assets/img/sponsors/f1forhelp.png',   'url' => ''],
];

$sponsors_section = [
    'heading'       => 'Kept free by its sponsors',
    'text'          => 'ITFlow has no paid edition. These companies help pay for its development.',
    'tiers_heading' => 'Sponsor ITFlow',
    'tiers_text'    => 'Back the software your MSP runs on, and put your name in front of the people who use it.',
    'button'        => 'Become a sponsor',
];

// ---------------------------------------------------------------------------
// About - the timeline, oldest first
// ---------------------------------------------------------------------------
$timeline = [
    'heading' => 'Built inside a working MSP',
    'text'    => 'ITFlow started as the tool one MSP needed and couldn\'t find. It is still built by people who use it every day.',
    'entries' => [
        ['date' => '2018',         'title' => 'An open-source MSP', 'text' => 'An MSP built on open-source software needs a system to run on, and starts writing ITFlow.'],
        ['date' => 'March 2019',   'title' => 'On GitHub',          'text' => 'The code is published for anyone to use, change and improve.'],
        ['date' => 'May 2021',     'title' => 'The team grows',     'text' => 'Marcus and Andrei join the development team.'],
        ['date' => 'April 2023',   'title' => '300 stars',          'text' => 'More than 300 stars on GitHub, and work begins toward a stable release.'],
        ['date' => 'January 2025', 'title' => 'Stable releases',    'text' => 'ITFlow moves to regular stable releases.'],
    ],
];

// ---------------------------------------------------------------------------
// Core team. 'photo' is optional - until the file exists, initials are shown.
// ---------------------------------------------------------------------------
$team = [
    'heading' => 'The people behind it',
    'text'    => 'IT professionals who build ITFlow alongside running their own MSP work.',
    'people'  => [
        [
            'name'  => 'Johnny',
            'role'  => 'President and lead developer',
            'photo' => 'assets/img/team/Johnny.jpg',
            'links' => [
                ['icon' => 'fab fa-github',    'href' => 'https://github.com/johnnyq',          'label' => 'GitHub'],
                ['icon' => 'fab fa-discourse', 'href' => 'https://forum.itflow.org/u/johnny',   'label' => 'Forum'],
                ['icon' => 'fas fa-globe',     'href' => 'https://pittpc.com',                  'label' => 'PittPC'],
            ],
        ],
        [
            'name'  => 'Marcus',
            'role'  => 'Project manager and senior developer',
            'photo' => 'assets/img/team/clippy.png',
            'links' => [
                ['icon' => 'fab fa-github',    'href' => 'https://github.com/wrongecho',        'label' => 'GitHub'],
                ['icon' => 'fab fa-discourse', 'href' => 'https://forum.itflow.org/u/wrongecho', 'label' => 'Forum'],
            ],
        ],
    ],
];

// ---------------------------------------------------------------------------
// Footer
// ---------------------------------------------------------------------------
$footer = [
    'text'   => 'Free, open-source documentation, ticketing and billing for MSPs. Licensed under the GPL-3.0.',
    'social' => [
        ['icon' => 'fab fa-github',  'href' => 'https://github.com/itflow-org/itflow#readme',     'label' => 'GitHub'],
        ['icon' => 'fab fa-youtube', 'href' => 'https://www.youtube.com/@wrongecho5029/featured', 'label' => 'YouTube'],
        ['icon' => 'fab fa-docker',  'href' => 'https://hub.docker.com/r/itfloworg/itflow',      'label' => 'Docker Hub'],
    ],
    'columns' => [
        'ITFlow' => [
            ['label' => 'Try the demo',  'href' => 'https://demo.itflow.org'],
            ['label' => 'Install',       'href' => 'https://docs.itflow.org/installation'],
            ['label' => 'Hosted ITFlow', 'href' => 'hosting.php?want=hosting', 'section' => 'hosting'],
            ['label' => 'Sponsor',       'href' => 'hosting.php?want=sponsor', 'section' => 'sponsorship'],
        ],
        'Help' => [
            ['label' => 'Documentation', 'href' => 'https://docs.itflow.org'],
            ['label' => 'Forum',         'href' => 'https://forum.itflow.org'],
            ['label' => 'Paid services', 'href' => 'https://services.itflow.org'],
        ],
        'Project' => [
            ['label' => 'GitHub',        'href' => 'https://github.com/itflow-org/itflow'],
            ['label' => 'Releases',      'href' => 'https://github.com/itflow-org/itflow/releases'],
            ['label' => 'Terms of Use',  'href' => 'terms.php'],
        ],
    ],
];

// ---------------------------------------------------------------------------
// Hosting prices and regions (used by the homepage and the sign-up form)
// ---------------------------------------------------------------------------
$hosting = [
    'base_price'     => 60,  // monthly, includes 'included_seats' technicians (agent users)
    'included_seats' => 2,
    'seat_price'     => 25,  // monthly, per technician above 'included_seats'
    'max_seats'      => 500,
    'regions'        => [
        'us-east02' => 'US East (us-east02)',
    ],
];

// ---------------------------------------------------------------------------
// Sponsorship tiers - monthly. 'custom' => true lets them name an amount >= price.
// 'includes' names the tier whose perks this one also gets ("Everything in X, plus").
// ---------------------------------------------------------------------------
$sponsor_tiers = [
    'forum' => [
        'name'  => 'Forum User Sponsor',
        'price' => 60,
        'icon'  => 'fab fa-discourse',
        'perks' => ['Your name listed as a sponsor on the ITFlow forum'],
    ],
    'github' => [
        'name'     => 'GitHub Company Sponsor',
        'price'    => 120,
        'icon'     => 'fab fa-github',
        'includes' => 'forum',
        'perks'    => ['Your company in the ITFlow GitHub README'],
    ],
    'website' => [
        'name'     => 'Website Company Sponsor',
        'price'    => 200,
        'icon'     => 'fas fa-globe',
        'includes' => 'github',
        'perks'    => ['Your logo and link on itflow.org', 'Your name in the itflow.org footer'],
    ],
    'custom' => [
        'name'     => 'Name Your Price',
        'price'    => 200,
        'icon'     => 'fas fa-heart',
        'includes' => 'website',
        'perks'    => ['You choose the monthly amount'],
        'custom'   => true,
    ],
];

// ---------------------------------------------------------------------------
// Sign-up form (hosting.php)
// 'required' lists the fields a customer must fill in. Company name, primary
// contact name and email are always required. 'phone' means phone OR mobile.
// Choices: address, city, state, postal, country, title, phone, timezone
// ---------------------------------------------------------------------------
$signup = [
    'notify_to'   => ['sales@itflow.org'],                  // who receives sign-ups (one or more)
    'form_secret' => 'CHANGE-ME-to-a-long-random-string',   // set the real one in config.local.php
    // Hosted sign-ups straight into the billing ITFlow's Hosting add-on - set both in config.local.php
    // (copy them from Hosting > Settings). Its prices and regions then replace $hosting's above.
    'itflow_url'    => '',
    'itflow_secret' => '',
    'required'    => ['address', 'city', 'state', 'postal', 'country', 'phone'],
];

// ---------------------------------------------------------------------------
// Mail - sent with PHPMailer (libs/PHPMailer)
// 'transport' => 'smtp' sends through the server below (recommended).
// 'transport' => 'mail' uses the host's local mail() instead; the SMTP settings are ignored.
// 'encryption': 'tls' (STARTTLS, usually port 587), 'ssl' (implicit TLS, usually 465) or '' (none, port 25)
// Put the real username/password in config.local.php, not here.
// ---------------------------------------------------------------------------
$mail = [
    'transport'  => 'smtp',
    'host'       => 'mail.itflow.org',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'no-reply@itflow.org',
    'password'   => '',
    'from_email' => 'no-reply@itflow.org',
    'from_name'  => 'ITFlow Sign-up',
    'timeout'    => 15,      // seconds to wait for the mail server
];

// ---------------------------------------------------------------------------
// Local overrides - config.local.php is not in git. Put passwords and secrets
// in it, and anything else you want different on this server, e.g.:
//
//   <?php
//   $signup['form_secret'] = 'a-long-random-string';
//   $mail['password']      = 'smtp-password';
//   $sections['hosting']   = false;
// ---------------------------------------------------------------------------
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
