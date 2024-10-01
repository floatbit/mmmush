<div class="container container-fluid bg-base-100 py-2 border-b border-gray-200">
    <div class="breadcrumbs text-sm">
        <ul>
            <li>
                <a href="/">Home</a>
            </li>
            <?php if (is_single() && get_post_type() == 'assistant') : ?>
                <li>
                    <a href="/">Assistants</a>
                </li>
                <li><?php the_title(); ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
