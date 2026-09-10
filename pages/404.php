<?php
$SEO_TITLE = t('misc.not_found') . ' | ' . cfg('settings.brand', 'Carclub');
$SEO_DESC  = t('misc.not_found_text');
require __DIR__ . '/../includes/header.php';
?>

<section class="section" style="min-height:52vh;display:grid;place-items:center;text-align:center">
    <div class="wrap" style="max-width:600px">
        <p class="eyebrow" style="justify-content:center">404</p>
        <h1 class="h2" style="margin-bottom:14px"><?= e(t('misc.not_found')) ?></h1>
        <p class="lead" style="margin-bottom:28px"><?= e(t('misc.not_found_text')) ?></p>
        <div style="display:flex;gap:11px;justify-content:center;flex-wrap:wrap">
            <a class="btn btn--lg" href="<?= e(url('home')) ?>"><?= e(t('misc.go_home')) ?></a>
            <a class="btn btn--ghost btn--lg" href="<?= e(url('cars')) ?>"><?= e(t('btn.all_cars')) ?></a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
