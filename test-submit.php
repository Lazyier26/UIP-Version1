<?php
// Minimal test file to check basic PHP functionality
header('Content-Type: application/json');

// Simple response without any dependencies
echo json_encode([
    'success' => true,
    'message' => 'PHP is working correctly!',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => !empty($_POST) ? 'Data received' : 'No POST data'
]);
?>