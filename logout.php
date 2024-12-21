<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Destroy the session when the form is submitted
    session_destroy();
    header("Location: login.php"); // Redirect to the login page after logout
    exit();
}
?>
