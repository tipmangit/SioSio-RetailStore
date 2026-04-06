<?php
/**
 * First-time Address Setup Modal
 * Include this file in pages that need the address setup modal
 * Place in: /siosios/includes/setup_address_modal.php
 */

// Check if user needs to set up address
$needs_address_setup = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_stmt = $con->prepare("SELECT address_line1, city, postal_code FROM userss WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user_data = $result->fetch_assoc();
    $check_stmt->close();
    
    // Check if address is incomplete
    if (empty($user_data['address_line1']) || empty($user_data['city']) || empty($user_data['postal_code'])) {
        $needs_address_setup = true;
    }
}
?>

<?php if ($needs_address_setup): ?>
<!-- First-Time Address Setup Modal -->
<div class="modal fade" id="addressSetupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-house-fill"></i> Welcome! Let's Set Up Your Delivery Address
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Important:</strong> Please set up your delivery address to continue shopping. 
                    This will make checkout faster and easier!
                </div>

                <div id="address-setup-alerts"></div>

                <form id="addressSetupForm">
                    <div class="address-section mb-4">
                        <h6><i class="bi bi-house-door"></i> Street Address</h6>
                        <div class="mb-3">
                            <label class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" name="address_line1" 
                                   placeholder="House/Unit No., Street Name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control" name="address_line2" 
                                   placeholder="Building, Subdivision, Landmark">
                        </div>
                    </div>

                    <div class="address-section mb-4">
                        <h6><i class="bi bi-map"></i> Location Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Barangay *</label>
                                <input type="text" class="form-control" name="barangay" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <select class="form-select" name="city" id="setup-city" required>
                                    <option value="">Select city</option>
                                    <option value="Caloocan">Caloocan</option>
                                    <option value="Las Piñas">Las Piñas</option>
                                    <option value="Makati">Makati</option>
                                    <option value="Malabon">Malabon</option>
                                    <option value="Mandaluyong">Mandaluyong</option>
                                    <option value="Manila">Manila</option>
                                    <option value="Marikina">Marikina</option>
                                    <option value="Muntinlupa">Muntinlupa</option>
                                    <option value="Navotas">Navotas</option>
                                    <option value="Parañaque">Parañaque</option>
                                    <option value="Pasay">Pasay</option>
                                    <option value="Pasig">Pasig</option>
                                    <option value="Quezon City">Quezon City</option>
                                    <option value="San Juan">San Juan</option>
                                    <option value="Taguig">Taguig</option>
                                    <option value="Valenzuela">Valenzuela</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="setup-postal-code" 
                                       name="postal_code" pattern="[0-9]{4}" maxlength="4" required>
                                <div id="setup-postal-info" class="postal-info" style="display: none; background: #e7f5ff; border-left: 4px solid #0d6efd; padding: 10px 15px; border-radius: 5px; margin-top: 10px;">
                                    <i class="bi bi-info-circle"></i>
                                    <strong id="setup-detected-city"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger w-100" onclick="submitAddressSetup()">
                    <i class="bi bi-check-circle"></i> Save Address & Continue
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.address-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.address-section h6 {
    color: #dc3545;
    font-weight: 600;
    margin-bottom: 15px;
}

.postal-info {
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
</style>

<script>
// NCR Postal Codes mapping
const ncrPostalCodes = {
    '1400': 'Caloocan', '1401': 'Caloocan', '1402': 'Caloocan', '1403': 'Caloocan', '1404': 'Caloocan', 
    '1405': 'Caloocan', '1406': 'Caloocan', '1407': 'Caloocan', '1408': 'Caloocan', '1409': 'Caloocan', 
    '1410': 'Caloocan', '1411': 'Caloocan', '1412': 'Caloocan', '1413': 'Caloocan', '1420': 'Caloocan', 
    '1421': 'Caloocan', '1422': 'Caloocan', '1423': 'Caloocan', '1424': 'Caloocan', '1425': 'Caloocan', 
    '1426': 'Caloocan', '1427': 'Caloocan', '1428': 'Caloocan',
    '1740': 'Las Piñas', '1741': 'Las Piñas', '1742': 'Las Piñas', '1743': 'Las Piñas', '1744': 'Las Piñas', 
    '1745': 'Las Piñas', '1746': 'Las Piñas', '1747': 'Las Piñas', '1748': 'Las Piñas', '1749': 'Las Piñas', 
    '1750': 'Las Piñas', '1751': 'Las Piñas', '1752': 'Las Piñas',
    '1200': 'Makati', '1201': 'Makati', '1202': 'Makati', '1203': 'Makati', '1204': 'Makati', 
    '1205': 'Makati', '1206': 'Makati', '1207': 'Makati', '1208': 'Makati', '1209': 'Makati', 
    '1210': 'Makati', '1211': 'Makati', '1212': 'Makati', '1213': 'Makati', '1214': 'Makati', 
    '1215': 'Makati', '1216': 'Makati', '1217': 'Makati', '1218': 'Makati', '1219': 'Makati', 
    '1220': 'Makati', '1221': 'Makati', '1222': 'Makati', '1223': 'Makati', '1224': 'Makati', 
    '1225': 'Makati', '1226': 'Makati', '1227': 'Makati', '1228': 'Makati', '1229': 'Makati', 
    '1230': 'Makati', '1231': 'Makati', '1232': 'Makati', '1233': 'Makati', '1234': 'Makati', '1235': 'Makati',
    '1470': 'Malabon', '1471': 'Malabon', '1472': 'Malabon', '1473': 'Malabon', '1474': 'Malabon', 
    '1475': 'Malabon', '1476': 'Malabon', '1477': 'Malabon', '1478': 'Malabon', '1479': 'Malabon',
    '1550': 'Mandaluyong', '1551': 'Mandaluyong', '1552': 'Mandaluyong', '1553': 'Mandaluyong', '1554': 'Mandaluyong', 
    '1555': 'Mandaluyong', '1556': 'Mandaluyong', '1557': 'Mandaluyong', '1558': 'Mandaluyong', '1559': 'Mandaluyong', 
    '1560': 'Mandaluyong', '1561': 'Mandaluyong', '1562': 'Mandaluyong', '1563': 'Mandaluyong', '1564': 'Mandaluyong', 
    '1565': 'Mandaluyong', '1566': 'Mandaluyong', '1567': 'Mandaluyong', '1568': 'Mandaluyong', '1569': 'Mandaluyong',
    '1000': 'Manila', '1001': 'Manila', '1002': 'Manila', '1003': 'Manila', '1004': 'Manila', 
    '1005': 'Manila', '1006': 'Manila', '1007': 'Manila', '1008': 'Manila', '1009': 'Manila', 
    '1010': 'Manila', '1011': 'Manila', '1012': 'Manila', '1013': 'Manila', '1014': 'Manila', 
    '1015': 'Manila', '1016': 'Manila', '1017': 'Manila', '1018': 'Manila',
    '1800': 'Marikina', '1801': 'Marikina', '1802': 'Marikina', '1803': 'Marikina', '1804': 'Marikina', 
    '1805': 'Marikina', '1806': 'Marikina', '1807': 'Marikina', '1808': 'Marikina', '1809': 'Marikina', 
    '1810': 'Marikina', '1811': 'Marikina', '1812': 'Marikina', '1813': 'Marikina', '1814': 'Marikina', 
    '1815': 'Marikina', '1816': 'Marikina', '1817': 'Marikina', '1818': 'Marikina', '1819': 'Marikina', 
    '1820': 'Marikina',
    '1770': 'Muntinlupa', '1771': 'Muntinlupa', '1772': 'Muntinlupa', '1773': 'Muntinlupa', '1774': 'Muntinlupa', 
    '1775': 'Muntinlupa', '1776': 'Muntinlupa', '1777': 'Muntinlupa', '1778': 'Muntinlupa', '1779': 'Muntinlupa', 
    '1780': 'Muntinlupa', '1781': 'Muntinlupa', '1782': 'Muntinlupa', '1783': 'Muntinlupa', '1784': 'Muntinlupa', 
    '1785': 'Muntinlupa', '1786': 'Muntinlupa', '1787': 'Muntinlupa', '1788': 'Muntinlupa', '1789': 'Muntinlupa', 
    '1790': 'Muntinlupa',
    '1485': 'Navotas', '1486': 'Navotas', '1487': 'Navotas', '1488': 'Navotas', '1489': 'Navotas', 
    '1490': 'Navotas',
    '1700': 'Parañaque', '1701': 'Parañaque', '1702': 'Parañaque', '1703': 'Parañaque', '1704': 'Parañaque', 
    '1705': 'Parañaque', '1706': 'Parañaque', '1707': 'Parañaque', '1708': 'Parañaque', '1709': 'Parañaque', 
    '1710': 'Parañaque', '1711': 'Parañaque', '1712': 'Parañaque', '1713': 'Parañaque', '1714': 'Parañaque', 
    '1715': 'Parañaque', '1716': 'Parañaque', '1717': 'Parañaque', '1718': 'Parañaque', '1719': 'Parañaque', 
    '1720': 'Parañaque',
    '1300': 'Pasay', '1301': 'Pasay', '1302': 'Pasay', '1303': 'Pasay', '1304': 'Pasay', 
    '1305': 'Pasay', '1306': 'Pasay', '1307': 'Pasay', '1308': 'Pasay', '1309': 'Pasay',
    '1600': 'Pasig', '1601': 'Pasig', '1602': 'Pasig', '1603': 'Pasig', '1604': 'Pasig', 
    '1605': 'Pasig', '1606': 'Pasig', '1607': 'Pasig', '1608': 'Pasig', '1609': 'Pasig', 
    '1610': 'Pasig', '1611': 'Pasig', '1612': 'Pasig',
    '1100': 'Quezon City', '1101': 'Quezon City', '1102': 'Quezon City', '1103': 'Quezon City', '1104': 'Quezon City', 
    '1105': 'Quezon City', '1106': 'Quezon City', '1107': 'Quezon City', '1108': 'Quezon City', '1109': 'Quezon City', 
    '1110': 'Quezon City', '1111': 'Quezon City', '1112': 'Quezon City', '1113': 'Quezon City', '1114': 'Quezon City', 
    '1115': 'Quezon City', '1116': 'Quezon City', '1117': 'Quezon City', '1118': 'Quezon City', '1119': 'Quezon City', 
    '1120': 'Quezon City', '1121': 'Quezon City', '1122': 'Quezon City', '1123': 'Quezon City', '1124': 'Quezon City', 
    '1125': 'Quezon City', '1126': 'Quezon City', '1127': 'Quezon City',
    '1500': 'San Juan', '1501': 'San Juan', '1502': 'San Juan', '1503': 'San Juan', '1504': 'San Juan',
    '1630': 'Taguig', '1631': 'Taguig', '1632': 'Taguig', '1633': 'Taguig', '1634': 'Taguig', 
    '1635': 'Taguig', '1636': 'Taguig', '1637': 'Taguig', '1638': 'Taguig', '1639': 'Taguig', 
    '1640': 'Taguig',
    '1440': 'Valenzuela', '1441': 'Valenzuela', '1442': 'Valenzuela', '1443': 'Valenzuela', '1444': 'Valenzuela', 
    '1445': 'Valenzuela', '1446': 'Valenzuela', '1447': 'Valenzuela', '1448': 'Valenzuela', '1449': 'Valenzuela', 
    '1469': 'Valenzuela'
};

// Show modal on page load if needed
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($needs_address_setup): ?>
    const addressModal = new bootstrap.Modal(document.getElementById('addressSetupModal'));
    addressModal.show();
    <?php endif; ?>
    
    // Postal code validation
    const postalInput = document.getElementById('setup-postal-code');
    const citySelect = document.getElementById('setup-city');
    const postalInfo = document.getElementById('setup-postal-info');
    const detectedCity = document.getElementById('setup-detected-city');
    
    if (postalInput) {
        postalInput.addEventListener('input', function() {
            const postalCode = this.value;
            if (postalCode.length === 4) {
                const city = ncrPostalCodes[postalCode];
                if (city) {
                    citySelect.value = city;
                    detectedCity.textContent = `City Detected: ${city}`;
                    postalInfo.style.display = 'block';
                } else {
                    citySelect.value = '';
                    detectedCity.textContent = 'Invalid NCR postal code';
                    postalInfo.style.display = 'block';
                }
            } else {
                postalInfo.style.display = 'none';
            }
        });
    }
});

// Submit address setup
async function submitAddressSetup() {
    const form = document.getElementById('addressSetupForm');
    const formData = new FormData(form);
    
    // Validate
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Validate postal code
    const postalCode = formData.get('postal_code');
    if (!ncrPostalCodes[postalCode]) {
        showAddressSetupAlert('Please enter a valid 4-digit NCR postal code.', 'danger');
        return;
    }
    
    try {
        const response = await fetch('../Profile/save_address_setup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAddressSetupAlert('Address saved successfully! Redirecting...', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAddressSetupAlert(data.message || 'Error saving address', 'danger');
        }
    } catch (error) {
        showAddressSetupAlert('Error: ' + error.message, 'danger');
    }
}

function showAddressSetupAlert(message, type) {
    const alertDiv = document.getElementById('address-setup-alerts');
    alertDiv.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}
</script>
<?php endif; ?>