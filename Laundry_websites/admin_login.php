
<?php
session_start();
require_once "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT admin_id, username, password_hash
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify(
            $password,
            $admin["password_hash"]
        )) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_username"] = $admin["username"];

            $stmt->close();
            $conn->close();

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
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
    <title>Admin Login | Laundry Care</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f8fb;
            color: #243447;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 18px #00000015;
        }

        h1 {
            text-align: center;
            color: #123b5d;
        }

        p {
            text-align: center;
            color: #526477;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccd6df;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #176b87;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #123b5d;
        }

        .error {
            background: #fff0f0;
            color: #a32121;
            padding: 10px;
            border-radius: 6px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #176b87;
        }
    </style>
</head>

<body>
    <main class="login-box">
        <h1>Admin Login</h1>
        <p>Laundry Care Management System</p>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Log In</button>
        </form>

        <a class="back-link" href="index.html">
            Back to Homepage
        </a>
    </main>
</body>
</html>
