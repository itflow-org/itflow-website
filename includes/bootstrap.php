<?php
/*
 * itflow.org - loaded first by every page: the config, and the small helpers
 * the pages share. Nothing here prints anything.
 */

define('ITFLOW_SITE', true);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/itflow_link.php';

// Prices, regions and the app domain from our billing ITFlow when the sign-up form is linked to it
$hosting = itflowHostingInfo($hosting);

/* Text made safe for HTML. */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* Dollars, without cents when it is a whole amount - $60, $25.50. */
function money($amount) {
    $amount = (float) $amount;
    return '$' . (floor($amount) == $amount ? number_format($amount) : number_format($amount, 2));
}

/* Is a section switched on? Unknown sections are on. */
function sectionOn($key) {
    global $sections;
    return !isset($sections[$key]) || $sections[$key];
}

/* Are all of these sections on? One name or a list - for a link that needs more than one. */
function sectionsOn($keys) {
    foreach ((array) $keys as $key) {
        if (!sectionOn($key)) {
            return false;
        }
    }
    return true;
}

/* Is the sign-up page open - switched on, with hosting or sponsorship on offer? */
function signupOn() {
    return sectionOn('signup') && (sectionOn('hosting') || sectionOn('sponsorship'));
}

/* Where a link goes from this page: a #section is the homepage's, from any other page. */
function siteHref($href) {
    global $is_home;
    if (strpos($href, '#') === 0 && empty($is_home)) {
        return '/' . $href;
    }
    return $href;
}

/* Is it a link off the site? */
function isExternal($href) {
    return (bool) preg_match('#^https?://#i', $href);
}

/* target and rel for a link that leaves the site. */
function linkAttrs($href) {
    return isExternal($href) ? ' target="_blank" rel="noopener"' : '';
}

/* A file under the site with its change time, so browsers fetch it again once it changes. */
function asset($path) {
    $file = __DIR__ . '/../' . ltrim($path, '/');
    return '/' . ltrim($path, '/') . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/* The sponsors' names. */
function sponsorNames(array $sponsors) {
    return array_values(array_filter(array_map(function ($s) {
        return trim((string) ($s['name'] ?? ''));
    }, $sponsors), 'strlen'));
}

/* "A", "A and B", "A, B and C". */
function listNames(array $names) {
    if (count($names) <= 1) {
        return (string) ($names[0] ?? '');
    }
    $last = array_pop($names);
    return implode(', ', $names) . ' and ' . $last;
}

/* The hosted price line, with its numbers filled in from $hosting. */
function hostingText($text) {
    global $hosting;
    return strtr($text, [
        '{price}'    => money($hosting['base_price']),
        '{included}' => (string) $hosting['included_seats'],
        '{seat}'     => money($hosting['seat_price']),
    ]);
}

/*
 * A screenshot: ITFlow's light look, and its dark look for visitors whose
 * device is in dark mode, when there is one. Two widths each, so phones fetch
 * the small one. $name is a set in assets/img/shots/ (see config.php).
 * Options: 'sizes', 'class', 'eager' (load it straight away - for the first
 * screen). Nothing when the files aren't there.
 */
function shot($name, $alt, array $opts = []) {
    $dir = __DIR__ . '/../assets/img/shots/';
    $url = 'assets/img/shots/';
    // NAME-light / NAME-dark, or just NAME for a set with one look; each in two widths, or one file
    $find = function ($base) use ($dir, $url) {
        if (is_file("$dir$base-1280.webp")) {
            $srcset = [];
            foreach ([1280, 2160] as $w) {
                if (is_file("$dir$base-$w.webp")) {
                    $actual = (int) (@getimagesize("$dir$base-$w.webp")[0] ?? 0) ?: $w;
                    $srcset[] = "/$url$base-$w.webp {$actual}w";
                }
            }
            return ['src' => "/$url$base-1280.webp", 'srcset' => implode(', ', $srcset), 'file' => "$dir$base-1280.webp"];
        }
        if (is_file("$dir$base.webp")) {
            return ['src' => "/$url$base.webp", 'srcset' => '', 'file' => "$dir$base.webp"];
        }
        return null;
    };
    $light = $find("$name-light") ?: $find($name);
    $dark = $find("$name-dark");
    if (!$light) {
        return '';
    }
    $sizes = $opts['sizes'] ?? '(min-width: 1240px) 1240px, 100vw';
    $html = '<picture class="shot' . (!empty($opts['class']) ? ' ' . e($opts['class']) : '') . '">';
    if ($dark) {
        $html .= '<source media="(prefers-color-scheme: dark)" srcset="' . e($dark['srcset'] !== '' ? $dark['srcset'] : $dark['src']) . '"'
            . ($dark['srcset'] !== '' ? ' sizes="' . e($sizes) . '"' : '') . '>';
    }
    $size = @getimagesize($light['file']);
    $html .= '<img src="' . e($light['src']) . '"'
        . ($light['srcset'] !== '' ? ' srcset="' . e($light['srcset']) . '" sizes="' . e($sizes) . '"' : '')
        . ' alt="' . e($alt) . '"'
        . (!empty($size[0]) ? ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"' : '')
        . (!empty($opts['eager']) ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
    return $html . '</picture>';
}

/* Is hosted ITFlow on offer - its section switched on and the sign-up page open? */
function hostingOn() {
    return sectionOn('hosting') && signupOn();
}

/* The ITFlow logo, as on ITFlow's own sign-in page: the paper plane and a bold "IT" in blue, then "Flow". */
function logoHtml() {
    return '<span class="logo"><i class="fas fa-paper-plane logo-plane" aria-hidden="true"></i><span class="logo-it">IT</span><span class="logo-flow">Flow</span></span>';
}

/* A person's or sponsor's initials, for when there is no picture yet. */
function initials($name) {
    $words = preg_split('/\s+/', trim((string) $name));
    $letters = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $letters .= mb_strtoupper(mb_substr($word, 0, 1));
    }
    return $letters;
}

/* Does a file under the site exist? For optional logos and photos. */
function siteFileExists($path) {
    return $path !== '' && is_file(__DIR__ . '/../' . ltrim((string) $path, '/'));
}
