<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php?error=' . urlencode('Please login first'));
    exit();
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Handle tenant deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_tenant'])) {
    $tenant_id_to_deactivate = $_POST['tenant_id'];
    $conn_deactivate = getDBConnection();
    $stmt_deactivate = $conn_deactivate->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'tenant'");
    $stmt_deactivate->bind_param("i", $tenant_id_to_deactivate);
    if ($stmt_deactivate->execute()) {
        header('Location: caretaker_dashboard.php?success=' . urlencode('Tenant deactivated successfully.'));
    } else {
        $error_message = "Error deactivating tenant: " . $stmt_deactivate->error;
    }
    $stmt_deactivate->close();
    $conn_deactivate->close();
    exit();
}

// Handle tenant deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tenant'])) {
    $tenant_id_to_delete = $_POST['tenant_id'];
    $conn_delete = getDBConnection();
    $stmt_delete = $conn_delete->prepare("DELETE FROM users WHERE id = ? AND role = 'tenant'");
    $stmt_delete->bind_param("i", $tenant_id_to_delete);
    if ($stmt_delete->execute()) {
        header('Location: caretaker_dashboard.php?success=' . urlencode('Tenant deleted successfully.'));
    } else {
        $error_message = "Error deleting tenant: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    $conn_delete->close();
    exit();
}

// Get pending issues count
$pending_issues_count = 0;
if ($user_id) {
    $sql_issues = "SELECT COUNT(*) AS count FROM issues i JOIN rentals r ON i.user_id = r.tenant_id WHERE i.status = 'pending' AND r.caretaker_id = ?";
    $stmt_issues = $conn->prepare($sql_issues);
    $stmt_issues->bind_param("i", $user_id);
    $stmt_issues->execute();
    $result_issues = $stmt_issues->get_result();
    if ($result_issues) {
        $row_issues = $result_issues->fetch_assoc();
        $pending_issues_count = $row_issues['count'];
    }
    $stmt_issues->close();
}

// Get unread notifications count
$unread_notifications_count = 0;
if ($user_id) {
    $sql_notifications = "SELECT COUNT(*) as count FROM notification_recipients WHERE recipient_id = ? AND is_read = FALSE";
    $stmt_notifications = $conn->prepare($sql_notifications);
    $stmt_notifications->bind_param("i", $user_id);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();
    if ($result_notifications) {
        $row_notifications = $result_notifications->fetch_assoc();
        $unread_notifications_count = $row_notifications['count'];
    }
    $stmt_notifications->close();
}

// Get number of tenants
$tenants_count = 0;
$sql_tenants = "SELECT COUNT(*) AS count FROM users u JOIN rentals r ON u.id = r.tenant_id WHERE u.role = 'tenant' AND r.caretaker_id = ?";
$stmt_tenants = $conn->prepare($sql_tenants);
$stmt_tenants->bind_param("i", $user_id);
$stmt_tenants->execute();
$result_tenants = $stmt_tenants->get_result();
if ($result_tenants) {
    $row_tenants = $result_tenants->fetch_assoc();
    $tenants_count = $row_tenants['count'];
}
$stmt_tenants->close();

// Get monthly revenue
$monthly_revenue = 0;
if ($user_id) {
    $current_month = date('Y-m-01');
    $sql_revenue = "SELECT SUM(p.amount_paid) AS total_revenue 
                    FROM payments p 
                    JOIN rentals r ON p.rental_id = r.id 
                    WHERE r.caretaker_id = ? AND p.payment_for_month = ?";
    $stmt_revenue = $conn->prepare($sql_revenue);
    $stmt_revenue->bind_param("is", $user_id, $current_month);
    $stmt_revenue->execute();
    $result_revenue = $stmt_revenue->get_result();
    if ($result_revenue) {
        $row_revenue = $result_revenue->fetch_assoc();
        $monthly_revenue = $row_revenue['total_revenue'] ?? 0;
    }
    $stmt_revenue->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caretaker Dashboard - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/caretaker_dashboard.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Caretaker Dashboard</h1>
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

        <div class="revenue-card-container">
            <div class="stat-card blue large-card" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">MONTHLY REVENUE</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value">Ksh <?php echo number_format($monthly_revenue, 2); ?></div>
                <div class="stat-description">Revenue for this month</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid-small">
            <a href="notifications.php" class="stat-card orange" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">NOTIFICATIONS</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $unread_notifications_count; ?></div>
                <div class="stat-description">New messages waiting</div>
            </a>

            <a href="tenants.php" class="stat-card green" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">TENANTS</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="17" y1="11" x2="23" y2="11"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $tenants_count; ?></div>
                <div class="stat-description">Total registered tenants</div>
            </a>

            <a href="issues.php" class="stat-card teal issues-card" style="text-decoration: none; color: white;">
                <span class="issues-badge">ISSUES</span>
                <div class="stat-value"><?php echo $pending_issues_count; ?></div>
                <div class="stat-description">Increased from last month</div>
                <div class="trend-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="18 15 12 9 6 15"/>
                    </svg>
                </div>
            </a>
        </div>
        




    </div>

    <script>
        // The navigation is handled by server-side rendering, so this script is not needed.
    </script>
</body>
</html>
