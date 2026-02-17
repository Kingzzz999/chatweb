<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    // Add country code if not present
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . ltrim($phone, '0');
    }
    
    $result = sendOTP($phone);
    
    if ($result && isset($result['status']) && $result['status']) {
        session_start();
        $_SESSION['otp_phone'] = $phone;
        header('Location: verify-otp.php');
        exit();
    } else {
        $error = "Gagal mengirim OTP. Coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Telegram Clone</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="https://via.placeholder.com/80" alt="Logo" class="logo">
                <h1>Daftar Telegram Clone</h1>
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
                    <small>Contoh: 81234567890 (tanpa 0 di depan)</small>
                </div>
                
                <button type="submit" name="send_otp" class="btn btn-primary btn-block">
                    Kirim Kode OTP
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Masuk</a></p>
            </div>
        </div>
    </div>
</body>
</html>
