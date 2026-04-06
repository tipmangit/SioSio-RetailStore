<?php
/**
 * CMS Helper Functions
 * Include this file in pages that need CMS content
 */

/**
 * Get CMS content by page and section name
 * 
 * @param mysqli $con Database connection
 * @param string $page_name Page name (homepage, about, contact)
 * @param string $section_name Section name
 * @param string $default Default value if not found
 * @return string Content value
 */
function getCMSContent($con, $page_name, $section_name, $default = '') {
    static $cache = [];
    
    $cache_key = $page_name . '_' . $section_name;
    
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    $stmt = $con->prepare("SELECT content_value FROM cms_content WHERE page_name = ? AND section_name = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("ss", $page_name, $section_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $cache[$cache_key] = $row['content_value'];
        $stmt->close();
        return $row['content_value'];
    }
    
    $stmt->close();
    return $default;
}

/**
 * Get all CMS content for a specific page
 * 
 * @param mysqli $con Database connection
 * @param string $page_name Page name
 * @return array Associative array of section_name => content_value
 */
function getAllCMSContent($con, $page_name) {
    $content = [];
    
    $stmt = $con->prepare("SELECT section_name, content_value FROM cms_content WHERE page_name = ? AND is_active = 1 ORDER BY display_order, section_name");
    $stmt->bind_param("s", $page_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $content[$row['section_name']] = $row['content_value'];
    }
    
    $stmt->close();
    return $content;
}

/**
 * Check if CMS content exists and is active
 * 
 * @param mysqli $con Database connection
 * @param string $page_name Page name
 * @param string $section_name Section name
 * @return bool
 */
function cmsContentExists($con, $page_name, $section_name) {
    $stmt = $con->prepare("SELECT id FROM cms_content WHERE page_name = ? AND section_name = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("ss", $page_name, $section_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Output CMS content with optional fallback
 * Automatically escapes HTML unless content_type is 'html'
 * 
 * @param mysqli $con Database connection
 * @param string $page_name Page name
 * @param string $section_name Section name
 * @param string $default Default value
 * @param bool $escape Whether to escape HTML (default: true)
 */
function echoCMS($con, $page_name, $section_name, $default = '', $escape = true) {
    $content = getCMSContent($con, $page_name, $section_name, $default);
    
    // Check if content type is HTML
    $stmt = $con->prepare("SELECT content_type FROM cms_content WHERE page_name = ? AND section_name = ? LIMIT 1");
    $stmt->bind_param("ss", $page_name, $section_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $is_html = ($row['content_type'] === 'html');
    } else {
        $is_html = false;
    }
    $stmt->close();
    
    // Output content
    if ($is_html || !$escape) {
        echo $content;
    } else {
        echo htmlspecialchars($content);
    }
}
?>