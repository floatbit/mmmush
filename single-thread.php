<?php

$thread_id = get_field('thread_id', $post->ID);
$assistant_id = get_field('assistant_id', $post->ID);

// assistant information
$client = OpenAI::client(CHATGPT_API_KEY);
$response = $client->assistants()->retrieve($assistant_id);

// Delete existing messages in the thread
$messages = $client->threads()->messages()->list($thread_id);
foreach ($messages->data as $msg) {
    $client->threads()->messages()->delete($thread_id, $msg->id);
}

?>

<?php get_header(); ?>

<div class="container prose my-[100px]"> 
    <h1><?php echo $response->name; ?></h1>
    <div class="flex gap-[50px]">
        <div class="basis-4/12">
            <form id="chat-form" action="" class="max-w-[500px] sticky top-[30px]">
                <div>
                    <textarea id="message-input" name="message" placeholder="Message" class="h-[150px] w-full border border-gray-300 rounded-[10px] p-[20px] mb-5"></textarea>
                </div>
                <input type="hidden" name="ThreadId" value="<?php echo $thread_id; ?>">
                <input type="hidden" name="AssistantId" value="<?php echo $assistant_id; ?>">
                <div class="text-right">
                    <button type="submit" class="border border-gray-300 rounded-[10px] py-[10px] px-[20px]">Send</button>
                    <span class="loading hidden">......</span>
                </div>
            </form>
        </div>
        <div class="basis-8/12">
            <div id="chat-messages" class="messages">
                <!-- Messages will be added here -->
            </div>
        </div>
    </div>
</div>

<?php while (have_posts()) : the_post(); ?>
    <?php the_content(); ?>
<?php endwhile; ?>

<?php get_footer(); ?>