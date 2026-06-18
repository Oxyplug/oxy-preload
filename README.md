# Oxyplug Preload

> Preload featured images, fonts, CSS, and JavaScript to improve **Largest Contentful Paint (LCP)** and your **Core Web Vitals (CWV)** score in Google Lighthouse.

[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![WordPress tested](https://img.shields.io/badge/WordPress-tested%207.0-21759b.svg)
![Requires PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)

A lightweight WordPress plugin that adds resource hints (`rel=preload`) so the browser fetches your most important assets sooner.

## Features

- **Automatic featured-image preload** for posts and pages (enabled by default) using a single, consistent image size for the `href`, `imagesrcset`, and `imagesizes` hints.
- **WooCommerce support** — falls back to the first product gallery image when no featured image is set.
- **Manual preloads** for fonts, CSS, and JavaScript, configured from the settings screen and emitted as HTTP `Link` headers via `.htaccess`.
- **Safe `.htaccess` updates** — backs up the file, writes the change, runs a site-availability check, and restores the backup automatically if anything fails.
- **Multisite-aware** option storage.
- Material Design 3 settings UI.

## Requirements

- WordPress 4.9 or later
- PHP 7.4 or later
- Apache/LiteSpeed for the `.htaccess`-based font/CSS/JS preloads (the featured-image preload works on any server)

## Installation

### From a release ZIP

1. Download the latest ZIP from the [Releases](../../releases) page.
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and select the ZIP.
3. Activate the plugin. Featured-image preloading is enabled automatically.

### Manual

1. Copy this directory into `wp-content/plugins/oxyplug-preload`.
2. Activate **Oxyplug Preload** from the Plugins screen.

## Usage

After activation, featured images are preloaded automatically on single posts and pages.

To preload fonts, CSS, or JS manually:

1. Go to **Tools → Oxyplug Preload**.
2. Add the full URLs under **Script**, **Style**, or **Font**.
3. Click **Save**. The URLs are written to your site's `.htaccess` as preload `Link` headers.

### Choosing the preloaded image size

By default the featured-image preload uses the `post-thumbnail` size. If your theme renders the
featured image at a different size, match it with the `oxyplug_preload_image_size` filter so the
preload points at the exact image the browser will render:

```php
add_filter('oxyplug_preload_image_size', function () {
    return 'large'; // or 'full', or any registered image size
});
```

## Development

The settings UI uses [Material Web](https://github.com/material-components/material-web) components bundled with [Vite](https://vitejs.dev/).

```bash
npm install      # install dependencies
npm run dev      # start the Vite dev server
npm run build    # build the component bundles into assets/js/dist
```

> The built files in `assets/js/dist` are committed because the plugin loads them at runtime.

## Project structure

```
oxy-preload.php          Main plugin file (all PHP logic)
readme.txt               WordPress.org-format readme + changelog
assets/css/              Admin styles
assets/js/src/           Material Web component entry points
assets/js/dist/          Built component bundles (loaded by the plugin)
assets/images/           Icons used in the admin UI
```

## Changelog

See [`readme.txt`](readme.txt) for the full changelog. Latest:

### 2.1.5
- Add translations for Spanish, French, German, Italian, and Brazilian Portuguese.
- Load the plugin text domain so bundled translations are applied.

### 2.1.4
- Fix preloaded featured-image size mismatch that could cause a duplicate image download and hurt LCP.
- Fix a fatal error on the front end when checking for the Oxyplug Image plugin.

## Contributing

Issues and pull requests are welcome. Please open an issue to discuss substantial changes before submitting a PR.

## License

Distributed under the **GPL v2 or later** license. See [`LICENSE`](LICENSE) for the full text.

## Links

- Plugin website: https://www.oxyplug.com/products/oxy-preload
- Documentation: https://www.oxyplug.com/docs/oxy-preload/
