<?php
/*
 * itflow.org - the bottom of every page. A page's own script goes in $page_scripts.
 */

defined('ITFLOW_SITE') || exit;
?>
</main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div class="footer-about">
            <a class="logo-link" href="/" aria-label="<?= e($site['name']) ?> home"><?= logoHtml() ?></a>
            <p><?= e($footer['text']) ?></p>
            <ul class="footer-social">
                <?php foreach ($footer['social'] as $link) { ?>
                    <li><a href="<?= e($link['href']) ?>" target="_blank" rel="noopener" aria-label="<?= e($link['label']) ?>" title="<?= e($link['label']) ?>"><i class="<?= e($link['icon']) ?>" aria-hidden="true"></i></a></li>
                <?php } ?>
            </ul>
        </div>
        <?php foreach ($footer['columns'] as $heading => $links) {
            $links = array_filter($links, function ($link) {
                return sectionsOn($link['section'] ?? []) && (strpos($link['href'], 'hosting.php') !== 0 || signupOn());
            });
            if (!$links) {
                continue;
            } ?>
            <nav class="footer-column" aria-label="<?= e($heading) ?>">
                <h2><?= e($heading) ?></h2>
                <ul>
                    <?php foreach ($links as $link) { ?>
                        <li><a href="<?= e(siteHref($link['href'])) ?>"<?= linkAttrs($link['href']) ?>><?= e($link['label']) ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>
    </div>
    <div class="wrap">
        <div class="footer-base">&copy; <?= date('Y') ?> <?= e($site['name']) ?></div>
    </div>
</footer>

<script src="<?= asset('assets/js/site.js') ?>" defer></script>
<?= $page_scripts ?? '' ?>
</body>
</html>
