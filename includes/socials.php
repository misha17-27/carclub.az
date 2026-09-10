<?php
/** Social icon row — rendered in the topbar, the drawer and the footer. */
$links = [
    'whatsapp'  => cfg('contacts.whatsapp', '') ? wa_href() : '',
    'instagram' => cfg('contacts.instagram', ''),
    'tiktok'    => cfg('contacts.tiktok', ''),
    'facebook'  => cfg('contacts.facebook', ''),
];
$links = array_filter($links);
if (!$links) {
    return;
}
?>
<div class="socials">
    <?php foreach ($links as $name => $href): ?>
        <a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($name)) ?>"><?= icon($name) ?></a>
    <?php endforeach; ?>
</div>
