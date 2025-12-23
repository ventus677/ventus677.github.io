<?php
     session_start();

    //remove all session variables
    session_unset();

    //destroy the session
    if (session_destroy()) {
        // Redirect to register.php with a 302 status code
        header('Location: ../home.php', true, 302);
        exit;
    }
?>