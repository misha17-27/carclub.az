<?php
/**
 * Request form.
 * Optional vars set by the caller:
 *   $FORM_ID    unique prefix for field ids (default "f")
 *   $FORM_CAR   pre-filled car name
 *   $FORM_TITLE / $FORM_SUB headings
 */
$fid   = $FORM_ID ?? 'f';
$fcar  = $FORM_CAR ?? '';
$sent  = $GLOBALS['FORM_RESULT'] ?? null;   // [bool ok, string messageKey]

/* When a WhatsApp number is set, the send button hands the filled-in request
   straight to WhatsApp. Without JavaScript the form still posts normally. */
$waNumber = cfg('settings.form_to_whatsapp', true) ? preg_replace('/\D/', '', (string) cfg('contacts.whatsapp', '')) : '';
$waLabels = json_encode([
    'intro'   => t('form.wa_intro') . ' — ' . cfg('settings.brand', 'Carclub'),
    'name'    => t('form.name'),
    'phone'   => t('form.phone'),
    'email'   => t('form.email'),
    'car'     => t('form.car'),
    'message' => t('form.message'),
], JSON_UNESCAPED_UNICODE);
?>
<div class="form-card" id="request-<?= e($fid) ?>">
    <h2 class="form-card__title"><?= e($FORM_TITLE ?? t('form.title')) ?></h2>
    <p class="form-card__sub"><?= e($FORM_SUB ?? t('form.sub')) ?></p>

    <?php if ($sent): ?>
        <p class="form-msg form-msg--<?= $sent[0] ? 'ok' : 'err' ?>" role="status"><?= e(t($sent[1])) ?></p>
    <?php endif; ?>

    <?php if (!$sent || !$sent[0]): ?>
        <form class="form" method="post" action="<?= e(current_url()) ?>#request-<?= e($fid) ?>" data-validate novalidate
            <?php if ($waNumber !== ''): ?>
            data-wa="<?= e($waNumber) ?>"
            data-wa-labels="<?= e($waLabels) ?>"
            data-wa-sent="<?= e(t('form.wa_sent')) ?>"
            <?php endif; ?>>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="ts" value="<?= e(form_stamp()) ?>">
            <input type="hidden" name="page" value="<?= e(current_url()) ?>">
            <?php if ($fcar !== ''): ?>
                <input type="hidden" name="car" value="<?= e($fcar) ?>">
            <?php endif; ?>
            <div class="sr-only" aria-hidden="true">
                <label for="<?= e($fid) ?>-website">Website</label>
                <input id="<?= e($fid) ?>-website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="field--row">
                <div class="field">
                    <label for="<?= e($fid) ?>-name"><?= e(t('form.name')) ?></label>
                    <input id="<?= e($fid) ?>-name" name="name" type="text" autocomplete="name"
                        placeholder="<?= e(t('form.ph_name')) ?>" value="<?= e($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="<?= e($fid) ?>-phone"><?= e(t('form.phone')) ?></label>
                    <input id="<?= e($fid) ?>-phone" name="phone" type="tel" autocomplete="tel"
                        placeholder="+994 __ ___ __ __" value="<?= e($_POST['phone'] ?? '') ?>" required>
                </div>
            </div>
            <div class="field">
                <label for="<?= e($fid) ?>-email"><?= e(t('form.email')) ?></label>
                <input id="<?= e($fid) ?>-email" name="email" type="email" autocomplete="email"
                    placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="<?= e($fid) ?>-msg"><?= e(t('form.message')) ?></label>
                <textarea id="<?= e($fid) ?>-msg" name="message" placeholder="<?= e(t('form.ph_msg')) ?>"><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button class="btn btn--block btn--lg" type="submit">
                <?php if ($waNumber !== ''): ?><?= icon('whatsapp') ?><?php endif; ?>
                <?= e($waNumber !== '' ? t('form.send_wa') : t('form.send')) ?>
            </button>
            <p class="form__note"><?= e(t('form.note')) ?></p>
        </form>
    <?php endif; ?>
</div>
