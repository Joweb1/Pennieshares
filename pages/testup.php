<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
}
?>

<!DOCTYPE html>
<html>
<body>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" value="Upload">
    </form>
</body>
</html>