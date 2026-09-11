<?php
/**
 * Deployment self-check. Open /health.php after a deploy.
 *
 * Deliberately free of dependencies: if the site itself returns 500, this page
 * still answers and says why. Delete it once the site is up.
 */

header('Content-Type: text/plain; charset=UTF-8');

$root = __DIR__;
$need = ['index.php', 'router.php', 'config/site.php', 'includes/functions.php',
    'includes/compat.php', 'includes/i18n.php', 'pages/home.php', 'admin/index.php',
    'assets/css/style.css', 'storage/cars.json', 'storage/site.json'];

echo "PHP\n";
echo "  version         : " . PHP_VERSION . "\n";
echo "  needs 7.4+      : " . (version_compare(PHP_VERSION, '7.4', '>=') ? 'ok' : 'TOO OLD') . "\n";
foreach (['str_starts_with' => '8.0', 'str_ends_with' => '8.0', 'array_is_list' => '8.1'] as $fn => $since) {
    echo sprintf("  %-16s: %s\n", $fn, function_exists($fn) ? 'built in' : 'missing (polyfill needed, PHP < ' . $since . ')');
}
foreach (['mbstring', 'json'] as $ext) {
    echo sprintf("  ext %-12s: %s\n", $ext, extension_loaded($ext) ? 'ok' : 'MISSING');
}

echo "\nFILES\n";
$missing = 0;
foreach ($need as $f) {
    $ok = is_file($root . '/' . $f);
    if (!$ok) {
        $missing++;
    }
    echo sprintf("  %-28s %s\n", $f, $ok ? 'ok' : 'MISSING');
}

echo "\nSTORAGE\n";
$dir = $root . '/storage';
echo "  exists          : " . (is_dir($dir) ? 'yes' : 'NO') . "\n";
echo "  writable        : " . (is_writable($dir) ? 'yes' : 'NO — admin panel cannot save') . "\n";
if (is_dir($dir)) {
    echo "  permissions     : " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
}

echo "\nADMIN ACCOUNT\n";
$users = $root . '/storage/admin-users.json';
if (!is_file($users)) {
    echo "  not created yet — open /admin/ once and the first account appears\n";
} else {
    $d = json_decode((string) file_get_contents($users), true);
    foreach ($d['users'] ?? [] as $u) {
        echo sprintf("  %-28s role %-8s %s\n", $u['email'] ?? '?', $u['role'] ?? '?',
            (int) ($u['active'] ?? 1) === 1 ? 'active' : 'disabled');
    }
}
$pw = $root . '/storage/admin-password.txt';
echo "  password file   : " . (is_file($pw) ? 'storage/admin-password.txt (read it, then delete)' : 'not present') . "\n";
echo "  to reset        : put storage/admin-reset.txt with \"email:newpassword\", then log in\n";

echo "\nLEFTOVER WORDPRESS\n";
$wp = 0;
foreach (['wp-config.php', 'wp-admin', 'wp-includes', 'wp-content', 'wp-login.php'] as $f) {
    if (file_exists($root . '/' . $f)) {
        echo "  still present   : $f\n";
        $wp++;
    }
}
echo $wp ? "  -> remove these, the old site was hacked\n" : "  clean\n";

echo "\nBOOT TEST\n";
try {
    require_once $root . '/includes/functions.php';
    $cars = cars_all();
    echo "  config loaded   : ok (default lang " . cfg('default_lang', '?') . ")\n";
    echo "  cars published  : " . count($cars) . "\n";
    echo "  site host       : " . site_host() . "\n";
    echo "\nRESULT: site code boots fine" . ($missing ? " ({$missing} files missing)" : '') . "\n";
} catch (Throwable $e) {
    echo "  FAILED: " . get_class($e) . ' — ' . $e->getMessage() . "\n";
    echo "  at " . str_replace($root, '', $e->getFile()) . ':' . $e->getLine() . "\n";
    echo "\nRESULT: this is what makes the site return 500\n";
}
