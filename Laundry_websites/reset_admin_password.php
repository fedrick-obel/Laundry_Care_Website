
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

require_once "db_connect.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (strlen($newPassword) < 12) {
        $error = "Use a password with at least 12 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "The passwords do not match.";
    } else {
        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "UPDATE admins SET password_hash = ? WHERE admin_id = ?"
        );

        $stmt->bind_param(
            "si",
            $passwordHash,
            $_SESSION["admin_id"]
        );

        if ($stmt->execute() && $stmt->affected_rows === 1) {
            $message = "Password changed successfully. Delete this file now.";
        } else {
            $error = "Password was not changed. Please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Admin Password | Laundry Care</title>
</head>
<body>
    <h2>Change Admin Password</h2>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
    <?php endif; ?>

    <?php if ($message === ""): ?>
        <form method="POST" action="reset_admin_password.php">
            <label for="new_password">New password</label><br>
            <input type="password" id="new_password"
                   name="new_password" minlength="12"
                   autocomplete="new-password" required><br><br>

            <label for="confirm_password">Confirm new password</label><br>
            <input type="password" id="confirm_password"
                   name="confirm_password" minlength="12"
                   autocomplete="new-password" required><br><br>

            <button type="submit">Change Password</button>
        </form>
    <?php endif; ?>

    <p><a href="admin_dashboard.php">Return to Dashboard</a></p>
</body>
</html>
