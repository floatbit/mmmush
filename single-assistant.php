<?php get_header(); ?>

<div class="container">
    <div class="grid gap-4 grid-cols-1 max-w-[720px]">
        
        <div>
            <h2><?php the_title(); ?> <a class="btn btn-xs btn-outline ml-2" href="/user/assistants/edit?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>">Edit</a></h2>
            <div class="instructions">
                <?php $instructions = $post->post_content; ?>
                <div class="text">
                    <?php echo apply_filters('the_content', $instructions); ?>
                </div>
                <div class="show-more">
                    <p>
                        <a href="#" class="">Show more</a>
                    </p>
                </div>
            </div>
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
                            <a href="<?php echo $file_url; ?>" target="_blank"><?php echo $file->post_title; ?></a> <em><?php print mmmush_time_ago($file->post_date_gmt);?></em>
                            <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="inline">
                                <input type="hidden" name="action" value="user_files_delete">
                                <input type="hidden" name="assistant_id" value="<?php echo get_the_ID(); ?>">
                                <input type="hidden" name="file_id" value="<?php echo $file->ID; ?>">
                                <button class="btn btn-xs btn-warning hidden delete-file ml-2">Delete</button>
                                <input type="submit" value="Really delete?" class="btn btn-xs btn-error hidden confirm-delete-file ml-2">
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
        </div>

        <div>
            <?php 
                $vector_store = get_field('vector_stores', get_the_ID());
                $data_feeds = get_field('data_feeds', $vector_store->ID);
        ?>
            <h3>Data Feeds <a href="/user/data-feeds/create?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>" class="btn btn-xs btn-outline ml-2">Add data feed</a></h3>
            <p>Your assistant will use these data feeds to answer queries.</p>
            <?php if ($data_feeds) : ?>
            <p>
                <ul>
                    <?php foreach ($data_feeds as $data_feed): ?>
                        <?php 
                            $feed_url = get_field('feed_url', $data_feed->ID);
                        ?>  
                        <li class="file">
                            <a href="<?php echo $feed_url; ?>" target="_blank"><?php echo $data_feed->post_title; ?></a> <?php print mmmush_time_ago($data_feed->post_date_gmt);?>
                            <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="inline">
                                <input type="hidden" name="action" value="user_data_feeds_delete">
                                <input type="hidden" name="assistant_id" value="<?php echo get_the_ID(); ?>">
                                <button class="btn btn-xs btn-warning hidden delete-file ml-2">Delete</button>
                                <input type="submit" value="Really delete?" class="btn btn-xs btn-error hidden confirm-delete-file ml-2">
                                <input type="hidden" name="data_feed_id" value="<?php echo $data_feed->ID; ?>">
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </p>
            <?php else: ?>
            <p>
                No feeds found. 
            </p>
            <?php endif; ?>
        </div>

        <div>   
            <h3>Chatbox Embed Code</h3>
            <?php if ($files || $data_feeds) : ?>
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
            <p>Upload files or data feeds to your assistant to enable embedding.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php if ($files || $data_feeds) : ?>
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
<?php endif; ?>

<?php get_footer(); ?>