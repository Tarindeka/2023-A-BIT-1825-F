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

// Admin functionality: Fetch all users (excluding the admin)
if ($user['role'] === 'admin') {
    $userSql = "SELECT id, username, role FROM users WHERE id != ?"; // Exclude the admin themselves
    $stmt = $conn->prepare($userSql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $usersResult = $stmt->get_result();
}

// Handle user deletion (if admin)
if (isset($_GET['delete']) && $user['role'] === 'admin') {
    $userIdToDelete = $_GET['delete'];

    // Delete the user from the database
    $deleteSql = "DELETE FROM users WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $userIdToDelete);
    if ($deleteStmt->execute()) {
        header("Location: dashboard.php"); // Refresh the page after deletion
        exit;
    } else {
        echo "Error deleting user: " . $conn->error;
    }
}

// Send message to user (if admin)
if (isset($_POST['send_message']) && $user['role'] === 'admin') {
    $recipientId = $_POST['recipient_id']; // Get the recipient ID
    $message = $_POST['message'];

    // Insert the message into the messages table
    $messageSql = "INSERT INTO messages (user_id, recipient_id, message) VALUES (?, ?, ?)";
    $messageStmt = $conn->prepare($messageSql);
    $messageStmt->bind_param("iis", $user['id'], $recipientId, $message); // Bind user_id, recipient_id, and message
    if ($messageStmt->execute()) {
        echo "<p class='alert alert-success'>Message sent successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Error sending message: " . $conn->error . "</p>";
    }
}

// Fetch messages for the logged-in user, including sender's role and handling deleted messages
$messageSql = "
    SELECT m.*, u.role AS sender_role, u.username AS sender_username
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE (m.recipient_id = ? AND m.deleted_by_username IS NULL) OR (m.user_id = ? AND m.deleted_by_username IS NULL)
    ORDER BY m.created_at DESC
";
$stmt = $conn->prepare($messageSql);
$stmt->bind_param("ii", $user['id'], $user['id']);  // Now both the recipient_id and user_id are used
$stmt->execute();
$messageResult = $stmt->get_result();

// Handle message deletion (mark as deleted by username)
if (isset($_GET['delete_message']) && isset($_GET['message_id'])) {
    $messageId = $_GET['message_id'];

    // Mark the message as deleted for the user
    $deleteMessageSql = "UPDATE messages SET deleted_by_username = ? WHERE id = ? AND deleted_by_username IS NULL";
    $deleteMessageStmt = $conn->prepare($deleteMessageSql);
    $deleteMessageStmt->bind_param("si", $user['username'], $messageId);
    if ($deleteMessageStmt->execute()) {
        header("Location: dashboard.php"); // Refresh the page after deletion
        exit;
    } else {
        echo "Error deleting message: " . $conn->error;
    }
}

// Handle message reply
if (isset($_POST['reply_message']) && isset($_POST['parent_message_id'])) {
    $parentMessageId = $_POST['parent_message_id'];
    $message = $_POST['message'];

    // Prevent replying to your own message
    $checkSenderSql = "SELECT user_id FROM messages WHERE id = ?";
    $checkSenderStmt = $conn->prepare($checkSenderSql);
    $checkSenderStmt->bind_param("i", $parentMessageId);
    $checkSenderStmt->execute();
    $checkSenderResult = $checkSenderStmt->get_result();

    if ($checkSenderResult->num_rows > 0) {
        $senderRow = $checkSenderResult->fetch_assoc();
        $senderId = $senderRow['user_id'];

        // Only allow replying if the current user is not the sender
        if ($senderId != $user['id']) {
            // Get the recipient of the original message (so the reply is sent to the correct recipient)
            $getRecipientSql = "SELECT recipient_id FROM messages WHERE id = ?";
            $getRecipientStmt = $conn->prepare($getRecipientSql);
            $getRecipientStmt->bind_param("i", $parentMessageId);
            $getRecipientStmt->execute();
            $recipientResult = $getRecipientStmt->get_result();
            
            if ($recipientResult->num_rows > 0) {
                $recipientRow = $recipientResult->fetch_assoc();
                $recipientId = $recipientRow['recipient_id'];

                // Insert the reply into the messages table
                $replySql = "INSERT INTO messages (user_id, message, parent_message_id, recipient_id) VALUES (?, ?, ?, ?)";
                $replyStmt = $conn->prepare($replySql);
                $replyStmt->bind_param("isis", $user['id'], $message, $parentMessageId, $recipientId);  // Use the correct recipient_id
                if ($replyStmt->execute()) {
                    echo "<p class='alert alert-success'>Reply sent successfully!</p>";
                } else {
                    echo "<p class='alert alert-danger'>Error replying: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='alert alert-danger'>Error: Parent message not found.</p>";
            }
        } else {
            echo "<p class='alert alert-warning'>You cannot reply to your own message.</p>";
        }
    }
}
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

        <!-- Send Message Form (Admin Only) -->
        <?php if ($user['role'] === 'admin'): ?>
            <h3 class="mt-4">Send Message</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="recipient_id">Recipient:</label>
                    <select name="recipient_id" class="form-control" required>
                        <?php while ($recipient = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $recipient['id']; ?>"><?php echo htmlspecialchars($recipient['username']); ?> (<?php echo htmlspecialchars($recipient['role']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary mt-2">Send Message</button>
            </form>
        <?php endif; ?>

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
            <p>No announcements found.</p>
        <?php endif; ?>

        <!-- Messages Section -->
        <h3 class="mt-4">Your Messages</h3>
        <?php if ($messageResult->num_rows > 0): ?>
            <ul class="list-group">
                <?php while($message = $messageResult->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <p><strong>From: <?php echo htmlspecialchars($message['sender_username']); ?> (<?php echo htmlspecialchars($message['sender_role']); ?>)</strong></p>
                        <p><?php echo htmlspecialchars($message['message']); ?></p>
                        <small class="text-muted">Sent on: <?php echo $message['created_at']; ?></small>

                        <!-- Delete message button -->
                        <a href="?delete_message=1&message_id=<?php echo $message['id']; ?>" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Are you sure you want to delete this message on your end?');">Delete</a>

                        <!-- Display replies for this message -->
                        <?php
                        $replySql = "SELECT m.*, u.username AS sender_username, u.role AS sender_role FROM messages m JOIN users u ON m.user_id = u.id WHERE parent_message_id = ? ORDER BY created_at ASC";
                        $replyStmt = $conn->prepare($replySql);
                        $replyStmt->bind_param("i", $message['id']);
                        $replyStmt->execute();
                        $replyResult = $replyStmt->get_result();

                        if ($replyResult->num_rows > 0): ?>
                            <ul class="list-group mt-3">
                                <?php while($reply = $replyResult->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <p><strong>From: <?php echo htmlspecialchars($reply['sender_username']); ?> (<?php echo htmlspecialchars($reply['sender_role']); ?>)</strong></p>
                                        <p><?php echo htmlspecialchars($reply['message']); ?></p>
                                        <small class="text-muted">Replied on: <?php echo $reply['created_at']; ?></small>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Reply Form -->
                        <?php if ($message['user_id'] != $user['id']): ?> <!-- Only allow reply if it's not your own message -->
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="parent_message_id" value="<?php echo $message['id']; ?>">
                            <textarea name="message" class="form-control" required></textarea>
                            <button type="submit" name="reply_message" class="btn btn-primary mt-2">Reply</button>
                        </form>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No messages found.</p>
        <?php endif; ?>
    </div>

    <script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
