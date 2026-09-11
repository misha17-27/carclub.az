<?php
$SEO_TITLE = c('home.seo_title', cfg('settings.brand', 'Carclub'));
$SEO_DESC  = c('home.seo_desc');
$cars = array_slice(cars_all(), 0, (int) cfg('settings.home_cars', 6));

$features = [];
foreach ([1 => 'truck', 2 => 'shield', 3 => 'clock', 4 => 'car'] as $n => $ic) {
    $ttl = c("home.f{$n}_title");
    if ($ttl !== '') {
        $features[] = ['icon' => $ic, 'title' => $ttl, 'text' => c("home.f{$n}_text")];
    }
}
$stats = [];
for ($n = 1; $n <= 4; $n++) {
    $v = c("home.stat{$n}_v");
    if ($v !== '') {
        $stats[] = ['v' => $v, 'l' => c("home.stat{$n}_l")];
    }
}
require __DIR__ . '/../includes/header.php';
?>

<section class="hero" id="request">
    <div class="hero__bg">
        <?= picture(ltrim(cfg('settings.hero_image', 'img/site/rentacar.jpg'), 'img/'), '', '100vw',
            ['fetchpriority' => 'high', 'width' => 1600, 'height' => 900]) ?>
    </div>
    <div class="wrap hero__grid">
        <div>
            <?php if ($eb = c('home.eyebrow')): ?><span class="eyebrow"><?= e($eb) ?></span><?php endif; ?>
            <h1 class="h1 hero__title"><?= e(c('home.title')) ?><span><?= e(c('home.title_accent')) ?></span></h1>
            <p class="lead hero__text"><?= e(c('home.text')) ?></p>
            <div class="hero__cta">
                <a class="btn btn--lg" href="<?= e(url('cars')) ?>"><?= e(t('btn.view_fleet')) ?></a>
                <?php if (cfg('contacts.whatsapp', '')): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(wa_href()) ?>" target="_blank" rel="noopener">
                        <?= icon('whatsapp') ?><?= e(t('btn.whatsapp')) ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($stats): ?>
                <div class="hero__stats">
                    <?php foreach ($stats as $s): ?>
                        <div class="hero__stat"><b><?= e($s['v']) ?></b><span><?= e($s['l']) ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php $FORM_ID = 'hero';
        require __DIR__ . '/../includes/form.php'; ?>
    </div>
</section>

<?php if ($brands = cfg('brands', [])): ?>
    <section class="brands">
        <div class="wrap">
            <ul class="brands__list">
                <?php foreach ($brands as $b): ?>
                    <li><img src="<?= e(img(ltrim($b['file'], 'img/'))) ?>" alt="<?= e($b['name']) ?>" loading="lazy"></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<?php if ($features): ?>
    <section class="section section--tight">
        <div class="wrap">
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

<?php if ($cars): ?>
    <section class="section" id="fleet">
        <div class="wrap">
            <div class="section-head section-head--row">
                <div>
                    <span class="eyebrow"><?= e(t('sec.our_fleet')) ?></span>
                    <h2 class="h2"><?= e(t('sec.fleet_title')) ?></h2>
                </div>
                <a class="btn btn--ghost" href="<?= e(url('cars')) ?>"><?= e(t('btn.all_cars')) ?><?= icon('chevron-right') ?></a>
            </div>
            <div class="cars-grid">
                <?php foreach ($cars as $car) {
                    include __DIR__ . '/../includes/car-card.php';
                } ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php $aboutBg = cfg('settings.about_bg', ''); ?>
<section class="section <?= $aboutBg !== '' ? 'section--texture' : 'section--alt' ?>"
    <?= $aboutBg !== '' ? 'style="--texture:url(\'' . e(img(ltrim($aboutBg, 'img/'))) . '\')"' : '' ?>>
    <div class="wrap about">
        <div class="about__media reveal">
            <?= picture(ltrim(cfg('settings.about_image', 'img/site/rentacar.jpg'), 'img/'),
                cfg('settings.brand', 'Carclub'), '(max-width:1024px) 100vw, 560px',
                ['loading' => 'lazy', 'decoding' => 'async', 'width' => 900, 'height' => 675]) ?>
            <div class="about__badge">
                <b><?= e(cfg('settings.brand', 'Carclub')) ?></b>
                <span><?= e(c('home.eyebrow')) ?></span>
            </div>
        </div>
        <div class="about__text reveal">
            <span class="eyebrow"><?= e(t('nav.about')) ?></span>
            <h2 class="h2" style="margin-bottom:18px"><?= e(c('home.about_title')) ?></h2>
            <?= c('about.text') ?>
            <p style="margin-top:24px"><a class="btn" href="<?= e(url('about')) ?>"><?= e(t('btn.more_about')) ?></a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/cta.php'; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
