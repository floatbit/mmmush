<?php
/**
 * Block template file: block.php
 *
 * User Data Feeds Create Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-data-feeds-create-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-data-feeds-create';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$assistant_embed_id = get_query_var('AssistantEmbedId', '');

$assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">

        <ul class="steps w-full mb-10">
            <li data-content="✓" class="step step-primary">Define Assistant</li>
            <li class="step step-primary">Add Data Feed</li>
            <li class="step">Chat with Assistant</li>
        </ul>

        <div class="grid gap-x-8 grid-cols-1 max-w-[720px]">

            <h2>Add a data feed to <em><?php echo $assistant->post_title; ?></em></h2>
            <p>Your assistant will use this feed to answer queries. Only <strong>JSON</strong> files are supported at this time.</p>

            <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="user_data_feeds_create">
                <input type="hidden" name="AssistantEmbedId" value="<?php echo $assistant_embed_id; ?>" />
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Title of the file</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="title" name="title" placeholder="For example: 2023-2024 Mid-Century Collections" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Feed URL</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="feed_url" name="feed_url" placeholder="https://example.com/feed.json" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <p>
                    <button type="submit" class="btn btn-neutral">Save</button>
                </p>
            </form>
        </div>
    </div>
</div>
