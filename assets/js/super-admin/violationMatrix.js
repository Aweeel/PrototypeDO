function openCatalogModal(type, data = null) {
    const form = document.getElementById('catalogForm');
    const isOffense = type === 'offense';
    document.getElementById('catalogModal').classList.remove('hidden');
    document.getElementById('catalogModalTitle').textContent = `${data ? 'Edit' : 'Add'} ${isOffense ? 'violation' : 'sanction'}`;
    document.getElementById('catalogAction').value = isOffense ? 'save_offense' : 'save_sanction';
    const offenseFields = document.getElementById('offenseFields');
    const sanctionFields = document.getElementById('sanctionFields');
    offenseFields.classList.toggle('hidden', !isOffense);
    sanctionFields.classList.toggle('hidden', isOffense);
    offenseFields.querySelectorAll('input, select, textarea').forEach((control) => { control.disabled = !isOffense; });
    sanctionFields.querySelectorAll('input, select, textarea').forEach((control) => { control.disabled = isOffense; });
    document.getElementById('sanction_name').required = !isOffense;
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
    requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
    document.querySelectorAll('input[name="active_tab"]').forEach((input) => { input.value = tab.dataset.tabTarget; });
    moveCatalogToolbar(tab.dataset.tabTarget);
}));
document.querySelectorAll('form[method="post"]').forEach((form) => form.addEventListener('submit', () => {
    const activePanel = document.querySelector('#violationCatalogPanel:not(.hidden), #penaltyGuidelinesPanel:not(.hidden)');
    const activeTab = activePanel ? activePanel.id : 'violationCatalogPanel';
    const input = form.querySelector('input[name="active_tab"]');
    if (input) input.value = activeTab;
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
        toolbar.className = 'catalog-toolbar flex w-full min-w-0 items-stretch gap-2 sm:w-auto sm:items-center';
        tabs.appendChild(toolbar);
    }

    if (!search.dataset.toolbarReady) {
        search.dataset.toolbarReady = '1';
        search.dataset.catalogPanel = panelId;
        search.classList.add('min-w-0', 'flex-1', 'sm:w-40', 'sm:flex-none');
        toolbar.appendChild(search);
    }
    if (!addButton.dataset.toolbarReady) {
        addButton.dataset.toolbarReady = '1';
        addButton.dataset.catalogPanel = panelId;
        addButton.classList.add('min-w-0', 'flex-1', 'sm:w-auto', 'sm:flex-none');
        toolbar.appendChild(addButton);
    }

    const sanctionSearch = document.querySelector('.catalog-search[data-row-selector=".sanction-row"]');
    const sanctionButton = document.querySelector('[onclick="openCatalogModal(\'sanction\')"]');
    if (sanctionSearch && !sanctionSearch.dataset.toolbarReady) {
        sanctionSearch.dataset.toolbarReady = '1';
        sanctionSearch.dataset.catalogPanel = 'penaltyGuidelinesPanel';
        sanctionSearch.classList.add('min-w-0', 'flex-1', 'sm:w-40', 'sm:flex-none');
        toolbar.appendChild(sanctionSearch);
    }
    if (sanctionButton && !sanctionButton.dataset.toolbarReady) {
        sanctionButton.dataset.toolbarReady = '1';
        sanctionButton.dataset.catalogPanel = 'penaltyGuidelinesPanel';
        sanctionButton.classList.add('min-w-0', 'flex-1', 'sm:w-auto', 'sm:flex-none');
        toolbar.appendChild(sanctionButton);
    }

    toolbar.querySelectorAll('[data-catalog-panel]').forEach((control) => {
        control.classList.toggle('hidden', control.dataset.catalogPanel !== panelId);
    });
}

function setActiveCatalogTab(tab) {
    document.querySelectorAll('.tab-button').forEach((item) => {
        const isActive = item === tab;
        item.classList.toggle('bg-blue-600', isActive);
        item.classList.toggle('text-white', isActive);
        item.classList.toggle('border-blue-600', isActive);
        item.classList.toggle('bg-white', !isActive);
        item.classList.toggle('dark:bg-[#111827]', !isActive);
        item.classList.toggle('text-gray-700', !isActive);
        item.classList.toggle('dark:text-gray-200', !isActive);
        item.classList.toggle('border-gray-300', !isActive);
        item.classList.toggle('dark:border-slate-600', !isActive);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const initialTab = document.querySelector('.tab-button[class*="bg-blue-600"]') || document.querySelector('.tab-button[data-tab-target="violationCatalogPanel"]');
    if (initialTab) {
        setActiveCatalogTab(initialTab);
        moveCatalogToolbar(initialTab.dataset.tabTarget);
    }
});
