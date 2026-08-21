# Production upgrade playbook: v2.x → v3.7.2-custom

This document is the source of truth for upgrading a **live** Pure Blog site that is still on **v2.x** (typically `2.3.4` or `2.3.5-custom`) to **[Banezzz/pureblog](https://github.com/Banezzz/pureblog) `v3.7.2-custom`**.

It is written for a coding agent with shell access to the production server. Follow the phases in order. Do not skip the backup. Do not improvise a different upgrade path.

## 0. Role and hard rules

You are performing a **manual file upgrade** of a production blog. v3 restructured the core. The in-app updater **cannot** apply this jump. Treat the live site as precious data.

**Do**

- Read this whole file before changing anything.
- Discover the live web root yourself (do not assume a path).
- Take a full backup and confirm you can restore it.
- Replace application code while leaving live data directories untouched.
- Report what you did, what you verified, and anything you left for a human.

**Do not**

- Overwrite `config/config.php` or any live content/data directories listed in Phase 3.
- Run `setup.php` or delete `config/` (that would look like a fresh install and can lock the operator out).
- Use Settings → Updates / the in-app updater for this jump.
- Use `--delete` on a whole-tree `rsync` unless the exclude list is complete and you have a verified backup.
- Change the admin password or rewrite post Markdown unless the operator asked.
- Mention, clone, fetch, or open a pull request against any repository other than `Banezzz/pureblog`. This upgrade is only for this repository.

## 1. What this upgrade is

| Item | Value |
| --- | --- |
| Source repository | https://github.com/Banezzz/pureblog |
| Release / tag | `v3.7.2-custom` |
| Release URL | https://github.com/Banezzz/pureblog/releases/tag/v3.7.2-custom |
| Expected `VERSION` after upgrade | `3.7.2` |
| PHP required | 8.1 or newer |
| Required extensions | `mbstring`, `xml` |
| Recommended extensions | `curl`, `zip` |

v3.7.2-custom is v3.7.2 plus the extras this repository keeps: custom admin path, updater pointed at `Banezzz/pureblog`, MIME-forced uploads, setup CSRF, extra Apache hardening, `zh_CN`, table of contents, related posts, year archives, and JSON-LD / Twitter cards.

Existing `config.php` does **not** need new keys written by hand. `load_config()` merges the file onto `default_config()`, so missing keys get safe defaults.

## 2. Phase 1 — Discover the live site

Find the web root. It is the directory that contains **all** of these:

- `functions.php`
- `VERSION`
- `admin/`
- `content/posts/`
- `config/config.php` (if the site is already installed)

Useful clues:

```bash
php -v
php -m | tr ' ' '\n' | grep -E '^(mbstring|xml|curl|zip)$'
find /var/www /home /srv /opt -name functions.php -o -name config.php 2>/dev/null | head
```

Record:

| Fact | How to read it |
| --- | --- |
| Web root | Directory of `functions.php` |
| Current version | `cat VERSION` (expect `2.3.4` or similar) |
| PHP version | Must be `>= 8.1` |
| Custom admin path | From the web root: `php -r '$c = require "config/config.php"; echo (is_array($c) && ($c["admin_path"] ?? "") !== "") ? $c["admin_path"] : "(empty, uses /admin)"; echo "\n";'` `config.php` is `<?php return [...];`. |
| Web server | Apache (`.htaccess` in play) or Nginx (need equivalent rewrite rules) |
| Site URL / `base_url` | From `config.php` |

**Stop if PHP is older than 8.1.** Tell the operator to upgrade PHP first.

**Stop if you cannot find `config/config.php`.** A missing config means this is not an installed production site, or you are in the wrong directory.

## 3. Phase 2 — Full backup

Create a timestamped archive of the **entire** web root, including `config/`, `content/`, `data/`, `backup/`, and `.htaccess`.

```bash
WEBROOT="PUT_THE_DISCOVERED_PATH_HERE"
STAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="${WEBROOT}/../pureblog-upgrade-backup-${STAMP}"
mkdir -p "$BACKUP_DIR"
tar -C "$WEBROOT" -czf "${BACKUP_DIR}/webroot.tar.gz" .
cp -a "${WEBROOT}/.htaccess" "${BACKUP_DIR}/htaccess.pre-upgrade" 2>/dev/null || true
test -f "${BACKUP_DIR}/webroot.tar.gz" && ls -lh "${BACKUP_DIR}/webroot.tar.gz"
```

Confirm the tarball is non-empty and extractable:

```bash
tar -tzf "${BACKUP_DIR}/webroot.tar.gz" | head
```

Do not continue without a readable backup.

## 4. Phase 3 — What must never be overwritten

Live data. Copy **code** in; leave these paths exactly as they are on the server:

| Path | Why |
| --- | --- |
| `config/config.php` | Credentials, site settings, `admin_path` |
| `config/hooks.php` | Operator hooks, if present |
| `content/posts/` | All posts |
| `content/pages/` | All pages (including a customized Search page) |
| `content/images/` | Uploads |
| `content/css/` | `custom.css` / `admin-custom.css` |
| `content/functions.php` | Operator PHP hooks, if present |
| `content/autosaves/` | Editor autosaves |
| `content/layouts/` | Custom layouts, if present |
| `data/` | Runtime data (login lockout, remember-me, etc.) |
| `backup/` | Existing in-app backups |

Safe to replace (application code and generated indexes):

- Root PHP: `index.php`, `functions.php`, `setup.php`, `post.php`, `page.php`, `feed.php`, `sitemap.php`, `404.php`, `og-image.php`
- `VERSION`, `upgrade-notice.json`, `LICENSE.md`
- `admin/` (entire tree of PHP/CSS/JS/icons)
- `includes/`
- `assets/` (including the new `assets/fonts/` tree)
- `lang/`
- `lib/`
- `content/search-index.json` and `content/tag-index.json` (rebuild after deploy)
- Directory `.htaccess` files that ship with the release (`config/.htaccess`, `content/.htaccess`, `data/.htaccess`, `backup/.htaccess`, `content/images/.htaccess`), **unless** the operator added extra rules there — then merge, do not blindly clobber

Root `.htaccess`: use the **new** file as the base, then re-apply any **custom** rules from the backup (IP allowlist for `/admin/`, extra redirects, etc.). See Phase 5.

## 5. Phase 4 — Fetch the release and deploy code

Work from a sibling directory. Do not unpack the zip on top of the live root.

```bash
SRC="${WEBROOT}/../pureblog-v3.7.2-custom-src"
rm -rf "$SRC"
mkdir -p "$SRC"

# Preferred: git
git clone --depth 1 --branch v3.7.2-custom https://github.com/Banezzz/pureblog.git "$SRC"

# Fallback: release zipball
# curl -L https://github.com/Banezzz/pureblog/archive/refs/tags/v3.7.2-custom.tar.gz | tar -xz -C "$SRC" --strip-components=1
```

Confirm the unpacked tree:

```bash
test -f "$SRC/includes/lib/fork.php"
test -f "$SRC/og-image.php"
grep -qx '3.7.2' "$SRC/VERSION"
```

Create directories the new code expects:

```bash
mkdir -p "${WEBROOT}/cache"
# cache must be writable by the PHP user
```

Copy application code **without** touching the preserve list. Example `rsync` (no `--delete`):

```bash
rsync -a \
  --exclude '.git/' \
  --exclude 'docs/' \
  --exclude 'config/config.php' \
  --exclude 'config/hooks.php' \
  --exclude 'content/posts/' \
  --exclude 'content/pages/' \
  --exclude 'content/images/' \
  --exclude 'content/css/' \
  --exclude 'content/functions.php' \
  --exclude 'content/autosaves/' \
  --exclude 'content/layouts/' \
  --exclude 'data/' \
  --exclude 'backup/' \
  --exclude 'cache/' \
  "$SRC/" "$WEBROOT/"
```

`docs/` is excluded so a documentation-only file from this repository does not have to exist on the server. Copying it is harmless if you want it on disk.

If `rsync` is missing, copy the same paths with `cp -a` file-by-file. Never `cp -a "$SRC/config/."` onto live `config/`.

### Delete leftover v2-only files

These no longer exist in 3.x. Remove them from the live root if they are still there:

```bash
rm -f \
  "${WEBROOT}/admin/pages.php" \
  "${WEBROOT}/admin/delete-post.php" \
  "${WEBROOT}/admin/delete-page.php" \
  "${WEBROOT}/config/hooks-example.php"
```

`admin/delete-content.php` replaces the old delete scripts. Do not delete `admin/settings.php` — v3 still ships it as a redirect to `settings-site.php`.

## 6. Phase 5 — Merge `.htaccess` (Apache)

New root `.htaccess` must include all of the following (already in `v3.7.2-custom`):

- `Options -Indexes`
- Static-asset cache headers (`mod_expires`)
- Hidden-file deny (`FilesMatch "^\."`)
- `sitemap.xml` → `sitemap.php`
- Forbidden: `config`, `data`, `backup`
- Forbidden: `content/autosaves/`, `content/layouts/`, `content/functions.php`
- Forbidden: `VERSION`, `*.md`, `.git`
- Forbidden: PHP under `content/images/`
- Front-controller fallback to `index.php`

Take the new file from `$SRC/.htaccess`. Then, from `${BACKUP_DIR}/htaccess.pre-upgrade`, copy back **only** operator-specific lines (commented IP allowlist that was uncommented, extra redirects, auth). Do not drop the new deny rules.

**Nginx:** there is no `.htaccess`. Mirror the same protections in the server block:

- Deny `/config`, `/data`, `/backup`, `/.git`, `/VERSION`, `*.md`
- Deny `/content/autosaves`, `/content/layouts`, `/content/functions.php`
- Deny PHP execution under `/content/images`
- Route unknown paths to `index.php`
- Optionally long-cache `/assets/fonts` and other static files

## 7. Phase 6 — Permissions and generated files

```bash
# PHP user must write these (adjust user/group to the live php-fpm / apache user)
chmod 775 "${WEBROOT}/cache" "${WEBROOT}/data" "${WEBROOT}/backup" \
  "${WEBROOT}/content/posts" "${WEBROOT}/content/pages" \
  "${WEBROOT}/content/images" "${WEBROOT}/content/autosaves" || true

cd "$WEBROOT"
php -r "require 'functions.php'; echo build_search_index() ? 'search ok\n' : 'search FAIL\n'; echo build_tag_index() ? 'tags ok\n' : 'tags FAIL\n';"
```

Do **not** visit `/setup.php`. If `config/config.php` is present, the site is already installed.

Remember-me cookies from v2.x are invalid. The operator must log in again with username + password.

If `admin_path` is set (for example `secret`), the login URL is `https://<site>/<admin_path>/` (or `/<admin_path>/index.php`). Direct `/admin/` returns **404** until a session has entered through that custom path.

## 8. Phase 7 — Verify

Run these checks against the live site (curl and/or a browser). Replace `ORIGIN` and `ADMIN_PATH`.

```bash
ORIGIN="https://example.com"   # from config base_url
ADMIN_PATH="admin"             # or the custom path, no slashes

# Homepage
curl -sS -o /tmp/pb-home.html -w "%{http_code}\n" "$ORIGIN/"
# Must be 200, not the setup wizard, not a PHP fatal.

# A known post slug from content/posts
curl -sS -o /tmp/pb-post.html -w "%{http_code}\n" "$ORIGIN/SOME-LIVE-SLUG"

# Search page
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/search"

# Year archive (new)
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/archive"

# Feed and sitemap
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/feed"
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/sitemap.xml"

# Config and VERSION must stay forbidden
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/config/config.php"   # expect 403/404
curl -sS -o /dev/null -w "%{http_code}\n" "$ORIGIN/VERSION"             # expect 403/404

# Custom admin path (if configured) should reach the login form, not setup
curl -sS -o /tmp/pb-admin.html -w "%{http_code}\n" "$ORIGIN/${ADMIN_PATH}/"
```

Manual / operator checks (tell the operator if you cannot log in yourself):

1. Log in through the custom admin path if one is set.
2. Confirm posts and pages still list in Content.
3. Open one post, one page, the image library.
4. Settings → Site: review new toggles (reading time, table of contents, related posts, JSON-LD, comments). Defaults are on for TOC / related / JSON-LD.
5. Settings → Updates: repository must show `github.com/Banezzz/pureblog`. It must **not** offer a downgrade to `v2.3.5-custom`.
6. Language list includes 简体中文 (`zh_CN`).
7. A post with two or more `h2`/`h3` headings shows a contents box.
8. `/archive` and `/archive/YYYY` render.

**Fail the upgrade** (restore the backup) if:

- PHP fatals on the homepage
- The site redirects to `setup.php`
- Posts disappeared from `content/posts/`
- `config/config.php` was replaced or emptied
- Admin login is impossible after trying both `/admin/` and the recorded custom path

## 9. Rollback

```bash
WEBROOT="PUT_THE_DISCOVERED_PATH_HERE"
BACKUP_DIR="PUT_THE_BACKUP_DIR_HERE"
# Optional: move the broken tree aside
mv "$WEBROOT" "${WEBROOT}.failed-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$WEBROOT"
tar -C "$WEBROOT" -xzf "${BACKUP_DIR}/webroot.tar.gz"
```

Then confirm the homepage and login work again.

## 10. After a successful upgrade

- Leave the backup tarball in place until the operator says it can go.
- Do not publish another GitHub release from production.
- Future **same-major** updates can use Settings → Updates against `Banezzz/pureblog` releases, once those releases are also 3.x.
- Point the operator at Settings → Site for TOC / related posts / JSON-LD.

## 11. Report format

When you finish, reply with:

1. Web root path and PHP version
2. Backup path
3. Old `VERSION` → new `VERSION`
4. Whether `admin_path` is set, and the URL you used to reach admin
5. Files deleted
6. `.htaccess` / Nginx rules you merged
7. Search/tag index rebuild result
8. HTTP status of homepage, one post, `/archive`, `/feed`, forbidden `/config/config.php`
9. Anything you did not verify (no browser, no credentials, etc.)

## Appendix A — New files that must exist after deploy

If any of these are missing, the copy step was incomplete:

- `includes/lib/auth.php`
- `includes/lib/cache.php`
- `includes/lib/content.php`
- `includes/lib/fork.php`
- `includes/lib/i18n.php`
- `includes/lib/template.php`
- `includes/updater.php`
- `admin/bootstrap.php`
- `admin/delete-content.php`
- `admin/images.php`
- `og-image.php`
- `lang/zh_CN.php`
- `lang/zh_TW.php`
- `lang/fi.php`
- `lang/pl.php`
- `assets/fonts/inter/` (and iosevka / merriweather)
- `VERSION` containing `3.7.2`

## Appendix B — Why search can look empty

v3 reads `content/search-index.json`. An old v2 index may not match. Rebuild with `build_search_index()` as in Phase 6, or edit-and-save any post in admin (that also rebuilds the index).
