<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$config = load_config();
$fontStack = font_stack_css($config['theme']['admin_font_stack'] ?? 'sans');

$type = trim(($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET)['type'] ?? '');
if (!in_array($type, ['post', 'page'], true)) {
    http_response_code(400);
    exit;
}

$item   = null;
$slug   = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_action_id'])) {
    verify_csrf();
    $slug = trim($_POST['slug'] ?? '');
    $item = $slug !== '' ? ($type === 'post' ? get_post_by_slug($slug, true) : get_page_by_slug($slug, true)) : null;
    if ($slug === '') {
        $errors[] = t("admin.delete_{$type}.error_missing");
    } elseif (!$item) {
        $errors[] = t("admin.delete_{$type}.error_not_found");
    } elseif (!($type === 'post' ? delete_post_by_slug($slug) : delete_page_by_slug($slug))) {
        $errors[] = t("admin.delete_{$type}.error_delete");
    } else {
        header('Location: ' . base_path() . '/admin/content.php?tab=' . ($type === 'post' ? 'posts' : 'pages') . '&deleted=1');
        exit;
    }
} else {
    $slug = trim($_GET['slug'] ?? '');
    $item = $slug !== '' ? ($type === 'post' ? get_post_by_slug($slug, true) : get_page_by_slug($slug, true)) : null;
}

$adminTitle = t("admin.delete_{$type}.page_title");
require __DIR__ . '/../includes/admin-head.php';
?>
    <main>
        <h1><?= e(t("admin.delete_{$type}.heading")) ?></h1>

        <?php if ($errors): ?>
            <div class="notice delete">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$item): ?>
            <p><?= e(t("admin.delete_{$type}.already_deleted")) ?></p>
        <?php else: ?>
            <p><?= e(t("admin.delete_{$type}.confirm", ['title' => $item['title']])) ?></p>
            <form method="post">
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <input type="hidden" name="slug" value="<?= e($item['slug']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="delete"><?= e(t("admin.delete_{$type}.submit")) ?></button>
            </form>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
