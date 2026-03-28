<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_connection.php');

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $typed_user = mysqli_real_escape_string($con, trim($_POST['user']));
    $user_q = "INSERT IGNORE INTO users (user_name) VALUES ('$typed_user')";
    mysqli_query($con, $user_q);

    $check_user_q = mysqli_query($con, "SELECT user_id FROM users WHERE user_name = '$typed_user'") or die("Query failed: " . mysqli_error($con));
    $user_data = mysqli_fetch_assoc($check_user_q);
    $user_id = $user_data['user_id'];

    $title = mysqli_real_escape_string($con, trim($_POST['title']));
    $body = mysqli_real_escape_string($con, trim($_POST['body']));
    $image = '';

    if(isset($_FILES['post_img']) && $_FILES['post_img']['error'] !== UPLOAD_ERR_NO_FILE){
        $dest = "images/";
        $file_name = time() . '_' . basename($_FILES["post_img"]["name"]);
        $target = $dest . $file_name;

        if(move_uploaded_file($_FILES["post_img"]["tmp_name"], $target)){
            $image = $target;
        }
    }

    $q = "INSERT INTO posts (title, body, post_img, user_id, post_date) VALUES ('$title', '$body', '$image', '$user_id', DATE(NOW()))";
    if (mysqli_query($con, $q)) {
        header("Location: community.php");
        exit();
    } else {
        // This line is key. It will stop the page and tell you the ERROR.
        die("STOP! Database Error: " . mysqli_error($con));
    }
}
?>