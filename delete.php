<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_connection.php');

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $post_id = mysqli_real_escape_string($con, ($_POST['post_id']));

    $image_query = "SELECT post_img FROM posts WHERE post_id = '$post_id'";
    $result = mysqli_query($con, $image_query);
    $postData = mysqli_fetch_assoc($result);

    $q = "DELETE FROM posts WHERE post_id = '$post_id'";
    if (mysqli_query($con, $q)) {
        if(!empty($postData['post_img']) && file_exists($postData['post_img'])) {
            unlink($postData[$post_img]);
        }

        header("Location: community.php");
        exit();
    }
}
?>