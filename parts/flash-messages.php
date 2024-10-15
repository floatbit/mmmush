<?php
$user_id = get_current_user_id();
if ($user_id) {
    $messages = get_transient('user_success_messages_' . $user_id);
    if ($messages) {
        ?>
        <div class="container container-fluid">
        <?php
        foreach ($messages as $message) {
            if ($message['type'] == 'success') {
            ?>
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
                <span><?php echo $message['message']; ?></span>
            </p>
                <?php
            }
        }
        delete_transient('user_success_messages_' . $user_id);
        ?>
        </div>
        <?php
    }
}