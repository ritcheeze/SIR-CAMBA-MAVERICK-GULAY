<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Force PHP to load the database connection
require __DIR__ . '/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email is already registered!'); window.history.back();</script>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Securely hash password before storing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $role = isset($_POST['role']) ? trim($_POST['role']) : 'student';
    // Normalize role values coming from the form
    $role = strtolower($role) === 'admin' ? 'admin' : 'student';

    // Auto-generate student_id for students
    $studentId = null;
    if ($role === 'student') {
        $prefix = date('Y'); // e.g., 2026
        // Loop until we generate a unique student_id
        do {
            $random = random_int(0, 999999); // 0 - 999999
            $candidate = $prefix . '-' . str_pad((string)$random, 6, '0', STR_PAD_LEFT);

            $chk = $conn->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
            $chk->bind_param("s", $candidate);
            $chk->execute();
            $chk->store_result();
            $exists = $chk->num_rows > 0;
            $chk->close();
        } while ($exists);

        $studentId = $candidate;
    }
    

    // Insert user into the database
    if ($role === 'student') {
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, student_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $email, $hashed_password, $role, $studentId);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);
    }


    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Something went wrong. Please try again.'); window.history.back();</script>";
    }
    
    $stmt->close();
    
    $conn->close(); 
}
?>
