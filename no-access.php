<?php
/**
 * Plugin Name: Limited Access
 * Description: Prevent access to specific pages for users depending on their capabilities.
 * Version: 1.0
 * Author: Luis Cardenas | WPMUDEV
 * Author URI: https://wpmudev.com/
 * License: GPL2
 */

// Add the 'Limited Access' settings page to the admin dashboard
function lr_admin_menu() {
    add_options_page('Limited Access', 'Limited Access', 'manage_options', 'limited-access', 'lr_settings_page');
}
add_action('admin_menu', 'lr_admin_menu');

// Display the 'Limited Access' settings page
function lr_settings_page() {
    ?>
    <div class="wrap">
        <h1>Limited Access</h1>
        <p>Prevent access to specific pages based on user capabilities.</p>
        <form action="options.php" method="post">
            <?php
            settings_fields('limited_access_options');
            do_settings_sections('limited_access');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the 'Limited Access' settings
function lr_settings_init() {
    register_setting('limited_access_options', 'blocked_pages');
    add_settings_section('limited_access_main', 'Main Settings', null, 'limited_access');

    add_settings_field('blocked_pages', 'Select Blocked Pages', 'display_pages', 'limited_access', 'limited_access_main');
    add_settings_field('no_access_page', 'Select No Access Page', 'display_options', 'limited_access', 'limited_access_main');
}
add_action('admin_init', 'lr_settings_init');

// Callback function to display the pages in the settings page
function display_pages() {
    $options = get_option('blocked_pages');
    $blocked_pages = $options['pages'];

    echo "<select name='blocked_pages[pages][]' id='blocked_pages' multiple='multiple'>";
    echo "<option value='0'>-- Select --</option>";

    $pages = get_pages();
    foreach ($pages as $page) {
        echo "<option value='" . esc_attr($page->ID) . "' " . (in_array($page->ID, (array) $blocked_pages) ? 'selected' : '') . ">" . esc_html($page->post_title) . "</option>";
    }

    echo "</select>";
    // Add instruction text below the Select Blocked Pages module
    echo "<p class='description'>Select the desired pages to be locked. You can use <code>Ctrl + Click</code> to add multiple pages to your selection.</p>";
}

function display_options() {
    $options = get_option('blocked_pages');
    $blocked_pages = $options['pages'];
    $no_access_page_id = $options['no_access_page'];
    $wp_login_option_value = '-1';

    echo "<select name='blocked_pages[no_access_page]' id='no_access_page'>";
    echo "<option value='0' " . selected($no_access_page_id, 0, false) . ">-- Select --</option>";

    // Add WP Login option
    echo "<option value='" . esc_attr($wp_login_option_value) . "' " . selected($no_access_page_id, $wp_login_option_value, false) . ">wp-login.php</option>";

    $pages = get_pages();
    foreach ($pages as $page) {
        echo "<option value='" . esc_attr($page->ID) . "' " . selected($no_access_page_id, $page->ID, false) . ">" . esc_html($page->post_title) . "</option>";
    }

    echo "</select>";
}

function lr_save_redirect_url() {
    $options = get_option('blocked_pages');
    $blocked_pages = $options['pages'];
    $no_access_page_id = $options['no_access_page'];
    $wp_login_option_value = '-1';

    if (is_singular()) {
        $current_post_id = get_the_ID();

        if (in_array($current_post_id, $blocked_pages)) {
            // If the user is not logged in
            if (!is_user_logged_in()) {
                // Redirect to WP Login page
                if ($no_access_page_id == $wp_login_option_value) {
                    // Get the current page URL to use as the 'redirect_to' parameter
                    $current_page_url = home_url(add_query_arg(null, null));
                    $login_url = wp_login_url($current_page_url);
                    wp_redirect($login_url);
                    exit;
                } else { // Redirect to selected 'No Access' page
                    wp_redirect(get_permalink($no_access_page_id));
                    exit;
                }
            } else {
                // If logged in, check for necessary capabilities
                if (!current_user_can('read_post', $current_post_id) || !current_user_can('publish_posts')) {
                    wp_redirect(get_permalink($no_access_page_id));
                    exit;
                }
            }
        }
    }
}
add_action('template_redirect', 'lr_save_redirect_url');

?>
