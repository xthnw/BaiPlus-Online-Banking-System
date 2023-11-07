<?php

// $connection = mysqli_connect("localhost","root","");
// $db = mysqli_select_db($connection, 'baiplus');

session_start();
$_SESSION['previous_page'] = basename($_SERVER['PHP_SELF']);
$previousPage = $_SESSION['previous_page'] ?? 'unknown';
require_once "db.php";


if (isset($_POST['insert_error'])) {
    $err_code = $_POST['err_code'];
    $err_desc = $_POST['err_desc'];

    $sql = $conn->prepare("INSERT INTO `error` (err_code, err_desc)
    VALUE (:err_code, :err_desc)");

    $sql->bindParam(":err_code", $err_code);
    $sql->bindParam(":err_desc", $err_desc);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: error.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: error.php");
    }
}

if (isset($_POST['insert_managehistory'])) {
    $datetime = $_POST['datetime'];
    $employee_id = $_POST['employee_id'];
    $account_id = $_POST['account_id'];
    $action_type = $_POST['action_type'];
    $customer_ID = $_POST['customer_ID'];
    $card_no = $_POST['card_no'];
    $loan_id = $_POST['loan_id'];
    $sql = $conn->prepare("INSERT INTO `managehistory` (datetime, employee_id, account_id, action_type, customer_ID, card_no, loan_id)
                        VALUE (:datetime, :employee_id, :account_id, :action_type, :customer_ID, :card_no, :loan_id)");
    $sql->bindParam(":datetime", $datetime);
    $sql->bindParam(":employee_id", $employee_id);
    $sql->bindParam(":account_id", $account_id);
    $sql->bindParam(":action_type", $action_type);
    $sql->bindParam(":customer_ID", $customer_ID);
    $sql->bindParam(":card_no", $card_no);
    $sql->bindParam(":loan_id", $loan_id);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: postcode.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: postcode.php");
    }
}

if (isset($_POST['insert_postcode'])) {
    $customer_postcode = $_POST['customer_postcode'];
    $customer_city = $_POST['customer_city'];
    $customer_district = $_POST['customer_district'];
    $sql = $conn->prepare("INSERT INTO `postcode` (customer_postcode, customer_city, customer_district)
                        VALUE (:customer_postcode, :customer_city, :customer_district)");
    $sql->bindParam(":customer_postcode", $customer_postcode);
    $sql->bindParam(":customer_city", $customer_city);
    $sql->bindParam(":customer_district", $customer_district);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: postcode.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: postcode.php");
    }
}

if (isset($_POST['insert_loan'])) {
    $loan_id = $_POST['loan_id'];
    $account_id = $_POST['account_id'];
    $loan_type = $_POST['loan_type'];
    $loan_amount = $_POST['loan_amount'];
    $loan_start_date = $_POST['loan_start_date'];
    $loan_duration = $_POST['loan_duration'];
    $loan_interest = $_POST['loan_interest'];
    $sql = $conn->prepare("INSERT INTO `loan` (loan_id, account_id, loan_type, loan_amount, loan_start_date, loan_duration, loan_interest)
                        VALUE (:loan_id, :account_id, :loan_type, :loan_amount, :loan_start_date, :loan_duration, :loan_interest)");
    $sql->bindParam(":loan_id", $loan_id);
    $sql->bindParam(":account_id", $account_id);
    $sql->bindParam(":loan_type", $loan_type);
    $sql->bindParam(":loan_amount", $loan_amount);
    $sql->bindParam(":loan_start_date", $loan_start_date);
    $sql->bindParam(":loan_duration", $loan_duration);
    $sql->bindParam(":loan_interest", $loan_interest);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: loan.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: loan.php");
    }
}


if (isset($_POST['insert_credit_card'])) {
    $card_no = generateRandomNumeric(16);
    $card_exp = date('Y-m-d', strtotime('+5 years'));
    $cvv = generateRandomNumeric(3);
    // $card_no = $_POST['card_no'];
    // $card_exp = $_POST['card_exp'];
    // $cvv = $_POST['cvv'];
    $max_limit = $_POST['max_limit'];
    $customer_ID = $_POST['customer_ID'];
    $card_status = $_POST['card_status'];
    $bank_id = $_POST['bank_id'];

    $sql = $conn->prepare("INSERT INTO `creditcard` (card_no, card_exp, cvv, max_limit, customer_id, card_status, bank_id)
                        VALUE (:card_no, :card_exp, :cvv, :max_limit, :customer_id, :card_status, :bank_id)");
    $sql->bindParam(":card_no", $card_no);
    $sql->bindParam(":card_exp", $card_exp);
    $sql->bindParam(":cvv", $cvv);
    $sql->bindParam(":max_limit", $max_limit);
    $sql->bindParam(":customer_id", $customer_ID);
    $sql->bindParam(":card_status", $card_status);
    $sql->bindParam(":bank_id", $bank_id);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: credit_card.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: credit_card.php");
    }
}
// Function to generate a random numeric string
function generateRandomNumeric($length)
{
    $numeric = '0123456789';
    $numericLength = strlen($numeric);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $numeric[rand(0, $numericLength - 1)];
    }
    return $randomString;
}


if (isset($_POST['insert_biller'])) {
    if (isset($_FILES['biller_img']) && $_FILES['biller_img']['error'] == 0) {

        $biller_id = $_POST['biller_id'];
        $biller_name = $_POST['biller_name'];
        $biller_img = $_FILES['biller_img'];
        $biller_address = $_POST['biller_address'];
        $postcode = $_POST['postcode'];


        $file_name = $biller_img['name'];
        $path = 'biller-img/' . $file_name;

        move_uploaded_file($biller_img['tmp_name'], $path);

        $sql = $conn->prepare("INSERT INTO `billerinfo` (biller_id, biller_name, biller_address, postcode, biller_img)
                        VALUE (:biller_id, :biller_name, :biller_address, :postcode, :biller_img)");
        $sql->bindParam(":biller_id", $biller_id);
        $sql->bindParam(":biller_name", $biller_name);
        $sql->bindParam(":biller_address", $biller_address);
        $sql->bindParam(":postcode", $postcode);
        $sql->bindParam(":biller_img", $file_name);
        $sql->execute();
        if ($sql) {
            $_SESSION['success'] = "Data has been inserted successfully";
            header("location: biller.php");
        } else {
            $_SESSION['error'] = "Data has not been inserted successfully";
            header("location: biller.php");
        }
    } else {
        $biller_id = $_POST['biller_id'];
        $biller_name = $_POST['biller_name'];
        $biller_img = $_FILES['biller_img'];
        $biller_address = $_POST['biller_address'];
        $postcode = $_POST['postcode'];
        $biller_img = NULL;

        $sql = $conn->prepare("INSERT INTO `billerinfo` (biller_id, biller_name, biller_address, postcode, biller_img)
        VALUE (:biller_id, :biller_name, :biller_address, :postcode, :biller_img)");
        $sql->bindParam(":biller_id", $biller_id);
        $sql->bindParam(":biller_name", $biller_name);
        $sql->bindParam(":biller_address", $biller_address);
        $sql->bindParam(":postcode", $postcode);
        $sql->bindParam(":biller_img", $biller_img);
        $sql->execute();
        if ($sql) {
            $_SESSION['success'] = "Data has been inserted successfully";
            header("location: biller.php");
        } else {
            $_SESSION['error'] = "Data has not been inserted successfully";
            header("location: biller.php");
        }
    }
}



if (isset($_POST['insert_bill'])) {
    $bill_id = $_POST['bill_id'];
    $bill_exp = $_POST['bill_exp'];
    $bill_amount = $_POST['bill_amount'];
    $bill_owner_fname = $_POST['bill_owner_fname'];
    $bill_owner_lname = $_POST['bill_owner_lname'];
    $biller_id = $_POST['biller_id'];
    $account_id = $_POST['account_id'];

    try {
        $sql = $conn->prepare("INSERT INTO `bill` (bill_id, bill_exp, bill_amount, bill_owner_fname, bill_owner_lname, biller_id, account_id)
        VALUE (:bill_id, :bill_exp, :bill_amount, :bill_owner_fname, :bill_owner_lname, :biller_id, :account_id)");
        $sql->bindParam(":bill_id", $bill_id);
        $sql->bindParam(":bill_exp", $bill_exp);
        $sql->bindParam(":bill_amount", $bill_amount);
        $sql->bindParam(":bill_owner_fname", $bill_owner_fname);
        $sql->bindParam(":bill_owner_lname", $bill_owner_lname);
        $sql->bindParam(":biller_id", $biller_id);
        $sql->bindParam(":account_id", $account_id);
    } catch (PDOException $e) {
        header("location: bill.php");
        $error = $conn->errorInfo();
        echo $error['message'];
        header("location: bill.php");
    }


    // $sql->execute();
    if ($sql->execute()) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: bill.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully: " . $sql->errorCode() . " - " . implode(", ", $sql->errorInfo());
        header("location: bill.php");
    }
}


if (isset($_POST['insert_employee'])) {
    $employee_id = $_POST['employee_id'];
    $employee_fname = $_POST['employee_fname'];
    $employee_lname = $_POST['employee_lname'];
    $employee_username = $_POST['employee_username'];
    $employee_password = $_POST['employee_password'];
    $employee_role = $_POST['employee_role'];

    $sql = $conn->prepare("INSERT INTO `employee` (employee_id, employee_fname, employee_lname, employee_username, employee_password, employee_role)
                        VALUE (:employee_id, :employee_fname, :employee_lname, :employee_username, :employee_password, :employee_role)");
    $sql->bindParam(":employee_id", $employee_id);
    $sql->bindParam(":employee_fname", $employee_fname);
    $sql->bindParam(":employee_lname", $employee_lname);
    $sql->bindParam(":employee_username", $employee_username);
    $sql->bindParam(":employee_password", $employee_password);
    $sql->bindParam(":employee_role", $employee_role);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: employee.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: employee.php");
    }
}


if (isset($_POST['insert_bank'])) {

    if (isset($_FILES['bank_pic']) && $_FILES['bank_pic']['error'] == 0) {
        $bank_id = $_POST['bank_id'];
        $bank_name = $_POST['bank_name'];
        $bank_pic = $_FILES['bank_pic'];

        $file_name = $bank_pic['name'];
        $path = 'bank-img/' . $file_name;

        move_uploaded_file($bank_pic['tmp_name'], $path);


        $sql = $conn->prepare("INSERT INTO `bank` (bank_id, bank_name, bank_pic)
                        VALUE (:bank_id, :bank_name, :bank_pic)");
        $sql->bindParam(":bank_id", $bank_id);
        $sql->bindParam(":bank_name", $bank_name);
        $sql->bindParam(":bank_pic", $file_name);
        $sql->execute();
        if ($sql) {
            $_SESSION['success'] = "Data has been inserted successfully";
            header("location: bank.php");
        } else {
            $_SESSION['error'] = "Data has not been inserted successfully";
            header("location: bank.php");
        }
    } else {
        $bank_id = $_POST['bank_id'];
        $bank_name = $_POST['bank_name'];
        $bank_pic = $_FILES['bank_pic'];
        $bank_pic = "default_bank.png";

        $sql = $conn->prepare("INSERT INTO `bank` (bank_id, bank_name, bank_pic)
                        VALUE (:bank_id, :bank_name, :bank_pic)");
        $sql->bindParam(":bank_id", $bank_id);
        $sql->bindParam(":bank_name", $bank_name);
        $sql->bindParam(":bank_pic", $bank_pic);
        $sql->execute();
        if ($sql) {
            $_SESSION['success'] = "Data has been inserted successfully";
            header("location: bank.php");
        } else {
            $_SESSION['error'] = "Data has not been inserted successfully";
            header("location: bank.php");
        }
    }
}
if (isset($_POST['insert_account'])) {

    $account_id = $_POST['account_id'];
    $account_name = $_POST['account_name'];
    $account_DOP = $_POST['account_DOP'];
    $account_balance = $_POST['account_balance'];
    $customer_ID = $_POST['customer_ID'];
    $account_type = $_POST['account_type'];
    $account_status = $_POST['account_status'];
    $bank_id = $_POST['bank_id'];

    $sql = $conn->prepare("INSERT INTO `account` (account_id, account_name, account_DOP, account_balance, customer_ID, account_type, account_status, bank_id)
                        VALUE (:account_id, :account_name, :account_DOP, :account_balance, :customer_ID, :account_type, :account_status, :bank_id)");
    $sql->bindParam(":account_id", $account_id);
    $sql->bindParam(":account_name", $account_name);
    $sql->bindParam(":account_DOP", $account_DOP);
    $sql->bindParam(":account_balance", $account_balance);
    $sql->bindParam(":customer_ID", $customer_ID);
    $sql->bindParam(":account_type", $account_type);
    $sql->bindParam(":account_status", $account_status);
    $sql->bindParam(":bank_id", $bank_id);
    $sql->execute();
    if ($sql) {
        $_SESSION['success'] = "Data has been inserted successfully";
        header("location: account.php");
    } else {
        $_SESSION['error'] = "Data has not been inserted successfully";
        header("location: account.php");
    }
}







if (isset($_POST['insertdata'])) {

    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {

        if (isset($_FILES['salary_file']) && $_FILES['salary_file']['error'] == 0) {
            $customer_ID = $_POST['customer_ID'];
            $customer_fname = $_POST['customer_fname'];
            $customer_lname = $_POST['customer_lname'];
            $customer_email = $_POST['customer_email'];
            $customer_DOB = $_POST['customer_DOB'];
            $customer_gender = $_POST['customer_gender'];
            $customer_address = $_POST['customer_address'];
            $customer_postcode = $_POST['customer_postcode'];
            $card_code = $_POST['card_code'];
            $customer_phone = $_POST['customer_phone'];
            $customer_password = $_POST['customer_password'];
            $account_pin = $_POST['account_pin'];
            $salary = $_POST['salary'];
            $salary_file = $_FILES['salary_file'];
            $img = $_FILES['img'];



            $allow = array('jpg', 'jpeg', 'png', 'pdf');
            $extension = explode(".", $img['name']);
            $fileActExt = strtolower(end($extension));
            $fileNew = rand() . "." . $fileActExt;
            $filePath = 'img/' . $fileNew;

            $salary_allow = array('jpg', 'jpeg', 'png', 'pdf');
            $extension_salary = explode(".", $salary_file['name']);
            $fileActExt_salary = strtolower(end($extension_salary));
            $fileNewSalary = rand() . "." . $fileActExt_salary;
            $filePathSalary = 'salary/' . $fileNewSalary;

            if (in_array($fileActExt, $allow) && in_array($fileActExt_salary, $salary_allow)) {

                move_uploaded_file($img['tmp_name'], $filePath);
                move_uploaded_file($salary_file['tmp_name'], $filePathSalary);
                $sql = $conn->prepare("INSERT INTO `customer` (customer_ID, customer_fname, customer_lname, customer_email, customer_DOB, customer_gender, customer_address, customer_postcode, card_code, customer_phone, customer_password, account_pin, salary, 
                            salary_file, 
                            img)
                        VALUE (:customer_ID, :customer_fname, :customer_lname, :customer_email, :customer_DOB, :customer_gender, :customer_address, :customer_postcode, :card_code, :customer_phone, :customer_password, :account_pin, :salary, 
                        :salary_file, 
                        :img) ");
                $sql->bindParam(":customer_ID", $customer_ID);
                $sql->bindParam(":customer_fname", $customer_fname);
                $sql->bindParam(":customer_lname", $customer_lname);
                $sql->bindParam(":customer_email", $customer_email);
                $sql->bindParam(":customer_DOB", $customer_DOB);
                $sql->bindParam(":customer_gender", $customer_gender);
                $sql->bindParam(":customer_address", $customer_address);
                $sql->bindParam(":customer_postcode", $customer_postcode);
                $sql->bindParam(":card_code", $card_code);
                $sql->bindParam(":customer_phone", $customer_phone);
                $sql->bindParam(":customer_password", $customer_password);
                $sql->bindParam(":account_pin", $account_pin);
                $sql->bindParam(":salary", $salary);
                $sql->bindParam(":salary_file", $fileNewSalary);
                $sql->bindParam(":img", $fileNew);
                $sql->execute();
                if ($sql) {
                    $_SESSION['success'] = "Data has been inserted successfully";
                    header("location: customer.php");
                } else {
                    $_SESSION['error'] = "Data has not been inserted successfully";
                    header("location: customer.php");
                }
            } else {
                $_SESSION['error'] = "Invalid file";
                header("location: customer.php");
            }
        } else {
            $customer_ID = $_POST['customer_ID'];
            $customer_fname = $_POST['customer_fname'];
            $customer_lname = $_POST['customer_lname'];
            $customer_email = $_POST['customer_email'];
            $customer_DOB = $_POST['customer_DOB'];
            $customer_gender = $_POST['customer_gender'];
            $customer_address = $_POST['customer_address'];
            $customer_postcode = $_POST['customer_postcode'];
            $card_code = $_POST['card_code'];
            $customer_phone = $_POST['customer_phone'];
            $customer_password = $_POST['customer_password'];
            $account_pin = $_POST['account_pin'];
            $salary = $_POST['salary'];
            $salary_file = NULL;
            $img = $_FILES['img'];



            $allow = array('jpg', 'jpeg', 'png', 'pdf');
            $extension = explode(".", $img['name']);
            $fileActExt = strtolower(end($extension));
            $fileNew = rand() . "." . $fileActExt;
            $filePath = 'img/' . $fileNew;

            if (in_array($fileActExt, $allow)) {
                if (move_uploaded_file($img['tmp_name'], $filePath)) {
                    $sql = $conn->prepare("INSERT INTO `customer` (customer_ID, customer_fname, customer_lname, customer_email, customer_DOB, customer_gender, customer_address, customer_postcode, card_code, customer_phone, customer_password, account_pin, salary, 
                            salary_file, 
                            img)
                        VALUE (:customer_ID, :customer_fname, :customer_lname, :customer_email, :customer_DOB, :customer_gender, :customer_address, :customer_postcode, :card_code, :customer_phone, :customer_password, :account_pin, :salary, 
                        :salary_file, 
                        :img) ");
                    $sql->bindParam(":customer_ID", $customer_ID);
                    $sql->bindParam(":customer_fname", $customer_fname);
                    $sql->bindParam(":customer_lname", $customer_lname);
                    $sql->bindParam(":customer_email", $customer_email);
                    $sql->bindParam(":customer_DOB", $customer_DOB);
                    $sql->bindParam(":customer_gender", $customer_gender);
                    $sql->bindParam(":customer_address", $customer_address);
                    $sql->bindParam(":customer_postcode", $customer_postcode);
                    $sql->bindParam(":card_code", $card_code);
                    $sql->bindParam(":customer_phone", $customer_phone);
                    $sql->bindParam(":customer_password", $customer_password);
                    $sql->bindParam(":account_pin", $account_pin);
                    $sql->bindParam(":salary", $salary);
                    $sql->bindParam(":salary_file", $salary_file);
                    $sql->bindParam(":img", $fileNew);
                    $sql->execute();
                    if ($sql) {
                        $_SESSION['success'] = "Data has been inserted successfully";
                        header("location: customer.php");
                    } else {
                        $_SESSION['error'] = "Data has not been inserted successfully";
                        header("location: customer.php");
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid file";
                header("location: customer.php");
            }
        }
    }
    // dont add image
    else {
        $img = "default_profile.png";
        $customer_ID = $_POST['customer_ID'];
        $customer_fname = $_POST['customer_fname'];
        $customer_lname = $_POST['customer_lname'];
        $customer_email = $_POST['customer_email'];
        $customer_DOB = $_POST['customer_DOB'];
        $customer_gender = $_POST['customer_gender'];
        $customer_address = $_POST['customer_address'];
        $customer_postcode = $_POST['customer_postcode'];
        $card_code = $_POST['card_code'];
        $customer_phone = $_POST['customer_phone'];
        $customer_password = $_POST['customer_password'];
        $account_pin = $_POST['account_pin'];
        $salary = $_POST['salary'];
        if (isset($_FILES['salary_file']) && $_FILES['salary_file']['error'] == 0) {
            $salary_file = $_FILES['salary_file'];

            $salary_allow = array('jpg', 'jpeg', 'png', 'pdf');
            $extension_salary = explode(".", $salary_file['name']);
            $fileActExt_salary = strtolower(end($extension_salary));
            $fileNewSalary = rand() . "." . $fileActExt_salary;
            $filePathSalary = 'salary/' . $fileNewSalary;
            if (in_array($fileActExt_salary, $salary_allow)) {
                move_uploaded_file($salary_file['tmp_name'], $filePathSalary);


                $sql = $conn->prepare("INSERT INTO `customer` (customer_ID, customer_fname, customer_lname, customer_email, customer_DOB, customer_gender, customer_address, customer_postcode, card_code, customer_phone, customer_password, account_pin, salary, 
                    salary_file, 
                    img)
                VALUE (:customer_ID, :customer_fname, :customer_lname, :customer_email, :customer_DOB, :customer_gender, :customer_address, :customer_postcode, :card_code, :customer_phone, :customer_password, :account_pin, :salary, 
                :salary_file, 
                :img) ");
                $sql->bindParam(":customer_ID", $customer_ID);
                $sql->bindParam(":customer_fname", $customer_fname);
                $sql->bindParam(":customer_lname", $customer_lname);
                $sql->bindParam(":customer_email", $customer_email);
                $sql->bindParam(":customer_DOB", $customer_DOB);
                $sql->bindParam(":customer_gender", $customer_gender);
                $sql->bindParam(":customer_address", $customer_address);
                $sql->bindParam(":customer_postcode", $customer_postcode);
                $sql->bindParam(":card_code", $card_code);
                $sql->bindParam(":customer_phone", $customer_phone);
                $sql->bindParam(":customer_password", $customer_password);
                $sql->bindParam(":account_pin", $account_pin);
                $sql->bindParam(":salary", $salary);
                $sql->bindParam(":salary_file", $fileNewSalary);
                $sql->bindParam(":img", $img);
                $sql->execute();
                if ($sql) {
                    $_SESSION['success'] = "Data has been inserted successfully";
                    header("location: customer.php");
                } else {
                    $_SESSION['error'] = "Data has not been inserted successfully";
                    header("location: customer.php");
                }
            } else {
                $_SESSION['error'] = "Invalid file";
                header("location: customer.php");
            }
        } else {
            $salary_file = NULL;




            $sql = $conn->prepare("INSERT INTO `customer` (customer_ID, customer_fname, customer_lname, customer_email, customer_DOB, customer_gender, customer_address, customer_postcode, card_code, customer_phone, customer_password, account_pin, salary, 
                    salary_file, 
                    img)
                VALUE (:customer_ID, :customer_fname, :customer_lname, :customer_email, :customer_DOB, :customer_gender, :customer_address, :customer_postcode, :card_code, :customer_phone, :customer_password, :account_pin, :salary, 
                :salary_file, 
                :img) ");
            $sql->bindParam(":customer_ID", $customer_ID);
            $sql->bindParam(":customer_fname", $customer_fname);
            $sql->bindParam(":customer_lname", $customer_lname);
            $sql->bindParam(":customer_email", $customer_email);
            $sql->bindParam(":customer_DOB", $customer_DOB);
            $sql->bindParam(":customer_gender", $customer_gender);
            $sql->bindParam(":customer_address", $customer_address);
            $sql->bindParam(":customer_postcode", $customer_postcode);
            $sql->bindParam(":card_code", $card_code);
            $sql->bindParam(":customer_phone", $customer_phone);
            $sql->bindParam(":customer_password", $customer_password);
            $sql->bindParam(":account_pin", $account_pin);
            $sql->bindParam(":salary", $salary);
            $sql->bindParam(":salary_file", $salary_file);
            $sql->bindParam(":img", $img);
            $sql->execute();
            if ($sql) {
                $_SESSION['success'] = "Data has been inserted successfully";
                header("location: customer.php");
            } else {
                $_SESSION['error'] = "Data has not been inserted successfully";
                header("location: customer.php");
            }
        }
    }



















    // $query = "INSERT INTO `customer` (customer_ID, customer_fname, customer_lname, customer_email, customer_DOB, customer_gender, customer_address, customer_postcode, profile_name, card_code, customer_phone, customer_password, account_pin, salary, salary_file) VALUES 
    // ('$customer_ID', '$customer_fname', '$customer_lname', '$customer_email', '$customer_DOB', '$customer_gender', '$customer_address', '$customer_postcode', '$profile_name', '$card_code', '$customer_phone', '$customer_password', '$account_pin', '$salary', '$salary_file')";

    // $query_run = mysqli_query($connection, $query);

    // if($query_run)
    // {
    //     echo '<script> alert("Data Saved"); </script>';
    //     header('Location: index.php');
    // }
    // else
    // {
    //     echo '<script> alert("Data Not Saved"); </script>';
    // }
}
