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

if (!isset($_GET['post_id']) || !ctype_digit($_GET['post_id'])) {
    die('Invalid post id.');
}

$postId = (int) $_GET['post_id'];

mysqli_begin_transaction($con);

try {
    // 1) Optional: fetch image path before deleting post
    $photoPath = null;
    $stmt = mysqli_prepare($con, "SELECT photo FROM posts WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $postId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $photoPath = $row['photo']; // change column name if yours is different
    }
    mysqli_stmt_close($stmt);

    // 2) Delete child rows first (required because of FK)
    $stmt = mysqli_prepare($con, "DELETE FROM comments WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $postId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // If you have other child tables (likes, tags, etc.), delete them here too.

    // 3) Delete parent row
    $stmt = mysqli_prepare($con, "DELETE FROM posts WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $postId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) < 1) {
        throw new Exception('Post not found or already deleted.');
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($con);

    // 4) Remove image file from disk after successful DB commit
    if (!empty($photoPath)) {
        $fullPath = __DIR__ . '/' . ltrim($photoPath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    header('Location: community.php?deleted=1');
    exit;
} catch (Throwable $e) {
    mysqli_rollback($con);
    die('Delete failed: ' . $e->getMessage());
}
?>