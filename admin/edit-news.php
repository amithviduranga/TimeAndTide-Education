<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Variables
$title = $content = $posted_date = "";
$title_err = $content_err = $posted_date_err = "";
$success_msg = $error_msg = "";
$image_paths = [];

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && !empty($_POST['id'])){
    $id = intval($_POST['id']);

    // Validate title
    $title = trim($_POST["title"]);
    if(empty($title)) $title_err = "Please enter a title.";

    // Validate content
    $content = trim($_POST["content"]);
    if(empty($content)) $content_err = "Please enter content.";

    // Validate date
    $posted_date = trim($_POST["posted_date"]);
    if(empty($posted_date)) $posted_date_err = "Please enter a date.";

    // Get existing images from DB
    $sql_get_images = "SELECT image_paths FROM news WHERE id = ?";
    if($stmt_get = mysqli_prepare($link, $sql_get_images)){
        mysqli_stmt_bind_param($stmt_get, "i", $id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        if($row_get = mysqli_fetch_assoc($result_get)){
            $image_paths = json_decode($row_get['image_paths'], true) ?: [];
        }
        mysqli_stmt_close($stmt_get);
    }

    // Process deletions
    if(!empty($_POST['deleted_images'])){
        foreach($_POST['deleted_images'] as $del_img){
            if(($key = array_search($del_img, $image_paths)) !== false){
                unset($image_paths[$key]);
                if(file_exists("../".$del_img)){
                    if(!unlink("../".$del_img)){
                        $error_msg .= "Failed to delete ".htmlspecialchars($del_img)."<br>";
                    }
                }
            }
        }
    }

    // Process new uploads
    if(isset($_FILES["new_images"]) && !empty(array_filter($_FILES['new_images']['name']))){
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];

        foreach($_FILES['new_images']['name'] as $key=>$filename){
            $tmp_name = $_FILES["new_images"]["tmp_name"][$key];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp_name);

            if(!array_key_exists($ext, $allowed_mimes) || $allowed_mimes[$ext] !== $mime){
                $error_msg .= "Invalid file type for ".htmlspecialchars($filename)."<br>";
                continue;
            }

            if($_FILES['new_images']['size'][$key] > 5*1024*1024){
                $error_msg .= "File too large: ".htmlspecialchars($filename)."<br>";
                continue;
            }

            $safeName = preg_replace("/[^A-Z0-9._-]/i", "-", pathinfo($filename, PATHINFO_FILENAME));
            $new_filename = uniqid('img_', true)."-".$safeName.".".$ext;

            if(move_uploaded_file($tmp_name, "../assets/images/".$new_filename)){
                $image_paths[] = "assets/images/".$new_filename;
            } else {
                $error_msg .= "Failed to upload ".htmlspecialchars($filename)."<br>";
            }
        }
    }

    // Update DB if no errors
    if(empty($title_err) && empty($content_err) && empty($posted_date_err) && empty($error_msg)){
        $sql = "UPDATE news SET title=?, content=?, posted_date=?, image_paths=? WHERE id=?";
        if($stmt = mysqli_prepare($link, $sql)){
            $json_paths = json_encode(array_values($image_paths));
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $content, $posted_date, $json_paths, $id);
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "News article updated successfully.";
                header("location: manage-news.php");
                exit;
            } else {
                $error_msg = "DB error: ".mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Validation or upload errors found.";
    }
} else {
    // Load article for editing
    if(isset($_GET['id']) && !empty($_GET['id'])){
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM news WHERE id=?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                $title = $row['title'];
                $content = $row['content'];
                $posted_date = $row['posted_date'];
                $image_paths = json_decode($row['image_paths'], true) ?: [];
            } else {
                $error_msg = "No article found for ID: ".$id;
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        header("location: manage-news.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit News Article</title>
<link rel="stylesheet" href="admin-style.css">
<style>
.image-preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.image-preview, .new-image-preview { position: relative; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
.image-preview img, .new-image-preview img { width: 100%; height: 100%; object-fit: cover; }
.remove-image-btn, .remove-new-image-btn { position: absolute; top:0; right:0; background: rgba(255,0,0,0.7); color:#fff; border:none; border-radius:0 0 0 5px; width:25px; height:25px; text-align:center; line-height:25px; cursor:pointer; }
</style>
</head>
<body>
<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Edit News Article</h1>
        <a href="manage-news.php" class="btn">Back to News</a>
    </div>

    <div class="form-container">
        <?php if(!empty($error_msg)) echo '<div class="alert alert-danger">'.$error_msg.'</div>'; ?>
        <?php if(isset($_SESSION['success_msg'])){ echo '<div class="alert alert-success">'.$_SESSION['success_msg'].'</div>'; unset($_SESSION['success_msg']); } ?>

        <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>

            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>"></div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control"><?php echo htmlspecialchars($content); ?></textarea></div>
            <div class="form-group"><label>Posted Date</label><input type="date" name="posted_date" class="form-control" value="<?php echo $posted_date; ?>"></div>

            <div class="form-group">
                <label>Current Images</label>
                <div class="image-preview-container" id="current-image-preview-container">
                    <?php foreach($image_paths as $img): ?>
                        <div class="image-preview" data-image-path="<?php echo htmlspecialchars($img); ?>">
                            <img src="../<?php echo htmlspecialchars($img); ?>">
                            <button type="button" class="remove-image-btn">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Add New Images</label>
                <input type="file" id="new_images" name="new_images[]" class="form-control" multiple>
                <div class="image-preview-container" id="new-image-preview-container"></div>
            </div>

            <div id="deleted-images-hidden-inputs"></div>
            <div class="form-group"><input type="submit" class="btn btn-primary" value="Update News"></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const newImageInput = document.getElementById('new_images');
    const newPreviewContainer = document.getElementById('new-image-preview-container');
    const currentContainer = document.getElementById('current-image-preview-container');
    const deletedInputs = document.getElementById('deleted-images-hidden-inputs');
    const dtNewFiles = new DataTransfer();

    // Remove existing images
    currentContainer.addEventListener('click', function(e){
        if(e.target.classList.contains('remove-image-btn')){
            const wrapper = e.target.closest('.image-preview');
            const path = wrapper.dataset.imagePath;
            const hidden = document.createElement('input');
            hidden.type='hidden'; hidden.name='deleted_images[]'; hidden.value=path;
            deletedInputs.appendChild(hidden);
            wrapper.remove();
        }
    });

    // Preview new images
    newImageInput.addEventListener('change', function(){
        for(const file of this.files){
            dtNewFiles.items.add(file);
            const reader = new FileReader();
            reader.onload = function(e){
                const wrapper = document.createElement('div'); wrapper.className='new-image-preview';
                const img = document.createElement('img'); img.src=e.target.result;
                const btn = document.createElement('button'); btn.className='remove-new-image-btn'; btn.innerHTML='&times;';
                btn.addEventListener('click', function(){
                    for(let i=0;i<dtNewFiles.items.length;i++){
                        if(dtNewFiles.items[i].getAsFile().name===file.name){ dtNewFiles.items.remove(i); break; }
                    }
                    newImageInput.files = dtNewFiles.files;
                    wrapper.remove();
                });
                wrapper.appendChild(img); wrapper.appendChild(btn);
                newPreviewContainer.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }
        newImageInput.files = dtNewFiles.files;
    });
});
</script>
</body>
</html>
