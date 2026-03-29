<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_connection.php');

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $post_id = mysqli_real_escape_string($con, ($_POST['post_id']));
    $typed_user = mysqli_real_escape_string($con, trim($_POST['user']));
    $user_q = "INSERT IGNORE INTO users (user_name) VALUES ('$typed_user')";
    mysqli_query($con, $user_q);

    $check_user_q = mysqli_query($con, "SELECT user_id FROM users WHERE user_name = '$typed_user'") or die("Query failed: " . mysqli_error($con));
    $user_data = mysqli_fetch_assoc($check_user_q);
    $user_id = $user_data['user_id'];

    $newTitle = mysqli_real_escape_string($con, trim($_POST['title']));
    $newBody = mysqli_real_escape_string($con, trim($_POST['body']));
    $newImage = isset($_POST['existing_img']) ? $_POST['existing_img'] : '';

    if(isset($_POST['remove_img']) && $_POST['remove_img'] == 'yes'){
        $newImage = '';
    }

    if(isset($_FILES['post_img']) && $_FILES['post_img']['error']!== UPLOAD_ERR_NO_FILE) {
        $dest = "images/";
        $file_name = time() . '_' . basename($_FILES["post_img"]["name"]);
        $target = $dest . $file_name;

        if(move_uploaded_file($_FILES["post_img"]["tmp_name"], $target)){
            $newImage = $target;
        }
    }

    $q = "UPDATE posts SET title = '$newTitle', body = '$newBody', post_img = '$newImage', user_id = '$user_id', post_date = DATE(NOW()) WHERE post_id = '$post_id'";
    if (mysqli_query($con, $q)) {
        header("Location: community.php");
        exit();
    }
}
?>