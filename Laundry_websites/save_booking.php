
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: booking.php");
    exit;
}

// Read the submitted form fields.
$customer_name = trim($_POST["customer_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$service = $_POST["service"] ?? "";
$pickup_date = $_POST["pickup_date"] ?? "";
$instructions = trim($_POST["instructions"] ?? "");

// Delivery-only bookings do not submit a quantity.
$quantity = ($service === "Delivery")
    ? 1
    : filter_var($_POST["quantity"] ?? null, FILTER_VALIDATE_INT);

// Only a checked checkbox submits include_delivery.
$include_delivery = isset($_POST["include_delivery"]);

// Allowed services and prices in UGX.
$prices = [
    "Washing" => 10000,
    "Dry Cleaning" => 7000,
    "Ironing" => 5000,
    "Delivery" => 4000
];

// Validate the date, including whether it is a real calendar date.
$date = DateTime::createFromFormat("!Y-m-d", $pickup_date);
$date_errors = DateTime::getLastErrors();

$valid_date = $date !== false
    && ($date_errors === false
        || ($date_errors["warning_count"] === 0
            && $date_errors["error_count"] === 0))
    && $date->format("Y-m-d") === $pickup_date
    && $pickup_date >= date("Y-m-d");

// Validate the submitted information.
if (
    $customer_name === ""
    || strlen($customer_name) > 100
    || $phone === ""
    || strlen($phone) > 20
    || !array_key_exists($service, $prices)
    || !$valid_date
    || strlen($instructions) > 2000
    || ($service !== "Delivery"
        && ($quantity === false
            || $quantity < 1
            || $quantity > 1000))
) {
    exit(
        "Invalid booking details. Please go back and check your entries."
    );
}

// Calculate prices on the server.
$service_price = 0;
$delivery_fee = 0;

if ($service === "Delivery") {
    $delivery_fee = 4000;
} else {
    $service_price = $prices[$service] * $quantity;

    if ($include_delivery) {
        $delivery_fee = 4000;
    }
}

$total_price = $service_price + $delivery_fee;
$status = "Pending";

// Save the booking and its prices.
$sql = "INSERT INTO bookings
        (customer_name, phone, service, pickup_date, instructions,
         booking_status, quantity, service_price, delivery_fee, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssssssiiii",
    $customer_name,
    $phone,
    $service,
    $pickup_date,
    $instructions,
    $status,
    $quantity,
    $service_price,
    $delivery_fee,
    $total_price
);

$stmt->execute();

$booking_id = $stmt->insert_id;

$stmt->close();
$conn->close();

// Safely display the customer's name.
$safe_name = htmlspecialchars(
    $customer_name,
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Received | Laundry Care</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            background: #f2f6fa;
            color: #263b50;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .confirmation {
            max-width: 600px;
            margin: 35px auto;
            padding: 32px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(18, 59, 93, 0.09);
        }

        h1 {
            color: #123b5d;
            margin-top: 0;
        }

        .success {
            color: #187443;
            font-weight: bold;
        }

        .price-line {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 8px 0;
        }

        .price-box {
            margin: 20px 0;
            padding: 18px;
            background: #edf6fc;
            border-radius: 10px;
        }

        .total {
            margin-top: 8px;
            padding-top: 12px;
            border-top: 1px solid #cbd8e2;
            color: #123b5d;
            font-size: 19px;
            font-weight: bold;
        }

        a {
            display: inline-block;
            margin: 8px 10px 0 0;
            padding: 11px 16px;
            border-radius: 7px;
            background: #0878bd;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            background: #075b91;
        }

        .note {
            color: #617487;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <main class="confirmation">
        <h1>Booking Received!</h1>

        <p class="success">
            Your laundry booking request has been saved successfully.
        </p>

        <p>Hello <?php echo $safe_name; ?>, thank you for choosing Laundry Care.</p>

        <p>
            <strong>Booking reference:</strong>
            <?php echo (int) $booking_id; ?>
        </p>

        <p>
            <strong>Service:</strong>
            <?php echo htmlspecialchars($service, ENT_QUOTES, "UTF-8"); ?>
        </p>

        <?php if ($service !== "Delivery"): ?>
            <p>
                <strong>Quantity:</strong>
                <?php echo (int) $quantity; ?>
            </p>
        <?php endif; ?>

        <p>
            <strong>Preferred pickup date:</strong>
            <?php echo htmlspecialchars($pickup_date, ENT_QUOTES, "UTF-8"); ?>
        </p>

        <section class="price-box">
            <div class="price-line">
                <span>Service cost</span>
                <span>UGX <?php echo number_format($service_price); ?></span>
            </div>

            <div class="price-line">
                <span>Delivery fee</span>
                <span>UGX <?php echo number_format($delivery_fee); ?></span>
            </div>

            <div class="price-line total">
                <span>Estimated total</span>
                <span>UGX <?php echo number_format($total_price); ?></span>
            </div>
        </section>

        <p>
            <strong>Status:</strong>
            <?php echo htmlspecialchars($status, ENT_QUOTES, "UTF-8"); ?>
        </p>

        <p class="note">
            This is an estimated price. Laundry Care will confirm the
            arrangements and final charge with you.
        </p>

        <a href="index.html">Back to Home</a>
        <a href="booking_status.php">Check Booking Status</a>
    </main>
</body>
</html>
