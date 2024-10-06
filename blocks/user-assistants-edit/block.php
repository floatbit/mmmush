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

$assistant = get_posts([
    'numberposts' => 1,
    'post_type'   => 'assistant',
    'meta_key'    => 'assistant_embed_id',
    'meta_value'  => $assistant_embed_id,
    'author'      => get_current_user_id()
])[0];

if ($assistant) {
    $title = $assistant->post_title;
    $description = $assistant->post_content;
    $assistant_id = get_field('assistant_id', $assistant->ID);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_text_field(strip_tags($_POST['title']));
    $description = sanitize_textarea_field(strip_tags($_POST['description']));

    // update assistant in wp
    wp_update_post([
        'ID'           => $assistant->ID,
        'post_title'   => $title,
        'post_content' => $description,
    ]);

    // update assistant in openai
    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->assistants()->modify($assistant_id, [
        'name' => $title,
        'instructions' => $description . MMUSH_FIXED_INSTRUCTIONS,
        'temperature' => 0.5,
    ]);

    print '<script>window.location.href = "/user/assistants/edit/?AssistantEmbedId=' . $assistant_embed_id . '&updated=1";</script>';
}
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1">
            <?php if (isset($_GET['updated']) && $_GET['updated'] == 1) : ?>
            <p role="alert" class="alert alert-success">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 shrink-0 stroke-current"
                    fill="none"
                    viewBox="0 0 24 24">
                    <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><em><a href="<?php print get_the_permalink($assistant->ID); ?>?<?php print $assistant_embed_id; ?>"><?php print $title;?></a></em> updated</span>
            </p>
            <?php endif;?>
            <h2>Edit your assistant</h2>
            <form action="<?php print get_the_permalink(); ?>?AssistantEmbedId=<?php print $assistant_embed_id; ?>" method="POST" class="max-w-[720px]">
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