
<?php
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Laundry Service | Laundry Care</title>

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
            font-size: 25px;
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        header a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 18px;
        }

        .booking-card {
            background: white;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(18, 59, 93, 0.09);
        }

        .eyebrow {
            color: #0878bd;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 0 0 8px;
        }

        h1 {
            color: #123b5d;
            font-size: 30px;
            line-height: 1.3;
            margin: 0 0 12px;
        }

        .intro {
            color: #617487;
            margin: 0 0 26px;
        }

        .form-row {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #263b50;
        }

        .required {
            color: #c0392b;
        }

        .optional {
            color: #617487;
            font-size: 13px;
            font-weight: normal;
        }

        input,
        select,
        textarea {
            display: block;
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd8e2;
            border-radius: 8px;
            background: #fff;
            color: #263b50;
            font: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0878bd;
            box-shadow: 0 0 0 3px rgba(8, 120, 189, 0.12);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .field-help {
            display: block;
            margin-top: 5px;
            color: #617487;
            font-size: 13px;
        }

        .delivery-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            background: #edf6fc;
            border-radius: 8px;
        }

        .delivery-option input {
            width: auto;
            margin-top: 6px;
        }

        .delivery-option label {
            margin: 0;
            cursor: pointer;
        }

        .price-card {
            margin: 22px 0;
            padding: 20px;
            background: #edf6fc;
            border: 1px solid #c9e3f4;
            border-radius: 10px;
        }

        .price-card h2 {
            margin: 0 0 12px;
            color: #123b5d;
            font-size: 20px;
        }

        .price-line {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin: 8px 0;
        }

        .total-line {
            border-top: 1px solid #cbd8e2;
            padding-top: 12px;
            margin-top: 12px;
            font-weight: bold;
            font-size: 19px;
            color: #123b5d;
        }

        button {
            width: 100%;
            margin-top: 8px;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: #0878bd;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #075b91;
        }

        .booking-note {
            margin: 20px 0 0;
            padding: 13px 15px;
            border-radius: 8px;
            background: #f7f9fb;
            color: #405c73;
            font-size: 13px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #617487;
            font-size: 13px;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 550px) {
            header {
                padding: 17px 20px;
            }

            header h2 {
                font-size: 22px;
            }

            main {
                margin: 24px auto;
                padding: 0 12px;
            }

            .booking-card {
                padding: 23px 19px;
            }

            h1 {
                font-size: 25px;
            }

            .price-line {
                font-size: 14px;
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
    <section class="booking-card">
        <p class="eyebrow">Simple and convenient</p>

        <h1>Book a Laundry Service</h1>

        <p class="intro">
            Choose your service and quantity to see an estimated price
            before submitting your booking request.
        </p>

        <form action="save_booking.php" method="POST" id="bookingForm">

            <div class="form-row">
                <label for="customer_name">
                    Full Name <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    maxlength="100"
                    autocomplete="name"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-row">
                <label for="phone">
                    Phone Number <span class="required">*</span>
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    autocomplete="tel"
                    placeholder="e.g. 07XXXXXXXX"
                    required
                >
                <small class="field-help">
                    Enter a phone number we can use to contact you.
                </small>
            </div>

            <div class="form-row">
                <label for="service">
                    Laundry Service <span class="required">*</span>
                </label>
                <select id="service" name="service" required>
                    <option value="">-- Select a service --</option>
                    <option value="Washing">Washing</option>
                    <option value="Dry Cleaning">Dry Cleaning</option>
                    <option value="Ironing">Ironing</option>
                    <option value="Delivery">Delivery only</option>
                </select>
            </div>

            <div class="form-row" id="quantityRow" hidden>
                <label for="quantity" id="quantityLabel">Quantity</label>
                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    min="1"
                    max="1000"
                    step="1"
                    value="1"
                >
                <small class="field-help" id="quantityHelp"></small>
            </div>

            <div class="form-row" id="deliveryRow" hidden>
                <div class="delivery-option">
                    <input
                        type="checkbox"
                        id="include_delivery"
                        name="include_delivery"
                        value="1"
                    >
                    <label for="include_delivery">
                        Add delivery — UGX 4,000
                        <small class="field-help">
                            Select this if you want your laundry delivered.
                        </small>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <label for="pickup_date">
                    Preferred Pickup Date <span class="required">*</span>
                </label>
                <input
                    type="date"
                    id="pickup_date"
                    name="pickup_date"
                    min="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
                <small class="field-help">
                    Choose today or a future date.
                </small>
            </div>

            <div class="form-row">
                <label for="instructions">
                    Additional Instructions
                    <span class="optional">(Optional)</span>
                </label>
                <textarea
                    id="instructions"
                    name="instructions"
                    maxlength="2000"
                    placeholder="Any special requests or information about your laundry?"
                ></textarea>
            </div>

            <section class="price-card" aria-live="polite">
                <h2>Estimated Price</h2>

                <div class="price-line">
                    <span>Service</span>
                    <span id="servicePrice">UGX 0</span>
                </div>

                <div class="price-line">
                    <span>Delivery fee</span>
                    <span id="deliveryPrice">UGX 0</span>
                </div>

                <div class="price-line total-line">
                    <span>Estimated total</span>
                    <span id="totalPrice">UGX 0</span>
                </div>

                <small class="field-help">
                    This is an estimate. The final charge should be confirmed
                    with Laundry Care.
                </small>
            </section>

            <button type="submit">Submit Booking Request</button>

            <p class="booking-note">
                <strong>Note:</strong> Your submission is a booking request.
                Laundry Care will need to confirm the arrangements with you.
            </p>

        </form>
    </section>
</main>

<footer>
    &copy; 2026 Laundry Care. All rights reserved.
</footer>

<script>
    const serviceSelect = document.getElementById("service");
    const quantityRow = document.getElementById("quantityRow");
    const quantityInput = document.getElementById("quantity");
    const quantityLabel = document.getElementById("quantityLabel");
    const quantityHelp = document.getElementById("quantityHelp");
    const deliveryRow = document.getElementById("deliveryRow");
    const deliveryCheckbox = document.getElementById("include_delivery");

    const servicePriceDisplay = document.getElementById("servicePrice");
    const deliveryPriceDisplay = document.getElementById("deliveryPrice");
    const totalPriceDisplay = document.getElementById("totalPrice");

    const prices = {
        "Washing": 10000,
        "Dry Cleaning": 7000,
        "Ironing": 5000,
        "Delivery": 4000
    };

    function formatUGX(amount) {
        return "UGX " + amount.toLocaleString("en-UG");
    }

    function updatePrice() {
        const service = serviceSelect.value;
        const isDeliveryOnly = service === "Delivery";
        const hasService = service !== "";

        quantityRow.hidden = !hasService || isDeliveryOnly;
        deliveryRow.hidden = !hasService || isDeliveryOnly;

        quantityInput.required = hasService && !isDeliveryOnly;
        if (isDeliveryOnly) {
          deliveryCheckbox.checked = true;
          deliveryCheckbox.disabled = true;
        }
        else {
           deliveryCheckbox.disabled = false;
        }

        let servicePrice = 0;
        let deliveryPrice = 0;

        if (service === "Washing") {
            quantityLabel.textContent = "Number of loads";
            quantityHelp.textContent = "UGX 10,000 per load.";
            servicePrice = prices[service] * (Number(quantityInput.value) || 0);
        } else if (service === "Dry Cleaning") {
            quantityLabel.textContent = "Number of garments";
            quantityHelp.textContent = "UGX 7,000 per garment.";
            servicePrice = prices[service] * (Number(quantityInput.value) || 0);
        } else if (service === "Ironing") {
            quantityLabel.textContent = "Number of garments";
            quantityHelp.textContent = "UGX 5,000 per garment.";
            servicePrice = prices[service] * (Number(quantityInput.value) || 0);
        } else if (isDeliveryOnly) {
            deliveryPrice = prices[service];
        }

        if (hasService && !isDeliveryOnly && deliveryCheckbox.checked) {
            deliveryPrice = prices["Delivery"];
        }

        servicePriceDisplay.textContent = formatUGX(servicePrice);
        deliveryPriceDisplay.textContent = formatUGX(deliveryPrice);
        totalPriceDisplay.textContent = formatUGX(servicePrice + deliveryPrice);
    }

    serviceSelect.addEventListener("change", updatePrice);
    quantityInput.addEventListener("input", updatePrice);
    deliveryCheckbox.addEventListener("change", updatePrice);

    document.getElementById("bookingForm").addEventListener("submit", function(event) {
        const service = serviceSelect.value;
        const quantity = Number(quantityInput.value);

        if (service !== "Delivery" && (!Number.isInteger(quantity) || quantity < 1 || quantity > 1000)) {
            event.preventDefault();
            alert("Please enter a quantity between 1 and 1000.");
            quantityInput.focus();
        }
    });

    updatePrice();
</script>

</body>
</html>
