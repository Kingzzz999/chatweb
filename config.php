<?php
define( 'DB_HOST', 'localhost');
define( 'DB_NAME', 'telegram_clone');
define( 'DB_USER', 'root');
define( 'DB_PASS', '');
define( 'FONNTE_API_TOKEN, 'xkdvQAq2XkZjg1T9AsRy');

define( 'SITE_URL', 'http://localhost./telegr{am-clone');
define( 'SITE_NAME', 'Telegram Clone');

function getDB() {
  try {
    $pdo = new PDO(
      'mysql:host=" . DB_HOST . ";dbname" . DB_NAME . ";charset=utf8mb4",
      DB_USER,
      DB_PASS,
      [
      PDO: :ATTR_ERRMODE => PDO: :ERRMODE_EXCEPTION,
      PDO: :ATTR_DEFAULT_FETCH_MODE => PDO: :FETCH_ASSOC
      PDO: :ATTR_EMULATE_PREPARES => false
      ]
  );
      return $pdo;
      } catch (PDOExeption $e) {
      die("Connention failed: " . $e->getMessage());
      }
      }
      ?>
