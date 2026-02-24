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

// Ensure only caretaker can access
if ($role !== 'caretaker') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$message = '';
$error = '';

// Handle Post Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title = $_POST['title'];
    $msg_content = $_POST['message'];

    if (!empty($title) && !empty($msg_content)) {
        // 1. Insert into announcements table
        $stmt = $conn->prepare("INSERT INTO announcements (caretaker_id, title, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $msg_content);
        
        if ($stmt->execute()) {
            $announcement_id = $stmt->insert_id;
            $stmt->close();

            // 2. Create a notification for this announcement
            // First, insert into notifications table
            $stmt_notif = $conn->prepare("INSERT INTO notifications (sender_id, title, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt_notif->bind_param("iss", $user_id, $title, $msg_content);
            
            if ($stmt_notif->execute()) {
                $notification_id = $stmt_notif->insert_id;
                $stmt_notif->close();

                // 3. Distribute to all tenants managed by this caretaker
                // Find all tenants
                $sql_tenants = "SELECT u.id FROM users u JOIN rentals r ON u.id = r.tenant_id WHERE u.role = 'tenant' AND r.caretaker_id = ? AND u.status = 'active'";
                $stmt_tenants = $conn->prepare($sql_tenants);
                $stmt_tenants->bind_param("i", $user_id);
                $stmt_tenants->execute();
                $result_tenants = $stmt_tenants->get_result();

                if ($result_tenants) {
                    $stmt_recipient = $conn->prepare("INSERT INTO notification_recipients (notification_id, recipient_id) VALUES (?, ?)");
                    while ($tenant = $result_tenants->fetch_assoc()) {
                        $stmt_recipient->bind_param("ii", $notification_id, $tenant['id']);
                        $stmt_recipient->execute();
                    }
                    $stmt_recipient->close();
                }
                $stmt_tenants->close();
            }

            $message = "Announcement posted successfully.";
        } else {
            $error = "Error posting announcement: " . $conn->error;
        }
    } else {
        $error = "Title and message are required.";
    }
}

// Handle Delete Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    
    // Verify ownership
    $check_stmt = $conn->prepare("SELECT id FROM announcements WHERE id = ? AND caretaker_id = ?");
    $check_stmt->bind_param("ii", $announcement_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        
        $del_stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $del_stmt->bind_param("i", $announcement_id);
        
        if ($del_stmt->execute()) {
            $message = "Announcement deleted successfully.";
        } else {
            $error = "Error deleting announcement: " . $conn->error;
        }
        $del_stmt->close();
    } else {
        $check_stmt->close();
        $error = "Invalid announcement or permission denied.";
    }
}

// Fetch Past Announcements
$announcements = [];
$fetch_sql = "SELECT * FROM announcements WHERE caretaker_id = ? ORDER BY created_at DESC";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_stmt->bind_param("i", $user_id);
$fetch_stmt->execute();
$result_fetch = $fetch_stmt->get_result();
if ($result_fetch) {
    while ($row = $result_fetch->fetch_assoc()) {
        $announcements[] = $row;
    }
}
$fetch_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/caretaker_dashboard.css">
    <style>
        .announcement-list {
            margin-top: 30px;
        }
        .announcement-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            position: relative;
        }
        .announcement-item h3 {
            margin-top: 0;
            color: #333;
        }
        .announcement-date {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 10px;
            display: block;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Announcements</h1>
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

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Post Announcement Section -->
        <div class="announcement-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; color: #333; font-size: 1.2rem; margin-bottom: 15px;">Post New Announcement</h2>
            <form action="caretaker_announcements.php" method="POST">
                <div style="margin-bottom: 15px;">
                    <label for="title" style="display: block; margin-bottom: 5px; color: #666;">Title</label>
                    <input type="text" id="title" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="message" style="display: block; margin-bottom: 5px; color: #666;">Message</label>
                    <textarea id="message" name="message" rows="4" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
                <button type="submit" name="post_announcement" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Post Announcement</button>
            </form>
        </div>

        <div class="announcement-list">
            <h2 style="color: #333;">Past Announcements</h2>
            <?php if (empty($announcements)): ?>
                <p>No announcements posted yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-item">
                        <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                        <span class="announcement-date">Posted on <?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
                        <p><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                        
                        <form action="caretaker_announcements.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                            <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                            <button type="submit" name="delete_announcement" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
