<?php

/**
 * Plugin Name: WP Delete Products
 * Plugin URI: https://example.com
 * Description: Delete all WooCommerce products.
 * Version: 1.0
 * Author: Amdadul Haq
 * Author URI: https://amdadulhaq.com
 * License: GPL2
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scripts and styles.
 */
function wp_delete_products_enqueue_scripts()
{
    wp_enqueue_script('wp-delete-products', plugin_dir_url(__FILE__) . 'js/wp-delete-products.js', array('jquery'), '1.0', true);
    wp_localize_script(
        'wp-delete-products',
        'wp_delete_products',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-delete-products-nonce'),
            'confirm' => __('Are you sure you want to delete all products?', 'wp-delete-products'),
            'progress' => __('Deleting products... %s%%', 'wp-delete-products'),
            'done' => __('All products deleted!', 'wp-delete-products'),
            'error' => __('An error occurred while deleting products.', 'wp-delete-products'),
        )
    );
}
add_action('admin_enqueue_scripts', 'wp_delete_products_enqueue_scripts');

function wp_delete_products_enqueue_styles()
{
    wp_enqueue_style('wp-delete-products', plugin_dir_url(__FILE__) . 'css/wp-delete-products.css', array(), '1.0');
}
add_action('admin_enqueue_scripts', 'wp_delete_products_enqueue_styles');

/**
 * Register the delete products page.
 */
function wp_delete_products_register_page()
{
    add_submenu_page(
        'edit.php?post_type=product',
        __('Delete Products', 'wp-delete-products'),
        __('Delete Products', 'wp-delete-products'),
        'manage_options',
        'wp-delete-products',
        'wp_delete_products_page'
    );
}
add_action('admin_menu', 'wp_delete_products_register_page');

/**
 * Render the delete products page.
 */
function wp_delete_products_page()
{
?>
    <div class="wrap">
        <h1><?php _e('Delete Products', 'wp-delete-products'); ?></h1>
        <p><?php _e('Click the button below to delete all WooCommerce products.', 'wp-delete-products'); ?></p>
        <p>
            <button id="wp-delete-products-button" class="button button-primary">
                <?php _e('Delete All Products', 'wp-delete-products'); ?>
            </button>
        </p>
        <div class="wp-delete-products-progress">
            <div class="wp-delete-products-progress-bar"></div>
            <div class="wp-delete-products-progress-label">0.00%</div>
        </div>
        <input type="hidden" id="wp-delete-products-count" value="<?php echo wp_count_posts('product')->publish; ?>">
    </div>
<?php
}

/**
 * Delete products by chunk.
 */
function wp_delete_products_chunk()
{
    check_ajax_referer('wp-delete-products-nonce', 'nonce');

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100;

    $products = wc_get_products(array(
        'status' => 'publish',
        'limit' => $limit,
        'offset' => $offset,
        'return' => 'ids',
    ));

    if (empty($products)) {
        wp_send_json_success(array('done' => true));
    }

    foreach ($products as $product_id) {
        wp_delete_post($product_id, true);
    }

    $count = wp_count_posts('product')->publish;

    if ($count > 0) {
        $percent = ($offset + $limit) / $count * 100;
    } else {
        $percent = 100;
    }

    wp_send_json_success(array('percent' => round($percent, 2)));
}
add_action('wp_ajax_wp_delete_products_chunk', 'wp_delete_products_chunk');
