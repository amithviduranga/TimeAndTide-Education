<?php
session_start();
require_once "db_connect.php";

// Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit;
}

// Validate ID
if (isset($_GET["id"]) && ctype_digit($_GET["id"])) {
    $id = (int) $_GET["id"];

    // First, get associated images
    $sql_select = "SELECT image_paths FROM news WHERE id = ?";
    if ($stmt_select = mysqli_prepare($link, $sql_select)) {
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        $result = mysqli_stmt_get_result($stmt_select);
        if ($row = mysqli_fetch_assoc($result)) {
            $images = json_decode($row['image_paths'], true) ?: [];
            // Delete each image file
            foreach ($images as $img) {
                $filePath = "../" . $img;
                if (file_exists($filePath)) {
                    @unlink($filePath); // @ to suppress errors if file doesn't exist
                }
            }
        }
        mysqli_stmt_close($stmt_select);
    }

    // Delete the news record
    $sql_delete = "DELETE FROM news WHERE id = ?";
    if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['success_msg'] = "News article deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to delete news article. Please try again.";
        }
        mysqli_stmt_close($stmt_delete);
    }

    mysqli_close($link);
    header("Location: dashboard.php");
    exit;

} else {
    // Invalid or missing ID
    $_SESSION['error_msg'] = "Invalid request.";
    header("Location: dashboard.php");
    exit;
}
?>
