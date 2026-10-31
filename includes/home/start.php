<?php
/*
 * Get started - $start in config.php: host it yourself, hosted ITFlow (its
 * price from $hosting; the card hides when hosting is off) and the demo.
 */
defined('ITFLOW_SITE') || exit;
$self = $start['self'];
$hosted = $start['hosted'];
$demo = $start['demo'];
$show_hosted = hostingOn();
?>
<section class="section start" id="start" aria-labelledby="startHeading">
    <div class="wrap">
        <header class="section-head">
            <h2 id="startHeading"><?= e($start['heading']) ?></h2>
            <p><?= e($start['text']) ?></p>
        </header>

        <div class="paths<?= $show_hosted ? '' : ' paths-two' ?>">
            <!-- Host it yourself -->
            <div class="path">
                <span class="path-icon"><i class="fas fa-server" aria-hidden="true"></i></span>
                <h3><?= e($self['title']) ?></h3>
                <p class="path-price"><?= e($self['price']) ?></p>
                <p><?= e($self['text']) ?></p>
                <ul class="checklist">
                    <?php foreach ($self['items'] as $item) { ?>
                        <li><?= e($item) ?></li>
                    <?php } ?>
                </ul>
                <div class="path-actions">
                    <a class="button button-secondary" href="<?= e($site['install_url']) ?>" target="_blank" rel="noopener"><?= e($self['button']) ?></a>
                    <?php if (!empty($self['docker'])) { ?>
                        <a class="text-link" href="<?= e($site['docker_url']) ?>" target="_blank" rel="noopener"><i class="fab fa-docker" aria-hidden="true"></i><?= e($self['docker']) ?></a>
                    <?php } ?>
                </div>
            </div>

            <?php if ($show_hosted) { ?>
            <!-- Hosted ITFlow -->
            <div class="path path-hosted">
                <span class="path-icon"><i class="fas fa-paper-plane" aria-hidden="true"></i></span>
                <h3><?= e($hosted['title']) ?></h3>
                <p class="path-price"><?= money($hosting['base_price']) ?><span>a month</span></p>
                <p class="path-price-note"><?= e(hostingText($hosted['note'])) ?></p>
                <p><?= e(hostingText($hosted['text'])) ?></p>
                <ul class="checklist">
                    <?php foreach ($hosted['items'] as $item) { ?>
                        <li><?= e(hostingText($item)) ?></li>
                    <?php } ?>
                </ul>
                <div class="path-actions">
                    <a class="button button-primary" href="signup.php?want=hosting"><?= e($hosted['button']) ?></a>
                </div>
            </div>
            <?php } ?>

            <!-- The demo -->
            <div class="path">
                <span class="path-icon"><i class="fas fa-desktop" aria-hidden="true"></i></span>
                <h3><?= e($demo['title']) ?></h3>
                <p><?= e($demo['text']) ?></p>
                <div class="path-actions">
                    <a class="button button-secondary" href="<?= e($site['demo_url']) ?>" target="_blank" rel="noopener"><?= e($demo['button']) ?></a>
                </div>
                <?php if (!empty($demo['phones'])) { ?>
                    <div class="phones">
                        <?php foreach ($demo['phones'] as $phone) { ?>
                            <figure class="phone"><?= shot($phone['shot'], $phone['alt'], ['sizes' => '160px']) ?></figure>
                        <?php } ?>
                    </div>
                    <p class="phones-text"><?= e($demo['phones_text']) ?></p>
                <?php } ?>
            </div>
        </div>
    </div>
</section>
