<?php
global $post;
    
$breadcrumbs = array(); // Initialize an empty array to store the links

// Check if the post has any parent pages
if ($post->post_parent) {
    // Get all the parent pages
    $parents = get_post_ancestors($post->ID);
    $parents = array_reverse($parents); // Reverse so the oldest parent is first

    // Loop through each parent page
    foreach ($parents as $parent) {
        // Get the parent page details
        $parent_page = get_post($parent);
        // Add each parent link to the breadcrumb array
        $breadcrumbs[] = '<a href="' . get_permalink($parent_page->ID) . '">' . get_the_title($parent_page->ID) . '</a>';
    }
}

// Add the current page title without a link
$breadcrumbs[] = get_the_title($post->ID);

if (strstr($breadcrumbs[0], 'User')) {
    unset($breadcrumbs[0]);
}

?>
<div class="container container-fluid bg-base-100 py-2 border-b border-gray-200">
    <div class="breadcrumbs text-md">
        <ul>
            <li>
                <a href="/">Home</a>
            </li>
            <?php if (is_front_page()) : ?>
                <li>
                    Assistants
                </li>
            <?php elseif (is_single() && get_post_type() == 'assistant') : ?>
                <li>
                    <a href="/user/assistants">Assistants</a>
                </li>
                <li><?php the_title(); ?></li>
            <?php else:?>
                <?php foreach ($breadcrumbs as $breadcrumb) : ?>
                    <li><?php echo $breadcrumb; ?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
