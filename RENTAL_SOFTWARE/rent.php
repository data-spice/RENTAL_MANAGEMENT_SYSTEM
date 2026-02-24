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

// Handle "Check Payment" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_payment'])) {
    if ($user_id && $role === 'tenant') {
        $rental_id = null;
        // Get the active rental_id for the current tenant
        $stmt_rental = $conn->prepare("SELECT id, rent_amount FROM rentals WHERE tenant_id = ? AND status = 'active'");
        $stmt_rental->bind_param("i", $user_id);
        $stmt_rental->execute();
        $result_rental = $stmt_rental->get_result();
        if ($result_rental->num_rows > 0) {
            $row_rental = $result_rental->fetch_assoc();
            $rental_id = $row_rental['id'];
            $rent_amount_from_rental = $row_rental['rent_amount'];
        }
        $stmt_rental->close();

        if ($rental_id) {
            $payment_for_month = date('Y-m-01');
            $current_time = date('Y-m-d H:i:s');

            // Check if payment already exists for this month and rental
            $stmt_check = $conn->prepare("SELECT id FROM payments WHERE rental_id = ? AND payment_for_month = ?");
            $stmt_check->bind_param("is", $rental_id, $payment_for_month);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Update existing payment
                $stmt_update = $conn->prepare("UPDATE payments SET status = 'paid', amount_paid = ?, payment_date = ? WHERE rental_id = ? AND payment_for_month = ?");
                $stmt_update->bind_param("dsis", $rent_amount_from_rental, $current_time, $rental_id, $payment_for_month);
                if ($stmt_update->execute()) {
                    error_log("Payment UPDATE successful for rental_id: $rental_id, month: $payment_for_month");
                } else {
                    error_log("Payment UPDATE failed: " . $stmt_update->error);
                }
                $stmt_update->close();
            } else {
                // Insert new payment
                $stmt_insert = $conn->prepare("INSERT INTO payments (rental_id, payment_for_month, amount_due, amount_paid, status, payment_date) VALUES (?, ?, ?, ?, 'paid', ?)");
                $stmt_insert->bind_param("isdds", $rental_id, $payment_for_month, $rent_amount_from_rental, $rent_amount_from_rental, $current_time);
                if ($stmt_insert->execute()) {
                    error_log("Payment INSERT successful for rental_id: $rental_id, month: $payment_for_month");
                } else {
                    error_log("Payment INSERT failed: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            }
            header('Location: rent.php?success=' . urlencode('Rent marked as paid for ' . date('F Y')));
            exit();
        } else {
            // No active rental found for the tenant
            header('Location: rent.php?error=' . urlencode('No active rental found for your account.'));
            exit();
        }
    } else {
        header('Location: rent.php?error=' . urlencode('Unauthorized action.'));
        exit();
    }
}


$rent_amount = 0;
$payment_status = 'N/A';
$current_month = date('m/Y');

if ($user_id && $role === 'tenant') {
    $sql = "SELECT r.rent_amount, p.status 
            FROM rentals r
            LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_for_month = ?
            WHERE r.tenant_id = ? AND r.status = 'active'";
    
    $payment_for_month = date('Y-m-01');

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $payment_for_month, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rent_amount = $row['rent_amount'];
        $payment_status = $row['status'] ? ucfirst(str_replace('_', ' ', $row['status'])) : 'Not Paid';
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Rent - PangaRms</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/inputs.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Pay Rent</h1>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <?php if ($role === 'tenant'): ?>
            <div class="rent-container">
                <div class="rent-header">
                    <div class="rent-period">Rent for <?php echo $current_month; ?></div>
                    <div class="rent-status <?php echo strtolower(str_replace(' ', '-', $payment_status)); ?>"><?php echo $payment_status; ?></div>
                </div>
                <div class="rent-amount"><span class="currency">Ksh</span> <?php echo number_format($rent_amount, 2); ?></div>
            </div>

            <div class="payment-details">
                <div class="payment-title">Payment Details</div>
                <form action="rent.php" method="POST">
                    <div class="phone-input-wrapper">
                        <input type="text" class="form-input form-input-light" placeholder="+254XXXXXXXXX" name="phone_number">
                    </div>
                    <div class="payment-info">Paybill: 234544 Account No: 2345</div>
                    <div class="payment-actions">
                        <button class="payment-btn send-prompt-btn" type="button">Send Prompt</button>
                        <button class="payment-btn check-payment-btn" type="submit" name="check_payment">Check Payment</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="rent-container">
                <p>This page is for tenants only.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>