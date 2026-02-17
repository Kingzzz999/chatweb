<?php
session_start();
require_once 'functions.php';

// If already logged in, redirect to chat
if (checkAuth()) {
    header('Location: chat.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . ltrim($phone, '0');
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Create session (no password needed, just phone verification)
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $sessionToken = createSession($user['id'], $deviceInfo, $ipAddress);
        
        if ($sessionToken) {
            setcookie('session_token', $sessionToken, time() + (86400 * 7), '/');
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = $user;
            
            header('Location: chat.php');
            exit();
        }
    } else {
        $error = "Nomor telepon tidak terdaftar";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Telegram Clone</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Masuk ke Telegram Clone</h1>
                <p>Masukkan nomor telepon Anda</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <div class="phone-input">
                        <span class="country-code">+62</span>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               placeholder="81234567890" 
                               pattern="[0-9]{10,13}"
                               required>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary btn-block">
                    Masuk
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Belum punya akun? <a href="register.php">Daftar</a></p>
            </div>
        </div>
    </div>
</body>
</html>
