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

$user_id = null;
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    }
    $stmt->close();
}

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $notification_id = $_POST['notification_id'];
    if ($user_id && $notification_id) {
        $stmt = $conn->prepare("UPDATE notification_recipients SET is_read = TRUE, read_at = NOW() WHERE notification_id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: notifications.php');
        exit();
    }
}

// Handle marking issue as finished by tenant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_finished'])) {
    $notification_id = $_POST['notification_id'];
    $issue_id = $_POST['issue_id'];

    if ($user_id && $notification_id && $issue_id) {
        // Mark notification as read
        $stmt_notification = $conn->prepare("UPDATE notification_recipients SET is_read = TRUE, read_at = NOW() WHERE notification_id = ? AND recipient_id = ?");
        $stmt_notification->bind_param("ii", $notification_id, $user_id);
        $stmt_notification->execute();
        $stmt_notification->close();

        // Close the issue
        $stmt_issue = $conn->prepare("UPDATE issues SET status = 'closed' WHERE id = ? AND user_id = ?");
        $stmt_issue->bind_param("ii", $issue_id, $user_id);
        $stmt_issue->execute();
        $stmt_issue->close();

        header('Location: notifications.php?success=' . urlencode('Issue marked as finished.'));
        exit();
    }
}

// Fetch unread notifications
$notifications = [];
if ($user_id) {
    $sql = "SELECT n.id, n.title, n.message, n.created_at, u.role as sender_role, s.fullname as sender_name, n.issue_id
            FROM notifications n
            JOIN notification_recipients nr ON n.id = nr.notification_id
            JOIN users u ON n.sender_id = u.id
            JOIN users s ON n.sender_id = s.id
            WHERE nr.recipient_id = ? AND nr.is_read = FALSE
            ORDER BY n.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Notifications</h1>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <div class="notifications-container">
            <?php if (!empty($notifications)): ?>
                <?php foreach($notifications as $notification): ?>
                    <?php
                    $item_html = '<span class="sender-tag">' . htmlspecialchars($notification['sender_role']) . '</span>' .
                                 '<div class="notification-content"><strong>' . htmlspecialchars($notification['title']) . '</strong>: ' . htmlspecialchars($notification['message']) . '</div>';

                    if ($notification['title'] === 'Issue Resolved' && $role === 'tenant') {
                        echo '<a href="view_issue.php?issue_id=' . $notification['issue_id'] . '&notification_id=' . $notification['id'] . '" class="notification-item">';
                        echo $item_html;
                        echo '</a>';
                    } else {
                        echo '<div class="notification-item">';
                        echo $item_html;
                        echo '<form method="POST" action="notifications.php" style="margin: 0;">' .
                             '<input type="hidden" name="notification_id" value="' . $notification['id'] . '">' .
                             '<button type="submit" name="mark_as_read" class="mark-read-btn">Mark as Read</button>' .
                             '</form>';
                        echo '</div>';
                    }
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <p>No unread notifications.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>