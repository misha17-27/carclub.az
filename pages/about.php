<?php
$SEO_TITLE = c('about.seo_title');
$SEO_DESC  = c('about.seo_desc');

$features = [];
foreach ([1 => 'truck', 2 => 'shield', 3 => 'clock', 4 => 'car'] as $n => $ic) {
    $ttl = c("home.f{$n}_title");
    if ($ttl !== '') {
        $features[] = ['icon' => $ic, 'title' => $ttl, 'text' => c("home.f{$n}_text")];
    }
}
require __DIR__ . '/../includes/header.php';
?>

<section class="phero">
    <div class="wrap phero__in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('home')) ?>"><?= e(t('nav.home')) ?></a>
            <?= icon('chevron-right') ?><span><?= e(c('about.title')) ?></span>
        </nav>
        <h1 class="h1"><?= e(c('about.title')) ?></h1>
        <?php if ($lead = c('about.lead')): ?>
            <p class="lead" style="max-width:60ch;margin-top:14px"><?= e($lead) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php $aboutBg = cfg('settings.about_bg', ''); ?>
<section class="section<?= $aboutBg !== '' ? ' section--texture' : '' ?>"
    <?= $aboutBg !== '' ? 'style="--texture:url(\'' . e(img(ltrim($aboutBg, 'img/'))) . '\')"' : '' ?>>
    <div class="wrap about">
        <div class="about__media reveal">
            <img src="<?= e(img(ltrim(cfg('settings.about_image', 'img/site/rentacar.jpg'), 'img/'))) ?>"
                alt="<?= e(cfg('settings.brand', 'Carclub')) ?>" loading="lazy" width="900" height="675">
            <div class="about__badge">
                <b><?= e(cfg('settings.brand', 'Carclub')) ?></b>
                <span><?= e(c('home.eyebrow')) ?></span>
            </div>
        </div>
        <div class="about__text reveal">
            <?= c('about.text') ?>
            <p style="margin-top:24px">
                <a class="btn" href="<?= e(url('cars')) ?>"><?= e(t('btn.view_fleet')) ?></a>
            </p>
        </div>
    </div>
</section>

<?php if ($features): ?>
    <section class="section section--alt">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow"><?= e(t('sec.why_us')) ?></span>
                <h2 class="h2"><?= e(c('home.about_title')) ?></h2>
            </div>
            <div class="features">
                <?php foreach ($features as $f): ?>
                    <article class="feature reveal">
                        <div class="feature__ico"><?= icon($f['icon']) ?></div>
                        <h3><?= e($f['title']) ?></h3>
                        <p><?= e($f['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/cta.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
