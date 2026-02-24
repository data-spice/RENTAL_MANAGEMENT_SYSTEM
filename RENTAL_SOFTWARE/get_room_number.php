<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

$room_number = null;
$stmt = $conn->prepare("SELECT room_number FROM rentals WHERE tenant_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $room_number = $row['room_number'];
}
$stmt->close();
$conn->close();

if ($room_number) {
    echo json_encode(['success' => true, 'room_number' => $room_number]);
} else {
    echo json_encode(['success' => false, 'message' => 'No active rental found for this user.']);
}
?>