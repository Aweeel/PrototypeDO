// // =========================
// // Audit Log Filters Script
// // =========================

// // Store current filter states
// let currentFilters = {
//     search: '',
//     actionType: '',
//     user: '',
//     dateFrom: '',
//     dateTo: ''
// };

// // When the page is ready
// document.addEventListener('DOMContentLoaded', () => {
//     loadUsers();
//     loadActionTypes();
// });

// // -------------------------
// // Load dropdown filters
// // -------------------------
// async function loadUsers() {
//     try {
//         const formData = new FormData();
//         formData.append('ajax', '1');
//         formData.append('action', 'getUsers');

//         const response = await fetch(window.location.href, { method: 'POST', body: formData });
//         const data = await response.json();

//         if (data.success) {
//             const userFilter = document.getElementById('userFilter');
//             data.users.forEach(user => {
//                 const option = document.createElement('option');
//                 option.value = user.user_id;
//                 option.textContent = user.name;
//                 userFilter.appendChild(option);
//             });
//         }
//     } catch (error) {
//         console.error('Error loading users:', error);
//     }
// }

// async function loadActionTypes() {
//     try {
//         const formData = new FormData();
//         formData.append('ajax', '1');
//         formData.append('action', 'getActionTypes');

//         const response = await fetch(window.location.href, { method: 'POST', body: formData });
//         const data = await response.json();

//         if (data.success) {
//             const actionFilter = document.getElementById('actionTypeFilter');
//             data.actionTypes.forEach(action => {
//                 const option = document.createElement('option');
//                 option.value = action.action;
//                 option.textContent = action.action;
//                 actionFilter.appendChild(option);
//             });
//         }
//     } catch (error) {
//         console.error('Error loading action types:', error);
//     }
// }

// // -------------------------
// // Filtering logic
// // -------------------------
// function filterLogs() {
//     currentFilters.search = document.getElementById('searchInput').value.trim();
//     currentFilters.actionType = document.getElementById('actionTypeFilter').value;
//     currentFilters.user = document.getElementById('userFilter').value;
//     refreshLogs();
// }

// // -------------------------
// // Date range modal controls
// // -------------------------
// function openAdvancedFilters() {
//     document.getElementById('dateRangeModal').classList.remove('hidden');
// }

// function closeDateRangeModal() {
//     document.getElementById('dateRangeModal').classList.add('hidden');
// }

// function applyDateFilter() {
//     const from = document.getElementById('dateFrom').value;
//     const to = document.getElementById('dateTo').value;

//     currentFilters.dateFrom = from;
//     currentFilters.dateTo = to;

//     closeDateRangeModal();
//     refreshLogs();
// }

// function clearDateFilter() {
//     document.getElementById('dateFrom').value = '';
//     document.getElementById('dateTo').value = '';
//     currentFilters.dateFrom = '';
//     currentFilters.dateTo = '';
//     refreshLogs();
// }

// // -------------------------
// // Refresh log table
// // -------------------------
// async function refreshLogs() {
//     try {
//         const formData = new FormData();
//         formData.append('ajax', '1');
//         formData.append('action', 'getAuditLogs');
//         formData.append('search', currentFilters.search);
//         formData.append('actionType', currentFilters.actionType);
//         formData.append('user', currentFilters.user);
//         formData.append('dateFrom', currentFilters.dateFrom);
//         formData.append('dateTo', currentFilters.dateTo);

//         const response = await fetch(window.location.href, { method: 'POST', body: formData });
//         const data = await response.json();

//         if (data.success) {
//             allLogs = data.logs;
//             filteredLogs = [...allLogs];
//             currentPage = 1;
//             renderTable();
//             updatePagination();
//         } else {
//             console.error('Failed to load audit logs.');
//         }
//     } catch (error) {
//         console.error('Error refreshing logs:', error);
//     }
// }
