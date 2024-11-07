<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $class = $_POST['class'];
    $due_date = $_POST['due_date'];

    // File upload handling
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["assignment_file"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a valid type
    $allowedTypes = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    if (!in_array($fileType, $allowedTypes)) {
        echo "Sorry, only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1 && move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO assignments (title, description, class, due_date, file_path) 
                VALUES ('$title', '$description', '$class', '$due_date', '$target_file')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Assignment added successfully.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Assignment</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Add Assignment</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="class" class="form-label">Class</label>
                <input type="text" name="class" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="assignment_file" class="form-label">Upload Assignment File</label>
                <input type="file" name="assignment_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Assignment

</button>
        </form>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>
