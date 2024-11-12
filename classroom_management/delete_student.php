<?php
include 'db.php';
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM students WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        header("Location: student_list.php");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: student_list.php");
}
?>
