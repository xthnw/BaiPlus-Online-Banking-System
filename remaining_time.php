<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Your code to establish a database connection and retrieve the account data

    $sql = "SELECT datetime FROM managehistory WHERE action_type = 'Active->Freeze Temp' AND account_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $freezeDateTime = $row['datetime'];
        $targetDateTime = date('Y-m-d H:i:s', strtotime('+19 hours', strtotime($freezeDateTime)));
        $currentDateTime = date('Y-m-d H:i:s');

        $targetTime = strtotime($targetDateTime);
        $currentTime = strtotime($currentDateTime);
        $remainingTime = $targetTime - $currentTime;

        if ($remainingTime > 0) {
            $remaining = array(
                'hours' => floor($remainingTime / 3600),
                'minutes' => floor(($remainingTime % 3600) / 60),
                'seconds' => $remainingTime % 60
            );
            echo json_encode($remaining);
        } else {
            echo json_encode(null); // Time expired
        }
    } else {
        echo json_encode(null); // Invalid account ID
    }
} else {
    echo json_encode(null); // Account ID not provided
}
?>
