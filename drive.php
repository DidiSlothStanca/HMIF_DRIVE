<?php
session_start();
if(!isset($_SESSION['user'])) header("Location: index.php");

$user = $_SESSION['user'];
$user_dir = "data/" . $user;

// Buat folder otomatis jika belum ada
if (!file_exists($user_dir)) {
    mkdir($user_dir, 0777, true);
}

$files = array_diff(scandir($user_dir), array('.', '..'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Drive</title>
    <style>
        body { font-family: 'Open Sans', sans-serif; margin: 0; display: flex; }
        .sidebar { width: 250px; padding: 20px; border-right: 1px solid #eee; height: 100vh; }
        .main { flex: 1; padding: 20px; background: #fff; }
        .header { display: flex; justify-content: space-between; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px; margin-top: 20px; }
        .file-card { border: 1px solid #dadce0; padding: 15px; border-radius: 10px; text-align: center; transition: 0.3s; }
        .file-card:hover { background: #f1f3f4; }
        .btn-upload { background: white; border: 1px solid #dadce0; padding: 10px 20px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="https://www.gstatic.com/images/branding/product/2x/drive_2020q4_48dp.png" width="40">
        <h3 style="color: #5f6368">Drive</h3>
        <button class="btn-upload" onclick="document.getElementById('upload').click()">+ New</button>
        <form id="upload_form" action="upload.php" method="POST" enctype="multipart/form-data" style="display:none">
            <input type="file" id="upload" name="file" onchange="this.form.submit()">
        </form>
        <p style="margin-top:40px; color:#1a73e8; font-weight:bold">My Drive</p>
        <p><a href="logout.php" style="text-decoration:none; color:grey">Logout</a></p>
    </div>
    <div class="main">
        <div class="header">
            <input type="text" placeholder="Search in Drive" style="width: 60%; padding: 10px; border-radius: 8px; border: 1px solid #dfe1e5; background: #f1f3f4;">
            <div>User: <b><?php echo $user; ?></b></div>
        </div>
        
        <div class="file-grid">
            <?php foreach($files as $file): ?>
            <div class="file-card">
                <div style="font-size: 40px;">📄</div>
                <div style="font-size: 13px; margin-top: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?php echo $file; ?>
                </div>
                <a href="<?php echo $user_dir.'/'.$file; ?>" download style="font-size: 11px; color: #1a73e8;">Download</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
