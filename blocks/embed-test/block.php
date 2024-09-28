<?php
/**
 * Block template file: block.php
 *
 * Embed Test Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'embed-test-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-embed-test';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div id="mmmush-embed">
            <h3>Chat with Kudos</h3>
            <div id="mmmush-chat-container"></div>
        </div>
        <link rel="stylesheet" href="http://mmmush.localhost/embed/styles.css">
        <script src="http://mmmush.localhost/embed/thread.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOMContentLoaded')
                MMMush({
                    threadId: 'thread_yTieSiVGrw5ovoNx9RCv8tlc'
                });
            });
        </script>
    </div>
</div>