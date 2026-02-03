<?php
// Lost and Found Functions
// Handles all database operations for lost and found items

require_once __DIR__ . '/config.php';

/**
 * Generate unique item ID
 */
function generateItemId($conn) {
    $prefix = 'LF-';
    $query = "SELECT TOP 1 item_id FROM lost_found_items ORDER BY item_id DESC";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $lastId = intval(substr($row['item_id'], 3));
            $newId = $prefix . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newId = $prefix . '1001';
        }
    } catch (PDOException $e) {
        error_log("generateItemId error: " . $e->getMessage());
        $newId = $prefix . '1001';
    }
    
    return $newId;
}

/**
 * Add a new lost or found item
 */
function addLostFoundItem($conn, $data) {
    $item_id = generateItemId($conn);
    
    $query = "INSERT INTO lost_found_items (
        item_id, item_name, category, description, found_location, 
        date_found, time_found, finder_name, finder_student_id, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed')";
    
    $params = [
        $item_id,
        $data['item_name'],
        $data['category'],
        $data['description'],
        $data['location'],
        $data['date_found'],
        $data['time_found'] ?? null,
        $data['finder_name'] ?? null,
        $data['finder_student_id'] ?? null
    ];
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'item_id' => $item_id,
            'message' => 'Item added successfully'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Failed to add item: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all items with optional filters
 */
function getLostFoundItems($conn, $filters = []) {
    $query = "SELECT 
        lf.*,
        s1.first_name + ' ' + s1.last_name AS finder_full_name,
        s2.first_name + ' ' + s2.last_name AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    WHERE lf.is_archived = 0";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $query .= " AND lf.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['category'])) {
        $query .= " AND lf.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (lf.item_name LIKE ? OR lf.description LIKE ? OR lf.found_location LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND lf.date_found >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND lf.date_found <= ?";
        $params[] = $filters['date_to'];
    }
    
    $query .= " ORDER BY lf.date_found DESC, lf.created_at DESC";
    
    $items = [];
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Format dates
            if ($row['date_found'] instanceof DateTime) {
                $row['date_found'] = $row['date_found']->format('Y-m-d');
            }
            if ($row['date_claimed'] instanceof DateTime) {
                $row['date_claimed'] = $row['date_claimed']->format('Y-m-d');
            }
            if ($row['time_found'] instanceof DateTime) {
                $row['time_found'] = $row['time_found']->format('H:i');
            }
            $items[] = $row;
        }
    } catch (PDOException $e) {
        error_log("getLostFoundItems error: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get single item by ID
 */
function getItemById($conn, $item_id) {
    $query = "SELECT 
        lf.*,
        s1.first_name + ' ' + s1.last_name AS finder_full_name,
        s2.first_name + ' ' + s2.last_name AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    WHERE lf.item_id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute([$item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Format dates
            if ($row['date_found'] instanceof DateTime) {
                $row['date_found'] = $row['date_found']->format('Y-m-d');
            }
            if ($row['date_claimed'] instanceof DateTime) {
                $row['date_claimed'] = $row['date_claimed']->format('Y-m-d');
            }
            if ($row['time_found'] instanceof DateTime) {
                $row['time_found'] = $row['time_found']->format('H:i');
            }
            return $row;
        }
    } catch (PDOException $e) {
        error_log("getItemById error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Update item details
 */
function updateItem($conn, $item_id, $data) {
    $query = "UPDATE lost_found_items SET 
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
        $data['description'],
        $data['location'],
        $data['date_found'],
        $data['time_found'] ?? null,
        $data['finder_name'] ?? null,
        $data['finder_student_id'] ?? null,
        $item_id
    ];
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return ['success' => true, 'message' => 'Item updated successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to update item: ' . $e->getMessage()];
    }
}

/**
 * Mark item as claimed
 */
function markAsClaimed($conn, $item_id, $claimer_data) {
    $query = "UPDATE lost_found_items SET 
        status = 'Claimed',
        claimer_name = ?,
        claimer_student_id = ?,
        date_claimed = CAST(GETDATE() AS DATE),
        updated_at = GETDATE()
    WHERE item_id = ?";
    
    $params = [
        $claimer_data['claimer_name'],
        $claimer_data['claimer_student_id'] ?? null,
        $item_id
    ];
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return ['success' => true, 'message' => 'Item marked as claimed'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to mark item as claimed: ' . $e->getMessage()];
    }
}

/**
 * Mark item as unclaimed (reverse claim)
 */
function markAsUnclaimed($conn, $item_id) {
    $query = "UPDATE lost_found_items SET 
        status = 'Unclaimed',
        claimer_name = NULL,
        claimer_student_id = NULL,
        date_claimed = NULL,
        updated_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute([$item_id]);
        return ['success' => true, 'message' => 'Item marked as unclaimed'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to mark item as unclaimed: ' . $e->getMessage()];
    }
}

/**
 * Search for matching items (public search)
 */
function searchLostItems($conn, $searchTerm, $category = null) {
    $query = "SELECT 
        item_id, item_name, category, description, found_location, 
        date_found, status
    FROM lost_found_items
    WHERE is_archived = 0 
    AND status = 'Unclaimed'
    AND (item_name LIKE ? OR description LIKE ? OR found_location LIKE ?)";
    
    $params = [
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ];
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY date_found DESC";
    
    $items = [];
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['date_found'] instanceof DateTime) {
                $row['date_found'] = $row['date_found']->format('Y-m-d');
            }
            $items[] = $row;
        }
    } catch (PDOException $e) {
        error_log("searchLostItems error: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get statistics
 */
function getLostFoundStats($conn) {
    $stats = [
        'total' => 0,
        'unclaimed' => 0,
        'claimed' => 0,
        'recent' => 0
    ];
    
    try {
        // Total items
        $query = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['total'] = $row['count'];
        }
        
        // Unclaimed
        $query = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0 AND status = 'Unclaimed'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['unclaimed'] = $row['count'];
        }
        
        // Claimed
        $query = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 0 AND status = 'Claimed'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['claimed'] = $row['count'];
        }
        
        // Recent (last 7 days)
        $query = "SELECT COUNT(*) as count FROM lost_found_items 
                  WHERE is_archived = 0 AND date_found >= DATEADD(day, -7, GETDATE())";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['recent'] = $row['count'];
        }
    } catch (PDOException $e) {
        error_log("getLostFoundStats error: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Delete/Archive item
 */
function archiveItem($conn, $item_id) {
    $query = "UPDATE lost_found_items SET 
        is_archived = 1,
        archived_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute([$item_id]);
        return ['success' => true, 'message' => 'Item archived successfully'];
    } catch (PDOException $e) {
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