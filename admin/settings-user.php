<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$config = load_config();
$fontStack = font_stack_css($config['theme']['admin_font_stack'] ?? 'sans');

$errors = [];
$notice = isset($_GET['saved']) ? t('admin.settings.user.notice_updated') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_action_id'])) {
    verify_csrf();
    $adminUsername = trim($_POST['admin_username'] ?? '');
    $newAdminPath = trim($_POST['admin_path'] ?? '');
    $newAdminPath = trim($newAdminPath, '/');
    $newAdminPath = preg_replace('/[^a-z0-9_-]/', '', strtolower($newAdminPath)) ?? '';
    if ($newAdminPath === 'admin') {
        $newAdminPath = '';
    }
    $passwordCurrent = $_POST['current_password'] ?? '';
    $passwordNew = $_POST['new_password'] ?? '';
    $passwordConfirm = $_POST['confirm_password'] ?? '';

    if ($adminUsername === '') {
        $errors[] = t('admin.settings.user.error_username');
    }

    if (($passwordNew !== '' || $passwordConfirm !== '') && $passwordNew !== $passwordConfirm) {
        $errors[] = t('admin.settings.user.error_password_match');
    }

    if ($passwordNew !== '' && !password_verify($passwordCurrent, $config['admin_password_hash'] ?? '')) {
        $errors[] = t('admin.settings.user.error_password_wrong');
    }

    if (!$errors) {
        $config['admin_username'] = $adminUsername;
        $oldAdminPath = custom_admin_path();
        $config['admin_path'] = $newAdminPath;

        if ($passwordNew !== '') {
            $config['admin_password_hash'] = password_hash($passwordNew, PASSWORD_DEFAULT);
        }

        if (save_config($config)) {
            if ($newAdminPath !== $oldAdminPath) {
                $_SESSION['admin_path_verified'] = true;
                $newBase = $newAdminPath !== '' ? $newAdminPath : 'admin';
                header('Location: ' . base_path() . '/' . $newBase . '/settings-user.php?saved=1');
                exit;
            }
            $notice = t('admin.settings.user.notice_updated');
        } else {
            $errors[] = t('admin.settings.user.error_save');
        }
    }
}

$adminTitle = t('admin.settings.user.page_title');
require __DIR__ . '/../includes/admin-head.php';
?>
    <main class="mid">
        <h1><?= e(t('admin.settings.user.heading')) ?></h1>
        <?php require __DIR__ . '/../includes/admin-notices.php'; ?>

        <nav class="admin-actions">
            <button class="save" type="submit" form="settings-form" aria-label="<?= e(t('admin.settings.nav.save')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#icon-save"></use></svg>
                <?= e(t('admin.settings.nav.save')) ?>
            </button>
        </nav>

        <form method="post" id="settings-form">
            <?= csrf_field() ?>
            <section class="section-divider">
                <span class="title"><?= e(t('admin.settings.user.section_account')) ?></span>
                <label for="admin_username"><?= e(t('admin.settings.user.username')) ?></label>
                <input type="text" id="admin_username" name="admin_username" value="<?= e($config['admin_username'] ?? '') ?>" required>

                <label for="admin_path"><?= e(t('admin.settings.user.admin_path')) ?></label>
                <input
                    type="text"
                    id="admin_path"
                    name="admin_path"
                    value="<?= e((string) ($config['admin_path'] ?? '')) ?>"
                    pattern="[a-z0-9_-]*"
                    maxlength="60"
                    placeholder="<?= e(t('admin.settings.user.admin_path_placeholder')) ?>"
                >
                <?php
                    $currentPath = custom_admin_path();
                    $displayPath = $currentPath !== '' ? $currentPath : 'admin';
                ?>
                <p class="tip"><?= e(t('admin.settings.user.admin_path_current')) ?> <code><?= e(base_path() . '/' . $displayPath . '/') ?></code>
                <?php if ($currentPath !== ''): ?>
                    <br><?= e(t('admin.settings.user.admin_path_blocked')) ?>
                <?php endif; ?>
                </p>
            </section>

            <section class="section-divider">
                <span class="title"><?= e(t('admin.settings.user.section_password')) ?></span>
                <label for="current_password"><?= e(t('admin.settings.user.current_password')) ?></label>
                <input type="password" id="current_password" name="current_password">

                <label for="new_password"><?= e(t('admin.settings.user.new_password')) ?></label>
                <input type="password" id="new_password" name="new_password">

                <label for="confirm_password"><?= e(t('admin.settings.user.confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password">
            </section>
        </form>
    </main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
