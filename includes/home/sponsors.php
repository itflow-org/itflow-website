<?php
/*
 * Sponsors - the logo wall ($sponsors, $sponsors_section) and the tiers
 * ($sponsor_tiers, when sponsorship is on and the sign-up page is open).
 * A sponsor without a logo file yet shows as their name.
 */
defined('ITFLOW_SITE') || exit;
$show_wall = sectionOn('sponsors') && $sponsors;
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
                    $has_logo = siteFileExists($sponsor['logo'] ?? '');
                    $tag = !empty($sponsor['url']) ? 'a' : 'span'; ?>
                    <li class="sponsor<?= !empty($sponsor['featured']) ? ' sponsor-featured' : '' ?>">
                        <<?= $tag ?> class="sponsor-tile"<?= $tag === 'a' ? ' href="' . e($sponsor['url']) . '" target="_blank" rel="noopener"' : '' ?>>
                            <?php if ($has_logo) { ?>
                                <img src="<?= asset($sponsor['logo']) ?>" alt="<?= e($sponsor['name']) ?>" loading="lazy">
                            <?php } else { ?>
                                <span class="sponsor-name"><?= e($sponsor['name']) ?></span>
                            <?php } ?>
                        </<?= $tag ?>>
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
                        <h4><i class="<?= e($tier['icon']) ?>" aria-hidden="true"></i><?= e($tier['name']) ?></h4>
                        <p class="tier-price"><?= money($tier['price']) ?><?= !empty($tier['custom']) ? '+' : '' ?><span>a month</span></p>
                        <ul class="checklist">
                            <?php if (!empty($tier['includes']) && isset($sponsor_tiers[$tier['includes']])) { ?>
                                <li class="tier-includes">Everything in <?= e($sponsor_tiers[$tier['includes']]['name']) ?></li>
                            <?php } ?>
                            <?php foreach ($tier['perks'] as $perk) { ?>
                                <li><?= e($perk) ?></li>
                            <?php } ?>
                        </ul>
                        <a class="button button-secondary button-small" href="hosting.php?want=sponsor&amp;tier=<?= e($key) ?>" aria-label="<?= e($sponsors_section['button'] . ': ' . $tier['name']) ?>"><?= e($sponsors_section['button']) ?></a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</section>
