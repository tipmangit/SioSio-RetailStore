<?php
include("../config.php");

$search = trim($_POST['query'] ?? '');
$results = [];

if ($search !== '') {
    $stmt = $con->prepare("SELECT * FROM products WHERE status='active' AND name LIKE ? LIMIT 20");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

// Return JSON
echo json_encode(['success' => true, 'products' => $results]);
?>
