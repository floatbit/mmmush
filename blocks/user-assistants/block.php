<?php
/**
 * Block template file: block.php
 *
 * User Assistants Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-assistants-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-assistants';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

// get assistants
$assistants = get_posts([
    'post_type' => 'assistant',
    'numberposts' => -1,
    'author' => get_current_user_id(),
]);

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 gap-y-12 grid-cols-3">
            <?php foreach ($assistants as $assistant) : ?>
            <?php 
                $vector_store = get_field('vector_stores', $assistant->ID);
                $files = get_field('files', $vector_store->ID);
            ?>
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mt-0"><?php echo $assistant->post_title; ?></h2>
                    <p class="font-normal text-gray-500"><?php echo $assistant->post_content; ?></p>
                    <div>
                        <p class="hidden"><span class="font-bold">Binder</span><br> <?php echo $vector_store->post_title; ?></p>
                        <p><span class="font-bold">Files</span><br>
                        <?php foreach ($files as $file) : ?>
                            <?php $the_file = get_field('file', $file->ID); ?>
                            <a href="<?php echo $the_file['url']; ?>" target="_blank">
                                <?php echo $the_file['title']; ?>
                            </a><br>
                        <?php endforeach; ?>
                        </p>
                    </div>
                    <div class="card-actions justify-end">
                        <a class="btn btn-info btn-sm" href="<?php echo get_the_permalink($assistant->ID); ?>">CHAT</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body items-center justify-center">
                    <p class="text-3xl card-title">
                        <a class="btn btn-lg " href="/user/assistants/create">+ Create New Assistant</a>
                    </p>                    
                </div>
            </div>
        </div>

        <table class="table hidden">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Binder</th>
                    <th>Files</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assistants as $assistant) : ?>
                <?php 
                    $assistant_id = get_field('assistant_id', $assistant->ID);
                ?>
                <tr>
                    <th>
                        <p><?php echo $assistant->post_title; ?></p>
                        <p class="font-normal text-gray-500"><?php echo $assistant->post_content; ?></p>
                    </th>
                    <th>
                        <?php 
                            $vector_store = get_field('vector_stores', $assistant->ID);
                        ?>
                        <p class="whitespace-nowrap mt-0"><?php echo $vector_store->post_title; ?></p>
                    </th>
                    <th>
                        <?php 
                            $files = get_field('files', $vector_store->ID);
                        ?>
                        <p class="nowrap">
                            <?php foreach ($files as $file) : ?>
                                <?php $the_file = get_field('file', $file->ID); ?>

                                    <a class="btn btn-ghost btn-xs" href="<?php echo $the_file['url']; ?>" target="_blank">
                                        <?php echo $the_file['title']; ?>
                                    </a>

                            <?php endforeach; ?>
                        </p>
                    </th>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>