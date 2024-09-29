<?php
/**
 * Block template file: block.php
 *
 * Threads Start Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'threads-start-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-threads-start';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$assistant_id = get_query_var('AssistantId');

if ($assistant_id) {
    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->threads()->create([]);
    $thread_id = $response->id;
    $new_post = [
        'post_type' => 'thread',
        'post_title' => $thread_id,
        'post_status' => 'publish',
    ];
    $new_post_id = wp_insert_post($new_post);
    update_field('field_66f6b0c661eca', $thread_id, $new_post_id);
    update_field('field_66f6b0dc59647', $assistant_id, $new_post_id);
    update_field('field_66f855bf7b16e', uniqid(), $new_post_id);
    $redirect_url = get_the_permalink($new_post_id);
}
?>

<script type="text/javascript">
    window.location.href = "<?php echo esc_url($redirect_url); ?>";
</script>
