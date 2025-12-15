<?php
require_once 'api/config.php';

echo "<h2>Testing Phone Store Setup</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $database = new Database();
    $db = $database->connect();
    
    if ($db) {
        echo "✅ <strong style='color: green;'>Database connected successfully!</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>Database connection failed!</strong><br>";
        exit();
    }
} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>Error: " . $e->getMessage() . "</strong><br>";
    exit();
}

// Test 2: Check Tables
echo "<h3>2. Tables Check</h3>";
$tables = ['users', 'admins', 'phones', 'cart', 'orders', 'order_items', 'payments'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Table '<strong>$table</strong>' exists - <strong>$count</strong> records<br>";
    } catch (Exception $e) {
        echo "❌ Table '<strong>$table</strong>' not found!<br>";
    }
}

// Test 3: Check Admin Account
echo "<h3>3. Admin Account Test</h3>";
try {
    $stmt = $db->query("SELECT username, email FROM admins LIMIT 1");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "✅ Admin account exists<br>";
        echo "   Username: <strong>" . $admin['username'] . "</strong><br>";
        echo "   Email: <strong>" . $admin['email'] . "</strong><br>";
        echo "   Password: <strong>admin123</strong> (default)<br>";
    } else {
        echo "❌ No admin account found!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check Sample Phones
echo "<h3>4. Sample Phones Test</h3>";
try {
    $stmt = $db->query("SELECT brand, model, price FROM phones LIMIT 5");
    $phones = $stmt->fetchAll();
    
    if (count($phones) > 0) {
        echo "✅ Found <strong>" . count($phones) . "</strong> sample phones:<br>";
        echo "<ul>";
        foreach ($phones as $phone) {
            echo "<li>{$phone['brand']} {$phone['model']} - NPR " . number_format($phone['price'], 2) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ No phones found in database!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Uploads Folder
echo "<h3>5. Uploads Folder Test</h3>";
$uploadDir = __DIR__ . '/uploads/phones/';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "✅ Uploads folder exists and is writable<br>";
        echo "   Path: <strong>$uploadDir</strong><br>";
    } else {
        echo "⚠️ Uploads folder exists but is NOT writable!<br>";
    }
} else {
    echo "❌ Uploads folder does not exist!<br>";
    echo "   Please create: <strong>$uploadDir</strong><br>";
}

echo "<hr>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p><a href='index.php' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Go to Store Homepage</a></p>";
echo "<p><a href='admin/login.php' style='padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px;'>Admin Login</a></p>";
?>