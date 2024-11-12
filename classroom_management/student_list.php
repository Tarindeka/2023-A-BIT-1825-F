<?php
include 'db.php';
session_start();

// Check if the user is logged in and has the role of either "teacher" or "admin"
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch all students from the database
$sql = "SELECT id, name, email, class FROM students";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Classroom Management System - Student List</h2>
        
        <!-- Add Student Button for Teacher and Admin roles -->
        <a href="add_student.php" class="btn btn-success mb-3">Add Student</a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['class'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No students found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>
