function filterCases() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    filteredCases = allCases.filter(c => {
        const matchesSearch = c.student.toLowerCase().includes(searchTerm) ||
                              c.id.toLowerCase().includes(searchTerm) ||
                              c.type.toLowerCase().includes(searchTerm);
        const matchesType = !typeFilter || c.type === typeFilter;
        const matchesStatus = !statusFilter || c.status === statusFilter;
        return matchesSearch && matchesType && matchesStatus;
    });

    currentPage = 1;
    renderCases();
}
