<?php
session_start();
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $con->prepare("SELECT * FROM userss WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: ../loginreg/logreg.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SioSio</title>
    <link rel="stylesheet" href="profile.css">
    <style>
        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
        
        .password-strength.weak {
            background: #dc3545;
            width: 33%;
        }
        
        .password-strength.medium {
            background: #ffc107;
            width: 66%;
        }
        
        .password-strength.strong {
            background: #28a745;
            width: 100%;
        }
        
        .alert {
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-photo-upload {
            position: relative;
            display: inline-block;
        }
        
        .upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-overlay:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }
        
        #profilePhotoInput {
            display: none;
        }
        
        .address-section {
            background: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .address-section h6 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include("../headfoot/header.php"); ?>

    <div class="profile-page">
        <div class="container">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-photo-container">
                        <div class="profile-photo-upload">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="profile-photo" id="profilePhotoPreview">
                            <?php else: ?>
                                <div class="default-avatar" id="profilePhotoPreview">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                            <?php endif; ?>
                            <label for="profilePhotoInput" class="upload-overlay" title="Change photo">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <input type="file" id="profilePhotoInput" accept="image/*">
                        </div>
                    </div>
                    <h2><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="text-muted">@<?= htmlspecialchars($user['username']) ?></p>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#personal-info">Personal Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#address">Delivery Address</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#password">Change Password</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Personal Information Tab -->
                    <div id="personal-info" class="tab-pane fade show active">
                        <h4 class="mb-4">Personal Information</h4>
                        <div id="personal-info-alerts"></div>
                        
                        <form id="personalInfoForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control" name="contact_num" value="<?= htmlspecialchars($user['contact_num']) ?>" pattern="^(09|\+639)\d{9}$" required>
                                    <small class="form-text text-muted">Format: 09XXXXXXXXX</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-sio">
                                <i class="bi bi-shield-check"></i> Update Information (OTP Required)
                            </button>
                        </form>
                    </div>

                    <!-- Delivery Address Tab -->
                    <div id="address" class="tab-pane fade">
                        <h4 class="mb-4">Delivery Address</h4>
                        <div id="address-alerts"></div>
                        
                        <form id="addressForm">
                            <div class="address-section">
                                <h6><i class="bi bi-house-door"></i> Street Address</h6>
                                <div class="mb-3">
                                    <label class="form-label">Address Line 1 *</label>
                                    <input type="text" class="form-control" name="address_line1" 
                                           value="<?= htmlspecialchars($user['address_line1'] ?? '') ?>" 
                                           placeholder="House/Unit No., Street Name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control" name="address_line2" 
                                           value="<?= htmlspecialchars($user['address_line2'] ?? '') ?>" 
                                           placeholder="Building, Subdivision, Landmark">
                                </div>
                            </div>

                            <div class="address-section">
                                <h6><i class="bi bi-map"></i> Location Details</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Barangay *</label>
                                        <input type="text" class="form-control" name="barangay" 
                                               value="<?= htmlspecialchars($user['barangay'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City *</label>
                                        <select class="form-select" name="city" id="city" required>
                                            <option value="">Select city</option>
                                            <option value="Caloocan" <?= ($user['city'] ?? '') === 'Caloocan' ? 'selected' : '' ?>>Caloocan</option>
                                            <option value="Las Piñas" <?= ($user['city'] ?? '') === 'Las Piñas' ? 'selected' : '' ?>>Las Piñas</option>
                                            <option value="Makati" <?= ($user['city'] ?? '') === 'Makati' ? 'selected' : '' ?>>Makati</option>
                                            <option value="Malabon" <?= ($user['city'] ?? '') === 'Malabon' ? 'selected' : '' ?>>Malabon</option>
                                            <option value="Mandaluyong" <?= ($user['city'] ?? '') === 'Mandaluyong' ? 'selected' : '' ?>>Mandaluyong</option>
                                            <option value="Manila" <?= ($user['city'] ?? '') === 'Manila' ? 'selected' : '' ?>>Manila</option>
                                            <option value="Marikina" <?= ($user['city'] ?? '') === 'Marikina' ? 'selected' : '' ?>>Marikina</option>
                                            <option value="Muntinlupa" <?= ($user['city'] ?? '') === 'Muntinlupa' ? 'selected' : '' ?>>Muntinlupa</option>
                                            <option value="Navotas" <?= ($user['city'] ?? '') === 'Navotas' ? 'selected' : '' ?>>Navotas</option>
                                            <option value="Parañaque" <?= ($user['city'] ?? '') === 'Parañaque' ? 'selected' : '' ?>>Parañaque</option>
                                            <option value="Pasay" <?= ($user['city'] ?? '') === 'Pasay' ? 'selected' : '' ?>>Pasay</option>
                                            <option value="Pasig" <?= ($user['city'] ?? '') === 'Pasig' ? 'selected' : '' ?>>Pasig</option>
                                            <option value="Quezon City" <?= ($user['city'] ?? '') === 'Quezon City' ? 'selected' : '' ?>>Quezon City</option>
                                            <option value="San Juan" <?= ($user['city'] ?? '') === 'San Juan' ? 'selected' : '' ?>>San Juan</option>
                                            <option value="Taguig" <?= ($user['city'] ?? '') === 'Taguig' ? 'selected' : '' ?>>Taguig</option>
                                            <option value="Valenzuela" <?= ($user['city'] ?? '') === 'Valenzuela' ? 'selected' : '' ?>>Valenzuela</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
<div class="col-md-6 mb-3">
    <label class="form-label">Postal Code *</label>
    <input type="text" class="form-control" id="postal_code" name="postal_code" 
           value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>" pattern="[0-9]{4}" maxlength="4" required>
    
    <div id="postal-info" class="postal-info" style="display: none; background: #e7f5ff; border-left: 4px solid #0d6efd; padding: 10px 15px; border-radius: 5px; margin-top: 10px;">
        <i class="bi bi-info-circle"></i>
        <strong id="detected-city"></strong>
    </div>
</div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-sio">
                                <i class="bi bi-shield-check"></i> Update Address (OTP Required)
                            </button>
                        </form>
                    </div>

                    <!-- Change Password Tab -->
                    <div id="password" class="tab-pane fade">
                        <h4 class="mb-4">Change Password</h4>
                        <div id="password-alerts"></div>
                        
                        <div class="info-card">
                            <i class="bi bi-info-circle"></i>
                            <strong>Password Requirements:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Minimum 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one lowercase letter</li>
                                <li>At least one number</li>
                                <li>At least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                        
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label class="form-label">Current Password *</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password *</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" required>
                                <div id="password-strength-bar" class="password-strength mt-2"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                <small id="password-match-text" class="form-text"></small>
                            </div>
                            
                            <button type="submit" class="btn btn-sio">
                                <i class="bi bi-shield-check"></i> Change Password (OTP Required)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Your Identity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="otp-alerts"></div>
                    
                    <p class="text-center mb-4">
                        We've sent a verification code to<br>
                        <strong><?= htmlspecialchars($user['email']) ?></strong>
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label">Enter 6-Digit OTP Code</label>
                        <input type="text" class="form-control text-center" id="otp_code" maxlength="6" pattern="\d{6}" placeholder="000000" style="font-size: 24px; letter-spacing: 10px;">
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-sio w-100" onclick="verifyAndUpdate()">
                            <i class="bi bi-check-circle"></i> Verify & Update
                        </button>
                        
                        <div class="mt-3">
                            <small class="text-muted">Didn't receive the code?</small>
                            <button type="button" class="btn btn-link btn-sm" onclick="resendOTP()">
                                Resend OTP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../headfoot/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentFormData = null;
        let currentChangeType = null;
        const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));

// --- START: Postal Code Logic ---

const ncrPostalCodes = {
    // Caloocan
    '1400': 'Caloocan', '1401': 'Caloocan', '1402': 'Caloocan', '1403': 'Caloocan', '1404': 'Caloocan', 
    '1405': 'Caloocan', '1406': 'Caloocan', '1407': 'Caloocan', '1408': 'Caloocan', '1409': 'Caloocan', 
    '1410': 'Caloocan', '1411': 'Caloocan', '1412': 'Caloocan', '1413': 'Caloocan', '1420': 'Caloocan', 
    '1421': 'Caloocan', '1422': 'Caloocan', '1423': 'Caloocan', '1424': 'Caloocan', '1425': 'Caloocan', 
    '1426': 'Caloocan', '1427': 'Caloocan', '1428': 'Caloocan',
    // Las Piñas
    '1740': 'Las Piñas', '1741': 'Las Piñas', '1742': 'Las Piñas', '1743': 'Las Piñas', '1744': 'Las Piñas', 
    '1745': 'Las Piñas', '1746': 'Las Piñas', '1747': 'Las Piñas', '1748': 'Las Piñas', '1749': 'Las Piñas', 
    '1750': 'Las Piñas', '1751': 'Las Piñas', '1752': 'Las Piñas',
    // Makati
    '1200': 'Makati', '1201': 'Makati', '1202': 'Makati', '1203': 'Makati', '1204': 'Makati', 
    '1205': 'Makati', '1206': 'Makati', '1207': 'Makati', '1208': 'Makati', '1209': 'Makati', 
    '1210': 'Makati', '1211': 'Makati', '1212': 'Makati', '1213': 'Makati', '1214': 'Makati', 
    '1215': 'Makati', '1216': 'Makati', '1217': 'Makati', '1218': 'Makati', '1219': 'Makati', 
    '1220': 'Makati', '1221': 'Makati', '1222': 'Makati', '1223': 'Makati', '1224': 'Makati', 
    '1225': 'Makati', '1226': 'Makati', '1227': 'Makati', '1228': 'Makati', '1229': 'Makati', 
    '1230': 'Makati', '1231': 'Makati', '1232': 'Makati', '1233': 'Makati', '1234': 'Makati', '1235': 'Makati',
    // Malabon
    '1470': 'Malabon', '1471': 'Malabon', '1472': 'Malabon', '1473': 'Malabon', '1474': 'Malabon', 
    '1475': 'Malabon', '1476': 'Malabon', '1477': 'Malabon', '1478': 'Malabon', '1479': 'Malabon',
    // Mandaluyong
    '1550': 'Mandaluyong', '1551': 'Mandaluyong', '1552': 'Mandaluyong', '1553': 'Mandaluyong', '1554': 'Mandaluyong', 
    '1555': 'Mandaluyong', '1556': 'Mandaluyong', '1557': 'Mandaluyong', '1558': 'Mandaluyong', '1559': 'Mandaluyong', 
    '1560': 'Mandaluyong', '1561': 'Mandaluyong', '1562': 'Mandaluyong', '1563': 'Mandaluyong', '1564': 'Mandaluyong', 
    '1565': 'Mandaluyong', '1566': 'Mandaluyong', '1567': 'Mandaluyong', '1568': 'Mandaluyong', '1569': 'Mandaluyong',
    // Manila
    '1000': 'Manila', '1001': 'Manila', '1002': 'Manila', '1003': 'Manila', '1004': 'Manila', 
    '1005': 'Manila', '1006': 'Manila', '1007': 'Manila', '1008': 'Manila', '1009': 'Manila', 
    '1010': 'Manila', '1011': 'Manila', '1012': 'Manila', '1013': 'Manila', '1014': 'Manila', 
    '1015': 'Manila', '1016': 'Manila', '1017': 'Manila', '1018': 'Manila',
    // Marikina
    '1800': 'Marikina', '1801': 'Marikina', '1802': 'Marikina', '1803': 'Marikina', '1804': 'Marikina', 
    '1805': 'Marikina', '1806': 'Marikina', '1807': 'Marikina', '1808': 'Marikina', '1809': 'Marikina', 
    '1810': 'Marikina', '1811': 'Marikina', '1812': 'Marikina', '1813': 'Marikina', '1814': 'Marikina', 
    '1815': 'Marikina', '1816': 'Marikina', '1817': 'Marikina', '1818': 'Marikina', '1819': 'Marikina', 
    '1820': 'Marikina',
    // Muntinlupa
    '1770': 'Muntinlupa', '1771': 'Muntinlupa', '1772': 'Muntinlupa', '1773': 'Muntinlupa', '1774': 'Muntinlupa', 
    '1775': 'Muntinlupa', '1776': 'Muntinlupa', '1777': 'Muntinlupa', '1778': 'Muntinlupa', '1779': 'Muntinlupa', 
    '1780': 'Muntinlupa', '1781': 'Muntinlupa', '1782': 'Muntinlupa', '1783': 'Muntinlupa', '1784': 'Muntinlupa', 
    '1785': 'Muntinlupa', '1786': 'Muntinlupa', '1787': 'Muntinlupa', '1788': 'Muntinlupa', '1789': 'Muntinlupa', 
    '1790': 'Muntinlupa',
    // Navotas
    '1485': 'Navotas', '1486': 'Navotas', '1487': 'Navotas', '1488': 'Navotas', '1489': 'Navotas', 
    '1490': 'Navotas',
    // Parañaque
    '1700': 'Parañaque', '1701': 'Parañaque', '1702': 'Parañaque', '1703': 'Parañaque', '1704': 'Parañaque', 
    '1705': 'Parañaque', '1706': 'Parañaque', '1707': 'Parañaque', '1708': 'Parañaque', '1709': 'Parañaque', 
    '1710': 'Parañaque', '1711': 'Parañaque', '1712': 'Parañaque', '1713': 'Parañaque', '1714': 'Parañaque', 
    '1715': 'Parañaque', '1716': 'Parañaque', '1717': 'Parañaque', '1718': 'Parañaque', '1719': 'Parañaque', 
    '1720': 'Parañaque',
    // Pasay
    '1300': 'Pasay', '1301': 'Pasay', '1302': 'Pasay', '1303': 'Pasay', '1304': 'Pasay', 
    '1305': 'Pasay', '1306': 'Pasay', '1307': 'Pasay', '1308': 'Pasay', '1309': 'Pasay',
    // Pasig
    '1600': 'Pasig', '1601': 'Pasig', '1602': 'Pasig', '1603': 'Pasig', '1604': 'Pasig', 
    '1605': 'Pasig', '1606': 'Pasig', '1607': 'Pasig', '1608': 'Pasig', '1609': 'Pasig', 
    '1610': 'Pasig', '1611': 'Pasig', '1612': 'Pasig',
    // Quezon City
    '1100': 'Quezon City', '1101': 'Quezon City', '1102': 'Quezon City', '1103': 'Quezon City', '1104': 'Quezon City', 
    '1105': 'Quezon City', '1106': 'Quezon City', '1107': 'Quezon City', '1108': 'Quezon City', '1109': 'Quezon City', 
    '1110': 'Quezon City', '1111': 'Quezon City', '1112': 'Quezon City', '1113': 'Quezon City', '1114': 'Quezon City', 
    '1115': 'Quezon City', '1116': 'Quezon City', '1117': 'Quezon City', '1118': 'Quezon City', '1119': 'Quezon City', 
    '1120': 'Quezon City', '1121': 'Quezon City', '1122': 'Quezon City', '1123': 'Quezon City', '1124': 'Quezon City', 
    '1125': 'Quezon City', '1126': 'Quezon City', '1127': 'Quezon City',
    // San Juan
    '1500': 'San Juan', '1501': 'San Juan', '1502': 'San Juan', '1503': 'San Juan', '1504': 'San Juan',
    // Taguig
    '1630': 'Taguig', '1631': 'Taguig', '1632': 'Taguig', '1633': 'Taguig', '1634': 'Taguig', 
    '1635': 'Taguig', '1636': 'Taguig', '1637': 'Taguig', '1638': 'Taguig', '1639': 'Taguig', 
    '1640': 'Taguig',
    // Valenzuela
    '1440': 'Valenzuela', '1441': 'Valenzuela', '1442': 'Valenzuela', '1443': 'Valenzuela', '1444': 'Valenzuela', 
    '1445': 'Valenzuela', '1446': 'Valenzuela', '1447': 'Valenzuela', '1448': 'Valenzuela', '1449': 'Valenzuela', 
    '1469': 'Valenzuela'
};

const postalCodeInput = document.getElementById('postal_code');
const citySelect = document.getElementById('city');
const postalInfoDiv = document.getElementById('postal-info');
const detectedCitySpan = document.getElementById('detected-city');

postalCodeInput.addEventListener('input', function() {
    const postalCode = this.value;
    if (postalCode.length === 4) {
        const city = ncrPostalCodes[postalCode];
        if (city) {
            citySelect.value = city;
            detectedCitySpan.textContent = `City Detected: ${city}`;
            postalInfoDiv.style.display = 'block';
        } else {
            citySelect.value = ''; // Reset city
            detectedCitySpan.textContent = 'Invalid NCR postal code';
            postalInfoDiv.style.display = 'block';
        }
    } else {
        postalInfoDiv.style.display = 'none';
    }
});

// --- END: Postal Code Logic ---


        // Profile Photo Upload
        document.getElementById('profilePhotoInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size must be less than 5MB');
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePhotoPreview');
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-photo';
                    img.id = 'profilePhotoPreview';
                    preview.replaceWith(img);
                }
            };
            reader.readAsDataURL(file);
            
            // Upload photo
            const formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('action', 'upload_photo');
            
            try {
                const response = await fetch('profile_otp_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('personal-info-alerts', 'Profile photo updated successfully!', 'success');
                } else {
                    showAlert('personal-info-alerts', data.message, 'danger');
                }
            } catch (error) {
                showAlert('personal-info-alerts', 'Error uploading photo: ' + error.message, 'danger');
            }
        });

        // Personal Info Form Handler
        document.getElementById('personalInfoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            currentChangeType = 'personal_info';
            currentFormData = new FormData(this);
            await sendOTP();
        });

// Handle Address Form submission
        document.getElementById('addressForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // --- ADD VALIDATION HERE ---
            const postalCode = document.getElementById('postal_code').value;
            if (!ncrPostalCodes[postalCode]) {
                showAlert('address-alerts', 'Please enter a valid 4-digit NCR postal code.', 'danger');
                return;
            }
            // --- END VALIDATION ---
            
            // Set the global variables
            currentChangeType = 'address';
            currentFormData = new FormData(this);

            // Call the correct function
            await sendOTP();
        });
        // Password Form Handler
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                showAlert('password-alerts', 'Passwords do not match', 'danger');
                return;
            }
            
            // Validate password strength
            const errors = validatePassword(newPassword);
            if (errors.length > 0) {
                showAlert('password-alerts', errors.join('<br>'), 'danger');
                return;
            }
            
            currentChangeType = 'password';
            currentFormData = new FormData(this);
            await sendOTP();
        });

        // Send OTP
        async function sendOTP() {
            try {
                currentFormData.append('action', 'send_otp');
                currentFormData.append('change_type', currentChangeType);
                
                const response = await fetch('profile_otp_handler.php', {
                    method: 'POST',
                    body: currentFormData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    otpModal.show();
                    document.getElementById('otp_code').value = '';
                    document.getElementById('otp_code').focus();
                    showAlert('otp-alerts', 'OTP sent successfully!', 'success');
                } else {
                    showAlert(currentChangeType === 'personal_info' ? 'personal-info-alerts' : 
                             currentChangeType === 'address' ? 'address-alerts' : 'password-alerts', 
                             data.message, 'danger');
                }
            } catch (error) {
                showAlert(currentChangeType === 'personal_info' ? 'personal-info-alerts' : 
                         currentChangeType === 'address' ? 'address-alerts' : 'password-alerts', 
                         'Error sending OTP: ' + error.message, 'danger');
            }
        }

        // Verify OTP and Update
        async function verifyAndUpdate() {
            const otpCode = document.getElementById('otp_code').value;
            
            if (otpCode.length !== 6) {
                showAlert('otp-alerts', 'Please enter a valid 6-digit OTP code', 'danger');
                return;
            }
            
            try {
                currentFormData.set('action', 'verify_and_update');
                currentFormData.set('otp_code', otpCode);
                
                const response = await fetch('profile_otp_handler.php', {
                    method: 'POST',
                    body: currentFormData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    otpModal.hide();
                    showAlert(currentChangeType === 'personal_info' ? 'personal-info-alerts' : 
                             currentChangeType === 'address' ? 'address-alerts' : 'password-alerts', 
                             data.message, 'success');
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert('otp-alerts', data.message, 'danger');
                }
            } catch (error) {
                showAlert('otp-alerts', 'Error verifying OTP: ' + error.message, 'danger');
            }
        }

        // Resend OTP
        async function resendOTP() {
            await sendOTP();
        }

        // Show Alert
        function showAlert(containerId, message, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        // Password Validation
        function validatePassword(password) {
            const errors = [];
            
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long');
            }
            if (!/[A-Z]/.test(password)) {
                errors.push('Password must contain at least one uppercase letter');
            }
            if (!/[a-z]/.test(password)) {
                errors.push('Password must contain at least one lowercase letter');
            }
            if (!/[0-9]/.test(password)) {
                errors.push('Password must contain at least one number');
            }
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                errors.push('Password must contain at least one special character');
            }
            
            return errors;
        }

        // Password Strength Indicator
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength mt-2';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        // Password Match Indicator
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('password-match-text');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#dc3545';
            }
        });

        // OTP Input: Only allow numbers
        document.getElementById('otp_code').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>