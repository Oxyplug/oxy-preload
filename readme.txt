=== Oxyplug Preload ===
Contributors: oxyplug
Tags: lcp, core web vital, preload, resource hint, seo
Requires at least: 4.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Preload featured images to improve the Largest Contentful Paint (LCP) and to get a better Core Web Vital (CWV) score on Google's Lighthouse.

== Description ==
Preload post/page featured images and product images to enhance the Largest Contentful Paint (LCP) and achieve a better Core Web Vitals (CWV) score in Google's Lighthouse. Additionally, the tool supports preloading fonts, CSS, and JavaScript files when specified manually, allowing for even greater optimization of page load performance.

== Installation ==
Install and activate it. It will preload the featured images automatically.
You can also manually preload fonts, CSS, and JavaScript files in the settings for even better results.

== Screenshots ==
1. Specifying script preload.
2. Specifying style preload.
3. Specifying font preload.
4. Enabling/Disabling featured image auto preload.
5. Font/CSS/Javascript preloads in response headers.
6. Featured image preload tag.

== Changelog ==
= 2.2.2 =
*Tested up to WordPress 7.1*

= 2.2.1 =
*Exclude development, test, and build files from the published plugin package to reduce its size*

= 2.2.0 =
*Declare a font MIME type on font preloads to avoid a double font download in some browsers*
*Add an uninstall routine that removes the .htaccess preload block and plugin options*
*Remove the .htaccess preload block on deactivation so stale headers are not sent while inactive*
*Security: require the manage_options capability in the preload save handler*
*Fix featured-image preload escaping (esc_url) and a PHP warning on servers that do not set SERVER_SOFTWARE*

= 2.1.5 =
*Add translations for Spanish, French, German, Italian, and Brazilian Portuguese*
*Load the plugin text domain so bundled translations are applied*

= 2.1.4 =
*Fix preloaded featured image size mismatch that could cause a duplicate image download and hurt LCP*
*Fix fatal error on the front end when checking for the Oxyplug Image plugin*
*Tested up to WordPress 7.0*

= 2.1.3 =
*Add compatibility with Oxyplug Image to prevent double preloading*

= 2.1.2 =
*Fix multiple preloads*

= 2.1.1 =
*Fix preloading when lazy images/js loaded*

= 2.1.0 =
*Placeholders dynamic to static*
*Make placeholders' color lighter*
*Use .htaccess instead of php for preloading*
*Fix displaying the order of inputs*
*Fix accepting query strings in URLs*
*Fix ignoring empty inputs*
*Fix duplicate URLs*

= 2.0.0 =
*Enable preloading of fonts, CSS, and JavaScript by specifying their URLs*
*Apply Material Design 3 design*

= 1.0.0 =
*Release Date - 08 January 2022*
