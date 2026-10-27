<?php
/*
 * ITFlow in action - $tour in config.php. One piece of work from start to
 * finish, a screenshot each step. On a wide screen the steps are a list beside
 * one screenshot: pick a step to see it (assets/js/site.js). On a phone, or
 * without the script, each step has its own screenshot under it.
 */
defined('ITFLOW_SITE') || exit;
?>
<section class="section tour" id="tour" aria-labelledby="tourHeading">
    <div class="wrap">
        <header class="section-head">
            <h2 id="tourHeading"><?= e($tour['heading']) ?></h2>
            <p><?= e($tour['text']) ?></p>
        </header>

        <div class="tour-grid" data-tour>
            <ol class="tour-steps">
                <?php foreach ($tour['steps'] as $i => $step) { ?>
                    <li class="tour-step<?= $i === 0 ? ' is-active' : '' ?>" data-tour-step="<?= $i ?>">
                        <button class="tour-step-head" type="button" data-tour-go="<?= $i ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="tourText<?= $i ?>">
                            <span class="tour-num" aria-hidden="true"><?= $i + 1 ?></span>
                            <span class="tour-title"><?= e($step['title']) ?></span>
                        </button>
                        <div class="tour-body" id="tourText<?= $i ?>">
                            <p class="tour-text"><?= e($step['text']) ?></p>
                            <figure class="screen tour-inline">
                                <?= shot($step['shot'], $step['alt'], ['sizes' => '(min-width: 1000px) 640px, 100vw']) ?>
                            </figure>
                        </div>
                    </li>
                <?php } ?>
            </ol>
            <div class="tour-viewer">
                <div class="tour-viewer-frame screen" aria-hidden="true">
                    <?php foreach ($tour['steps'] as $i => $step) { ?>
                        <div class="tour-shot<?= $i === 0 ? ' is-active' : '' ?>" data-tour-shot="<?= $i ?>">
                            <?= shot($step['shot'], '', ['sizes' => '(min-width: 1240px) 800px, 64vw']) ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="tour-nav">
                    <button type="button" class="tour-arrow" data-tour-step-by="-1"><i class="fas fa-arrow-left" aria-hidden="true"></i><span class="visually-hidden">Previous step</span></button>
                    <span class="tour-count" data-tour-count aria-live="polite">1 of <?= count($tour['steps']) ?></span>
                    <button type="button" class="tour-arrow" data-tour-step-by="1"><i class="fas fa-arrow-right" aria-hidden="true"></i><span class="visually-hidden">Next step</span></button>
                </div>
            </div>
        </div>
    </div>
</section>
