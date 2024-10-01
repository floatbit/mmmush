<?php
/**
 * Block template file: block.php
 *
 * User Files Create Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-files-create-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-files-create';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$assistant_embed_id = get_query_var('AssistantEmbedId');

if ($assistant_embed_id) {
    $assistant = get_posts([
        'numberposts' => 1,
        'post_type'   => 'assistant',
        'meta_key'    => 'assistant_embed_id',
        'meta_value'  => $assistant_embed_id
    ])[0];
    pr($assistant);
    echo $assistant_embed_id;
}
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <h2 class="pt-[100px] pb-[100px]">
            TODO: blocks/user-files-create
        </h2>
    </div>
</div>