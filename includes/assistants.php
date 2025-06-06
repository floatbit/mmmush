<?php

// Require the OpenAI PHP client
require_once get_template_directory() . '/vendor/autoload.php';

// Include the secrets file
require_once get_template_directory() . '/includes/secrets.php';

define('MMUSH_FIXED_INSTRUCTIONS', "
IMPORTANT:
- Use only the provided files for your responses.
- If the answer is not in the data, respond with: 'I cannot answer from the data given to me.'
- Avoid citations, references, or any text inside brackets (e.g., [source]) and do not use source numbers.
- Exclude concluding statements like suggestions, feedback requests, or invitations for further questions.
- Review the data to ensure accuracy before finalizing your response.
- If uncertain about specific information, ask for further clarification from the data rather than providing an inaccurate answer.
");
define('MMUSH_DEFAULT_MODEL', 'gpt-4o-mini');

add_action('template_redirect', function() {
    // callback for assistant message
    if (get_query_var('am') == 1) {
        if (empty($_SERVER['HTTP_REFERER'])) {
            wp_redirect(home_url());
            exit;
        }
        mmmush_handle_am_event();
        exit;
    }

    $post = get_post();
    // user not logged in - go to login url
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    } else {
        // user logged in - check if they have an assistant
        $user = wp_get_current_user();
        $assistant = get_posts(array(
            'numberposts' => -1,    
            'post_type'   => 'assistant',
            'author' => $user->ID
        ));
        if (count($assistant) === 0 && $post->ID != 308) { 
            wp_redirect(home_url('/user/assistants/create?new=1'));
            exit;
        }
    }
});

// add query vars
add_filter( 'query_vars', 'mmmush_query_vars' );
function mmmush_query_vars( $query_vars ){
    $query_vars[] = 'am';
    $query_vars[] = 'AssistantId';
    $query_vars[] = 'AssistantEmbedId';
    $query_vars[] = 'new';
    $query_vars[] = 'upload_success';
    return $query_vars;
}

// login redirect
add_filter('login_redirect', 'mmmush_custom_login_redirect', 10, 3);
function mmmush_custom_login_redirect($redirect_to, $request, $user) {
    return home_url();
}

// register redirect
add_action('user_register', 'mmmush_custom_registration_redirect');
function mmmush_custom_registration_redirect($user_id) {
    if (!isset($_GET['loginSocial'])) {
        wp_safe_redirect(home_url());
        exit;
    }
}

// Add JSON support
function mmmush_custom_mime_types($mime_types) {
    $mime_types['json'] = 'application/json';
    return $mime_types;
}
add_filter('upload_mimes', 'mmmush_custom_mime_types');

/*
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
                update_field('field_66f5b63711fc3', $assistant_id, $post->ID);
                update_field('field_66f9e904b7204', uniqid(), $post->ID);
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
*/
//add_action('wp_insert_post', 'mmmush_handle_wp_insert_post', 10, 3);

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
    $thread_post = get_post($new_post_id);
    return $thread_post;
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

// Function to handle AJAX request to create a new thread
function create_new_thread() {
    $assistant_embed_id = $_POST['assistantEmbedId'];
    $assistant_post = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'assistant',
        'meta_key'    => 'assistant_embed_id',
        'meta_value'  => $assistant_embed_id
    ))[0];

    if ($assistant_post) {
        $assistant_id = get_field('assistant_id', $assistant_post->ID);
        $thread_post = mmmush_create_default_thread($assistant_id, $assistant_post);
        $thread_embed_id = get_field('thread_embed_id', $thread_post->ID);

        wp_send_json_success(array('thread_embed_id' => $thread_embed_id));
    } else {
        wp_send_json_error(array('message' => 'Assistant not found'));
    }
}
add_action('wp_ajax_create_new_thread', 'create_new_thread');
add_action('wp_ajax_nopriv_create_new_thread', 'create_new_thread');


// Add rewrite rule for AM (assistant message)
add_action('init', 'register_am_endpoint');

function register_am_endpoint() {
    add_rewrite_rule('^am-endpoint/?', 'index.php?am=1', 'top');
}

// Grabs the AM (assistant message) data and streams the response
function mmmush_handle_am_event() {

    ob_implicit_flush( true );
    ob_end_flush();  

    // Set the headers for SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header( 'X-Accel-Buffering: no' );

    // Get the GET data
    $message = $_GET['message'];
    $thread_embed_id = $_GET['ThreadEmbedId'];

    if ($thread_embed_id) {
        // find thread post by thread embed id
        $thread_post = get_posts(array(
            'numberposts' => -1,
            'post_type'   => 'thread',
            'meta_key'    => 'thread_embed_id',
            'meta_value'  => $thread_embed_id
        ))[0];

        $thread_id = get_field('thread_id', $thread_post->ID);
        $assistant_id = get_field('assistant_id', $thread_post->ID);
    } else {
        $thread_id = $_GET['ThreadId'];
        $assistant_id = $_GET['AssistantId'];
    }

    // Create OpenAI client
    $client = OpenAI::client(CHATGPT_API_KEY);

    $client->threads()->messages()->create($thread_id, [
        'role' => 'user',
        'content' => $message,
    ]);

    // Create and start a run
    $stream = $client->threads()->runs()->createStreamed(
        threadId: $thread_id,
        parameters: [
            'assistant_id' => $assistant_id,
            'temperature' => 0.5,
        ],
    );
    
    $in_progress = false;

    foreach($stream as $response) {
        if ($response->event === 'thread.run.created') {
            $run_id = $response->response->id;
        }
        if ($response->event === 'thread.run.in_progress') {
            $in_progress = true;
        }
        if ($response->event === 'thread.message.delta') {
            //pdebug($response);
            $delta_message = [
                'event' => 'message',
                'message' => $response->response->delta->content[0]->text->value,
            ];
            if ($in_progress) {
                $delta_message['run_id'] = $run_id;
            }
            $eventData = json_encode($delta_message);

            echo "data: {$eventData}" . PHP_EOL . PHP_EOL;
            echo str_pad('', 8186) . PHP_EOL;
            flush();
        }
    }
    echo "data: {\"event\": \"complete\", \"message\": \"Stream completed\"}" . PHP_EOL . PHP_EOL;
    flush();
    exit;
}

add_action('wp_ajax_stop_run', 'stop_run');
add_action('wp_ajax_nopriv_stop_run', 'stop_run');

function stop_run() {
    $run_id = $_POST['RunId'];
    $thread_embed_id = $_POST['ThreadEmbedId'];
    $thread_id = mmmush_get_thread_id_from_thread_embed_id($thread_embed_id);

    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->threads()->runs()->cancel($thread_id, $run_id);
    pdebug($response);
    exit;
}

function mmmush_get_thread_id_from_thread_embed_id($thread_embed_id) {
    $thread_post = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'thread',
        'meta_key'    => 'thread_embed_id',
        'meta_value'  => $thread_embed_id
    ))[0];
    if ($thread_post) {
        return get_field('thread_id', $thread_post->ID);
    }
    return null;
}

add_action('wp_ajax_get_previous_messages', 'get_previous_messages');
add_action('wp_ajax_nopriv_get_previous_messages', 'get_previous_messages');

function get_previous_messages() {
    // Check if the required parameters are set
    if (!isset($_POST['ThreadEmbedId']) || !isset($_POST['AssistantEmbedId'])) {
        wp_send_json_error('Missing parameters');
        wp_die();
    }

    $thread_embed_id = sanitize_text_field($_POST['ThreadEmbedId']);
    $assistant_embed_id = sanitize_text_field($_POST['AssistantEmbedId']);

    $thread_id = mmmush_get_thread_id_from_thread_embed_id($thread_embed_id);

    $client = OpenAI::client(CHATGPT_API_KEY);
    $response = $client->threads()->messages()->list($thread_id, ['limit' => 10]);

    $previous_messages = [];
    foreach ($response->data as $message) {
        $previous_messages[] = [
            'role' => $message->role,
            'content' => $message->content[0]->text->value,
        ];
    }
    $previous_messages = array_reverse($previous_messages);
    if ($previous_messages) {
        wp_send_json_success(['messages' => $previous_messages]);
    } else {
        wp_send_json_error('No previous messages found');
    }

    wp_die(); // This is required to terminate immediately and return a proper response
}


function mmmush_time_ago($date) {
    $timestamp = strtotime($date);
    $time_ago = time() - $timestamp;
    $seconds = $time_ago;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "just now";
    } elseif ($minutes <= 60) {
        return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
    } elseif ($hours <= 24) {
        return ($hours == 1) ? "an hour ago" : "$hours hours ago";
    } elseif ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } elseif ($weeks <= 4.3) {
        return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
    } elseif ($months <= 12) {
        return ($months == 1) ? "a month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "one year ago" : "$years years ago";
    }
}

// Get an assistant from the assistant embed id
function mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id) {
    $assistant = get_posts([
        'numberposts' => 1,
        'post_type'   => 'assistant',
        'meta_key'    => 'assistant_embed_id',
        'meta_value'  => $assistant_embed_id,
        'author' => get_current_user_id(),
    ])[0];
    return $assistant;
}

function handle_user_data_feeds_create() {
    $assistant_embed_id = sanitize_text_field($_POST['AssistantEmbedId']);
    $feed_url = sanitize_text_field($_POST['feed_url']);
    $title = sanitize_text_field($_POST['title']);
    $assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);

    if ($assistant) {
        $vector_store = get_field('vector_stores', $assistant->ID);
        $openai_vector_store_id = get_field('vector_store_id', $vector_store->ID);

        // upload file to openai
        $client = OpenAI::client(CHATGPT_API_KEY);
        $response = $client->files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($feed_url, 'r'),
        ]);
        $openai_file_id = $response->id;

        // create data feed post
        $new_post = [
            'post_type' => 'data-feed',
            'post_title' => $title,
            'post_status' => 'publish',
        ];
        $new_post_id = wp_insert_post($new_post);
        update_field('field_6709d7dfe7bb8', $feed_url, $new_post_id);
        update_field('field_6709d92c2f4db', $openai_file_id, $new_post_id);

        // add data feed to vector store
        $data_files = get_field('data_feeds', $vector_store->ID);
        $data_feed_ids = array();
        foreach ($data_files as $data_feed) {
            $data_feed_ids[] = $data_feed->ID;
        }
        $data_feed_ids[] = $new_post_id;
        update_field('field_6709e0b90fc3d', $data_feed_ids, $vector_store->ID);

        // add data feed to vector store
        mmmush_openai_add_file_to_vector_store($openai_file_id, $openai_vector_store_id, $vector_store->ID);

        mmmush_flash_message('Data feed added to assistant <a href="' . get_the_permalink($assistant->ID) . '">' . $assistant->post_title . '</a>.', 'success');
        $redirect_url = '/user/data-feeds/create/?AssistantEmbedId=' . $assistant_embed_id;
        wp_redirect($redirect_url);
        exit;
    }
}
add_action('admin_post_user_data_feeds_create', 'handle_user_data_feeds_create'); // For logged-in users
add_action('admin_post_nopriv_user_data_feeds_create', 'handle_user_data_feeds_create');

function mmmush_get_file_ids_from_vector_store($vector_store_id) {
    $files = get_field('files', $vector_store_id);
    $data_feeds = get_field('data_feeds', $vector_store_id);
    $file_ids = [];
    // Ensure $files is an array before looping
    if (is_array($files)) {
        foreach ($files as $file) {
            // Ensure $file is an object with an ID property
            if (is_object($file) && isset($file->ID)) {
                $file_id = get_field('file_id', $file->ID);
                if ($file_id) {
                    $file_ids[] = $file_id;
                }
            }
        }
    }
    // Ensure $data_feeds is an array before looping
    if (is_array($data_feeds)) {
        foreach ($data_feeds as $data_feed) {
            // Ensure $data_feed is an object with an ID property
            if (is_object($data_feed) && isset($data_feed->ID)) {
                $file_id = get_field('file_id', $data_feed->ID);
                if ($file_id) {
                    $file_ids[] = $file_id;
                }
            }
        }
    }
    return $file_ids;
}

function handle_user_data_feeds_delete() {
    $data_feed_id = sanitize_text_field($_POST['data_feed_id']);
    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    $data_feed_post = get_posts([
        'post_type' => 'data-feed',
        'p' => $data_feed_id,
        'numberposts' => 1,
        'author' => get_current_user_id()
    ])[0];

    if ($data_feed_post) {
        // delete openai file
        $openai_file_id = get_field('file_id', $data_feed_post->ID);
        if ($openai_file_id) {
            mmmush_openai_delete_file_from_vector_store($openai_file_id, $assistant_id);
            mmmush_openai_delete_file($openai_file_id);
            wp_delete_post($data_feed_post->ID, true);
        }
        mmmush_flash_message('Data feed deleted.', 'success');
        $redirect_url = get_the_permalink($assistant_id);
        wp_redirect($redirect_url);
        exit;
    }
    wp_redirect('/user/assistants/');
    exit;
}
add_action('admin_post_user_data_feeds_delete', 'handle_user_data_feeds_delete');
add_action('admin_post_nopriv_user_data_feeds_delete', 'handle_user_data_feeds_delete');

function handle_user_files_delete() {
    $file_id = sanitize_text_field($_POST['file_id']);
    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    $file_post = get_posts([
        'post_type' => 'file',
        'p' => $file_id,
        'numberposts' => 1,
        'author' => get_current_user_id()
    ])[0];

    if ($file_post) {
        // Delete from OpenAI
        $openai_file_id = get_field('file_id', $file_id);
        if ($openai_file_id) {
            mmmush_openai_delete_file_from_vector_store($openai_file_id, $assistant_id);
            mmmush_openai_delete_file($openai_file_id);
            $file = get_field('file', $file_post->ID);
            $media_id = $file['ID'];
            wp_delete_attachment($media_id, true);
            wp_delete_post($file_post->ID, true);
        }
    }
    mmmush_flash_message('File deleted.', 'success');
    $redirect_url = get_the_permalink($assistant_id);
    wp_redirect($redirect_url);
    exit;
}

add_action('admin_post_user_files_delete', 'handle_user_files_delete');
add_action('admin_post_nopriv_user_files_delete', 'handle_user_files_delete');

function handle_user_files_create() {
    $assistant_embed_id = sanitize_text_field($_POST['AssistantEmbedId']);
    $assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);
    if ($assistant) {
        $vector_store = get_field('vector_stores', $assistant->ID);
        $openai_vector_store_id = get_field('vector_store_id', $vector_store->ID);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
    

            $files = array();
    
            if (isset($_FILES['file1']['name'])) {
                $files[] = $_FILES['file1'];
            }
            if (isset($_FILES['file2']['name'])) {
                $files[] = $_FILES['file2'];
            }
            if (isset($_FILES['file3']['name'])) {
                $files[] = $_FILES['file3'];
            }
            if (isset($_FILES['file4']['name'])) {
                $files[] = $_FILES['file4'];
            }
            if (isset($_FILES['file5']['name'])) {
                $files[] = $_FILES['file5'];
            }
            
            // Loop through each file
            foreach ($files as $file) {
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
                        'post_title'   => $file_name,
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
                    $openai_file_id = $response->id;
                    // update file id
                    update_field('field_66f5c2e748393', $openai_file_id, $new_post_id);

                    // add file to vector store post
                    $files = get_field('files', $vector_store->ID);
                    $file_ids = array($new_post_id);
                    if ($files) {
                        foreach ($files as $file) {
                            $file_ids[] = $file->ID;
                        }
                    }
                    update_field('field_66f76c4f3450d', $file_ids, $vector_store->ID);

                    // add file to vector store
                    mmmush_openai_add_file_to_vector_store($openai_file_id, $openai_vector_store_id, $vector_store->ID);

                    // Flash message for each file
                    mmmush_flash_message('<em>' . $file_name . '</em> added to assistant <a href="' . get_the_permalink($assistant->ID) . '">' . $assistant->post_title . '</a>.', 'success');
                }
            }

            // Redirect after processing all files
            $redirect_url = '/user/files/create/?AssistantEmbedId=' . $assistant_embed_id;
            wp_redirect($redirect_url);
            exit;
        }
    }
}
add_action('admin_post_user_files_create', 'handle_user_files_create');
add_action('admin_post_nopriv_user_files_create', 'handle_user_files_create');

function mmmush_flash_message($message, $type = 'success') {
    $user_id = get_current_user_id();
    if ($user_id) {
        $messages = get_transient('user_success_messages_' . $user_id);
        if (!$messages) {
            $messages = [];
        }
        $messages[] = ['message' => $message, 'type' => $type];
        set_transient('user_success_messages_' . $user_id, $messages, 3600); // Store for 1 hour
    }
}

function mmmush_openai_delete_file($openai_file_id) {
    $client = OpenAI::client(CHATGPT_API_KEY);
    $client->files()->delete($openai_file_id);
}

function mmmush_openai_delete_file_from_vector_store($openai_file_id, $assistant_id) {
    $vector_store = get_field('vector_stores', $assistant_id);
    $vector_store_id = get_field('vector_store_id', $vector_store->ID);
    $client = OpenAI::client(CHATGPT_API_KEY);
    $client->vectorStores()->files()->delete($vector_store_id, $openai_file_id);
}

function mmmush_openai_add_file_to_vector_store($openai_file_id, $openai_vector_store_id, $vector_store_id) {
    // $file_ids = mmmush_get_file_ids_from_vector_store($vector_store_id); // No longer needed
    // $file_ids[] = $openai_file_id; // No longer needed
    $client = OpenAI::client(CHATGPT_API_KEY);
    // Use the correct API method to add a single file to the vector store
    try {
        $client->vectorStores()->files()->create($openai_vector_store_id, [
            'file_id' => $openai_file_id,
        ]);
        // Optionally add success flash message here if needed
    } catch (\Exception $e) {
        // Log the error or display a user-friendly message
        error_log("Error adding file {$openai_file_id} to vector store {$openai_vector_store_id}: " . $e->getMessage());
        mmmush_flash_message('Error adding file to the AI assistant. Details: ' . $e->getMessage(), 'error');
        // Potentially redirect back with error
        // Consider if you need to remove the file association in WordPress if the OpenAI call fails
    }
}

function handle_user_assistants_edit() {
    $assistant_embed_id = sanitize_text_field($_POST['AssistantEmbedId']);
    $assistant = mmmush_get_assistant_from_assistant_embed_id($assistant_embed_id);
    if ($assistant) {
        $assistant_id = get_field('assistant_id', $assistant->ID);
        $title = sanitize_text_field(strip_tags($_POST['title']));
        $description = sanitize_textarea_field(strip_tags($_POST['description']));

        if ($title && $description) {
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
                'instructions' => $description . PHP_EOL . PHP_EOL . MMUSH_FIXED_INSTRUCTIONS,
                'temperature' => 0.5,
            ]);

            mmmush_flash_message('Assistant updated.', 'success');
            $redirect_url = '/user/assistants/edit/?AssistantEmbedId=' . $assistant_embed_id;
            wp_redirect($redirect_url);
            exit;
        }
    }
    
}
add_action('admin_post_user_assistants_edit', 'handle_user_assistants_edit');
add_action('admin_post_nopriv_user_assistants_edit', 'handle_user_assistants_edit');

function handle_user_assistants_create() {
    $new = sanitize_text_field($_POST['new']);
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
        
        // Define instructions before using them
        $instructions = $description . PHP_EOL . PHP_EOL . MMUSH_FIXED_INSTRUCTIONS;

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
            'model' => MMUSH_DEFAULT_MODEL,
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
        if ($vector_stores) {
            $vector_store_ids = [];
            $vector_store_ids[] = get_field('vector_store_id', $vector_stores->ID);
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
                'model' => MMUSH_DEFAULT_MODEL
            ];
            $response = $client->assistants()->modify($assistant_id, $data);
        }

        mmmush_flash_message('Assistant created.', 'success');
        $redirect_url = '/user/files/create/?new=' . $new . '&AssistantEmbedId=' . $unique_id;
        wp_redirect($redirect_url);
        exit;
    }
}
add_action('admin_post_user_assistants_create', 'handle_user_assistants_create');
add_action('admin_post_nopriv_user_assistants_create', 'handle_user_assistants_create');
