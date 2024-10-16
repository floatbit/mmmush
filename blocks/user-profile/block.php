<?php
/**
 * Block template file: block.php
 *
 * User Profile Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-profile-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-profile';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1 max-w-[720px]">
            <h2>Profile</h2>
            <p>
                <?php $user = wp_get_current_user(); ?>
                <strong>Email:</strong> <?php echo $user->user_email; ?>
            </p>
            <p>
                <strong>Joined:</strong> <?php echo $user->user_registered; ?>
            </p>
        </div>
    </div>
</div>