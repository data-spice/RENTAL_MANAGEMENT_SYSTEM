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

$conn = getDBConnection();

// Get user ID from username
$user_id = null;
if (isset($_SESSION['username'])) {
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_user->bind_param("s", $_SESSION['username']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['id'];
    }
    $stmt_user->close();
}

// Get pending issues count
$pending_issues_count = 0;
if ($user_id) {
    $sql_issues = "SELECT COUNT(*) AS count FROM issues WHERE status = 'pending'";
    if ($role === 'tenant') {
        $sql_issues .= " AND user_id = ?";
    }
    $stmt_issues = $conn->prepare($sql_issues);
    if ($role === 'tenant') {
        $stmt_issues->bind_param("i", $user_id);
    }
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

// Get rent status for the current month
$rent_payment_status = 'N/A';
$rent_description = 'Not applicable';
if ($user_id && $role === 'tenant') {
    $payment_for_month = date('Y-m-01');
    $sql_rent = "SELECT p.status 
                 FROM rentals r
                 LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
                 WHERE r.tenant_id = ? AND r.status = 'active'";
    
    $stmt_rent = $conn->prepare($sql_rent);
    $stmt_rent->bind_param("si", $payment_for_month, $user_id);
    $stmt_rent->execute();
    $result_rent = $stmt_rent->get_result();

    if ($result_rent && $result_rent->num_rows > 0) {
        $row_rent = $result_rent->fetch_assoc();
        $rent_payment_status = $row_rent['status'] ? ucfirst(str_replace('_', ' ', $row_rent['status'])) : 'Not Paid';
    } else {
        $rent_payment_status = 'Not Paid';
    }

    if ($rent_payment_status === 'Paid') {
        $next_month = date('M jS', strtotime('first day of next month'));
        $rent_description = 'Next payment due ' . $next_month;
    } else {
        $rent_description = 'Payment for this month is due';
    }
}

// Get latest announcement
$latest_announcement = null;
if ($user_id && $role === 'tenant') {
    // Find caretaker ID first
    $stmt_ct = $conn->prepare("SELECT caretaker_id FROM rentals WHERE tenant_id = ? AND status = 'active' LIMIT 1");
    $stmt_ct->bind_param("i", $user_id);
    $stmt_ct->execute();
    $res_ct = $stmt_ct->get_result();
    if ($res_ct && $res_ct->num_rows > 0) {
        $ct = $res_ct->fetch_assoc();
        $caretaker_id = $ct['caretaker_id'];
        
        $stmt_ann = $conn->prepare("SELECT title, message, created_at FROM announcements WHERE caretaker_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt_ann->bind_param("i", $caretaker_id);
        $stmt_ann->execute();
        $res_ann = $stmt_ann->get_result();
        if ($res_ann && $res_ann->num_rows > 0) {
            $latest_announcement = $res_ann->fetch_assoc();
        }
        $stmt_ann->close();
    }
    $stmt_ct->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard</h1>
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

        <!-- Announcement Card -->
        <div class="announcement-card">
            <div class="announcement-content">
                <h2 class="announcement-title">Announcements</h2>
                <?php if ($latest_announcement): ?>
                    <h3 style="margin: 5px 0 10px 0; font-size: 1.1em; color: white;"><?php echo htmlspecialchars($latest_announcement['title']); ?></h3>
                    <p class="announcement-text" style="margin-bottom: 5px;"><?php echo htmlspecialchars($latest_announcement['message']); ?></p>
                    <small style="color: rgba(255,255,255,0.8); display: block; margin-bottom: 10px;"><?php echo date('M j, Y', strtotime($latest_announcement['created_at'])); ?></small>
                <?php else: ?>
                    <p class="announcement-text">No announcements at this time.</p>
                <?php endif; ?>
                <a href="notifications.php" class="see-more-btn" style="text-decoration: none; display: inline-block; cursor: pointer;">
                    See More →
                </a>
            </div>
            <div class="announcement-image">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <?php if ($role === 'tenant'): ?>
            <a href="rent.php" class="stat-card <?php echo ($rent_payment_status === 'Paid') ? 'green' : 'orange'; ?>" style="text-decoration: none; color: white;">
                <div class="stat-header">
                    <span class="stat-label">RENT STATUS</span>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $rent_payment_status; ?></div>
                <div class="stat-description"><?php echo $rent_description; ?></div>
            </a>
            <?php else: ?>
            <div class="stat-card green">
                <div class="stat-header">
                    <span class="stat-label">RENT STATUS</span>
                </div>
                <div class="stat-value">N/A</div>
                <div class="stat-description">Not applicable for this role</div>
            </div>
            <?php endif; ?>

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
