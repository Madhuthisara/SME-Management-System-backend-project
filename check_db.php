<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $stmt = $pdo->query("SHOW DATABASES LIKE 'bm_Projct_db'");
    if ($stmt->fetch()) {
        echo "DATABASE_EXISTS\n";
    } else {
        echo "DATABASE_MISSING\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
