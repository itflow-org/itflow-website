<?php
/*
 * Sponsors - the premier sponsors' tiles and the other sponsors' names
 * ($sponsors, $sponsors_section), and the tiers ($sponsor_tiers, when
 * sponsorship is on and the sign-up page is open).
 * A premier sponsor (sponsorIsPremier()) gets a tile with their logo, sized to
 * the same weight as the others (sponsorLogoSize()) - or their name until the
 * logo is here - and when they started. The last tile invites the next one: to
 * the sign-up form while it is open, else to its 'url'. Every other sponsor is
 * their name, linking to their website, then a line for the anonymous ones.
 * Linked to ITFlow, the paying sponsors with a place on the website are added
 * as premier sponsors by themselves (itflowSponsors()).
 */
defined('ITFLOW_SITE') || exit;
$show_wall = sectionOn('sponsors') && $sponsors;
$premier = array_values(array_filter($sponsors, 'sponsorIsPremier'));
$listed = array_values(array_filter($sponsors, function ($sponsor) { return !sponsorIsPremier($sponsor); }));
$anonymous = $sponsors_section['anonymous'] ?? null;
$anonymous_since = sponsorSince($anonymous['since'] ?? '');
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
            <?php if ($premier || $join_url !== '') { ?>
                <div class="sponsor-group">
                    <h3 class="sponsor-group-title"><?= e($sponsors_section['premier_title'] ?? 'Premier sponsors') ?></h3>
                    <ul class="sponsor-wall">
                        <?php foreach ($premier as $sponsor) {
                            // A logo file here, or - for a sponsor from ITFlow - the picture ITFlow sent
                            $logo = !empty($sponsor['logo_data']) ? $sponsor['logo_data'] : (siteFileExists($sponsor['logo'] ?? '') ? asset($sponsor['logo']) : '');
                            $tag = !empty($sponsor['url']) ? 'a' : 'span';
                            $since = sponsorSince($sponsor['since'] ?? '');
                            $size = $logo !== '' ? sponsorLogoSize(!empty($sponsor['logo_data']) ? $sponsor['logo_data'] : $sponsor['logo']) : null; ?>
                            <li class="sponsor<?= $logo !== '' ? ' sponsor-has-logo' : '' ?>">
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
                        <?php if ($join_url !== '') { ?>
                            <li class="sponsor sponsor-join">
                                <a class="sponsor-tile" href="<?= e(siteHref($join_url)) ?>"<?= linkAttrs($join_url) ?>>
                                    <span class="sponsor-join-title"><?= e($join['title'] ?? '') ?></span>
                                    <span class="sponsor-join-text"><?= e($join['text'] ?? '') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
            <?php if ($listed || !empty($anonymous['text'])) { ?>
                <div class="sponsor-group">
                    <h3 class="sponsor-group-title"><?= e($sponsors_section['list_title'] ?? 'Sponsors') ?></h3>
                    <?php if ($listed) { ?>
                        <ul class="sponsor-list">
                            <?php foreach ($listed as $sponsor) {
                                $since = sponsorSince($sponsor['since'] ?? ''); ?>
                                <li>
                                    <?php if (!empty($sponsor['url'])) { ?>
                                        <a class="sponsor-chip" href="<?= e($sponsor['url']) ?>" target="_blank" rel="noopener"<?= $since !== '' ? ' title="' . e($since) . '"' : '' ?>><?= e($sponsor['name']) ?></a>
                                    <?php } else { ?>
                                        <span class="sponsor-chip"<?= $since !== '' ? ' title="' . e($since) . '"' : '' ?>><?= e($sponsor['name']) ?></span>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                    <?php if (!empty($anonymous['text'])) { ?>
                        <p class="sponsor-anonymous"><i class="fas fa-heart" aria-hidden="true"></i> <?= e($anonymous['text']) ?><?= $anonymous_since !== '' ? ', ' . e(lcfirst($anonymous_since)) : '' ?>.</p>
                    <?php } ?>
                </div>
            <?php } ?>
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
