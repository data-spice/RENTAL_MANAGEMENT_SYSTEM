<?php
require_once 'config.php';

$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        handleRegistration($conn);
    } elseif ($action === 'login') {
        handleLogin($conn);
    }
}

function handleRegistration($conn) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    // Validation
    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        redirect('index.php', 'All fields are required', 'error');
        return;
    }

    if ($role === 'caretaker' && empty($phone_number)) {
        redirect('index.php', 'Phone number is required for caretakers', 'error');
        return;
    }
    
    if ($password !== $confirm_password) {
        redirect('index.php', 'Passwords do not match', 'error');
        return;
    }
    
    if (strlen($password) < 6) {
        redirect('index.php', 'Password must be at least 6 characters', 'error');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('index.php', 'Invalid email format', 'error');
        return;
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        redirect('index.php', 'Username or email already exists', 'error');
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role, phone_number, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $fullname, $email, $username, $hashed_password, $role, $phone_number);
    
    if ($stmt->execute()) {
        redirect('index.php', 'Registration successful! Please login.', 'success');
    } else {
        redirect('index.php', 'Registration failed. Please try again.', 'error');
    }
    
    $stmt->close();
}

function handleLogin($conn) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    
    if (empty($username) || empty($password)) {
        redirect('index.php', 'Please enter username and password', 'error');
        return;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, fullname, username, email, password, role FROM users WHERE (username = ? OR email = ?) AND role = ?");
    $stmt->bind_param("sss", $username, $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirect('index.php', 'Invalid credentials or role', 'error');
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Redirect to the appropriate dashboard
        if ($user['role'] === 'tenant') {
            header('Location: dashboard.php');
        } elseif ($user['role'] === 'caretaker') {
            header('Location: caretaker_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        redirect('index.php', 'Invalid credentials', 'error');
    }
    
    $stmt->close();
}

function redirect($page, $message, $type) {
    $encoded_message = urlencode($message);
    header("Location: $page?$type=$encoded_message");
    exit();
}

$conn->close();
?>