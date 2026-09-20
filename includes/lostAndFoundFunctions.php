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
 * Handle image file upload for Lost & Found items
 * 
 * @param array $file - $_FILES array element
 * @param string $item_id - Item ID for naming the file
 * @return array - ['success' => bool, 'path' => string or 'error' => string]
 */
function handleLostFoundImageUpload($file, $item_id) {
    // Define upload directory
    $uploadDir = __DIR__ . '/../assets/case_attachments/lost_found/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No valid file uploaded'];
    }
    
    // Check file size (5MB limit)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
    }
    
    // Validate MIME type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only images are allowed'];
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $item_id . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return relative path for database storage
        $relativePath = '/PrototypeDO/assets/case_attachments/lost_found/' . $filename;
        return ['success' => true, 'path' => $relativePath];
    } else {
        error_log("Failed to move uploaded file: " . $file['tmp_name'] . " to " . $filepath);
        return ['success' => false, 'error' => 'Failed to save file'];
    }
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
    $image_path = (isset($data['image_path']) && trim($data['image_path']) !== '') ? $data['image_path'] : null;
    
    // Build SQL based on whether time is provided
    if ($time_found !== null) {
        $sql = "INSERT INTO lost_found_items (
            item_id, item_name, category, description, found_location, 
            date_found, time_found, finder_name, finder_student_id, status, image_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed', ?)";
        
        $params = [
            $item_id,
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $time_found,
            $finder_name,
            $finder_student_id,
            $image_path
        ];
    } else {
        $sql = "INSERT INTO lost_found_items (
            item_id, item_name, category, description, found_location, 
            date_found, finder_name, finder_student_id, status, image_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed', ?)";
        
        $params = [
            $item_id,
            $data['item_name'],
            $data['category'],
            $description,
            $data['location'],
            $data['date_found'],
            $finder_name,
            $finder_student_id,
            $image_path
        ];
    }
    
    // Debug logging
    error_log("addLostFoundItem SQL: $sql");
    error_log("addLostFoundItem Params: " . print_r($params, true));
    
    try {
        executeQuery($sql, $params);
        
        // 🧾 Audit Log
        auditLostItemAdded($item_id, $data['item_name'], [
            'category' => $data['category'],
            'location' => $data['location'],
            'date_found' => $data['date_found'],
            'finder_name' => $finder_name,
            'status' => 'Unclaimed'
        ]);
        
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
 * Pass $filters['archived'] = true to fetch archived items instead of active ones.
 */
function getLostFoundItems($filters = []) {
    // Run auto-archive check for unclaimed items older than 30 days
    checkAndAutoArchiveLostFound();

    $archivedFlag = !empty($filters['archived']) ? 1 : 0;

    $sql = "SELECT 
        lf.*,
        s1.first_name + ' ' + s1.last_name AS finder_full_name,
        COALESCE(s2.first_name + ' ' + s2.last_name, u2.full_name) AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    LEFT JOIN users u2 ON lf.claimer_student_id = u2.teacher_id OR lf.claimer_student_id = u2.do_id
    WHERE lf.is_archived = ?";
    
    $params = [$archivedFlag];
    
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
    
    $sql .= $archivedFlag
        ? " ORDER BY lf.archived_at DESC"
        : " ORDER BY lf.date_found DESC, lf.created_at DESC";
    
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
            if (!empty($item['archived_at']) && $item['archived_at'] instanceof DateTime) {
                $item['archived_at'] = $item['archived_at']->format('Y-m-d H:i:s');
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
        COALESCE(s2.first_name + ' ' + s2.last_name, u2.full_name) AS claimer_full_name
    FROM lost_found_items lf
    LEFT JOIN students s1 ON lf.finder_student_id = s1.student_id
    LEFT JOIN students s2 ON lf.claimer_student_id = s2.student_id
    LEFT JOIN users u2 ON lf.claimer_student_id = u2.teacher_id OR lf.claimer_student_id = u2.do_id
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
            if (!empty($item['archived_at']) && $item['archived_at'] instanceof DateTime) {
                $item['archived_at'] = $item['archived_at']->format('Y-m-d H:i:s');
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
    
    // Build SQL based on whether time is provided
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
        // Get old data for audit comparison
        $oldItem = getItemById($item_id);
        
        executeQuery($sql, $params);
        
        // Get new data after update
        $newItem = getItemById($item_id);
        
        // 🧾 Audit Log
        auditLostItemUpdated($item_id, 
            [
                'item_name' => $oldItem['item_name'],
                'category' => $oldItem['category'],
                'location' => $oldItem['found_location'],
                'date_found' => $oldItem['date_found']
            ],
            [
                'item_name' => $newItem['item_name'],
                'category' => $newItem['category'],
                'location' => $newItem['found_location'],
                'date_found' => $newItem['date_found']
            ]
        );
        
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
    // Get old data for audit
    $oldItem = getItemById($item_id);
    
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
        
        // 🧾 Audit Log
        auditLostItemClaimed($item_id, $claimer_student_id, $claimer_data['claimer_name']);
        
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
    // Get old data for audit
    $oldItem = getItemById($item_id);
    $claimer_name = $oldItem['claimer_name'] ?? 'Unknown';
    
    $sql = "UPDATE lost_found_items SET 
        status = 'Unclaimed',
        claimer_name = NULL,
        claimer_student_id = NULL,
        date_claimed = NULL,
        updated_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        executeQuery($sql, [$item_id]);
        
        // 🧾 Audit Log
        auditLostItemUnclaimed($item_id, $oldItem['claimer_student_id'] ?? null, $claimer_name);
        
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
        'recent' => 0,
        'archived' => 0
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

        // Archived
        $sql = "SELECT COUNT(*) as count FROM lost_found_items WHERE is_archived = 1";
        $result = fetchOne($sql);
        if ($result) {
            $stats['archived'] = $result['count'];
        }
    } catch (Exception $e) {
        error_log("getLostFoundStats error: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Archive item
 */
function archiveItem($item_id) {
    // Get item name for audit logging
    $item = getItemById($item_id);
    $item_name = $item['item_name'] ?? 'Unknown Item';
    
    $sql = "UPDATE lost_found_items SET 
        is_archived = 1,
        archived_at = GETDATE()
    WHERE item_id = ?";
    
    try {
        executeQuery($sql, [$item_id]);
        
        // 🧾 Audit Log
        auditLostItemArchived($item_id, $item_name);
        
        return ['success' => true, 'message' => 'Item archived successfully'];
    } catch (Exception $e) {
        error_log("archiveItem error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to archive item: ' . $e->getMessage()];
    }
}

/**
 * Restore an archived item back to active
 */
function unarchiveItem($item_id) {
    $item = getItemById($item_id);
    $item_name = $item['item_name'] ?? 'Unknown Item';

    $sql = "UPDATE lost_found_items SET 
        is_archived = 0,
        archived_at = NULL
    WHERE item_id = ?";

    try {
        executeQuery($sql, [$item_id]);

        // 🧾 Audit Log
        auditLostItemUnarchived($item_id, $item_name);

        return ['success' => true, 'message' => 'Item restored from archive'];
    } catch (Exception $e) {
        error_log("unarchiveItem error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to restore item: ' . $e->getMessage()];
    }
}

/**
 * Audit log for item restored from archive
 */
function auditLostItemUnarchived($item_id, $item_name) {
    $userId = $_SESSION['user_id'] ?? null;

    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found', 'UNARCHIVE_ITEM', ?, ?)";

    try {
        executeQuery($sql, [
            $userId,
            'Archived',
            'Item: ' . $item_name . ' (' . $item_id . ')'
        ]);
    } catch (Exception $e) {
        error_log("auditLostItemUnarchived error: " . $e->getMessage());
    }
}

/**
 * Get available categories from database
 */
function getCategories() {
    $sql = "SELECT category_name FROM lost_found_categories WHERE is_active = 1 ORDER BY category_name";
    
    try {
        $categories = fetchAll($sql);
        
        // Extract just the category names
        $categoryNames = [];
        foreach ($categories as $cat) {
            $categoryNames[] = $cat['category_name'];
        }
        
        return $categoryNames;
    } catch (Exception $e) {
        error_log("getCategories error: " . $e->getMessage());
        // Return default categories as fallback
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
}

/**
 * Get all categories with full details
 */
function getAllCategories($includeInactive = false) {
    $sql = "SELECT * FROM lost_found_categories";
    
    if (!$includeInactive) {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY category_name";
    
    try {
        return fetchAll($sql);
    } catch (Exception $e) {
        error_log("getAllCategories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a new category
 */
function addCategory($categoryName, $description = null) {
    // Validate input
    $categoryName = trim($categoryName);
    if (empty($categoryName)) {
        return [
            'success' => false,
            'message' => 'Category name cannot be empty'
        ];
    }
    
    // Check for duplicates (case-insensitive)
    $sql = "SELECT COUNT(*) as count FROM lost_found_categories WHERE LOWER(category_name) = LOWER(?)";
    try {
        $result = fetchOne($sql, [$categoryName]);
        if ($result && $result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Category already exists'
            ];
        }
    } catch (Exception $e) {
        error_log("addCategory duplicate check error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error occurred'
        ];
    }
    
    // Insert new category
    $sql = "INSERT INTO lost_found_categories (category_name, description) VALUES (?, ?)";
    try {
        executeQuery($sql, [$categoryName, $description]);
        
        // Get the inserted category ID
        $selectSql = "SELECT TOP 1 category_id FROM lost_found_categories WHERE category_name = ? ORDER BY created_at DESC";
        $result = fetchOne($selectSql, [$categoryName]);
        $categoryId = $result['category_id'] ?? null;
        
        // 🧾 Audit Log
        auditCategoryAdded($categoryName, $description);
        
        return [
            'success' => true,
            'message' => 'Category added successfully',
            'category_name' => $categoryName,
            'category_id' => $categoryId
        ];
    } catch (Exception $e) {
        error_log("addCategory error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add category: ' . $e->getMessage()
        ];
    }
}

/**
 * Update a category
 */
function updateCategory($categoryId, $categoryName, $description = null) {
    // Validate input
    $categoryName = trim($categoryName);
    if (empty($categoryName)) {
        return [
            'success' => false,
            'message' => 'Category name cannot be empty'
        ];
    }
    
    // Check for duplicate names (excluding current category)
    $sql = "SELECT COUNT(*) as count FROM lost_found_categories WHERE category_name = ? AND category_id != ?";
    try {
        $result = fetchOne($sql, [$categoryName, $categoryId]);
        if ($result && $result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Category name already exists'
            ];
        }
    } catch (Exception $e) {
        error_log("updateCategory duplicate check error: " . $e->getMessage());
    }
    
    // Update category
    $sql = "UPDATE lost_found_categories SET category_name = ?, description = ?, updated_at = GETDATE() WHERE category_id = ?";
    try {
        executeQuery($sql, [$categoryName, $description, $categoryId]);
        
        // 🧾 Audit Log
        auditCategoryUpdated($categoryId, $categoryName);
        
        return [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
    } catch (Exception $e) {
        error_log("updateCategory error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update category: ' . $e->getMessage()
        ];
    }
}

/**
 * Deactivate a category
 */
function deactivateCategory($categoryId) {
    $sql = "UPDATE lost_found_categories SET is_active = 0, updated_at = GETDATE() WHERE category_id = ?";
    try {
        executeQuery($sql, [$categoryId]);
        
        // 🧾 Audit Log
        auditCategoryDeactivated($categoryId);
        
        return [
            'success' => true,
            'message' => 'Category deactivated successfully'
        ];
    } catch (Exception $e) {
        error_log("deactivateCategory error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to deactivate category: ' . $e->getMessage()
        ];
    }
}

/**
 * Reactivate a category
 */
function reactivateCategory($categoryId) {
    $sql = "UPDATE lost_found_categories SET is_active = 1, updated_at = GETDATE() WHERE category_id = ?";
    try {
        executeQuery($sql, [$categoryId]);
        
        // 🧾 Audit Log
        auditCategoryReactivated($categoryId);
        
        return [
            'success' => true,
            'message' => 'Category reactivated successfully'
        ];
    } catch (Exception $e) {
        error_log("reactivateCategory error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to reactivate category: ' . $e->getMessage()
        ];
    }
}

/**
 * Get category by ID
 */
function getCategoryById($categoryId) {
    $sql = "SELECT * FROM lost_found_categories WHERE category_id = ?";
    try {
        return fetchOne($sql, [$categoryId]);
    } catch (Exception $e) {
        error_log("getCategoryById error: " . $e->getMessage());
        return null;
    }
}

/**
 * Audit log for category added
 */
function auditCategoryAdded($categoryName, $description = null) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value, notes)
            VALUES (?, 'Lost & Found Categories', 'ADD_CATEGORY', NULL, ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            $categoryName,
            $description ?? ''
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryAdded error: " . $e->getMessage());
    }
}

/**
 * Audit log for category updated
 */
function auditCategoryUpdated($categoryId, $categoryName) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found Categories', 'UPDATE_CATEGORY', ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            'Category ID: ' . $categoryId,
            $categoryName
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryUpdated error: " . $e->getMessage());
    }
}

/**
 * Audit log for category deactivated
 */
function auditCategoryDeactivated($categoryId) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found Categories', 'DEACTIVATE_CATEGORY', ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            'Category ID: ' . $categoryId,
            'Deactivated'
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryDeactivated error: " . $e->getMessage());
    }
}

/**
 * Delete a category (permanent deletion)
 */
function deleteCategory($categoryId) {
    // First, get the category name
    $getCategorySql = "SELECT category_name FROM lost_found_categories WHERE category_id = ?";
    try {
        $categoryResult = fetchOne($getCategorySql, [$categoryId]);
        if (!$categoryResult) {
            return [
                'success' => false,
                'message' => 'Category not found'
            ];
        }
        $categoryName = $categoryResult['category_name'];
    } catch (Exception $e) {
        error_log("deleteCategory lookup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error occurred'
        ];
    }
    
    // Check if category has any items
    $checkSql = "SELECT COUNT(*) as count FROM lost_found_items WHERE category = ?";
    try {
        $countResult = fetchOne($checkSql, [$categoryName]);
        if ($countResult && $countResult['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category with items',
                'code' => 'HAS_ITEMS',
                'itemCount' => $countResult['count']
            ];
        }
    } catch (Exception $e) {
        error_log("deleteCategory count check error: " . $e->getMessage());
    }
    
    // Delete category
    $sql = "DELETE FROM lost_found_categories WHERE category_id = ?";
    try {
        executeQuery($sql, [$categoryId]);
        
        // 🧾 Audit Log
        auditCategoryDeleted($categoryId);
        
        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
    } catch (Exception $e) {
        error_log("deleteCategory error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to delete category: ' . $e->getMessage()
        ];
    }
}

/**
 * Audit log for category deleted
 */
function auditCategoryDeleted($categoryId) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found Categories', 'DELETE_CATEGORY', ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            'Category ID: ' . $categoryId,
            'Deleted'
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryDeleted error: " . $e->getMessage());
    }
}

/**
 * Delete a category by name
 */
function deleteCategoryByName($categoryName) {
    $categoryName = trim($categoryName);
    
    if (empty($categoryName)) {
        return [
            'success' => false,
            'message' => 'Category name cannot be empty'
        ];
    }
    
    // Get category ID first for audit logging
    $selectSql = "SELECT category_id FROM lost_found_categories WHERE category_name = ?";
    try {
        $result = fetchOne($selectSql, [$categoryName]);
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Category not found'
            ];
        }
        $categoryId = $result['category_id'];
    } catch (Exception $e) {
        error_log("deleteCategoryByName lookup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error occurred'
        ];
    }
    
    // Check if category has any items
    $checkSql = "SELECT COUNT(*) as count FROM lost_found_items WHERE category = ?";
    try {
        $countResult = fetchOne($checkSql, [$categoryName]);
        if ($countResult && $countResult['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category with items',
                'code' => 'HAS_ITEMS',
                'itemCount' => $countResult['count']
            ];
        }
    } catch (Exception $e) {
        error_log("deleteCategoryByName count check error: " . $e->getMessage());
    }
    
    // Delete category
    $sql = "DELETE FROM lost_found_categories WHERE category_id = ?";
    try {
        executeQuery($sql, [$categoryId]);
        
        // 🧾 Audit Log
        auditCategoryDeletedByName($categoryId, $categoryName);
        
        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
    } catch (Exception $e) {
        error_log("deleteCategoryByName error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to delete category: ' . $e->getMessage()
        ];
    }
}

/**
 * Audit log for category deleted by name
 */
function auditCategoryDeletedByName($categoryId, $categoryName) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found Categories', 'DELETE_CATEGORY', ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            'Category: ' . $categoryName . ' (ID: ' . $categoryId . ')',
            'Deleted'
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryDeletedByName error: " . $e->getMessage());
    }
}

/**
 * Audit log for category reactivated
 */
function auditCategoryReactivated($categoryId) {
    $userId = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, old_value, new_value)
            VALUES (?, 'Lost & Found Categories', 'REACTIVATE_CATEGORY', ?, ?)";
    
    try {
        executeQuery($sql, [
            $userId,
            'Category ID: ' . $categoryId,
            'Reactivated'
        ]);
    } catch (Exception $e) {
        error_log("auditCategoryReactivated error: " . $e->getMessage());
    }
}

/**
 * Automatically archive lost & found items that have been unclaimed for 1 month (30+ days)
 */
function autoArchiveLostFoundItems() {
    $sql = "UPDATE lost_found_items 
            SET is_archived = 1, 
                archived_at = GETDATE()
            WHERE is_archived = 0 
              AND status = 'Unclaimed'
              AND date_found IS NOT NULL
              AND DATEDIFF(day, date_found, GETDATE()) >= 30";
    
    try {
        executeQuery($sql);
        
        $countSql = "SELECT @@ROWCOUNT as archived_count";
        $count = fetchValue($countSql);
        
        if ($count > 0) {
            error_log("Auto-archived $count lost & found items older than 30 days.");
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("Error in autoArchiveLostFoundItems: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check and auto-archive items once per session/day
 */
function checkAndAutoArchiveLostFound() {
    $today = date('Y-m-d');
    if (!isset($_SESSION['auto_archive_lf_date']) || $_SESSION['auto_archive_lf_date'] !== $today) {
        $archivedCount = autoArchiveLostFoundItems();
        $_SESSION['auto_archive_lf_date'] = $today;
        return $archivedCount;
    }
    return 0;
}
