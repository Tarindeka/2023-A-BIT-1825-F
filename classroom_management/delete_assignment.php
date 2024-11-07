<?php
include 'db.php';
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Get the file path before deleting the record
    $sql = "SELECT file_path FROM assignments WHERE id = '$id'";
    $result = $conn->query($sql);
    $file_path = $result->fetch_assoc()['file_path'];

    // Delete the assignment record
    $sql = "DELETE FROM assignments WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        // Delete the file from the server
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
        header("Location: assignment_list.php");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: assignment_list.php");
}
?>
