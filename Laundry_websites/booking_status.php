
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$bookingStatus = "";
$error = "";
$searched = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $searched = true;

    $bookingId = trim($_POST["booking_id"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if (
        $bookingId === "" ||
        !ctype_digit($bookingId) ||
        (int)$bookingId < 1 ||
        $phone === "" ||
        strlen($phone) > 20
    ) {
        $error = "Please enter a valid booking reference and phone number.";
    } else {
        require_once "db_connect.php";

        try {
            // Match BOTH the booking reference and phone number.
            $sql = "SELECT booking_status
                    FROM bookings
                    WHERE booking_id = ? AND phone = ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);

            $id = (int)$bookingId;
            $stmt->bind_param("is", $id, $phone);
            $stmt->execute();

            $stmt->bind_result($foundStatus);

            if ($stmt->fetch()) {
                $bookingStatus = $foundStatus;
            } else {
                $error = "We could not find a booking matching those details. Please check them and try again.";
            }

            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Booking lookup failed: " . $e->getMessage());
            $error = "We could not check your booking right now. Please try again later.";
        }

        $conn->close();
    }
}

$safeStatus = htmlspecialchars(
    $bookingStatus,
    ENT_QUOTES,
    "UTF-8"
);

$safeError = htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Check Booking Status | Laundry Care</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f6fa;
            color: #263b50;
            line-height: 1.6;
        }

        header {
            background: #123b5d;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        main {
            max-width: 580px;
            margin: 45px auto;
            padding: 0 18px;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(18, 59, 93, 0.09);
        }

        h1 {
            color: #123b5d;
            margin-top: 0;
            line-height: 1.3;
        }

        .intro {
            color: #617487;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd8e2;
            border-radius: 8px;
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: #0878bd;
            box-shadow: 0 0 0 3px rgba(8, 120, 189, 0.12);
        }

        button,
        .home-link {
            display: inline-block;
            width: 100%;
            margin-top: 22px;
            padding: 13px 16px;
            border: none;
            border-radius: 8px;
            background: #0878bd;
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover,
        .home-link:hover {
            background: #075b91;
        }

        .result {
            margin-top: 25px;
            padding: 18px;
            background: #edf6fc;
            border-radius: 9px;
            text-align: center;
        }

        .result p {
            margin: 0 0 8px;
            color: #52677b;
        }

        .status {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 20px;
            background: #fff2d6;
            color: #805500;
            font-size: 17px;
            font-weight: bold;
        }

        .error {
            margin-top: 22px;
            padding: 14px;
            border-radius: 8px;
            background: #fff0f0;
            color: #9b2424;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #617487;
            font-size: 13px;
        }

        @media (max-width: 500px) {
            header {
                padding: 17px 20px;
            }

            .card {
                padding: 24px 19px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>Laundry Care</h2>
    <a href="index.html">&larr; Back to Home</a>
</header>

<main>
    <section class="card">
        <h1>Check Your Booking</h1>

        <p class="intro">
            Enter your booking reference and the phone number you used
            when submitting your booking to check its current status.
        </p>

        <form method="POST" action="booking_status.php">
            <label for="booking_id">Booking Reference</label>
            <input
                type="number"
                id="booking_id"
                name="booking_id"
                min="1"
                step="1"
                required
                placeholder="e.g. 3"
                value="<?php
                    echo htmlspecialchars(
                        $_POST["booking_id"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
            >

            <label for="phone">Phone Number Used for Booking</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="20"
                required
                autocomplete="tel"
                placeholder="Enter your booking phone number"
            >

            <button type="submit">Check Booking Status</button>
        </form>

        <?php if ($bookingStatus !== ""): ?>
            <div class="result" role="status">
                <p>Your booking status is:</p>
                <span class="status"><?php echo $safeStatus; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="error" role="alert">
                <?php echo $safeError; ?>
            </div>
        <?php endif; ?>

        <a class="home-link" href="index.html">Return to Home</a>
    </section>
</main>

<footer>
    &copy; 2026 Laundry Care. All rights reserved.
</footer>

</body>
</html>
