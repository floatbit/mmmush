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
                        <li><a href="<?php echo $file_url; ?>"><?php echo $file_name; ?></a> (<?php echo $file_subtype; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </p>
            <?php else: ?>
            <p>
                No files found. 
            </p>
            <?php endif; ?>
            <p>
                <a href="/user/files/create" class="btn btn-sm">+ Add File</a>
            </p>
        </div>
        <div>   
            <h3>Embed Code</h3>
            <?php if ($files) : ?>
                <p>You can use this assistant in your website by copying and pasting the following code.</p>
            <?php else: ?>
                <p>
                    Upload files to your assistant to enable embedding.
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