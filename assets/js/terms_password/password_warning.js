// Mark the password warning modal as shown in this login session
async function markPasswordWarningAsShown() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'markPasswordWarningShown');

        await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error marking password warning as shown:', error);
    }
}

// Mark as shown when modal first appears
window.addEventListener('DOMContentLoaded', function() {
    markPasswordWarningAsShown();
});

function closePasswordWarningModal() {
    const modal = document.getElementById('passwordWarningModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside (only on the modal itself, not on child elements)
document.addEventListener('click', function(event) {
    const modal = document.getElementById('passwordWarningModal');
    if (modal && modal.style.display !== 'none' && event.target === modal) {
        closePasswordWarningModal();
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePasswordWarningModal();
    }
});
