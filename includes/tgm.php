<?php

require_once get_template_directory() . '/includes/class-tgm.php';

add_action( 'tgmpa_register', function() {
    $plugins = [
        [
            'name'         => 'Advance Custom Fields Pro', // The plugin name.
            'slug'         => 'advanced-custom-fields-pro', // The plugin slug (typically the folder name).
            'source'       => 'https://assets.secure-staging.com/plugins/advanced-custom-fields-pro.zip', // The plugin source.
            'required'     => true, // If false, the plugin is only 'recommended' instead of required.
            'external_url' => 'https://www.advancedcustomfields.com/', // If set, overrides default API URL and points to an external URL.
        ],
        [
            'name'         => 'Admin Columns Pro',
            'slug'         => 'admin-columns-pro',
            'source'       => 'https://assets.secure-staging.com/plugins/admin-columns-pro-6-4-7.zip',
            'required'     => true,
            'external_url' => 'https://www.admincolumns.com/',
        ],
        [
            'name'         => 'ACF The Code Pro',
            'slug'         => 'acf-theme-code-pro',
            'source'       => 'https://assets.secure-staging.com/plugins/acf-theme-code-pro.zip',
            'required'     => false,
            'external_url' => 'https://hookturn.io/downloads/acf-theme-code-pro/',
        ],
        [
            'name'         => 'Gravity Form',
            'slug'         => 'gravityforms',
            'source'       => 'https://assets.secure-staging.com/plugins/gravityforms_2.8.8.zip',
            'required'     => true,
            'external_url' => 'https://gravityforms.com/',
        ],
        [
            'name' => 'Regenerate Thumbnails',
            'slug' => 'regenerate-thumbnails',
            'required' => false,
        ],
        [
            'name' => 'ACF Gravity Form Add-on',
            'slug' => 'acf-gravityforms-add-on',
            'required' => true,
        ],
    ];

    $config = [
        'id'           => 'kudos-starter',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'parent_slug'  => 'themes.php',            // Parent menu slug.
        'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
    ];

    tgmpa( $plugins, $config );
});