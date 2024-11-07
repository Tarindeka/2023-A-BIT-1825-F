<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Fetch announcements from the database
$sql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"; // Show the latest 5 announcements
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</h2>

        <!-- Links to Other Pages -->
        <div class="mb-4">
            <?php if ($user['role'] === 'admin' || $user['role'] === 'teacher'): ?>
                <a href="student_list.php" class="btn btn-secondary">View Students</a>
                <a href="add_assignment.php" class="btn btn-success">Add Assignment</a>
            <?php endif; ?>
            <a href="assignment_list.php" class="btn btn-info">View Assignments</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <!-- Announcements Section -->
        <h3 class="mt-4">Latest Announcements</h3>
        <?php if ($result->num_rows > 0): ?>
            <ul class="list-group">
                <?php while($announcement = $result->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                        <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                        <small class="text-muted">Posted on: <?php echo $announcement['created_at']; ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No announcements available.</p>
        <?php endif; ?>

        <!-- Add Announcement (Visible to Admins and Teachers) -->
        <?php if ($user['role'] === 'admin' || $user['role'] === 'teacher'): ?>
            <a href="add_announcement.php" class="btn btn-primary mt-3">Add Announcement</a>
        <?php endif; ?>
    </div>
    <script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
