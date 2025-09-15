<?php
// Simple test config to verify basic functionality

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP Configuration Test</h2>";

// Test 1: PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Test 2: Database connection
echo "<h3>Database Connection Test</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
    echo "<p>✅ MySQL connection successful</p>";
    
    // Test database exists
    $stmt = $pdo->prepare("SHOW DATABASES LIKE 'uip_registration'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p>✅ Database 'uip_registration' exists</p>";
    } else {
        echo "<p>❌ Database 'uip_registration' does not exist</p>";
        echo "<p>Please create it using phpMyAdmin</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 3: Uploads directory
echo "<h3>File Upload Test</h3>";
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p>✅ Created uploads directory</p>";
    } else {
        echo "<p>❌ Failed to create uploads directory</p>";
    }
} else {
    echo "<p>✅ Uploads directory exists</p>";
}

// Create subdirectories
$subdirs = ['cv', 'pictures', 'endorsements', 'moa'];
foreach ($subdirs as $subdir) {
    $path = $uploadDir . $subdir . '/';
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            echo "<p>✅ Created {$subdir} directory</p>";
        } else {
            echo "<p>❌ Failed to create {$subdir} directory</p>";
        }
    } else {
        echo "<p>✅ {$subdir} directory exists</p>";
    }
}

// Test 4: File upload settings
echo "<h3>PHP Upload Settings</h3>";
echo "<p><strong>Max file size:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>Max post size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>File uploads:</strong> " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</p>";

echo "<hr>";
echo "<p>If you see any ❌ above, please fix those issues before testing the form.</p>";
?>