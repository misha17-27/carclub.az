<?php
$brand = cfg('settings.brand', 'Carclub');
$ph1 = cfg('contacts.phone1', '');
$ph2 = cfg('contacts.phone2', '');
$mail = cfg('contacts.email', '');
$nav = [
    'home'    => t('nav.home'),
    'cars'    => t('nav.cars'),
    'about'   => t('nav.about'),
    'contact' => t('nav.contact'),
];
?>
</main>

<footer class="footer">
    <div class="wrap">
        <div class="footer__grid">
            <div class="footer__about">
                <img src="<?= e(img('logo.png')) ?>" alt="<?= e($brand) ?>" style="height:52px;width:auto" loading="lazy">
                <p><?= e(c('footer_text')) ?></p>
                <?php include __DIR__ . '/socials.php'; ?>
            </div>

            <div>
                <h4><?= e(t('sec.useful_links')) ?></h4>
                <nav class="footer__links" aria-label="<?= e(t('sec.useful_links')) ?>">
                    <?php foreach ($nav as $key => $label): ?>
                        <a href="<?= e(url($key)) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div>
                <h4><?= e(t('sec.contact_us')) ?></h4>
                <div class="footer__contacts">
                    <?php if ($ph1): ?><a href="<?= e(tel_href($ph1)) ?>"><?= icon('phone') ?><?= ltr($ph1) ?></a><?php endif; ?>
                    <?php if ($ph2): ?><a href="<?= e(tel_href($ph2)) ?>"><?= icon('phone') ?><?= ltr($ph2) ?></a><?php endif; ?>
                    <?php if ($mail): ?><a href="mailto:<?= e($mail) ?>"><?= icon('mail') ?><?= ltr($mail) ?></a><?php endif; ?>
                    <span><?= icon('pin') ?><?= e(t('misc.location')) ?></span>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <span>© <span data-year><?= date('Y') ?></span> <?= e($brand) ?>.az — <?= e(t('misc.rights')) ?></span>
            <span><?= e(t('misc.site_by')) ?></span>
        </div>
    </div>
</footer>

<?php if ($wa = cfg('contacts.whatsapp', '')): ?>
    <a class="fab" href="<?= e(wa_href()) ?>" target="_blank" rel="noopener" aria-label="<?= e(t('misc.write_wa')) ?>"><?= icon('whatsapp') ?></a>
<?php endif; ?>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>

</html>
