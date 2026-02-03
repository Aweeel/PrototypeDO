// ====== Modal Utilities ======

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const historyModal = document.getElementById('historyModal');
    const noteModal = document.getElementById('noteModal');
    
    if (event.target === historyModal) {
        closeHistoryModal();
    }
    if (event.target === noteModal) {
        closeNoteModal();
    }
});

// Close modals with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeHistoryModal();
        closeNoteModal();
    }
});

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function closeNoteModal() {
    const modal = document.getElementById('noteModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}