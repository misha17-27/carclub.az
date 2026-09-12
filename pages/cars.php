<?php
$cars = cars_all();

/* Filters by body type only — Sedan and SUV, the way the old site had them. */
$counts = ['all' => count($cars)];
foreach ($cars as $car) {
    $tag = $car['body'] ?? '';
    if ($tag !== '') {
        $counts[$tag] = ($counts[$tag] ?? 0) + 1;
    }
}
$bodies = array_values(array_unique(array_filter(array_column($cars, 'body'))));
sort($bodies);

/* ?f=suv opens the page already on that category — heading and title included,
   so the filtered view is a real page for a visitor arriving from a link. */
$active = (string) ($_GET['f'] ?? 'all');
if (!in_array($active, $bodies, true)) {
    $active = 'all';
}
$pageTitle = c('cars.title');
$heading   = $active === 'all' ? $pageTitle : body_label($active);

$SEO_TITLE = $active === 'all'
    ? c('cars.seo_title')
    : $heading . ' — ' . $pageTitle . ' | ' . cfg('settings.brand', 'Carclub');
$SEO_DESC = c('cars.seo_desc');

/* filtered views are the same catalogue, so they point at the unfiltered page */
$CANONICAL = site_host() . url('cars');

require __DIR__ . '/../includes/header.php';
?>

<section class="phero">
    <div class="wrap phero__in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('home')) ?>"><?= e(t('nav.home')) ?></a>
            <?= icon('chevron-right') ?><span><?= e($pageTitle) ?></span>
        </nav>
        <h1 class="h1" id="cars-title" data-default="<?= e($pageTitle) ?>"
            data-title-suffix=" — <?= e($pageTitle) ?> | <?= e(cfg('settings.brand', 'Carclub')) ?>"
            data-title-default="<?= e(c('cars.seo_title')) ?>"><?= e($heading) ?></h1>
        <?php if ($lead = c('cars.lead')): ?>
            <p class="lead" style="max-width:64ch;margin-top:14px"><?= e($lead) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="filters" role="group" aria-label="<?= e(t('nav.cars')) ?>">
            <button class="chip<?= $active === 'all' ? ' is-active' : '' ?>" type="button" data-filter="all"
                data-label="<?= e($pageTitle) ?>" aria-pressed="<?= $active === 'all' ? 'true' : 'false' ?>">
                <?= e(t('filter.all')) ?> <span class="chip__n"><?= (int) $counts['all'] ?></span>
            </button>
            <?php foreach ($bodies as $b): ?>
                <button class="chip<?= $active === $b ? ' is-active' : '' ?>" type="button" data-filter="<?= e($b) ?>"
                    data-label="<?= e(body_label($b)) ?>" aria-pressed="<?= $active === $b ? 'true' : 'false' ?>">
                    <?= e(body_label($b)) ?> <span class="chip__n"><?= (int) ($counts[$b] ?? 0) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php
        $ACTIVE_FILTER = $active;                       // read by car-card.php
        $shown = $active === 'all' ? count($cars) : (int) ($counts[$active] ?? 0);
        ?>
        <?php if ($cars): ?>
            <div class="cars-grid">
                <?php foreach ($cars as $car) {
                    include __DIR__ . '/../includes/car-card.php';
                } ?>
            </div>
            <p class="empty" id="cars-empty" <?= $shown > 0 ? 'hidden' : '' ?>><?= e(t('filter.empty')) ?></p>
        <?php else: ?>
            <p class="empty"><?= e(t('filter.empty')) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/cta.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
