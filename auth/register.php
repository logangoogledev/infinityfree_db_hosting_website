<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    header('Location: ../index.php?error=Please fill in all fields');
    exit;
}

if (strlen($password) < 6) {
    header('Location: ../index.php?error=Password must be at least 6 characters');
    exit;
}

if ($password !== $confirm_password) {
    header('Location: ../index.php?error=Passwords do not match');
    exit;
}

// Check if email already exists
$check_query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    header('Location: ../index.php?error=Email already registered');
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$insert_query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    header('Location: ../index.php?success=Account created! You can now login');
    exit;
} else {
    header('Location: ../index.php?error=Registration failed. Please try again');
    exit;
}
?>
