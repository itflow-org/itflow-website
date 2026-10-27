<?php
/*
 * itflow.org - the top of every page: <head> and the menu.
 * Set $page_title and $page_description (optional) and $is_home (the homepage) first.
 */

defined('ITFLOW_SITE') || exit;

$page_title = $page_title ?? $site['title'];
$page_description = $page_description ?? $site['description'];
$canonical = rtrim($site['url'], '/') . (!empty($is_home) ? '/' : '/' . basename($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0f1a2a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <?php if (siteFileExists($site['og_image'] ?? '')) { ?>
        <meta property="og:image" content="<?= e(rtrim($site['url'], '/') . '/' . ltrim($site['og_image'], '/')) ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php } ?>
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body<?= !empty($is_home) ? ' class="home"' : '' ?>>
<a class="skip" href="#main">Skip to the content</a>

<header class="masthead" data-masthead>
    <div class="wrap masthead-row">
        <a class="logo-link" href="/" aria-label="<?= e($site['name']) ?> home"><?= logoHtml() ?></a>

        <button class="menu-button" type="button" aria-expanded="false" aria-controls="siteMenu" data-menu-button>
            <i class="fas fa-bars" aria-hidden="true"></i><span class="visually-hidden">Menu</span>
        </button>

        <nav class="menu" id="siteMenu" aria-label="Main">
            <ul>
                <?php foreach ($nav as $item) {
                    if (!sectionsOn($item['section'] ?? [])) {
                        continue;
                    } ?>
                    <li><a href="<?= e(siteHref($item['href'])) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?><?php if (!empty($item['external'])) { ?><i class="fas fa-arrow-up-right-from-square menu-out" aria-hidden="true"></i><?php } ?></a></li>
                <?php } ?>
            </ul>
            <div class="menu-end">
                <a class="menu-github" href="<?= e($site['github_url']) ?>" target="_blank" rel="noopener" title="ITFlow on GitHub"><i class="fab fa-github" aria-hidden="true"></i><span>GitHub</span></a>
                <a class="button button-primary button-small" href="<?= e($site['demo_url']) ?>" target="_blank" rel="noopener">Try the demo</a>
            </div>
        </nav>
    </div>
</header>

<main id="main">
