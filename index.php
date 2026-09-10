<?php
/**
 * Front controller.
 *
 * URL scheme (kept from the old WordPress/WPML install so old links keep working):
 *   /                     home, default language
 *   /az/ /ru/ /ar/        home, other languages
 *   /masinlar/            cars listing        (+ language prefix)
 *   /haqqimizda/          about               (+ language prefix)
 *   /elaqe/               contact             (+ language prefix)
 *   /<car-slug>/          single car          (+ language prefix)
 */

require __DIR__ . '/includes/functions.php';

/* Session must start before any output — the request forms need a CSRF token. */
boot_session();

/* ---------- parse the request ---------- */
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = base_url();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$segments = array_values(array_filter(explode('/', trim($uri, '/')), fn($s) => $s !== ''));

/* language prefix */
$L = cfg('default_lang', 'en');
foreach (langs() as $code => $l) {
    if ($l['prefix'] !== '' && isset($segments[0]) && $segments[0] === $l['prefix']) {
        $L = $code;
        array_shift($segments);
        break;
    }
}
lang($L);

/* page */
$slugs = cfg('slugs', []);
$rest  = $segments[0] ?? '';
$PAGE = 'home';
$PAGE_SLUG = '';

if ($rest === '') {
    $PAGE = 'home';
} elseif (($k = array_search($rest, $slugs, true)) !== false) {
    $PAGE = $k;
} elseif (car_find($rest)) {
    $PAGE = 'car';
    $PAGE_SLUG = $rest;
} else {
    $PAGE = '404';
}

$GLOBALS['PAGE'] = $PAGE;
$GLOBALS['PAGE_SLUG'] = $PAGE_SLUG;

/* ---------- form submissions ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $GLOBALS['FORM_RESULT'] = save_request($_POST);
}

/* ---------- render ---------- */
$file = __DIR__ . '/pages/' . ($PAGE === '404' ? '404' : $PAGE) . '.php';
if (!is_file($file)) {
    $file = __DIR__ . '/pages/404.php';
    $PAGE = '404';
}
if ($PAGE === '404') {
    http_response_code(404);
}
require $file;
