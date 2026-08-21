<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Extra helpers for this repository
// Keep these together so they are easier to re-apply after later version upgrades.
// ---------------------------------------------------------------------------

function custom_admin_path(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }

    $config = load_config();
    $raw = trim((string) ($config['admin_path'] ?? ''));
    $raw = trim($raw, '/');
    $raw = preg_replace('/[^a-z0-9_-]/', '', strtolower($raw)) ?? '';
    $path = ($raw !== '' && $raw !== 'admin') ? $raw : '';

    return $path;
}

function is_custom_admin_routed(): bool
{
    return !empty($GLOBALS['__pureblog_custom_admin_routed']);
}

function admin_login_url(): string
{
    $custom = custom_admin_path();
    return $custom !== ''
        ? base_path() . '/' . $custom . '/'
        : base_path() . '/admin/index.php';
}

/**
 * Block direct /admin/ access when a custom admin path is configured.
 *
 * Must only be called from admin PHP files, never from public templates.
 * Public pages also start a session for the logged-in edit button.
 */
function guard_admin_path(): void
{
    $custom = custom_admin_path();
    if ($custom === '') {
        return;
    }

    if (is_custom_admin_routed()) {
        $_SESSION['admin_path_verified'] = true;
        return;
    }

    if (!empty($_SESSION['admin_path_verified'])) {
        return;
    }

    http_response_code(404);
    exit('Not found.');
}

function route_custom_admin_request(string $requestPath): void
{
    $customAdminPath = custom_admin_path();
    if ($customAdminPath === '') {
        return;
    }

    if ($requestPath !== $customAdminPath && !str_starts_with($requestPath, $customAdminPath . '/')) {
        return;
    }

    $adminRelativePath = $requestPath === $customAdminPath
        ? 'index.php'
        : substr($requestPath, strlen($customAdminPath) + 1);
    $adminRelativePath = trim((string) $adminRelativePath, '/');
    if ($adminRelativePath === '') {
        $adminRelativePath = 'index.php';
    }
    if (!str_ends_with($adminRelativePath, '.php')) {
        $adminRelativePath .= '.php';
    }

    $adminRoot = realpath(PUREBLOG_BASE_PATH . '/admin');
    $adminTarget = realpath(PUREBLOG_BASE_PATH . '/admin/' . $adminRelativePath);
    if (
        $adminRoot === false
        || $adminTarget === false
        || !str_starts_with($adminTarget, $adminRoot . DIRECTORY_SEPARATOR)
        || !is_file($adminTarget)
    ) {
        require PUREBLOG_BASE_PATH . '/404.php';
        exit;
    }

    $adminQueryString = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    if ($adminQueryString !== null && $adminQueryString !== '') {
        $_SERVER['QUERY_STRING'] = $adminQueryString;
        parse_str($adminQueryString, $parsedQuery);
        $_GET = array_merge($_GET, $parsedQuery);
    }

    $GLOBALS['__pureblog_custom_admin_routed'] = true;
    require $adminTarget;
    exit;
}

function is_newer_release(string $latest, string $current): bool
{
    if (versions_match($current, $latest)) {
        return false;
    }

    $latestCore = preg_replace('/[^0-9.].*/', '', ltrim(strtolower(trim($latest)), 'v')) ?? '';
    $currentCore = preg_replace('/[^0-9.].*/', '', ltrim(strtolower(trim($current)), 'v')) ?? '';
    if ($latestCore === '' || $currentCore === '') {
        return !versions_match($current, $latest);
    }

    return version_compare($latestCore, $currentCore, '>');
}

function prepare_heading_ids(string $html): array
{
    $toc = [];
    $used = [];

    $html = preg_replace_callback(
        '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
        static function (array $match) use (&$toc, &$used): string {
            $level = (int) $match[1];
            $attrs = $match[2] ?? '';
            $inner = $match[3];
            $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text === '') {
                return $match[0];
            }

            if (preg_match('/\bid\s*=\s*([\'"])([^\'"]+)\1/i', $attrs, $idMatch)) {
                $id = $idMatch[2];
            } else {
                $id = function_exists('slugify') ? slugify($text) : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text) ?? 'section');
                if ($id === '') {
                    $id = 'section';
                }
                $base = $id;
                $suffix = 2;
                while (isset($used[$id])) {
                    $id = $base . '-' . $suffix;
                    $suffix++;
                }
                $attrs .= ' id="' . htmlspecialchars($id, ENT_QUOTES) . '"';
            }

            $used[$id] = true;
            $toc[] = [
                'id' => $id,
                'text' => $text,
                'level' => $level,
            ];

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $html
    ) ?? $html;

    return ['html' => $html, 'toc' => $toc];
}

function render_post_toc(array $toc): string
{
    if (count($toc) < 2) {
        return '';
    }

    $out = '<nav class="post-toc" aria-label="' . e(t('frontend.toc_heading')) . '">';
    $out .= '<p class="post-toc-heading">' . e(t('frontend.toc_heading')) . '</p><ol>';
    foreach ($toc as $item) {
        $out .= '<li class="toc-h' . (int) $item['level'] . '"><a href="#' . e((string) $item['id']) . '">' . e((string) $item['text']) . '</a></li>';
    }
    $out .= '</ol></nav>';

    return $out;
}
