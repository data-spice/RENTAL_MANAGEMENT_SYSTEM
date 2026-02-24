<?php
require_once 'config.php';

// Check if user is logged in and is a caretaker
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'caretaker') {
    header('Location: index.php?error=' . urlencode('You do not have permission to view this page'));
    exit();
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

$conn = getDBConnection();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname_tenant = $_POST['fullname'];
    $room_number = $_POST['room_number'];
    $id_number = $_POST['id_number'];
    $phone_number = $_POST['phone_number'];
    $new_email = trim($_POST['email']);
    $new_username = trim($_POST['username']);
    
    // Default password for new tenants, can be changed later
    $new_password = password_hash('password123', PASSWORD_DEFAULT);

    // Basic validation
    if (empty($fullname_tenant) || empty($room_number) || empty($id_number) || empty($phone_number) || empty($new_email) || empty($new_username)) {
        $error_message = "All fields are required.";
        $conn->close();
        return;
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
        $conn->close();
        return;
    }

    // Check for uniqueness of email and username
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
    $stmt_check->bind_param("ss", $new_email, $new_username);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $error_message = "Email or Username already exists.";
        $conn->close();
        return;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert new user
        $stmt_user = $conn->prepare("INSERT INTO users (fullname, email, username, password, phone_number, role) VALUES (?, ?, ?, ?, ?, 'tenant')");
        $stmt_user->bind_param("sssss", $fullname_tenant, $new_email, $new_username, $new_password, $phone_number);
        if (!$stmt_user->execute()) {
            throw new Exception("Error registering user: " . $stmt_user->error);
        }
        $new_tenant_id = $stmt_user->insert_id;
        $stmt_user->close();

        // Assuming a default property_id for now, replace with actual logic to select property
        $property_id = 1; 
        $rent_amount = 10000.00; // Default rent amount, replace with actual logic
        $start_date = date('Y-m-d'); // Current date as start date

        // Insert new rental
        $stmt_rental = $conn->prepare("INSERT INTO rentals (property_id, tenant_id, caretaker_id, room_number, rent_amount, start_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt_rental->bind_param("iiisds", $property_id, $new_tenant_id, $_SESSION['user_id'], $room_number, $rent_amount, $start_date);
        if (!$stmt_rental->execute()) {
            throw new Exception("Error registering rental: " . $stmt_rental->error);
        }
        $stmt_rental->close();

        $conn->commit();
        $success_message = "Tenant registered successfully. Username: $new_username, Password: password123";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Registration failed: " . $e->getMessage();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Tenant - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Register Tenant</h1>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <div class="register-tenant-container">
            
            <form action="register_tenant.php" method="post" class="register-form">
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" class="form-input form-input-light" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-input form-input-light" required>
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" class="form-input form-input-light" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" class="form-input form-input-light" required>
                </div>
                 
                <div class="form-group">
                    <label for="room_number">Room Number:</label>
                    <input type="text" id="room_number" name="room_number" class="form-input form-input-light" required>
                </div>

                <div class="form-group">
                    <label for="id_number">ID Number:</label>
                    <input type="text" id="id_number" name="id_number" class="form-input form-input-light" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-input form-input-light" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Register Tenant</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-notification" class="toast-notification"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('toast-notification');

            // Function to display toast
            function showToast(message, type = 'success') {
                toast.textContent = message;
                toast.className = 'toast-notification show ' + type;
                setTimeout(() => {
                    toast.className = toast.className.replace('show', '');
                }, 3000);
            }

            // Check for PHP messages and display toast
            const successMessage = "<?php echo isset($success_message) ? htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') : ''; ?>";
            const errorMessage = "<?php echo isset($error_message) ? htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') : ''; ?>";

            if (successMessage) {
                showToast(successMessage, 'success');
            } else if (errorMessage) {
                showToast(errorMessage, 'error');
            }
        });
    </script>
</body>
</html>
