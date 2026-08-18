// Global variables
let allEvents = [];
let currentDate = new Date();
let selectedCategoryFilter = '';
let pendingEventId = null;
let pendingEventOpened = false;
let currentCalendarView = 'shared';

// Month names
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];

// If the strikethrough line still looks off in your browser after the
// background-based fix, it's no longer a centering bug (background-position:
// center is exact, math can't drift) — it's most likely font rendering on
// your system. Nudge this up/down in 1px steps until it looks right; positive
// moves the line down, negative moves it up.
const STRIKETHROUGH_OFFSET_PX = 0;

// Category colors
const categoryColors = {
    'Meeting': { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-300', border: 'border-blue-500' },
    'Conference': { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-300', border: 'border-green-500' },
    'Hearing': { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-300', border: 'border-red-500' },
    'Deadline': { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-300', border: 'border-yellow-500' },
    'Other': { bg: 'bg-gray-100 dark:bg-gray-900/30', text: 'text-gray-700 dark:text-gray-300', border: 'border-gray-500' }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initializeCalendarFromUrl();
    renderCalendar();
    loadEvents();
    loadCategories();
});

function initializeCalendarFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    pendingEventId = urlParams.get('event_id');
    const eventDate = urlParams.get('event_date');
    currentCalendarView = urlParams.get('calendar_view') || window.CALENDAR_VIEW || 'shared';

    if (eventDate) {
        const parsedDate = new Date(`${eventDate}T00:00:00`);

        if (!Number.isNaN(parsedDate.getTime())) {
            currentDate = parsedDate;
        }
    }
}

// Navigation
function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
    loadEvents();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
    loadEvents();
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
    loadEvents();
}

// Render calendar
function renderCalendar() {
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();

    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const cell = createDayCell(day, true, new Date(year, month - 1, day));
        grid.appendChild(cell);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const cell = createDayCell(day, false, date);
        grid.appendChild(cell);
    }

    // Next month days
    // Decide whether to render 5 or 6 rows (35 or 42 cells)
    const totalNeeded = firstDay + daysInMonth; // prev month blanks + current month days
    const cells = totalNeeded > 35 ? 42 : 35;
    const totalCells = grid.children.length;
    const remainingCells = cells - totalCells;
    for (let day = 1; day <= remainingCells; day++) {
        const cell = createDayCell(day, true, new Date(year, month + 1, day));
        grid.appendChild(cell);
    }
}

// Native text-decoration:line-through renders inconsistently at 12px next to a
// left border (it sits too high). Draw a manually centered line instead.
//
// FIX: earlier versions positioned a separate <span> line with
// `top: 50%; transform: translateY(-50%)` inside a wrapper. That measures
// against the wrapper's own box, which can shift by a pixel depending on
// layout context (e.g. it landed differently in the "today" cell than in
// a plain cell, because of the badge circle changing surrounding layout).
// A CSS background is centered directly against the element's own padding
// box by the layout engine, with no extra child element to mis-measure,
// so it can't drift between cells. `inline` shrinks the box to fit the
// text (for untruncated single-line labels); omit it for elements that
// should already span their full row width (e.g. a truncated event pill).
function renderStrikethroughText(container, text, { inline = false } = {}) {
    container.textContent = text;
    container.style.backgroundImage = 'linear-gradient(currentColor, currentColor)';
    container.style.backgroundRepeat = 'no-repeat';
    container.style.backgroundPosition = `center calc(50% + ${STRIKETHROUGH_OFFSET_PX}px)`;
    container.style.backgroundSize = '100% 1px';
    if (inline) {
        container.style.display = 'inline-block';
        container.style.maxWidth = '100%';
    }
}

// Create day cell - FIXED VERSION
function createDayCell(day, isOtherMonth, date) {
    const cell = document.createElement('div');
    const today = new Date();
    const isToday = date.toDateString() === today.toDateString();

    // Date-only comparison so "past" doesn't depend on time-of-day
    const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const cellDateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const isPastDay = cellDateOnly < todayDateOnly;

    cell.className = `min-h-[120px] p-2 ${isOtherMonth ? 'bg-gray-50 dark:bg-slate-800/50' : 'bg-white dark:bg-[#111827]'} hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors cursor-pointer`;

    const dayNumber = document.createElement('div');
    dayNumber.className = `text-sm font-medium mb-2 ${isToday
        ? 'inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white rounded-full'
        : isOtherMonth
            ? 'text-gray-400'
            : 'text-gray-700 dark:text-gray-300'}`;
    dayNumber.textContent = day;
    cell.appendChild(dayNumber);

    // Add events for this day - FIX: Use local date string
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const dayStr = String(date.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${dayStr}`;

    const dayEvents = allEvents.filter(e => e.date === dateStr && (!selectedCategoryFilter || e.category === selectedCategoryFilter));

    const eventsContainer = document.createElement('div');
    eventsContainer.className = 'space-y-1';

    dayEvents.slice(0, 3).forEach(event => {
        const eventEl = document.createElement('div');
        const colors = categoryColors[event.category] || categoryColors['Other'];
        eventEl.className = `text-xs px-2 py-1 rounded ${colors.bg} ${colors.text} border-l-2 ${colors.border} truncate cursor-pointer hover:opacity-80${isPastDay ? ' opacity-60' : ''}`;

        const label = event.time ? `${event.time} ${event.name}` : event.name;
        if (isPastDay) {
            renderStrikethroughText(eventEl, label);
        } else {
            eventEl.textContent = label;
        }

        eventEl.onclick = (e) => {
            e.stopPropagation();
            viewEvent(event);
        };
        eventsContainer.appendChild(eventEl);
    });

    if (dayEvents.length > 3) {
        const more = document.createElement('div');
        more.className = 'text-xs text-blue-600 dark:text-blue-400 font-medium cursor-pointer';
        more.textContent = `+${dayEvents.length - 3} more`;
        more.onclick = (e) => {
            e.stopPropagation();
            showDayEvents(date, dayEvents);
        };
        eventsContainer.appendChild(more);
    }

    cell.appendChild(eventsContainer);

    // FIX: Pass the date object directly
    cell.onclick = () => openAddEventModal(date);

    return cell;
}

// Compute the date range actually shown on the grid (including leading/trailing
// days from the previous/next month), so we always fetch what's visible.
function getVisibleGridRange(date) {
    const month = date.getMonth();
    const year = date.getFullYear();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Same 35-vs-42-cell decision renderCalendar() makes
    const totalNeeded = firstDay + daysInMonth;
    const cells = totalNeeded > 35 ? 42 : 35;

    const startDate = new Date(year, month, 1 - firstDay);
    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + cells - 1);

    const toDateStr = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    return { startDate: toDateStr(startDate), endDate: toDateStr(endDate) };
}

// Load events
async function loadEvents() {
    try {
        const { startDate, endDate } = getVisibleGridRange(currentDate);

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getEvents');
        formData.append('startDate', startDate);
        formData.append('endDate', endDate);
        formData.append('calendarView', currentCalendarView);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            allEvents = data.events;
            renderCalendar();
            renderUpcomingEvents();
            openPendingEventFromUrl();
        }
    } catch (error) {
        console.error('Error loading events:', error);
    }
}

function openPendingEventFromUrl() {
    if (!pendingEventId || pendingEventOpened) {
        return;
    }

    const event = allEvents.find(item => String(item.id) === String(pendingEventId));

    if (!event) {
        return;
    }

    pendingEventOpened = true;
    viewEvent(event);

    const url = new URL(window.location.href);
    url.searchParams.delete('event_id');
    url.searchParams.delete('event_date');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}

// Load categories
async function loadCategories() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getCategories');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            renderCategories(data.categories);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Render categories
function renderCategories(categories) {
    const list = document.getElementById('categoriesList');
    list.innerHTML = categories.map(cat => {
        const colors = categoryColors[cat.name] || categoryColors['Other'];
        const isActive = selectedCategoryFilter === cat.name;
        return `
            <button type="button" onclick="toggleCategoryFilter('${cat.name}')" class="w-full flex items-center justify-between p-2 rounded-lg transition-colors ${isActive ? 'bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-500' : 'hover:bg-gray-50 dark:hover:bg-slate-800'}">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full ${colors.bg} ${colors.border} border-2"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${cat.name}</span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">${cat.count}</span>
            </button>
        `;
    }).join('');
}

function toggleCategoryFilter(category) {
    selectedCategoryFilter = selectedCategoryFilter === category ? '' : category;
    renderCalendar();
    renderUpcomingEvents();
    loadCategories();
}

// Render upcoming events
function renderUpcomingEvents() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const upcoming = allEvents
        .filter(e => {
            const eventDate = new Date(e.date + 'T00:00:00');
            const matchesCategory = !selectedCategoryFilter || e.category === selectedCategoryFilter;
            return eventDate >= today && matchesCategory;
        })
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 3);

    const list = document.getElementById('upcomingEventsList');

    if (upcoming.length === 0) {
        list.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>';
        return;
    }

    list.innerHTML = upcoming.map(event => {
        const colors = categoryColors[event.category] || categoryColors['Other'];
        const eventDate = new Date(event.date + 'T00:00:00');
        const dateStr = eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

        return `
            <div onclick='viewEvent(${JSON.stringify(event).replace(/'/g, "&#39;")})' class="p-3 rounded-lg border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                <div class="flex items-start gap-3">
                    <div class="text-center min-w-[48px]">
                        <div class="text-xs text-gray-500 dark:text-gray-400">${dateStr.split(' ')[0]}</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-100">${dateStr.split(' ')[1]}</div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">${event.name}</div>
                        <div class="text-xs ${colors.text} mt-1">${event.category}</div>
                        ${event.time ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${event.time}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function openUpcomingEventsModal() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const upcoming = allEvents
        .filter(e => {
            const eventDate = new Date(e.date + 'T00:00:00');
            return eventDate >= today;
        })
        .sort((a, b) => new Date(a.date) - new Date(b.date));

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upcoming Events</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                ${upcoming.length === 0 ? '<p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>' : upcoming.map(event => {
                    const colors = categoryColors[event.category] || categoryColors['Other'];
                    const eventDate = new Date(event.date + 'T00:00:00');
                    const dateStr = eventDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });

                    return `
                        <div onclick='this.closest(".fixed").remove(); viewEvent(${JSON.stringify(event).replace(/'/g, "&#39;")})' class="p-3 rounded-lg border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="text-center min-w-[72px]">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">${dateStr.split(' ')[0]}</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">${dateStr.split(' ')[1]} ${dateStr.split(' ')[2]}</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">${event.name}</div>
                                    <div class="text-xs ${colors.text} mt-1">${event.category}</div>
                                    ${event.time ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${event.time}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

// Open add event modal - FIXED VERSION
function openAddEventModal(preselectedDate = null) {
    const date = preselectedDate || new Date();

    // FIX: Format date properly without timezone conversion
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${day}`;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add Event</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="addEventForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Title <span class="text-red-500">*</span></label>
                    <input type="text" id="eventName" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100" placeholder="Enter event title">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="eventDate" value="${dateStr}" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                        <input type="time" id="eventTime" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                        <input type="time" id="eventEndTime" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                    <select id="eventCategory" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">Select category...</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Conference">Conference</option>
                        <option value="Hearing">Hearing</option>
                        <option value="Deadline">Deadline</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                    <input type="text" id="eventLocation" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100" placeholder="Enter location">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea id="eventDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" placeholder="Add details about the event..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Add Event
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('addEventForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'createEvent');
        formData.append('eventName', document.getElementById('eventName').value);
        formData.append('eventDate', document.getElementById('eventDate').value);
        formData.append('eventTime', document.getElementById('eventTime').value);
        formData.append('eventEndTime', document.getElementById('eventEndTime').value);
        formData.append('category', document.getElementById('eventCategory').value);
        formData.append('location', document.getElementById('eventLocation').value);
        formData.append('description', document.getElementById('eventDescription').value);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                modal.remove();
                showToast('Event added successfully', 'success');
                loadEvents();
                loadCategories();
            } else {
                showToast('Failed to add event: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error adding event', 'error');
        }
    });
}

// Hearing events are created by sanctions.js as `Hearing - Case ${caseId}`
// (see saveScheduleToCalendar in modals/sanctions.js). Pull the case ID back
// out of that name so we can link straight to it.
function extractCaseIdFromHearingEvent(event) {
    if (!event || event.category !== 'Hearing' || !event.name) return null;
    const match = event.name.match(/^Hearing\s*-\s*Case\s+(.+)$/i);
    return match ? match[1].trim() : null;
}

// View event
function viewEvent(event) {
    const colors = categoryColors[event.category];
    const eventDate = new Date(event.date + 'T00:00:00');
    const dateStr = eventDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const linkedCaseId = extractCaseIdFromHearingEvent(event);
    const isPersonalCalendar = currentCalendarView === 'personal';

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full ${colors.bg} ${colors.border} border-2"></div>
                        <span class="text-sm ${colors.text} font-medium">${event.category}</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">${event.name}</h3>
                </div>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-3 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${dateStr}</div>
                        ${event.time ? `<div class="text-sm text-gray-500 dark:text-gray-400">${event.time}</div>` : ''}
                    </div>
                </div>

                ${event.location ? `
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="text-sm text-gray-700 dark:text-gray-300">${event.location}</div>
                    </div>
                ` : ''}

                ${event.description ? `
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <div class="text-sm text-gray-700 dark:text-gray-300">${event.description}</div>
                    </div>
                ` : ''}

                ${event.createdBy ? `
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <div class="text-sm text-gray-700 dark:text-gray-300">Scheduled by: ${event.createdBy}</div>
                    </div>
                ` : ''}
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                ${isPersonalCalendar ? `
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Close
                    </button>
                ` : `
                    <button onclick="deleteEvent(${event.id})" class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        Delete
                    </button>
                    <div class="flex gap-2">
                        ${linkedCaseId ? `
                            <a href="/PrototypeDO/modules/do/cases.php?highlightCaseId=${encodeURIComponent(linkedCaseId)}&highlightCase=1" class="px-4 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-500 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                View Case
                            </a>
                        ` : ''}
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            Close
                        </button>
                        <button onclick='editEvent(${JSON.stringify(event).replace(/'/g, "&#39;")})' class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Edit
                        </button>
                    </div>
                `}
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Delete event
async function deleteEvent(eventId) {
    if (!confirm('Are you sure you want to delete this event?')) return;

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'deleteEvent');
    formData.append('eventId', eventId);

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            document.querySelector('.fixed.inset-0').remove();
            showToast('Event deleted successfully', 'success');
            loadEvents();
            loadCategories();
        } else {
            showToast('Failed to delete event', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error deleting event', 'error');
    }
}

// Edit event - FIXED VERSION
function editEvent(event) {
    // Close view modal
    document.querySelector('.fixed.inset-0').remove();

    // FIX: Ensure time is in correct format for time input (HH:MM)
    // Parse time range "9:00 AM - 10:30 AM" into start and end times
    let timeValue = '';
    let endTimeValue = '';
    if (event.time) {
        // Check if it's a time range
        const timeRange = event.time.split(' - ');
        if (timeRange.length === 2) {
            // Parse start time
            const startParts = timeRange[0].match(/(\d+):(\d+)\s*(AM|PM)/i);
            if (startParts) {
                let hours = parseInt(startParts[1]);
                const minutes = startParts[2];
                const period = startParts[3].toUpperCase();

                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }

                timeValue = `${String(hours).padStart(2, '0')}:${minutes}`;
            }

            // Parse end time
            const endParts = timeRange[1].match(/(\d+):(\d+)\s*(AM|PM)/i);
            if (endParts) {
                let hours = parseInt(endParts[1]);
                const minutes = endParts[2];
                const period = endParts[3].toUpperCase();

                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }

                endTimeValue = `${String(hours).padStart(2, '0')}:${minutes}`;
            }
        } else {
            // Single time (backward compatibility)
            const timeParts = event.time.match(/(\d+):(\d+)\s*(AM|PM)/i);
            if (timeParts) {
                let hours = parseInt(timeParts[1]);
                const minutes = timeParts[2];
                const period = timeParts[3].toUpperCase();

                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }

                timeValue = `${String(hours).padStart(2, '0')}:${minutes}`;
            }
        }
    }

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Edit Event</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editEventForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Title <span class="text-red-500">*</span></label>
                    <input type="text" id="editEventName" value="${event.name}" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="editEventDate" value="${event.date}" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                        <input type="time" id="editEventTime" value="${timeValue}" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                        <input type="time" id="editEventEndTime" value="${endTimeValue}" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                    <select id="editEventCategory" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="Meeting" ${event.category === 'Meeting' ? 'selected' : ''}>Meeting</option>
                        <option value="Conference" ${event.category === 'Conference' ? 'selected' : ''}>Conference</option>
                        <option value="Hearing" ${event.category === 'Hearing' ? 'selected' : ''}>Hearing</option>
                        <option value="Deadline" ${event.category === 'Deadline' ? 'selected' : ''}>Deadline</option>
                        <option value="Other" ${event.category === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                    <input type="text" id="editEventLocation" value="${event.location || ''}" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea id="editEventDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none">${event.description || ''}</textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('editEventForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'updateEvent');
        formData.append('eventId', event.id);
        formData.append('eventName', document.getElementById('editEventName').value);
        formData.append('eventDate', document.getElementById('editEventDate').value);
        formData.append('eventTime', document.getElementById('editEventTime').value);
        formData.append('eventEndTime', document.getElementById('editEventEndTime').value);
        formData.append('category', document.getElementById('editEventCategory').value);
        formData.append('location', document.getElementById('editEventLocation').value);
        formData.append('description', document.getElementById('editEventDescription').value);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                modal.remove();
                showToast('Event updated successfully', 'success');
                loadEvents();
                loadCategories();
            } else {
                showToast('Failed to update event', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error updating event', 'error');
        }
    });
}

// Show day events
function showDayEvents(date, events) {
    const dateStr = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    const today = new Date();
    const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const cellDateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const isPastDay = cellDateOnly < todayDateOnly;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-lg p-6 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">${dateStr}</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-2" id="dayEventsList"></div>
        </div>
    `;
    document.body.appendChild(modal);

    const listContainer = modal.querySelector('#dayEventsList');
    events.forEach(event => {
        const colors = categoryColors[event.category] || categoryColors['Other'];

        const item = document.createElement('div');
        item.className = 'p-3 rounded-lg border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer transition-colors';
        item.onclick = () => {
            modal.remove();
            viewEvent(event);
        };

        const row = document.createElement('div');
        row.className = 'flex items-start gap-3';

        const dot = document.createElement('div');
        dot.className = `w-3 h-3 mt-1 rounded-full ${colors.bg} ${colors.border} border-2`;
        row.appendChild(dot);

        const textWrap = document.createElement('div');
        textWrap.className = 'flex-1';

        const nameEl = document.createElement('div');
        nameEl.className = 'font-medium text-sm text-gray-900 dark:text-gray-100';
        if (isPastDay) {
            renderStrikethroughText(nameEl, event.name, { inline: true });
        } else {
            nameEl.textContent = event.name;
        }
        textWrap.appendChild(nameEl);

        const metaEl = document.createElement('div');
        metaEl.className = 'flex items-center gap-2 mt-1';
        metaEl.innerHTML = `<span class="text-xs ${colors.text}">${event.category}</span>${event.time ? `<span class="text-xs text-gray-500 dark:text-gray-400">• ${event.time}</span>` : ''}`;
        textWrap.appendChild(metaEl);

        row.appendChild(textWrap);
        item.appendChild(row);
        listContainer.appendChild(item);
    });
}

// Toast notification
function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };

    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[1000] transition-all duration-300 transform translate-x-full`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}