<?php
/**
 * Block template file: block.php
 *
 * User Assistants - Edit Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-assistants-edit-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-assistants-edit';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$assistant_embed_id = get_query_var('AssistantEmbedId', '');

if (empty($assistant_embed_id)) {
    print '<script>window.location.href = "/user/assistants";</script>';
}

$assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);

if ($assistant) {
    $title = $assistant->post_title;
    $description = $assistant->post_content;
    $assistant_id = get_field('assistant_id', $assistant->ID);
}

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1">

            <h2>Edit your assistant</h2>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" class="max-w-[720px]">
                <input type="hidden" name="action" value="user_assistants_edit">
                <input type="hidden" name="AssistantEmbedId" value="<?php echo $assistant_embed_id; ?>" />
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Title/Name</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="Type here" value="<?php print $title; ?>" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Description</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <textarea name="description" class="textarea textarea-bordered text-sm h-[300px] w-full" placeholder="You are a helpful assistant specializing in data analysis..."><?php print $description; ?></textarea>    
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
