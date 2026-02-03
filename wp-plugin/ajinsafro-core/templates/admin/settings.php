<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('ajinsafro_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="laravel_base_url"><?php _e('Laravel Base URL', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="laravel_base_url" 
                           name="laravel_base_url" 
                           value="<?php echo esc_attr($options['laravel_base_url']); ?>" 
                           class="regular-text" 
                           placeholder="https://booking.ajinsafro.net">
                    <p class="description">
                        <?php _e('Laravel API base URL (without trailing slash)', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="booking_checkout_base_url"><?php _e('Checkout Base URL', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="booking_checkout_base_url" 
                           name="booking_checkout_base_url" 
                           value="<?php echo esc_attr($options['booking_checkout_base_url']); ?>" 
                           class="regular-text" 
                           placeholder="https://booking.ajinsafro.net">
                    <p class="description">
                        <?php _e('Checkout page base URL (without trailing slash)', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="hmac_secret"><?php _e('HMAC Secret', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="hmac_secret" 
                           name="hmac_secret" 
                           value="<?php echo esc_attr($options['hmac_secret']); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('Secret key for sync signature verification (must match Laravel)', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="enable_sync"><?php _e('Enable Sync', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="enable_sync" 
                               name="enable_sync" 
                               value="1" 
                               <?php checked($options['enable_sync'], true); ?>>
                        <?php _e('Enable Laravel to WordPress sync endpoint', 'ajinsafro-core'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cache_ttl_seconds"><?php _e('Cache TTL (seconds)', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="cache_ttl_seconds" 
                           name="cache_ttl_seconds" 
                           value="<?php echo esc_attr($options['cache_ttl_seconds']); ?>" 
                           min="0" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Cache duration for package state (0 to disable)', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php _e('Package Builder Display', 'ajinsafro-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="auto_inject_builder"><?php _e('Auto-inject Package Builder', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="auto_inject_builder" 
                               name="auto_inject_builder" 
                               value="1" 
                               <?php checked($options['auto_inject_builder'] ?? true, true); ?>>
                        <?php _e('Automatically display Package Builder on tour pages', 'ajinsafro-core'); ?>
                    </label>
                    <p class="description">
                        <?php _e('When enabled, the Package Builder will be automatically added to single tour pages without needing the shortcode.', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="auto_inject_position"><?php _e('Auto-inject Position', 'ajinsafro-core'); ?></label>
                </th>
                <td>
                    <select id="auto_inject_position" name="auto_inject_position">
                        <option value="after" <?php selected($options['auto_inject_position'] ?? 'after', 'after'); ?>>
                            <?php _e('After content', 'ajinsafro-core'); ?>
                        </option>
                        <option value="before" <?php selected($options['auto_inject_position'] ?? 'after', 'before'); ?>>
                            <?php _e('Before content', 'ajinsafro-core'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose where to display the Package Builder relative to the tour content', 'ajinsafro-core'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" 
                   name="ajinsafro_save_settings" 
                   class="button-primary" 
                   value="<?php esc_attr_e('Save Settings', 'ajinsafro-core'); ?>">
        </p>
    </form>

    <hr>

    <h2><?php _e('Usage', 'ajinsafro-core'); ?></h2>
    <p>
        <?php _e('The Package Builder can be displayed in two ways:', 'ajinsafro-core'); ?>
    </p>
    <ol>
        <li>
            <strong><?php _e('Auto-injection (Recommended)', 'ajinsafro-core'); ?>:</strong>
            <?php _e('Enable "Auto-inject Package Builder" above and it will automatically appear on all tour pages.', 'ajinsafro-core'); ?>
        </li>
        <li>
            <strong><?php _e('Manual shortcode', 'ajinsafro-core'); ?>:</strong>
            <?php _e('Add the shortcode manually in your tour content:', 'ajinsafro-core'); ?>
            <code>[aj_package_builder]</code>
        </li>
    </ol>
    <p class="description">
        <?php _e('Note: If auto-injection is enabled and you also add the shortcode manually, the plugin will prevent duplicate display.', 'ajinsafro-core'); ?>
    </p>

    <h3><?php _e('Sync Endpoint', 'ajinsafro-core'); ?></h3>
    <p>
        <strong><?php _e('REST API URL:', 'ajinsafro-core'); ?></strong><br>
        <code><?php echo esc_url(rest_url('ajinsafro-sync/v1/laravel-to-wp')); ?></code>
    </p>
    <p>
        <?php _e('Use this endpoint in Laravel to sync tours from booking system to WordPress.', 'ajinsafro-core'); ?>
    </p>
</div>
