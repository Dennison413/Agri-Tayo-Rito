<?php
// Place this file in: /agri_system/public/test_db.php
// Access via: http://localhost/agri_system/public/test_db.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Connection Test ===<br><br>";

// Test 1: Check if config file exists
$configPath = __DIR__ . '/../config/database.php';
echo "1. Config file path: " . $configPath . "<br>";
echo "   File exists: " . (file_exists($configPath) ? "YES ✓" : "NO ✗") . "<br><br>";

if (!file_exists($configPath)) {
    die("ERROR: Database config file not found!");
}

// Test 2: Load database class
require_once $configPath;

echo "2. Database class loaded: YES ✓<br><br>";

// Test 3: Try to connect
try {
    $db = new Database();
    $conn = $db->connect();
    echo "3. Database connection: SUCCESS ✓<br><br>";
    
    // Test 4: Check if users table exists
    $query = "SHOW TABLES LIKE 'users'";
    $stmt = $conn->query($query);
    $tableExists = $stmt->rowCount() > 0;
    
    echo "4. Users table exists: " . ($tableExists ? "YES ✓" : "NO ✗") . "<br><br>";
    
    if ($tableExists) {
        // Test 5: Check users table structure
        $query = "DESCRIBE users";
        $stmt = $conn->query($query);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "5. Users table structure:<br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-family: monospace;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Test 6: Check if phone field is nullable
        $phoneField = array_filter($columns, function($col) {
            return $col['Field'] === 'phone';
        });
        
        if (!empty($phoneField)) {
            $phoneField = array_values($phoneField)[0];
            echo "6. Phone field nullable: " . ($phoneField['Null'] === 'YES' ? "YES ✓" : "NO ✗") . "<br>";
            
            if ($phoneField['Null'] === 'NO') {
                echo "<strong style='color: red;'>   ⚠️ WARNING: Phone field is NOT NULL! Run this SQL:</strong><br>";
                echo "<code style='background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;'>";
                echo "ALTER TABLE `users` MODIFY COLUMN `phone` varchar(20) DEFAULT NULL;";
                echo "</code>";
            }
        }
        echo "<br>";
        
        // Test 7: Try to insert a test user (will rollback)
        echo "7. Testing user creation (will rollback):<br>";
        
        try {
            $conn->beginTransaction();
            
            $testEmail = 'test_' . time() . '@example.com';
            $testPassword = password_hash('testpassword123', PASSWORD_DEFAULT);
            
            $insertQuery = "INSERT INTO users (email, password_hash, full_name, role) 
                           VALUES (:email, :password_hash, :full_name, :role)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute([
                ':email' => $testEmail,
                ':password_hash' => $testPassword,
                ':full_name' => 'Test User',
                ':role' => 'buyer'
            ]);
            
            $insertedId = $conn->lastInsertId();
            echo "   Test insert successful! ID: $insertedId ✓<br>";
            echo "   Rolling back transaction...<br>";
            
            $conn->rollBack();
            echo "   Transaction rolled back ✓<br><br>";
            
            echo "<strong style='color: green;'>✓ ALL TESTS PASSED!</strong><br>";
            echo "<strong>Your database is configured correctly.</strong><br><br>";
            
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "   <strong style='color: red;'>✗ Test insert failed!</strong><br>";
            echo "   Error: " . htmlspecialchars($e->getMessage()) . "<br><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "<strong style='color: red;'>3. Database connection FAILED ✗</strong><br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<br>Common fixes:<br>";
    echo "- Check database credentials in config/database.php<br>";
    echo "- Ensure MySQL/MariaDB server is running<br>";
    echo "- Verify database name exists<br>";
}

echo "<br><hr><br>";
echo "<strong>Next Steps:</strong><br>";
echo "1. If all tests passed, try registering again<br>";
echo "2. If tests failed, fix the issues shown above<br>";
echo "3. Delete this file after testing for security<br>";
?>