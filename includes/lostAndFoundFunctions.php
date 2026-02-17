<?php
// Lost and Found Functions
// Handles all database operations for lost and found items

require_once __DIR__ . '/config.php';

/**
 * Generate unique item ID
 */
function generateItemId() {
    $prefix = 'LF-';
    $sql = "SELECT TOP 1 item_id FROM lost_found_items ORDER BY item_id DESC";
    
    try {
        $result = fetchOne($sql);
        
        if ($result) {
            $lastId = intval(substr($result['item_id'], 3));
            $newId = $prefix . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newId = $prefix . '1001';
        }
    } catch (Exception $e) {
        error_log("generateItemId error: " . $e->getMessage());
        $newId = $prefix . '1001';
    }
    
    return $newId;
}

/**
 * Add a new lost or found item
 */
function addLostFoundItem($data) {
    $item_id = generateItemId();
    
    // Convert empty strings to NULL
    $time_found = (isset($data['time_found']) && trim($data['time_found']) !== '') ? $data['time_found'] : null;
    $finder_name = (isset($data['finder_name']) && trim($data['finder_name']) !== '') ? $data['finder_name'] : null;
    $finder_student_id = (isset($data['finder_student_id']) && trim($data['finder_student_id']) !== '') ? $data['finder_student_id'] : null;
    $description = (isset($data['description']) && trim($data['description']) !== '') ? $data['description'] : null;
    
    // Build SQL based on whether time is provided (like calendar.php)
    if ($time_found !== null) {
        $sql = "INSERT INTO lost_found_items (
            item_id, item_name, category, description, found_location, 
            date_found, time_found, finder_name, finder_student_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed')";
        
        $params = [
            $item_id,
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $time_found,
            $finder_name,
            $finder_student_id
        ];
    } else {
        $sql = "INSERT INTO lost_found_items (
            item_id, item_name, category, description, found_location, 
            date_found, finder_name, finder_student_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed')";
        
        $params = [
            $item_id,
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $finder_name,
            $finder_student_id
        ];
    }
    
    // Debug logging
    error_log("addLostFoundItem SQL: $sql");
    error_log("addLostFoundItem Params: " . print_r($params, true));
    
    try {
        executeQuery($sql, $params);
        
        return [
            'success' => true,
            'item_id' => $item_id,
            'message' => 'Item added successfully'
        ];
    } catch (Exception $e) {
        error_log("addLostFoundItem error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add item: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all items with optional filters
 */
function getLostFoundItems($filters = []) {
    $sql = "SELECT 
        lf.*,
        s1.first_name + ' ' + s1.last_name AS finder_full_name,
        s2.first_name + ' ' + s2.last_name AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    WHERE lf.is_archived = 0";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND lf.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['category'])) {
        $sql .= " AND lf.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (lf.item_name LIKE ? OR lf.description LIKE ? OR lf.found_location LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND lf.date_found >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND lf.date_found <= ?";
        $params[] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY lf.date_found DESC, lf.created_at DESC";
    
    try {
        $items = fetchAll($sql, $params);
        
        // Format dates if needed
        foreach ($items as &$item) {
            if ($item['date_found'] instanceof DateTime) {
                $item['date_found'] = $item['date_found']->format('Y-m-d');
            }
            if ($item['date_claimed'] instanceof DateTime) {
                $item['date_claimed'] = $item['date_claimed']->format('Y-m-d');
            }
            if ($item['time_found'] instanceof DateTime) {
                $item['time_found'] = $item['time_found']->format('H:i');
            }
        }
        
        return $items;
    } catch (Exception $e) {
        error_log("getLostFoundItems error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single item by ID
 */
function getItemById($item_id) {
    $sql = "SELECT 
        lf.*,
        s1.first_name + ' ' + s1.last_name AS finder_full_name,
        s2.first_name + ' ' + s2.last_name AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    WHERE lf.item_id = ?";
    
    try {
        $item = fetchOne($sql, [$item_id]);
        
        if ($item) {
            // Format dates
            if ($item['date_found'] instanceof DateTime) {
                $item['date_found'] = $item['date_found']->format('Y-m-d');
            }
            if ($item['date_claimed'] instanceof DateTime) {
                $item['date_claimed'] = $item['date_claimed']->format('Y-m-d');
            }
            if ($item['time_found'] instanceof DateTime) {
                $item['time_found'] = $item['time_found']->format('H:i');
            }
            return $item;
        }
    } catch (Exception $e) {
        error_log("getItemById error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Update item details
 */
function updateItem($item_id, $data) {
    // Convert empty strings to NULL
    $time_found = (isset($data['time_found']) && trim($data['time_found']) !== '') ? $data['time_found'] : null;
    $finder_name = (isset($data['finder_name']) && trim($data['finder_name']) !== '') ? $data['finder_name'] : null;
    $finder_student_id = (isset($data['finder_student_id']) && trim($data['finder_student_id']) !== '') ? $data['finder_student_id'] : null;
    $description = (isset($data['description']) && trim($data['description']) !== '') ? $data['description'] : null;
    
    // Build SQL based on whether time is provided (like calendar.php)
    if ($time_found !== null) {
        $sql = "UPDATE lost_found_items SET 
            item_name = ?,
            category = ?,
            description = ?,
            found_location = ?,
            date_found = ?,
            time_found = ?,
            finder_name = ?,
            finder_student_id = ?,
            updated_at = GETDATE()
        WHERE item_id = ?";
        
        $params = [
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $time_found,
            $finder_name,
            $finder_student_id,
            $item_id
        ];
    } else {
        $sql = "UPDATE lost_found_items SET 
            item_name = ?,
            category = ?,
            description = ?,
            found_location = ?,
            date_found = ?,
            time_found = NULL,
            finder_name = ?,
            finder_student_id = ?,
            updated_at = GETDATE()
        WHERE item_id = ?";
        
        $params = [
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $finder_name,
            $finder_student_id,
            $item_id
        ];
    }
    
    // Debug logging
    error_log("updateItem SQL: $sql");
    error_log("updateItem Params: " . print_r($params, true));
    
    try {
        executeQuery($sql, $params);
        return ['success' => true, 'message' => 'Item updated successfully'];
    } catch (Exception $e) {
        error_log("updateItem error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update item: ' . $e->getMessage()];
    }
}

/**
 * Mark item as claimed
 */
function markAsClaimed($item_id, $claimer_data) {
    $sql = "UPDATE lost_found_items SET 
        status = 'Claimed',
        claimer_name = ?,
        claimer_student_id = ?,
        date_claimed = CAST(GETDATE() AS DATE),
        updated_at = GETDATE()
    WHERE item_id = ?";
    
    // Convert empty student ID to NULL
    $claimer_student_id = !empty($claimer_data['claimer_student_id']) ? $claimer_data['claimer_student_id'] : null;
    
    $params = [
        $claimer_data['claimer_name'],
        $claimer_student_id,
        $item_id
    ];
    
    try {
        executeQuery($sql, $params);
        return ['success' => true, 'message' => 'Item marked as claimed'];
    } catch (Exception $e) {
        error_log("markAsClaimed error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to mark item as claimed: ' . $e->getMessage()];
    }
}

/**
 * Mark item as unclaimed (reverse claim)
 */
function markAsUnclaimed($item_id) {
    $sql = "UPDATE lost_found_items SET 
        status = 'Unclaimed',
        claimer_name = NULL,
        claimer_student_id = NULL,
        date_claimed = NULL,
        updated_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        executeQuery($sql, [$item_id]);
        return ['success' => true, 'message' => 'Item marked as unclaimed'];
    } catch (Exception $e) {
        error_log("markAsUnclaimed error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to mark item as unclaimed: ' . $e->getMessage()];
    }
}

/**
 * Search for matching items (public search)
 * Returns LIMITED information to prevent false claiming
 */
function searchLostItems($searchTerm, $category = null) {
    $sql = "SELECT 
        item_id,
        item_name,
        category,
        found_location,
        date_found
    FROM lost_found_items
    WHERE is_archived = 0 
    AND status = 'Unclaimed'
    AND (item_name LIKE ? OR category LIKE ?)";
    
    $params = [
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ];
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY date_found DESC";
    
    try {
        $items = fetchAll($sql, $params);
        
        // Format dates and sanitize data for public display
        foreach ($items as &$item) {
            if ($item['date_found'] instanceof DateTime) {
                $item['date_found'] = $item['date_found']->format('Y-m-d');
            }
            
            // Add a security note for display
            $item['claim_note'] = 'To claim this item, visit the Discipline Office with valid proof of ownership and your School ID.';
        }
        
        return $items;
    } catch (Exception $e) {
        error_log("searchLostItems error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get statistics
 */
function getLostFoundStats() {
    $stats = [
        'total' => 0,
        'unclaimed' => 0,
        'claimed' => 0,
        'recent' => 0
    ];
    
    try {
        // Total items
        $sql = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0";
        $result = fetchOne($sql);
        if ($result) {
            $stats['total'] = $result['count'];
        }
        
        // Unclaimed
        $sql = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0 AND status = 'Unclaimed'";
        $result = fetchOne($sql);
        if ($result) {
            $stats['unclaimed'] = $result['count'];
        }
        
        // Claimed
        $sql = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0 AND status = 'Claimed'";
        $result = fetchOne($sql);
        if ($result) {
            $stats['claimed'] = $result['count'];
        }
        
        // Recent (last 7 days)
        $sql = "SELECT COUNT(*) as count FROM lost_found_items 
                WHERE is_archived = 0 AND date_found >= DATEADD(day, -7, GETDATE())";
        $result = fetchOne($sql);
        if ($result) {
            $stats['recent'] = $result['count'];
        }
    } catch (Exception $e) {
        error_log("getLostFoundStats error: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Delete/Archive item
 */
function archiveItem($item_id) {
    $sql = "UPDATE lost_found_items SET 
        is_archived = 1,
        archived_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        executeQuery($sql, [$item_id]);
        return ['success' => true, 'message' => 'Item archived successfully'];
    } catch (Exception $e) {
        error_log("archiveItem error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to archive item: ' . $e->getMessage()];
    }
}

/**
 * Get available categories
 */
function getCategories() {
    return [
        'Electronics',
        'Books',
        'Accessories',
        'Clothing',
        'ID/Documents',
        'Keys',
        'Sports Equipment',
        'Personal Items',
        'School Supplies',
        'Others'
    ];
}
?>