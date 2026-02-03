// Lost & Found Management JavaScript

// Modal management
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addItemForm').reset();
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

// Close modals on outside click
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const viewModal = document.getElementById('viewModal');
    const editModal = document.getElementById('editModal');
    const claimModal = document.getElementById('claimModal');
    
    if (event.target === addModal) closeAddModal();
    if (viewModal && event.target === viewModal) closeViewModal();
    if (editModal && event.target === editModal) closeEditModal();
    if (claimModal && event.target === claimModal) closeClaimModal();
}

// Add new item
document.getElementById('addItemForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add');
    
    try {
        const response = await fetch('/PrototypeDO/modules/do/lostAndFoundAPI.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success!', 'Item added successfully', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Failed to add item', 'error');
    }
});

// View item details
async function viewItem(itemId) {
    try {
        const response = await fetch(`/PrototypeDO/modules/do/lostAndFoundAPI.php?action=get&item_id=${itemId}`);
        const result = await response.json();
        
        if (result.success) {
            showViewModal(result.data);
        } else {
            showNotification('Error', 'Failed to load item details', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Failed to load item details', 'error');
    }
}

function showViewModal(item) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('viewModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'viewModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-eye mr-2 text-blue-600 dark:text-blue-400"></i>
                        Item Details
                    </h3>
                </div>
                <div id="viewModalContent" class="p-6"></div>
                <div class="p-6 border-t border-gray-200 dark:border-slate-700">
                    <button onclick="closeViewModal()" 
                            class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Close
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Format date
    const dateFound = new Date(item.date_found).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const dateClaimed = item.date_claimed ? new Date(item.date_claimed).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) : 'N/A';
    
    // Populate content
    document.getElementById('viewModalContent').innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Item ID</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">${item.item_id}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full ${
                        item.status === 'Claimed' 
                            ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' 
                            : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'
                    }">
                        ${item.status}
                    </span>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Item Name</label>
                <p class="text-lg text-gray-900 dark:text-gray-100">${item.item_name}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Category</label>
                <p class="text-gray-900 dark:text-gray-100">${item.category}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
                <p class="text-gray-900 dark:text-gray-100">${item.description || 'No description provided'}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Location Found</label>
                    <p class="text-gray-900 dark:text-gray-100">${item.found_location}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date Found</label>
                    <p class="text-gray-900 dark:text-gray-100">${dateFound}</p>
                </div>
            </div>
            
            ${item.time_found ? `
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Time Found</label>
                    <p class="text-gray-900 dark:text-gray-100">${item.time_found}</p>
                </div>
            ` : ''}
            
            ${item.finder_name ? `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Finder Name</label>
                        <p class="text-gray-900 dark:text-gray-100">${item.finder_name}</p>
                    </div>
                    ${item.finder_student_id ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Finder Student ID</label>
                            <p class="text-gray-900 dark:text-gray-100">${item.finder_student_id}</p>
                        </div>
                    ` : ''}
                </div>
            ` : ''}
            
            ${item.status === 'Claimed' ? `
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                        Claim Information
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Claimer Name</label>
                            <p class="text-gray-900 dark:text-gray-100">${item.claimer_name || 'N/A'}</p>
                        </div>
                        ${item.claimer_student_id ? `
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Claimer Student ID</label>
                                <p class="text-gray-900 dark:text-gray-100">${item.claimer_student_id}</p>
                            </div>
                        ` : ''}
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date Claimed</label>
                            <p class="text-gray-900 dark:text-gray-100">${dateClaimed}</p>
                        </div>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal')?.classList.add('hidden');
}

// Edit item
async function editItem(itemId) {
    try {
        const response = await fetch(`/PrototypeDO/modules/do/lostAndFoundAPI.php?action=get&item_id=${itemId}`);
        const result = await response.json();
        
        if (result.success) {
            showEditModal(result.data);
        } else {
            showNotification('Error', 'Failed to load item details', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Failed to load item details', 'error');
    }
}

function showEditModal(item) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('editModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'editModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        document.body.appendChild(modal);
    }
    
    // Get categories from page
    const categorySelect = document.querySelector('select[name="category"]');
    const categories = Array.from(categorySelect.options).map(opt => opt.value).filter(v => v);
    
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-edit mr-2 text-green-600 dark:text-green-400"></i>
                    Edit Item
                </h3>
            </div>
            <form id="editItemForm" class="p-6 space-y-4">
                <input type="hidden" name="item_id" value="${item.item_id}">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item Name *</label>
                        <input type="text" name="item_name" required value="${item.item_name}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category *</label>
                        <select name="category" required 
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            ${categories.map(cat => `<option value="${cat}" ${cat === item.category ? 'selected' : ''}>${cat}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Found *</label>
                        <input type="date" name="date_found" required value="${item.date_found}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Found</label>
                        <input type="time" name="time_found" value="${item.time_found || ''}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location Found *</label>
                        <input type="text" name="location" required value="${item.found_location}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Finder Name</label>
                        <input type="text" name="finder_name" value="${item.finder_name || ''}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Finder Student ID</label>
                        <input type="text" name="finder_student_id" value="${item.finder_student_id || ''}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">${item.description || ''}</textarea>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Attach submit handler
    document.getElementById('editItemForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update');
        
        try {
            const response = await fetch('/PrototypeDO/modules/do/lostAndFoundAPI.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Success!', 'Item updated successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Error', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error', 'Failed to update item', 'error');
        }
    });
}

function closeEditModal() {
    document.getElementById('editModal')?.classList.add('hidden');
}

// Mark as claimed
function markClaimed(itemId) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('claimModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'claimModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-hand-holding mr-2 text-purple-600 dark:text-purple-400"></i>
                    Mark as Claimed
                </h3>
            </div>
            <form id="claimItemForm" class="p-6 space-y-4">
                <input type="hidden" name="item_id" value="${itemId}">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Claimer Name *</label>
                    <input type="text" name="claimer_name" required placeholder="Full name of person claiming item"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Claimer Student ID (Optional)</label>
                    <input type="text" name="claimer_student_id" placeholder="Optional"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-check mr-2"></i>Mark as Claimed
                    </button>
                    <button type="button" onclick="closeClaimModal()" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Attach submit handler
    document.getElementById('claimItemForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'mark_claimed');
        
        try {
            const response = await fetch('/PrototypeDO/modules/do/lostAndFoundAPI.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Success!', 'Item marked as claimed', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification('Error', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error', 'Failed to mark item as claimed', 'error');
        }
    });
}

function closeClaimModal() {
    document.getElementById('claimModal')?.classList.add('hidden');
}

// Mark as unclaimed
async function markUnclaimed(itemId) {
    if (!confirm('Are you sure you want to mark this item as unclaimed?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'mark_unclaimed');
    formData.append('item_id', itemId);
    
    try {
        const response = await fetch('/PrototypeDO/modules/do/lostAndFoundAPI.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success!', 'Item marked as unclaimed', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Failed to mark item as unclaimed', 'error');
    }
}

// Notification system
function showNotification(title, message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };
    
    notification.className += ` ${colors[type]} text-white`;
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="fas ${icons[type]} text-2xl"></i>
            <div>
                <div class="font-semibold">${title}</div>
                <div class="text-sm">${message}</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}