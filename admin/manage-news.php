<?php
// manage-news.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db_connect.php"; // must create $link (mysqli)

// Redirect if not logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

/* -------------------------
   Initialize variables
   ------------------------- */
$title = $content = $posted_date = "";
$title_err = $content_err = $posted_date_err = "";
$image_path_err = "";
$success_msg = $error_msg = "";

$default_posted_date = date('Y-m-d');

/* -------------------------
   Handle form submission
   ------------------------- */
if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_news'])){

    // Trim inputs
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $posted_date = trim($_POST['posted_date'] ?? '');

    // Validate
    if($title === '') $title_err = "Please enter a title.";
    if($content === '') $content_err = "Please enter content.";
    if($posted_date === '') $posted_date_err = "Please enter a date.";

    // Image upload handling
    $news_image_paths = [];
    $upload_errors = [];
    $upload_dir = __DIR__ . "/../assets/images/"; // filesystem path
    $upload_dir_rel = "assets/images/"; // path stored in DB (relative to project root used elsewhere)

    if(!is_dir($upload_dir)){
        if(!mkdir($upload_dir, 0755, true)){
            $upload_errors[] = "Unable to create image upload directory.";
        }
    }

    // Allowed MIME types & extensions
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];

    if(isset($_FILES['images']) && !empty(array_filter($_FILES['images']['name']))){
        // Fallback for servers without fileinfo extension
        $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;

        foreach($_FILES['images']['name'] as $key => $originalName){
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $filesize = $_FILES['images']['size'][$key];
            $error = $_FILES['images']['error'][$key];

            if($error !== UPLOAD_ERR_OK){
                $upload_errors[] = "Error uploading file: " . htmlspecialchars($originalName);
                continue;
            }

            // Validate size (5MB)
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize){
                $upload_errors[] = "File " . htmlspecialchars($originalName) . " is larger than allowed (5MB).";
                continue;
            }

            // Validate MIME using finfo or fallback to extension check
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            if($finfo) {
                // Use finfo when available
                $mimeType = $finfo->file($tmp_name);
                if(!in_array($mimeType, $allowed_mimes) || !array_key_exists($ext, $allowed_mimes)){
                    $upload_errors[] = "Invalid file type for " . htmlspecialchars($originalName) . ".";
                    continue;
                }
            } else {
                // Fallback: just check file extension when finfo is not available
                if(!array_key_exists($ext, $allowed_mimes)){
                    $upload_errors[] = "Invalid file type for " . htmlspecialchars($originalName) . ".";
                    continue;
                }
            }

            // Sanitize original name and build unique filename
            $safeBase = preg_replace("/[^A-Z0-9._-]/i", "-", pathinfo($originalName, PATHINFO_FILENAME));
            $newFilename = uniqid('img_', true) . "-" . $safeBase . "." . $ext;
            $destination = $upload_dir . $newFilename;

            if(!move_uploaded_file($tmp_name, $destination)){
                $upload_errors[] = "Failed to move uploaded file " . htmlspecialchars($originalName) . ".";
                continue;
            }

            // File saved successfully; add relative path for DB
            $news_image_paths[] = $upload_dir_rel . $newFilename;
        }
    } else {
        $upload_errors[] = "Please select at least one image.";
    }

    if(!empty($upload_errors)){
        $image_path_err = implode("<br>", $upload_errors);
    }

    // Only insert when no validation errors
    if(empty($title_err) && empty($content_err) && empty($posted_date_err) && empty($image_path_err)){
        $sql = "INSERT INTO news (title, content, posted_date, image_paths, image_path) VALUES (?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            $param_title = $title;
            $param_content = $content;
            $param_posted_date = $posted_date;
            $param_image_paths = json_encode($news_image_paths);
            $param_image_path = $news_image_paths[0] ?? "";

            mysqli_stmt_bind_param($stmt, "sssss", $param_title, $param_content, $param_posted_date, $param_image_paths, $param_image_path);

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "News article added successfully.";
                mysqli_stmt_close($stmt);
                header("location: manage-news.php");
                exit();
            } else {
                $error_msg = "Database error (News Article): " . htmlspecialchars(mysqli_error($link));
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Database prepare failed: " . htmlspecialchars(mysqli_error($link));
        }
    } else {
        $error_msg = "Please fix the validation errors and try again.";
    }
}

// Show session success message (if any)
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Minimal admin styles (you can keep your admin-style.css or merge these) -->
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Small admin styles to ensure consistent preview + table thumbnails */
        .admin-wrapper { max-width: 1100px; margin: 30px auto; padding: 20px; font-family: Arial, sans-serif; }
        .admin-header { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:20px; }
        .admin-header h1 { margin:0; font-size:1.5rem; color:#222; }
        .btn { display:inline-block; padding:10px 14px; border-radius:6px; background:#2563eb; color:#fff; text-decoration:none; }
        .btn.btn-danger { background:#ef4444; }
        .form-container { background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); margin-bottom:30px; }
        .form-group { margin-bottom:15px; }
        label { display:block; margin-bottom:6px; color:#333; font-weight:600; }
        input[type="text"], input[type="date"], textarea, input[type="file"] { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; }
        textarea { min-height:120px; resize:vertical; }
        .help-block { color:#d9534f; margin-top:6px; display:block; font-size:0.95rem; }
        .image-preview-container { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
        .image-preview { position:relative; width:140px; height:140px; border-radius:6px; overflow:hidden; background:#f5f5f5; display:flex; align-items:center; justify-content:center; }
        .image-preview img { width:100%; height:100%; object-fit:cover; display:block; }
        .remove-image { position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.6); color:#fff; border:none; width:26px; height:26px; border-radius:50%; cursor:pointer; font-size:16px; }
        .alert { padding:12px 14px; border-radius:6px; margin-bottom:18px; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        table.table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.04); }
        table.table th, table.table td { padding:12px 14px; border-bottom:1px solid #f1f1f1; text-align:left; vertical-align:middle; }
        table.table th { background:#fafafa; font-weight:700; color:#333; }
        table.table img.thumb { width:100px; height:70px; object-fit:cover; border-radius:6px; margin-right:6px; }
        .actions .btn { margin-right:6px; }
        .has-error input, .has-error textarea { border-color:#dc3545; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Manage News Articles</h1>
            <div>
                <a href="dashboard.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2 style="margin-top:0;">Add New News Article</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="add_news" value="1">
                <div class="form-group <?php echo (!empty($title_err)) ? 'has-error' : ''; ?>">
                    <label for="title">Title</label>
                    <input id="title" type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">
                    <span class="help-block"><?php echo $title_err; ?></span>
                </div>

                <div class="form-group <?php echo (!empty($content_err)) ? 'has-error' : ''; ?>">
                    <label for="content">Content</label>
                    <textarea id="content" name="content"><?php echo htmlspecialchars($content); ?></textarea>
                    <span class="help-block"><?php echo $content_err; ?></span>
                </div>

                <div class="form-group <?php echo (!empty($posted_date_err)) ? 'has-error' : ''; ?>">
                    <label for="posted_date">Posted Date</label>
                    <input id="posted_date" type="date" name="posted_date" value="<?php echo htmlspecialchars($default_posted_date); ?>">
                    <span class="help-block"><?php echo $posted_date_err; ?></span>
                </div>

                <div class="form-group <?php echo (!empty($image_path_err)) ? 'has-error' : ''; ?>">
                    <label for="images">Images (max 5MB each)</label>
                    <input id="images" type="file" name="images[]" multiple accept="image/*">
                    <div class="image-preview-container" id="image-preview-container"></div>
                    <span class="help-block"><?php echo $image_path_err; ?></span>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add News</button>
                </div>
            </form>
        </div>

        <hr style="margin:30px 0; border:none; border-top:1px solid #eee;">

        <h2>Existing News Articles</h2>
        <table class="table" aria-describedby="Existing news articles">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th style="width:140px;">Posted Date</th>
                    <th style="width:240px;">Images</th>
                    <th style="width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM news ORDER BY posted_date DESC, id DESC";
                if($result = mysqli_query($link, $sql)){
                    if(mysqli_num_rows($result) > 0){
                        while($row = mysqli_fetch_array($result)){
                            echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                $excerpt = substr(strip_tags($row['content']), 0, 140);
                                if(strlen(strip_tags($row['content'])) > 140) $excerpt .= '...';
                                echo "<td>" . htmlspecialchars($excerpt) . "</td>";
                                echo "<td>" . htmlspecialchars($row['posted_date']) . "</td>";
                                echo "<td>";
                                    $images = json_decode($row['image_paths'], true);
                                    if(is_array($images)){
                                        foreach($images as $image){
                                            echo "<img class='thumb' src='../" . htmlspecialchars($image) . "' alt='thumb'>";
                                        }
                                    }
                                echo "</td>";
                                echo "<td class='actions'>";
                                    echo "<a href='edit-news.php?id=". urlencode($row['id']) ."' class='btn'>Update</a> ";
                                    echo "<a href='delete-news.php?id=". urlencode($row['id']) ."' class='btn btn-danger' onclick=\"return confirm('Delete this news article?');\">Delete</a>";
                                echo "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result);
                    } else {
                        echo "<tr><td colspan='6'>No news found.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>ERROR: Could not execute query: " . htmlspecialchars(mysqli_error($link)) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Preview + remove JS (robust handling) -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('image-preview-container');

        // We'll maintain an array of File objects to submit
        let selectedFiles = [];

        function renderPreviews() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'image-preview';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = file.name;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'remove-image';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function () {
                        selectedFiles.splice(idx, 1);
                        updateFileInput();
                        renderPreviews();
                    });

                    wrapper.appendChild(img);
                    wrapper.appendChild(btn);
                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        }

        function updateFileInput(){
            // Build a DataTransfer to update the native input.files
            const dt = new DataTransfer();
            selectedFiles.forEach(f => dt.items.add(f));
            imageInput.files = dt.files;
        }

        imageInput.addEventListener('change', function (e) {
            // Add new files to selectedFiles (avoid duplicates by name + size)
            const incoming = Array.from(e.target.files);
            incoming.forEach(file => {
                const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
                if(!exists) selectedFiles.push(file);
            });
            updateFileInput();
            renderPreviews();
        });
    });
    </script>
</body>
</html>
