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
