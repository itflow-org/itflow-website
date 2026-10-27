<?php
/* The features - $features in config.php, a card each. */
defined('ITFLOW_SITE') || exit;
?>
<section class="section features" id="features" aria-labelledby="featuresHeading">
    <div class="wrap">
        <header class="section-head">
            <h2 id="featuresHeading"><?= e($features['heading']) ?></h2>
            <p><?= e($features['text']) ?></p>
        </header>
        <ul class="feature-grid">
            <?php foreach ($features['items'] as $item) { ?>
                <li class="feature">
                    <span class="feature-icon"><i class="<?= e($item['icon']) ?>" aria-hidden="true"></i></span>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['text']) ?></p>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
