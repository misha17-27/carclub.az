<?php
/**
 * Shared helpers for the public site and the admin panel.
 */

require_once __DIR__ . '/compat.php';

define('ROOT', dirname(__DIR__));
define('STORAGE', ROOT . '/storage');

/* ---------------------------------------------------------------------
 * Config: defaults from config/site.php, overlaid by storage/site.json
 * ------------------------------------------------------------------ */

function array_replace_deep(array $base, array $over): array
{
    foreach ($over as $k => $v) {
        if (is_array($v) && isset($base[$k]) && is_array($base[$k]) && !array_is_list($v)) {
            $base[$k] = array_replace_deep($base[$k], $v);
        } else {
            $base[$k] = $v;
        }
    }
    return $base;
}

function json_read(string $file, $fallback = [])
{
    if (!is_file($file)) {
        return $fallback;
    }
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

function json_write(string $file, $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $tmp = $file . '.tmp';
    $ok = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($ok === false) {
        return false;
    }
    return rename($tmp, $file);
}

function cfg(?string $path = null, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require ROOT . '/config/site.php';
        $overlay = json_read(STORAGE . '/site.json');
        if ($overlay) {
            $cfg = array_replace_deep($cfg, $overlay);
        }
    }
    if ($path === null) {
        return $cfg;
    }
    $node = $cfg;
    foreach (explode('.', $path) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return $default;
        }
        $node = $node[$part];
    }
    // an empty override must not mask a usable default
    if ($node === '' && $default !== null) {
        return $default;
    }
    return $node;
}

/* ---------------------------------------------------------------------
 * Language
 * ------------------------------------------------------------------ */

function langs(): array
{
    return cfg('languages', []);
}

function lang(?string $set = null): string
{
    static $cur = null;
    if ($set !== null && isset(langs()[$set])) {
        $cur = $set;
    }
    return $cur ?: cfg('default_lang', 'en');
}

function is_rtl(): bool
{
    return (cfg('languages.' . lang() . '.dir') ?? 'ltr') === 'rtl';
}

/** UI string lookup. */
function t(string $key, ?string $lang = null): string
{
    static $dict = null;
    if ($dict === null) {
        $dict = require __DIR__ . '/i18n.php';
    }
    $l = $lang ?: lang();
    if (!isset($dict[$key])) {
        return $key;
    }
    return $dict[$key][$l] ?? $dict[$key]['en'] ?? $key;
}

/** Editable content lookup: c('home.title'). */
function c(string $path, string $default = '', ?string $lang = null): string
{
    $l = $lang ?: lang();
    $v = cfg('content.' . $l . '.' . $path);
    if ($v === null || $v === '') {
        $v = cfg('content.' . cfg('default_lang', 'en') . '.' . $path);
    }
    return ($v === null || $v === '') ? $default : (string) $v;
}

/* ---------------------------------------------------------------------
 * URLs and assets
 * ------------------------------------------------------------------ */

function base_url(): string
{
    return rtrim(cfg('settings.base_url', ''), '/');
}

/** URL of a page: url('cars'), url('home', 'ru'), url('car', 'az', 'bmw-g30-2021'). */
function url(string $page = 'home', ?string $lang = null, string $slug = ''): string
{
    $l = $lang ?: lang();
    $prefix = cfg('languages.' . $l . '.prefix', '');
    $parts = [base_url()];
    if ($prefix !== '') {
        $parts[] = $prefix;
    }
    if ($page === 'car') {
        $parts[] = $slug;
    } elseif ($page !== 'home') {
        $parts[] = cfg('slugs.' . $page, $page);
    }
    $u = implode('/', array_filter($parts, fn($p) => $p !== ''));
    return ($u === '' ? '/' : '/' . ltrim($u, '/') . '/');
}

function car_url(array $car, ?string $lang = null): string
{
    return url('car', $lang, $car['slug']);
}

/** Versioned asset URL — busts CDN/browser caches when the file changes. */
function asset(string $path): string
{
    $rel = ltrim($path, '/');
    $file = ROOT . '/assets/' . $rel;
    $v = is_file($file) ? filemtime($file) : null;
    return base_url() . '/assets/' . $rel . ($v ? '?v=' . $v : '');
}

function img(string $path): string
{
    return asset('img/' . ltrim($path, '/'));
}

/**
 * <picture> using the WebP variants built by tools/optimize-images.py.
 *
 * Falls back to a plain <img> with the original file when the variants are
 * missing, so the site still renders before the script has ever been run.
 *
 *   picture('cars/bmw-1.jpeg', 'BMW G30', '(max-width:767px) 100vw, 380px',
 *           ['class' => 'car__img', 'width' => 800, 'height' => 500])
 */
function picture(string $path, string $alt, string $sizes = '100vw', array $attr = []): string
{
    $rel  = ltrim($path, '/');
    $base = preg_replace('/\.(jpe?g|png)$/i', '', $rel);
    $have = is_file(ROOT . '/assets/img/' . $base . '-800.webp');

    $a = '';
    foreach ($attr as $k => $v) {
        $a .= $v === true ? ' ' . $k : ' ' . $k . '="' . e((string) $v) . '"';
    }

    if (!$have) {
        return '<img src="' . e(img($rel)) . '" alt="' . e($alt) . '"' . $a . '>';
    }

    $widths = [400, 800, 1400];
    $srcset = function (string $ext) use ($base, $widths): string {
        $out = [];
        foreach ($widths as $w) {
            if (is_file(ROOT . '/assets/img/' . $base . '-' . $w . '.' . $ext)) {
                $out[] = e(img($base . '-' . $w . '.' . $ext)) . ' ' . $w . 'w';
            }
        }
        return implode(', ', $out);
    };
    $webp = $srcset('webp');
    $jpg  = $srcset('jpg');

    return '<picture>'
        . '<source type="image/webp" srcset="' . $webp . '" sizes="' . e($sizes) . '">'
        . '<img src="' . e(img($base . '-1400.jpg')) . '" srcset="' . $jpg . '" sizes="' . e($sizes) . '"'
        . ' alt="' . e($alt) . '"' . $a . '>'
        . '</picture>';
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Phone numbers and e-mail addresses read left-to-right even on the Arabic
 * pages, so isolate them from the surrounding RTL run.
 */
function ltr(?string $s): string
{
    return '<bdi dir="ltr">' . e($s) . '</bdi>';
}

function current_url(?string $lang = null): string
{
    $page = $GLOBALS['PAGE'] ?? 'home';
    $slug = $GLOBALS['PAGE_SLUG'] ?? '';
    if ($page === '404') {
        return url('home', $lang);      // nothing to switch to — offer the homepage
    }
    return url($page, $lang, $slug);
}

/* ---------------------------------------------------------------------
 * Contacts
 * ------------------------------------------------------------------ */

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function wa_href(string $number = '', string $text = ''): string
{
    $n = $number ?: cfg('contacts.whatsapp', '');
    $n = preg_replace('/\D/', '', $n);
    $u = 'https://api.whatsapp.com/send?phone=' . $n;
    if ($text !== '') {
        $u .= '&text=' . rawurlencode($text);
    }
    return $u;
}

/* ---------------------------------------------------------------------
 * Cars
 * ------------------------------------------------------------------ */

function cars_all(bool $only_published = true): array
{
    static $cars = null;
    if ($cars === null) {
        $cars = json_read(STORAGE . '/cars.json', []);
        usort($cars, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
    }
    if (!$only_published) {
        return $cars;
    }
    return array_values(array_filter($cars, fn($c) => !empty($c['published'])));
}

function car_find(string $slug): ?array
{
    foreach (cars_all(false) as $car) {
        if ($car['slug'] === $slug) {
            return !empty($car['published']) ? $car : null;
        }
    }
    return null;
}

function car_title(array $car, ?string $lang = null): string
{
    $l = $lang ?: lang();
    return $car['title'][$l] ?? $car['title']['en'] ?? $car['slug'];
}

function car_cover(array $car): string
{
    $imgs = $car['images'] ?? [];
    if (!$imgs) {
        return 'img/site/rentacar.jpg';
    }
    $i = max(1, (int) ($car['cover'] ?? 1)) - 1;
    return 'img/' . ($imgs[$i] ?? $imgs[0]);
}

/** Gallery with the cover photo first. */
function car_gallery(array $car): array
{
    $imgs = array_map(fn($p) => 'img/' . $p, $car['images'] ?? []);
    if (!$imgs) {
        return [];
    }
    $i = max(1, (int) ($car['cover'] ?? 1)) - 1;
    if (isset($imgs[$i]) && $i > 0) {
        $cover = $imgs[$i];
        unset($imgs[$i]);
        array_unshift($imgs, $cover);
    }
    return array_values($imgs);
}

/** Human-readable spec rows for one car. */
function car_specs(array $car): array
{
    $rows = [];
    if (!empty($car['year'])) {
        $rows['year'] = ['icon' => 'calendar', 'label' => t('spec.year'), 'value' => $car['year']];
    }
    if (!empty($car['engine'])) {
        $rows['engine'] = ['icon' => 'engine', 'label' => t('spec.engine'), 'value' => $car['engine']];
    }
    if (!empty($car['gearbox'])) {
        $rows['gearbox'] = ['icon' => 'gearbox', 'label' => t('spec.gearbox'), 'value' => t('val.' . $car['gearbox'])];
    }
    if (!empty($car['fuel'])) {
        $rows['fuel'] = ['icon' => 'fuel', 'label' => t('spec.fuel'), 'value' => t('val.' . $car['fuel'])];
    }
    if (!empty($car['seats'])) {
        $rows['seats'] = ['icon' => 'seats', 'label' => t('spec.seats'), 'value' => $car['seats']];
    }
    if (!empty($car['body'])) {
        $rows['body'] = ['icon' => 'car', 'label' => t('spec.body'), 'value' => t('val.' . $car['body'])];
    }
    if (!empty($car['color'])) {
        $rows['color'] = ['icon' => 'palette', 'label' => t('spec.color'), 'value' => t('val.' . $car['color'])];
    }
    return $rows;
}

/* ---------------------------------------------------------------------
 * SVG icons
 * ------------------------------------------------------------------ */

function icon(string $name, string $cls = ''): string
{
    static $set = null;
    if ($set === null) {
        $set = require __DIR__ . '/icons.php';
    }
    $svg = $set[$name] ?? '';
    if ($svg === '') {
        return '';
    }
    $attr = $cls !== '' ? ' class="' . e($cls) . '"' : '';
    return '<svg viewBox="0 0 24 24"' . $attr . ' aria-hidden="true" focusable="false">' . $svg . '</svg>';
}

/* ---------------------------------------------------------------------
 * Session, CSRF, requests
 * ------------------------------------------------------------------ */

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function csrf_token(): string
{
    boot_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(?string $token): bool
{
    boot_session();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------------------------------------------------------------------
 * Secrets — kept out of site.json, which is committed
 * ------------------------------------------------------------------ */

function secret_get(string $key, string $default = ''): string
{
    static $data = null;
    if ($data === null) {
        $data = json_read(STORAGE . '/secrets.json', []);
    }
    $v = $data[$key] ?? '';
    return is_string($v) && $v !== '' ? $v : $default;
}

function secret_set(string $key, string $value): bool
{
    $file = STORAGE . '/secrets.json';
    $data = json_read($file, []);
    $data[$key] = $value;
    $ok = json_write($file, $data);
    @chmod($file, 0600);
    return $ok;
}

/* ---------------------------------------------------------------------
 * Cloudflare Turnstile — the anti-spam check, keys live in the panel
 * ------------------------------------------------------------------ */

function turnstile_site_key(): string
{
    return (string) cfg('settings.turnstile_site', '');
}

function turnstile_secret_key(): string
{
    return secret_get('turnstile_secret');
}

function turnstile_on(): bool
{
    return turnstile_site_key() !== '' && turnstile_secret_key() !== '';
}

/** Renders nothing while the keys are unset, so the form keeps working. */
function turnstile_widget(string $theme = 'dark'): string
{
    if (!turnstile_on()) {
        return '';
    }
    return '<div class="cf-turnstile" data-sitekey="' . e(turnstile_site_key())
        . '" data-theme="' . e($theme) . '"></div>';
}

function turnstile_script(): string
{
    return turnstile_on()
        ? '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
        : '';
}

/** True when the check is off, so an unconfigured site behaves as before. */
function turnstile_verify(string $token): bool
{
    if (!turnstile_on()) {
        return true;
    }
    if ($token === '') {
        return false;
    }
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'timeout' => 10,
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => turnstile_secret_key(),
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
    ]]);
    $raw = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($raw === false) {
        return false;
    }
    $data = json_decode($raw, true);
    return !empty($data['success']);
}

/* ---------------------------------------------------------------------
 * Request form protection
 * ------------------------------------------------------------------ */

/** Per-install key for signing form stamps; created on first use. */
function form_secret(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }
    $file = STORAGE . '/secret.txt';
    if (is_file($file)) {
        $key = trim((string) file_get_contents($file));
    }
    if (empty($key)) {
        $key = bin2hex(random_bytes(32));
        @file_put_contents($file, $key, LOCK_EX);
        @chmod($file, 0600);
    }
    return $key;
}

/** Signed render time, checked on submit to catch instant bot posts. */
function form_stamp(): string
{
    $t = (string) time();
    return $t . '.' . hash_hmac('sha256', $t, form_secret());
}

function form_stamp_ok(string $value): bool
{
    $parts = explode('.', $value, 2);
    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        return false;
    }
    if (!hash_equals(hash_hmac('sha256', $parts[0], form_secret()), $parts[1])) {
        return false;
    }
    $age = time() - (int) $parts[0];
    // under 3s means nobody typed it; over 6h means a stale or replayed page
    return $age >= 3 && $age <= 21600;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Rate limit per IP: at most 5 requests in 10 minutes and 20 per day.
 * Returns false when the caller is over the limit.
 */
function form_rate_ok(string $ip, bool $record = false): bool
{
    $file = STORAGE . '/form-throttle.json';
    $data = json_read($file, []);
    $now = time();

    foreach ($data as $k => $stamps) {                      // forget yesterday
        $data[$k] = array_values(array_filter((array) $stamps, fn($t) => $t > $now - 86400));
        if (!$data[$k]) {
            unset($data[$k]);
        }
    }
    $mine = $data[$ip] ?? [];
    $recent = count(array_filter($mine, fn($t) => $t > $now - 600));

    if ($recent >= 5 || count($mine) >= 20) {
        json_write($file, $data);
        return false;
    }
    if ($record) {
        $mine[] = $now;
        $data[$ip] = $mine;
        json_write($file, $data);
    }
    return true;
}

/** Cheap heuristics for the usual link-spam payload. */
function looks_like_spam(string $text): bool
{
    if ($text === '') {
        return false;
    }
    if (preg_match_all('~https?://|www\.~i', $text) >= 2) {
        return true;
    }
    if (preg_match('~\[url=|\[/url\]|<a\s+href~i', $text)) {
        return true;
    }
    return false;
}

/** Strip anything that could break out into extra mail headers. */
function header_safe(string $v): string
{
    return trim(str_replace(["\r", "\n", "\0", '%0a', '%0d'], ' ', $v));
}

/** Store an incoming request; returns [ok, messageKey]. */
function save_request(array $post): array
{
    if (!csrf_ok($post['_csrf'] ?? null)) {
        return [false, 'form.spam'];
    }
    if (!empty($post['website'])) {          // honeypot
        return [false, 'form.spam'];
    }
    if (!form_stamp_ok((string) ($post['ts'] ?? ''))) {
        return [false, 'form.spam'];
    }
    if (!turnstile_verify((string) ($post['cf-turnstile-response'] ?? ''))) {
        return [false, 'form.captcha'];
    }

    $ip = client_ip();
    if (!form_rate_ok($ip)) {
        return [false, 'form.too_many'];
    }

    $name  = trim((string) ($post['name'] ?? ''));
    $phone = trim((string) ($post['phone'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $msg   = trim((string) ($post['message'] ?? ''));

    if ($name === '' || $phone === '') {
        return [false, 'form.error'];
    }
    if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
        return [false, 'form.error_phone'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'form.error_mail'];
    }
    if (looks_like_spam($msg) || looks_like_spam($name)) {
        return [false, 'form.spam'];
    }

    form_rate_ok($ip, true);                 // count this one

    $file = STORAGE . '/messages.json';
    $all = json_read($file, []);
    $all[] = [
        'id'      => bin2hex(random_bytes(6)),
        'date'    => date('Y-m-d H:i:s'),
        'name'    => mb_substr($name, 0, 120),
        'phone'   => mb_substr($phone, 0, 60),
        'email'   => mb_substr($email, 0, 160),
        'message' => mb_substr($msg, 0, 4000),
        'car'     => mb_substr(trim((string) ($post['car'] ?? '')), 0, 160),
        'lang'    => lang(),
        'page'    => mb_substr((string) ($post['page'] ?? ''), 0, 200),
        'ip'      => $ip,
        'status'  => 'new',
    ];
    // keep the file from growing without bound
    if (count($all) > 2000) {
        $all = array_slice($all, -2000);
    }
    json_write($file, $all);

    notify_email(end($all));
    return [true, 'form.success'];
}

function notify_email(array $msg): void
{
    $to = cfg('settings.notify_email', '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $brand = cfg('settings.brand', 'Carclub');
    $subject = '=?UTF-8?B?' . base64_encode($brand . ' — new request') . '?=';
    $body = "New request from the site\n\n"
        . "Name:    {$msg['name']}\n"
        . "Phone:   {$msg['phone']}\n"
        . "Email:   {$msg['email']}\n"
        . "Car:     {$msg['car']}\n"
        . "Page:    {$msg['page']}\n"
        . "Date:    {$msg['date']}\n\n"
        . "Message:\n{$msg['message']}\n";
    $host = preg_replace('~^https?://~', '', site_host());
    $from = 'no-reply@' . (preg_replace('~[^a-z0-9.\-]~i', '', $host) ?: 'carclub.az');
    $headers = 'From: ' . header_safe($brand) . ' <' . $from . ">\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
    // only a validated address may reach a header line
    if ($msg['email'] !== '' && filter_var($msg['email'], FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . header_safe($msg['email']) . "\r\n";
    }
    @mail($to, $subject, $body, $headers);
}

/* ---------------------------------------------------------------------
 * SEO helpers
 * ------------------------------------------------------------------ */

function site_host(): string
{
    $h = cfg('settings.host', '');
    if ($h !== '') {
        return rtrim($h, '/');
    }
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'carclub.az');
}

function canonical(): string
{
    return site_host() . current_url();
}
