<?php get_header(); ?>

<div class="container">
    <div class="grid gap-4 grid-cols-1 max-w-[720px]">
        <div>
            <h3>Description</h3>
            <?php the_content(); ?>
        </div>

        <?php 
            $vector_store = get_field('vector_stores', get_the_ID());
            $files = get_field('files', $vector_store->ID);
        ?>
        <div>
            <h3>Files</h3>
            <p>Your assistant will use these files to answer queries.</p>
            <?php if ($files) : ?>
            <p>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <?php 
                            $the_file = get_field('file', $file->ID);
                            $file_url = $the_file['url'];
                            $file_name = $the_file['name'];
                            $file_subtype = $the_file['subtype'];
                        ?>  
                        <li><a href="<?php echo $file_url; ?>"><?php echo $file->post_title; ?></a> (<?php echo $file_subtype; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </p>
            <?php else: ?>
            <p>
                No files found. 
            </p>
            <?php endif; ?>
            <p>
                <a href="/user/files/create?AssistantEmbedId=<?php echo get_field('assistant_embed_id'); ?>" class="btn btn-sm">+ Add File</a>
            </p>
        </div>
        <div>   
            <h3>Embed Code</h3>
            <?php if ($files) : ?>
            <p>You can use this assistant in your website by copying and pasting the following code. Look to the right - that's how it will look.</p>
            <textarea readonly class="textarea textarea-bordered text-sm h-[260px] w-full">
                <div id="mmmush-embed">
                    <h3><?php the_title(); ?></h3>
                    <div id="mmmush-chat-container"></div>
                </div>
                <script src="http://mmmush.localhost/embed/thread.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        MMMush({
                            assistantEmbedId: '<?php print get_field('assistant_embed_id'); ?>'
                        });
                    });
                </script>
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

<div id="mmmush-embed">
    <h3><?php the_title(); ?></h3>
    <div id="mmmush-chat-container"></div>
</div>
<script src="http://mmmush.localhost/embed/thread.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        MMMush({
            assistantEmbedId: '<?php print get_field('assistant_embed_id'); ?>'
        });
    });
</script>

<?php get_footer(); ?>