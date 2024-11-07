<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Fetch assignments from the database
$sql = "SELECT * FROM assignments";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Assignment List</h2>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Class</th>
                    <th>Due Date</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($assignment = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $assignment['id']; ?></td>
                            <td><?php echo $assignment['title']; ?></td>
                            <td><?php echo $assignment['description']; ?></td>
                            <td><?php echo $assignment['class']; ?></td>
                            <td><?php echo $assignment['due_date']; ?></td>
                            <td>
                                <?php if (!empty($assignment['file_path'])): ?>
                                    <a href="<?php echo $assignment['file_path']; ?>" target="_blank" class="btn btn-info btn-sm">Download</a>
                                <?php else: ?>
                                    No file
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No assignments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>