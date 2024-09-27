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
                    'model' => 'gpt-4o',
                ]);
                update_field( 'assistant_id', $response->id, $post->ID );
            }

            // assistant files
            $assistant_id = get_field('assistant_id', $post->ID);
            $files = get_field('files', $post->ID);
            if ($files) {
                $vector_store_ids = [];
                $vector_store_ids[] = get_field('vector_store_id', $files->ID);
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
                    'model' => 'gpt-4o',
                ];
                $response = $client->assistants()->modify($assistant_id, $data);
            }
            // if ($assistant_files) {
            //     $client = OpenAI::client(CHATGPT_API_KEY);
            //     foreach($assistant_files as $assistant_file) {
            //         $file_id = get_field('file_id', $assistant_file->ID);
            //         $client->assistants()->files()->create($assistant_id, [
            //             'file_id' => $file_id,
            //         ]);
            //     }
            // }
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
            // create the vector store
            $vector_store_id = get_field('vector_store_id', $post->ID);
            $file_ids = [$response->id];
            if (empty($vector_store_id)) {
                $client = OpenAI::client(CHATGPT_API_KEY);
                $response = $client->vectorStores()->create([
                    'file_ids' => $file_ids,
                    'name' => $post->post_title,
                ]);
                update_field('field_66f5cf94ef09e', $response->id, $post->ID);
            }
        }
    }

    if ($post->post_type === 'vector-store') {
        if ($post->post_status === 'publish') {
            $files = get_field('files', $post->ID);
            $file_ids = [];
            if ($files) {
                foreach ($files as $file) {
                    $file_ids[] = get_field('file_id', $file['file']->ID);
                }

                $vector_store_id = get_field('vector_store_id', $post->ID);
                if (empty($vector_store_id)) {
                    $client = OpenAI::client(CHATGPT_API_KEY);
                    $response = $client->vectorStores()->create([
                        'file_ids' => $file_ids,
                        'name' => $post->post_title,
                    ]);
                    update_field('field_66f5cb66169b5', $response->id, $post->ID);
                } else {
                    $response = $client->vectorStores()->files()->create(
                        vectorStoreId: $vector_store_id,
                        parameters: [
                            'file_id' => 'file-fUU0hFRuQ1GzhOweTNeJlCXG',
                        ]
                    );
                    pr($file_ids);
                    die($vector_store_id);
                }
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
        $client = OpenAI::client(CHATGPT_API_KEY);
        $response = $client->threads()->delete($thread_id);
    }
    
});