<?php

session_start();
$_SESSION['previous_page'] = basename($_SERVER['PHP_SELF']);
$previousPage = $_SESSION['previous_page'] ?? 'unknown';

require_once "db.php";

if (isset($_POST['update'])) {
    $id = $_POST['id'];
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

    if (isset($_FILES['salary_file']) && $_FILES['salary_file']['error'] == 0){
        $salary_file = $_FILES['salary_file'];
    }
    else
    {
        $salary_file = $_POST['salary_file'];
    }
    $img = $_FILES['img_file'];



    $allow = array('jpg', 'jpeg', 'png', '');
    $extension = explode(".", $img['name']);
    $fileActExt = strtolower(end($extension));
    $fileNew = rand() . "." . $fileActExt;
    $filePath = "img/" . $fileNew;

    // $img2 = $_POST['img2'];
    // $upload = $_FILES['img']['name'];

    // if ($upload != '') {
    //     $allow = array('jpg', 'jpeg', 'png');
    //     $extension = explode('.', $img['name']);
    //     $fileActExt = strtolower(end($extension));
    //     $fileNew = rand() . "." . $fileActExt;  // rand function create the rand number 
    //     $filePath = 'uploads/' . $fileNew;

    //     if (in_array($fileActExt, $allow)) {
    //         if ($img['size'] > 0 && $img['error'] == 0) {
    //             move_uploaded_file($img['tmp_name'], $filePath);
    //         }
    //     }
    // } else {
    //     $fileNew = $img2;
    // }

    $sql = $conn->prepare("UPDATE customer SET customer_fname = :customer_fname, customer_lname = :customer_lname WHERE customer_ID = :id");
    $sql->bindParam(":id", $id);
    $sql->bindParam(":customer_fname", $customer_fname);
    $sql->bindParam(":customer_lname", $customer_lname);
    $sql->execute();

    if ($sql) {
        $_SESSION['success'] = "Data has been updated successfully";
        header("location: index.php");
    } else {
        $_SESSION['error'] = "Data has not been updated successfully";
        header("location: index.php");
    }
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

    <title>Customber Table</title>

    <!-- Custom fonts for this template -->
<link rel="icon" href="img/favicon.ico" type="img/ico">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

</head>

<style>
    table.table td a.delete {
        color: #F44336;
    }
</style>

<body>
    <div class="container mt-5">
        <h1>Edit Data</h1>
        <hr>
        <form action="edit.php" method="post" enctype="multipart/form-data">
            <?php
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $conn->query("SELECT * FROM customer WHERE customer_ID = $id");
                $stmt->execute();
                $data = $stmt->fetch();
            }
            ?>
            <div class="mb-3">
                <label for="id" class="col-form-label">ID:</label>
                <input type="text" readonly value="<?php echo $data['customer_ID']; ?>" required class="form-control" name="id">
                <label for="customer_fname" class="col-form-label">First Name:</label>
                <input type="text" value="<?php echo $data['customer_fname']; ?>" required class="form-control" name="customer_fname">
                <input type="hidden" value="<?php echo $data['img']; ?>" required class="form-control" name="img2">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="customer_fname" class="col-form-label">Last Name:</label>
                <input type="text" value="<?php echo $data['customer_lname']; ?>" required class="form-control" name="customer_lname">
            </div>
            <div class="mb-3">
                <label for="firstname" class="col-form-label">Position:</label>
                <input type="text" value="<?php echo $data['customer_phone']; ?>" required class="form-control" name="position">
            </div>

            <hr>
            <a href="index.php" class="btn btn-outline-secondary">Go Back</a>
            <button type="submit" name="update" class="btn btn-outline-primary">Update</button>
        </form>
    </div>

    <script>
        let imgInput = document.getElementById('imgInput');
        let previewImg = document.getElementById('previewImg');

        imgInput.onchange = evt => {
            const [file] = imgInput.files;
            if (file) {
                previewImg.src = URL.createObjectURL(file)
            }
        }
    </script>
</body>

</html>