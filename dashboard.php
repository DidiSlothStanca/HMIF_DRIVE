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
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

require_once('config/db.php');
$user = htmlspecialchars($_SESSION['username']);
$role = $_SESSION['role'] ?? 'user';
$user_dir = "data/" . $user;

if (!is_dir($user_dir)) {
    mkdir($user_dir, 0755, true);
    
    file_put_contents($user_dir . "/index.php", ""); 
}
$files = array_diff(scandir($user_dir), array('.', '..', 'index.php'));

$categories = [
    'Gambar'      => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'Dokumen'     => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
    'Spreadsheet' => ['xls', 'xlsx', 'csv'],
    'Video'       => ['mp4', 'mkv', 'mov', 'avi'],
    'Lainnya'     => []
];

$grouped_files = [];
foreach ($files as $f) {
    if ($f === 'index.php') continue;

    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $found = false;
    
    foreach ($categories as $cat => $extensions) {
        if (in_array($ext, $extensions)) {
            $grouped_files[$cat][] = $f;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $grouped_files['Lainnya'][] = $f;
    }
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
    <title>My Drive - HMIF Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="dashboard-wrapper">

<div class="sidebar">
	<nav style="display: flex; flex-direction: column; gap: 2px;">
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="admin_manage.php" class="menu-item active">⚙️ Kelola Member</a>
    <?php endif; ?>
	</nav>
	
	<button class="btn-new" onclick="document.getElementById('upload').click()">
		<span style="font-size: 20px;">+</span> Baru
	</button>
    
    <div style="display:flex; align-items:center; gap:10px; padding-left: 10px; height: 100%;">
       <img src="assets/logo/himp.png" width="35" alt="Logo" style="display: block;">
       <span style="font-size:18px; color:var(--primary-blue); font-weight: bold;" class="hide-mobile">HMIF Drive</span>
    </div>
    
	<form id="upload_form" action="includes/upload.php" method="POST" enctype="multipart/form-data" style="display:none">
		<input type="file" id="upload" name="file[]" multiple onchange="handleUpload(this)">
	</form>
	
	<button type="button" class="btn-action btn-delete" id="activateDeleteMode" 
        style="width: 100%; margin-bottom: 10px; background-color: #ea4335; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
    <span id="delBtnIcon">🗑️</span> <span id="delBtnText">Hapus</span>
	</button>

	<button type="button" id="executeBulkDelete" 
			style="display: none; width: 100%; margin-bottom: 20px; background-color: #ffffff; color: #ea4335; border: 2px solid #ea4335; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; align-items: center; justify-content: center; gap: 8px;" 
			onclick="openDeleteModal(true)">
		🗑️ <span id="selectedCount">0</span> Terpilih
	</button>
	
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
        <h3 style="margin: 0; font-size: 18px;">🗂️ Drive Saya</h3>
        
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
    <?php if (isset($_GET['status'])): ?>
    <div id="statusAlert" class="note-alert">
        <?php 
            if($_GET['status'] == 'upload_success') {
                echo "✅ Berhasil mengunggah " . ($_GET['count'] ?? '1') . " file.";
            } elseif($_GET['status'] == 'deleted') {
                echo "🗑️ " . ($_GET['count'] ?? '1') . " file telah dihapus.";
            } elseif($_GET['status'] == 'error') {
                echo "❌ Terjadi kesalahan saat memproses file.";
            }
        ?>
    </div>
    <?php endif; ?>

    <div style="margin: 20px 0; position: relative;">
    <input type="text" id="fileSearch" placeholder="Cari file..." 
        style="width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); outline: none;">
    <span style="position: absolute; right: 15px; top: 12px; opacity: 0.5;">🔍</span>
</div>


<form id="bulkDeleteForm" action="includes/delete_bulk.php" method="POST">
    <div class="file-section">
        <?php if(empty($files)): ?>
            <div style="text-align: center; padding: 100px; opacity: 0.3;">
                <img src="assets/logo/himp.png" style="width: 60px; filter: grayscale(1);"><br><br>
                Drive kosong
            </div>
        <?php else: ?>
            <?php foreach($grouped_files as $catName => $fileList): ?>
                <?php if(!empty($fileList)): ?>
                
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px;">
                        <h3 style="margin: 0;"><?php echo $catName; ?> (<?php echo count($fileList); ?>)</h3>
                        
                        <label class="select-mode" style="font-size: 12px; cursor: pointer; color: var(--primary-blue); display: none;">
							<input type="checkbox" onclick="toggleCategory('<?php echo $catName; ?>', this)"> Pilih Semua
						</label>
						
                    </div>
                    
                    <div class="grid" style="margin-bottom: 30px;">
                        <?php foreach($fileList as $f): 
                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                            $filePath = $user_dir . '/' . $f;
                        ?>
                            <div class="file-box" id="file-<?php echo md5($f); ?>" onclick="handleFileClick(event, '<?php echo md5($f); ?>')" style="position: relative;">
                            
                                <input type="checkbox" name="files[]" value="<?php echo htmlspecialchars($f); ?>" 
								   id="check-<?php echo md5($f); ?>"
								   class="file-checkbox select-mode cat-<?php echo $catName; ?>" 
								   style="position: absolute; top: 10px; left: 10px; z-index: 5; width: 18px; height: 18px; cursor: pointer; display: none;"
								   onchange="updateBulkUI()">
                                
                                <div class="file-preview">
                                    <?php if(in_array($ext, $categories['Gambar'])): ?>
                                        <img src="view_file.php?file=<?php echo urlencode($f); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="font-size: 40px;">
                                            <?php 
                                                if($catName == 'Dokumen') echo "📄";
                                                elseif($catName == 'Spreadsheet') echo "📊";
                                                elseif($catName == 'Video') echo "🎬";
                                                else echo "📁";
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 13px; font-weight: 500; margin-bottom: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo $f; ?>">
                                    <?php echo $f; ?>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <button type="button" onclick="openPreview('<?php echo $filePath; ?>', '<?php echo $ext; ?>')" class="btn-action" style="background:none; border:1px solid var(--border-color); color:var(--text-color);">Pratinjau</button>
                                   <div style="display: flex; gap: 5px;">
                                        <a href="includes/download.php?file=<?php echo urlencode($f); ?>" class="btn-action btn-download" style="flex:1;">Unduh</a>
                                        <a href="javascript:void(0)" class="btn-action btn-delete" style="flex:1;" onclick="openDeleteModal(false, '<?php echo $f; ?>')">Hapus</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</form>
	<footer style="margin-top: auto; padding: 20px; text-align: center; border-top: 1px solid var(--border-color); opacity: 0.6;">
        <p style="margin: 0; font-size: 13px;">
            &copy; <?php echo date("Y"); ?> HMIF Drive | Created by HMIF IPTEK Universitas Pancasakti Tegal
        </p>
    </footer>
	</div>

<div id="previewModal">
    <div style="background:var(--card-bg); padding:20px; border-radius:12px; width:85%; max-width:900px; position:relative; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <span onclick="closePreview()" style="position:absolute; top:10px; right:20px; font-size:30px; cursor:pointer; color:var(--text-color); z-index: 10;">&times;</span>
        <div id="previewContent" style="text-align:center; min-height: 200px; display: flex; align-items: center; justify-content: center;">
            </div>
    </div>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById("userDropdown");
        dropdown.classList.toggle("show");
    }

    window.onclick = function(e) {
        if (e.target == document.getElementById('previewModal')) {
            closePreview();
        }
        if (!e.target.closest('.user-menu')) {
            const dropdown = document.getElementById("userDropdown");
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeText = document.getElementById('themeText');
    const themeIcon = document.getElementById('themeIcon');
    const body = document.body;

    function updateThemeUI(isDark) {
        body.setAttribute('data-theme', isDark ? 'dark' : 'light');
        if(themeText) themeText.innerText = isDark ? "Mode Gelap" : "Mode Terang";
        if(themeIcon) themeIcon.innerText = isDark ? "🌙" : "☀️";
    }

    if (localStorage.getItem('theme') === 'dark') { updateThemeUI(true); }

    themeToggle.addEventListener('click', () => {
        const isNowDark = body.getAttribute('data-theme') !== 'dark';
        localStorage.setItem('theme', isNowDark ? 'dark' : 'light');
        updateThemeUI(isNowDark);
    });

    function openPreview(url, ext) {
        const modal = document.getElementById('previewModal');
        const content = document.getElementById('previewContent');
        const extension = ext.toLowerCase();
        const fileName = url.split('/').pop();
        const secureUrl = `view_file.php?file=${encodeURIComponent(fileName)}`;

        content.innerHTML = 'Memuat...';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension)) {
            content.innerHTML = `<img src="${secureUrl}" style="max-width:100%; max-height:80vh; border-radius:4px;">`;
        } else if (extension === 'pdf') {
            content.innerHTML = `<iframe src="${secureUrl}" width="100%" height="600px" style="border:none;"></iframe>`;
        } else if (['mp4', 'mkv', 'mov', 'webm'].includes(extension)) {
            content.innerHTML = `<video width="100%" height="auto" controls autoplay style="max-height:75vh;"><source src="${secureUrl}" type="video/mp4"></video>`;
        } else {
            content.innerHTML = `<div style="padding:40px;">📄 Preview tidak tersedia untuk .${extension}<br><br><a href="${secureUrl}" download class="btn-new" style="display:inline-block; width:auto; padding:10px 20px;">Unduh File</a></div>`;
        }
        modal.style.display = 'flex';
    }

    function closePreview() {
        document.getElementById('previewModal').style.display = 'none';
        document.getElementById('previewContent').innerHTML = '';
    }

    const searchInput = document.getElementById('fileSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const fileBoxes = document.querySelectorAll('.file-box');
            fileBoxes.forEach(box => {
                const fileName = box.querySelector('div[title]').getAttribute('title').toLowerCase();
                box.style.display = fileName.includes(filter) ? "block" : "none";
            });
        });
    }

    function handleUpload(input) {
        if (input.files.length === 0) return;
        const modal = document.getElementById('confirmUploadModal');
        const confirmState = document.getElementById('confirmState');
        const loadingState = document.getElementById('loadingState');
        const confirmText = document.getElementById('confirmUploadText');

        confirmState.style.display = 'block';
        loadingState.style.display = 'none';
        confirmText.innerText = `Unggah ${input.files.length} file ke HMIF Drive?`;
        modal.style.display = 'flex';

        document.getElementById('btnConfirmUpload').onclick = function() {
            confirmState.style.display = 'none';
            loadingState.style.display = 'block';
            startAjaxUpload(input.files);
        };
    }

    function startAjaxUpload(files) {
        const progressBar = document.getElementById('uploadProgressBar');
        const progressPercent = document.getElementById('uploadProgressPercent');
        const statusTitle = document.getElementById('statusTitle');
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) { formData.append('file[]', files[i]); }

        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.innerText = percent + '%';
                if (percent === 100) statusTitle.innerText = "Menyimpan...";
            }
        });
        xhr.onload = function() {
            if (xhr.status === 200) {
                window.location.href = `dashboard.php?status=upload_success&count=${files.length}`;
            } else {
                alert("Gagal mengunggah.");
                closeModal('confirmUploadModal');
            }
        };
        xhr.open('POST', 'includes/upload.php', true);
        xhr.send(formData);
    }

    let isDeleteMode = false;
    let deleteAction = null;

    function handleFileClick(event, fileId) {
        if (!isDeleteMode) return;
        if (event.target.closest('.btn-action') || event.target.tagName === 'A') return;

        const checkbox = document.getElementById('check-' + fileId);
        const fileBox = document.getElementById('file-' + fileId);

        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            fileBox.classList.add('selected-file');
            fileBox.style.border = "2px solid #ea4335";
            fileBox.style.backgroundColor = "rgba(234, 67, 53, 0.05)";
        } else {
            fileBox.classList.remove('selected-file');
            fileBox.style.border = "1px solid var(--border-color)";
            fileBox.style.backgroundColor = "transparent";
        }
        updateBulkUI();
    }

    document.getElementById('activateDeleteMode').addEventListener('click', function() {
        isDeleteMode = !isDeleteMode;
        const checkboxes = document.querySelectorAll('.select-mode');
        const delBtnText = document.getElementById('delBtnText');
        const delBtnIcon = document.getElementById('delBtnIcon');
        const executeBtn = document.getElementById('executeBulkDelete');

        if (isDeleteMode) {
            checkboxes.forEach(el => el.style.display = 'block');
            delBtnText.innerText = "Batal";
            delBtnIcon.innerText = "❌";
            this.style.backgroundColor = "#5f6368";
        } else {
            checkboxes.forEach(el => {
                el.style.display = 'none';
                if(el.type === 'checkbox') el.checked = false;
            });
            // Reset visual seleksi saat batal
            document.querySelectorAll('.file-box').forEach(box => {
                box.style.border = "1px solid var(--border-color)";
                box.style.backgroundColor = "transparent";
            });
            delBtnText.innerText = "Hapus";
            delBtnIcon.innerText = "🗑️";
            this.style.backgroundColor = "#ea4335";
            executeBtn.style.display = 'none';
        }
    });

    function updateBulkUI() {
        const checkedFiles = document.querySelectorAll('.file-checkbox:checked');
        const executeBtn = document.getElementById('executeBulkDelete');
        const countLabel = document.getElementById('selectedCount');
        
        if (checkedFiles.length > 0 && isDeleteMode) {
            executeBtn.style.display = 'flex';
            countLabel.innerText = checkedFiles.length;
        } else {
            executeBtn.style.display = 'none';
        }
    }

    function toggleCategory(catName, source) {
        const checkboxes = document.querySelectorAll('.cat-' + catName);
        checkboxes.forEach(cb => {
            cb.checked = source.checked;
            // Update visual border untuk setiap box dalam kategori
            const fileBox = cb.closest('.file-box');
            if (cb.checked) {
                fileBox.style.border = "2px solid #ea4335";
                fileBox.style.backgroundColor = "rgba(234, 67, 53, 0.05)";
            } else {
                fileBox.style.border = "1px solid var(--border-color)";
                fileBox.style.backgroundColor = "transparent";
            }
        });
        updateBulkUI();
    }

    function openDeleteModal(isBulk, fileName = '') {
        const modal = document.getElementById('deleteConfirmModal');
        const confirmState = document.getElementById('confirmDeleteState');
        const loadingState = document.getElementById('loadingDeleteState');
        const text = document.getElementById('deleteConfirmText');
        const btn = document.getElementById('btnExecuteDelete');

        confirmState.style.display = 'block';
        loadingState.style.display = 'none';
        
        if(isBulk) {
            const count = document.querySelectorAll('.file-checkbox:checked').length;
            text.innerText = `Anda akan menghapus ${count} file secara permanen.`;
            deleteAction = 'bulk';
        } else {
            text.innerText = `Hapus "${fileName}" secara permanen?`;
            deleteAction = fileName;
        }

        modal.style.display = 'flex';

        btn.onclick = function() {
            confirmState.style.display = 'none';
            loadingState.style.display = 'block';
            let progress = 0;
            const bar = document.getElementById('deleteProgressBar');
            const interval = setInterval(() => {
                progress += 10;
                bar.style.width = progress + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                    executeDelete();
                }
            }, 50);
        };
    }

    function executeDelete() {
        if (deleteAction === 'bulk') {
            document.getElementById('bulkDeleteForm').submit();
        } else {
            window.location.href = `includes/delete_file.php?name=${encodeURIComponent(deleteAction)}`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('statusAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            }, 4000);
        }
    });
</script>
</body>
<div id="confirmUploadModal" class="custom-modal">
    <div class="modal-box">
        <div id="confirmState">
            <h2>Konfirmasi Upload</h2>
            <p id="confirmUploadText">Unggah file?</p>
            <div class="modal-btns">
                <button class="btn-cancel-new" onclick="closeModal('confirmUploadModal')">Batal</button>
                <button class="btn-confirm-new" id="btnConfirmUpload">Upload Sekarang</button>
            </div>
        </div>

        <div id="loadingState" style="display: none;">
            <h2 id="statusTitle">Mengirim...</h2>
            <div style="background: #eee; height: 10px; border-radius: 10px; margin: 25px 0; overflow: hidden;">
                <div id="uploadProgressBar" style="width: 0%; height: 100%; background: #1a73e8; transition: width 0.2s ease;"></div>
            </div>
            <span id="uploadProgressPercent" style="font-size: 20px; font-weight: bold;">0%</span>
        </div>
    </div>
</div>

<div id="deleteConfirmModal" class="custom-modal">
    <div class="modal-box">
        <div id="confirmDeleteState">
            <div style="font-size: 50px; margin-bottom: 10px;">🗑️</div>
            <h2 style="color: #ea4335;">Hapus File?</h2>
            <p id="deleteConfirmText">Apakah Anda yakin ingin menghapus file ini?</p>
            <div class="modal-btns">
                <button class="btn-cancel-new" onclick="closeModal('deleteConfirmModal')">Batal</button>
                <button class="btn-confirm-new" id="btnExecuteDelete" style="background: #ea4335;">Hapus Sekarang</button>
            </div>
        </div>

        <div id="loadingDeleteState" style="display: none;">
            <h2>Menghapus...</h2>
            <div style="background: #eee; height: 10px; border-radius: 10px; margin: 25px 0; overflow: hidden;">
                <div id="deleteProgressBar" style="width: 0%; height: 100%; background: #ea4335; transition: width 0.3s ease;"></div>
            </div>
            <p style="font-size: 14px; color: #666;">Mohon tunggu sebentar</p>
        </div>
    </div>
</div>
</html>
