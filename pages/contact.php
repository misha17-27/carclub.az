<?php
$SEO_TITLE = c('contact.seo_title');
$SEO_DESC  = c('contact.seo_desc');

$ph1 = cfg('contacts.phone1', '');
$ph2 = cfg('contacts.phone2', '');
$mail = cfg('contacts.email', '');
$wa  = cfg('contacts.whatsapp', '');
$map = cfg('contacts.map_query', 'baku');

require __DIR__ . '/../includes/header.php';
?>

<section class="phero">
    <div class="wrap phero__in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('home')) ?>"><?= e(t('nav.home')) ?></a>
            <?= icon('chevron-right') ?><span><?= e(c('contact.title')) ?></span>
        </nav>
        <h1 class="h1"><?= e(c('contact.title')) ?></h1>
        <?php if ($lead = c('contact.lead')): ?>
            <p class="lead" style="max-width:60ch;margin-top:14px"><?= e($lead) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="wrap contact-grid">
        <div>
            <div class="contact-list">
                <?php if ($ph1): ?>
                    <a class="contact-item" href="<?= e(tel_href($ph1)) ?>">
                        <span class="contact-item__ico"><?= icon('phone') ?></span>
                        <span><small><?= e(t('misc.phone')) ?></small><b><?= ltr($ph1) ?></b></span>
                    </a>
                <?php endif; ?>
                <?php if ($ph2): ?>
                    <a class="contact-item" href="<?= e(tel_href($ph2)) ?>">
                        <span class="contact-item__ico"><?= icon('phone') ?></span>
                        <span><small><?= e(t('misc.phone')) ?></small><b><?= ltr($ph2) ?></b></span>
                    </a>
                <?php endif; ?>
                <?php if ($wa): ?>
                    <a class="contact-item" href="<?= e(wa_href()) ?>" target="_blank" rel="noopener">
                        <span class="contact-item__ico"><?= icon('whatsapp') ?></span>
                        <span><small>WhatsApp</small><b><?= ltr($ph1 ?: $wa) ?></b></span>
                    </a>
                <?php endif; ?>
                <?php if ($mail): ?>
                    <a class="contact-item" href="mailto:<?= e($mail) ?>">
                        <span class="contact-item__ico"><?= icon('mail') ?></span>
                        <span><small><?= e(t('misc.email')) ?></small><b><?= ltr($mail) ?></b></span>
                    </a>
                <?php endif; ?>
                <div class="contact-item">
                    <span class="contact-item__ico"><?= icon('pin') ?></span>
                    <span><small><?= e(t('misc.address')) ?></small><b><?= e(t('misc.location')) ?></b></span>
                </div>
            </div>

            <h2 class="h3" style="margin:32px 0 16px"><?= e(t('sec.our_location')) ?></h2>
            <div class="map">
                <iframe src="https://maps.google.com/maps?q=<?= e(rawurlencode($map)) ?>&amp;t=m&amp;z=13&amp;output=embed&amp;iwloc=near"
                    title="<?= e(t('sec.our_location')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <?php $FORM_ID = 'contact';
        require __DIR__ . '/../includes/form.php'; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/cta.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
