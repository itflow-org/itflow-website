<?php
/* About - the timeline ($timeline) and the team ($team). A person without a photo file yet shows their initials. */
defined('ITFLOW_SITE') || exit;
?>
<section class="section about" id="about" aria-labelledby="aboutHeading">
    <div class="wrap">
        <?php if (sectionOn('about')) { ?>
            <header class="section-head">
                <h2 id="aboutHeading"><?= e($timeline['heading']) ?></h2>
                <p><?= e($timeline['text']) ?></p>
            </header>
            <ol class="timeline">
                <?php foreach ($timeline['entries'] as $entry) { ?>
                    <li>
                        <span class="timeline-date"><?= e($entry['date']) ?></span>
                        <h3><?= e($entry['title']) ?></h3>
                        <p><?= e($entry['text']) ?></p>
                    </li>
                <?php } ?>
            </ol>
        <?php } ?>

        <?php if (sectionOn('team')) { ?>
            <div class="team" id="team">
                <div class="team-head">
                    <h3<?= sectionOn('about') ? '' : ' id="aboutHeading"' ?>><?= e($team['heading']) ?></h3>
                    <p><?= e($team['text']) ?></p>
                </div>
                <ul class="people">
                    <?php foreach ($team['people'] as $person) { ?>
                        <li class="person">
                            <?php if (siteFileExists($person['photo'] ?? '')) { ?>
                                <img class="person-photo" src="<?= asset($person['photo']) ?>" alt="" loading="lazy" width="72" height="72">
                            <?php } else { ?>
                                <span class="person-photo person-initials" aria-hidden="true"><?= e(initials($person['name'])) ?></span>
                            <?php } ?>
                            <div>
                                <h4><?= e($person['name']) ?></h4>
                                <p><?= e($person['role']) ?></p>
                                <ul class="person-links">
                                    <?php foreach ($person['links'] as $link) { ?>
                                        <li><a href="<?= e($link['href']) ?>" target="_blank" rel="noopener" aria-label="<?= e($person['name'] . ' - ' . $link['label']) ?>" title="<?= e($link['label']) ?>"><i class="<?= e($link['icon']) ?>" aria-hidden="true"></i></a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</section>
