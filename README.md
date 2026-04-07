# Pure Blog

Pure Blog is a simple, flat‑file blogging platform with a Markdown‑first editor and a lightweight admin area. It stores posts and pages as Markdown files on disk—no database required.

## Features
- Flat-file content using Markdown and front matter.
- A clean, distraction-free admin dashboard for writing and organising posts/pages.
- Draft previews so you can check your work before publishing.
- Optional tags and tag archives for grouping related posts.
- Automatic pagination when your post list grows long.
- An RSS feed so readers can follow along however they like.
- Built-in search that helps readers find exactly what they’re looking for.
- A settings page that allows you to customise and configure your blog.

## Getting started
All you need to run Pure Blog is a host that supports PHP (pretty much all of them do). Once you have that, all you need to do is:

1. Download the Pure Blog package.
2. Extract the zip file and upload the contents to your web server.
3. Visit the URL of your blog and setup will automatically start.
4. Once your site is setup, visit `/admin` and login to your new blog.

## Content
Posts live in `content/posts` and pages live in `content/pages`. When images are uploaded they are stored in `/content/images/[post/page-slug]`.

## Notes
- Pure Blog is intentionally minimal and designed for personal sites.
- HTML in Markdown is supported.

## Upgrading from v1.x to v2.x

When upgrading from v1.x to v2.x, keep the following in mind:

### Data preservation
Upload all new code files but **do not overwrite** these directories — they contain your data:
- `config/` — site configuration and credentials
- `content/posts/` — blog posts
- `content/pages/` — pages (but note v2.x adds `content/pages/search.md`)
- `content/images/` — uploaded images
- `content/css/` — custom CSS
- `data/` — runtime data
- `backup/` — backups

### Rebuild search index
v2.x uses a new JSON-based search index (`content/search-index.json`). After upgrading, the index will be empty and search will return no results. Rebuild it by either:
- Editing and saving any post in the admin panel (triggers automatic rebuild), or
- Running on the server: `php -r "require 'functions.php'; build_search_index(); echo 'done';"`

### Removed files
The following files from v1.x can be safely deleted:
- `search.php` — replaced by page-based search (`content/pages/search.md`)
- `search/` directory

### New directories to create
Ensure these exist with correct permissions after uploading:
- `content/autosaves/`
- `lang/`
- `lib/`
