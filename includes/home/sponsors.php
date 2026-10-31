<?php
/*
 * Sponsors - the logo wall ($sponsors, $sponsors_section) and the tiers
 * ($sponsor_tiers, when sponsorship is on and the sign-up page is open).
 * A sponsor without a logo yet shows as their name, with how long they have
 * sponsored under it when config.php says. Linked to ITFlow, the paying
 * sponsors with a place on the website are added to the wall by themselves
 * (itflowSponsors()). Logos are sized to the same weight (sponsorLogoSize()).
 * The anonymous sponsors get one tile, then one invites the next sponsor - to
 * the sign-up form while it is open, else to its 'url'.
 */
defined('ITFLOW_SITE') || exit;
$show_wall = sectionOn('sponsors') && $sponsors;
$anonymous = $sponsors_section['anonymous'] ?? null;
$join = $sponsors_section['join'] ?? null;
$join_url = !$join ? '' : ((sectionOn('sponsorship') && signupOn()) ? 'hosting.php?want=sponsor' : (string) ($join['url'] ?? ''));
$show_tiers = sectionOn('sponsorship') && signupOn() && $sponsor_tiers;
?>
<section class="section sponsors" id="sponsors" aria-labelledby="sponsorsHeading">
    <div class="wrap">
        <?php if ($show_wall) { ?>
            <header class="section-head">
                <h2 id="sponsorsHeading"><?= e($sponsors_section['heading']) ?></h2>
                <p><?= e($sponsors_section['text']) ?></p>
            </header>
            <ul class="sponsor-wall">
                <?php foreach ($sponsors as $sponsor) {
                    // A logo file here, or - for a sponsor from ITFlow - the picture ITFlow sent
                    $logo = !empty($sponsor['logo_data']) ? $sponsor['logo_data'] : (siteFileExists($sponsor['logo'] ?? '') ? asset($sponsor['logo']) : '');
                    $tag = !empty($sponsor['url']) ? 'a' : 'span';
                    $since = sponsorSince($sponsor['since'] ?? '');
                    $size = $logo !== '' ? sponsorLogoSize(!empty($sponsor['logo_data']) ? $sponsor['logo_data'] : $sponsor['logo'], !empty($sponsor['featured'])) : null; ?>
                    <li class="sponsor<?= !empty($sponsor['featured']) ? ' sponsor-featured' : '' ?><?= $logo !== '' ? ' sponsor-has-logo' : '' ?>">
                        <<?= $tag ?> class="sponsor-tile"<?= $tag === 'a' ? ' href="' . e($sponsor['url']) . '" target="_blank" rel="noopener" title="' . e($sponsor['name']) . '"' : '' ?>>
                            <?php if ($logo !== '') { ?>
                                <img class="sponsor-logo" src="<?= e($logo) ?>" alt="<?= e($sponsor['name']) ?>"<?= $size ? ' width="' . $size[0] . '" height="' . $size[1] . '"' : '' ?> loading="lazy" decoding="async">
                            <?php } else { ?>
                                <span class="sponsor-name"><?= e($sponsor['name']) ?></span>
                            <?php } ?>
                            <?php if ($since !== '') { ?>
                                <span class="sponsor-since"><?= e($since) ?></span>
                            <?php } ?>
                        </<?= $tag ?>>
                    </li>
                <?php } ?>
                <?php if (!empty($anonymous['text'])) {
                    $since = sponsorSince($anonymous['since'] ?? ''); ?>
                    <li class="sponsor sponsor-anonymous">
                        <span class="sponsor-tile">
                            <span class="sponsor-name"><?= e($anonymous['text']) ?></span>
                            <?php if ($since !== '') { ?>
                                <span class="sponsor-since"><?= e($since) ?></span>
                            <?php } ?>
                        </span>
                    </li>
                <?php } ?>
                <?php if ($join_url !== '') { ?>
                    <li class="sponsor sponsor-join">
                        <a class="sponsor-tile" href="<?= e(siteHref($join_url)) ?>"<?= linkAttrs($join_url) ?>>
                            <span class="sponsor-join-title"><?= e($join['title'] ?? '') ?></span>
                            <span class="sponsor-join-text"><?= e($join['text'] ?? '') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if ($show_tiers) { ?>
            <div class="tiers-head"<?= $show_wall ? '' : ' id="sponsorsHeading"' ?>>
                <h3><?= e($sponsors_section['tiers_heading']) ?></h3>
                <p><?= e($sponsors_section['tiers_text']) ?></p>
            </div>
            <ul class="tiers">
                <?php foreach ($sponsor_tiers as $key => $tier) { ?>
                    <li class="tier">
                        <?= tierHead($tier, 'h4') ?>
                        <?= tierPerks($tier, $sponsor_tiers) ?>
                        <a class="button button-secondary button-small" href="hosting.php?want=sponsor&amp;tier=<?= e($key) ?>" aria-label="<?= e($sponsors_section['button'] . ': ' . $tier['name']) ?>"><?= e($sponsors_section['button']) ?></a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</section>
