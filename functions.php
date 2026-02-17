<?php
require_once 'config.php';

// Send OTP via Fonnte
function sendOTP($phoneNumber) {
    $pdo = getDB();
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Save OTP to database
    $stmt = $pdo->prepare("
        INSERT INTO otp_verifications (phone_number, otp_code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$phoneNumber, $otp, $expiresAt]);
    
    // Send OTP via Fonnte API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $phoneNumber,
            'message' => "Kode OTP Anda: $otp\n\nJangan berikan kode ini kepada siapapun.\nBerlaku 5 menit.",
            'countryCode' => '62',
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . FONNTE_API_TOKEN
        ],
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response, true);
}

// Verify OTP
function verifyOTP($phoneNumber, $otp) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT * FROM otp_verifications 
        WHERE phone_number = ? 
        AND otp_code = ? 
        AND is_used = FALSE 
        AND expires_at > NOW() 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$phoneNumber, $otp]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Mark OTP as used
        $stmt = $pdo->prepare("
            UPDATE otp_verifications SET is_used = TRUE WHERE id = ?
        ");
        $stmt->execute([$result['id']]);
        
        return true;
    }
    
    return false;
}

// Register new user
function registerUser($phoneNumber, $fullName, $username) {
    $pdo = getDB();
    
    // Check if phone exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Nomor sudah terdaftar'];
    }
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username sudah digunakan'];
    }
    
    // Create new user
    $stmt = $pdo->prepare("
        INSERT INTO users (phone_number, full_name, username) 
        VALUES (?, ?, ?)
    ");
    
    if ($stmt->execute([$phoneNumber, $fullName, $username])) {
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    }
    
    return ['success' => false, 'message' => 'Gagal mendaftar'];
}

// Create session
function createSession($userId, $deviceInfo, $ipAddress) {
    $pdo = getDB();
    $sessionToken = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_token, device_info, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$userId, $sessionToken, $deviceInfo, $ipAddress])) {
        return $sessionToken;
    }
    
    return false;
}

// Validate session
function validateSession($sessionToken) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT u.* FROM users u
        JOIN user_sessions s ON u.id = s.user_id
        WHERE s.session_token = ? 
        AND s.is_active = TRUE 
        AND s.last_activity > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$sessionToken]);
    
    return $stmt->fetch();
}

// Send message
function sendMessage($senderId, $receiverId, $message, $type = 'text', $mediaUrl = null) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, message_type, media_url) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$senderId, $receiverId, $message, $type, $mediaUrl])) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

// Get messages between two users
function getMessages($userId1, $userId2, $limit = 50, $offset = 0) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u1.full_name as sender_name,
               u1.username as sender_username
        FROM messages m
        JOIN users u1 ON m.sender_id = u1.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        AND m.is_deleted = FALSE
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit, $offset]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = TRUE 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$userId2, $userId1]);
    
    return array_reverse($messages);
}

// Get user contacts
function getContacts($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               c.contact_name,
               (SELECT message FROM messages 
                WHERE (sender_id = ? AND receiver_id = u.id) 
                   OR (sender_id = u.id AND receiver_id = ?)
                ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM messages 
                WHERE (sender_id = ? AND receiver_id = u.id) 
                   OR (sender_id = u.id AND receiver_id = ?)
                ORDER BY created_at DESC LIMIT 1) as last_message_time,
               (SELECT COUNT(*) FROM messages 
                WHERE sender_id = u.id AND receiver_id = ? AND is_read = FALSE) as unread_count
        FROM contacts c
        JOIN users u ON c.contact_id = u.id
        WHERE c.user_id = ? AND c.is_blocked = FALSE
        ORDER BY last_message_time DESC
    ");
    
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    
    return $stmt->fetchAll();
}

// Add contact
function addContact($userId, $phoneNumber, $contactName = null) {
    $pdo = getDB();
    
    // Find user by phone
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    $contact = $stmt->fetch();
    
    if (!$contact) {
        return ['success' => false, 'message' => 'Nomor tidak ditemukan'];
    }
    
    if ($contact['id'] == $userId) {
        return ['success' => false, 'message' => 'Tidak bisa menambah diri sendiri'];
    }
    
    // Check if already contact
    $stmt = $pdo->prepare("
        SELECT id FROM contacts 
        WHERE user_id = ? AND contact_id = ?
    ");
    $stmt->execute([$userId, $contact['id']]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Kontak sudah ada'];
    }
    
    // Add contact
    $stmt = $pdo->prepare("
        INSERT INTO contacts (user_id, contact_id, contact_name) 
        VALUES (?, ?, ?)
    ");
    
    if ($stmt->execute([$userId, $contact['id'], $contactName])) {
        return ['success' => true, 'contact' => $contact];
    }
    
    return ['success' => false, 'message' => 'Gagal menambah kontak'];
}

// Search users
function searchUsers($query, $excludeUserId = null) {
    $pdo = getDB();
    
    $sql = "SELECT id, phone_number, username, full_name, profile_photo 
            FROM users 
            WHERE (phone_number LIKE ? OR username LIKE ? OR full_name LIKE ?)";
    
    $params = ["%$query%", "%$query%", "%$query%"];
    
    if ($excludeUserId) {
        $sql .= " AND id != ?";
        $params[] = $excludeUserId;
    }
    
    $sql .= " LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

// Update last seen
function updateLastSeen($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE users SET last_seen = NOW() WHERE id = ?
    ");
    $stmt->execute([$userId]);
}

// Get unread messages count
function getUnreadCount($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM messages 
        WHERE receiver_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    return $result['total'];
}

// Logout user
function logoutUser() {
    if (isset($_COOKIE['session_token'])) {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            UPDATE user_sessions SET is_active = FALSE WHERE session_token = ?
        ");
        $stmt->execute([$_COOKIE['session_token']]);
        setcookie('session_token', '', time() - 3600, '/');
    }
    
    $_SESSION = array();
    session_destroy();
}
?>
