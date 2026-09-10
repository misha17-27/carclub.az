<?php
/**
 * Carclub admin panel — single entry point.
 * Sections are selected with ?section=..., all writes go through POST + redirect.
 */
declare(strict_types=1);

require __DIR__ . '/lib.php';

$section = preg_replace('/[^a-z_]/', '', (string) ($_GET['section'] ?? 'overview')) ?: 'overview';

/* ---------------- auth ---------------- */
if ($section === 'logout') {
    logout();
    redirect('index.php?section=login');
}

if ($section === 'login') {
    boot_session();
    if (current_admin()) {
        redirect('index.php');
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        if (login_locked($ip)) {
            flash('Слишком много попыток входа. Попробуйте через 15 минут.', 'err');
        } elseif (attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            login_ok($ip);
            redirect('index.php');
        } else {
            login_fail($ip);
            flash('Неверный логин или пароль.', 'err');
        }
        redirect('index.php?section=login');
    }
    ?>
    <!doctype html>
    <html lang="ru">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>Вход — Carclub admin</title>
        <link rel="icon" href="<?= e(img('favicon.ico')) ?>">
        <style><?= admin_css() ?></style>
    </head>

    <body>
        <div class="login">
            <form class="box" method="post">
                <img src="<?= e(img('logo.png')) ?>" alt="Carclub">
                <h2>Панель управления</h2>
                <p class="sub">Введите данные для входа</p>
                <?= csrf_field() ?>
                <label for="l-email">Email</label>
                <input id="l-email" name="email" type="email" autocomplete="username" required autofocus>
                <label for="l-pass">Пароль</label>
                <input id="l-pass" name="password" type="password" autocomplete="current-password" required>
                <div class="mt"><button class="btn gold" style="width:100%;justify-content:center">Войти</button></div>
                <?= flash_render() ?>
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

$admin = require_login();
if (!isset(SECTIONS[$section])) {
    $section = 'overview';
}
if (in_array($section, ADMIN_ONLY, true) && !is_admin()) {
    $section = 'overview';
}

/* editing language for content sections */
$ELANGS = edit_langs();
$el = (string) ($_GET['lang'] ?? cfg('default_lang', 'en'));
if (!isset($ELANGS[$el])) {
    $el = cfg('default_lang', 'en');
}

/* ====================================================================
 * POST handlers
 * ================================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $act = (string) ($_POST['act'] ?? '');

    /* ---------- home / pages / seo text ---------- */
    if (in_array($section, ['home', 'pages', 'seo'], true) && $act === 'save') {
        $lang = (string) ($_POST['lang'] ?? $el);
        if (!isset($ELANGS[$lang])) {
            $lang = cfg('default_lang', 'en');
        }
        foreach ((array) ($_POST['f'] ?? []) as $path => $value) {
            if (!preg_match('~^[a-z0-9_]+\.[a-z0-9_]+$~', (string) $path)) {
                continue;
            }
            ov_set('content.' . $lang . '.' . $path, trim((string) $value));
        }
        ov_save();
        flash('Тексты сохранены (' . strtoupper($lang) . ').');
        redirect('index.php?section=' . $section . '&lang=' . $lang);
    }

    /* ---------- footer text (single field per language) ---------- */
    if ($section === 'pages' && $act === 'save_footer') {
        $lang = (string) ($_POST['lang'] ?? $el);
        if (isset($ELANGS[$lang])) {
            ov_set('content.' . $lang . '.footer_text', trim((string) ($_POST['footer_text'] ?? '')));
            ov_save();
            flash('Текст подвала сохранён.');
        }
        redirect('index.php?section=pages&lang=' . $lang);
    }

    /* ---------- contacts ---------- */
    if ($section === 'contacts' && $act === 'save') {
        foreach (['phone1', 'phone2', 'whatsapp', 'whatsapp2', 'email', 'tiktok', 'instagram', 'facebook', 'map_query'] as $k) {
            ov_set('contacts.' . $k, trim((string) ($_POST[$k] ?? '')));
        }
        ov_save();
        flash('Контакты сохранены.');
        redirect('index.php?section=contacts');
    }

    /* ---------- settings ---------- */
    if ($section === 'settings' && $act === 'save') {
        foreach (['brand', 'notify_email', 'ga_id', 'host'] as $k) {
            ov_set('settings.' . $k, trim((string) ($_POST[$k] ?? '')));
        }
        ov_set('settings.home_cars', max(1, min(24, (int) ($_POST['home_cars'] ?? 6))));
        foreach (['hero_image', 'about_image', 'og_image'] as $k) {
            if ($up = admin_upload($k . '_file')) {
                ov_set('settings.' . $k, 'img/' . $up);
            } elseif (($v = trim((string) ($_POST[$k] ?? ''))) !== '') {
                ov_set('settings.' . $k, $v);
            }
        }
        ov_save();
        flash('Настройки сохранены.');
        redirect('index.php?section=settings');
    }

    /* ---------- cars ---------- */
    if ($section === 'cars') {
        $cars = cars_load();

        if ($act === 'save') {
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $orig = trim((string) ($_POST['orig_slug'] ?? ''));
            $titles = [];
            foreach (array_keys($ELANGS) as $code) {
                $titles[$code] = trim((string) ($_POST['title'][$code] ?? ''));
            }
            $base = $titles[cfg('default_lang', 'en')] ?: reset($titles);
            if ($slug === '') {
                $slug = slugify((string) $base);
            }
            $slug = slugify($slug);
            if ($slug === '' || $base === '') {
                flash('Укажите название автомобиля.', 'err');
                redirect('index.php?section=cars&edit=' . rawurlencode($orig));
            }
            foreach ($titles as $code => $v) {
                if ($v === '') {
                    $titles[$code] = (string) $base;
                }
            }

            $idx = null;
            foreach ($cars as $i => $c) {
                if ($c['slug'] === $orig) {
                    $idx = $i;
                    break;
                }
            }
            // a new slug must stay unique
            foreach ($cars as $i => $c) {
                if ($c['slug'] === $slug && $i !== $idx) {
                    $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
                    break;
                }
            }

            $rec = $idx !== null ? $cars[$idx] : ['images' => [], 'cover' => 1, 'order' => count($cars) + 1];
            $rec['slug'] = $slug;
            $rec['title'] = $titles;
            $rec['brand'] = trim((string) ($_POST['brand'] ?? ''));
            $rec['body'] = in_array($_POST['body'] ?? '', ['sedan', 'suv'], true) ? (string) $_POST['body'] : 'sedan';
            $rec['year'] = trim((string) ($_POST['year'] ?? ''));
            $rec['engine'] = trim((string) ($_POST['engine'] ?? ''));
            $rec['fuel'] = in_array($_POST['fuel'] ?? '', ['benzin', 'hybrid', 'dizel', 'elektro'], true) ? (string) $_POST['fuel'] : 'benzin';
            $rec['gearbox'] = in_array($_POST['gearbox'] ?? '', ['avtomat', 'mexanika'], true) ? (string) $_POST['gearbox'] : 'avtomat';
            $rec['seats'] = trim((string) ($_POST['seats'] ?? ''));
            $rec['color'] = trim((string) ($_POST['color'] ?? ''));
            $rec['published'] = !empty($_POST['published']);

            // keep only the photos still ticked, then append the newly uploaded ones
            $keep = array_values(array_intersect((array) ($_POST['keep'] ?? []), $rec['images'] ?? []));
            foreach (admin_upload_multi('photos') as $p) {
                $keep[] = $p;
            }
            $rec['images'] = array_values(array_unique($keep));
            $cover = (int) ($_POST['cover'] ?? 1);
            $rec['cover'] = max(1, min(count($rec['images']) ?: 1, $cover));

            if ($idx !== null) {
                $cars[$idx] = $rec;
            } else {
                $cars[] = $rec;
            }
            cars_save($cars);
            flash('Автомобиль сохранён.');
            redirect('index.php?section=cars&edit=' . rawurlencode($slug));
        }

        if ($act === 'delete') {
            $slug = (string) ($_POST['slug'] ?? '');
            $cars = array_values(array_filter($cars, fn($c) => $c['slug'] !== $slug));
            cars_save($cars);
            flash('Автомобиль удалён.');
            redirect('index.php?section=cars');
        }

        if ($act === 'move') {
            $slug = (string) ($_POST['slug'] ?? '');
            $dir = ($_POST['dir'] ?? 'up') === 'down' ? 1 : -1;
            foreach ($cars as $i => $c) {
                if ($c['slug'] !== $slug) {
                    continue;
                }
                $j = $i + $dir;
                if ($j >= 0 && $j < count($cars)) {
                    [$cars[$i], $cars[$j]] = [$cars[$j], $cars[$i]];
                }
                break;
            }
            cars_save($cars);
            redirect('index.php?section=cars');
        }

        if ($act === 'toggle') {
            $slug = (string) ($_POST['slug'] ?? '');
            foreach ($cars as $i => $c) {
                if ($c['slug'] === $slug) {
                    $cars[$i]['published'] = empty($c['published']);
                }
            }
            cars_save($cars);
            redirect('index.php?section=cars');
        }
    }

    /* ---------- submissions ---------- */
    if ($section === 'submissions') {
        $list = array_reverse(messages_load());     // back to storage order
        if ($act === 'read' || $act === 'unread') {
            $id = (string) ($_POST['id'] ?? '');
            foreach ($list as $i => $m) {
                if (($m['id'] ?? '') === $id) {
                    $list[$i]['status'] = $act === 'read' ? 'read' : 'new';
                }
            }
            messages_save(array_reverse($list));
        }
        if ($act === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            $list = array_values(array_filter($list, fn($m) => ($m['id'] ?? '') !== $id));
            messages_save(array_reverse($list));
            flash('Заявка удалена.');
        }
        if ($act === 'clear_read') {
            $list = array_values(array_filter($list, fn($m) => ($m['status'] ?? 'new') !== 'read'));
            messages_save(array_reverse($list));
            flash('Прочитанные заявки удалены.');
        }
        redirect('index.php?section=submissions');
    }

    /* ---------- images ---------- */
    if ($section === 'images') {
        if ($act === 'upload') {
            $n = count(admin_upload_multi('files'));
            flash($n ? "Загружено файлов: {$n}." : 'Не удалось загрузить файлы.', $n ? 'ok' : 'err');
        }
        if ($act === 'delete') {
            $f = basename((string) ($_POST['file'] ?? ''));
            $p = ROOT . '/assets/img/uploads/' . $f;
            if ($f !== '' && is_file($p)) {
                @unlink($p);
                flash('Файл удалён.');
            }
        }
        redirect('index.php?section=images');
    }

    /* ---------- profile ---------- */
    if ($section === 'profile' && $act === 'save') {
        $data = admin_users();
        foreach ($data['users'] as $i => $u) {
            if ((int) $u['id'] !== (int) $admin['id']) {
                continue;
            }
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            if ($name !== '') {
                $data['users'][$i]['name'] = $name;
            }
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['users'][$i]['email'] = $email;
            }
            $new = (string) ($_POST['password'] ?? '');
            if ($new !== '') {
                if (!password_verify((string) ($_POST['current'] ?? ''), (string) $u['pass_hash'])) {
                    flash('Текущий пароль указан неверно.', 'err');
                    redirect('index.php?section=profile');
                }
                if (strlen($new) < 8) {
                    flash('Новый пароль должен быть не короче 8 символов.', 'err');
                    redirect('index.php?section=profile');
                }
                $data['users'][$i]['pass_hash'] = password_hash($new, PASSWORD_DEFAULT);
            }
            save_admin_users($data);
            $_SESSION['admin']['name'] = $data['users'][$i]['name'];
            $_SESSION['admin']['email'] = $data['users'][$i]['email'];
            flash('Профиль обновлён.');
            break;
        }
        redirect('index.php?section=profile');
    }

    /* ---------- users ---------- */
    if ($section === 'users' && is_admin()) {
        $data = admin_users();
        if ($act === 'add') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $pass  = (string) ($_POST['password'] ?? '');
            $exists = false;
            foreach ($data['users'] as $u) {
                if (strcasecmp((string) $u['email'], $email) === 0) {
                    $exists = true;
                }
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
                flash('Нужен корректный email и пароль от 8 символов.', 'err');
            } elseif ($exists) {
                flash('Пользователь с таким email уже есть.', 'err');
            } else {
                $data['users'][] = [
                    'id' => (int) ($data['next_id'] ?? 2),
                    'name' => trim((string) ($_POST['name'] ?? '')) ?: 'User',
                    'email' => $email,
                    'pass_hash' => password_hash($pass, PASSWORD_DEFAULT),
                    'role' => ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor',
                    'active' => 1,
                    'last_login' => null,
                ];
                $data['next_id'] = (int) ($data['next_id'] ?? 2) + 1;
                save_admin_users($data);
                flash('Пользователь добавлен.');
            }
        }
        if ($act === 'toggle' || $act === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) $admin['id']) {
                flash('Нельзя изменить собственную учётную запись здесь.', 'err');
            } elseif ($act === 'delete') {
                $data['users'] = array_values(array_filter($data['users'], fn($u) => (int) $u['id'] !== $id));
                save_admin_users($data);
                flash('Пользователь удалён.');
            } else {
                foreach ($data['users'] as $i => $u) {
                    if ((int) $u['id'] === $id) {
                        $data['users'][$i]['active'] = (int) ($u['active'] ?? 1) === 1 ? 0 : 1;
                    }
                }
                save_admin_users($data);
            }
        }
        if ($act === 'reset') {
            $id = (int) ($_POST['id'] ?? 0);
            $pass = (string) ($_POST['password'] ?? '');
            if (strlen($pass) < 8) {
                flash('Пароль должен быть не короче 8 символов.', 'err');
            } else {
                foreach ($data['users'] as $i => $u) {
                    if ((int) $u['id'] === $id) {
                        $data['users'][$i]['pass_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                    }
                }
                save_admin_users($data);
                flash('Пароль изменён.');
            }
        }
        redirect('index.php?section=users');
    }
}

/* ====================================================================
 * Rendering
 * ================================================================= */
layout_top($section, SECTIONS[$section][0]);

/** Language tabs for content sections. */
function lang_tabs(string $section, string $active): void
{
    echo '<div class="tabs">';
    foreach (edit_langs() as $code => $label) {
        $cls = $code === $active ? ' class="on"' : '';
        echo '<a' . $cls . ' href="index.php?section=' . e($section) . '&lang=' . e($code) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}

/** One text field bound to content.<lang>.<path>. */
function field(string $path, string $label, string $lang, string $type = 'text', string $hint = ''): void
{
    $id = 'f_' . str_replace('.', '_', $path);
    $v = (string) (cfg('content.' . $lang . '.' . $path) ?? '');
    echo '<label for="' . e($id) . '">' . e($label) . '</label>';
    if ($type === 'textarea') {
        echo '<textarea id="' . e($id) . '" name="f[' . e($path) . ']">' . e($v) . '</textarea>';
    } elseif ($type === 'html') {
        echo '<textarea id="' . e($id) . '" class="tall" name="f[' . e($path) . ']">' . e($v) . '</textarea>';
    } else {
        echo '<input id="' . e($id) . '" type="text" name="f[' . e($path) . ']" value="' . e($v) . '">';
    }
    if ($hint !== '') {
        echo '<p class="muted" style="margin:6px 0 0">' . e($hint) . '</p>';
    }
}

/* ---------------------------------------------------------------- overview */
if ($section === 'overview') {
    $cars = cars_load();
    $pub = count(array_filter($cars, fn($c) => !empty($c['published'])));
    $msgs = messages_load();
    $new = count(array_filter($msgs, fn($m) => ($m['status'] ?? 'new') === 'new'));
    $photos = array_sum(array_map(fn($c) => count($c['images'] ?? []), $cars));
    ?>
    <div class="cards">
        <div class="card">
            <div class="n"><?= $pub ?></div>
            <div class="l">Автомобилей на сайте</div>
        </div>
        <div class="card">
            <div class="n"><?= count($cars) - $pub ?></div>
            <div class="l">Скрыто</div>
        </div>
        <div class="card">
            <div class="n"><?= $photos ?></div>
            <div class="l">Фотографий</div>
        </div>
        <div class="card">
            <div class="n"><?= $new ?></div>
            <div class="l">Новых заявок</div>
        </div>
    </div>

    <div class="panel">
        <h2>Последние заявки</h2>
        <p class="hint">Полный список — в разделе «Заявки с сайта».</p>
        <?php if (!$msgs): ?>
            <p class="muted">Заявок пока нет.</p>
        <?php else: ?>
            <div class="tablewrap">
                <table>
                    <tr>
                        <th>Дата</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Автомобиль</th>
                        <th></th>
                    </tr>
                    <?php foreach (array_slice($msgs, 0, 6) as $m): ?>
                        <tr>
                            <td class="muted"><?= e($m['date'] ?? '') ?></td>
                            <td><b><?= e($m['name'] ?? '') ?></b></td>
                            <td><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $m['phone'] ?? '')) ?>"><?= e($m['phone'] ?? '') ?></a></td>
                            <td class="muted"><?= e($m['car'] ?? '—') ?></td>
                            <td class="right"><?php if (($m['status'] ?? 'new') === 'new'): ?><span class="badge new">новая</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Быстрые действия</h2>
        <p class="hint">Частые операции — в один клик.</p>
        <div style="display:flex;gap:9px;flex-wrap:wrap">
            <a class="btn gold" href="index.php?section=cars&edit=new">Добавить автомобиль</a>
            <a class="btn ghost" href="index.php?section=home">Редактировать главную</a>
            <a class="btn ghost" href="index.php?section=contacts">Контакты</a>
            <a class="btn ghost" href="index.php?section=submissions">Заявки<?= $new ? ' (' . $new . ')' : '' ?></a>
        </div>
    </div>
<?php
}

/* -------------------------------------------------------------------- cars */
elseif ($section === 'cars') {
    $cars = cars_load();
    $edit = isset($_GET['edit']) ? (string) $_GET['edit'] : null;

    if ($edit !== null) {
        $car = null;
        foreach ($cars as $c) {
            if ($c['slug'] === $edit) {
                $car = $c;
            }
        }
        $isNew = $car === null;
        if ($isNew) {
            $car = ['slug' => '', 'title' => [], 'brand' => '', 'body' => 'sedan', 'year' => '', 'engine' => '',
                'fuel' => 'benzin', 'gearbox' => 'avtomat', 'seats' => '', 'color' => '', 'published' => true,
                'images' => [], 'cover' => 1];
        }
        ?>
        <p style="margin:0 0 16px"><a class="btn ghost sm" href="index.php?section=cars">← К списку</a></p>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="orig_slug" value="<?= e($car['slug']) ?>">

            <div class="panel">
                <h2><?= $isNew ? 'Новый автомобиль' : 'Редактирование' ?></h2>
                <p class="hint">Название задаётся отдельно для каждого языка. Пустые поля заполнятся названием на языке по умолчанию.</p>
                <?php foreach ($ELANGS as $code => $label): ?>
                    <label for="t_<?= e($code) ?>">Название — <?= e($label) ?></label>
                    <input id="t_<?= e($code) ?>" type="text" name="title[<?= e($code) ?>]" value="<?= e($car['title'][$code] ?? '') ?>">
                <?php endforeach; ?>

                <label for="c_slug">Адрес страницы (slug)</label>
                <input id="c_slug" type="text" name="slug" value="<?= e($car['slug']) ?>" placeholder="toyota-camry-2022">
                <p class="muted" style="margin:6px 0 0">Если оставить пустым — создастся автоматически из названия.</p>

                <div class="chkline">
                    <input id="c_pub" type="checkbox" name="published" value="1" <?= !empty($car['published']) ? 'checked' : '' ?>>
                    <label for="c_pub" style="margin:0">Показывать на сайте</label>
                </div>
            </div>

            <div class="panel">
                <h2>Характеристики</h2>
                <p class="hint">Значения из выпадающих списков переводятся на сайте автоматически.</p>
                <div class="row3">
                    <div>
                        <label for="c_brand">Марка</label>
                        <input id="c_brand" type="text" name="brand" value="<?= e($car['brand']) ?>" placeholder="Toyota">
                    </div>
                    <div>
                        <label for="c_body">Кузов</label>
                        <select id="c_body" name="body">
                            <option value="sedan" <?= $car['body'] === 'sedan' ? 'selected' : '' ?>>Седан</option>
                            <option value="suv" <?= $car['body'] === 'suv' ? 'selected' : '' ?>>Внедорожник (SUV)</option>
                        </select>
                    </div>
                    <div>
                        <label for="c_year">Год</label>
                        <input id="c_year" type="text" name="year" value="<?= e($car['year']) ?>" placeholder="2022">
                    </div>
                </div>
                <div class="row3">
                    <div>
                        <label for="c_engine">Двигатель</label>
                        <input id="c_engine" type="text" name="engine" value="<?= e($car['engine']) ?>" placeholder="2.5 L">
                    </div>
                    <div>
                        <label for="c_fuel">Топливо</label>
                        <select id="c_fuel" name="fuel">
                            <?php foreach (['benzin' => 'Бензин', 'hybrid' => 'Гибрид', 'dizel' => 'Дизель', 'elektro' => 'Электро'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $car['fuel'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="c_gear">Коробка</label>
                        <select id="c_gear" name="gearbox">
                            <option value="avtomat" <?= $car['gearbox'] === 'avtomat' ? 'selected' : '' ?>>Автомат</option>
                            <option value="mexanika" <?= $car['gearbox'] === 'mexanika' ? 'selected' : '' ?>>Механика</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label for="c_seats">Количество мест</label>
                        <input id="c_seats" type="text" name="seats" value="<?= e($car['seats']) ?>" placeholder="4">
                    </div>
                    <div>
                        <label for="c_color">Цвет</label>
                        <select id="c_color" name="color">
                            <option value="">— не указан —</option>
                            <?php foreach (['white' => 'Белый', 'black' => 'Чёрный', 'grey' => 'Серый', 'silver' => 'Серебристый', 'blue' => 'Синий', 'burgundy' => 'Бордовый', 'red' => 'Красный'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= ($car['color'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Фотографии</h2>
                <p class="hint">Снимите галочку, чтобы удалить фото при сохранении. Отмеченное как «обложка» показывается в каталоге.</p>
                <?php if ($car['images']): ?>
                    <div class="thumbs">
                        <?php foreach ($car['images'] as $i => $p): $n = $i + 1; ?>
                            <div class="gitem<?= (int) ($car['cover'] ?? 1) === $n ? ' cover' : '' ?>">
                                <img src="<?= e(img($p)) ?>" alt="">
                                <div class="nm"><?= e(basename($p)) ?></div>
                                <label style="margin:0 0 5px;font-weight:500;font-size:12px;display:flex;gap:6px;align-items:center;justify-content:center">
                                    <input type="checkbox" name="keep[]" value="<?= e($p) ?>" checked style="width:auto"> оставить
                                </label>
                                <label style="margin:0;font-weight:500;font-size:12px;display:flex;gap:6px;align-items:center;justify-content:center">
                                    <input type="radio" name="cover" value="<?= $n ?>" <?= (int) ($car['cover'] ?? 1) === $n ? 'checked' : '' ?> style="width:auto"> обложка
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted">Фотографий пока нет.</p>
                    <input type="hidden" name="cover" value="1">
                <?php endif; ?>

                <label for="c_photos" class="mt">Добавить фотографии</label>
                <input id="c_photos" type="file" name="photos[]" accept="image/*" multiple>
            </div>

            <div style="display:flex;gap:9px;flex-wrap:wrap">
                <button class="btn gold">Сохранить</button>
                <a class="btn ghost" href="index.php?section=cars">Отмена</a>
                <?php if (!$isNew): ?>
                    <a class="btn ghost" href="<?= e(url('car', null, $car['slug'])) ?>" target="_blank" rel="noopener">Открыть на сайте</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$isNew): ?>
            <form method="post" class="mt" onsubmit="return confirm('Удалить автомобиль без возможности восстановления?')">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="slug" value="<?= e($car['slug']) ?>">
                <button class="btn red sm">Удалить автомобиль</button>
            </form>
        <?php endif; ?>
    <?php
    } else { ?>
        <div class="panel">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                <div>
                    <h2 style="margin:0">Автопарк</h2>
                    <p class="hint" style="margin:3px 0 0">Порядок в списке = порядок на сайте.</p>
                </div>
                <a class="btn gold" href="index.php?section=cars&edit=new">+ Добавить</a>
            </div>

            <?php if (!$cars): ?>
                <p class="muted">Пока ни одного автомобиля.</p>
            <?php else: ?>
                <div class="tablewrap">
                    <table>
                        <tr>
                            <th style="width:78px"></th>
                            <th>Название</th>
                            <th>Марка</th>
                            <th>Кузов</th>
                            <th>Год</th>
                            <th>Фото</th>
                            <th>Статус</th>
                            <th class="right">Порядок</th>
                            <th></th>
                        </tr>
                        <?php foreach ($cars as $c): ?>
                            <tr>
                                <td><img src="<?= e(img(ltrim(car_cover($c), 'img/'))) ?>" alt="" style="width:66px;height:44px;object-fit:cover;border-radius:6px"></td>
                                <td><b><?= e($c['title'][cfg('default_lang', 'en')] ?? $c['slug']) ?></b><br><span class="muted"><?= e($c['slug']) ?></span></td>
                                <td><?= e($c['brand'] ?? '') ?></td>
                                <td><?= e(($c['body'] ?? '') === 'suv' ? 'SUV' : 'Седан') ?></td>
                                <td><?= e($c['year'] ?? '') ?></td>
                                <td><?= count($c['images'] ?? []) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="act" value="toggle">
                                        <input type="hidden" name="slug" value="<?= e($c['slug']) ?>">
                                        <button class="badge <?= !empty($c['published']) ? 'on' : 'off' ?>" style="border:0;cursor:pointer" title="Переключить">
                                            <?= !empty($c['published']) ? 'на сайте' : 'скрыт' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="right" style="white-space:nowrap">
                                    <form method="post" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="act" value="move">
                                        <input type="hidden" name="slug" value="<?= e($c['slug']) ?>">
                                        <button class="sortbtn" name="dir" value="up" title="Выше">▲</button>
                                        <button class="sortbtn" name="dir" value="down" title="Ниже">▼</button>
                                    </form>
                                </td>
                                <td class="right"><a class="btn ghost sm" href="index.php?section=cars&edit=<?= e(rawurlencode($c['slug'])) ?>">Изменить</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php }
}

/* -------------------------------------------------------------------- home */
elseif ($section === 'home') {
    lang_tabs('home', $el);
    ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">
        <input type="hidden" name="lang" value="<?= e($el) ?>">

        <div class="panel">
            <h2>Первый экран</h2>
            <p class="hint">Заголовок разделён на две части: вторая выводится золотым цветом.</p>
            <?php
            field('home.eyebrow', 'Надпись над заголовком', $el);
            echo '<div class="row"><div>';
            field('home.title', 'Заголовок — первая часть', $el);
            echo '</div><div>';
            field('home.title_accent', 'Заголовок — акцент (золотой)', $el);
            echo '</div></div>';
            field('home.text', 'Текст под заголовком', $el, 'textarea');
            ?>
        </div>

        <div class="panel">
            <h2>Показатели</h2>
            <p class="hint">Четыре пары «значение / подпись» под первым экраном. Пустое значение скрывает пару.</p>
            <?php for ($n = 1; $n <= 4; $n++): ?>
                <div class="row">
                    <div><?php field("home.stat{$n}_v", "Показатель {$n} — значение", $el); ?></div>
                    <div><?php field("home.stat{$n}_l", "Показатель {$n} — подпись", $el); ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="panel">
            <h2>Преимущества</h2>
            <p class="hint">Четыре плитки. Пустой заголовок скрывает плитку.</p>
            <?php for ($n = 1; $n <= 4; $n++): ?>
                <?php field("home.f{$n}_title", "Плитка {$n} — заголовок", $el); ?>
                <?php field("home.f{$n}_text", "Плитка {$n} — текст", $el, 'textarea'); ?>
            <?php endfor; ?>
        </div>

        <div class="panel">
            <h2>Блок «О нас» и призыв к действию</h2>
            <p class="hint">Сам текст «О нас» редактируется в разделе «Тексты страниц».</p>
            <?php
            field('home.about_title', 'Заголовок блока «О нас»', $el);
            field('home.cta_title', 'Заголовок призыва', $el);
            field('home.cta_text', 'Текст призыва', $el, 'textarea');
            ?>
        </div>

        <button class="btn gold">Сохранить</button>
    </form>
<?php
}

/* ------------------------------------------------------------------- pages */
elseif ($section === 'pages') {
    lang_tabs('pages', $el);
    ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">
        <input type="hidden" name="lang" value="<?= e($el) ?>">

        <div class="panel">
            <h2>Страница «О нас»</h2>
            <?php
            field('about.title', 'Заголовок', $el);
            field('about.lead', 'Подзаголовок', $el);
            field('about.text', 'Основной текст (можно использовать теги &lt;p&gt;)', $el, 'html');
            ?>
        </div>

        <div class="panel">
            <h2>Страница «Автомобили»</h2>
            <?php
            field('cars.title', 'Заголовок', $el);
            field('cars.lead', 'Подзаголовок', $el, 'textarea');
            ?>
        </div>

        <div class="panel">
            <h2>Страница «Контакты»</h2>
            <?php
            field('contact.title', 'Заголовок', $el);
            field('contact.lead', 'Подзаголовок', $el, 'textarea');
            ?>
        </div>

        <button class="btn gold">Сохранить</button>
    </form>

    <form method="post" class="mt">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save_footer">
        <input type="hidden" name="lang" value="<?= e($el) ?>">
        <div class="panel">
            <h2>Подвал сайта</h2>
            <p class="hint">Короткое описание компании рядом с логотипом.</p>
            <label for="ft">Текст в подвале</label>
            <textarea id="ft" name="footer_text"><?= e((string) (cfg('content.' . $el . '.footer_text') ?? '')) ?></textarea>
        </div>
        <button class="btn gold">Сохранить подвал</button>
    </form>
<?php
}

/* --------------------------------------------------------------------- seo */
elseif ($section === 'seo') {
    lang_tabs('seo', $el);
    $titles = ['home' => 'Главная', 'cars' => 'Автомобили', 'about' => 'О нас', 'contact' => 'Контакты'];
    ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">
        <input type="hidden" name="lang" value="<?= e($el) ?>">
        <?php foreach ($titles as $key => $label): ?>
            <div class="panel">
                <h2><?= e($label) ?></h2>
                <p class="hint">Title — до 60 знаков, description — до 160.</p>
                <?php
                field($key . '.seo_title', 'Title', $el);
                field($key . '.seo_desc', 'Description', $el, 'textarea');
                ?>
            </div>
        <?php endforeach; ?>
        <button class="btn gold">Сохранить</button>
    </form>

    <div class="panel">
        <h2>Страницы автомобилей</h2>
        <p class="hint">Title и description для карточек формируются автоматически из названия и характеристик автомобиля.</p>
    </div>
<?php
}

/* ---------------------------------------------------------------- contacts */
elseif ($section === 'contacts') {
    ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">

        <div class="panel">
            <h2>Телефоны и почта</h2>
            <p class="hint">Отображаются в шапке, подвале и на странице контактов.</p>
            <div class="row">
                <div>
                    <label for="p1">Телефон 1</label>
                    <input id="p1" type="text" name="phone1" value="<?= e(val('contacts.phone1')) ?>">
                </div>
                <div>
                    <label for="p2">Телефон 2</label>
                    <input id="p2" type="text" name="phone2" value="<?= e(val('contacts.phone2')) ?>">
                </div>
            </div>
            <label for="em">Email</label>
            <input id="em" type="email" name="email" value="<?= e(val('contacts.email')) ?>">
        </div>

        <div class="panel">
            <h2>WhatsApp</h2>
            <p class="hint">Только цифры, с кодом страны: 994556080806.</p>
            <div class="row">
                <div>
                    <label for="w1">WhatsApp (основной)</label>
                    <input id="w1" type="text" name="whatsapp" value="<?= e(val('contacts.whatsapp')) ?>">
                </div>
                <div>
                    <label for="w2">WhatsApp (второй)</label>
                    <input id="w2" type="text" name="whatsapp2" value="<?= e(val('contacts.whatsapp2')) ?>">
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Социальные сети</h2>
            <p class="hint">Пустое поле — иконка не показывается.</p>
            <label for="ig">Instagram</label>
            <input id="ig" type="url" name="instagram" value="<?= e(val('contacts.instagram')) ?>" placeholder="https://instagram.com/...">
            <label for="tt">TikTok</label>
            <input id="tt" type="url" name="tiktok" value="<?= e(val('contacts.tiktok')) ?>">
            <label for="fb">Facebook</label>
            <input id="fb" type="url" name="facebook" value="<?= e(val('contacts.facebook')) ?>" placeholder="https://facebook.com/...">
        </div>

        <div class="panel">
            <h2>Карта</h2>
            <p class="hint">Поисковый запрос для карты на странице контактов.</p>
            <label for="mq">Запрос на карте</label>
            <input id="mq" type="text" name="map_query" value="<?= e(val('contacts.map_query', 'baku')) ?>" placeholder="baku">
        </div>

        <button class="btn gold">Сохранить</button>
    </form>
<?php
}

/* ---------------------------------------------------------------- settings */
elseif ($section === 'settings') {
    ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">

        <div class="panel">
            <h2>Общие</h2>
            <div class="row">
                <div>
                    <label for="s_brand">Название компании</label>
                    <input id="s_brand" type="text" name="brand" value="<?= e(val('settings.brand', 'Carclub')) ?>">
                </div>
                <div>
                    <label for="s_mail">Email для уведомлений о заявках</label>
                    <input id="s_mail" type="email" name="notify_email" value="<?= e(val('settings.notify_email')) ?>">
                </div>
            </div>
            <div class="row">
                <div>
                    <label for="s_cars">Сколько автомобилей показывать на главной</label>
                    <input id="s_cars" type="number" name="home_cars" min="1" max="24" value="<?= e(val('settings.home_cars', '6')) ?>">
                </div>
                <div>
                    <label for="s_ga">Google Analytics ID</label>
                    <input id="s_ga" type="text" name="ga_id" value="<?= e(val('settings.ga_id')) ?>" placeholder="G-XXXXXXX">
                </div>
            </div>
            <label for="s_host">Адрес сайта (для canonical и Open Graph)</label>
            <input id="s_host" type="url" name="host" value="<?= e(val('settings.host')) ?>" placeholder="https://carclub.az">
            <p class="muted" style="margin:6px 0 0">Если оставить пустым — определяется автоматически из запроса.</p>
        </div>

        <?php foreach ([
            'hero_image'  => ['Фон первого экрана', 'Широкое фото, желательно от 1600 px.'],
            'about_image' => ['Фото в блоке «О нас»', 'Соотношение примерно 4:3.'],
            'og_image'    => ['Картинка для соцсетей (Open Graph)', 'Показывается при отправке ссылки в мессенджеры.'],
        ] as $key => [$label, $hint]): $cur = val('settings.' . $key); ?>
            <div class="panel">
                <h2><?= e($label) ?></h2>
                <p class="hint"><?= e($hint) ?></p>
                <?php if ($cur !== ''): ?>
                    <img src="<?= e(img(ltrim($cur, 'img/'))) ?>" alt="" style="max-height:150px;width:auto;border-radius:10px;margin-bottom:10px">
                <?php endif; ?>
                <label for="<?= e($key) ?>_file">Загрузить новое</label>
                <input id="<?= e($key) ?>_file" type="file" name="<?= e($key) ?>_file" accept="image/*">
                <label for="<?= e($key) ?>">Или укажите путь</label>
                <input id="<?= e($key) ?>" type="text" name="<?= e($key) ?>" value="<?= e($cur) ?>">
            </div>
        <?php endforeach; ?>

        <button class="btn gold">Сохранить</button>
    </form>
<?php
}

/* ------------------------------------------------------------------ images */
elseif ($section === 'images') {
    $dir = ROOT . '/assets/img/uploads';
    $files = is_dir($dir) ? array_values(array_diff(scandir($dir) ?: [], ['.', '..'])) : [];
    rsort($files);
    ?>
    <div class="panel">
        <h2>Загрузить изображения</h2>
        <p class="hint">JPG, PNG, WebP, GIF или SVG. Загруженные файлы можно использовать в настройках и в карточках автомобилей.</p>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="upload">
            <input type="file" name="files[]" accept="image/*" multiple required>
            <div class="mt"><button class="btn gold">Загрузить</button></div>
        </form>
    </div>

    <div class="panel">
        <h2>Загруженные файлы<?= $files ? ' (' . count($files) . ')' : '' ?></h2>
        <?php if (!$files): ?>
            <p class="muted">Пока ничего не загружено.</p>
        <?php else: ?>
            <div class="thumbs">
                <?php foreach ($files as $f): ?>
                    <div class="gitem">
                        <img src="<?= e(img('uploads/' . $f)) ?>" alt="">
                        <div class="nm">img/uploads/<?= e($f) ?></div>
                        <form method="post" onsubmit="return confirm('Удалить файл?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="act" value="delete">
                            <input type="hidden" name="file" value="<?= e($f) ?>">
                            <button class="btn red sm">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}

/* ------------------------------------------------------------- submissions */
elseif ($section === 'submissions') {
    $msgs = messages_load();
    $new = count(array_filter($msgs, fn($m) => ($m['status'] ?? 'new') === 'new'));
    ?>
    <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
            <div>
                <h2 style="margin:0">Заявки<?= $msgs ? ' — всего ' . count($msgs) : '' ?></h2>
                <p class="hint" style="margin:3px 0 0"><?= $new ?> новых</p>
            </div>
            <?php if ($msgs): ?>
                <form method="post" onsubmit="return confirm('Удалить все прочитанные заявки?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="act" value="clear_read">
                    <button class="btn ghost sm">Очистить прочитанные</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!$msgs): ?>
            <p class="muted">Заявок пока нет.</p>
        <?php else: ?>
            <?php foreach ($msgs as $m): $isNew = ($m['status'] ?? 'new') === 'new'; ?>
                <div style="border:1px solid <?= $isNew ? '#d4ad00' : '#e7e5e4' ?>;border-radius:12px;padding:15px 17px;margin-bottom:11px;background:<?= $isNew ? '#fffdf3' : '#fff' ?>">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline">
                        <div>
                            <b style="font-size:15.5px"><?= e($m['name'] ?? '') ?></b>
                            <span class="badge <?= $isNew ? 'new' : 'read' ?>" style="margin-left:8px"><?= $isNew ? 'новая' : 'прочитана' ?></span>
                        </div>
                        <span class="muted"><?= e($m['date'] ?? '') ?> · <?= e(strtoupper($m['lang'] ?? '')) ?></span>
                    </div>
                    <p style="margin:9px 0 0;font-size:14px">
                        <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $m['phone'] ?? '')) ?>"><?= e($m['phone'] ?? '') ?></a>
                        <?php if (!empty($m['email'])): ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?php endif; ?>
                        <?php if (!empty($m['car'])): ?> · <span class="muted">авто:</span> <b><?= e($m['car']) ?></b><?php endif; ?>
                    </p>
                    <?php if (!empty($m['message'])): ?>
                        <p style="margin:9px 0 0;white-space:pre-wrap;font-size:14px;color:#44403c"><?= e($m['message']) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;gap:7px;margin-top:12px;flex-wrap:wrap">
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="act" value="<?= $isNew ? 'read' : 'unread' ?>">
                            <input type="hidden" name="id" value="<?= e($m['id'] ?? '') ?>">
                            <button class="btn ghost sm"><?= $isNew ? 'Отметить прочитанной' : 'Вернуть в новые' ?></button>
                        </form>
                        <?php if (!empty($m['phone']) && cfg('contacts.whatsapp', '')): ?>
                            <a class="btn ghost sm" href="<?= e(wa_href(preg_replace('/\D/', '', $m['phone']))) ?>" target="_blank" rel="noopener">Ответить в WhatsApp</a>
                        <?php endif; ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Удалить заявку?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="act" value="delete">
                            <input type="hidden" name="id" value="<?= e($m['id'] ?? '') ?>">
                            <button class="btn red sm">Удалить</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php
}

/* ----------------------------------------------------------------- profile */
elseif ($section === 'profile') {
    $me = null;
    foreach (admin_users()['users'] as $u) {
        if ((int) $u['id'] === (int) $admin['id']) {
            $me = $u;
        }
    }
    ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="save">
        <div class="panel">
            <h2>Мой профиль</h2>
            <p class="hint">Роль: <?= e(($me['role'] ?? '') === 'admin' ? 'администратор' : 'редактор') ?><?php if (!empty($me['last_login'])): ?> · последний вход <?= e($me['last_login']) ?><?php endif; ?></p>
            <div class="row">
                <div>
                    <label for="pf_name">Имя</label>
                    <input id="pf_name" type="text" name="name" value="<?= e($me['name'] ?? '') ?>">
                </div>
                <div>
                    <label for="pf_mail">Email (логин)</label>
                    <input id="pf_mail" type="email" name="email" value="<?= e($me['email'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="panel">
            <h2>Смена пароля</h2>
            <p class="hint">Заполните, только если хотите изменить пароль. Минимум 8 символов.</p>
            <label for="pf_cur">Текущий пароль</label>
            <input id="pf_cur" type="password" name="current" autocomplete="current-password">
            <label for="pf_new">Новый пароль</label>
            <input id="pf_new" type="password" name="password" autocomplete="new-password">
        </div>
        <button class="btn gold">Сохранить</button>
    </form>
<?php
}

/* ------------------------------------------------------------------- users */
elseif ($section === 'users') {
    $data = admin_users();
    ?>
    <div class="panel">
        <h2>Пользователи панели</h2>
        <p class="hint">Администратор видит все разделы. Редактор — всё, кроме «Пользователей» и «Настроек».</p>
        <div class="tablewrap">
            <table>
                <tr>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Последний вход</th>
                    <th>Статус</th>
                    <th class="right">Действия</th>
                </tr>
                <?php foreach ($data['users'] as $u): $self = (int) $u['id'] === (int) $admin['id']; ?>
                    <tr>
                        <td><b><?= e($u['name'] ?? '') ?></b><?= $self ? ' <span class="muted">(это вы)</span>' : '' ?></td>
                        <td><?= e($u['email'] ?? '') ?></td>
                        <td><?= e(($u['role'] ?? '') === 'admin' ? 'администратор' : 'редактор') ?></td>
                        <td class="muted"><?= e($u['last_login'] ?? '—') ?></td>
                        <td><span class="badge <?= (int) ($u['active'] ?? 1) === 1 ? 'on' : 'off' ?>"><?= (int) ($u['active'] ?? 1) === 1 ? 'активен' : 'выключен' ?></span></td>
                        <td class="right" style="white-space:nowrap">
                            <?php if (!$self): ?>
                                <form method="post" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="act" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button class="btn ghost sm"><?= (int) ($u['active'] ?? 1) === 1 ? 'Выключить' : 'Включить' ?></button>
                                </form>
                                <form method="post" style="display:inline" onsubmit="return confirm('Удалить пользователя?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="act" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button class="btn red sm">Удалить</button>
                                </form>
                            <?php else: ?>
                                <a class="btn ghost sm" href="index.php?section=profile">Профиль</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="add">
        <div class="panel">
            <h2>Добавить пользователя</h2>
            <div class="row3">
                <div>
                    <label for="u_name">Имя</label>
                    <input id="u_name" type="text" name="name">
                </div>
                <div>
                    <label for="u_mail">Email</label>
                    <input id="u_mail" type="email" name="email" required>
                </div>
                <div>
                    <label for="u_role">Роль</label>
                    <select id="u_role" name="role">
                        <option value="editor">Редактор</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
            </div>
            <label for="u_pass">Пароль (минимум 8 символов)</label>
            <input id="u_pass" type="password" name="password" autocomplete="new-password" required>
        </div>
        <button class="btn gold">Добавить</button>
    </form>

    <form method="post" class="mt">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="reset">
        <div class="panel">
            <h2>Сбросить пароль пользователю</h2>
            <div class="row">
                <div>
                    <label for="r_id">Пользователь</label>
                    <select id="r_id" name="id">
                        <?php foreach ($data['users'] as $u): ?>
                            <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                                <option value="<?= (int) $u['id'] ?>"><?= e($u['email'] ?? '') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="r_pass">Новый пароль</label>
                    <input id="r_pass" type="password" name="password" autocomplete="new-password">
                </div>
            </div>
        </div>
        <button class="btn gold">Сбросить пароль</button>
    </form>
<?php
}

layout_bottom();
