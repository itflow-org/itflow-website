<?php
/*
 * itflow.org - loaded first by every page: the config, and the small helpers
 * the pages share. Nothing here prints anything.
 */

define('ITFLOW_SITE', true);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/itflow_link.php';

// Prices, regions, the app domain and the paying sponsors from our billing ITFlow when the sign-up form is linked to it
$hosting = itflowHostingInfo($hosting);
$sponsor_tiers = itflowSponsorTiers($sponsor_tiers);
$sponsors = itflowSponsors($sponsors);

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

/* When a sponsor started - 'YYYY-MM' as "Since Dec 2025", 'YYYY' as "Since 2024" - or '' when it isn't set. */
function sponsorSince($since) {
    $since = trim((string) $since);
    if (preg_match('/^(\d{4})-(\d{2})$/', $since, $m) && checkdate((int) $m[2], 1, (int) $m[1])) {
        return 'Since ' . date('M Y', mktime(0, 0, 0, (int) $m[2], 1, (int) $m[1]));
    }
    return preg_match('/^\d{4}$/', $since) ? "Since $since" : '';
}

/*
 * How a sponsor appears - 'large' (a large tile with their logo), 'logo' (a smaller
 * tile) or 'name' (their name in the list) - from their tier's 'show'. One with no
 * tier, or a tier not in $sponsor_tiers, is a name.
 */
function sponsorShow(array $sponsor) {
    global $sponsor_tiers;
    $show = $sponsor_tiers[$sponsor['tier'] ?? '']['show'] ?? 'name';
    return in_array($show, ['large', 'logo'], true) ? $show : 'name';
}

/*
 * The size to show a sponsor's logo at, [width, height] - every logo given about
 * the same area, so a wide wordmark and a square badge look the same weight, and
 * kept inside the tile. An SVG's comes from its width and height, or its viewBox.
 * null when the picture's size can't be read - the stylesheet's limits apply then.
 */
function sponsorLogoSize($src, $show = 'large') {
    $src = (string) $src;
    if (strncmp($src, 'data:', 5) === 0) {
        $bytes = (string) base64_decode(substr($src, strpos($src, ',') + 1));
    } else {
        $file = __DIR__ . '/../' . ltrim($src, '/');
        $bytes = is_file($file) ? (string) file_get_contents($file) : '';
    }
    $size = $bytes !== '' ? @getimagesizefromstring($bytes) : false;
    if (!$size && preg_match('/<svg\b[^>]*>/i', $bytes, $tag)) {
        if (preg_match('/\bwidth="([\d.]+)(px)?"/', $tag[0], $w) && preg_match('/\bheight="([\d.]+)(px)?"/', $tag[0], $h)) {
            $size = [(float) $w[1], (float) $h[1]];
        } elseif (preg_match('/\bviewBox="[\d.\-]+[ ,]+[\d.\-]+[ ,]+([\d.]+)[ ,]+([\d.]+)"/', $tag[0], $box)) {
            $size = [(float) $box[1], (float) $box[2]];
        }
    }
    if (!$size || $size[0] <= 0 || $size[1] <= 0) {
        return null;
    }
    $ratio = $size[0] / $size[1];
    [$area, $max_w, $max_h] = $show === 'large' ? [15000, 230, 76] : [8000, 160, 52];
    $w = sqrt($area * $ratio);
    $h = $w / $ratio;
    $fit = min(1, $max_w / $w, $max_h / $h);
    return [(int) round($w * $fit), (int) round($h * $fit)];
}

/*
 * A sponsor's tile on the homepage - their logo (a file here, or the picture ITFlow
 * sent), or their name until it's here, and when they started - linking to their
 * website. $show is 'large' or 'logo'.
 */
function sponsorTileHtml(array $sponsor, $show) {
    $logo = !empty($sponsor['logo_data']) ? $sponsor['logo_data'] : (siteFileExists($sponsor['logo'] ?? '') ? asset($sponsor['logo']) : '');
    $tag = !empty($sponsor['url']) ? 'a' : 'span';
    $since = sponsorSince($sponsor['since'] ?? '');
    $html = '<li class="sponsor' . ($logo !== '' ? ' sponsor-has-logo' : '') . '">'
        . "<$tag class=\"sponsor-tile\"" . ($tag === 'a' ? ' href="' . e($sponsor['url']) . '" target="_blank" rel="noopener" title="' . e($sponsor['name']) . '"' : '') . '>';
    if ($logo !== '') {
        $size = sponsorLogoSize(!empty($sponsor['logo_data']) ? $sponsor['logo_data'] : $sponsor['logo'], $show);
        $html .= '<img class="sponsor-logo" src="' . e($logo) . '" alt="' . e($sponsor['name']) . '"'
            . ($size ? ' width="' . $size[0] . '" height="' . $size[1] . '"' : '') . ' loading="lazy" decoding="async">';
    } else {
        $html .= '<span class="sponsor-name">' . e($sponsor['name']) . '</span>';
    }
    if ($since !== '') {
        $html .= '<span class="sponsor-since">' . e($since) . '</span>';
    }
    return $html . "</$tag></li>";
}

/* The first few sponsors' names, then how many more - "A, B, C and 9 more". */
function sponsorNamesShort(array $sponsors, $show = 3) {
    $names = sponsorNames($sponsors);
    if (count($names) <= $show + 1) {
        return listNames($names);
    }
    return implode(', ', array_slice($names, 0, $show)) . ' and ' . (count($names) - $show) . ' more';
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

/*
 * The top of a sponsorship tier's card - its icon in a circle, name and price - and its
 * perks, with "Everything in X, plus" first when it builds on another tier. Used by the
 * homepage tiers and the sign-up form, so the two look alike.
 */
function tierHead(array $tier, $name_tag = 'strong') {
    return '<span class="tier-icon" aria-hidden="true"><i class="' . e($tier['icon'] ?? 'fas fa-heart') . '"></i></span>'
        . "<$name_tag class=\"tier-name\">" . e($tier['name']) . "</$name_tag>"
        . '<span class="tier-amount">' . money($tier['price']) . (!empty($tier['custom']) ? '+' : '') . '<small>/mo</small></span>';
}
/* Does a tier have $flag ('whitelabel', say) - set on it, or on a tier it includes? */
function tierHas($key, $flag, array $tiers) {
    for ($seen = 0; isset($tiers[$key]) && $seen < 10; $seen++) {
        if (!empty($tiers[$key][$flag])) {
            return true;
        }
        $key = $tiers[$key]['includes'] ?? '';
    }
    return false;
}

/*
 * Forum usernames typed into one box - separated by commas, spaces or new lines,
 * an @ in front or not - as [usernames, error]. Up to 20, each as the forum allows.
 */
function forumUsernames($text) {
    $names = [];
    foreach (preg_split('/[\s,;]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY) as $name) {
        $name = ltrim($name, '@');
        if ($name === '') {
            continue;
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{1,60}$/', $name)) {
            return [[], 'A forum username has only letters, numbers, dots, dashes and underscores - "' . $name . '" doesn\'t.'];
        }
        if (!in_array(strtolower($name), array_map('strtolower', $names), true)) {
            $names[] = $name;
        }
    }
    if (count($names) > 20) {
        return [[], 'Up to 20 forum usernames, please - we can add more by email.'];
    }
    return [$names, ''];
}

function tierPerks(array $tier, array $tiers) {
    $html = '<ul class="tier-perks">';
    if (!empty($tier['includes']) && isset($tiers[$tier['includes']])) {
        $html .= '<li class="tier-includes"><i class="fas fa-layer-group" aria-hidden="true"></i><span>Everything in ' . e($tiers[$tier['includes']]['name']) . ', plus</span></li>';
    }
    foreach ((array) ($tier['perks'] ?? []) as $perk) {
        $html .= '<li><i class="fas fa-check" aria-hidden="true"></i><span>' . e($perk) . '</span></li>';
    }
    return $html . '</ul>';
}

/* Does a file under the site exist? For optional logos and photos. */
function siteFileExists($path) {
    return $path !== '' && is_file(__DIR__ . '/../' . ltrim((string) $path, '/'));
}
