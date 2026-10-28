<?php
class Theme
{
    // Function to detect the theme (dark or light) using JavaScript
    public static function getBodyTheme()
    {
        // We can return a default theme for the page first (for PHP page rendering),
        // and JavaScript will update it later
        return isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light-mode';
    }

    // Set the theme in session
    public static function setBodyTheme($theme)
    {
        $_SESSION['theme'] = $theme;
    }
}
?>