<?php
session_start();

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } else {
        $bytes = $bytes . ' bytes';
    }
    return $bytes;
}

$disk_path = "."; 
$free_disk = disk_free_space($disk_path);
$total_disk = disk_total_space($disk_path);
$used_disk = $total_disk - $free_disk;
$used_percent = ($used_disk / $total_disk) * 100;

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?error=access_denied");
    exit();
}
require_once('config/db.php');

$user = $_SESSION['username'];
$role = $_SESSION['role'];

$status_msg = "";
$is_error = false;

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'error' && isset($_GET['types'])) {
        $is_error = true;
        $types = explode(",", $_GET['types']);
        $msgs = [];
        foreach ($types as $type) {
            if ($type == 'duplicate_user') $msgs[] = "⚠️ User sudah ada!";
            if ($type == 'format_user') $msgs[] = "⚠️ Username hanya boleh huruf kecil & angka!";
            if ($type == 'format_password') $msgs[] = "⚠️ Password: Min 8 karakter, ada huruf Besar, Kecil, & Angka!";
        }
        $status_msg = implode("<br>", $msgs);
    } else {
        switch ($_GET['status']) {
            case 'reset_success': $status_msg = "✅ Password berhasil diperbarui."; break;
            case 'add_success': $status_msg = "✅ Member baru ditambahkan."; break;
            case 'delete_success': $status_msg = "✅ Member berhasil dihapus."; break;
        }
    }
}

$query = "SELECT id, username, role FROM users WHERE username != ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Member - HMIF Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-wrapper">

<div class="sidebar">
	<nav style="display: flex; flex-direction: column; gap: 2px;">
        <a href="dashboard.php" class="menu-item active">🗂️ Drive Saya</a>
    </nav>
    <div style="display:flex; align-items:center; gap:10px; padding-left: 10px; height: 100%;">
       <img src="assets/logo/himp.png" width="35" alt="Logo" style="display: block;">
       <span style="font-size:18px; color:var(--primary-blue); font-weight: bold;" class="hide-mobile">HMIF Drive</span>
    </div>
    <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-color);">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
        <span style="font-size: 16px;">☁️</span>
        <span style="font-size: 13px; font-weight: 600; color: var(--text-color);">Penyimpanan Server</span>
    </div>
    
    <div style="background: var(--border-color); border-radius: 10px; height: 6px; width: 100%; overflow: hidden;">
        <div style="background: var(--primary-blue); height: 100%; width: <?php echo $used_percent; ?>%; transition: width 0.8s ease-in-out;"></div>
    </div>
    
    <p style="font-size: 11px; margin-top: 8px; color: gray;">
        Terpakai <?php echo formatSizeUnits($used_disk); ?> dari <?php echo formatSizeUnits($total_disk); ?>
    </p>

    <?php if ($used_percent > 85): ?>
        <p style="font-size: 10px; color: #ea4335; margin-top: 5px; font-weight: bold;">⚠️ Kapasitas hampir habis!</p>
    <?php endif; ?>
</div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h3 style="margin: 0; font-size: 18px;">Manajemen Member</h3>
        
        <div class="user-menu">
            <div class="user-trigger" onclick="toggleDropdown()">
                <div style="background: var(--primary-blue); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    <?php echo strtoupper(substr($user, 0, 1)); ?>
                </div>
                <span style="font-size: 14px; font-weight: 500;" class="hide-mobile"><?php echo $user; ?></span>
            </div>
            <div id="userDropdown" class="dropdown-content">
                <button class="dropdown-item" id="themeToggle">
                    <span id="themeIcon">🌓</span> <span id="themeText">Mode Terang</span>
                </button>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item" style="color: #ea4335;">🚪 Keluar</a>
            </div>
        </div>
    </div>

    <div class="admin-section">
        <?php if ($status_msg): ?>
            <div id="statusAlert" style="padding: 15px; margin-bottom: 20px; border-radius: 6px; background: var(--card-bg); border-left: 4px solid <?php echo $is_error ? '#ea4335' : 'var(--primary-blue)'; ?>; font-size: 14px; transition: opacity 0.5s ease; color: var(--text-color);">
                <?php echo $status_msg; ?>
            </div>
        <?php endif; ?>

        <div class="card-form">
    <h4 style="margin-top:0; margin-bottom:15px;">Tambah Member Baru</h4>
    <form action="includes/admin_proc.php" method="POST" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start;">
		<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
		<input type="text" name="new_user" placeholder="Username (huruf kecil, no spasi)" required 
			   pattern="[a-z0-9]+" 
			   title="Username hanya boleh huruf kecil dan angka, tanpa spasi"
			   style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-color);">
        
        <div style="flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 5px;">
            <input type="password" name="new_pass" id="addPassInput" placeholder="Password" required 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                   title="Minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan angka"
                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-color);">
            
            <label style="display: flex; align-items: center; gap: 5px; font-size: 11px; cursor: pointer; color: var(--text-color);">
                <input type="checkbox" onclick="toggleAddPassVisibility()"> Lihat
            </label>
        </div>
        
        <select name="new_role" style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-color);">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        
        <button type="submit" name="add_member" class="btn-new" style="width: auto; padding: 0 25px; margin-bottom:0; height: 38px;">Tambah</button>
    </form>
    
		<div class="note-alert2">
			<span class="note-icon">💡</span>
			<div class="note-content">
				<strong>Note:</strong><br>
				1. Username wajib huruf kecil tanpa spasi (a-z, 0-9).<br>
				2. Password minimal 8 karakter, wajib kombinasi Huruf Besar, Kecil, dan Angka.
			</div>
		</div>
		
	</div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="Username"><b><?php echo $row['username']; ?></b></td>
                            <td data-label="Role">
                                <span class="badge-role <?php echo ($row['role'] == 'admin') ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo $row['role']; ?>
                                </span>
                            </td>
                            <td data-label="Aksi">
								<button class="btn-action" 
										style="background: var(--primary-blue); color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-size: 12px;"
										onclick="openResetModal('<?php echo $row['id']; ?>', '<?php echo $row['username']; ?>')">
									Sandi
								</button>

								<form action="includes/admin_proc.php" method="POST" style="display: inline;" onsubmit="return confirm('Hapus user ini?')">
									<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
									<input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">									
									<button type="submit" name="confirm_delete" class="btn-action btn-delete-small" 
											style="display: inline-block; padding: 6px 15px; border: none; cursor: pointer; background-color: #ea4335; color: white; border-radius: 4px; font-size: 12px;">
										Hapus
									</button>
								</form>
							</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    	<footer style="margin-top: auto; padding: 20px; text-align: center; border-top: 1px solid var(--border-color); opacity: 0.6;">
			<p style="margin: 0; font-size: 13px;">
				&copy; <?php echo date("Y"); ?> HMIF Drive | Created by HMIF IPTEK Universitas Pancasakti Tegal
			</p>
		</footer>
</div>

<div id="resetModal" class="modal-overlay" style="display:none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div style="background: var(--card-bg); padding: 25px; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h4 style="margin-top:0; color: var(--text-color);">Ubah Password: <span id="targetUser" style="color: var(--primary-blue);"></span></h4>
        <form action="includes/admin_proc.php" method="POST">
			<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="user_id" id="resetUserId">
            
            <div style="margin: 20px 0;">
                <label style="display:block; margin-bottom:8px; font-size:13px; color: var(--text-color);">Password Baru</label>
                <input type="password" name="new_password" id="newPassInput" required 
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="Minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan angka"
                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-color); box-sizing: border-box;">
                
                <label style="display: flex; align-items: center; gap: 5px; margin-top: 8px; font-size: 12px; cursor: pointer; color: var(--text-color);">
                    <input type="checkbox" onclick="togglePassVisibility()"> Lihat Password
                </label>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeResetModal()" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-color); padding: 10px 20px; border-radius: 6px; cursor:pointer;">Batal</button>
                <button type="submit" name="reset_password" class="btn-new" style="width: auto; margin:0; padding: 10px 25px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDropdown() {
        document.getElementById("userDropdown").classList.toggle("show");
    }

    function openResetModal(id, username) {
        document.getElementById("resetUserId").value = id;
        document.getElementById("targetUser").innerText = username;
        document.getElementById("resetModal").style.display = "flex";
    }

    function closeResetModal() {
        document.getElementById("resetModal").style.display = "none";
    }

    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    function updateThemeUI(isDark) {
        body.setAttribute('data-theme', isDark ? 'dark' : 'light');
        document.getElementById('themeText').innerText = isDark ? "Gelap" : "Terang";
		const iconElement = document.getElementById('themeIcon');
        iconElement.innerText = isDark ? "🌙" : "☀️";
    }

    if (localStorage.getItem('theme') === 'dark') updateThemeUI(true);

    themeToggle.addEventListener('click', () => {
        const isNowDark = body.getAttribute('data-theme') !== 'dark';
        localStorage.setItem('theme', isNowDark ? 'dark' : 'light');
        updateThemeUI(isNowDark);
    });

    window.onclick = function(e) {
        const modal = document.getElementById("resetModal");
        if (e.target == modal) {
            closeResetModal();
        }
        if (!e.target.closest('.user-menu')) {
            document.getElementById("userDropdown").classList.remove("show");
        }
    }
    
    function togglePassVisibility() {
        const x = document.getElementById("newPassInput");
        x.type = x.type === "password" ? "text" : "password";
    }
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('statusAlert');
        if (alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 3000);
        }
    });
 
 document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('statusAlert');
    if (alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500); 
        }, 3000);
    }
});

function toggleAddPassVisibility() {
    const x = document.getElementById("addPassInput");
    x.type = x.type === "password" ? "text" : "password";
}

</script>

</body>
</html>
