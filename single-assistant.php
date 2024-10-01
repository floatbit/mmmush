<?php get_header(); ?>

<div class="container">
    <div class="grid gap-8 grid-cols-3">
        <div class="col-span-2">
            <h3>About</h3>
            <?php the_content(); ?>

            <h3>Files</h3>
            <?php 
                $vector_store = get_field('vector_stores', $assistant->ID);
                $files = get_field('files', $vector_store->ID);
            ?>
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