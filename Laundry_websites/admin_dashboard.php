
<?php
session_start();

// Protect the dashboard from users who are not logged in.
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

require_once "db_connect.php";

// Retrieve bookings, including their pricing information.
$sql = "SELECT booking_id, customer_name, phone, service,
               pickup_date, instructions, booking_status,
               quantity, service_price, delivery_fee, total_price
        FROM bookings
        ORDER BY booking_id DESC";

$result = $conn->query($sql);

// Safely display text from the database.
function escapeOutput($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Available booking statuses.
$statuses = [
    "Pending",
    "In Progress",
    "Completed",
    "Cancelled"
];

// Count all bookings.
$totalBookings = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Laundry Care</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f8fb;
            color: #243447;
            line-height: 1.6;
        }

        header {
            background: #123b5d;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: #176b87;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: bold;
        }

        .logout:hover {
            background: #0d526b;
        }

        main {
            padding: 30px 5%;
        }

        .summary {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px #0000000d;
        }

        .summary h2 {
            margin-top: 0;
            color: #123b5d;
        }

        .total-bookings {
            display: inline-block;
            padding: 8px 14px;
            background: #edf6fc;
            color: #123b5d;
            border-radius: 6px;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 10px #0000000d;
        }

        .table-container h2 {
            color: #123b5d;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1450px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8ee;
            vertical-align: middle;
        }

        th {
            background: #e8f2f8;
            color: #123b5d;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fbfd;
        }

        .price {
            white-space: nowrap;
        }

        .total-price {
            color: #123b5d;
            font-weight: bold;
        }

        .status-form {
            min-width: 150px;
        }

        select {
            width: 100%;
            padding: 9px;
            border: 1px solid #ccd6df;
            border-radius: 5px;
            background: white;
            font: inherit;
        }

        .update-button {
            margin-top: 8px;
            padding: 9px 12px;
            background: #176b87;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .update-button:hover {
            background: #123b5d;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #617487;
        }

        .table-note {
            color: #617487;
            font-size: 13px;
            margin-bottom: 15px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #657587;
        }

        @media (max-width: 600px) {
            main {
                padding: 20px 12px;
            }

            header {
                padding: 18px;
            }

            header h1 {
                font-size: 21px;
            }

            .summary {
                padding: 18px;
            }

            .table-container {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Laundry Care Admin</h1>

    <a class="logout" href="admin_logout.php">Log Out</a>
</header>

<main>

    <section class="summary">
        <h2>Booking Dashboard</h2>

        <p>
            Welcome,
            <strong>
                <?php
                echo escapeOutput(
                    $_SESSION["admin_username"] ?? "Admin"
                );
                ?>
            </strong>.
        </p>

        <p>
            View customer bookings, review their prices, and update
            their progress below.
        </p>

        <p class="total-bookings">
            Total bookings:
            <strong><?php echo (int)$totalBookings; ?></strong>
        </p>
    </section>

    <section class="table-container">
        <h2>Customer Bookings</h2>

        <p class="table-note">
            Prices are displayed in Ugandan shillings (UGX).
            Scroll horizontally to view all columns.
        </p>

        <?php if ($totalBookings > 0): ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Quantity</th>
                        <th>Service Cost</th>
                        <th>Delivery Fee</th>
                        <th>Total Price</th>
                        <th>Pickup Date</th>
                        <th>Instructions</th>
                        <th>Booking Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php
                            echo (int)$booking["booking_id"];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo escapeOutput($booking["customer_name"]);
                            ?>
                        </td>

                        <td>
                            <?php
                            echo escapeOutput($booking["phone"]);
                            ?>
                        </td>

                        <td>
                            <?php
                            echo escapeOutput($booking["service"]);
                            ?>
                        </td>

                        <td>
                            <?php
                            if ($booking["service"] === "Delivery") {
                                echo "N/A";
                            } else {
                                echo (int)$booking["quantity"];
                            }
                            ?>
                        </td>

                        <td class="price">
                            UGX
                            <?php
                            echo number_format(
                                (int)$booking["service_price"]
                            );
                            ?>
                        </td>

                        <td class="price">
                            UGX
                            <?php
                            echo number_format(
                                (int)$booking["delivery_fee"]
                            );
                            ?>
                        </td>

                        <td class="price total-price">
                            UGX
                            <?php
                            echo number_format(
                                (int)$booking["total_price"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo escapeOutput($booking["pickup_date"]);
                            ?>
                        </td>

                        <td>
                            <?php
                            echo escapeOutput($booking["instructions"]);
                            ?>
                        </td>

                        <td>
                            <form
                                class="status-form"
                                method="POST"
                                action="update_booking.php"
                            >

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                        echo (int)$booking["booking_id"];
                                    ?>"
                                >

                                <select
                                    name="booking_status"
                                    required
                                >
                                    <?php foreach ($statuses as $status): ?>

                                        <option
                                            value="<?php
                                                echo escapeOutput($status);
                                            ?>"
                                            <?php
                                            echo (
                                                $booking["booking_status"]
                                                === $status
                                            ) ? "selected" : "";
                                            ?>
                                        >
                                            <?php
                                            echo escapeOutput($status);
                                            ?>
                                        </option>

                                    <?php endforeach; ?>
                                </select>

                                <button
                                    type="submit"
                                    class="update-button"
                                >
                                    Update Status
                                </button>

                            </form>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php else: ?>

            <p class="empty">
                No bookings have been recorded yet.
            </p>

        <?php endif; ?>
    </section>

</main>

<footer>
    &copy; 2026 Laundry Care. All rights reserved.
</footer>

</body>
</html>

<?php
$result->free();
$conn->close();
?>
