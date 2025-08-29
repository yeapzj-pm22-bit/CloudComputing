<?php
session_start();
require_once '_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateEnrollmentForm($_POST);

    if (!empty($errors)) {
        // Save errors + form data to session
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;

        // Redirect back to form
        header("Location: enrollment.php");
        exit;
    }

    // ✅ For now, only validation → show success
    echo "<h2>Validation passed ✅</h2>";
    echo "<p>Your application data is valid. (Next step: save to DB or send email)</p>";
}
