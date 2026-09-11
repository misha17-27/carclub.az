<?php
$car = car_find($PAGE_SLUG);
if (!$car) {
    require __DIR__ . '/404.php';
    return;
}

$title   = car_title($car);
$gallery = car_gallery($car);
$specs   = car_specs($car);
$brand   = cfg('settings.brand', 'Carclub');

$SEO_TITLE = $title . ' — ' . t('nav.cars') . ' | ' . $brand;
$SEO_DESC  = trim(sprintf(
    '%s %s, %s, %s. %s',
    $title,
    $car['year'] ?? '',
    $car['engine'] ?? '',
    $car['gearbox'] ? t('val.' . $car['gearbox']) : '',
    c('cars.lead')
));
$OG_IMAGE = $gallery[0] ?? null;

/* other cars of the same body type, then anything else */
$similar = array_values(array_filter(cars_all(), fn($x) => $x['slug'] !== $car['slug'] && ($x['body'] ?? '') === ($car['body'] ?? '')));
if (count($similar) < 3) {
    foreach (cars_all() as $x) {
        if ($x['slug'] !== $car['slug'] && !in_array($x, $similar, true)) {
            $similar[] = $x;
        }
    }
}
$similar = array_slice($similar, 0, 3);

require __DIR__ . '/../includes/header.php';
?>

<section class="phero">
    <div class="wrap phero__in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('home')) ?>"><?= e(t('nav.home')) ?></a>
            <?= icon('chevron-right') ?>
            <a href="<?= e(url('cars')) ?>"><?= e(t('nav.cars')) ?></a>
            <?= icon('chevron-right') ?><span><?= e($title) ?></span>
        </nav>
        <h1 class="h1" style="font-size:clamp(28px,4.6vw,48px)"><?= e($title) ?></h1>
    </div>
</section>

<section class="section">
    <div class="wrap detail">
        <div>
            <?php if ($gallery): ?>
                <div class="gallery" id="gallery">
                    <?php foreach ($gallery as $i => $g): ?>
                        <button class="gallery__shot" type="button" data-i="<?= $i ?>"
                            aria-label="<?= e($title . ' — ' . ($i + 1) . ' / ' . count($gallery)) ?>">
                            <?= picture(ltrim($g, 'img/'), $title . ' — ' . ($i + 1),
                                '(max-width:1024px) 100vw, 700px',
                                ['width' => 1200, 'height' => 750, 'decoding' => 'async',
                                    'data-full' => img(ltrim($g, 'img/')),
                                ] + ($i === 0 ? ['fetchpriority' => 'high'] : ['loading' => 'lazy'])) ?>
                            <span class="gallery__badge"><?= $i + 1 ?> / <?= count($gallery) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="spec-panel">
            <?php if (!empty($car['brand'])): ?><p class="spec-panel__brand"><?= e($car['brand']) ?></p><?php endif; ?>
            <h2 class="spec-panel__title"><?= e($title) ?></h2>

            <dl class="spec-list">
                <?php foreach ($specs as $row): ?>
                    <div class="spec-list__row">
                        <dt><?= icon($row['icon']) ?><?= e($row['label']) ?></dt>
                        <dd><?= e($row['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>

            <div class="spec-panel__actions">
                <a class="btn btn--lg" href="#request-car"><?= e(t('btn.book')) ?></a>
                <?php if (cfg('contacts.whatsapp', '')): ?>
                    <a class="btn btn--wa btn--lg" href="<?= e(wa_href('', $title)) ?>" target="_blank" rel="noopener">
                        <?= icon('whatsapp') ?><?= e(t('btn.whatsapp')) ?>
                    </a>
                <?php endif; ?>
                <?php if ($ph = cfg('contacts.phone1', '')): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(tel_href($ph)) ?>"><?= icon('phone') ?><?= ltr($ph) ?></a>
                <?php endif; ?>
            </div>

            <?php if ($note = c('home.cta_title')): ?>
                <p class="spec-panel__note"><?= icon('info') ?><span><?= e($note) ?></span></p>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="section section--alt" id="request-car">
    <div class="wrap" style="max-width:620px">
        <?php
        $FORM_ID = 'car';
        $FORM_CAR = $title;
        require __DIR__ . '/../includes/form.php';
        ?>
    </div>
</section>

<?php if ($similar): ?>
    <section class="section">
        <div class="wrap">
            <div class="section-head section-head--row">
                <h2 class="h2"><?= e(t('sec.similar')) ?></h2>
                <a class="btn btn--ghost" href="<?= e(url('cars')) ?>"><?= e(t('btn.all_cars')) ?><?= icon('chevron-right') ?></a>
            </div>
            <div class="cars-grid">
                <?php foreach ($similar as $car) {
                    include __DIR__ . '/../includes/car-card.php';
                } ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="<?= e(t('sec.gallery')) ?>">
    <button class="lightbox__close" type="button" aria-label="<?= e(t('nav.close')) ?>"><?= icon('close') ?></button>
    <button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="&#8592;"><?= icon('chevron-left') ?></button>
    <img src="" alt="<?= e($title) ?>">
    <button class="lightbox__nav lightbox__nav--next" type="button" aria-label="&#8594;"><?= icon('chevron-right') ?></button>
    <span class="lightbox__count" aria-hidden="true">1 / <?= count($gallery) ?></span>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
