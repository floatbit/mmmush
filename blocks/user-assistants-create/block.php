<?php
/**
 * Block template file: block.php
 *
 * User Assistants - Create Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'user-assistants-create-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'acf-block block-user-assistants-create';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

$new = get_query_var('new', 0);

$default_instructions = 'You are a data-driven assistant designed to retrieve, analyze, and present information based solely on the files or data sources attached to you. Your responses should be clear, accurate, and directly related to the user’s query. Use only the data provided; do not rely on external sources or make assumptions beyond what you have access to. When responding, ensure that results are organized and presented in a way that is easy to understand, using bullet points or paragraphs as needed. Always verify the information for accuracy before providing it to the user. If the requested data is unavailable, clearly indicate that it cannot be found within the provided sources. Your goal is to offer concise, actionable insights that fulfill the user’s request while maintaining clarity and precision.';
?>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?>">
    <div class="container">

        <ul class="steps w-full mb-10">
            <li class="step step-primary">Define Assistant</li>
            <li class="step">Add Files</li>
            <li class="step">Chat with Assistant</li>
        </ul>

        <div class="grid gap-x-8 grid-cols-1 max-w-[720px]">
            <?php if ($new == 1) : ?>
                <h2>Welcome! Let's create your first assistant.</h2>
                <p>An assistant is a personalized AI that answers your questions and provides insights from a specific perspective or area of expertise. It embodies the persona you define, responding to your queries with the knowledge and mindset of an expert in that field. You can shape the assistant’s personality—whether you want it to be friendly, serious, or sarcastic, the tone of its responses will match the style you choose.</p>
                <p>To get the most accurate and relevant answers, think about the type of expert you need. Define their role and knowledge base so that the assistant can respond effectively from that persona’s perspective.</p>
            <?php else : ?>
                <h2>Add a new assistant</h2>
            <?php endif; ?>
            <form action="<?php print esc_url(admin_url('admin-post.php')); ?>" method="POST">
                <input type="hidden" name="action" value="user_assistants_create">
                <input type="hidden" name="new" value="<?php print $new; ?>">
                <label class="form-control w-full mt-5 mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Assistant's name</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <input type="text" name="title" placeholder="For example: Chief Curator" class="input input-bordered w-full text-sm" />
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <label class="form-control w-full mb-10">
                    <div class="label">
                        <span class="label-text"><strong>Describe your assistant</strong></span>
                    </div>
                    <div class="indicator w-full">
                        <textarea name="description" class="textarea textarea-bordered text-sm h-[300px] w-full" placeholder="For example: You are the chief curator of a world-renowned museum. You curate art collections, provide historical context on artworks, and manage exhibition logistics. Using your museum’s extensive data files, you offer detailed insights into specific pieces, artists, and historical periods. Respond to questions about art history, artists, exhibit planning, and your museum’s collection."><?php print $default_instructions;?></textarea>
                        <span class="indicator-item badge">Required</span>
                    </div>
                </label>
                <p>
                    <button type="submit" class="btn btn-neutral">Save</button>
                </p>
            </form>
        </div>
    </div>

    <dialog id="my_modal_1" className="modal">
  <div className="modal-box">
    <h3 className="font-bold text-lg">Hello!</h3>
    <p className="py-4">Press ESC key or click the button below to close</p>
    <div className="modal-action">
      <form method="dialog">
        {/* if there is a button in form, it will close the modal */}
        <button className="btn">Close</button>
      </form>
    </div>
  </div>
</dialog>
</div>
