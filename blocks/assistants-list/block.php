<?php
/**
 * Block template file: block.php
 *
 * Assistants List Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'assistants-list-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-assistants-list';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$client = OpenAI::client(CHATGPT_API_KEY);

$response = $client->assistants()->list([
    'limit' => 50,
]);


?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <h2 class="pt-[100px] pb-[100px]">
            TODO: blocks/assistants-list
        </h2>

        <?php foreach ($response->data as $assistant) : ?>
            <div class="assistant">
                <h3>
                    <a href="/assistants/retrieve?AssistantId=<?php echo $assistant->id; ?>"><?php echo $assistant->name; ?></a>
                </h3>
                <p><?php echo $assistant->instructions; ?></p>
            </div>
            <hr>
        <?php endforeach; ?>
    </div>
</div>