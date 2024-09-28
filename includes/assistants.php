<?php

// Require the OpenAI PHP client
require_once get_template_directory() . '/vendor/autoload.php';

// Include the secrets file
require_once get_template_directory() . '/includes/secrets.php';

// Assistant post actions for new and updated posts
add_action('wp_insert_post', function($post_id, $post, $update) {
    if ($post->post_type === 'assistant') {
        // Check if the post status is 'publish'
        if ($post->post_status === 'publish') {
            
            $assistant_id = get_field('assistant_id', $post->ID);
            $instructions = strip_tags($post->post_content);

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
                update_field( 'assistant_id', $response->id, $post->ID );
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

    if ($post->post_type === 'file') {
        if ($post->post_status === 'publish') {
            // create the file
            $file = get_field('file', $post->ID);
            $client = OpenAI::client(CHATGPT_API_KEY);
            $response = $client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($file['url'], 'r'),
            ]);
            // update file id
            update_field('field_66f5c2e748393', $response->id, $post->ID);
            // // create the vector store
            // $vector_store_id = get_field('vector_store_id', $post->ID);
            // $file_ids = [$response->id];
            // // create a vector store
            // if (empty($vector_store_id)) {
            //     $client = OpenAI::client(CHATGPT_API_KEY);
            //     $response = $client->vectorStores()->create([
            //         'file_ids' => $file_ids,
            //         'name' => $post->post_title,
            //     ]);
            //     $vector_store_id = $response->id;
            //     update_field('field_66f5cf94ef09e', $vector_store_id, $post->ID);
            // }
            // // additional files
        }
    }

    if ($post->post_type === 'vector-store') {
        if ($post->post_status === 'publish') {
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

}, 10, 3);

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
    $thread_id = $_POST['ThreadId'];
    mmmush_debug($thread_id);
    $assistant_id = 'asst_OEuxgw4AwGuxq2jPXCwPdn73';
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

