// ====== Modal Utilities ======

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const historyModal = document.getElementById('historyModal');
    const importModal = document.getElementById('importModal');
    
    if (event.target === historyModal) {
        closeHistoryModal();
    }
    if (event.target === importModal) {
        closeImportModal();
    }
});

// Close modals with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeHistoryModal();
        closeImportModal();
    }
});

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function openImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('csvFile').value = '';
        document.getElementById('importResult').classList.add('hidden');
        document.getElementById('importResult').innerHTML = '';
    }
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}