<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $firstName = $_POST["first_name"];
    $lastName = $_POST["last_name"];
    $cardNumber = $_POST["card_number"];
    $salary = $_POST["salary"];
    $file = $_FILES["file"];

    session_start();
    if (isset($_SESSION['customer_ID'])) {
        $customer_ID = $_SESSION['customer_ID'];

        // Connect to the database
        $con = mysqli_connect("localhost", "root", "", "baiplus_database");
        if (!$con) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Retrieve customer data from the database based on the customer ID
        $sql = "SELECT * FROM customer WHERE customer_ID = '$customer_ID'";
        $result = mysqli_query($con, $sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Check if the input matches the customer data
            if ($row["customer_fname"] == $firstName && $row["customer_lname"] == $lastName && $row["customer_ID"] == $customer_ID) {
                // Update the salary and upload the file for the customer
                $updateSql = "UPDATE customer SET salary = '$salary' WHERE customer_ID = '$customer_ID'";
                // salary_file = '$file'
                if ($con->query($updateSql) === TRUE) {
                    // Generate random values for card_no, card_exp, and cvv
                    $cardNo = generateRandomNumeric(16);
                    $cardExp = date('Y-m-d', strtotime('+5 years'));
                    $cvv = generateRandomNumeric(3);

                    // Insert a new row into the creditcard table
                    $insertSql = "INSERT INTO creditcard (card_no, card_exp, cvv, max_limit, customer_ID, bank_id, card_status)
                                  VALUES ('$cardNo', '$cardExp', '$cvv', 100000, '$customer_ID', 'BPBANK', 0)";
                    if ($con->query($insertSql) === TRUE) {
                        // Success message
                        echo "<script>alert('Credit-card request success');</script>";
                    } else {
                        // Error message
                        echo "<script>alert('Failed to update salary and upload file: " . $con->error . "');</script>";
                    }
                } else {
                    // Identity verification failed
                    echo "<script>alert('Failed identity verification');</script>";
                }
            } else {
                // Customer not found
                echo "<script>alert('Customer not found');</script>";
            }

            mysqli_close($con);
        } else {
            // Session not found or customer ID not set
            echo "<script>alert('Session not found or customer ID not set');</script>";
        }
    }
}
// Function to generate a random numeric string
function generateRandomNumeric($length) {
    $numeric = '0123456789';
    $numericLength = strlen($numeric);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $numeric[rand(0, $numericLength - 1)];
    }
    return $randomString;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- custom css file link  -->
    <link rel="stylesheet" href="Asset/css/style-card.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <script>
        function enableSubmitButton() {
            var checkbox = document.getElementById("termsCheckbox");
            var submitButton = document.getElementById("submitBtn");
            submitButton.disabled = !checkbox.checked;
        }
    </script>
    <style>
        .submit-btn {
            background-color: #ccc; /* Gray color */
            cursor: not-allowed; /* Show 'not-allowed' cursor when disabled */
        }

        .submit-btn:enabled {
            background-color: #ff5c5c; /* Change to your desired color when enabled */
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="card-container">

        <div class="front">
            <div class="image">
                <img src="Asset/images/chip.png" alt="">
                <img src="Asset/images/master.png" alt="">
            </div>
            <div class="card-number-box">################</div>
            <div class="flexbox">
                <div class="box">
                    <span>card holder</span>
                    <div class="card-holder-name">full name</div>
                </div>
                <div class="box">
                    <span>expires</span>
                    <div class="expiration">
                        <span class="exp-month">mm</span>
                        <span class="exp-year">yy</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="back">
            <div class="stripe"></div>
            <div class="box">
                <span>cvv</span>
                <div class="cvv-box"></div>
                <img src="master.png" alt="">
            </div>
        </div>

    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="inputBox">
            <span>First name</span>
            <input type="text" maxlength="16" class="card-number-input" name="first_name">
        </div>
        <div class="inputBox">
            <span>Last name</span>
            <input type="text" maxlength="16" class="card-number-input" name="last_name">
        </div>
        <div class="inputBox">
            <span>card number</span>
            <input type="text" maxlength="16" class="card-number-input" name="card_number">
        </div>
        <div class="inputBox">
            <span>Salary</span>
            <input type="text" class="" name="salary">
        </div>

        <input type="file" id="uploadBtn" name="file">
        <label for="uploadBtn"><i class='bx bx-upload'></i> Upload</label>

        <div class="checkbox">
            <span>Yes, I accept the Terms of Use</span>
            <input type="checkbox" id="termsCheckbox" onchange="enableSubmitButton()">
        </div>

        <input type="submit" value="Submit" class="submit-btn" id="submitBtn" disabled>
    </form>
</div>
</body>
</html>
