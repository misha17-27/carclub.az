<?php
/**
 * Carclub admin — shared helpers.
 *
 * Same shape as the tce.az panel (file storage, no database):
 *   storage/site.json          content + settings overlay
 *   storage/cars.json          the fleet
 *   storage/messages.json      requests from the site
 *   storage/admin-users.json   panel users
 *   storage/login-throttle.json brute-force guard
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

/* ---------------------------------------------------------------------
 * site.json overlay (what the panel edits)
 * ------------------------------------------------------------------ */

function overlay(): array
{
    if (!isset($GLOBALS['__overlay'])) {
        $GLOBALS['__overlay'] = json_read(STORAGE . '/site.json', []);
    }
    return $GLOBALS['__overlay'];
}

/** Write a dotted path into the overlay (in memory). */
function ov_set(string $path, $value): void
{
    $data = overlay();
    $ref = &$data;
    foreach (explode('.', $path) as $key) {
        if (!isset($ref[$key]) || !is_array($ref[$key])) {
            $ref[$key] = [];
        }
        $ref = &$ref[$key];
    }
    $ref = $value;
    unset($ref);
    $GLOBALS['__overlay'] = $data;
}

function ov_save(): bool
{
    $ok = json_write(STORAGE . '/site.json', overlay());
    unset($GLOBALS['__overlay']);
    return $ok;
}

/** Current effective value (overlay first, then config default). */
function val(string $path, string $default = ''): string
{
    $v = cfg($path);
    return ($v === null || is_array($v)) ? $default : (string) $v;
}

/** Directory the panel is served from, always with a trailing slash. */
function admin_base(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';
    if (str_ends_with($uri, '.php')) {
        $uri = dirname($uri);
    }
    return rtrim($uri, '/') . '/';
}

/**
 * A relative Location is resolved against the *current* URL, so a request to
 * /admin (no trailing slash) would send "index.php?section=login" to the site
 * root and land on its 404. Always redirect to an absolute path.
 */
function redirect(string $to): void
{
    if (!preg_match('~^(https?:)?//|^/~', $to)) {
        $to = admin_base() . $to;
    }
    header('Location: ' . $to);
    exit;
}

function slugify(string $s): string
{
    static $map = [
        'ə' => 'e', 'Ə' => 'e', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ü' => 'u', 'Ü' => 'u',
        'ç' => 'c', 'Ç' => 'c', 'ş' => 's', 'Ş' => 's', 'ğ' => 'g', 'Ğ' => 'g',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $s = strtr(mb_strtolower($s, 'UTF-8'), $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-');
}

/* ---------------------------------------------------------------------
 * Users
 * ------------------------------------------------------------------ */

function admin_users_file(): string
{
    return STORAGE . '/admin-users.json';
}

/**
 * Password rescue for hosting without shell access.
 *
 * Put a file storage/admin-reset.txt next to the data with either
 *     newpassword
 *     admin@carclub.az:newpassword
 * and it is applied on the next visit to the panel, then deleted. The folder
 * is not reachable over HTTP, so only someone with FTP or the hosting file
 * manager can do this.
 */
function apply_password_reset(array $data): array
{
    $file = STORAGE . '/admin-reset.txt';
    if (!is_file($file)) {
        return $data;
    }
    $raw = trim((string) file_get_contents($file));
    @unlink($file);                       // one shot, whatever happens next

    if ($raw === '') {
        return $data;
    }
    if (strpos($raw, ':') !== false) {
        [$email, $pass] = array_map('trim', explode(':', $raw, 2));
    } else {
        $email = '';
        $pass = $raw;
    }
    if (strlen($pass) < 8) {
        return $data;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $done = false;
    foreach ($data['users'] as $i => $u) {
        if ($email === '' || strcasecmp((string) $u['email'], $email) === 0) {
            $data['users'][$i]['pass_hash'] = $hash;
            $data['users'][$i]['active'] = 1;
            $done = true;
            if ($email === '') {
                break;                    // no address given: first account
            }
        }
    }
    if (!$done && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data['users'][] = [
            'id' => (int) ($data['next_id'] ?? 2),
            'name' => 'Admin',
            'email' => $email,
            'pass_hash' => $hash,
            'role' => 'admin',
            'active' => 1,
            'last_login' => null,
        ];
        $data['next_id'] = (int) ($data['next_id'] ?? 2) + 1;
        $done = true;
    }
    if ($done) {
        save_admin_users($data);
    }
    return $data;
}

function admin_users(): array
{
    if (isset($GLOBALS['__users'])) {
        return $GLOBALS['__users'];
    }
    $data = json_read(admin_users_file(), []);
    if (!empty($data['users'])) {
        return $GLOBALS['__users'] = apply_password_reset($data);
    }

    // first run: create an admin with a random password, written next to the data
    $password = bin2hex(random_bytes(6));
    $data = ['next_id' => 2, 'users' => [[
        'id' => 1,
        'name' => 'Admin',
        'email' => 'admin@carclub.az',
        'pass_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
        'active' => 1,
        'last_login' => null,
    ]]];
    save_admin_users($data);
    @file_put_contents(STORAGE . '/admin-password.txt', "Login: admin@carclub.az\nPassword: {$password}\n", LOCK_EX);
    return $data;
}

function save_admin_users(array $data): void
{
    json_write(admin_users_file(), $data);
    $GLOBALS['__users'] = $data;
}

function current_admin(): ?array
{
    boot_session();
    return $_SESSION['admin'] ?? null;
}

function require_login(): array
{
    $a = current_admin();
    if (!$a) {
        redirect('index.php?section=login');
    }
    return $a;
}

function attempt_login(string $email, string $pass): bool
{
    $email = trim($email);
    $data = admin_users();
    foreach ($data['users'] as $i => $u) {
        if (strcasecmp((string) $u['email'], $email) !== 0) {
            continue;
        }
        if ((int) ($u['active'] ?? 1) === 1 && password_verify($pass, (string) $u['pass_hash'])) {
            boot_session();
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id' => (int) $u['id'],
                'email' => (string) $u['email'],
                'name' => (string) ($u['name'] ?? ''),
                'role' => (string) ($u['role'] ?? 'admin'),
            ];
            $data['users'][$i]['last_login'] = date('Y-m-d H:i');
            save_admin_users($data);
            return true;
        }
        return false;
    }
    return false;
}

/* Brute-force guard: 10 failures from one IP → locked for 15 minutes. */
function throttle_data(): array
{
    return json_read(STORAGE . '/login-throttle.json', []);
}

function throttle_save(array $data): void
{
    $now = time();
    foreach ($data as $ip => $row) {
        if (($row['ts'] ?? 0) < $now - 86400) {
            unset($data[$ip]);
        }
    }
    json_write(STORAGE . '/login-throttle.json', $data);
}

function login_locked(string $ip): bool
{
    $row = throttle_data()[$ip] ?? null;
    return $row && ($row['locked_until'] ?? 0) > time();
}

function login_fail(string $ip): void
{
    $data = throttle_data();
    $row = $data[$ip] ?? ['fails' => 0, 'locked_until' => 0];
    $row['fails'] = (int) ($row['fails'] ?? 0) + 1;
    $row['ts'] = time();
    if ($row['fails'] >= 10) {
        $row['locked_until'] = time() + 15 * 60;
        $row['fails'] = 0;
    }
    $data[$ip] = $row;
    throttle_save($data);
}

function login_ok(string $ip): void
{
    $data = throttle_data();
    unset($data[$ip]);
    throttle_save($data);
}

function is_admin(): bool
{
    $a = current_admin();
    return ($a['role'] ?? 'admin') === 'admin';
}

function logout(): void
{
    boot_session();
    $_SESSION = [];
    session_destroy();
}

/* ---------------------------------------------------------------------
 * CSRF and flash
 * ------------------------------------------------------------------ */

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    boot_session();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
            http_response_code(400);
            exit('Bad CSRF token. Обновите страницу.');
        }
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    boot_session();
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_render(): string
{
    boot_session();
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $out .= '<div class="flash ' . ($t === 'err' ? 'err' : 'ok') . '">' . e($m) . '</div>';
    }
    $_SESSION['flash'] = [];
    return $out;
}

/* ---------------------------------------------------------------------
 * Uploads → assets/img/uploads
 * ------------------------------------------------------------------ */

function admin_move(string $tmp, string $name): ?string
{
    $dir = ROOT . '/assets/img/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name)) ?? '';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $raster = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, array_merge($raster, ['svg']), true)) {
        return null;
    }
    if (!is_uploaded_file($tmp)) {
        return null;
    }
    // a renamed script must not pass as an image
    if (in_array($ext, $raster, true) && @getimagesize($tmp) === false) {
        return null;
    }
    if ($ext === 'svg') {
        $head = (string) @file_get_contents($tmp, false, null, 0, 200 * 1024);
        if (preg_match('~<script|on[a-z]+\s*=|javascript:~i', $head)) {
            return null;
        }
    }
    $name = time() . '_' . $name;
    return move_uploaded_file($tmp, "$dir/$name") ? 'uploads/' . $name : null;
}

/** Video upload — kept apart from images, different types and size limits. */
function admin_upload_video(string $field): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $_FILES[$field]['name'])) ?? '';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'mov'], true)) {
        return null;
    }
    $tmp = (string) $_FILES[$field]['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return null;
    }
    $dir = ROOT . '/assets/video';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = time() . '_' . $name;
    return move_uploaded_file($tmp, "$dir/$name") ? 'video/' . $name : null;
}

function admin_upload(string $field): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    return admin_move((string) $_FILES[$field]['tmp_name'], (string) $_FILES[$field]['name']);
}

function admin_upload_multi(string $field): array
{
    $out = [];
    if (empty($_FILES[$field]['name'][0])) {
        return $out;
    }
    foreach ((array) $_FILES[$field]['name'] as $i => $name) {
        if ($name === '') {
            continue;
        }
        if ($p = admin_move((string) $_FILES[$field]['tmp_name'][$i], (string) $name)) {
            $out[] = $p;
        }
    }
    return $out;
}

/* ---------------------------------------------------------------------
 * Cars storage
 * ------------------------------------------------------------------ */

function cars_load(): array
{
    $cars = json_read(STORAGE . '/cars.json', []);
    usort($cars, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
    return $cars;
}

function cars_save(array $cars): bool
{
    $i = 1;
    foreach ($cars as &$c) {
        $c['order'] = $i++;
    }
    unset($c);
    return json_write(STORAGE . '/cars.json', $cars);
}

function messages_load(): array
{
    $m = json_read(STORAGE . '/messages.json', []);
    return array_reverse($m);   // newest first
}

function messages_save(array $list): bool
{
    return json_write(STORAGE . '/messages.json', array_reverse($list));
}

/* ---------------------------------------------------------------------
 * Panel structure
 * ------------------------------------------------------------------ */

const SECTIONS = [
    'overview'    => ['Обзор', '▤'],
    'cars'        => ['Автомобили', '◆'],
    'bodies'      => ['Типы кузова', '▤'],
    'home'        => ['Главная страница', '★'],
    'pages'       => ['Тексты страниц', '¶'],
    'images'      => ['Изображения', '▣'],
    'contacts'    => ['Контакты и соцсети', '☏'],
    'seo'         => ['SEO', '◎'],
    'submissions' => ['Заявки с сайта', '✉'],
    'settings'    => ['Настройки', '⚙'],
    'security'    => ['Безопасность', '⚿'],
    'users'       => ['Пользователи', '☺'],
    'profile'     => ['Мой профиль', '☻'],
];

const GROUPS = [
    'Контент'   => ['overview', 'cars', 'bodies', 'home', 'pages', 'images'],
    'Сайт'      => ['contacts', 'seo', 'settings', 'security'],
    'Обращения' => ['submissions'],
    'Доступ'    => ['users', 'profile'],
];

const ADMIN_ONLY = ['users', 'settings', 'security'];

/** Site languages as [code => label] for the editor tabs. */
function edit_langs(): array
{
    $out = [];
    foreach (langs() as $code => $l) {
        $out[$code] = $l['short'] . ' · ' . $l['name'];
    }
    return $out;
}

/* ---------------------------------------------------------------------
 * Layout
 * ------------------------------------------------------------------ */

function layout_top(string $active, string $title): void
{
    $admin = current_admin();
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . e($title) . ' — Carclub admin</title>';
    echo '<link rel="icon" href="' . e(img('favicon.ico')) . '">';
    echo '<style>' . admin_css() . '</style></head><body><div class="wrap">';

    echo '<aside class="side"><div class="brand"><img src="' . e(img('logo.png')) . '" alt="Carclub"></div><nav>';
    foreach (GROUPS as $glabel => $keys) {
        echo '<div class="navgroup">' . e($glabel) . '</div>';
        foreach ($keys as $key) {
            if (in_array($key, ADMIN_ONLY, true) && !is_admin()) {
                continue;
            }
            [$label, $ic] = SECTIONS[$key];
            $cls = $key === $active ? ' class="on"' : '';
            echo '<a' . $cls . ' href="index.php?section=' . $key . '"><i>' . $ic . '</i><span>' . e($label) . '</span></a>';
        }
    }
    echo '</nav><div class="who">Вы вошли как<br><b>' . e(($admin['name'] ?? '') !== '' ? $admin['name'] : ($admin['email'] ?? '')) . '</b></div></aside>';

    echo '<main><header class="bar"><h1>' . e($title) . '</h1><div class="bar__act">';
    echo '<a class="btn ghost" href="' . e(url('home')) . '" target="_blank" rel="noopener">Открыть сайт</a> ';
    echo '<a class="btn red" href="index.php?section=logout">Выйти</a></div></header><div class="body">';
    echo flash_render();
}

function layout_bottom(): void
{
    echo '</div></main></div></body></html>';
}

function admin_css(): string
{
    return <<<'CSS'
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f5f5f4;color:#18181b;font-size:15px}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
.wrap{display:flex;min-height:100vh}

.side{width:252px;background:#0d0d0f;color:#d6d3d1;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;flex:none}
.brand{padding:18px;border-bottom:1px solid #26262a}
.brand img{max-height:40px;width:auto;margin:0 auto}
.side nav{display:flex;flex-direction:column;padding:6px 0 14px;flex:1;overflow:auto}
.navgroup{color:#78716c;font-size:11px;letter-spacing:.1em;padding:16px 20px 6px;font-weight:700;text-transform:uppercase}
.side nav a{color:#a8a29e;padding:11px 20px;font-size:14px;display:flex;gap:12px;align-items:center;border-left:3px solid transparent}
.side nav a i{width:18px;font-style:normal;opacity:.85;text-align:center}
.side nav a:hover{background:#1c1c20;color:#fff}
.side nav a.on{background:#1c1c20;color:#d4ad00;font-weight:700;border-left-color:#d4ad00}
.who{padding:16px 20px;font-size:12px;color:#78716c;border-top:1px solid #26262a}
.who b{color:#d6d3d1}

main{flex:1;min-width:0}
.bar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 26px;background:#fff;border-bottom:1px solid #e7e5e4;position:sticky;top:0;z-index:5}
.bar h1{margin:0;font-size:21px}
.bar__act{display:flex;gap:8px;flex-wrap:wrap}
.body{padding:24px 26px;max-width:1160px}

.btn{display:inline-flex;align-items:center;gap:7px;background:#0d0d0f;color:#fff;border:0;padding:10px 18px;border-radius:22px;font-weight:700;cursor:pointer;font-size:14px;font-family:inherit;line-height:1.2}
.btn:hover{background:#26262a}
.btn.ghost{background:transparent;color:#0d0d0f;border:1.5px solid #d6d3d1}
.btn.ghost:hover{background:#f5f5f4;border-color:#0d0d0f}
.btn.gold{background:#d4ad00;color:#000}
.btn.gold:hover{background:#b89700}
.btn.sm{padding:6px 13px;font-size:13px;border-radius:8px}
.btn.red{background:#b91c1c}.btn.red:hover{background:#991717}

.flash{padding:12px 16px;border-radius:9px;margin-bottom:16px;font-weight:600}
.flash.ok{background:#052e16;color:#bbf7d0}
.flash.err{background:#7f1d1d;color:#fecaca}

.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:15px;margin-bottom:22px}
.card{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:19px 21px}
.card .n{font-size:32px;font-weight:800;color:#0d0d0f;line-height:1.15}
.card .l{color:#78716c;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-top:3px}

.panel{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:22px;margin-bottom:20px}
.panel h2{margin:0 0 4px;font-size:17px}
.panel .hint{color:#78716c;font-size:13px;margin:0 0 16px}

table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:10px 11px;border-bottom:1px solid #f0efee;font-size:14px;vertical-align:middle}
th{color:#78716c;font-size:11px;text-transform:uppercase;letter-spacing:.05em}
tr:last-child td{border-bottom:0}
td.right,th.right{text-align:right}

label{display:block;font-weight:600;margin:14px 0 6px;font-size:13.5px}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=url],textarea,select{width:100%;padding:10px 12px;border:1px solid #d6d3d1;border-radius:9px;font-size:14px;font-family:inherit;background:#fff;color:inherit}
input:focus,textarea:focus,select:focus{outline:none;border-color:#d4ad00;box-shadow:0 0 0 3px rgba(212,173,0,.15)}
textarea{min-height:92px;resize:vertical;line-height:1.5}
textarea.tall{min-height:190px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.muted{color:#78716c;font-size:13px}
.right{text-align:right}
.mt{margin-top:18px}
.chkline{display:flex;align-items:center;gap:9px;margin:14px 0 0;font-weight:600;font-size:14px}
.chkline input{width:auto}

.tabs{display:flex;gap:6px;flex-wrap:wrap;border-bottom:1px solid #e7e5e4;margin-bottom:18px;padding-bottom:0}
.tabs a{padding:9px 15px;border-radius:9px 9px 0 0;font-weight:600;font-size:13.5px;color:#78716c;border:1px solid transparent;border-bottom:0;margin-bottom:-1px}
.tabs a:hover{color:#0d0d0f;background:#faf9f8}
.tabs a.on{background:#fff;border-color:#e7e5e4;color:#0d0d0f}

.thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(132px,1fr));gap:12px;margin-top:10px}
.gitem{border:1px solid #e7e5e4;border-radius:11px;padding:8px;text-align:center;background:#faf9f8}
.gitem img{width:100%;height:88px;object-fit:cover;border-radius:7px;margin-bottom:7px}
.gitem .nm{font-size:11px;color:#78716c;word-break:break-all;line-height:1.3;margin-bottom:6px}
.gitem.cover{border-color:#d4ad00;box-shadow:0 0 0 2px rgba(212,173,0,.2)}

.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.02em}
.badge.new{background:#d4ad00;color:#000}
.badge.read{background:#e7e5e4;color:#57534e}
.badge.on{background:#dcfce7;color:#166534}
.badge.off{background:#fee2e2;color:#991b1b}

.login{min-height:100vh;display:grid;place-items:center;background:#0d0d0f;padding:16px}
.login .box{background:#fff;padding:34px 32px;border-radius:18px;width:370px;max-width:94vw;box-shadow:0 24px 70px rgba(0,0,0,.45)}
/* the logo is gold-on-black artwork — it needs a dark plate on a white card */
.login .box img{max-height:44px;width:auto;margin:0 auto 18px;background:#0d0d0f;padding:11px 20px;border-radius:12px;box-sizing:content-box}
.login h2{margin:0 0 3px;font-size:20px}
.login .sub{color:#78716c;margin:0 0 16px;font-size:13.5px}
.login .flash{margin-top:14px}

.bodyrow{border:1px solid #e7e5e4;border-radius:12px;padding:15px 17px;margin-bottom:12px}
.bodyrow__top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:2px}
code{background:#f5f5f4;padding:2px 7px;border-radius:5px;font-size:13px}
.sortbtn{background:#f5f5f4;border:1px solid #d6d3d1;border-radius:7px;width:30px;height:28px;cursor:pointer;font-size:13px;line-height:1}
.sortbtn:hover{background:#e7e5e4}

@media(max-width:900px){
  .side{width:60px}
  .side .brand,.who{display:none}
  .side nav a{justify-content:center;padding:14px 0;border-left:0;border-bottom:3px solid transparent}
  .side nav a span{display:none}
  .side nav a.on{border-left:0;border-bottom-color:#d4ad00}
  .navgroup{padding:11px 0 3px;text-align:center;font-size:9px;letter-spacing:0}
  .row,.row3{grid-template-columns:1fr}
  .bar{padding:13px 15px}
  .body{padding:17px 14px}
  .panel{padding:17px}
  .panel>table,.tablewrap{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  .tablewrap table{min-width:820px}
  /* the thumbnail column is not worth its width on a phone */
  .tablewrap td:first-child,.tablewrap th:first-child{display:none}
}
CSS;
}
