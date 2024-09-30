<?php
/**
 * Block template file: block.php
 *
 * Assistants Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'assistants-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-assistants';
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
    <div class="container prose">
        <h1><?php print uniqid(); ?></h1>
        <h2 class="pt-[100px] pb-[50px]">
            Who to Chat With?
        </h2>
        <?php foreach ($response->data as $assistant) : ?>
            <div class="assistant">
                <h3>
                    <a href="/assistants/retrieve?AssistantId=<?php echo $assistant->id; ?>"><?php echo $assistant->name; ?></a>
                </h3>
                <p><?php echo $assistant->instructions; ?></p>
                <p>
                    <a href="/threads/start?AssistantId=<?php echo $assistant->id; ?>">Start Chat &#8594;</a>
                </p>
            </div>
            <hr class="my-10">
        <?php endforeach; ?>
    </div>
</div>