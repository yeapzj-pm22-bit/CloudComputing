<?php
// _base.php
function validateEnrollmentForm($data) {
    $errors = [];

    // Required fields
    $requiredFields = [
        'lastName', 'firstName', 'dateOfBirth', 'nationality',
        'email', 'phone', 'address', 'program', 'major',
        'enrollmentType', 'startTerm', 'highSchool', 'graduationYear',
        'emergencyName', 'relationship', 'emergencyPhone',
        'declaration', 'digitalSignature'
    ];

    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // Email format
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    // Phone format
    if (!empty($data['phone']) && !preg_match("/^[0-9+\-\s()]{7,20}$/", $data['phone'])) {
        $errors['phone'] = "Please enter a valid phone number.";
    }

    // Emergency phone format
    if (!empty($data['emergencyPhone']) && !preg_match("/^[0-9+\-\s()]{7,20}$/", $data['emergencyPhone'])) {
        $errors['emergencyPhone'] = "Please enter a valid emergency contact number.";
    }

    // Graduation year
    if (!empty($data['graduationYear']) && 
        ($data['graduationYear'] < 1950 || $data['graduationYear'] > 2035)) {
        $errors['graduationYear'] = "Graduation year must be between 1950 and 2035.";
    }

    return $errors;
}
