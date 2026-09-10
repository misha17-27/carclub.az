<?php
$SEO_TITLE = c('cars.seo_title');
$SEO_DESC  = c('cars.seo_desc');
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

require __DIR__ . '/../includes/header.php';
?>

<section class="phero">
    <div class="wrap phero__in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('home')) ?>"><?= e(t('nav.home')) ?></a>
            <?= icon('chevron-right') ?><span><?= e(c('cars.title')) ?></span>
        </nav>
        <h1 class="h1"><?= e(c('cars.title')) ?></h1>
        <?php if ($lead = c('cars.lead')): ?>
            <p class="lead" style="max-width:64ch;margin-top:14px"><?= e($lead) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="filters" role="group" aria-label="<?= e(t('nav.cars')) ?>">
            <button class="chip is-active" type="button" data-filter="all" aria-pressed="true">
                <?= e(t('filter.all')) ?> <span class="chip__n"><?= (int) $counts['all'] ?></span>
            </button>
            <?php foreach ($bodies as $b): ?>
                <button class="chip" type="button" data-filter="<?= e($b) ?>" aria-pressed="false">
                    <?= e(t('val.' . $b)) ?> <span class="chip__n"><?= (int) ($counts[$b] ?? 0) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if ($cars): ?>
            <div class="cars-grid">
                <?php foreach ($cars as $car) {
                    include __DIR__ . '/../includes/car-card.php';
                } ?>
            </div>
            <p class="empty" id="cars-empty" hidden><?= e(t('filter.empty')) ?></p>
        <?php else: ?>
            <p class="empty"><?= e(t('filter.empty')) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/cta.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
