<?php
$settingsPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
$adminSegment = admin_path_segment();
$internalPath = $settingsPath;
if ($settingsPath === $adminSegment) {
    $internalPath = 'admin/index.php';
} elseif (str_starts_with($settingsPath, $adminSegment . '/')) {
    $internalPath = 'admin/' . substr($settingsPath, strlen($adminSegment) + 1);
}
$settingsItems = [
    'settings-site.php' => ['label' => 'Site', 'icon' => 'globe'],
    'settings-theme.php' => ['label' => 'Theme', 'icon' => 'paintbrush'],
    'settings-css.php' => ['label' => 'CSS', 'icon' => 'braces'],
    'settings-user.php' => ['label' => 'User', 'icon' => 'user'],
    'settings-updates.php' => ['label' => 'Updates', 'icon' => 'upgrade'],
];
$settingsSaveFormId = $settingsSaveFormId ?? '';
?>
<ul class="settings-nav-list" aria-label="Settings sections">
    <?php foreach ($settingsItems as $script => $item): ?>
        <?php
        $href = admin_url($script);
        $isCurrent = $internalPath === 'admin/' . $script;
        ?>
        <li>
            <a href="<?= e($href) ?>"<?= $isCurrent ? ' class="current"' : '' ?>>
                <svg class="icon" aria-hidden="true"><use href="/admin/icons/sprite.svg#icon-<?= e($item['icon']) ?>"></use></svg>
                <?= e($item['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php if ($settingsSaveFormId !== ''): ?>
    <button class="save" type="submit" form="<?= e($settingsSaveFormId) ?>" aria-label="Save settings">
        <svg class="icon" aria-hidden="true"><use href="/admin/icons/sprite.svg#icon-save"></use></svg>
        Save settings
    </button>
<?php endif; ?>
