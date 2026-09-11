<?php
/** @var string $PAGE      current page key: home|cars|car|about|contact|404 */
/** @var string $SEO_TITLE */
/** @var string $SEO_DESC  */

$L        = lang();
$dir      = is_rtl() ? 'rtl' : 'ltr';
$brand    = cfg('settings.brand', 'Carclub');
$ogImage  = site_host() . img(ltrim(($OG_IMAGE ?? cfg('settings.og_image', 'img/site/rentacar.jpg')), 'img/'));
$nav = [
    'home'    => t('nav.home'),
    'cars'    => t('nav.cars'),
    'about'   => t('nav.about'),
    'contact' => t('nav.contact'),
];
$ph1 = cfg('contacts.phone1', '');
$ph2 = cfg('contacts.phone2', '');
$mail = cfg('contacts.email', '');
?>
<!DOCTYPE html>
<html lang="<?= e($L) ?>" dir="<?= $dir ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($SEO_TITLE ?? $brand) ?></title>
    <meta name="description" content="<?= e($SEO_DESC ?? '') ?>">
    <link rel="canonical" href="<?= e($CANONICAL ?? canonical()) ?>">
    <?php foreach (langs() as $code => $l): ?>
        <link rel="alternate" hreflang="<?= e($code) ?>" href="<?= e(site_host() . current_url($code)) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(site_host() . current_url(cfg('default_lang', 'en'))) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($brand) ?>">
    <meta property="og:title" content="<?= e($SEO_TITLE ?? $brand) ?>">
    <meta property="og:description" content="<?= e($SEO_DESC ?? '') ?>">
    <meta property="og:url" content="<?= e(canonical()) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="<?= e(img('favicon.ico')) ?>" sizes="any">
    <link rel="apple-touch-icon" href="<?= e(img('favicon.png')) ?>">
    <?php
    /* Montserrat is served from this domain, so there is no extra DNS lookup
       and TLS handshake before the first text can be painted. Preload only the
       subset this language actually needs. */
    $subset = $L === 'ru' ? 'cyrillic' : 'latin';   // ar still needs latin for the brand and numbers
    ?>
    <?php foreach ([400, 800] as $w): ?>
        <?php /* no ?v= here: fonts.css references the plain path and a
                 mismatched URL would make the browser fetch the file twice */ ?>
        <link rel="preload" as="font" type="font/woff2" crossorigin
            href="<?= e(base_url() . "/assets/fonts/montserrat-{$w}-{$subset}.woff2") ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">

    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'AutoRental',
            'name' => $brand,
            'url' => site_host() . url('home', $L),
            'image' => $ogImage,
            'logo' => site_host() . img('logo.png'),
            'telephone' => array_values(array_filter([$ph1, $ph2])),
            'email' => $mail,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Baku', 'addressCountry' => 'AZ'],
            'areaServed' => 'Baku, Azerbaijan',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php if ($ga = cfg('settings.ga_id', '')): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '<?= e($ga) ?>');
        </script>
    <?php endif; ?>
</head>

<body class="page-<?= e($PAGE ?? 'home') ?>">

    <a class="sr-only" href="#main"><?= e(t('misc.skip')) ?></a>

    <div class="topbar">
        <div class="wrap topbar__in">
            <div class="topbar__contacts">
                <?php if ($ph1): ?><a href="<?= e(tel_href($ph1)) ?>"><?= icon('phone') ?><?= ltr($ph1) ?></a><?php endif; ?>
                <?php if ($ph2): ?><a href="<?= e(tel_href($ph2)) ?>" class="topbar__hide-sm"><?= icon('phone') ?><?= ltr($ph2) ?></a><?php endif; ?>
                <?php if ($mail): ?><a href="mailto:<?= e($mail) ?>" class="topbar__hide-sm"><?= icon('mail') ?><?= ltr($mail) ?></a><?php endif; ?>
            </div>
            <?php include __DIR__ . '/socials.php'; ?>
        </div>
    </div>

    <header class="header">
        <div class="wrap header__in">
            <a class="logo" href="<?= e(url('home')) ?>" aria-label="<?= e($brand) ?>">
                <img src="<?= e(img('logo.png')) ?>" alt="<?= e($brand) ?>" width="434" height="160">
            </a>

            <nav class="nav" aria-label="<?= e(t('nav.menu')) ?>">
                <?php foreach ($nav as $key => $label): ?>
                    <a href="<?= e(url($key)) ?>" <?= ($PAGE ?? '') === $key ? 'class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="header__actions">
                <div class="lang">
                    <button class="lang__btn" type="button" aria-haspopup="true" aria-expanded="false">
                        <?= e(cfg('languages.' . $L . '.short', strtoupper($L))) ?><?= icon('chevron-down') ?>
                    </button>
                    <div class="lang__menu">
                        <?php foreach (langs() as $code => $l): ?>
                            <a href="<?= e(current_url($code)) ?>" <?= $code === $L ? 'class="is-active"' : '' ?> lang="<?= e($code) ?>"><?= e($l['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($ph1): ?>
                    <a class="btn" href="<?= e(tel_href($ph1)) ?>" aria-label="<?= e(t('btn.call_us') . ' ' . $ph1) ?>"><?= icon('phone') ?><?= e(t('btn.call_us')) ?></a>
                <?php endif; ?>
                <button class="burger" type="button" aria-label="<?= e(t('nav.menu')) ?>" aria-expanded="false" aria-controls="drawer">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <div class="drawer" id="drawer">
        <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="<?= e(t('nav.menu')) ?>">
            <div class="drawer__top">
                <img src="<?= e(img('logo.png')) ?>" alt="<?= e($brand) ?>" style="height:40px;width:auto">
                <button class="drawer__close" type="button" aria-label="<?= e(t('nav.close')) ?>"><?= icon('close') ?></button>
            </div>
            <nav class="drawer__nav" aria-label="<?= e(t('nav.menu')) ?>">
                <?php foreach ($nav as $key => $label): ?>
                    <a href="<?= e(url($key)) ?>" <?= ($PAGE ?? '') === $key ? 'class="is-active"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="drawer__langs">
                <?php foreach (langs() as $code => $l): ?>
                    <a href="<?= e(current_url($code)) ?>" <?= $code === $L ? 'class="is-active"' : '' ?> lang="<?= e($code) ?>"><?= e($l['short']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="drawer__contacts">
                <?php if ($ph1): ?><a href="<?= e(tel_href($ph1)) ?>"><?= icon('phone') ?><?= ltr($ph1) ?></a><?php endif; ?>
                <?php if ($ph2): ?><a href="<?= e(tel_href($ph2)) ?>"><?= icon('phone') ?><?= ltr($ph2) ?></a><?php endif; ?>
                <?php if ($mail): ?><a href="mailto:<?= e($mail) ?>"><?= icon('mail') ?><?= ltr($mail) ?></a><?php endif; ?>
            </div>
            <?php include __DIR__ . '/socials.php'; ?>
        </div>
    </div>

    <main id="main">
