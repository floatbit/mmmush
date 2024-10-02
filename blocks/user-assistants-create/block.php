<?php
/**
 * Block template file: block.php
 *
 * User Assistants - Create Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-assistants-create-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-assistants-create';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_text_field(strip_tags($_POST['title']));
    $description = sanitize_textarea_field(strip_tags($_POST['description']));

    // create assistant
    $post_data = array(
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'publish',
        'post_type'    => 'assistant',
        'post_author'  => get_current_user_id(),
    );

    $new_post_id = wp_insert_post($post_data);
    $post = get_post($new_post_id);
    
    // create assistant on openai
    $client = OpenAI::client(CHATGPT_API_KEY);

    $response = $client->assistants()->create([
        'instructions' => $instructions,
        'name' => $post->post_title,
        'tools' => [
            [
                'type' => 'file_search', 
            ],
        ],
        'model' => 'gpt-4o-mini',
    ]);
    $assistant_id = $response->id;

    // update assistant id on post
    $unique_id = uniqid();
    update_field('field_66f5b63711fc3', $assistant_id, $post->ID);
    update_field('field_66f9e904b7204', $unique_id, $post->ID);

    // create default vector store for this assistant
    $new_vector_store_id = mmmush_create_default_vector_store($assistant_id,$post);
    update_field('field_66f76eb6e5e74', [$new_vector_store_id], $post->ID);

    // create vector store on openai
    $assistant_id = get_field('assistant_id', $post->ID);
    $vector_stores = get_field('vector_stores', $post->ID);
    $instructions = $description . ' Respond to queries without including any citations, references, or text inside brackets (e.g., [source]), and without indicating source numbers or references of any kind. Do not include any concluding statements, such as offering suggestions, asking for feedback, or inviting further questions.';
    if ($vector_stores) {
        $vector_store_ids = [];
        $vector_store_ids[] = get_field('vector_store_id', $vector_stores->ID);
        $client = OpenAI::client(CHATGPT_API_KEY);
        $data  = [
            'instructions' => $instructions,
            'name' => $post->post_title,
            'tools' => [
                [
                    'type' => 'file_search', 
                ],
            ],
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => $vector_store_ids,
                ],
            ],
            'model' => 'gpt-4o-mini',
        ];
        $response = $client->assistants()->modify($assistant_id, $data);
    }
    echo "<script type='text/javascript'>window.location.href = '/user/files/create/?new=1&AssistantEmbedId=$unique_id';</script>";
    exit;
}

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1">
            <h2>Add and configure your assistant</h2>
            <form action="<?php print get_the_permalink(); ?>" method="POST" class="max-w-[720px]">
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Title/Name</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="Type here" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Description</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <textarea name="description" class="textarea textarea-bordered text-sm h-[100px] w-full" placeholder="You are a helpful assistant specializing in data analysis..."></textarea>    
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