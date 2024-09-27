<?php

/**
 * Laravel Mix Assets helper
 */
function assets_url($path)
{
    $theme_dir = get_template_directory();

    if (file_exists($theme_dir . '/hot')) {
        // $url = file_get_contents($theme_dir . '/hot');

        return "//localhost:8080{$path}";
    }

    $manifestPath = $theme_dir . '/mix-manifest.json';
    $manifest = json_decode(file_get_contents($manifestPath), true);
    
    if (isset($manifest[$path])) {
        return get_template_directory_uri() . $manifest[$path];
    } else {
        return get_template_directory_uri() . $path;
    }
}
