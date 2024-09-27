<?php
$thread_id = get_the_ID();
$assistant_id = get_field('assistant_id', $thread_id);

// assistant information
$client = OpenAI::client(CHATGPT_API_KEY);
$response = $client->assistants()->retrieve($assistant_id);

?>

<?php get_header(); ?>

<div class="container prose my-[100px]"> 
    <h1><?php echo $response->name; ?></h1>
    <div class="flex gap-[20px]">
        <div class="basis-4/12">
            <form action="" class="max-w-[500px]">
                <div>
                    <textarea name="message" placeholder="Message" class="h-[200px] w-full"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
        <div class="basis-6/12">
            <div class="messages border border-gray-300 rounded-[10px] p-[20px]">
                <p>fff</p>
            </div>
        </div>
    </div>
</div>

<?php while (have_posts()) : the_post(); ?>
    <?php the_content(); ?>
<?php endwhile; ?>

<?php get_footer(); ?>