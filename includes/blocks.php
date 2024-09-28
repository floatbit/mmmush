<?php

define('CUSTOM_BLOCKS', [
    'page' => [
        'text',
        'assistants',
        'assistants-list',
        'assistants-retrieve',
        'files',
        'files-list',
        'threads',
        'threads-list',
        'threads-start',
        'chat',
        'embed-test'
    ],
    'post' => [
        'text',
    ],
]);

$allowed_core_blocks = [
    // Add allowed core blocks here
    // 'post' => [
    //     'core/paragraph',
    // ],
];

/**
 * Register blocks
 */
add_action('init', function () {
    $dir = get_template_directory();
    $block_list = array_unique(array_merge(...array_values(CUSTOM_BLOCKS)));

    foreach ($block_list as $block) {
        register_block_type($dir . '/blocks/' . $block);
    }
}, 5);

/**
 * Set allowed blocks
 */
add_filter('allowed_block_types_all', function($allowed_blocks, $editor_context) use ($allowed_core_blocks) {
    if ($editor_context->post) {
        $current_post_type = $editor_context->post->post_type;
        foreach (CUSTOM_BLOCKS as $post_type => $block_list) {
            if ($current_post_type == $post_type) {
                $allowed_blocks = [];
                foreach ($block_list as $block) {
                    $allowed_blocks[] = 'acf/' . $block;
                }

                if (isset($allowed_core_blocks[$post_type])) {
                    $allowed_blocks = [
                        ...$allowed_blocks,
                        ...$allowed_core_blocks[$post_type],
                    ];
                }
            }

        }
    }

    return $allowed_blocks;
}, 10, 2);
