# Pure Blog

Pure Blog is a simple, flat-file blogging platform with a Markdown-first editor and a lightweight admin area. It stores posts and pages as Markdown files on disk—no database required.

This repository is **v3.7.2** of Pure Blog, plus extra hardening and features for personal use.

## Features

- Flat-file Markdown posts and pages with front matter
- Admin dashboard, content manager, image library, and draft previews
- Tags, search, pagination, RSS, sitemap, and scheduled posts
- Reading time, feature images, dynamic Open Graph cards, and custom navigation
- Theme settings, custom CSS, hooks, and optional comments integration
- Configurable admin URL path. Direct `/admin/` access returns 404 until you enter through the custom path
- Built-in updater pointed at [Banezzz/pureblog](https://github.com/Banezzz/pureblog) releases, with version compare that will not offer a downgrade
- Simplified Chinese (`zh_CN`) language pack
- Automatic table of contents on long posts
- Related posts by shared tags
- Year archives at `/archive` and `/archive/YYYY`
- JSON-LD structured data and Twitter card meta tags
- Extra `.htaccess` hardening (config/backup/git/VERSION, hidden files)
- MIME-forced image extensions and CSRF protection on the setup form

## Requirements

- PHP 8.1 or newer
- A standard web server (Apache or Nginx)
- Required PHP extensions: `mbstring`, `xml`
- Recommended PHP extensions: `curl`, `zip`
- Write access to `/config`, `/content`, and `/data`

## Getting started

1. Download the Pure Blog package.
2. Extract the zip file and upload the contents to your web server.
3. Visit the URL of your blog and setup will automatically start.
4. Once your site is set up, visit `/admin` and log in. You can later change the admin path in Settings → User.

## Content

Posts live in `content/posts` and pages live in `content/pages`. Uploaded images are stored in `/content/images/[post/page-slug]`.

## Notes

- Pure Blog is intentionally minimal and designed for personal sites.
- HTML in Markdown is supported.
- Table of contents, related posts, and JSON-LD can be toggled in Settings → Site.

## Upgrading from v2.x to v3.x

v3.0 restructured the core and cannot be applied with the old in-app updater. Treat this as a manual upgrade.

### Data preservation

Upload the new code files but **do not overwrite** these directories — they contain your data:

- `config/` — site configuration and credentials
- `content/posts/` — blog posts
- `content/pages/` — pages
- `content/images/` — uploaded images
- `content/css/` — custom CSS
- `data/` — runtime data
- `backup/` — backups

Keep any custom rules you added to the root `.htaccess`.

### After upgrading

1. Log in again. Remember-me cookies from v2.x are invalid.
2. If search results look empty, edit and save any post, or run: `php -r "require 'functions.php'; build_search_index(); echo 'done';"`
3. Review Settings → Site for new options (reading time, table of contents, related posts, JSON-LD, comments).
4. If you use a custom admin path, enter through that path after the upgrade.

### Removed / replaced files

These v2.x files are gone in 3.x and can be deleted if they are still on the server:

- `admin/pages.php`
- `admin/delete-post.php`
- `admin/delete-page.php` (replaced by `admin/delete-content.php`)
- `config/hooks-example.php`
