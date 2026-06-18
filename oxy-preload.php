<?php
/**
 * Plugin Name: Oxyplug Preload
 * Plugin URI: https://www.oxyplug.com/products/oxy-preload
 * Description: Preload post/page featured images and product images to enhance the Largest Contentful Paint (LCP) and achieve a better Core Web Vitals (CWV) score in Google's Lighthouse. Additionally, the tool supports preloading fonts, CSS, and JavaScript files when specified manually, allowing for even greater optimization of page load performance.
 * Version: 2.2.0
 * Author: Oxyplug
 * Author URI: https://www.oxyplug.com
 * Requires PHP: 7.4
 * Requires at least: 4.9
 * Tested up to: 7.0
 * Text Domain: oxyplug-preload
 * Domain Path: /lang/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2025 Oxyplug
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Class OxyPreload
 */
class OxyPreload
{
  protected string $imgurl;
  protected string $srcset;
  protected string $sizes;
  const OXYPLUG_PRELOAD_VERSION = '2.1.5';

  public function __construct()
  {
    if (!defined('FS_CHMOD_FILE')) {
      define('FS_CHMOD_FILE', 0644);
    }

    // Init on activate
    register_activation_hook(__FILE__, array($this, 'activate_it'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate_it'));
    add_action('admin_init', array($this, 'init'));

    // Load translations from the bundled /lang directory
    add_action('init', array($this, 'oxyplug_preload_load_textdomain'));

    // Add preload tag
    add_action('plugins_loaded', array($this, 'check_required_plugin'));

    // Save preloads
    add_action('wp_ajax_oxyplug_preload_save_preloads', array($this, 'oxyplug_preload_save_preloads'));

    // Add menu
    add_action('admin_menu', array($this, 'add_menu'));

    // Add settings in plugins page
    add_filter('plugin_action_links', array($this, 'add_settings'), 10, 3);

    // Add necessities in admin head
    add_action('admin_head', array($this, 'admin_head'));

    // Add admin assets
    add_action('admin_enqueue_scripts', array($this, 'add_admin_assets'));
  }

  /**
   * @return void
   */
  public function activate_it()
  {
    // Enable preloading `featured image` by default
    $preload_featured_image = $this->oxyplug_preload_get_option('_oxyplug_preload_featured_image');
    if (empty($preload_featured_image)) {
      $this->oxyplug_preload_update_option('_oxyplug_preload_featured_image', 'true');
    }

    // Set a transient to indicate an update has occurred
    set_transient('oxyplug_preload_updated', true, 30);
  }

  /**
   * Remove the static `.htaccess` preload block on deactivation.
   *
   * The preload `Link` headers live in `.htaccess`, so they would keep being
   * emitted while the plugin is inactive. Strip the block on deactivation, but
   * leave the `_oxyplug_preload_*` options intact so settings survive a
   * reactivation (the block is regenerated when preloads are next saved).
   *
   * @return void
   */
  public function deactivate_it()
  {
    $htaccess_path = ABSPATH . '.htaccess';

    global $wp_filesystem;
    if (!$wp_filesystem) {
      require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
      require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
      $wp_filesystem = new \WP_Filesystem_Direct(null);
    }

    if (!$wp_filesystem->exists($htaccess_path)
      || !$wp_filesystem->is_readable($htaccess_path)
      || !$wp_filesystem->is_writable($htaccess_path)) {
      return;
    }

    $current_content = $wp_filesystem->get_contents($htaccess_path);
    if ($current_content === false) {
      return;
    }

    $cleaned = $this->strip_oxyplug_section($current_content);

    if ($cleaned !== $current_content) {
      $wp_filesystem->put_contents($htaccess_path, rtrim($cleaned) . "\n");
    }
  }

  /**
   * Load the plugin text domain so bundled translations in /lang are applied.
   *
   * @return void
   */
  public function oxyplug_preload_load_textdomain()
  {
    load_plugin_textdomain('oxyplug-preload', false, dirname(plugin_basename(__FILE__)) . '/lang/');
  }

  public function init()
  {
    // Check if we need to perform update tasks
    if (get_transient('oxyplug_preload_updated')) {
      // Delete the transient
      delete_transient('oxyplug_preload_updated');

      // Update .htaccess with improved rules if needed
      $preloads = $this->oxyplug_preload_get_option('_oxyplug_preload_preloads', array());

      if (!empty($preloads)) {
        // Prepare data for htaccess generation
        $htaccess_preloads = array();

        foreach ($preloads as $type => $urls) {
          if (!isset($htaccess_preloads[$type])) {
            $htaccess_preloads[$type] = array();
          }

          foreach ($urls as $url) {
            $htaccess_preloads[$type][] = $url;
          }
        }

        // Generate htaccess content
        $htaccess_content = $this->generate_htaccess_content($htaccess_preloads);

        // Update .htaccess file
        $this->update_htaccess($htaccess_content);
      }
    }
  }

  /**
   * @return void
   */
  public function check_required_plugin()
  {
    // is_plugin_active() lives in wp-admin/includes/plugin.php, which is not
    // loaded on the front end. Use the active_plugins option directly so this
    // works on public requests without a fatal "undefined function" error.
    $active_plugins = (array)get_option('active_plugins', array());
    if (!in_array('oxyplug-image/index.php', $active_plugins, true)) {
      add_action('wp_head', array($this, 'add_preload_tag'));
    }
  }

  /**
   * @return void
   */
  public function add_preload_tag()
  {
    $preload_featured_image = $this->oxyplug_preload_get_option('_oxyplug_preload_featured_image') == 'true';
    if ($preload_featured_image) {
      if (is_single() || is_page()) {
        // Use a single, consistent image size for the href, srcset and sizes
        // hints. Mismatched sizes make the browser download the preloaded image
        // AND the rendered one, wasting bandwidth and hurting LCP.
        $size = apply_filters('oxyplug_preload_image_size', 'post-thumbnail');

        $thumbnail_id = (int)(get_post_thumbnail_id());
        if ($thumbnail_id > 0) {
          $this->imgurl = get_the_post_thumbnail_url(null, $size);
        } else if (function_exists('wc_get_product')) {
          if ($product = wc_get_product(get_the_id())) {
            $attachment_ids = $product->get_gallery_image_ids();
            if (sizeof($attachment_ids) > 0) {
              $thumbnail_id = reset($attachment_ids);
              $this->imgurl = wp_get_attachment_image_url($thumbnail_id, $size);
            }
          }
        }

        if ($thumbnail_id) {
          $this->srcset = wp_get_attachment_image_srcset($thumbnail_id, $size);
          $this->sizes = wp_get_attachment_image_sizes($thumbnail_id, $size);
          ?>
          <link rel="preload"
                as="image"
                href="<?php echo esc_url($this->imgurl) ?>"
                imagesrcset="<?php echo esc_attr($this->srcset) ?>"
                imagesizes="<?php echo esc_attr($this->sizes) ?>"
                fetchpriority="high">
          <?php
        }
      }
    }
  }

  /**
   * @return void
   */
  public function admin_head()
  {
    // Load only in specific pages
    $screen = get_current_screen();
    if ($screen && $screen->base == 'tools_page_oxyplug-preload-settings') {
      $component_path = plugins_url('assets/js/dist/', __FILE__);
      $components = array(
        'tools_page_oxyplug-preload-settings' => array(
          'outlined-text-field',
          'icon',
          'icon-button',
          'outlined-button',
          'filled-button',
          'divider',
          'switch'
        ),
      );
      $components = wp_json_encode($components[$screen->base]);
      ?>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Oxygen:wght@300;400;700&display=swap" rel="stylesheet">
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

      <script type="module">
        // Load MD3 components
        (async function () {
          const OXYPLUG_PRELOAD_VERSION = '<?php echo self::OXYPLUG_PRELOAD_VERSION ?>'
          const components = '<?php echo $components ?>'
          const component_path = '<?php echo $component_path ?>'

          for (const component of JSON.parse(components)) {
            await import(`${component_path}${component}.js?ver=${OXYPLUG_PRELOAD_VERSION}`);
          }
        })();
      </script>
    <?php }
  }

  /**
   * @param string $hook_suffix Current admin page hook suffix.
   *
   * @return void
   */
  public function add_admin_assets($hook_suffix = '')
  {
    // Only load assets on the plugin's settings screen, not on every admin page.
    if ($hook_suffix !== 'tools_page_oxyplug-preload-settings') {
      return;
    }

    wp_register_script('oxyplug-preload-admin-script', plugins_url('assets/js/admin-script.js', __FILE__), array('jquery'), self::OXYPLUG_PRELOAD_VERSION);
    wp_enqueue_script('oxyplug-preload-admin-script');

    wp_register_style(
      'oxyplug-preload-admin-style',
      plugins_url('assets/css/admin-style.css', __FILE__),
      array(),
      self::OXYPLUG_PRELOAD_VERSION
    );
    wp_enqueue_style('oxyplug-preload-admin-style');

    wp_localize_script(
      'oxyplug-preload-admin-script',
      'oxyplug_preload_defines',
      array(
        'trans' => array(
          'invalid_url' => __('Invalid URL', 'oxyplug-preload'),
        )
      )
    );
  }

  /**
   * @return void
   */
  public function add_menu()
  {
    add_submenu_page(
      'tools.php',
      'Oxyplug Preload',
      'Oxyplug Preload',
      'manage_options',
      'oxyplug-preload-settings',
      array($this, 'oxyplug_preload_settings')
    );
  }

  /**
   * @param $actions
   * @param $plugin_file
   * @param $plugin_data
   *
   * @return mixed
   */
  public function add_settings($actions, $plugin_file, $plugin_data)
  {
    if (isset($plugin_data['slug']) && $plugin_data['slug'] == 'oxyplug-preload') {
      $href = admin_url('tools.php?page=oxyplug-preload-settings');

      $actions['Settings'] = '<a href="' . $href . '">' . __('Settings', 'oxyplug-preload') . '</a>';
    }

    return $actions;
  }

  /**
   * @return void
   */
  public function oxyplug_preload_settings()
  {
    $preloads = $this->oxyplug_preload_get_option('_oxyplug_preload_preloads', array()); ?>

    <div class="oxyplug-preload-admin-page">
      <section class="oxyplug-preload-admin-head">
        <h1 class="oxyplug-preload-head-title">
          <span class="oxyplug-preload-brand-highlight">
            <?php esc_html_e('Oxyplug Preload', 'oxyplug-preload') ?>
          </span>
          <span>|</span>
          <span>
            <?php esc_html_e('Settings', 'oxyplug-preload') ?>
          </span>
        </h1>

        <div class="oxyplug-preload-need-help">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M7.59161 31.5676L8.1794 30.7586L7.59161 31.5676ZM5.93237 29.9084L6.74139 29.3206L5.93237 29.9084ZM30.0676 29.9084L29.2586 29.3206L30.0676 29.9084ZM28.4084 31.5676L27.8206 30.7586L28.4084 31.5676ZM28.4084 4.43237L28.9962 3.62336L28.4084 4.43237ZM30.0676 6.09161L29.2586 6.6794L30.0676 6.09161ZM7.59161 4.43237L8.1794 5.24139L7.59161 4.43237ZM5.93237 6.09161L6.74139 6.6794L5.93237 6.09161ZM19.7192 9.89296L19.8756 10.8807L19.7192 9.89296ZM17.3727 9.89296L17.2163 10.8807L17.3727 9.89296ZM12 23C11.4477 23 11 23.4477 11 24C11 24.5523 11.4477 25 12 25V23ZM24 25C24.5523 25 25 24.5523 25 24C25 23.4477 24.5523 23 24 23V25ZM12 17C11.4477 17 11 17.4477 11 18C11 18.5523 11.4477 19 12 19V17ZM16.5 19C17.0523 19 17.5 18.5523 17.5 18C17.5 17.4477 17.0523 17 16.5 17V19ZM30.5 16.5V19.5H32.5V16.5H30.5ZM5.5 19.5V16.5H3.5V19.5H5.5ZM18 32C15.1654 32 13.1198 31.9986 11.5336 31.8268C9.9661 31.6569 8.96626 31.3303 8.1794 30.7586L7.00383 32.3766C8.18845 33.2373 9.58051 33.6269 11.3182 33.8151C13.0371 34.0014 15.21 34 18 34V32ZM3.5 19.5C3.5 22.29 3.49863 24.4629 3.68486 26.1818C3.87313 27.9195 4.26267 29.3116 5.12336 30.4962L6.74139 29.3206C6.1697 28.5337 5.84306 27.5339 5.67323 25.9664C5.50137 24.3802 5.5 22.3346 5.5 19.5H3.5ZM8.1794 30.7586C7.62758 30.3577 7.14231 29.8724 6.74139 29.3206L5.12336 30.4962C5.64763 31.2178 6.28222 31.8524 7.00383 32.3766L8.1794 30.7586ZM30.5 19.5C30.5 22.3346 30.4986 24.3802 30.3268 25.9664C30.1569 27.5339 29.8303 28.5337 29.2586 29.3206L30.8766 30.4962C31.7373 29.3116 32.1269 27.9195 32.3151 26.1818C32.5014 24.4629 32.5 22.29 32.5 19.5H30.5ZM18 34C20.79 34 22.9629 34.0014 24.6818 33.8151C26.4195 33.6269 27.8115 33.2373 28.9962 32.3766L27.8206 30.7586C27.0337 31.3303 26.0339 31.6569 24.4664 31.8268C22.8802 31.9986 20.8346 32 18 32V34ZM29.2586 29.3206C28.8577 29.8724 28.3724 30.3577 27.8206 30.7586L28.9962 32.3766C29.7178 31.8524 30.3524 31.2178 30.8766 30.4962L29.2586 29.3206ZM32.5 16.5C32.5 13.71 32.5014 11.5371 32.3151 9.81818C32.1269 8.08051 31.7373 6.68845 30.8766 5.50383L29.2586 6.6794C29.8303 7.46626 30.1569 8.4661 30.3268 10.0336C30.4986 11.6198 30.5 13.6654 30.5 16.5H32.5ZM27.8206 5.24139C28.3724 5.64231 28.8577 6.12758 29.2586 6.6794L30.8766 5.50383C30.3524 4.78222 29.7178 4.14763 28.9962 3.62336L27.8206 5.24139ZM5.5 16.5C5.5 13.6654 5.50137 11.6198 5.67323 10.0336C5.84306 8.4661 6.1697 7.46626 6.74139 6.6794L5.12336 5.50383C4.26267 6.68845 3.87313 8.08051 3.68486 9.81818C3.49863 11.5371 3.5 13.71 3.5 16.5H5.5ZM7.00383 3.62336C6.28222 4.14763 5.64763 4.78222 5.12336 5.50383L6.74139 6.6794C7.14231 6.12758 7.62758 5.64231 8.1794 5.24139L7.00383 3.62336ZM19.5628 8.90528C18.8891 9.01198 18.2028 9.01198 17.5291 8.90528L17.2163 10.8807C18.0972 11.0202 18.9947 11.0202 19.8756 10.8807L19.5628 8.90528ZM12 25H24V23H12V25ZM12 19H16.5V17H12V19ZM26.9539 3.26967C25.0951 5.12711 23.7338 6.46779 22.5558 7.39555C21.3926 8.31159 20.4881 8.75872 19.5628 8.90528L19.8756 10.8807C21.2687 10.66 22.4884 9.99435 23.7932 8.9668C25.0831 7.95097 26.5334 6.51727 28.3676 4.68439L26.9539 3.26967ZM18 4C20.4907 4 22.3761 4.00071 23.8821 4.11906C25.3848 4.23715 26.4116 4.46712 27.2105 4.86993L28.111 3.08413C26.9722 2.50991 25.6443 2.25137 24.0388 2.1252C22.4366 1.99929 20.4605 2 18 2V4ZM27.2105 4.86993C27.4269 4.97906 27.6289 5.1021 27.8206 5.24139L28.9962 3.62336C28.7158 3.41966 28.4216 3.24078 28.111 3.08413L27.2105 4.86993ZM8.40124 4.3614C10.3389 6.29902 11.8529 7.81034 13.1857 8.87708C14.5333 9.95562 15.7832 10.6537 17.2163 10.8807L17.5291 8.90528C16.5773 8.75451 15.6476 8.28574 14.4355 7.31562C13.2086 6.33371 11.7829 4.91456 9.81542 2.94716L8.40124 4.3614ZM18 2C15.8443 2 14.0633 1.99964 12.5843 2.08338C11.1063 2.16707 9.85915 2.33736 8.78216 2.70897L9.4345 4.59959C10.2537 4.31692 11.2865 4.16007 12.6974 4.08019C14.1073 4.00036 15.824 4 18 4V2ZM8.78216 2.70897C8.1318 2.93337 7.54439 3.23061 7.00383 3.62336L8.1794 5.24139C8.54517 4.97564 8.95294 4.76575 9.4345 4.59959L8.78216 2.70897Z"
              fill="#2D2D2D"></path>
          </svg>
          <div>
            <span><?php esc_html_e('Need Help Or Have Questions?', 'oxyplug-preload') ?></span>
            <a class="oxyplug-preload-a" href="https://www.oxyplug.com/docs/oxy-preload/" target="_blank">
              <?php esc_html_e('Check our documentation.', 'oxyplug-preload') ?>
            </a>
          </div>
        </div>
      </section>

      <div class="oxyplug-preload-in-row">
        <div class="oxyplug-preload-card">
          <h2>
            <?php esc_html_e('Script Preload', 'oxyplug-preload') ?>
            <i class="dashicons dashicons-editor-help oxyplug-preload-has-tooltip"
               data-tooltip="<?php esc_attr_e('Preload scripts', 'oxyplug-preload') ?>"
               data-href="https://www.oxyplug.com/docs/oxy-preload/settings/?utm_source=plugin-settings&utm_medium=wordpress&utm_campaign=oxyplug-preload#preload-settings"
               data-href-text="<?php esc_attr_e('Learn More', 'oxyplug-preload'); ?>"></i>
          </h2>

          <?php if (empty($preloads['script'])): ?>
            <div class="oxyplug-preload-input-wrap">
              <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                      name="preloads[script][]"
                                      label="<?php esc_attr_e('Script URL', 'oxyplug-preload'); ?>"
                                      placeholder="https://www.example.com/wp-content/my-script.js"
                                      type="url">
                <md-icon-button toggle slot="trailing-icon" type="button">
                  <md-icon>cancel</md-icon>
                </md-icon-button>
              </md-outlined-text-field>
              <md-icon-button class="oxyplug-preload-remove-url"
                              toggle
                              slot="trailing-icon"
                              type="button"
                              style="display:none">
                <md-icon>delete</md-icon>
              </md-icon-button>
            </div>
          <?php else: ?>
            <?php foreach ($preloads['script'] as $index => $link): ?>
              <div class="oxyplug-preload-input-wrap">
                <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                        name="preloads[script][]"
                                        value="<?php echo esc_url($link) ?>"
                                        label="Script URL"
                                        placeholder="https://www.example.com/wp-content/my-script.js"
                                        type="url">
                  <md-icon-button toggle slot="trailing-icon" type="button">
                    <md-icon>cancel</md-icon>
                  </md-icon-button>
                </md-outlined-text-field>
                <md-icon-button class="oxyplug-preload-remove-url"
                                toggle
                                slot="trailing-icon"
                                type="button"
                  <?php if ($index == 0): ?> style="display:none" <?php endif; ?>>
                  <md-icon>delete</md-icon>
                </md-icon-button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <md-outlined-button class="oxyplug-preload-add-more">
            <?php esc_html_e('Add More Script URL', 'oxyplug-preload') ?>
          </md-outlined-button>

        </div>
        <div class="oxyplug-preload-card">
          <h2>
            <?php esc_html_e('Style Preload', 'oxyplug-preload') ?>
            <i class="dashicons dashicons-editor-help oxyplug-preload-has-tooltip"
               data-tooltip="<?php esc_attr_e('Preload scripts', 'oxyplug-preload') ?>"
               data-href="https://www.oxyplug.com/docs/oxy-preload/settings/?utm_source=plugin-settings&utm_medium=wordpress&utm_campaign=oxyplug-preload#preload-settings"
               data-href-text="<?php esc_attr_e('Learn More', 'oxyplug-preload'); ?>"></i>
          </h2>

          <?php if (empty($preloads['style'])): ?>
            <div class="oxyplug-preload-input-wrap">
              <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                      name="preloads[style][]"
                                      label="<?php esc_attr_e('Style URL', 'oxyplug-preload'); ?>"
                                      placeholder="https://www.example.com/wp-content/my-style.css"
                                      type="url">
                <md-icon-button toggle slot="trailing-icon" type="button">
                  <md-icon>cancel</md-icon>
                </md-icon-button>
              </md-outlined-text-field>
              <md-icon-button class="oxyplug-preload-remove-url"
                              toggle
                              slot="trailing-icon"
                              type="button"
                              style="display:none">
                <md-icon>delete</md-icon>
              </md-icon-button>
            </div>
          <?php else: ?>
            <?php foreach ($preloads['style'] as $index => $link): ?>
              <div class="oxyplug-preload-input-wrap">
                <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                        name="preloads[style][]"
                                        value="<?php echo esc_url($link) ?>"
                                        label="<?php esc_attr_e('Style URL', 'oxyplug-preload'); ?>"
                                        placeholder="https://www.example.com/wp-content/my-style.css"
                                        type="url">
                  <md-icon-button toggle slot="trailing-icon" type="button">
                    <md-icon>cancel</md-icon>
                  </md-icon-button>
                </md-outlined-text-field>
                <md-icon-button class="oxyplug-preload-remove-url"
                                toggle
                                slot="trailing-icon"
                                type="button"
                  <?php if ($index == 0): ?> style="display:none" <?php endif; ?>>
                  <md-icon>delete</md-icon>
                </md-icon-button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <md-outlined-button class="oxyplug-preload-add-more">
            <?php esc_html_e('Add More Style URL', 'oxyplug-preload') ?>
          </md-outlined-button>

        </div>
      </div>

      <div class="oxyplug-preload-in-row">
        <div class="oxyplug-preload-card">
          <h2>
            <?php esc_html_e('Font Preload', 'oxyplug-preload') ?>
            <i class="dashicons dashicons-editor-help oxyplug-preload-has-tooltip"
               data-tooltip="<?php esc_attr_e('Preload fonts', 'oxyplug-preload') ?>"
               data-href="https://www.oxyplug.com/docs/oxy-preload/settings/?utm_source=plugin-settings&utm_medium=wordpress&utm_campaign=oxyplug-preload#preload-settings"
               data-href-text="<?php esc_attr_e('Learn More', 'oxyplug-preload'); ?>"></i>
          </h2>

          <?php if (empty($preloads['font'])): ?>
            <div class="oxyplug-preload-input-wrap">
              <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                      name="preloads[font][]"
                                      label="<?php esc_attr_e('Font URL', 'oxyplug-preload'); ?>"
                                      placeholder="https://www.example.com/wp-content/my-font.woff2"
                                      type="url">
                <md-icon-button toggle slot="trailing-icon" type="button">
                  <md-icon>cancel</md-icon>
                </md-icon-button>
              </md-outlined-text-field>
              <md-icon-button class="oxyplug-preload-remove-url"
                              toggle
                              slot="trailing-icon"
                              type="button"
                              style="display:none">
                <md-icon>delete</md-icon>
              </md-icon-button>
            </div>
          <?php else: ?>
            <?php foreach ($preloads['font'] as $index => $link): ?>
              <div class="oxyplug-preload-input-wrap">
                <md-outlined-text-field class="oxyplug-preload-text-field has-clear-button"
                                        name="preloads[font][]"
                                        value="<?php echo esc_url($link) ?>"
                                        label="<?php esc_attr_e('Font URL', 'oxyplug-preload'); ?>"
                                        placeholder="https://www.example.com/wp-content/my-font.woff2"
                                        type="url">
                  <md-icon-button toggle slot="trailing-icon" type="button">
                    <md-icon>cancel</md-icon>
                  </md-icon-button>
                </md-outlined-text-field>
                <md-icon-button class="oxyplug-preload-remove-url"
                                toggle
                                slot="trailing-icon"
                                type="button"
                  <?php if ($index == 0): ?> style="display:none" <?php endif; ?>>
                  <md-icon>delete</md-icon>
                </md-icon-button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <md-outlined-button class="oxyplug-preload-add-more">
            <?php esc_html_e('Add More Font URL', 'oxyplug-preload') ?>
          </md-outlined-button>
        </div>
        <div class="oxyplug-preload-card oxyplug-preload-self-height">
          <h2>
            <?php esc_html_e('Featured Image Preload', 'oxyplug-preload') ?>
            <i class="dashicons dashicons-editor-help oxyplug-preload-has-tooltip"
               data-tooltip="<?php esc_attr_e('Preload featured image', 'oxyplug-preload') ?>"
               data-href="https://www.oxyplug.com/docs/oxy-preload/settings/?utm_source=plugin-settings&utm_medium=wordpress&utm_campaign=oxyplug-preload#preload-settings"
               data-href-text="<?php esc_attr_e('Learn More', 'oxyplug-preload'); ?>"></i>
          </h2>

          <div class="oxyplug-preload-switch-wrap">
            <md-switch icons
                       name="featured_image_preload" <?php if ($this->oxyplug_preload_get_option('_oxyplug_preload_featured_image', 'true') == 'true'): ?> selected <?php endif ?>></md-switch>
            <span><?php esc_html_e('Preload featured image automatically? (No need to add URLs)', 'oxyplug-preload'); ?></span>
          </div>
        </div>
      </div>

      <md-divider class="oxyplug-preload-horizontal-divider"></md-divider>

      <md-filled-button id="oxyplug-preload-save-preloads"
                        class="oxyplug-preload-has-loading"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('oxyplug_preload_save_preloads')) ?>">
        <?php esc_html_e('Save', 'oxyplug-preload'); ?>
      </md-filled-button>
      <div class="oxyplug-preload-spinner-wrap">
        <div class="oxyplug-preload-spinner"></div>
        <p><?php esc_html_e('Saving...Please wait.', 'oxyplug-preload'); ?></p>
      </div>

    </div>
  <?php }

  /**
   * @return void
   */
  public function oxyplug_preload_save_preloads()
  {
    if (!empty($_POST['oxyplug_preload_save_preloads_nonce'])) {
      $sanitized_nonce = sanitize_text_field(wp_unslash($_POST['oxyplug_preload_save_preloads_nonce']));
      if (wp_verify_nonce($sanitized_nonce, 'oxyplug_preload_save_preloads')) {

        // A nonce proves intent, not authorization — require the capability too.
        if (!current_user_can('manage_options')) {
          wp_send_json(array('messages' => array(esc_html__('Permission denied.', 'oxyplug-preload'))), 403);
        }

        // Featured image preload
        $preload_featured_image = empty($_POST['featured_image_preload']) ? 'false' : 'true';
        $this->oxyplug_preload_update_option('_oxyplug_preload_featured_image', $preload_featured_image);

        // CSS/JS/Font preload
        if (!empty($_POST['preloads']) && is_array($_POST['preloads'])) {
          $valid_links = array();
          $htaccess_preloads = array();
          $link_regex = '/^(https?:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[-a-zA-Z0-9@:%._+~#=]*)*(\?[a-zA-Z0-9=&._-]*)?)?$/';

          foreach ($_POST['preloads'] as $key => $links) {
            if (in_array((string)($key), array('script', 'style', 'font'))) {
              foreach ($links as $link) {
                $is_valid = preg_match($link_regex, $link);
                if (!empty(trim($link)) && $is_valid) {
                  $md5link = md5($link);
                  if (!isset($valid_links[$key][$md5link])) {
                    $escaped_url = esc_url($link);
                    $valid_links[$key][$md5link] = $escaped_url;

                    // Store for htaccess generation
                    if (!isset($htaccess_preloads[$key])) {
                      $htaccess_preloads[$key] = array();
                    }
                    $htaccess_preloads[$key][] = $escaped_url;
                  }
                }
              }

              if (isset($valid_links[$key])) {
                $valid_links[$key] = array_values($valid_links[$key]);
              }
            }
          }

          // Generate htaccess content
          $htaccess_content = $this->generate_htaccess_content($htaccess_preloads);

          // Add preload URLs to .htaccess file
          $write_to_htaccess_result = $this->update_htaccess($htaccess_content);
          if ($write_to_htaccess_result !== true) {
            wp_send_json(array('messages' => array($write_to_htaccess_result)), 500);
          }

          // Insert preload URLs into the database
          $this->oxyplug_preload_update_option('_oxyplug_preload_preloads', $valid_links);
        }

        wp_send_json_success(array('messages' => array(esc_html__('Successfully saved.', 'oxyplug-preload'))), 200);
      }

      wp_send_json(array('messages' => array(esc_html__('Wrong wpnonce. Refresh the page.', 'oxyplug-preload'))), 403);
    }
  }

  /**
   * Remove the `# BEGIN Oxyplug Preload ... # END Oxyplug Preload` block from
   * a chunk of .htaccess content.
   *
   * This is the single source of truth for the marker pattern, shared by
   * update_htaccess() and deactivate_it(). On a regex failure the original
   * content is returned unchanged so a corrupt match never blanks the file.
   *
   * @param string $content
   *
   * @return string
   */
  private function strip_oxyplug_section($content): string
  {
    $pattern = '/\n*# BEGIN Oxyplug Preload\n.*?# END Oxyplug Preload\n*/s';
    $stripped = preg_replace($pattern, '', $content);

    return $stripped === null ? $content : $stripped;
  }

  /**
   * Generate .htaccess content for preloading
   *
   * @param array $preloads Array of preload URLs grouped by type
   * @return string Generated .htaccess content
   */
  private function generate_htaccess_content($preloads)
  {
    $htaccess_content = '';

    // Add preload directives
    foreach ($preloads as $type => $urls) {
      foreach ($urls as $url) {
        $htaccess_content .= "      Header append Link \"<$url>; rel=preload; as=$type";
        if ($type == 'font') {
          $mime = $this->font_mime_type($url);
          if ($mime) {
            $htaccess_content .= "; type=$mime";
          }
          $htaccess_content .= '; crossorigin';
        }
        $htaccess_content .= "\"\n";
      }
    }

    if (!empty($htaccess_content)) {
      $htaccess_content =
        '  <FilesMatch "index\.(html|htm|php)$">' . "\n" .
        '    <IfModule mod_headers.c>' . "\n" .
        $htaccess_content .
        '    </IfModule>' . "\n" .
        '  </FilesMatch>' . "\n";
    }

    return $htaccess_content;
  }

  /**
   * Resolve a font URL's MIME type from its file extension.
   *
   * Declaring `type=` on a font preload lets the browser skip the hint when it
   * can't use that format, and avoids the double-fetch some browsers do when
   * the type is omitted.
   *
   * @param string $url
   *
   * @return string Empty string when the extension is unknown.
   */
  private function font_mime_type($url): string
  {
    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_EXTENSION));

    $mime_types = array(
      'woff2' => 'font/woff2',
      'woff'  => 'font/woff',
      'ttf'   => 'font/ttf',
      'otf'   => 'font/otf',
      'eot'   => 'application/vnd.ms-fontobject',
    );

    return $mime_types[$extension] ?? '';
  }

  /**
   * @param $htaccess_content
   *
   * @return string|true
   */
  private function update_htaccess($htaccess_content)
  {
    $htaccess_path = ABSPATH . '.htaccess';
    global $wp_filesystem;

    if (!$wp_filesystem) {
      require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
      require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
      $wp_filesystem = new \WP_Filesystem_Direct(null);
    }

    if (!$wp_filesystem->exists($htaccess_path) && $this->is_server(array('iis', 'nginx'))) {
      wp_send_json(array('messages' => array(esc_html__('Your server does not support .htaccess file.', 'oxyplug-preload'))), 422);
    }

    if ($wp_filesystem->is_writable($htaccess_path) && $wp_filesystem->is_readable($htaccess_path)) {
      $htaccess_backup_path = $htaccess_path . '.oxybackup';

      try {
        // Read current content
        $current_content = $wp_filesystem->get_contents($htaccess_path);
        if ($current_content === false) {
          throw new Exception(esc_html__('Could not read .htaccess file.', 'oxyplug-preload'));
        }

        // Remove existing Oxyplug Preload section
        $current_content = rtrim($this->strip_oxyplug_section($current_content));

        if (!empty($htaccess_content)) {
          // Create new section with headers
          $section = "\n\n# BEGIN Oxyplug Preload\n";
          $section .= $htaccess_content;
          $section .= "# END Oxyplug Preload\n";

          // Append the new section to the content
          $htaccess_content = $current_content . $section;
        } else {
          $htaccess_content = $current_content;
        }

        // Create backup before making changes
        if (!$wp_filesystem->copy($htaccess_path, $htaccess_backup_path, true)) {
          throw new Exception(esc_html__('Failed to create .htaccess backup file.', 'oxyplug-preload'));
        }

        // Write new content
        if (!$wp_filesystem->put_contents($htaccess_path, $htaccess_content)) {
          throw new Exception(esc_html__('Failed to write new .htaccess content.', 'oxyplug-preload'));
        }

        // Quick site check for errors or 500 status
        if (!$this->check_site_availability()) {
          throw new Exception(esc_html__('Site check failed after .htaccess update.', 'oxyplug-preload'));
        }

        // Success - clean up backup
        $wp_filesystem->delete($htaccess_backup_path);
        return true;

      } catch (Exception $e) {
        // Restore backup if available
        if ($wp_filesystem->exists($htaccess_backup_path)) {
          $wp_filesystem->copy($htaccess_backup_path, $htaccess_path, true);
          $wp_filesystem->delete($htaccess_backup_path);
        }

        return esc_html__('Oxyplug Preload .htaccess update failed: ' . $e->getMessage(), 'oxyplug-preload');
      }
    }

    return esc_html__('Oxyplug Preload .htaccess update failed.', 'oxyplug-preload');
  }

  /**
   * @return bool
   */
  private function check_site_availability(): bool
  {
    // Try REST url first (fastest)
    $rest_url = get_rest_url();
    $response = wp_remote_head($rest_url, [
      'timeout' => 5,
      'redirection' => 0,
      'sslverify' => false
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500) {
      return true;
    }

    // Fallback to admin-ajax.php
    $response = wp_remote_head(admin_url('admin-ajax.php'), [
      'timeout' => 5,
      'redirection' => 0,
      'sslverify' => false
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500) {
      return true;
    }

    // Last resort - check homepage
    $response = wp_remote_head(home_url(), [
      'timeout' => 5,
      'redirection' => 0,
      'sslverify' => false
    ]);

    return !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500;
  }

  /**
   * @param array $servers
   *
   * @return bool
   */
  private function is_server(array $servers): bool
  {
    $server = '';
    $server_software = strtolower(sanitize_text_field($_SERVER['SERVER_SOFTWARE'] ?? ''));
    if (strpos($server_software, 'apache') !== false) {
      $server = 'apache';
    } elseif (strpos($server_software, 'litespeed') !== false) {
      $server = 'litespeed';
    } elseif (strpos($server_software, 'nginx') !== false) {
      $server = 'nginx';
    } elseif (strpos($server_software, 'microsoft-iis') !== false || strpos($server_software, 'expressiondevserver') !== false) {
      $server = 'iis';
    }

    return in_array($server, $servers);
  }

  /**
   * @param $option_name
   * @param $default
   *
   * @return false|mixed|void
   */
  protected function oxyplug_preload_get_option($option_name, $default = false)
  {
    if (is_multisite()) {
      $network_id = get_current_blog_id();

      return get_network_option($network_id, $option_name, $default);
    }

    return get_option($option_name, $default);
  }

  /**
   * @param $option_name
   * @param $option_value
   *
   * @return void
   */
  protected function oxyplug_preload_update_option($option_name, $option_value): void
  {
    if (is_multisite()) {
      update_network_option(get_current_blog_id(), $option_name, $option_value);
    } else {
      update_option($option_name, $option_value);
    }
  }
}

new OxyPreload();

