<?php
/* The top of the homepage - $hero in config.php: what ITFlow is, the buttons, and the dashboard on a band of ITFlow blue. */
defined('ITFLOW_SITE') || exit;
?>
<section class="hero">
    <div class="wrap">
        <div class="hero-text">
            <?php if (!empty($hero['tag'])) { ?>
                <p class="hero-tag"><i class="fas fa-code-branch" aria-hidden="true"></i><?= e($hero['tag']) ?></p>
            <?php } ?>
            <h1><?= e($hero['heading']) ?></h1>
            <p class="hero-lede"><?= e($hero['text']) ?></p>
            <div class="hero-actions">
                <?php foreach ($hero['buttons'] as $button) {
                    if (strpos($button['href'], '#') === 0 && !sectionOn(substr($button['href'], 1))) {
                        continue;
                    } ?>
                    <a class="button button-large button-<?= e($button['style'] ?? 'primary') ?>" href="<?= e(siteHref($button['href'])) ?>"<?= linkAttrs($button['href']) ?>><?= e($button['label']) ?></a>
                <?php } ?>
            </div>
            <?php if (!empty($hero['hosting_line']) && hostingOn()) { ?>
                <p class="hero-hosting"><a href="<?= sectionOn('start') ? '#start' : 'hosting.php?want=hosting' ?>"><i class="fas fa-cloud" aria-hidden="true"></i><?= e(hostingText($hero['hosting_line'])) ?></a></p>
            <?php } ?>
        </div>
    </div>

    <div class="hero-stage">
        <div class="wrap">
            <figure class="screen screen-hero">
                <?= shot($hero['shot'], $hero['shot_alt'], ['eager' => true]) ?>
            </figure>
        </div>
    </div>
    <div class="hero-band">
        <div class="wrap">
            <ul class="facts">
                <?php foreach ($hero['facts'] as $fact) { ?>
                    <li><i class="<?= e($fact['icon']) ?>" aria-hidden="true"></i><?= e($fact['text']) ?></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</section>
