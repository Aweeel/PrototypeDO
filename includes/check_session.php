<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
    echo json_encode(['valid' => true, 'user_id' => $_SESSION['user_id']]);
} else {
    echo json_encode(['valid' => false]);
}
?>