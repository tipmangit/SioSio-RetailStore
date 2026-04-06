<?php
/**
 * Save Address Setup (First-time login)
 * Location: /siosios/Profile/save_address_setup.php
 */
session_start();
include("../config.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$address_line1 = trim($_POST['address_line1'] ?? '');
$address_line2 = trim($_POST['address_line2'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$city = trim($_POST['city'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');

// Validate required fields
if (empty($address_line1) || empty($barangay) || empty($city) || empty($postal_code)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

// Validate postal code format
if (!preg_match('/^\d{4}$/', $postal_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid postal code format']);
    exit;
}

// NCR Postal codes validation
$valid_ncr_codes = [
    '1400', '1401', '1402', '1403', '1404', '1405', '1406', '1407', '1408', '1409', '1410', '1411', '1412', '1413', '1420', '1421', '1422', '1423', '1424', '1425', '1426', '1427', '1428',
    '1740', '1741', '1742', '1743', '1744', '1745', '1746', '1747', '1748', '1749', '1750', '1751', '1752',
    '1200', '1201', '1202', '1203', '1204', '1205', '1206', '1207', '1208', '1209', '1210', '1211', '1212', '1213', '1214', '1215', '1216', '1217', '1218', '1219', '1220', '1221', '1222', '1223', '1224', '1225', '1226', '1227', '1228', '1229', '1230', '1231', '1232', '1233', '1234', '1235',
    '1470', '1471', '1472', '1473', '1474', '1475', '1476', '1477', '1478', '1479',
    '1550', '1551', '1552', '1553', '1554', '1555', '1556', '1557', '1558', '1559', '1560', '1561', '1562', '1563', '1564', '1565', '1566', '1567', '1568', '1569',
    '1000', '1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008', '1009', '1010', '1011', '1012', '1013', '1014', '1015', '1016', '1017', '1018',
    '1800', '1801', '1802', '1803', '1804', '1805', '1806', '1807', '1808', '1809', '1810', '1811', '1812', '1813', '1814', '1815', '1816', '1817', '1818', '1819', '1820',
    '1770', '1771', '1772', '1773', '1774', '1775', '1776', '1777', '1778', '1779', '1780', '1781', '1782', '1783', '1784', '1785', '1786', '1787', '1788', '1789', '1790',
    '1485', '1486', '1487', '1488', '1489', '1490',
    '1700', '1701', '1702', '1703', '1704', '1705', '1706', '1707', '1708', '1709', '1710', '1711', '1712', '1713', '1714', '1715', '1716', '1717', '1718', '1719', '1720',
    '1300', '1301', '1302', '1303', '1304', '1305', '1306', '1307', '1308', '1309',
    '1600', '1601', '1602', '1603', '1604', '1605', '1606', '1607', '1608', '1609', '1610', '1611', '1612',
    '1100', '1101', '1102', '1103', '1104', '1105', '1106', '1107', '1108', '1109', '1110', '1111', '1112', '1113', '1114', '1115', '1116', '1117', '1118', '1119', '1120', '1121', '1122', '1123', '1124', '1125', '1126', '1127',
    '1500', '1501', '1502', '1503', '1504',
    '1630', '1631', '1632', '1633', '1634', '1635', '1636', '1637', '1638', '1639', '1640',
    '1440', '1441', '1442', '1443', '1444', '1445', '1446', '1447', '1448', '1449', '1469'
];

if (!in_array($postal_code, $valid_ncr_codes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid NCR postal code']);
    exit;
}

// Update user address
$stmt = $con->prepare("UPDATE userss SET 
    address_line1 = ?,
    address_line2 = ?,
    barangay = ?,
    city = ?,
    postal_code = ?
    WHERE user_id = ?");

$stmt->bind_param("sssssi", 
    $address_line1,
    $address_line2,
    $barangay,
    $city,
    $postal_code,
    $user_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $con->error]);
}

$stmt->close();
$con->close();
?>