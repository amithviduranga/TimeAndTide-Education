<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db_connect.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values for News Articles
$title = $content = $posted_date = "";
$title_err = $content_err = $posted_date_err = "";
$image_path_err = ""; // For news images
$success_msg = $error_msg = "";

// Set default posted date for news form
$default_posted_date = date('Y-m-d');

// Processing form data when form is submitted for adding a news article
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_news'])){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }

    // Validate content
    if(empty(trim($_POST["content"]))){
        $content_err = "Please enter content.";
    } else{
        $content = trim($_POST["content"]);
    }

    // Validate posted date
    if(empty(trim($_POST["posted_date"]))){
        $posted_date_err = "Please enter a date.";
    } else{
        $posted_date = trim($_POST["posted_date"]);
    }

    // Validate and upload images
    $news_image_paths = [];
    $upload_errors = [];

    if(isset($_FILES["images"]) && !empty(array_filter($_FILES['images']['name']))){
        $allowed = array("jpg", "jpeg", "gif", "png");
        
        foreach($_FILES['images']['name'] as $key=>$val){
            $filename = $_FILES["images"]["name"][$key];
            $filesize = $_FILES["images"]["size"][$key];
            $tmp_name = $_FILES["images"]["tmp_name"][$key];

            $current_image_error = "";

            // Verify file extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if(!in_array($ext, $allowed)) {
                $current_image_error = "Invalid file format for " . $filename . ".";
            }
        
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize) {
                $current_image_error = "File " . $filename . " is larger than the allowed limit.";
            }
            
            if(empty($current_image_error)){ // Only proceed if no errors so far for this file
                $new_filename = uniqid() . "-" . $filename;
                if(move_uploaded_file($tmp_name, "../assets/images/" . $new_filename)){
                    $news_image_paths[] = "assets/images/" . $new_filename;
                } else {
                    $current_image_error = "Problem uploading " . $filename . ".";
                }
            }
            
            if(!empty($current_image_error)){
                $upload_errors[] = $current_image_error;
            }
        }
    } else {
        $upload_errors[] = "Please select at least one image.";
    }

    if(!empty($upload_errors)){
        $image_path_err = implode("<br>", $upload_errors);
    }

    // Check input errors before inserting in database
    if(empty($title_err) && empty($content_err) && empty($posted_date_err) && empty($image_path_err)){
        
        $sql = "INSERT INTO news (title, content, posted_date, image_paths, image_path) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssss", $param_title, $param_content, $param_posted_date, $param_image_paths, $param_image_path);
            
            $param_title = $title;
            $param_content = $content;
            $param_posted_date = $posted_date;
            $param_image_paths = json_encode($news_image_paths);
            $param_image_path = $news_image_paths[0] ?? ""; // Use the first uploaded image as the main image_path
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "News article added successfully.";
                header("location: manage-news.php"); // Redirect to this page
                exit();
            } else{
                $error_msg = "Database error (News Article): " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please fix the validation errors for the news article and try again: " . $title_err . " " . $content_err . " " . $posted_date_err . " " . $image_path_err;
    }
}

if(isset($_SESSION['success_msg'])){
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage News Articles</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .image-preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .image-preview { position: relative; width: 150px; height: 150px; }
        .image-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 5px; }
        .remove-image { position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; cursor: pointer; width: 25px; height: 25px; font-size: 16px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Manage News Articles</h1>
            <div>
                <a href="dashboard.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success" style="padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">' . $success_msg . '</div>';
        }        
        if(!empty($error_msg)){
            echo '<div class="alert alert-danger" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">' . $error_msg . '</div>';
        }        
        ?>

        <div class="form-container">
            <h2>Add New News Article</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="add_news" value="1">
                <div class="form-group <?php echo (!empty($title_err)) ? 'has-error' : ''; ?>">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $title; ?>">
                    <span class="help-block"><?php echo $title_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($content_err)) ? 'has-error' : ''; ?>">
                    <label>Content</label>
                    <textarea name="content" class="form-control"><?php echo $content; ?></textarea>
                    <span class="help-block"><?php echo $content_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($posted_date_err)) ? 'has-error' : ''; ?>">
                    <label>Posted Date</label>
                    <input type="date" name="posted_date" class="form-control" value="<?php echo $default_posted_date; ?>">
                    <span class="help-block"><?php echo $posted_date_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($image_path_err)) ? 'has-error' : ''; ?>">
                    <label>Images</label>
                    <input type="file" id="images" name="images[]" class="form-control" multiple>
                    <div class="image-preview-container" id="image-preview-container"></div>
                    <span class="help-block"><?php echo $image_path_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Add News">
                </div>
            </form>
        </div>

        <hr>

        <h2>Existing News Articles</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Posted Date</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM news";
                if($result = mysqli_query($link, $sql)){
                    if(mysqli_num_rows($result) > 0){
                        while($row = mysqli_fetch_array($result)){
                            echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . $row['title'] . "</td>";
                                echo "<td>" . substr($row['content'], 0, 100) . "...</td>";
                                echo "<td>" . $row['posted_date'] . "</td>";
                                echo "<td>";
                                $images = json_decode($row['image_paths']);
                                if(is_array($images)){
                                    foreach($images as $image){
                                        echo "<img src='../" . $image . "' width='100' style='margin-right: 5px;'>";
                                    }
                                }
                                echo "</td>";
                                echo "<td>";
                                    echo "<a href='edit-news.php?id=". $row['id'] ."' class='btn btn-primary'>Update</a>";
                                    echo "<a href='delete-news.php?id=". $row['id'] ."' class='btn btn-danger'>Delete</a>";
                                echo "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result);
                    } else{
                        echo "<tr><td colspan='6'>No news found.</td></tr>";
                    }
                } else{
                    echo "<tr><td colspan='6'>ERROR: Could not able to execute $sql. " . mysqli_error($link) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const imageInput = document.getElementById('images');
    const previewContainer = document.getElementById('image-preview-container');
    const dataTransfer = new DataTransfer();

    imageInput.addEventListener('change', function () {
        for (const file of this.files) {
            dataTransfer.items.add(file);
            
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'image-preview';

                const img = document.createElement('img');
                img.src = e.target.result;

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-image';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', function () {
                    // Find and remove the file from the DataTransfer object
                    for (let i = 0; i < dataTransfer.items.length; i++) {
                        if (dataTransfer.items[i].getAsFile().name === file.name) {
                            dataTransfer.items.remove(i);
                            break;
                        }
                    }
                    // Update the file input's files
                    imageInput.files = dataTransfer.files;
                    // Remove the preview
                    previewWrapper.remove();
                });

                previewWrapper.appendChild(img);
                previewWrapper.appendChild(removeBtn);
                previewContainer.appendChild(previewWrapper);
            };
            reader.readAsDataURL(file);
        }
        // Update the file input's files with the combined list
        imageInput.files = dataTransfer.files;
    });
});
</script>
</body>
</html>