<?php
/*
 * Sponsors ($sponsors, $sponsors_section) by how their tier shows them
 * (sponsorShow()): premier sponsors a large tile each with their logo, company
 * sponsors a smaller one, supporters their name linking to their website - then
 * a line for the anonymous ones. Logos are sized to the same weight
 * (sponsorLogoSize()); a tile shows the name until its logo is here. The last
 * premier tile invites the next one: to the sign-up form while it is open, else
 * to its 'url'. Then the tiers ($sponsor_tiers, when sponsorship is on and the
 * sign-up page is open). Linked to ITFlow, the paying sponsors are added by
 * themselves (itflowSponsors()).
 */
defined('ITFLOW_SITE') || exit;
$show_wall = sectionOn('sponsors') && $sponsors;
$by_show = ['large' => [], 'logo' => [], 'name' => []];
foreach ($sponsors as $sponsor) {
    $by_show[sponsorShow($sponsor)][] = $sponsor;
}
$premier = $by_show['large'];
$company = $by_show['logo'];
$listed = $by_show['name'];
$anonymous = $sponsors_section['anonymous'] ?? null;
$anonymous_since = sponsorSince($anonymous['since'] ?? '');
$join = $sponsors_section['join'] ?? null;
$join_url = !$join ? '' : ((sectionOn('sponsorship') && signupOn()) ? 'signup.php?want=sponsor' : (string) ($join['url'] ?? ''));
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
                            echo sponsorTileHtml($sponsor, 'large');
                        } ?>
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
            <?php if ($company) { ?>
                <div class="sponsor-group">
                    <h3 class="sponsor-group-title"><?= e($sponsors_section['company_title'] ?? 'Company sponsors') ?></h3>
                    <ul class="sponsor-wall sponsor-wall-company">
                        <?php foreach ($company as $sponsor) {
                            echo sponsorTileHtml($sponsor, 'logo');
                        } ?>
                    </ul>
                </div>
            <?php } ?>
            <?php if ($listed || !empty($anonymous['text'])) { ?>
                <div class="sponsor-group">
                    <h3 class="sponsor-group-title"><?= e($sponsors_section['list_title'] ?? 'Supporters') ?></h3>
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
                        <a class="button button-secondary button-small" href="signup.php?want=sponsor&amp;tier=<?= e($key) ?>" aria-label="<?= e($sponsors_section['button'] . ': ' . $tier['name']) ?>"><?= e($sponsors_section['button']) ?></a>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</section>
