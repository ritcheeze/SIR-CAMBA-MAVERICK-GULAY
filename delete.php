<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/db.php';

// Admin-only
$meStmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
$meStmt->bind_param('i', $_SESSION['user_id']);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();
$meStmt->close();

$profRole = $me['role'] ?? null;
$profRole = is_string($profRole) ? strtolower(trim($profRole)) : $profRole;
if ($profRole !== 'admin') {
    echo "<script>alert('Access denied. Admin account required.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Invalid request method.'); window.location.href='prof_dashboard.php';</script>";
    exit;
}

$targetUserId = isset($_POST['student_user_id']) ? (int)$_POST['student_user_id'] : 0;
if ($targetUserId <= 0) {
    echo "<script>alert('Invalid student id.'); window.location.href='prof_dashboard.php';</script>";
    exit;
}

// Delete full student data (account + enrollments + grades + profile).
// Use transaction to keep data consistent.
$conn->begin_transaction();

try {
    // Grades for this student
    $gradeDel = $conn->prepare('DELETE FROM student_grades WHERE user_id = ?');
    $gradeDel->bind_param('i', $targetUserId);
    $gradeDel->execute();
    $gradeDel->close();

    // Enrollments for this student
    $enrDel = $conn->prepare('DELETE FROM student_enrollments WHERE user_id = ?');
    $enrDel->bind_param('i', $targetUserId);
    $enrDel->execute();
    $enrDel->close();

    // Profile row for this student
    $profileDel = $conn->prepare('DELETE FROM student_profiles WHERE user_id = ?');
    $profileDel->bind_param('i', $targetUserId);
    $profileDel->execute();
    $profileDel->close();

    // Finally delete the user account
    $userDel = $conn->prepare('DELETE FROM users WHERE id = ?');
    $userDel->bind_param('i', $targetUserId);
    $userDel->execute();
    $userDel->close();

    $conn->commit();
    header('Location: prof_dashboard.php?status=student_deleted');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    echo "<script>alert('Delete failed: " . addslashes($e->getMessage()) . "'); window.location.href='prof_dashboard.php';</script>";
    exit;
}


