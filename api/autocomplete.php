<?php
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$results = [];

if (mb_strlen($q) >= 2) {
    $esc = $conn->real_escape_string($q);
    $res = $conn->query(
        "SELECT id, name, price, image
         FROM products
         WHERE name LIKE '%$esc%'
         ORDER BY views DESC
         LIMIT 8"
    );
    while ($r = $res->fetch_assoc()) {
        $results[] = [
            'id'    => (int)$r['id'],
            'name'  => $r['name'],
            'price' => (int)$r['price'],
            'image' => $r['image'],
        ];
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);