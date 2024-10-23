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

$new = get_query_var('new', 0);

$assistant_embed_id = get_query_var('AssistantEmbedId', '');

if (empty($assistant_embed_id)) {
    print '<script>window.location.href = "/user/assistants";</script>';
}

$assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">

        <ul class="steps w-full mb-10">
            <li data-content="✓" class="step step-primary">Define Assistant</li>
            <li class="step step-primary">Add Files</li>
            <li class="step">Chat with Assistant</li>
        </ul>

        <div class="grid gap-x-8 grid-cols-1 max-w-[720px]">

            <?php if ($new == 1) : ?>
                <h2>Hello, <em><?php echo $assistant->post_title; ?></em></h2>
                <p>In this step, you’ll upload files that your assistant will use to provide accurate and detailed responses to user queries. These documents contain valuable information, context, and data that equip the assistant to deliver tailored insights based on the specific expertise you’ve defined.</p>
                <p>By uploading comprehensive files, you enhance the assistant’s ability to assist users effectively. The more relevant and informative the materials, the better the assistant can draw from its knowledge base, ensuring that interactions are both insightful and engaging.</p>
            <?php else : ?>
                <h2>Add a file to <em><?php echo $assistant->post_title; ?></em></h2>
                <p>Your assistant will use this file to answer queries.</p>
            <?php endif; ?>

            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="user_files_create">
                <input type="hidden" name="AssistantEmbedId" value="<?php echo $assistant_embed_id; ?>" />
                <input type="hidden" name="new" value="<?php echo $new; ?>" />
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Select File</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="file" name="file" class="file-input file-input-bordered w-full text-sm" accept=".pdf,.txt,.json" /> 
                        <span class="indicator-item badge hidden">Required</span>
                    </div>
                    <div class="label">
                        <span class="label-text-alt">Accepted file types: pdf json txt</span>
                        <span class="label-text-alt">Max 15MB</span>
                    </div>
                </label>
                <label class="form-control w-full mt-5 mb-10 hidden">
                    <div class="label">
                        <span class="label-text"><strong>Title of the file</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="For example: 2023-2024 Mid-Century Collections" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">optional</span>
                    </div>
                </label>
                <p>
                    <button type="submit" class="btn btn-neutral">Upload</button>
                </p>
            </form>
        </div>
    </div>
</div>
