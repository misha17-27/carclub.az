<?php
/** Call-to-action band, shared by several pages. */
$ph1 = cfg('contacts.phone1', '');
$title = c('home.cta_title');
if ($title === '') {
    return;
}
?>
<section class="section">
    <div class="wrap">
        <div class="cta reveal">
            <div class="cta__text">
                <h2 class="h2"><?= e($title) ?></h2>
                <p><?= e(c('home.cta_text')) ?></p>
            </div>
            <div class="cta__actions">
                <?php if ($ph1): ?>
                    <a class="btn btn--lg" href="<?= e(tel_href($ph1)) ?>"><?= icon('phone') ?><?= e(t('btn.call_now')) ?></a>
                <?php endif; ?>
                <?php if (cfg('contacts.whatsapp', '')): ?>
                    <a class="btn btn--wa btn--lg" href="<?= e(wa_href()) ?>" target="_blank" rel="noopener">
                        <?= icon('whatsapp') ?><?= e(t('btn.whatsapp')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
