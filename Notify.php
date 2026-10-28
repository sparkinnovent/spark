<?php
//session_start();

class Notify
{
    // Set a notification message with a type (e.g., 'success', 'error', 'info')
    public static function set($message, $type = 'info')
    {
        // Ensure the notifications array is initialized
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }

        // Store the notification with its type
        $_SESSION['notifications'][] = [
            'message' => $message,
            'type'    => $type
        ];
    }

    // Get and display all notifications, then clear them
    public static function display()
    {
        if (isset($_SESSION['notifications']) && count($_SESSION['notifications']) > 0) {
            foreach ($_SESSION['notifications'] as $notification) {
                self::renderNotification($notification['message'], $notification['type']);
            }

            // Clear notifications after they are displayed
            unset($_SESSION['notifications']);
        }
    }

    // Render the notification in HTML (can be customized based on type)
    private static function renderNotification($message, $type)
    {
        /*
         * FIX H1 — the message was echoed raw inside <p>. Several call sites
         * build it from $_GET (index.php ?format= / ?session_expired=), which
         * turned every one of them into reflected XSS. Escape both halves.
         */
        $safeType = in_array($type, ['danger', 'warning', 'info', 'success'], true) ? $type : 'info';
        $text     = htmlspecialchars((string)$message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<div class="alert alert-' . $safeType . ' alert-dismissible">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
        if ($safeType == "danger") {
            echo '<h5><i class="icon fas fa-ban"></i> Alert!</h5>';
        }
        else if($safeType == "warning"){
            echo '<h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>';
        }
        else if($safeType == "info"){
            echo '<h5><i class="icon fas fa-info"></i> Info</h5>';
        }
        else if($safeType == "success"){
            echo '<h5><i class="icon fas fa-check"></i> Excellent!</h5>';
        }
        echo "<p>$text</p>";
        echo '</div>';
    }
}
?>
