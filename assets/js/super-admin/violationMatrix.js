function openCatalogModal(type, data = null) {
    const form = document.getElementById('catalogForm');
    const isOffense = type === 'offense';
    document.getElementById('catalogModal').classList.remove('hidden');
    document.getElementById('catalogModalTitle').textContent = `${data ? 'Edit' : 'Add'} ${isOffense ? 'violation' : 'sanction'}`;
    document.getElementById('catalogAction').value = isOffense ? 'save_offense' : 'save_sanction';
    document.getElementById('offenseFields').classList.toggle('hidden', !isOffense);
    document.getElementById('sanctionFields').classList.toggle('hidden', isOffense);
    form.reset();
    if (isOffense) {
        document.getElementById('offense_id').value = data?.id || '';
        document.getElementById('offense_name').value = data?.name || '';
        document.getElementById('offense_category').value = data?.category || 'Minor';
        document.getElementById('offense_description').value = data?.description || '';
        document.getElementById('offense_active').checked = data ? data.active === '1' : true;
    } else {
        document.getElementById('sanction_id').value = data?.id || '';
        document.getElementById('sanction_name').value = data?.name || '';
        document.getElementById('severity_level').value = data?.level || 1;
        document.getElementById('sanction_description').value = data?.description || '';
        document.getElementById('sanction_active').checked = data ? data.active === '1' : true;
    }
}
function closeCatalogModal() { document.getElementById('catalogModal').classList.add('hidden'); }
document.querySelectorAll('.edit-offense').forEach((button) => button.addEventListener('click', () => openCatalogModal('offense', button.dataset)));
document.querySelectorAll('.edit-sanction').forEach((button) => button.addEventListener('click', () => openCatalogModal('sanction', button.dataset)));
document.querySelectorAll('[data-tab-target]').forEach((tab) => tab.addEventListener('click', () => {
    setActiveCatalogTab(tab);
    document.querySelectorAll('#violationCatalogPanel, #penaltyGuidelinesPanel').forEach((panel) => panel.classList.toggle('hidden', panel.id !== tab.dataset.tabTarget));
    moveCatalogToolbar(tab.dataset.tabTarget);
}));
document.getElementById('catalogModal')?.addEventListener('click', (event) => { if (event.target.id === 'catalogModal') closeCatalogModal(); });
document.querySelectorAll('.catalog-search').forEach((input) => input.addEventListener('input', () => {
    const query = input.value.toLowerCase().trim();
    document.querySelectorAll(input.dataset.rowSelector).forEach((row) => { row.classList.toggle('hidden', !row.textContent.toLowerCase().includes(query)); });
}));

function moveCatalogToolbar(panelId) {
    const panel = document.getElementById(panelId);
    const tabs = document.querySelector('.tab-button')?.parentElement;
    const rowSelector = panelId === 'violationCatalogPanel' ? '.offense-row' : '.sanction-row';
    const search = document.querySelector(`.catalog-search[data-row-selector="${rowSelector}"]`);
    const addButton = document.querySelector(`[onclick="openCatalogModal('${panelId === 'violationCatalogPanel' ? 'offense' : 'sanction'}')"]`);
    if (!panel || !tabs || !search || !addButton) return;

    let toolbar = document.getElementById('catalogToolbar');
    if (!toolbar) {
        toolbar = document.createElement('div');
        toolbar.id = 'catalogToolbar';
        toolbar.className = 'ml-auto flex items-center gap-2';
        tabs.appendChild(toolbar);
    }

    if (!search.dataset.toolbarReady) {
        search.dataset.toolbarReady = '1';
        search.dataset.catalogPanel = panelId;
        toolbar.appendChild(search);
    }
    if (!addButton.dataset.toolbarReady) {
        addButton.dataset.toolbarReady = '1';
        addButton.dataset.catalogPanel = panelId;
        toolbar.appendChild(addButton);
    }

    const sanctionSearch = document.querySelector('.catalog-search[data-row-selector=".sanction-row"]');
    const sanctionButton = document.querySelector('[onclick="openCatalogModal(\'sanction\')"]');
    if (sanctionSearch && !sanctionSearch.dataset.toolbarReady) {
        sanctionSearch.dataset.toolbarReady = '1';
        sanctionSearch.dataset.catalogPanel = 'penaltyGuidelinesPanel';
        toolbar.appendChild(sanctionSearch);
    }
    if (sanctionButton && !sanctionButton.dataset.toolbarReady) {
        sanctionButton.dataset.toolbarReady = '1';
        sanctionButton.dataset.catalogPanel = 'penaltyGuidelinesPanel';
        toolbar.appendChild(sanctionButton);
    }

    toolbar.querySelectorAll('[data-catalog-panel]').forEach((control) => {
        control.classList.toggle('hidden', control.dataset.catalogPanel !== panelId);
    });
}

function setActiveCatalogTab(tab) {
    document.querySelectorAll('.tab-button').forEach((item) => {
        item.className = item === tab
            ? 'tab-button px-4 py-2 bg-blue-600 text-white rounded-lg font-medium border border-blue-600'
            : 'tab-button px-4 py-2 bg-white dark:bg-[#111827] text-gray-700 dark:text-gray-200 rounded-lg border border-gray-300 dark:border-slate-600';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const initialTab = document.querySelector('.tab-button[data-tab-target="violationCatalogPanel"]');
    if (initialTab) {
        setActiveCatalogTab(initialTab);
        moveCatalogToolbar('violationCatalogPanel');
    }
});
