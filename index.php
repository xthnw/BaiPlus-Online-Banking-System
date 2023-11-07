<style>
    .animated-text {
        font-size: 1rem;
        text-align: center;
        overflow: hidden;
        white-space: nowrap;
        color: #333;
        /* Change the color as per your preference */
        border-right: 0.15em solid #333;
        /* Change the border color and width as per your preference */
        animation: typing 0.5s steps(40, end), blink-caret 1.5s step-end infinite;
        transition: border-color 0.5s ease-out;
        /* Add transition effect to border-color property */
    }

    @keyframes typing {
        from {
            width: 0;
        }

        to {
            width: 100%;
        }
    }

    @keyframes blink-caret {

        from,
        to {
            border-color: transparent;
        }

        50% {
            border-color: #333;
            /* Change the color as per your preference */
        }
    }

    .animated-text:hover {
        border-color: #999;
        /* Change the border color on hover as per your preference */
    }


    /* .reveal {
        position: relative;
        transform: translateY(125px);
        opacity: 0.2;
        transition: 0.75s all ease;
    }

    .reveal.active {
        transform: translateY(0);
        opacity: 1;
    }

    .collapse {
        transition: height 0.3s ease;
        overflow: hidden;
    } */
</style>











<?php

session_start();


$_SESSION['previous_page'] = basename($_SERVER['PHP_SELF']);
$previousPage = $_SESSION['previous_page'] ?? 'unknown';



















if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
} else if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
} else {
    echo "Invalid username or password.";
}

// Connect to the database
$conn = mysqli_connect('localhost', 'root', '', 'baiplus_final');

// Check if the username and password match those in the database
$query = "SELECT * FROM employee WHERE employee_username = '$username' AND employee_password = '$password'";
$result = mysqli_query($conn, $query);

// If the username and password are correct, retrieve the user's role from the database
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $user_role = $row['employee_role'];
    $user_fname = $row['employee_fname'];
    $user_lname = $row['employee_lname'];
    $user_employee_id = $row['employee_id'];

    // Show the appropriate code based on the user's role
    if ($user_role == 'Administrator') {
        $_SESSION['param1'] = $username;
        $_SESSION['param2'] = $password;
        $_SESSION['param3'] = $user_role;
        $_SESSION['param4'] = $user_fname;
        $_SESSION['param5'] = $user_lname;
        $_SESSION['param6'] = $user_employee_id;



        $conn = mysqli_connect('localhost', 'root', '', 'baiplus_final');

        // Check connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Run the SQL query
        $query = "SELECT b.bank_name, t.trans_type, COUNT(*) as num_transactions
        FROM `transaction` t
        JOIN account a ON t.account_transferor = a.account_id
        JOIN bank b ON a.bank_id = b.bank_id
        GROUP BY b.bank_name, t.trans_type;";
        $result = mysqli_query($conn, $query);

        // Close the connection

        $data = array();
        $banks = array();
        $trans_types = array();

        // Loop through the result set and store the data in the array
        // while ($row = mysqli_fetch_assoc($result)) {
        //     $bank_name = $row['bank_name'];
        //     $trans_type = $row['trans_type'];
        //     $num_transactions = $row['num_transactions'];
        //     $data[$bank_name][$trans_type] = $num_transactions;
        // }

        //dyncmic
        while ($row = mysqli_fetch_assoc($result)) {
            $bank_name = $row['bank_name'];
            $trans_type = $row['trans_type'];
            $num_transactions = $row['num_transactions'];

            // Store the bank name in the banks array if it doesn't exist
            if (!in_array($bank_name, $banks)) {
                $banks[] = $bank_name;
            }

            // Store the transaction type in the trans_types array if it doesn't exist
            if (!in_array($trans_type, $trans_types)) {
                $trans_types[] = $trans_type;
            }

            // Store the data in the data array using the bank name and transaction type as keys
            $data[$bank_name][$trans_type] = $num_transactions;
        }


        $sql_blocked_accounts = "SELECT a.account_type, COUNT(*) as num_blocked_accounts
        FROM managehistory m
        JOIN employee e ON m.employee_id = e.employee_id
        JOIN account a ON m.account_id = a.account_id
        WHERE (m.action_type LIKE '%->Freeze Permanent%' OR
               m.action_type LIKE '%Active->Freeze Permanent,%' OR
               m.action_type LIKE '%Active->Freeze Temp,%' OR
               m.action_type LIKE '%Inactive->Freeze Permanent' OR
               m.action_type LIKE '%Inactive->Freeze Permanent,%' OR
               m.action_type LIKE '%Inactive->Freeze Temp,%')
              AND e.employee_role = 'Administrator'
        GROUP BY a.account_type";

        $result_blocked_accounts = mysqli_query($conn, $sql_blocked_accounts);

        // Create an array to hold the data
        $data_blocked_accounts = array();

        // Loop through the result set and add each row to the data array
        while ($row = mysqli_fetch_assoc($result_blocked_accounts)) {
            $data_blocked_accounts[] = array(
                "account_type" => $row["account_type"],
                "num_blocked_accounts" => $row["num_blocked_accounts"]
            );
        }

        $datablocked_accounts_json = json_encode($data_blocked_accounts);






        $sql_debt = "SELECT c.customer_id, c.customer_fname, l.loan_amount - COALESCE(SUM(t.transaction_amount), 0) as outstanding_debt
        FROM loan l
        JOIN account a ON l.account_id = a.account_id
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN transaction t ON a.account_id = t.account_transferor
        WHERE t.trans_type = 'Loan Installment Payment' OR t.trans_type IS NULL
        GROUP BY c.customer_id, c.customer_fname, l.loan_amount";
        $result_debt = mysqli_query($conn, $sql_debt);
        while ($row = mysqli_fetch_assoc($result_debt)) {
            $data_debt[] = array(
                "customer_id" => $row["customer_id"],
                "customer_fname" => $row["customer_fname"],
                "outstanding_debt" => $row["outstanding_debt"]
            );
        }

        // Encode the data array as a JSON object
        $data_json_debt = json_encode($data_debt);




        // Retrieve data from the database
        $sql_best_bill = "SELECT bi.biller_name, SUM(t.transaction_amount) as total_bills_paid
FROM transaction t
JOIN account a ON t.account_transferor = a.account_id
JOIN bill b ON t.bill_id = b.bill_id
JOIN billerinfo bi ON b.biller_id = bi.biller_id
WHERE t.trans_type = 'Bill Payment'
GROUP BY bi.biller_name
ORDER BY SUM(t.transaction_amount) DESC
LIMIT 5";

        $result_best_bill = mysqli_query($conn, $sql_best_bill);

        // Format data for Chart.js
        $data_best_bill = [
            "labels" => [],
            "datasets" => [
                [
                    "label" => "Total Bills Paid",
                    "backgroundColor" => ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"],
                    "hoverBackgroundColor" => ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"],
                    "data" => []
                ]
            ]
        ];

        while ($row = mysqli_fetch_assoc($result_best_bill)) {
            $data_best_bill["labels"][] = $row["biller_name"];
            $data_best_bill["datasets"][0]["data"][] = $row["total_bills_paid"];
        }

        // Convert data to JSON
        $data_best_bill_json = json_encode($data_best_bill);

        $sql = "SELECT SUM(account_balance) AS total_amount FROM account";
        $result = $conn->query($sql);

        // Extract total amount from query result
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total_amount = $row["total_amount"];
        } else {
            $total_amount = 0;
        }



        $sql = "SELECT SUM(transaction_amount) FROM `transaction` WHERE trans_type = 'Transfer'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $sum = $row['SUM(transaction_amount)'];










        $sql_age = "SELECT 
        age_range,
        COUNT(*) AS total_loans,
        SUM(on_time_payments) AS on_time_payments,
        SUM(total_loan_amount) AS total_loan_amount,
        SUM(total_payments_amount) AS total_payments_amount,
        ROUND(100 * SUM(total_payments_amount) / SUM(total_loan_amount), 2) AS timeliness_percentage
      FROM (
        SELECT 
          CASE 
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 20 THEN 'Under 20'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 30 THEN '20-30'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 40 THEN '30-40'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 50 THEN '40-50'
            ELSE 'Over 50'
          END AS age_range,
          COUNT(*) AS total_loans,
          SUM(CASE WHEN t.transaction_date <= DATE_ADD(l.loan_start_date, INTERVAL l.loan_duration MONTH) THEN 1 ELSE 0 END) AS on_time_payments,
          l.loan_amount AS total_loan_amount,
          IFNULL(SUM(CASE WHEN t.transaction_date <= DATE_ADD(l.loan_start_date, INTERVAL l.loan_duration MONTH) THEN IFNULL(t.transaction_amount, 0) ELSE 0 END), 0) AS total_payments_amount
        FROM loan l
        JOIN account a ON l.account_id = a.account_id
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN transaction t ON a.account_id = t.account_transferor AND t.trans_type = 'Loan Installment Payment'
        GROUP BY age_range, l.loan_id
      ) AS subquery
      GROUP BY age_range;
      ";
        $result_age = mysqli_query($conn, $sql_age); // assuming you're using mysqli to connect to the database
        while ($row = mysqli_fetch_assoc($result_age)) {
            $data_age[$row['age_range']] = array(
                'total_loans' => $row['total_loans'],
                'on_time_payments' => $row['on_time_payments'],
                'total_loan_amount' => $row['total_loan_amount'],
                'total_payments_amount' => $row['total_payments_amount'],
                'timeliness_percentage' => $row['timeliness_percentage'],
            );
        }


















        // echo "<table>";
        // echo "<tr><th>Age Range</th><th>Total Loans</th><th>On-Time Payments</th><th>Total Loan Amount</th><th>Total Payments Amount</th><th>Timeliness Percentage</th></tr>";
        // while ($row = mysqli_fetch_assoc($result)) {
        //     echo "<tr>";
        //     echo "<td>" . $row['age_range'] . "</td>";
        //     echo "<td>" . $row['total_loans'] . "</td>";
        //     echo "<td>" . $row['on_time_payments'] . "</td>";
        //     echo "<td>" . $row['total_loan_amount'] . "</td>";
        //     echo "<td>" . $row['total_payments_amount'] . "</td>";
        //     echo "<td>" . $row['timeliness_percentage'] . "%</td>";
        //     echo "</tr>";
        // }
        // echo "</table>";









?>


        <!DOCTYPE html>
        <html lang="en">


        <head>




            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            <meta name="description" content="">
            <meta name="author" content="">

            <title>BaiPlus+ Dashboard</title>

            <!-- Custom fonts for this template-->
            <link rel="icon" href="img/favicon.ico" type="img/ico">
            <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
            <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

            <!-- Custom styles for this template-->
            <link href="css/sb-admin-2.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-TMzC7PhA9ZpW1yVfJ6avULU6ItPyU6lxwPP7g30QfbA0nqBY+Onqmj53/ja5l5c5G5f+I06aYjRi7PQTSgFEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        </head>
        <style>
            @keyframes fade-in {
                0% {
                    opacity: 0;
                }

                100% {
                    opacity: 1;
                }
            }

            .page-transition-fade-in {
                animation: fade-in 0.5s ease-in-out;
            }


            @keyframes slide-in-bottom {
                0% {
                    transform: translateY(100%);
                    opacity: 0;
                }

                100% {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .page-transition-slide-in-bottom {
                animation: slide-in-bottom 0.5s ease-in-out;
            }

            @keyframes slide-in-right {
                0% {
                    transform: translateX(100%);
                    opacity: 0;
                }

                100% {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            .page-transition-slide-in-right {
                animation: slide-in-right 0.5s ease-in-out;
            }

            @keyframes scale-in {
                0% {
                    transform: scale(0);
                }

                100% {
                    transform: scale(1);
                }
            }

            /* Apply scale-in animation */
            .page-transition-slide-in {
                animation: scale-in 0.5s ease-in-out;
            }
        </style>

        <body id="page-top">


            <!-- Page Wrapper -->
            <div id="wrapper">

                <!-- Sidebar -->
                <ul class="navbar-nav bg-info sidebar sidebar-dark accordion" id="accordionSidebar">

                    <!-- Sidebar - Brand -->
                    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="http://127.0.0.1/baiplus/index.php">
                        <div class="sidebar-brand-icon rotate-n-0">
                            <img src="img\baiplus_logo.png.png" alt="baiplus_logo" width="71">
                        </div>
                        <div class="sidebar-brand-text mx-3">BaiPlus <sup>+</sup></div>
                    </a>

                    <!-- Divider -->
                    <hr class="sidebar-divider my-0">

                    <!-- Nav Item - Dashboard -->
                    <li class="nav-item active">
                        <a class="nav-link" href="http://127.0.0.1/baiplus/index.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span></a>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider">

                    <!-- Heading -->
                    <div class="sidebar-heading">
                        Management
                    </div>
                    <!-- Nav Item - Utilities Collapse Menu -->
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
                            <i class="fas fa-fw fa-wrench"></i>
                            <span>Administrator</span>
                        </a>
                        <div id="collapseUtilities" class="collapse show" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">Administrator</h6>
                                <a class="collapse-item" href="customer.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-vcard" viewBox="0 0 16 16">
                                        <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm4-2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5ZM9 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 9 8Zm1 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5Z" />
                                        <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2ZM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8.96c.026-.163.04-.33.04-.5C9 10.567 7.21 9 5 9c-2.086 0-3.8 1.398-3.984 3.181A1.006 1.006 0 0 1 1 12V4Z" />
                                    </svg> Customer</a>
                                <a class="collapse-item" href="account.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                                    </svg> Account</a>
                                <a class="collapse-item" href="bank.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bank" viewBox="0 0 16 16">
                                        <path d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.38l.5 2a.498.498 0 0 1-.485.62H.5a.498.498 0 0 1-.485-.62l.5-2A.501.501 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89L8 0ZM3.777 3h8.447L8 1 3.777 3ZM2 6v7h1V6H2Zm2 0v7h2.5V6H4Zm3.5 0v7h1V6h-1Zm2 0v7H12V6H9.5ZM13 6v7h1V6h-1Zm2-1V4H1v1h14Zm-.39 9H1.39l-.25 1h13.72l-.25-1Z" />
                                    </svg> Bank</a>
                                <a class="collapse-item" href="employee.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                    </svg> Employee</a>
                                <a class="collapse-item" href="credit_card.php"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z" />
                                        <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z" />
                                    </svg> Credit Card</a>
                                <a class="collapse-item" href="transaction.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash-coin" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0z" />
                                        <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1h-.003zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195l.054.012z" />
                                        <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083c.058-.344.145-.678.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1H1z" />
                                        <path d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 5.982 5.982 0 0 1 3.13-1.567z" />
                                    </svg> Transaction</a>
                                <a class="collapse-item" href="bill.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pass" viewBox="0 0 16 16">
                                        <path d="M5.5 5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5Zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3Z" />
                                        <path d="M8 2a2 2 0 0 0 2-2h2.5A1.5 1.5 0 0 1 14 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-13A1.5 1.5 0 0 1 3.5 0H6a2 2 0 0 0 2 2Zm0 1a3.001 3.001 0 0 1-2.83-2H3.5a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-13a.5.5 0 0 0-.5-.5h-1.67A3.001 3.001 0 0 1 8 3Z" />
                                    </svg> Bill</a>
                                <a class="collapse-item" href="biller.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                                        <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1ZM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM7.5 5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM4.5 8a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Z" />
                                        <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V1Zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3V1Z" />
                                    </svg> Biller Info</a>
                                <a class="collapse-item" href="loan.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z" />
                                        <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z" />
                                    </svg> Loan</a>
                                <a class="collapse-item" href="postcode.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mailbox" viewBox="0 0 16 16">
                                        <path d="M4 4a3 3 0 0 0-3 3v6h6V7a3 3 0 0 0-3-3zm0-1h8a4 4 0 0 1 4 4v6a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V7a4 4 0 0 1 4-4zm2.646 1A3.99 3.99 0 0 1 8 7v6h7V7a3 3 0 0 0-3-3H6.646z" />
                                        <path d="M11.793 8.5H9v-1h5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.354-.146l-.853-.854zM5 7c0 .552-.448 0-1 0s-1 .552-1 0a1 1 0 0 1 2 0z" />
                                    </svg> Postcode</a>
                                <a class="collapse-item" href="managehistory.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z" />
                                        <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z" />
                                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z" />
                                    </svg> Manage History</a>
                                <a class="collapse-item" href="error.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bug" viewBox="0 0 16 16">
                                        <path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A4.979 4.979 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A4.985 4.985 0 0 1 13 6h.5a.5.5 0 0 0 .5-.5V5a.5.5 0 0 1 1 0v.5A1.5 1.5 0 0 1 13.5 7H13v1h1.5a.5.5 0 0 1 0 1H13v1h.5a1.5 1.5 0 0 1 1.5 1.5v.5a.5.5 0 1 1-1 0v-.5a.5.5 0 0 0-.5-.5H13a5 5 0 0 1-10 0h-.5a.5.5 0 0 0-.5.5v.5a.5.5 0 1 1-1 0v-.5A1.5 1.5 0 0 1 2.5 10H3V9H1.5a.5.5 0 0 1 0-1H3V7h-.5A1.5 1.5 0 0 1 1 5.5V5a.5.5 0 0 1 1 0v.5a.5.5 0 0 0 .5.5H3c0-1.364.547-2.601 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623zM4 7v4a4 4 0 0 0 3.5 3.97V7H4zm4.5 0v7.97A4 4 0 0 0 12 11V7H8.5zM12 6a3.989 3.989 0 0 0-1.334-2.982A3.983 3.983 0 0 0 8 2a3.983 3.983 0 0 0-2.667 1.018A3.989 3.989 0 0 0 4 6h8z" />
                                    </svg> Error</a>
                                <a class="collapse-item" href="requesting.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                                        <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z" />
                                    </svg> Pending Request</a>

                            </div>
                        </div>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider">

                    <!-- Heading -->
                    <div class="sidebar-heading">
                        Addons
                    </div>
                    <li class="nav-item">
                        <a class="nav-link" href="table.php">
                            <i class="fas fa-fw fa-table"></i>
                            <span>Tables</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advance.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-heart-fill" viewBox="0 0 16 16">
                                <path d="M2 15.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v13.5zM8 4.41c1.387-1.425 4.854 1.07 0 4.277C3.146 5.48 6.613 2.986 8 4.412z" />
                            </svg>
                            <span>Advanced Analysis Report</span></a>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider d-none d-md-block">

                    <!-- Sidebar Toggler (Sidebar) -->
                    <div class="text-center d-none d-md-inline">
                        <button class="rounded-circle border-0" id="sidebarToggle"></button>
                    </div>

                    <!-- Sidebar Message -->
                    <!-- <div class="sidebar-card d-none d-lg-flex">
                        <img class="sidebar-card-illustration mb-2" src="img/undraw_rocket.svg" alt="...">
                        <p class="text-center mb-2"><strong>BaiPlus</strong> is has many features, components, and more!</p>
                        <a class="btn btn-success btn-sm" href="https://startbootstrap.com/theme/sb-admin-pro">Let's get started!</a>
                    </div> -->

                </ul>
                <!-- End of Sidebar -->

                <!-- Content Wrapper -->
                <div id="content-wrapper" class="d-flex flex-column">

                    <!-- Main Content -->
                    <div id="content">

                        <!-- Topbar -->
                        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                            <!-- Sidebar Toggle (Topbar) -->
                            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                                <i class="fa fa-bars"></i>
                            </button>

                            <!-- Topbar Search -->

                            <div class="top-tools-bar">
                                <h1 class="animated-text" style="margin-top: 10px;">BaiPlus : Online Banking System Management</h1>
                            </div>

                            <style>
                                .animated-text {
                                    font-size: 1rem;
                                    text-align: center;
                                    overflow: hidden;
                                    white-space: nowrap;
                                    color: #333;
                                    /* Change the color as per your preference */
                                    border-right: 0.15em solid #333;
                                    /* Change the border color and width as per your preference */
                                    animation: typing 0.5s steps(40, end), blink-caret 1.5s step-end infinite;
                                    transition: border-color 0.5s ease-out;
                                    /* Add transition effect to border-color property */
                                }

                                @keyframes typing {
                                    from {
                                        width: 0;
                                    }

                                    to {
                                        width: 100%;
                                    }
                                }

                                @keyframes blink-caret {

                                    from,
                                    to {
                                        border-color: transparent;
                                    }

                                    50% {
                                        border-color: #333;
                                        /* Change the color as per your preference */
                                    }
                                }

                                .animated-text:hover {
                                    border-color: #999;
                                    /* Change the border color on hover as per your preference */
                                }


                                /* .reveal {
        position: relative;
        transform: translateY(125px);
        opacity: 0.2;
        transition: 0.75s all ease;
    }

    .reveal.active {
        transform: translateY(0);
        opacity: 1;
    }

    .collapse {
        transition: height 0.3s ease;
        overflow: hidden;
    } */
                            </style>




                            <!-- Topbar Navbar -->
                            <ul class="navbar-nav ml-auto">

                                <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                                <li class="nav-item dropdown no-arrow d-sm-none">
                                    <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-search fa-fw"></i>
                                    </a>
                                    <!-- Dropdown - Messages -->
                                    <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                        <form class="form-inline mr-auto w-100 navbar-search">
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary" type="button">
                                                        <i class="fas fa-search fa-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </li>

                                <!-- Nav Item - Alerts -->
                                <li class="nav-item dropdown no-arrow mx-1">
                                    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-bell fa-fw"></i>
                                        <!-- Counter - Alerts -->
                                        <?php
                                        $query = "SELECT mh.account_id, mh.employee_id, e.employee_fname, e.employee_lname, mh.action_type, mh.datetime
                  FROM managehistory mh
                  INNER JOIN employee e ON mh.employee_id = e.employee_id
                  WHERE (mh.action_type LIKE '%Active->Suspend(Waiting for Approve),%' OR
                    --    mh.action_type LIKE '%Active->Freeze Permanent,%' OR
                    --    mh.action_type LIKE '%Active->Freeze Temp,%' OR
                       mh.action_type LIKE '%Inactive->Suspend(Waiting for Approve),%')";
                                        // --    mh.action_type LIKE '%Inactive->Freeze Permanent,%' OR
                                        // --    mh.action_type LIKE '%Inactive->Freeze Temp,%')";

                                        $result = mysqli_query($conn, $query);
                                        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                                        $alert_count = count($rows);
                                        ?>

                                        <span class="badge badge-danger badge-counter">
                                            <?php if ($alert_count > 2) { ?>
                                                <?php echo $alert_count; ?>+
                                            <?php } else { ?>
                                                <?php echo $alert_count; ?>
                                            <?php } ?>
                                        </span>
                                    </a>
                                    <!-- Dropdown - Alerts -->
                                    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                        <h6 class="dropdown-header">
                                            Alerts Center
                                        </h6>
                                        <div class="dropdown-scrollable">
                                            <?php foreach ($rows as $row) { ?>
                                                <?php
                                                // Format the datetime value
                                                $formatted_date = date('F d, Y H:i:s', strtotime($row['datetime']));
                                                ?>
                                                <a class="dropdown-item d-flex align-items-center" href="requesting.php">
                                                    <div class="mr-3">
                                                        <div class="icon-circle bg-primary">
                                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="small text-gray-500"><?php echo $formatted_date; ?></div>
                                                        <span class="font-weight-bold">From : <?php echo $row['employee_fname']; ?> <?php echo $row['employee_lname']; ?><br></span>
                                                        <?php echo $row['action_type']; ?> to Account ID : <?php echo $row['account_id']; ?>
                                                    </div>
                                                </a>
                                            <?php } ?>
                                        </div>
                                        <a class="dropdown-item text-center small text-gray-500" href="requesting.php">Show All Alerts</a>
                                    </div>
                                </li>

                                <style>
                                    .dropdown-scrollable {
                                        max-height: 300px;
                                        /* Adjust the height as needed */
                                        overflow-y: scroll;
                                    }
                                </style>

                                <!-- Nav Item - Messages -->
                                <!-- IF WANT TO USE MESSAGE -->

                                <div class="topbar-divider d-none d-sm-block"></div>

                                <!-- Nav Item - User Information -->
                                <li class="nav-item dropdown no-arrow">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?php echo '<span class="mr-2 d-none d-lg-inline text-gray-600 small">' . $user_fname . ' ' . $user_lname . '<br>' . $user_role . '</span>'; ?>

                                        <?php
                                        if ($user_role == 'Administrator') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/administrator.gif">
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        if ($user_role == 'Manager') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/manager.gif">
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        if ($user_role == 'Owner') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/owner.gif">
                                        <?php
                                        }
                                        ?>


                                    </a>




                                    <div class="modal fade bd-example-modal-lg" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="modal-body text-center">
                                                        <div class="form-group">
                                                            <img class="img-profile rounded-circle" src="img/<?php echo $user_role; ?>.gif" width="150">
                                                        </div>
                                                        <h2> <?php echo $user_role; ?> </h2>
                                                        <label> Username : <?php echo $username; ?> </label>
                                                        <h4> <?php echo $user_fname; ?> <?php echo $user_lname; ?> </h4>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Close</button>
                                                    </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>







                                    <!-- Dropdown - User Information -->
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#profileModal">
                                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Profile
                                        </a>
                                        <a class="dropdown-item" href="http://127.0.0.1/baiplus/employee_profile.php">
                                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Settings
                                        </a>
                                        <a class="dropdown-item" href="http://127.0.0.1/baiplus/managehistory.php">
                                            <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Activity Log
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Logout
                                        </a>
                                    </div>
                                </li>

                            </ul>

                        </nav>
                        <!-- End of Topbar -->


                        <?php if (isset($_SESSION['success'])) {

                        ?>

                            <div class="alert alert-success">
                                <?php
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php } ?>
                        <?php if (isset($_SESSION['error'])) {
                        ?>
                            <div class="alert alert-danger">
                                <?php
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php } ?>























                        <style>
                            @keyframes fade-in {
                                0% {
                                    opacity: 0;
                                }

                                100% {
                                    opacity: 1;
                                }
                            }

                            .page-transition-fade-in {
                                animation: fade-in 0.5s ease-in-out;
                            }


                            @keyframes slide-in-bottom {
                                0% {
                                    transform: translateY(100%);
                                    opacity: 0;
                                }

                                100% {
                                    transform: translateY(0);
                                    opacity: 1;
                                }
                            }

                            .page-transition-slide-in-bottom {
                                animation: slide-in-bottom 0.5s ease-in-out;
                            }

                            @keyframes slide-in-right {
                                0% {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }

                                100% {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                            }

                            .page-transition-slide-in-right {
                                animation: slide-in-right 0.s ease-in-out;
                            }

                            @keyframes scale-in {
                                0% {
                                    transform: scale(0);
                                }

                                100% {
                                    transform: scale(1);
                                }
                            }

                            /* Apply scale-in animation */
                            .page-transition-slide-in {
                                animation: scale-in 0.5s ease-in-out;
                            }
                        </style>

                        <!-- Begin Page Content -->
                        <div class="page-transition-fade-in">
                            <div class="container-fluid">

                                <!-- Page Heading -->
                                <!-- <div class="d-sm-flex align-items-center justify-content-between mb-4">
                                <h1 class="h3 mb-0 text-gray-800">Dashboardd</h1>
                                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
                            </div> -->

                                <!-- Content Row -->
                                <div class="row">

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Earnings (Total Accounts Balance)</div>
                                                        <?php
                                                        // Set up database connection and execute query to get total amount in Thai baht

                                                        // Format total amount in Thai baht as string with comma separator and two decimal places
                                                        $total_amount_formatted = number_format($total_amount, 2, '.', ',') . ' ฿';

                                                        // Display total amount in Thai baht in div
                                                        echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $total_amount_formatted . '</div>';
                                                        ?>

                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="85" src="img/baht.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Total Transaction Transfer</div>
                                                        <?php
                                                        // Set up database connection and execute query to get total amount in Thai baht

                                                        // Format total amount in Thai baht as string with comma separator and two decimal places
                                                        $total_transfer_formatt = number_format($sum, 2, '.', ',') . ' ฿';

                                                        // Display total amount in Thai baht in div
                                                        echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $total_transfer_formatt . '</div>';
                                                        ?>
                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="60" src="img/transaction2.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>




                                    <?php
                                    // Execute the SQL query and fetch the result
                                    $query = "SELECT COUNT(*) AS active_users_count, (COUNT(*) / (SELECT COUNT(*) FROM account)) * 100 AS active_users_percentage FROM account WHERE account_status = 'Active'";
                                    $result = mysqli_query($conn, $query);
                                    $row = mysqli_fetch_assoc($result);

                                    // Get the count and percentage values
                                    $active_users_count = $row['active_users_count'];
                                    $active_users_percentage = $row['active_users_percentage'];
                                    ?>

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total accounts (active)
                                                        </div>
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col-auto">
                                                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $active_users_count; ?> Accounts</div>
                                                            </div>
                                                            <div class="col">
                                                                <div class="progress progress-sm mr-2">
                                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $active_users_percentage; ?>%" aria-valuenow="<?php echo $active_users_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="70" src="img/active.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pending Requests Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <?php
                                                        // Assuming you have established a database connection

                                                        // Execute the query to count the requesting accounts
                                                        $query = "SELECT COUNT(*) AS count FROM account WHERE account_status = 'Suspend'";
                                                        $result = mysqli_query($conn, $query);

                                                        // Check if the query executed successfully
                                                        if ($result) {
                                                            // Fetch the count from the result
                                                            $row = mysqli_fetch_assoc($result);
                                                            $count = $row['count'];

                                                            // Replace '18' with the obtained count
                                                            $countToShow = 'None';
                                                            if ($count > 0) {
                                                                $countToShow = $count;
                                                            }

                                                            // Display the count in the HTML
                                                            echo '<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>';
                                                            echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $countToShow . '</div>';
                                                        } else {
                                                            // Query execution failed
                                                            echo 'Error: ' . mysqli_error($conn);
                                                        }

                                                        // Close the database connection
                                                        mysqli_close($conn);
                                                        ?>

                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="80" src="img/question.svg" alt="SVG Image">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Row -->

                                <div class="row">

                                    <!-- Area Chart -->
                                    <div class="col-lg-6 col-lg-7">
                                        <div class="card shadow mb-4">
                                            <!-- Card Header - Dropdown -->
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">The number of different types of transactions by bank name</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <div class="chart-area">
                                                    <canvas id="transaction-chart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pie Chart -->
                                    <div class="col-lg-5">
                                        <div class="card shadow mb-4">
                                            <!-- Card Header - Dropdown -->
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Types of accounts that are Frozen by employees with role Administrator</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <div class="chart-pie pt-4 pb-2">
                                                    <canvas id="myChartpie"></canvas>
                                                </div>
                                                <div class="mt-4 text-center small">
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-danger"></i> Business
                                                    </span>
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-primary"></i> Current
                                                    </span>
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-warning"></i> Savings
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Row -->
                                <div class="row">

                                    <!-- Content Column -->
                                    <div class="col-lg-6 mb-4">

                                        <!-- Project Card Example -->
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">The period of payment for cash loans the most (percentage)</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                // assuming you have already fetched the data from the database and stored it in $data_age
                                                foreach ($data_age as $age_range => $data_show) {
                                                    $timeliness_percentage = $data_show['timeliness_percentage'];
                                                ?>
                                                    <h4 class="small font-weight-bold"><?= $age_range ?><span class="float-right"><?= $timeliness_percentage ?>%</span></h4>
                                                    <div class="progress mb-4">
                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $timeliness_percentage ?>%" aria-valuenow="<?= $timeliness_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                                <!-- <h4 class="small font-weight-bold">Withdrawal <span class="float-right">40%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Transfer <span class="float-right">60%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Bill Payment <span class="float-right">80%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Account Setup <span class="float-right">Complete!</span></h4>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div> -->
                                            </div>
                                        </div>

                                        <!-- Color System -->
                                        <div class="row">
                                            <div class="col-lg-12 mb-4 reveal">
                                                <div class="card shadow ">
                                                    <div class="card-body">
                                                        <canvas id="age-chart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                    <div class="col-lg-6 mb-4">

                                        <!-- Illustrations -->
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Amount of debt outstanding for each customer</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center">
                                                    <!-- <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;" src="img/undraw_posting_photo.svg" alt="..."> -->
                                                    <canvas id="debt-chart"></canvas>
                                                    <!-- <p>Add some quality, svg illustrations to your project courtesy of <a target="_blank" rel="nofollow" href="advance.php">unDraw</a>, a
                                                constantly updated collection of beautiful svg images that you can use
                                                completely free and without attribution!</p> -->
                                                    <a target="_blank" rel="nofollow" href="advance.php">Browse Data in
                                                        Table &rarr;</a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Approach -->
                                        <div class="card shadow mb-4 reveal">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Total amount of bills paid Sorted by the top 5 most popular billing service providers</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <div class="card-body ">
                                                <!-- <p>SB Admin 2 makes extensive use of Bootstrap 4 utility classes in order to reduce
                                                CSS bloat and poor page performance. Custom CSS classes are used to create
                                                custom components and custom utility classes.</p>
                                            <p class="mb-0">Before working with this theme, you should become familiar with the
                                                Bootstrap framework, especially the utility classes.</p> -->
                                                <div class="chart-pie pt-4 pb-2">
                                                    <canvas id="best-bill-chart"></canvas>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>


















                        </div>
                        <!-- /.container-fluid -->
                        <!-- End of Main Content -->

                        <!-- Footer -->
                        <footer class="sticky-footer bg-white">
                            <div class="container my-auto">
                                <div class="copyright text-center my-auto">
                                    <span>
                                        <img src="img/mybplogo.png" alt="My Logo" width="30" />
                                        &nbsp;Copyright &copy; BaiPlus 2023
                                    </span>

                                </div>
                            </div>
                        </footer>
                        <!-- End of Footer -->

                    </div>
                    <!-- End of Content Wrapper -->

                </div>
                <!-- End of Page Wrapper -->

                <!-- Scroll to Top Button-->
                <a class="scroll-to-top rounded" href="#page-top">
                    <i class="fas fa-angle-up"></i>
                </a>

                <!-- Logout Modal-->
                <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">Cancel</button>
                                <a class="btn btn-outline-primary" href="logout.php">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap core JavaScript-->
                <script src="vendor/jquery/jquery.min.js"></script>
                <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

                <!-- Core plugin JavaScript-->
                <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

                <!-- Custom scripts for all pages-->
                <script src="js/sb-admin-2.min.js"></script>

                <!-- Page level plugins -->
                <script src="vendor/chart.js/Chart.min.js"></script>

                <!-- Page level custom scripts -->
                <script src="js/demo/chart-area-demo.js"></script>
                <script src="js/demo/chart-pie-demo.js"></script>

        </body>

        </html>



        <script>
            // Dynamic data
            const data = <?php echo json_encode($data); ?>;

            // Extract labels and data arrays from the data object
            const labels = Object.keys(data);
            const withdrawalData = Object.values(data).map(trans => trans['Withdrawal']);
            const transferData = Object.values(data).map(trans => trans['Transfer']);
            const billPaymentData = Object.values(data).map(trans => trans['Bill Payment']);
            const loanData = Object.values(data).map(trans => trans['Loan Installment Payment']);

            // Chart configuration object
            const config = {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Withdrawal',
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        data: withdrawalData,
                    }, {
                        label: 'Transfer',
                        backgroundColor: 'rgba(255, 206, 86, 0.5)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
                        data: transferData,
                    }, {
                        label: 'Bill Payment',
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        data: billPaymentData,
                    }, {
                        label: 'Loan Installment Payment',
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        data: loanData,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            stacked: true,
                            ticks: {
                                beginAtZero: true,
                                callback: function(value, index, values) {
                                    return '฿' + value;
                                }
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                            ticks: {
                                beginAtZero: true,
                            }
                        }]
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            fontSize: 12,
                            padding: 10,
                        }
                    },
                }
            };

            // Render the chart on the canvas element
            const chart = new Chart(document.getElementById('transaction-chart'), config);
        </script>





        <script>
            // Get the encoded JSON data from PHP
            var data_blocked_accounts = <?php echo $datablocked_accounts_json; ?>;

            // Create arrays to hold the data for the chart
            var account_types = [];
            var num_blocked_accounts = [];

            // Loop through the data and add each row to the arrays
            for (var i in data_blocked_accounts) {
                account_types.push(data_blocked_accounts[i].account_type);
                num_blocked_accounts.push(data_blocked_accounts[i].num_blocked_accounts);
            }

            // Create a new pie chart
            var ctx = document.getElementById("myChartpie").getContext("2d");
            var myPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: account_types,
                    datasets: [{
                        label: '# of Blocked Accounts',
                        data: num_blocked_accounts,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: true,
                        position: 'right'
                    }
                }
            });
        </script>


        <script>
            var data_debt = {
                labels: [],
                datasets: [{
                    label: "Outstanding Debt",
                    backgroundColor: "rgba(255,99,132,0.2)",
                    borderColor: "rgba(255,99,132,1)",
                    borderWidth: 1,
                    hoverBackgroundColor: "rgba(255,99,132,0.4)",
                    hoverBorderColor: "rgba(255,99,132,1)",
                    data: []
                }]
            };

            // Parse the JSON object and add the data to the Chart.js data object
            var obj_debt = JSON.parse('<?php echo $data_json_debt; ?>');
            for (var i = 0; i < obj_debt.length; i++) {
                data_debt.labels.push(obj_debt[i].customer_fname);
                data_debt.datasets[0].data.push(obj_debt[i].outstanding_debt);
            }

            // Create the Chart.js chart using the data object
            var ctx = document.getElementById("debt-chart").getContext("2d");
            var myChart = new Chart(ctx, {
                type: "bar",
                data: data_debt,
                options: {
                    responsive: true,
                    legend: {
                        position: "top"
                    },
                    title: {
                        display: true,
                        text: "Outstanding Debt by Customer"
                    }
                }
            });
        </script>







        <script>
            var data_best_bill = <?php echo $data_best_bill_json; ?>;

            // Create arrays to hold the data for the chart
            var biller_names = [];
            var total_bills_paid = [];

            // Loop through the data and add each row to the arrays
            for (var i in data_best_bill.labels) {
                biller_names.push(data_best_bill.labels[i]);
                total_bills_paid.push(data_best_bill.datasets[0].data[i]);
            }

            // Create a new pie chart
            var ctx = document.getElementById("best-bill-chart").getContext("2d");
            var myPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: biller_names,
                    datasets: [{
                        label: 'Total Bills Paid',
                        data: total_bills_paid,
                        backgroundColor: [
                            '#FFA500',
                            '#e74a3b',
                            '#00FFFF',
                            '#1cc88a',
                            '#9966FF'
                        ],
                        hoverBackgroundColor: [
                            // '#FF6384',
                            // '#36A2EB',
                            // '#FFCE56',
                            // '#4BC0C0',
                            // '#9966FF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: true,
                        position: 'right' // Adjust the position to 'right'
                    }
                }
            });
        </script>

        <script>
            var ctx = document.getElementById('age-chart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(<?php echo json_encode($data_age); ?>),
                    datasets: [{
                        label: 'Total loans',
                        data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_loans),
                        backgroundColor: '#ff6384',
                        borderWidth: 1
                    }, {
                        label: 'On-time payments',
                        data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.on_time_payments),
                        backgroundColor: '#36a2eb',
                        borderWidth: 1
                    }, {
                        label: 'Total loan amount',
                        data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_loan_amount),
                        backgroundColor: '#ffce56',
                        borderWidth: 1
                    }, {
                        label: 'Total payments amount',
                        data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_payments_amount),
                        backgroundColor: '#4bc0c0',
                        borderWidth: 1
                    }, {
                        label: 'Percentages (Paid)',
                        data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.timeliness_percentage),
                        backgroundColor: '#e84393',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        </script>
















        <!-- <script>
            function reveal() {
                var reveals = document.querySelectorAll(".reveal");

                for (var i = 0; i < reveals.length; i++) {
                    var windowHeight = window.innerHeight;
                    var elementTop = reveals[i].getBoundingClientRect().top;
                    var elementVisible = 150;

                    if (elementTop < windowHeight - elementVisible) {
                        reveals[i].classList.add("active");
                    } else {
                        reveals[i].classList.remove("active");
                    }
                }
            }

            window.addEventListener("scroll", reveal);
        </script> -->

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var collapseElement = document.querySelector("#collapseUtilities");
                var autoCollapseCheckbox = document.querySelector("#autoCollapseCheckbox");

                function toggleCollapse() {
                    if (autoCollapseCheckbox.checked) {
                        collapseElement.style.height = collapseElement.scrollHeight + "px";
                    } else {
                        collapseElement.style.height = "0px";
                    }
                }

                autoCollapseCheckbox.addEventListener("change", toggleCollapse);
                toggleCollapse();
            });
        </script>






















    <?php
    } elseif ($user_role == 'Manager') {
        $_SESSION['param1'] = $username;
        $_SESSION['param2'] = $password;
        $_SESSION['param3'] = $user_role;
        $_SESSION['param4'] = $user_fname;
        $_SESSION['param5'] = $user_lname;
        $_SESSION['param6'] = $user_employee_id;










        $query = "SELECT b.bank_name, t.trans_type, COUNT(*) as num_transactions
        FROM `transaction` t
        JOIN account a ON t.account_transferor = a.account_id
        JOIN bank b ON a.bank_id = b.bank_id
        GROUP BY b.bank_name, t.trans_type;";
        $result = mysqli_query($conn, $query);

        // Close the connection

        $data = array();
        $banks = array();
        $trans_types = array();

        // Loop through the result set and store the data in the array
        // while ($row = mysqli_fetch_assoc($result)) {
        //     $bank_name = $row['bank_name'];
        //     $trans_type = $row['trans_type'];
        //     $num_transactions = $row['num_transactions'];
        //     $data[$bank_name][$trans_type] = $num_transactions;
        // }

        //dyncmic
        while ($row = mysqli_fetch_assoc($result)) {
            $bank_name = $row['bank_name'];
            $trans_type = $row['trans_type'];
            $num_transactions = $row['num_transactions'];

            // Store the bank name in the banks array if it doesn't exist
            if (!in_array($bank_name, $banks)) {
                $banks[] = $bank_name;
            }

            // Store the transaction type in the trans_types array if it doesn't exist
            if (!in_array($trans_type, $trans_types)) {
                $trans_types[] = $trans_type;
            }

            // Store the data in the data array using the bank name and transaction type as keys
            $data[$bank_name][$trans_type] = $num_transactions;
        }


        $sql_blocked_accounts = "SELECT a.account_type, COUNT(*) as num_blocked_accounts
        FROM managehistory m
        JOIN employee e ON m.employee_id = e.employee_id
        JOIN account a ON m.account_id = a.account_id
        WHERE (m.action_type LIKE '%->Freeze Permanent%' OR
               m.action_type LIKE '%Active->Freeze Permanent,%' OR
               m.action_type LIKE '%Active->Freeze Temp,%' OR
               m.action_type LIKE '%Inactive->Freeze Permanent' OR
               m.action_type LIKE '%Inactive->Freeze Permanent,%' OR
               m.action_type LIKE '%Inactive->Freeze Temp,%')
              AND e.employee_role = 'Administrator'
        GROUP BY a.account_type";

        $result_blocked_accounts = mysqli_query($conn, $sql_blocked_accounts);

        // Create an array to hold the data
        $data_blocked_accounts = array();

        // Loop through the result set and add each row to the data array
        while ($row = mysqli_fetch_assoc($result_blocked_accounts)) {
            $data_blocked_accounts[] = array(
                "account_type" => $row["account_type"],
                "num_blocked_accounts" => $row["num_blocked_accounts"]
            );
        }
        $datablocked_accounts_json = json_encode($data_blocked_accounts);




        $sql_debt = "SELECT c.customer_id, c.customer_fname, l.loan_amount - COALESCE(SUM(t.transaction_amount), 0) as outstanding_debt
        FROM loan l
        JOIN account a ON l.account_id = a.account_id
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN transaction t ON a.account_id = t.account_transferor
        WHERE t.trans_type = 'Loan Installment Payment' OR t.trans_type IS NULL
        GROUP BY c.customer_id, c.customer_fname, l.loan_amount";
        $result_debt = mysqli_query($conn, $sql_debt);
        while ($row = mysqli_fetch_assoc($result_debt)) {
            $data_debt[] = array(
                "customer_id" => $row["customer_id"],
                "customer_fname" => $row["customer_fname"],
                "outstanding_debt" => $row["outstanding_debt"]
            );
        }

        // Encode the data array as a JSON object
        $data_json_debt = json_encode($data_debt);




        // Retrieve data from the database
        $sql_best_bill = "SELECT bi.biller_name, SUM(t.transaction_amount) as total_bills_paid
FROM transaction t
JOIN account a ON t.account_transferor = a.account_id
JOIN bill b ON t.bill_id = b.bill_id
JOIN billerinfo bi ON b.biller_id = bi.biller_id
WHERE t.trans_type = 'Bill Payment'
GROUP BY bi.biller_name
ORDER BY SUM(t.transaction_amount) DESC
LIMIT 5";

        $result_best_bill = mysqli_query($conn, $sql_best_bill);

        // Format data for Chart.js
        $data_best_bill = [
            "labels" => [],
            "datasets" => [
                [
                    "label" => "Total Bills Paid",
                    "backgroundColor" => ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"],
                    "hoverBackgroundColor" => ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"],
                    "data" => []
                ]
            ]
        ];

        while ($row = mysqli_fetch_assoc($result_best_bill)) {
            $data_best_bill["labels"][] = $row["biller_name"];
            $data_best_bill["datasets"][0]["data"][] = $row["total_bills_paid"];
        }

        // Convert data to JSON
        $data_best_bill_json = json_encode($data_best_bill);

        $sql = "SELECT SUM(account_balance) AS total_amount FROM account";
        $result = $conn->query($sql);

        // Extract total amount from query result
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total_amount = $row["total_amount"];
        } else {
            $total_amount = 0;
        }



        $sql = "SELECT SUM(transaction_amount) FROM `transaction` WHERE trans_type = 'Transfer'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $sum = $row['SUM(transaction_amount)'];










        $sql_age = "SELECT 
        age_range,
        COUNT(*) AS total_loans,
        SUM(on_time_payments) AS on_time_payments,
        SUM(total_loan_amount) AS total_loan_amount,
        SUM(total_payments_amount) AS total_payments_amount,
        ROUND(100 * SUM(total_payments_amount) / SUM(total_loan_amount), 2) AS timeliness_percentage
      FROM (
        SELECT 
          CASE 
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 20 THEN 'Under 20'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 30 THEN '20-30'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 40 THEN '30-40'
            WHEN YEAR(CURDATE()) - YEAR(c.customer_DOB) <= 50 THEN '40-50'
            ELSE 'Over 50'
          END AS age_range,
          COUNT(*) AS total_loans,
          SUM(CASE WHEN t.transaction_date <= DATE_ADD(l.loan_start_date, INTERVAL l.loan_duration MONTH) THEN 1 ELSE 0 END) AS on_time_payments,
          l.loan_amount AS total_loan_amount,
          IFNULL(SUM(CASE WHEN t.transaction_date <= DATE_ADD(l.loan_start_date, INTERVAL l.loan_duration MONTH) THEN IFNULL(t.transaction_amount, 0) ELSE 0 END), 0) AS total_payments_amount
        FROM loan l
        JOIN account a ON l.account_id = a.account_id
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN transaction t ON a.account_id = t.account_transferor AND t.trans_type = 'Loan Installment Payment'
        GROUP BY age_range, l.loan_id
      ) AS subquery
      GROUP BY age_range;
      ";
        $result_age = mysqli_query($conn, $sql_age); // assuming you're using mysqli to connect to the database
        while ($row = mysqli_fetch_assoc($result_age)) {
            $data_age[$row['age_range']] = array(
                'total_loans' => $row['total_loans'],
                'on_time_payments' => $row['on_time_payments'],
                'total_loan_amount' => $row['total_loan_amount'],
                'total_payments_amount' => $row['total_payments_amount'],
                'timeliness_percentage' => $row['timeliness_percentage'],
            );
        }











































    ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            <meta name="description" content="">
            <meta name="author" content="">

            <title>BaiPlus+ Dashboard</title>

            <!-- Custom fonts for this template-->
            <link rel="icon" href="img/favicon.ico" type="img/ico">
            <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
            <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

            <!-- Custom styles for this template-->
            <link href="css/sb-admin-2.min.css" rel="stylesheet">

        </head>

        <body id="page-top">

            <!-- Page Wrapper -->
            <div id="wrapper">

                <!-- Sidebar -->
                <ul class="navbar-nav bg-info sidebar sidebar-dark accordion" id="accordionSidebar">

                    <!-- Sidebar - Brand -->
                    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="http://127.0.0.1/baiplus/index.php">
                        <div class="sidebar-brand-icon rotate-n-0">
                            <img src="img\baiplus_logo.png.png" alt="baiplus_logo" width="71">
                        </div>
                        <div class="sidebar-brand-text mx-3">BaiPlus <sup>+</sup></div>
                    </a>

                    <!-- Divider -->
                    <hr class="sidebar-divider my-0">

                    <!-- Nav Item - Dashboard -->
                    <li class="nav-item active">
                        <a class="nav-link" href="http://127.0.0.1/baiplus/index.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span></a>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider">

                    <!-- Heading -->
                    <div class="sidebar-heading">
                        Management
                    </div>

                    <!-- Nav Item - Pages Collapse Menu -->
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            <i class="fas fa-fw fa-cog"></i>
                            <span>Account Manager</span>
                        </a>
                        <div id="collapseTwo" class="collapse show show" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">Account Manager</h6>
                                <a class="collapse-item" href="customer.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-vcard" viewBox="0 0 16 16">
                                        <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm4-2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5ZM9 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 9 8Zm1 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5Z" />
                                        <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2ZM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8.96c.026-.163.04-.33.04-.5C9 10.567 7.21 9 5 9c-2.086 0-3.8 1.398-3.984 3.181A1.006 1.006 0 0 1 1 12V4Z" />
                                    </svg> Customer</a>
                                <a class="collapse-item" href="account.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                                    </svg> Account</a>
                                <a class="collapse-item" href="bank.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bank" viewBox="0 0 16 16">
                                        <path d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.38l.5 2a.498.498 0 0 1-.485.62H.5a.498.498 0 0 1-.485-.62l.5-2A.501.501 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89L8 0ZM3.777 3h8.447L8 1 3.777 3ZM2 6v7h1V6H2Zm2 0v7h2.5V6H4Zm3.5 0v7h1V6h-1Zm2 0v7H12V6H9.5ZM13 6v7h1V6h-1Zm2-1V4H1v1h14Zm-.39 9H1.39l-.25 1h13.72l-.25-1Z" />
                                    </svg> Bank</a>
                                <a class="collapse-item" href="employee.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                    </svg> Employee</a>
                                <a class="collapse-item" href="credit_card.php"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z" />
                                        <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z" />
                                    </svg> Credit Card</a>
                                <a class="collapse-item" href="loan.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z" />
                                        <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z" />
                                    </svg> Loan</a>
                                <a class="collapse-item" href="postcode.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mailbox" viewBox="0 0 16 16">
                                        <path d="M4 4a3 3 0 0 0-3 3v6h6V7a3 3 0 0 0-3-3zm0-1h8a4 4 0 0 1 4 4v6a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V7a4 4 0 0 1 4-4zm2.646 1A3.99 3.99 0 0 1 8 7v6h7V7a3 3 0 0 0-3-3H6.646z" />
                                        <path d="M11.793 8.5H9v-1h5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.354-.146l-.853-.854zM5 7c0 .552-.448 0-1 0s-1 .552-1 0a1 1 0 0 1 2 0z" />
                                    </svg> Postcode</a>
                                <a class="collapse-item" href="managehistory.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z" />
                                        <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z" />
                                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z" />
                                    </svg> Manage History</a>


                            </div>
                        </div>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider">

                    <!-- Heading -->
                    <div class="sidebar-heading">
                        Addons
                    </div>


                    <!-- Nav Item - Tables -->
                    <li class="nav-item">
                        <a class="nav-link" href="table.php">
                            <i class="fas fa-fw fa-table"></i>
                            <span>Tables</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advance.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-heart-fill" viewBox="0 0 16 16">
                                <path d="M2 15.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v13.5zM8 4.41c1.387-1.425 4.854 1.07 0 4.277C3.146 5.48 6.613 2.986 8 4.412z" />
                            </svg>
                            <span>Advanced Analysis Report</span></a>
                    </li>

                    <!-- Divider -->
                    <hr class="sidebar-divider d-none d-md-block">

                    <!-- Sidebar Toggler (Sidebar) -->
                    <div class="text-center d-none d-md-inline">
                        <button class="rounded-circle border-0" id="sidebarToggle"></button>
                    </div>

                    <!-- Sidebar Message -->
                    <!-- <div class="sidebar-card d-none d-lg-flex">
                        <img class="sidebar-card-illustration mb-2" src="img/undraw_rocket.svg" alt="...">
                        <p class="text-center mb-2"><strong>BaiPlus</strong> is has many features, components, and more!</p>
                        <a class="btn btn-success btn-sm" href="https://startbootstrap.com/theme/sb-admin-pro">Let's get started!</a>
                    </div> -->

                </ul>
                <!-- End of Sidebar -->

                <!-- Content Wrapper -->
                <div id="content-wrapper" class="d-flex flex-column">

                    <!-- Main Content -->
                    <div id="content">

                        <!-- Topbar -->
                        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                            <!-- Sidebar Toggle (Topbar) -->
                            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                                <i class="fa fa-bars"></i>
                            </button>

                            <!-- Topbar Search -->
                            <div class="top-tools-bar">
                                <h1 class="animated-text" style="margin-top: 10px;">BaiPlus : Online Banking System Management</h1>
                            </div>

                            <style>
                                .animated-text {
                                    font-size: 1rem;
                                    text-align: center;
                                    overflow: hidden;
                                    white-space: nowrap;
                                    color: #333;
                                    /* Change the color as per your preference */
                                    border-right: 0.15em solid #333;
                                    /* Change the border color and width as per your preference */
                                    animation: typing 0.5s steps(40, end), blink-caret 1.5s step-end infinite;
                                    transition: border-color 0.5s ease-out;
                                    /* Add transition effect to border-color property */
                                }

                                @keyframes typing {
                                    from {
                                        width: 0;
                                    }

                                    to {
                                        width: 100%;
                                    }
                                }

                                @keyframes blink-caret {

                                    from,
                                    to {
                                        border-color: transparent;
                                    }

                                    50% {
                                        border-color: #333;
                                        /* Change the color as per your preference */
                                    }
                                }

                                .animated-text:hover {
                                    border-color: #999;
                                    /* Change the border color on hover as per your preference */
                                }


                                /* .reveal {
        position: relative;
        transform: translateY(125px);
        opacity: 0.2;
        transition: 0.75s all ease;
    }

    .reveal.active {
        transform: translateY(0);
        opacity: 1;
    }

    .collapse {
        transition: height 0.3s ease;
        overflow: hidden;
    } */
                            </style>

                            <!-- Topbar Navbar -->
                            <ul class="navbar-nav ml-auto">

                                <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                                <li class="nav-item dropdown no-arrow d-sm-none">
                                    <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-search fa-fw"></i>
                                    </a>
                                    <!-- Dropdown - Messages -->
                                    <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                        <form class="form-inline mr-auto w-100 navbar-search">
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary" type="button">
                                                        <i class="fas fa-search fa-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </li>

                                <!-- Nav Item - Alerts -->


                                <div class="topbar-divider d-none d-sm-block"></div>

                                <!-- Nav Item - User Information -->
                                <li class="nav-item dropdown no-arrow">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?php echo '<span class="mr-2 d-none d-lg-inline text-gray-600 small">' . $user_fname . ' ' . $user_lname . '<br>' . $user_role . '</span>'; ?>

                                        <?php
                                        if ($user_role == 'Administrator') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/administrator.gif">
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        if ($user_role == 'Manager') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/manager.gif">
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        if ($user_role == 'Owner') {
                                        ?>
                                            <img class="img-profile rounded-circle" src="img/owner.gif">
                                        <?php
                                        }
                                        ?>
                                    </a>
                                    <div class="modal fade bd-example-modal-lg" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="modal-body text-center">
                                                        <div class="form-group">
                                                            <img class="img-profile rounded-circle" src="img/<?php echo $user_role; ?>.gif" width="150">
                                                        </div>
                                                        <h2> <?php echo $user_role; ?> </h2>
                                                        <label> Username : <?php echo $username; ?> </label>
                                                        <h4> <?php echo $user_fname; ?> <?php echo $user_lname; ?> </h4>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Close</button>
                                                    </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>







                                    <!-- Dropdown - User Information -->
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#profileModal">
                                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Profile
                                        </a>
                                        <a class="dropdown-item" href="http://127.0.0.1/baiplus/employee_profile.php">
                                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Settings
                                        </a>
                                        <a class="dropdown-item" href="#">
                                            <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Activity Log
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Logout
                                        </a>
                                    </div>
                                </li>

                            </ul>

                        </nav>
                        <!-- End of Topbar -->





                        <?php if (isset($_SESSION['success'])) {

                        ?>

                            <div class="alert alert-success">
                                <?php
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php } ?>
                        <?php if (isset($_SESSION['error'])) {
                        ?>
                            <div class="alert alert-danger">
                                <?php
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php } ?>





                        <style>
                            @keyframes fade-in {
                                0% {
                                    opacity: 0;
                                }

                                100% {
                                    opacity: 1;
                                }
                            }

                            .page-transition-fade-in {
                                animation: fade-in 0.5s ease-in-out;
                            }


                            @keyframes slide-in-bottom {
                                0% {
                                    transform: translateY(100%);
                                    opacity: 0;
                                }

                                100% {
                                    transform: translateY(0);
                                    opacity: 1;
                                }
                            }

                            .page-transition-slide-in-bottom {
                                animation: slide-in-bottom 0.5s ease-in-out;
                            }

                            @keyframes slide-in-right {
                                0% {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }

                                100% {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                            }

                            .page-transition-slide-in-right {
                                animation: slide-in-right 0.5s ease-in-out;
                            }

                            @keyframes scale-in {
                                0% {
                                    transform: scale(0);
                                }

                                100% {
                                    transform: scale(1);
                                }
                            }

                            /* Apply scale-in animation */
                            .page-transition-slide-in {
                                animation: scale-in 0.5s ease-in-out;
                            }
                        </style>
                        <!-- Begin Page Content -->
                        <div class="page-transition-fade-in">
                            <div class="container-fluid">

                                <!-- Page Heading -->
                                <!-- <div class="d-sm-flex align-items-center justify-content-between mb-4">
                                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
                            </div> -->

                                <!-- Content Row -->
                                <div class="row">

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Earnings (Total Accounts Balance)</div>
                                                        <?php
                                                        // Set up database connection and execute query to get total amount in Thai baht

                                                        // Format total amount in Thai baht as string with comma separator and two decimal places
                                                        $total_amount_formatted = number_format($total_amount, 2, '.', ',') . ' ฿';

                                                        // Display total amount in Thai baht in div
                                                        echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $total_amount_formatted . '</div>';
                                                        ?>

                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="85" src="img/baht.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Total Transaction Transfer</div>
                                                        <?php
                                                        // Set up database connection and execute query to get total amount in Thai baht

                                                        // Format total amount in Thai baht as string with comma separator and two decimal places
                                                        $total_transfer_formatt = number_format($sum, 2, '.', ',') . ' ฿';

                                                        // Display total amount in Thai baht in div
                                                        echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $total_transfer_formatt . '</div>';
                                                        ?>
                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="60" src="img/transaction2.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>




                                    <?php
                                    // Execute the SQL query and fetch the result
                                    $query = "SELECT COUNT(*) AS active_users_count, (COUNT(*) / (SELECT COUNT(*) FROM account)) * 100 AS active_users_percentage FROM account WHERE account_status = 'Active'";
                                    $result = mysqli_query($conn, $query);
                                    $row = mysqli_fetch_assoc($result);

                                    // Get the count and percentage values
                                    $active_users_count = $row['active_users_count'];
                                    $active_users_percentage = $row['active_users_percentage'];
                                    ?>

                                    <!-- Earnings (Monthly) Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total accounts (active)
                                                        </div>
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col-auto">
                                                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $active_users_count; ?> Accounts</div>
                                                            </div>
                                                            <div class="col">
                                                                <div class="progress progress-sm mr-2">
                                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $active_users_percentage; ?>%" aria-valuenow="<?php echo $active_users_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="70" src="img/active.svg" alt="SVG Image">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pending Requests Card Example -->
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <?php
                                                        // Assuming you have established a database connection

                                                        // Execute the query to count the requesting accounts
                                                        $query = "SELECT COUNT(*) AS count FROM account WHERE account_status = 'Suspend'";
                                                        $result = mysqli_query($conn, $query);

                                                        // Check if the query executed successfully
                                                        if ($result) {
                                                            // Fetch the count from the result
                                                            $row = mysqli_fetch_assoc($result);
                                                            $count = $row['count'];

                                                            // Replace '18' with the obtained count
                                                            $countToShow = 'None';
                                                            if ($count > 0) {
                                                                $countToShow = $count;
                                                            }

                                                            // Display the count in the HTML
                                                            echo '<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>';
                                                            echo '<div class="h5 mb-0 font-weight-bold text-gray-800">' . $countToShow . '</div>';
                                                        } else {
                                                            // Query execution failed
                                                            echo 'Error: ' . mysqli_error($conn);
                                                        }

                                                        // Close the database connection
                                                        mysqli_close($conn);
                                                        ?>

                                                    </div>
                                                    <div class="col-auto">
                                                        <img width="80" src="img/question.svg" alt="SVG Image">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Row -->

                                <div class="row">

                                    <!-- Area Chart -->
                                    <div class="col-lg-6 col-lg-7">
                                        <div class="card shadow mb-4">
                                            <!-- Card Header - Dropdown -->
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">The number of different types of transactions by bank name</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <div class="chart-area">
                                                    <canvas id="transaction-chart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pie Chart -->
                                    <div class="col-lg-5">
                                        <div class="card shadow mb-4">
                                            <!-- Card Header - Dropdown -->
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Types of accounts that are Frozen by employees with role Administrator</h6>
                                                <div class="dropdown no-arrow">
                                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                                    </a>
                                                    <!-- dropdown header -->
                                                </div>
                                            </div>
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <div class="chart-pie pt-4 pb-2">
                                                    <canvas id="myChartpie"></canvas>
                                                </div>
                                                <div class="mt-4 text-center small">
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-danger"></i> Business
                                                    </span>
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-primary"></i> Current
                                                    </span>
                                                    <span class="mr-2">
                                                        <i class="fas fa-circle text-warning"></i> Savings
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Row -->
                                <div class="row">

                                    <!-- Content Column -->
                                    <div class="col-lg-6 mb-4">

                                        <!-- Project Card Example -->
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">The period of payment for cash loans the most (percentage)</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                // assuming you have already fetched the data from the database and stored it in $data_age
                                                foreach ($data_age as $age_range => $data_show) {
                                                    $timeliness_percentage = $data_show['timeliness_percentage'];
                                                ?>
                                                    <h4 class="small font-weight-bold"><?= $age_range ?><span class="float-right"><?= $timeliness_percentage ?>%</span></h4>
                                                    <div class="progress mb-4">
                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $timeliness_percentage ?>%" aria-valuenow="<?= $timeliness_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                                <!-- <h4 class="small font-weight-bold">Withdrawal <span class="float-right">40%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Transfer <span class="float-right">60%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Bill Payment <span class="float-right">80%</span></h4>
                                                <div class="progress mb-4">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <h4 class="small font-weight-bold">Account Setup <span class="float-right">Complete!</span></h4>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div> -->
                                            </div>
                                        </div>

                                        <!-- Color System -->
                                        <div class="row">
                                            <div class="col-lg-12 mb-4 reveal">
                                                <div class="card shadow ">
                                                    <div class="card-body">
                                                        <canvas id="age-chart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                    <div class="col-lg-6 mb-4">

                                        <!-- Illustrations -->
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Amount of debt outstanding for each customer</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center">
                                                    <!-- <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;" src="img/undraw_posting_photo.svg" alt="..."> -->
                                                    <canvas id="debt-chart"></canvas>
                                                    <!-- <p>Add some quality, svg illustrations to your project courtesy of <a target="_blank" rel="nofollow" href="advance.php">unDraw</a>, a
                                                constantly updated collection of beautiful svg images that you can use
                                                completely free and without attribution!</p> -->
                                                    <a target="_blank" rel="nofollow" href="advance.php">Browse Data in
                                                        Table &rarr;</a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Approach -->
                                        <div class="card shadow mb-4 reveal">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Total amount of bills paid Sorted by the top 5 most popular billing service providers</h6>
                                            </div>
                                            <div class="card-body ">
                                                <!-- <p>SB Admin 2 makes extensive use of Bootstrap 4 utility classes in order to reduce
                                                CSS bloat and poor page performance. Custom CSS classes are used to create
                                                custom components and custom utility classes.</p>
                                            <p class="mb-0">Before working with this theme, you should become familiar with the
                                                Bootstrap framework, especially the utility classes.</p> -->
                                                <div class="chart-pie pt-4 pb-2">
                                                    <canvas id="best-bill-chart"></canvas>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- /.container-fluid -->

                    </div>
                    <!-- End of Main Content -->

                    <!-- Footer -->
                    <footer class="sticky-footer bg-white">
                        <div class="container my-auto">
                            <div class="copyright text-center my-auto">
                                <span>
                                    <img src="img/mybplogo.png" alt="My Logo" width="30" />
                                    &nbsp;Copyright &copy; BaiPlus 2023
                                </span>
                            </div>
                        </div>
                    </footer>
                    <!-- End of Footer -->

                </div>
                <!-- End of Content Wrapper -->

            </div>
            <!-- End of Page Wrapper -->

            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>

            <!-- Logout Modal-->
            <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <a class="btn btn-outline-primary" href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bootstrap core JavaScript-->
            <script src="vendor/jquery/jquery.min.js"></script>
            <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

            <!-- Core plugin JavaScript-->
            <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

            <!-- Custom scripts for all pages-->
            <script src="js/sb-admin-2.min.js"></script>

            <!-- Page level plugins -->
            <script src="vendor/chart.js/Chart.min.js"></script>

            <!-- Page level custom scripts -->
            <script src="js/demo/chart-area-demo.js"></script>
            <script src="js/demo/chart-pie-demo.js"></script>








            <script>
                // Dynamic data
                const data = <?php echo json_encode($data); ?>;

                // Extract labels and data arrays from the data object
                const labels = Object.keys(data);
                const withdrawalData = Object.values(data).map(trans => trans['Withdrawal']);
                const transferData = Object.values(data).map(trans => trans['Transfer']);
                const billPaymentData = Object.values(data).map(trans => trans['Bill Payment']);
                const loanData = Object.values(data).map(trans => trans['Loan Installment Payment']);

                // Chart configuration object
                const config = {
                    type: 'horizontalBar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Withdrawal',
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            data: withdrawalData,
                        }, {
                            label: 'Transfer',
                            backgroundColor: 'rgba(255, 206, 86, 0.5)',
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1,
                            data: transferData,
                        }, {
                            label: 'Bill Payment',
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            data: billPaymentData,
                        }, {
                            label: 'Loan Installment Payment',
                            backgroundColor: 'rgba(153, 102, 255, 0.5)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                            data: loanData,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            xAxes: [{
                                stacked: true,
                                ticks: {
                                    beginAtZero: true,
                                    callback: function(value, index, values) {
                                        return '฿' + value;
                                    }
                                }
                            }],
                            yAxes: [{
                                stacked: true,
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 15,
                                fontSize: 12,
                                padding: 10,
                            }
                        },
                    }
                };

                // Render the chart on the canvas element
                const chart = new Chart(document.getElementById('transaction-chart'), config);
            </script>





            <script>
                // Get the encoded JSON data from PHP
                var data_blocked_accounts = <?php echo $datablocked_accounts_json; ?>;

                // Create arrays to hold the data for the chart
                var account_types = [];
                var num_blocked_accounts = [];

                // Loop through the data and add each row to the arrays
                for (var i in data_blocked_accounts) {
                    account_types.push(data_blocked_accounts[i].account_type);
                    num_blocked_accounts.push(data_blocked_accounts[i].num_blocked_accounts);
                }

                // Create a new pie chart
                var ctx = document.getElementById("myChartpie").getContext("2d");
                var myPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: account_types,
                        datasets: [{
                            label: '# of Blocked Accounts',
                            data: num_blocked_accounts,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            display: true,
                            position: 'right'
                        }
                    }
                });
            </script>


            <script>
                var data_debt = {
                    labels: [],
                    datasets: [{
                        label: "Outstanding Debt",
                        backgroundColor: "rgba(255,99,132,0.2)",
                        borderColor: "rgba(255,99,132,1)",
                        borderWidth: 1,
                        hoverBackgroundColor: "rgba(255,99,132,0.4)",
                        hoverBorderColor: "rgba(255,99,132,1)",
                        data: []
                    }]
                };

                // Parse the JSON object and add the data to the Chart.js data object
                var obj_debt = JSON.parse('<?php echo $data_json_debt; ?>');
                for (var i = 0; i < obj_debt.length; i++) {
                    data_debt.labels.push(obj_debt[i].customer_fname);
                    data_debt.datasets[0].data.push(obj_debt[i].outstanding_debt);
                }

                // Create the Chart.js chart using the data object
                var ctx = document.getElementById("debt-chart").getContext("2d");
                var myChart = new Chart(ctx, {
                    type: "bar",
                    data: data_debt,
                    options: {
                        responsive: true,
                        legend: {
                            position: "top"
                        },
                        title: {
                            display: true,
                            text: "Outstanding Debt by Customer"
                        }
                    }
                });
            </script>







            <script>
                var data_best_bill = <?php echo $data_best_bill_json; ?>;

                // Create arrays to hold the data for the chart
                var biller_names = [];
                var total_bills_paid = [];

                // Loop through the data and add each row to the arrays
                for (var i in data_best_bill.labels) {
                    biller_names.push(data_best_bill.labels[i]);
                    total_bills_paid.push(data_best_bill.datasets[0].data[i]);
                }

                // Create a new pie chart
                var ctx = document.getElementById("best-bill-chart").getContext("2d");
                var myPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: biller_names,
                        datasets: [{
                            label: 'Total Bills Paid',
                            data: total_bills_paid,
                            backgroundColor: [
                                '#FFA500',
                                '#e74a3b',
                                '#00FFFF',
                                '#1cc88a',
                                '#9966FF'
                            ],
                            hoverBackgroundColor: [
                                // '#FF6384',
                                // '#36A2EB',
                                // '#FFCE56',
                                // '#4BC0C0',
                                // '#9966FF'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            display: true,
                            position: 'right' // Adjust the position to 'right'
                        }
                    }
                });
            </script>

            <script>
                var ctx = document.getElementById('age-chart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(<?php echo json_encode($data_age); ?>),
                        datasets: [{
                            label: 'Total loans',
                            data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_loans),
                            backgroundColor: '#ff6384',
                            borderWidth: 1
                        }, {
                            label: 'On-time payments',
                            data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.on_time_payments),
                            backgroundColor: '#36a2eb',
                            borderWidth: 1
                        }, {
                            label: 'Total loan amount',
                            data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_loan_amount),
                            backgroundColor: '#ffce56',
                            borderWidth: 1
                        }, {
                            label: 'Total payments amount',
                            data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.total_payments_amount),
                            backgroundColor: '#4bc0c0',
                            borderWidth: 1
                        }, {
                            label: 'Percentages (Paid)',
                            data: Object.values(<?php echo json_encode($data_age); ?>).map(d => d.timeliness_percentage),
                            backgroundColor: '#e84393',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            </script>
















            <!-- <script>
                function reveal() {
                    var reveals = document.querySelectorAll(".reveal");

                    for (var i = 0; i < reveals.length; i++) {
                        var windowHeight = window.innerHeight;
                        var elementTop = reveals[i].getBoundingClientRect().top;
                        var elementVisible = 150;

                        if (elementTop < windowHeight - elementVisible) {
                            reveals[i].classList.add("active");
                        } else {
                            reveals[i].classList.remove("active");
                        }
                    }
                }

                window.addEventListener("scroll", reveal);
            </script> -->



















        </body>

        </html>




<?php
    } else {
        echo "You do not have permission to access this content.";
    }
} else {
    // Show an error message or redirect to a login page
    header("Location: login.php");
    echo "Invalid username or password.";
}
?>