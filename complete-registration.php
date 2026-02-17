<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['verified_phone'])) {
    header('Location: register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullName = $_POST['full_name'];
    $username = $_POST['username'];
    $phone = $_SESSION['verified_phone'];
    
    $result = registerUser($phone, $fullName, $username);
    
    if ($result['success']) {
        // Auto login after registration
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $sessionToken = createSession($result['user_id'], $deviceInfo, $ipAddress);
        
        if ($sessionToken) {
            setcookie('session_token', $sessionToken, time() + (86400 * 7), '/'); // 7 days
            $_SESSION['user_id'] = $result['user_id'];
            
            // Get user data
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$result['user_id']]);
            $_SESSION['user_data'] = $stmt->fetch();
            
            header('Location: chat.php');
            exit();
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil - Telegram Clone</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Lengkapi Profil</h1>
                <p>Buat profil Anda untuk memulai</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           placeholder="Masukkan nama lengkap"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Masukkan username"
                           pattern="[a-zA-Z0-9_]{3,20}"
                           required>
                    <small>Hanya huruf, angka, dan underscore (3-20 karakter)</small>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary btn-block">
                    Mulai Chat
                </button>
            </form>
        </div>
    </div>
</body>
</html>
