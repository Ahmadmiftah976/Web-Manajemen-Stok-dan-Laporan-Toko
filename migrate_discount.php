<?php
$documentRoot = __DIR__;
require_once $documentRoot . '/config/app.php';

foreach (glob(CORE_PATH . '/*.php') as $file) {
    require_once $file;
}
foreach (glob(APP_PATH . '/helpers/*.php') as $file) {
    require_once $file;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if column exists
    $checkSql = "SELECT column_name FROM information_schema.columns WHERE table_name='transactions' and column_name='discount_amount';";
    $stmt = $pdo->query($checkSql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result)) {
        $sql = "ALTER TABLE transactions ADD COLUMN discount_amount NUMERIC(12, 2) DEFAULT 0 CHECK (discount_amount >= 0);";
        $pdo->exec($sql);
        echo "Column discount_amount added successfully.\n";
    } else {
        echo "Column discount_amount already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
