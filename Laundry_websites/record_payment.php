
<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "db_connect.php";

// Protect the page: only logged-in administrators can record payments.
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$error = "";
$success = "";
$booking = null;
$bookingId = 0;

$amount = "";
$paymentMethod = "Cash";
$transactionReference = "";
$notes = "";

// Safely display text in HTML.
function escapePayment($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Load booking details and total completed payments.
function loadPaymentBooking($conn, $bookingId) {
    $sql = "
        SELECT
            b.booking_id,
            b.customer_name,
            b.phone,
            b.service,
            b.quantity,
            b.service_price,
            b.delivery_fee,
            b.total_price,
            COALESCE(
                SUM(
                    CASE
                        WHEN p.payment_status = 'Completed'
                        THEN p.amount
                        ELSE 0
                    END
                ),
                0
            ) AS amount_paid
        FROM bookings b
        LEFT JOIN payments p
            ON b.booking_id = p.booking_id
        WHERE b.booking_id = ?
        GROUP BY
            b.booking_id,
            b.customer_name,
            b.phone,
            b.service,
            b.quantity,
            b.service_price,
            b.delivery_fee,
            b.total_price
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();

    $stmt->close();

    return $booking ?: null;
}

// Process a submitted payment.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $validatedId = filter_var(
        $_POST["booking_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    $bookingId = (
        $validatedId !== false && $validatedId > 0
    ) ? (int)$validatedId : 0;

    $amount = trim($_POST["amount"] ?? "");
    $paymentMethod = $_POST["payment_method"] ?? "Cash";
    $transactionReference = trim(
        $_POST["transaction_reference"] ?? ""
    );
    $notes = trim($_POST["notes"] ?? "");

    $allowedMethods = ["Cash", "Mobile Money"];

    if ($bookingId <= 0) {
        $error = "Please enter a valid booking ID.";
    } elseif (
        !in_array($paymentMethod, $allowedMethods, true)
    ) {
        $error = "Please select a valid payment method.";
    } elseif (
        !preg_match('/^[0-9]+$/', $amount) ||
        (int)$amount <= 0
    ) {
        $error = "Enter a valid payment amount greater than zero.";
    } elseif (strlen($transactionReference) > 100) {
        $error = "The transaction reference is too long.";
    } else {
        try {
            $conn->begin_transaction();

            // Lock the booking row while checking its balance.
            $stmt = $conn->prepare("
                SELECT total_price
                FROM bookings
                WHERE booking_id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();

            $result = $stmt->get_result();
            $bookingRecord = $result->fetch_assoc();
            $stmt->close();

            if (!$bookingRecord) {
                throw new Exception(
                    "No booking was found with that ID."
                );
            }

            $totalPrice = (int)$bookingRecord["total_price"];

            // Calculate payments already marked Completed.
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(amount), 0) AS amount_paid
                FROM payments
                WHERE booking_id = ?
                  AND payment_status = 'Completed'
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();

            $result = $stmt->get_result();
            $paymentRecord = $result->fetch_assoc();
            $stmt->close();

            $amountPaid = (int)$paymentRecord["amount_paid"];
            $newAmount = (int)$amount;
            $balance = $totalPrice - $amountPaid;

            if ($totalPrice <= 0) {
                throw new Exception(
                    "This booking has no valid amount to pay."
                );
            } elseif ($balance <= 0) {
                throw new Exception(
                    "This booking is already fully paid."
                );
            } elseif ($newAmount > $balance) {
                throw new Exception(
                    "The payment exceeds the outstanding balance of UGX "
                    . number_format(max(0, $balance)) . "."
                );
            } else {
                $paymentDate = date("Y-m-d H:i:s");
                $adminId = (int)$_SESSION["admin_id"];

                // Empty references and notes are stored as NULL.
                $referenceValue = $transactionReference !== ""
                    ? $transactionReference
                    : null;

                $notesValue = $notes !== ""
                    ? $notes
                    : null;

                $paymentStatus = "Completed";

                $stmt = $conn->prepare("
                    INSERT INTO payments (
                        booking_id,
                        amount,
                        payment_method,
                        payment_status,
                        transaction_reference,
                        payment_date,
                        recorded_by,
                        notes
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iissssis",
                    $bookingId,
                    $newAmount,
                    $paymentMethod,
                    $paymentStatus,
                    $referenceValue,
                    $paymentDate,
                    $adminId,
                    $notesValue
                );

                $stmt->execute();
                $stmt->close();

                $conn->commit();

                $success = "Payment recorded successfully.";

                // Clear the payment fields after successful saving.
                $amount = "";
                $transactionReference = "";
                $notes = "";
            }
        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                // No further action is needed here.
            }

            $error = $e->getMessage();

            // Log technical details without showing them to customers.
            error_log("Payment recording error: " . $e->getMessage());
        }
    }
}

// Load a booking selected using the search form.
if ($bookingId <= 0) {
    $validatedId = filter_var(
        $_GET["booking_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    if ($validatedId !== false && $validatedId > 0) {
        $bookingId = (int)$validatedId;
    }
}

if ($bookingId > 0) {
    try {
        $booking = loadPaymentBooking($conn, $bookingId);

        if (!$booking && $error === "") {
            $error = "No booking was found with that ID.";
        }
    } catch (Throwable $e) {
        error_log("Loading booking error: " . $e->getMessage());

        if ($error === "") {
            $error = "Unable to load this booking. Please check the database.";
        }
    }
}

if ($booking) {
    $totalPrice = (int)$booking["total_price"];
    $amountPaid = (int)$booking["amount_paid"];
    $balance = max(0, $totalPrice - $amountPaid);
} else {
    $totalPrice = 0;
    $amountPaid = 0;
    $balance = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Record Payment | Laundry Care</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #243247;
        }

        header {
            background: #102a43;
            color: white;
            padding: 20px;
        }

        header .header-content {
            max-width: 1050px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        main {
            max-width: 1050px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .panel {
            background: white;
            padding: 24px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        h2 {
            margin-top: 0;
            color: #102a43;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 15px 0 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button,
        .button-link {
            display: inline-block;
            border: none;
            border-radius: 5px;
            background: #087e8b;
            color: white;
            padding: 12px 18px;
            margin-top: 18px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
        }

        button:hover,
        .button-link:hover {
            background: #06636d;
        }

        .search-form {
            display: flex;
            gap: 12px;
            align-items: end;
            flex-wrap: wrap;
        }

        .search-form div {
            flex: 1;
            min-width: 180px;
        }

        .search-form button {
            margin-top: 0;
        }

        .message {
            padding: 13px;
            border-radius: 5px;
            margin-bottom: 18px;
        }

        .error {
            background: #fde8e8;
            color: #8a1c1c;
        }

        .success {
            background: #e4f6e9;
            color: #176534;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail {
            padding: 15px;
            background: #f3f6fa;
            border-radius: 6px;
        }

        .detail span {
            display: block;
            color: #526477;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .detail strong {
            font-size: 18px;
            overflow-wrap: anywhere;
        }

        .balance {
            color: #a33b00;
        }

        .paid {
            color: #176534;
        }

        .hint {
            color: #526477;
            font-size: 14px;
            line-height: 1.5;
        }

        footer {
            text-align: center;
            padding: 22px;
            color: #526477;
        }

        @media (max-width: 600px) {
            .panel {
                padding: 18px;
            }

            header .header-content {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <h1>Laundry Care — Payments</h1>

        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </div>
</header>

<main>

    <?php if ($error !== ""): ?>
        <div class="message error">
            <?= escapePayment($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="message success">
            <?= escapePayment($success) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Find a Booking</h2>

        <p class="hint">
            Enter the booking ID to check its total cost, previous payments
            and outstanding balance.
        </p>

        <form method="GET" action="record_payment.php" class="search-form">
            <div>
                <label for="search_booking_id">Booking ID</label>
                <input
                    type="number"
                    id="search_booking_id"
                    name="booking_id"
                    min="1"
                    required
                    value="<?= $bookingId > 0 ? $bookingId : "" ?>"
                >
            </div>

            <button type="submit">Find Booking</button>
        </form>
    </section>

    <?php if ($booking): ?>

        <section class="panel">
            <h2>Booking Details</h2>

            <div class="details">
                <div class="detail">
                    <span>Booking ID</span>
                    <strong>
                        #<?= escapePayment($booking["booking_id"]) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Customer</span>
                    <strong>
                        <?= escapePayment($booking["customer_name"]) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Phone</span>
                    <strong>
                        <?= escapePayment($booking["phone"]) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Service</span>
                    <strong>
                        <?= escapePayment($booking["service"]) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Total Booking Cost</span>
                    <strong>
                        UGX <?= number_format($totalPrice) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Amount Paid</span>
                    <strong class="paid">
                        UGX <?= number_format($amountPaid) ?>
                    </strong>
                </div>

                <div class="detail">
                    <span>Outstanding Balance</span>
                    <strong class="balance">
                        UGX <?= number_format($balance) ?>
                    </strong>
                </div>
            </div>
        </section>

        <?php if ($balance > 0): ?>

            <section class="panel">
                <h2>Record a Payment</h2>

                <p class="hint">
                    Record a payment that the business has actually received.
                    This page does not initiate or verify an online payment.
                </p>

                <form method="POST" action="record_payment.php">
                    <input
                        type="hidden"
                        name="booking_id"
                        value="<?= (int)$booking["booking_id"] ?>"
                    >

                    <label for="amount">Amount Received (UGX)</label>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="1"
                        max="<?= $balance ?>"
                        step="1"
                        required
                        value="<?= escapePayment($amount) ?>"
                    >

                    <p class="hint">
                        Maximum amount you can record:
                        <strong>UGX <?= number_format($balance) ?></strong>
                    </p>

                    <label for="payment_method">Payment Method</label>
                    <select
                        id="payment_method"
                        name="payment_method"
                        required
                    >
                        <option
                            value="Cash"
                            <?= $paymentMethod === "Cash" ? "selected" : "" ?>
                        >
                            Cash
                        </option>

                        <option
                            value="Mobile Money"
                            <?= $paymentMethod === "Mobile Money" ? "selected" : "" ?>
                        >
                            Mobile Money
                        </option>
                    </select>

                    <label for="transaction_reference">
                        Transaction Reference (optional)
                    </label>
                    <input
                        type="text"
                        id="transaction_reference"
                        name="transaction_reference"
                        maxlength="100"
                        value="<?= escapePayment($transactionReference) ?>"
                        placeholder="Enter mobile money reference if available"
                    >

                    <label for="notes">Notes (optional)</label>
                    <textarea
                        id="notes"
                        name="notes"
                        maxlength="2000"
                        placeholder="Additional payment details"
                    ><?= escapePayment($notes) ?></textarea>

                    <button type="submit">
                        Record Payment
                    </button>
                </form>
            </section>

        <?php else: ?>

            <section class="panel">
                <h2>Booking Fully Paid</h2>
                <p class="paid">
                    No outstanding balance remains for this booking.
                </p>
            </section>

        <?php endif; ?>

    <?php endif; ?>

</main>

<footer>
    Laundry Care Management System
</footer>

</body>
</html>
