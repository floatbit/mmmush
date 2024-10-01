<?php get_header(); ?>

<div class="container">
    <div class="grid gap-4 grid-cols-1 max-w-[720px]">
        <div>
            <h3>Assistant Description</h3>
            <?php the_content(); ?>
        </div>

        <?php 
            $vector_store = get_field('vector_stores', get_the_ID());
            $files = get_field('files', $vector_store->ID);
        ?>
        <?php if ($files) : ?>
        <div>
            <h3>Files</h3>
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
            <p>
                <a href="/user/files/create" class="btn btn-sm">+ Add File</a>
            </p>
        </div>
        <?php endif; ?>
        <div>   
            <h3>Embed Code</h3>
            <p>You can use this assistant in your website by copying and pasting the following code.</p>
                <p>
                    Sorry, you cannot embed this assistant because there are no files. 
                </p>
                <p>
                    <a href="/user/files/create" class="btn btn-sm">+ Add File</a>
                </p>
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