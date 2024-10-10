<?php get_header(); ?>

<?php if (isset($_POST['file_id'])) : ?>
    <?php 
        $file_id = $_POST['file_id'];
        $file_post = get_posts([
            'post_type' => 'file',
            'p' => $file_id,
            'numberposts' => 1
        ])[0];
        if ($file_post) {
            // Delete from OpenAI
            $openai_file_id = get_field('file_id', $file_id);
            if ($openai_file_id) {
                $client = OpenAI::client(CHATGPT_API_KEY);
                $client->files()->delete($openai_file_id);
            }
            $file = get_field('file', $file_post->ID);
            $media_id = $file['ID'];
            wp_delete_attachment($media_id, true);
            wp_delete_post($file_post->ID, true);
            $file_deleted = true;
        }
    ?>
<?php endif; ?>

<div class="container">
    <div class="grid gap-4 grid-cols-1 max-w-[720px]">
        
        <?php if ($file_deleted) : ?>
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
                <span>File deleted.</span>
            </p>
        <?php endif; ?>
            
        <div>
            <h2><?php the_title(); ?> <a class="btn btn-xs btn-outline ml-2" href="/user/assistants/edit?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>">Edit</a></h2>
            <?php the_content(); ?>
        </div>
        <div>
            <?php 
                $vector_store = get_field('vector_stores', get_the_ID());
                $files = get_field('files', $vector_store->ID);
            ?>

            <h3>Files <a href="/user/files/create?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>" class="btn btn-xs btn-outline ml-2">Add file</a></h3>
            <p>Your assistant will use these files to answer queries.</p>
            <?php if ($files) : ?>
            <p>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <?php 
                            $the_file = get_field('file', $file->ID);
                            $file_url = $the_file['url'];
                            $file_name = $the_file['name'];
                            $file_subtype = pathinfo($file_url, PATHINFO_EXTENSION);
                        ?>  
                        <li class="file">
                            <a href="<?php echo $file_url; ?>" target="_blank"><?php echo $file->post_title; ?></a> (<?php echo $file_subtype; ?>) <?php print mmmush_time_ago($file->post_date);?>
                            <form action="<?php print get_the_permalink(); ?>" method="post" class="inline">
                                <button class="btn btn-xs btn-warning hidden delete-file ml-2">Delete</button>
                                <input type="submit" value="Confirm deletetion" class="btn btn-xs btn-error hidden confirm-delete-file ml-2">
                                <input type="hidden" name="file_id" value="<?php echo $file->ID; ?>">
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </p>
            <?php else: ?>
            <p>
                No files found. 
            </p>
            <?php endif; ?>
            <p>
                
            </p>
        </div>
        <div>   
            <h3>Embed Code</h3>
            <?php if ($files) : ?>
            <p>You can add this assistant to your website by copying and pasting the following code.</p>
            <textarea readonly class="textarea textarea-bordered text-sm h-[280px] w-full overflow-hidden">
&lt;div id=&quot;allybox-embed&quot;&gt;
    &lt;h3&gt;<?php the_title(); ?>&lt;/h3&gt;
    &lt;div id=&quot;allybox-chat-container&quot;&gt;&lt;/div&gt;
&lt;/div&gt;
&lt;script src=&quot;https://dashboard.allybox.app/embed/thread.js&quot;&gt;&lt;/script&gt;
&lt;script&gt;
    document.addEventListener(&#39;DOMContentLoaded&#39;, function() {
        allybox({
            assistantEmbedId: &#39;<?php print get_field('assistant_embed_id'); ?>&#39;
        });
    });
&lt;/script&gt;
            </textarea>
            <?php else: ?>
            <p role="alert" class="alert alert-warning">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 shrink-0 stroke-current"
                    fill="none"
                    viewBox="0 0 24 24">
                    <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><a href="/user/files/create?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>">Upload files</a> to your assistant to enable embedding.</span>
            </p>
            <?php endif; ?>
        </div>

    </div>
</div>

<div id="allybox-embed" class="shadow-xl">
    <h3><?php the_title(); ?></h3>
    <div id="allybox-chat-container"></div>
</div>
<?php if ($_SERVER['HTTP_HOST'] === 'mmmush.localhost') : ?>
    <script src="http://mmmush.localhost/embed/thread.js"></script>
<?php else : ?>
    <script src="https://dashboard.allybox.app/embed/thread.js"></script>
<?php endif; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        allybox({
            assistantEmbedId: '<?php print get_field('assistant_embed_id'); ?>'
        });
    });
</script>

<?php get_footer(); ?>