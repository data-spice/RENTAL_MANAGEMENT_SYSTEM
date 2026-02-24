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

$error_message = '';
$success_message = '';

// Handle issue submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_issue'])) {
    if ($user_id) {
        $issue_type = $_POST['issue_type'];
        $description = trim($_POST['description']);
        $room_number = null;

        // Get room number from active rental
        $stmt_room = $conn->prepare("SELECT room_number FROM rentals WHERE tenant_id = ? AND status = 'active'");
        $stmt_room->bind_param("i", $user_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        if ($result_room->num_rows > 0) {
            $room_data = $result_room->fetch_assoc();
            $room_number = $room_data['room_number'];
        }
        $stmt_room->close();

        if (!empty($issue_type) && !empty($description)) {
            $stmt = $conn->prepare("INSERT INTO issues (user_id, issue_type, room_number, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $issue_type, $room_number, $description);
            if ($stmt->execute()) {
                header('Location: issues.php?success=' . urlencode('Issue submitted successfully.'));
                exit();
            } else {
                $error_message = "Error: Could not submit the issue.";
            }
            $stmt->close();
        } else {
            $error_message = "Please fill out all fields.";
        }
    } else {
        $error_message = "Could not identify user. Please login again.";
    }
}

// Handle issue completion by caretaker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_issue'])) {
    $issue_id = $_POST['issue_id'];
    if ($user_id && $issue_id && $role === 'caretaker') {
        // Update issue status
        $stmt_update = $conn->prepare("UPDATE issues SET status = 'resolved' WHERE id = ?");
        $stmt_update->bind_param("i", $issue_id);
        
        if ($stmt_update->execute()) {
                // Get tenant user_id and issue description
                $stmt_issue_details = $conn->prepare("SELECT user_id, description FROM issues WHERE id = ?");
                $stmt_issue_details->bind_param("i", $issue_id);
                $stmt_issue_details->execute();
                $result_issue_details = $stmt_issue_details->get_result();
                if ($result_issue_details->num_rows > 0) {
                    $issue_details = $result_issue_details->fetch_assoc();
                    $tenant_id = $issue_details['user_id'];
                    $issue_description = $issue_details['description'];

                    // Create a notification for the tenant
                    $notification_title = "Issue Resolved";
                    $notification_message = "Your issue '" . substr($issue_description, 0, 50) . "...' has been marked as resolved by the caretaker.";
                    $stmt_notification = $conn->prepare("INSERT INTO notifications (sender_id, title, message, issue_id) VALUES (?, ?, ?, ?)");
                    $stmt_notification->bind_param("issi", $user_id, $notification_title, $notification_message, $issue_id);
                    $stmt_notification->execute();
                    $notification_id = $stmt_notification->insert_id;

                    // Add recipient to the notification
                    $stmt_recipient = $conn->prepare("INSERT INTO notification_recipients (notification_id, recipient_id) VALUES (?, ?)");
                    $stmt_recipient->bind_param("ii", $notification_id, $tenant_id);
                    $stmt_recipient->execute();
                }

            header('Location: issues.php?success=' . urlencode('Issue has been marked as resolved.'));
            exit();
        } else {
            $error_message = "Error: Could not complete the issue.";
        }
    }
}

// Handle issue removal (soft delete by changing status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_issue'])) {
    $issue_id = $_POST['issue_id'];
    if ($user_id && $issue_id) {
        $sql = "UPDATE issues SET status = 'closed' WHERE id = ?";
        // Allow landlord/caretaker to close any issue, tenant only their own.
        if ($role === 'tenant') {
            $sql .= " AND user_id = ?";
        }
        $stmt = $conn->prepare($sql);

        if ($role === 'tenant') {
            $stmt->bind_param("ii", $issue_id, $user_id);
        } else {
            $stmt->bind_param("i", $issue_id);
        }

        if ($stmt->execute()) {
            header('Location: issues.php?success=' . urlencode('Issue has been closed.'));
            exit();
        } else {
            $error_message = "Error: Could not remove the issue.";
        }
        $stmt->close();
    }
}


// Fetch issues based on role
$issues = [];
if ($role === 'tenant') {
    $sql = "SELECT i.id, i.issue_type, i.description, i.status, i.created_at, u.fullname 
            FROM issues i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'pending' AND i.user_id = ?
            ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} elseif ($role === 'caretaker') {
    $sql = "SELECT i.id, i.issue_type, i.description, i.status, i.created_at, u.fullname 
            FROM issues i 
            JOIN users u ON i.user_id = u.id 
            JOIN rentals r ON i.user_id = r.tenant_id
            WHERE i.status = 'pending' AND r.caretaker_id = ?
            ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    $sql = "SELECT i.id, i.issue_type, i.description, i.status, i.created_at, u.fullname 
            FROM issues i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'pending'
            ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
}
$stmt->close();

if(isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if(isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div></div>
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
            <div class="issues-header">
                <h1 class="page-title">Issues</h1>
                <div class="action-buttons">
                    <?php if ($role === 'tenant'): ?>
                    <button class="action-btn file-issue-btn">File Issue +</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="issues-list">
                <div class="list-subtitle">Pending</div>
                <?php if (!empty($issues)): ?>
                    <?php foreach($issues as $issue): ?>
                        <div class="issue-item">
                            <span class="issue-tag"><?php echo htmlspecialchars($issue['issue_type']); ?></span>
                            <div class="issue-description"><?php echo htmlspecialchars($issue['description']); ?></div>
                            <?php if ($role !== 'tenant'): ?>
                                <div class="issue-reporter">By: <?php echo htmlspecialchars($issue['fullname']); ?></div>
                            <?php endif; ?>
                            <form method="POST" action="issues.php" style="margin: 0;">
                                <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                                <button type="submit" name="complete_issue" class="remove-btn">Complete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-issues">
                        <p>No pending issues found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Issue Submission Modal -->
    <div class="modal-overlay" id="issue-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">New Issue</h2>
                <button class="close-modal-btn" id="close-modal">&times;</button>
            </div>
            <form action="issues.php" method="POST">
                <div class="form-group">
                    <label for="issue_type">Issue Type</label>
                    <select name="issue_type" id="issue_type" class="form-input">
                        <option value="repair">Repair</option>
                        <option value="complaint">Complaint</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="room_number">Room Number</label>
                    <input type="text" name="room_number" id="room_number" class="form-input" placeholder="Room Number" readonly>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" placeholder="Please describe the issue in detail." class="form-input"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit_issue" class="submit-btn">Submit Issue</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/issues.js"></script>
</body>
</html>
