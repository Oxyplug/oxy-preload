# Developer Guide

This document is a quick reference for working on the Oxyplug Preload plugin.

## Project Overview

Oxyplug Preload is a WordPress plugin that:

- Preloads featured images for posts and pages to improve LCP.
- Falls back to WooCommerce product gallery images when a featured image is missing.
- Lets site owners add manual preload URLs for scripts, styles, and fonts.
- Writes HTTP `Link` preload headers into `.htaccess` when the server supports it.
- Ships a Material Design 3 admin UI built with Material Web components.

## Main Files

- [oxy-preload.php](oxy-preload.php) - main plugin bootstrap and all PHP logic.
- [package.json](package.json) - front-end build scripts and dependencies.
- [assets/js/src/](assets/js/src/) - Material Web component entry points.
- [assets/js/dist/](assets/js/dist/) - built bundles loaded by the plugin.
- [assets/css/admin-style.css](assets/css/admin-style.css) - admin screen styles.
- [assets/js/admin-script.js](assets/js/admin-script.js) - admin page interactions and AJAX save flow.
- [readme.txt](readme.txt) - WordPress.org readme and changelog.

## Local Setup

Requirements:

- WordPress 4.9 or later.
- PHP 7.4 or later.
- Node.js for the admin UI build.

Install dependencies:

```bash
npm install
```

Development commands:

```bash
npm run dev
npm run build
npm run serve
```

## Development Flow

1. Edit the PHP logic in [oxy-preload.php](oxy-preload.php) when changing preload behavior, AJAX handling, or `.htaccess` updates.
2. Edit the admin UI sources in [assets/js/src/](assets/js/src/) when changing the Material Web component bundles.
3. Edit [assets/js/admin-script.js](assets/js/admin-script.js) when changing the settings screen behavior, validation, or save flow.
4. Run `npm run build` after changing the UI source files so the committed bundles in [assets/js/dist/](assets/js/dist/) stay in sync.

## Behavior Notes

- Featured-image preloading is enabled by default on activation.
- The plugin uses a single image size for `href`, `imagesrcset`, and `imagesizes` to avoid duplicate downloads.
- Manual preload URLs are saved from the settings screen and normalized before `.htaccess` generation.
- `.htaccess` updates are backed up and restored if validation or the site check fails.
- The admin screen loads the bundled Material Web bundles dynamically.

## Working Conventions

- Keep changes focused and avoid broad refactors unless they support the requested behavior.
- Preserve the plugin's WordPress-friendly style and escaping/sanitization patterns.
- Update the built frontend bundles when source files change.
- Keep the readme and changelog aligned with user-facing changes.

## Useful Checks

- Confirm the plugin still loads on the front end without fatal errors.
- Verify the settings page still saves URLs and toggles the featured-image preload option.
- Check that `.htaccess` output is generated only on supported servers.

## Release Reminder

Before publishing a release, verify:

- `package.json` version and plugin header version in [oxy-preload.php](oxy-preload.php).
- Bundled assets in [assets/js/dist/](assets/js/dist/).
- User-facing notes in [README.md](README.md) and [readme.txt](readme.txt).