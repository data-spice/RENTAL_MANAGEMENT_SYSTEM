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
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Handle deactivate action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_tenant'])) {
    $tenant_id = $_POST['tenant_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->close();
    header('Location: tenants.php?success=' . urlencode('Tenant deactivated successfully.'));
    exit();
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tenant'])) {
    $tenant_id = $_POST['tenant_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->close();
    header('Location: tenants.php?success=' . urlencode('Tenant deleted successfully.'));
    exit();
}


// Fetch all tenants with their rent status for the current month
$tenants = [];
$payment_for_month = date('Y-m-01');
$search_query = $_GET['search'] ?? '';

$sql = "SELECT u.id, u.fullname, u.email, u.phone_number, u.status, p.status as rent_status, r.room_number
        FROM users u
        LEFT JOIN rentals r ON u.id = r.tenant_id AND r.status = 'active'
        LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
        WHERE u.role = 'tenant' AND r.caretaker_id = ?";

$params = [$payment_for_month, $user_id];
$types = "si";

if (!empty($search_query)) {
    $sql .= " AND (u.fullname LIKE ? OR r.room_number LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= "ss";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tenants[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Tenants</h1>
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

        <div class="tenant-list-container">
            <div class="tenant-search-bar">
                <form action="tenants.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search by name or room number..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Room Number</th>
                        <th>Status</th>
                        <th>Rent Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tenants)): ?>
                        <?php foreach($tenants as $tenant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tenant['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['phone_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($tenant['room_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tenant['status']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['rent_status'] ?? 'Not Paid'); ?></td>
                                <td>
                                    <form method="POST" action="tenants.php" style="display: inline-block;">
                                        <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                        <button type="submit" name="deactivate_tenant" class="action-btn repair">Deactivate</button>
                                    </form>
                                    <form method="POST" action="tenants.php" style="display: inline-block;">
                                        <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                        <button type="submit" name="delete_tenant" class="action-btn complain">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No tenants found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>