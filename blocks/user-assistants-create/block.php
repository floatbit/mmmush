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

$new = get_query_var('new', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_text_field(strip_tags($_POST['title']));
    $description = sanitize_textarea_field(strip_tags($_POST['description']));

    if ($title && $description) {
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
            'name' => $post->post_title,
            'instructions' => $instructions,
            'temperature' => 0.5,
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
        $instructions = $description . PHP_EOL . PHP_EOL . MMUSH_FIXED_INSTRUCTIONS;
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
        echo "<script type='text/javascript'>window.location.href = '/user/files/create/?new=$new&AssistantEmbedId=$unique_id';</script>";
        exit;
    }
}

?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1 max-w-[720px]">
            <?php if ($new == 1) : ?>
                <h2>Welcome! Let's create your first assistant.</h2>
                <p>An assistant is a personalized AI that answers your questions and provides insights from a specific perspective or area of expertise. It embodies the persona you define, responding to your queries with the knowledge and mindset of an expert in that field. You can shape the assistant’s personality—whether you want it to be friendly, serious, or sarcastic, the tone of its responses will match the style you choose.</p>
                <p>To get the most accurate and relevant answers, think about the type of expert you need. Define their role and knowledge base so that the assistant can respond effectively from that persona’s perspective.</p>
            <?php else : ?>
                <h2>Add a new assistant</h2>
            <?php endif; ?>
            <form action="<?php print get_the_permalink(); ?>?new=<?php print $new; ?>" method="POST">
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Assistant's name</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="For example: Chief Curator" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Describe your assistant</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <textarea name="description" class="textarea textarea-bordered text-sm h-[300px] w-full" placeholder="For example: You are the chief curator of a world-renowned museum. You curate art collections, provide historical context on artworks, and manage exhibition logistics. Using your museum’s extensive data files, you offer detailed insights into specific pieces, artists, and historical periods. Respond to questions about art history, artists, exhibit planning, and your museum’s collection."></textarea>    
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <p>
                    <button type="submit" class="btn btn-neutral">Save</button>
                </p>
            </form>
        </div>
    </div>

    <dialog id="my_modal_1" className="modal">
  <div className="modal-box">
    <h3 className="font-bold text-lg">Hello!</h3>
    <p className="py-4">Press ESC key or click the button below to close</p>
    <div className="modal-action">
      <form method="dialog">
        {/* if there is a button in form, it will close the modal */}
        <button className="btn">Close</button>
      </form>
    </div>
  </div>
</dialog>
</div>
