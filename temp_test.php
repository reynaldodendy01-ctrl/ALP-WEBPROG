<?php
require 'db.php';
try {
    $q = $pdo->query("DESCRIBE maintenance_staff");
    print_r($q->fetchAll());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
