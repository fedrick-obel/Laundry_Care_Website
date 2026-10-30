
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_dashboard.php");
    exit;
}

require_once "db_connect.php";

$bookingId = filter_input(INPUT_POST, "booking_id", FILTER_VALIDATE_INT);
$newStatus = $_POST["booking_status"] ?? "";

$allowedStatuses = [
    "Pending",
    "In Progress",
    "Completed",
    "Cancelled"
];

if (!$bookingId || !in_array($newStatus, $allowedStatuses, true)) {
    $conn->close();
    header("Location: admin_dashboard.php");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE bookings SET booking_status = ? WHERE booking_id = ?"
);

$stmt->bind_param("si", $newStatus, $bookingId);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: admin_dashboard.php");
exit;
?>
