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

$assistant_embed_id = get_query_var('AssistantEmbedId', '');

if (empty($assistant_embed_id)) {
    print '<script>window.location.href = "/user/assistants";</script>';
}

$assistant = get_posts([
    'numberposts' => 1,
    'post_type'   => 'assistant',
    'meta_key'    => 'assistant_embed_id',
    'meta_value'  => $assistant_embed_id
])[0];

if ($assistant) {
    $vector_store = get_field('vector_stores', $assistant->ID);
    $vector_store_id = get_field('vector_store_id', $vector_store->ID);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $title = sanitize_text_field(strip_tags($_POST['title']));
        $file = $_FILES['file'];
        
        $uploaded_file = wp_handle_upload($file, ['test_form' => false]);

        if (isset($uploaded_file['file'])) {
            $file_name = basename($uploaded_file['file']);
            $file_type = wp_check_filetype($uploaded_file['file']);

            // Prepare an array of post data for the attachment.
            $attachment = [
                'guid'           => $uploaded_file['url'],
                'post_mime_type' => $file_type['type'],
                'post_title'     => $file_name,
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            // Insert the attachment.
            $attach_id = wp_insert_attachment($attachment, $uploaded_file['file']);

            // Include image.php
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Generate the metadata for the attachment, and update the database record.
            $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Create a new post of type 'file'
            $post_data = [
                'post_title'   => $title,
                'post_status'  => 'publish',
                'post_type'    => 'file',
                'post_author'  => get_current_user_id(),
            ];

            $new_post_id = wp_insert_post($post_data);

            // Update the ACF field with the attachment ID
            update_field('field_66f5c2f148394', $attach_id, $new_post_id);

            // get the file
            $file = get_field('file', $new_post_id);

            // upload file to openai
            $client = OpenAI::client(CHATGPT_API_KEY);
            $response = $client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($file['url'], 'r'),
            ]);
            $file_id = $response->id;
            // update file id
            update_field('field_66f5c2e748393', $file_id, $new_post_id);

            // add file to vector store post
            $files = get_field('files', $vector_store->ID);
            $file_ids = array($new_post_id);
            if ($files) {
                foreach ($files as $file) {
                    $file_ids[] = $file->ID;
                }
            }
            update_field('field_66f76c4f3450d', $file_ids, $vector_store->ID);

            // get files
            $files = get_field('files', $vector_store->ID);
            
            // get file ids
            $file_ids = [];
            foreach ($files as $file) {
                $file_ids[] = get_field('file_id', $file->ID);
            }
            // add the new files to the vector store
            $response = $client->vectorStores()->batches()->create($vector_store_id, [
                'file_ids' => $file_ids,
            ]);

            $upload_success = TRUE;
        }
    }
}
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">
        <div class="grid gap-x-8 grid-cols-1">

            <?php if ($upload_success) : ?>
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
                <span><em><?php print $title;?></em> added to <a href="<?php print get_the_permalink($assistant->ID); ?>">your assistant</a></span>
            </p>
            <?php endif;?>

            <h2>Add a file to <em><?php echo $assistant->post_title; ?></em></h2>
            <p>Your assistant will use this file to answer queries.</p>
            <form action="<?php print get_the_permalink(); ?>?AssistantEmbedId=<?php echo $assistant_embed_id; ?>" method="POST" class="max-w-[720px]" enctype="multipart/form-data">
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Title</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="e.g. 2024 Q1 Sales Report" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>File</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="file" name="file" class="file-input file-input-bordered w-full text-sm" accept=".pdf,.txt,.json" /> 
                        <span class="indicator-item badge">Required</span>
                    </div>
                    <div class="label">
                        <span class="label-text-alt">Accepted file types: PDF TXT JSON</span>
                        <span class="label-text-alt">Max 15MB</span>
                    </div>
                </label>
                <p>
                    <button type="submit" class="btn btn-neutral">Save</button>
                </p>
            </form>
        </div>
    </div>
</div>