<?php

/**
 * Add theme support
 */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('menus');
    add_theme_support('editor-styles');
    add_editor_style('dist/editor.css');

    register_nav_menus([
        'main_navigation' => __('Main Navigation'),
    ]);

    // Add ACF options page
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'Global Options',
            'menu_slug' => 'global-options',
        ]);
    }
});

/**
 * Enqueue script and styles
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('app', assets_url('/dist/app.css'), [], null);
    wp_enqueue_script('app', assets_url('/dist/app.js'), ['jquery'], null, true);

    // Register script for blocks
    // If needed, separate the script per block
    wp_register_script('blocks/text', assets_url('/dist/blocks/text.js'), ['jquery'], null, true);
});

add_filter( 'query_vars', 'mmmush_query_vars' );
function mmmush_query_vars( $query_vars ){
    $query_vars[] = 'AssistantId';
    return $query_vars;
}

/**
 * Disable Gutenberg for specific post types
 */
function mmmush_disable_gutenberg_for_post_types($can_edit, $post_type) {
    // Add the post types you want to disable Gutenberg for
    $disabled_post_types = array('assistant', 'file', 'vector-store');

    if (in_array($post_type, $disabled_post_types)) {
        return false;
    }

    return $can_edit;
}
add_filter('use_block_editor_for_post_type', 'mmmush_disable_gutenberg_for_post_types', 10, 2);


function pr($array) {
    echo '<pre>';
    print_r($array);
    echo '</pre>';
}

function pd($array) {
    pr($array);
    die();
}   

add_action('template_redirect', function() {
    // user not logged in
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
});

// Disable sitemap
add_filter( 'wp_sitemaps_enabled', '__return_false' );

function mmmush_debug($input) {
    $log_file = $_SERVER['DOCUMENT_ROOT'] . '/logs/' . date('Y-m-d') . '.log';
    $log_message = is_string($input) ? $input : print_r($input, true);
    $log_message = "\n==================\n\n" . $log_message;;
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}
