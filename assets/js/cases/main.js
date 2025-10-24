document.addEventListener('DOMContentLoaded', () => {
    renderCases();
    updatePaginationInfo();

    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(i => i.setAttribute('max', today));
});
