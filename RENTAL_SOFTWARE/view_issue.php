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
$role = $_SESSION['role']; // Directly use role from session
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

$issue = null;
if (isset($_GET['issue_id'])) {
    $issue_id = $_GET['issue_id'];

    $sql = "SELECT i.id, i.issue_type, i.description, i.status, i.created_at, u.fullname, i.user_id 
            FROM issues i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $issue = $result->fetch_assoc();
        // If the user is a tenant, check if they are the owner of the issue
        if ($role === 'tenant' && $issue['user_id'] !== $user_id) {
            $issue = null;
        }
    }
    $stmt->close();
}

// Handle marking issue as finished by tenant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_finished'])) {
    $issue_id = $_POST['issue_id'];
    $notification_id = $_POST['notification_id'];

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Issue - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">View Issue</h1>
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

        <div class="issues-container">
            <?php if ($issue): ?>
                <div class="issue-item">
                    <span class="issue-tag"><?php echo htmlspecialchars($issue['issue_type']); ?></span>
                    <div class="issue-description"><?php echo htmlspecialchars($issue['description']); ?></div>
                    <div class="issue-reporter">Status: <?php echo htmlspecialchars($issue['status']); ?></div>
                </div>
                <?php if ($role === 'tenant' && $issue['status'] === 'resolved'): ?>
                <form method="POST" action="view_issue.php?issue_id=<?php echo $issue['id']; ?>" style="margin-top: 20px;">
                    <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                    <input type="hidden" name="notification_id" value="<?php echo $_GET['notification_id'] ?? ''; ?>">
                    <button type="submit" name="mark_as_finished" class="submit-btn">Mark as Finished</button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-issues">
                    <p>Issue not found or you do not have permission to view it.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
