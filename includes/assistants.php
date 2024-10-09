<?php

// Require the OpenAI PHP client
require_once get_template_directory() . '/vendor/autoload.php';

// Include the secrets file
require_once get_template_directory() . '/includes/secrets.php';

define('MMUSH_FIXED_INSTRUCTIONS', ' Your responses should solely draw from the content and data provided in the files you have been given. If you do not know the answer, say something like, “I cannot answer from the data given to me.” Respond to queries without including any citations, references, or text inside brackets (e.g., [source]), and without indicating source numbers or references of any kind. Do not include any concluding statements, such as offering suggestions, asking for feedback, or inviting further questions.');

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
        return "Just Now";
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