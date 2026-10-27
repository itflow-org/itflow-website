<?php
/*
 * itflow.org - the homepage. Its content is in config.php; each part of the
 * page is a file in includes/home/, shown when its section is switched on.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$is_home = true;
require __DIR__ . '/includes/header.php';

require __DIR__ . '/includes/home/hero.php';

// Each part, in page order, and the switches that show it
$home_parts = [
    'features' => sectionOn('features'),
    'tour'     => sectionOn('tour'),
    'start'    => sectionOn('start'),
    'sponsors' => sectionOn('sponsors') || (sectionOn('sponsorship') && signupOn()),
    'about'    => sectionOn('about') || sectionOn('team'),
];
foreach ($home_parts as $part => $on) {
    if ($on) {
        require __DIR__ . "/includes/home/$part.php";
    }
}

require __DIR__ . '/includes/footer.php';
