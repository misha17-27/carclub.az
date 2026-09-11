<?php
/** @var array $car */
$title = car_title($car);
$specs = car_specs($car);
$href  = car_url($car);
$tags  = trim(($car['body'] ?? '') . ' ' . strtolower($car['brand'] ?? '') . ' ' . ($car['fuel'] ?? ''));

/* The catalogue sets $ACTIVE_FILTER so a ?f=… link renders already filtered,
   without a flash of the full grid before the script runs. */
$hidden = isset($ACTIVE_FILTER) && $ACTIVE_FILTER !== 'all' && ($car['body'] ?? '') !== $ACTIVE_FILTER;
?>
<article class="car reveal" data-car data-tags="<?= e($tags) ?>" <?= $hidden ? 'hidden' : '' ?>>
    <a class="car__media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
        <?= picture(ltrim(car_cover($car), 'img/'), $title, '(max-width:520px) 100vw, (max-width:1024px) 50vw, 380px',
            ['loading' => 'lazy', 'decoding' => 'async', 'width' => 800, 'height' => 500]) ?>
        <?php if (!empty($car['body'])): ?>
            <span class="car__badge"><?= e(t('val.' . $car['body'])) ?></span>
        <?php endif; ?>
    </a>
    <div class="car__body">
        <div>
            <?php if (!empty($car['brand'])): ?><p class="car__brand"><?= e($car['brand']) ?></p><?php endif; ?>
            <h3 class="car__title"><a href="<?= e($href) ?>"><?= e($title) ?></a></h3>
        </div>
        <dl class="car__specs">
            <?php foreach (array_slice($specs, 0, 4, true) as $row): ?>
                <div class="car__spec"><?= icon($row['icon']) ?><span><?= e($row['value']) ?></span></div>
            <?php endforeach; ?>
        </dl>
        <div class="car__foot">
            <a class="btn" href="<?= e($href) ?>"><?= e(t('btn.read_more')) ?></a>
            <?php if (cfg('contacts.whatsapp', '')): ?>
                <a class="btn btn--wa btn--icon" href="<?= e(wa_href('', $title)) ?>" target="_blank" rel="noopener"
                    aria-label="<?= e(t('btn.whatsapp') . ' — ' . $title) ?>"><?= icon('whatsapp') ?></a>
            <?php endif; ?>
        </div>
    </div>
</article>
