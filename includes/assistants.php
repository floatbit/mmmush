<?php

// Require the OpenAI PHP client
require_once get_template_directory() . '/vendor/autoload.php';

// Include the secrets file
require_once get_template_directory() . '/includes/secrets.php';

// Assistant post actions for new and updated posts
function mmmush_handle_wp_insert_post($post_id, $post, $update) {
    if ($post->post_status === 'publish') {
        if ($post->post_type === 'assistant') {    
            $assistant_id = get_field('assistant_id', $post->ID);
            $instructions = strip_tags($post->post_content);

            if (empty($instructions)) {
                $instructions = 'Respond to queries without including any citations, references, or text inside brackets (e.g., [source]), and without indicating source numbers or references of any kind. Do not include any concluding statements, such as offering suggestions, asking for feedback, or inviting further questions.';
            }

            if ($update && $assistant_id) {
                $client = OpenAI::client(CHATGPT_API_KEY);
                $client->assistants()->modify($assistant_id, [
                    'name' => $post->post_title,
                    'instructions' => $instructions,
                ]);
            } else {
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
                update_field('assistant_id', $assistant_id, $post->ID);
            }

            // create default thread
            $existing_threads = get_posts(array(    
                'numberposts' => -1,
                'post_type'   => 'thread',
                'meta_key'    => 'assistant_id',
                'meta_value'  => $assistant_id
            ));

            if (count($existing_threads) === 0) {
                mmmush_create_default_thread($assistant_id, $post);
            }

            // TODO: create default vector store
            $existing_vector_stores = get_posts(array(    
                'numberposts' => -1,
                'post_type'   => 'vector-store',
                'meta_key'    => 'assistant_id',
                'meta_value'  => $assistant_id
            ));

            if (count($existing_vector_stores) === 0) {
                $new_vector_store_id = mmmush_create_default_vector_store($assistant_id,$post);
                update_field('field_66f76eb6e5e74', [$new_vector_store_id], $post->ID);
            }

            // vector stores
            $assistant_id = get_field('assistant_id', $post->ID);
            $vector_stores = get_field('vector_stores', $post->ID);
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

        }
    }

    if ($post->post_status === 'publish' && $update === true) {
        if ($post->post_type === 'vector-store') {
            $client = OpenAI::client(CHATGPT_API_KEY);
            $vector_store_id = get_field('vector_store_id', $post->ID);
            if (empty($vector_store_id)) {
                $response = $client->vectorStores()->create([
                    'name' => $post->post_title,
                ]);
                update_field('field_66f5cb66169b5', $response->id, $post->ID);
            }
            // put files in the vector store
            $files = get_field('files', $post->ID);
            if ($files) {
                // store file ids
                $file_ids = [];
                foreach ($files as $file) {
                    $file_ids[] = get_field('file_id', $file->ID);
                }
                // first delete all files in the vector store
                $response = $client->vectorStores()->files()->list($vector_store_id);   
                if (count($response->data) > 0) {
                    foreach ($response->data as $file) {
                        if (!in_array($file->id, $file_ids)) {
                            $client->vectorStores()->files()->delete($vector_store_id, $file->id);
                        }
                    }
                }
                // add the new files
                $response = $client->vectorStores()->batches()->create($vector_store_id, [
                    'file_ids' => $file_ids,
                ]);
            }
        }
    }

    if ($post->post_status === 'publish') {
        if ($post->post_type === 'file') {
            // create the file
            $file = get_field('file', $post->ID);
            $client = OpenAI::client(CHATGPT_API_KEY);
            $response = $client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($file['url'], 'r'),
            ]);
            // update file id
            update_field('field_66f5c2e748393', $response->id, $post->ID);
        }
    }

}
add_action('wp_insert_post', 'mmmush_handle_wp_insert_post', 10, 3);

// Delete assistant when post is trashed
add_action('wp_trash_post', function($post_id) {
    $post = get_post($post_id);
    if ($post->post_type === 'assistant') {
        $assistant_id = get_field('assistant_id', $post_id);
        if ($assistant_id) {
            $client = OpenAI::client(CHATGPT_API_KEY);
            $client->assistants()->delete($assistant_id);
        }
    }

    if ($post->post_type === 'file') {
        $file_id = get_field('file_id', $post_id);
        if ($file_id) {
            $client = OpenAI::client(CHATGPT_API_KEY);
            $client->files()->delete($file_id);
        }
    }

    if ($post->post_type === 'thread') {
        $thread_id = get_field('thread_id', $post_id);
        if ($thread_id) {
            $client = OpenAI::client(CHATGPT_API_KEY);
            $response = $client->threads()->delete($thread_id);
        }
    }

    if ($post->post_type === 'vector-store') {
        $vector_store_id = get_field('vector_store_id', $post_id);
        if ($vector_store_id) {
            $client = OpenAI::client(CHATGPT_API_KEY);
            $client->vectorStores()->delete($vector_store_id);
        }
    }
});

add_action('wp_ajax_send_message', 'handle_send_message');
add_action('wp_ajax_nopriv_send_message', 'handle_send_message');

function handle_send_message() {
    // Ensure output buffering is turned off
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Get the POST data
    $thread_id = $_POST['ThreadId'];
    $assistant_id = $_POST['AssistantId'];
    $message = $_POST['message'];

    // Create OpenAI client
    $client = OpenAI::client(CHATGPT_API_KEY);

    // Create a message in the thread
    $client->threads()->messages()->create($thread_id, [
        'role' => 'user',
        'content' => $message,
    ]);

    // Create and start a run
    $run = $client->threads()->runs()->create($thread_id, [
        'assistant_id' => $assistant_id,
    ]);

    // Poll for run completion and stream the results
    while (true) {
        $run = $client->threads()->runs()->retrieve($thread_id, $run->id);
        mmmush_debug($run);
        if ($run->status === 'completed') {
            // Retrieve and send the assistant's response
            $messages = $client->threads()->messages()->list($thread_id);
            foreach ($messages->data as $msg) {
                if ($msg->role === 'assistant') {
                    $eventData = json_encode([
                        'event' => 'message',
                        'message' => $msg->content[0]->text->value,
                    ]);
                    mmmush_debug($eventData);
                    echo "data: {$eventData}\n\n";
                    flush();
                    break; // Only send the latest assistant message
                }
            }
            break;
        } elseif (in_array($run->status, ['failed', 'cancelled', 'expired'])) {
            $eventData = json_encode([
                'event' => 'error',
                'message' => 'Run failed: ' . $run->status,
            ]);
            mmmush_debug($eventData);
            echo "data: {$eventData}\n\n";
            flush();
            break;
        }

        // Wait before polling again
        sleep(1);
    }

    // End the connection when streaming is done
    exit;
}

add_action('wp_ajax_embed_send_message', 'handle_embed_send_message');
add_action('wp_ajax_nopriv_embed_send_message', 'handle_embed_send_message');

function handle_embed_send_message() {
    mmmush_debug('embed_handle_send_message');
    // Ensure output buffering is turned off
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Get the POST data
    $thread_embed_id = $_POST['ThreadEmbedId'];
    mmmush_debug($thread_embed_id);

    // find thread post by thread embed id
    $thread_post = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'thread',
        'meta_key'    => 'thread_embed_id',
        'meta_value'  => $thread_embed_id
    ))[0];
    
    $thread_id = get_field('thread_id', $thread_post->ID);

    // find assistant post by thread id
    $thread_post = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'thread',
        'meta_key'    => 'thread_id',
        'meta_value'  => $thread_id
    ))[0];
    $assistant_id = get_field('assistant_id', $thread_post->ID);

    $message = $_POST['message'];

    // Create OpenAI client
    $client = OpenAI::client(CHATGPT_API_KEY);

    // Create a message in the thread
    $client->threads()->messages()->create($thread_id, [
        'role' => 'user',
        'content' => $message,
    ]);

    // Create and start a run
    $run = $client->threads()->runs()->create($thread_id, [
        'assistant_id' => $assistant_id,
    ]);

    // Poll for run completion and stream the results
    while (true) {
        $run = $client->threads()->runs()->retrieve($thread_id, $run->id);
        mmmush_debug($run);
        if ($run->status === 'completed') {
            // Retrieve and send the assistant's response
            $messages = $client->threads()->messages()->list($thread_id);
            foreach ($messages->data as $msg) {
                if ($msg->role === 'assistant') {
                    $eventData = json_encode([
                        'event' => 'message',
                        'message' => $msg->content[0]->text->value,
                    ]);
                    mmmush_debug($eventData);
                    echo "data: {$eventData}\n\n";
                    flush();
                    break; // Only send the latest assistant message
                }
            }
            break;
        } elseif (in_array($run->status, ['failed', 'cancelled', 'expired'])) {
            $eventData = json_encode([
                'event' => 'error',
                'message' => 'Run failed: ' . $run->status,
            ]);
            mmmush_debug($eventData);
            echo "data: {$eventData}\n\n";
            flush();
            break;
        }

        // Wait before polling again
        sleep(1);
    }

    // End the connection when streaming is done
    exit;
}

function mmmush_create_default_thread($assistant_id, $assistant_post) {
    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->threads()->create([]);
    $thread_id = $response->id;
    $new_post = [
        'post_type' => 'thread',
        'post_title' => $assistant_post->post_title,
        'post_status' => 'publish',
    ];
    $new_post_id = wp_insert_post($new_post);
    update_field('field_66f6b0c661eca', $thread_id, $new_post_id);
    update_field('field_66f6b0dc59647', $assistant_id, $new_post_id);
    update_field('field_66f855bf7b16e', uniqid(), $new_post_id);
    return $new_post_id;
}

function mmmush_create_default_vector_store($assistant_id, $assistant_post) {
    // Temporarily remove the wp_insert_post action
    remove_action('wp_insert_post', 'mmmush_handle_wp_insert_post', 10, 3);

    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->vectorStores()->create([
        'name' => $assistant_post->post_title,
    ]);
    $new_post = [
        'post_type' => 'vector-store',
        'post_title' => $assistant_post->post_title,
        'post_status' => 'publish',
    ];
    $new_post_id = wp_insert_post($new_post);
    update_field('field_66f5cb66169b5', $response->id, $new_post_id);
    update_field('field_66f955498d624', $assistant_id, $new_post_id);

    // Re-add the wp_insert_post action
    add_action('wp_insert_post', 'mmmush_handle_wp_insert_post', 10, 3);

    return $new_post_id;
}
