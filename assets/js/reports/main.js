// Reports Page - Core Logic
const TABS        = ['incident','statistics','lostfound','student'];
const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
let ADMIN_NAME  = '';
const PAGE_URL    = '/PrototypeDO/modules/do/reports.php';
const reportCache = {};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Use window.ADMIN_NAME if set by PHP, otherwise read from meta tag, otherwise default
    ADMIN_NAME = window.ADMIN_NAME || document.querySelector('meta[data-admin-name]')?.content || 'User';
    console.log('Final ADMIN_NAME:', ADMIN_NAME);
    populateAjaxSelects();
});

// ── Tab Switching ────────────────────────────────────────
function switchTab(id) {
    TABS.forEach(t => {
        document.getElementById(`tab-panel-${t}`).classList.add('hidden');
        document.getElementById(`tab-${t}`).classList.remove('tab-active');
    });
    document.getElementById(`tab-panel-${id}`).classList.remove('hidden');
    document.getElementById(`tab-${id}`).classList.add('tab-active');
}

// ── Populate AJAX dropdowns ───────────────────────────────
async function populateAjaxSelects() {
    const selects = document.querySelectorAll('[data-ajax]');
    for (const el of selects) {
        await populateSelect(el);
    }
    setupDependentFilters();
}

async function populateSelect(el) {
    const action = el.dataset.ajax, vk = el.dataset.vk, lk = el.dataset.lk || vk;
    if (!action) return;
    const fd = new FormData();
    fd.append('ajax', '1'); fd.append('action', action);
    try {
        const res  = await fetch(PAGE_URL, {method:'POST', body:fd});
        const data = await res.json();
        if (data.success) {
            data.data.forEach(row => {
                const o = document.createElement('option');
                o.value = row[vk];
                o.textContent = row[lk] || row[vk];
                el.appendChild(o);
            });
        }
    } catch(e) { 
        console.warn('Dropdown failed:', action); 
    }
}

// ── Dependent Filters (Grade > Courses) ──────────────────
function setupDependentFilters() {
    const gradeLevelSelect = document.getElementById('stat-gradeLevel');
    const courseSelect = document.getElementById('stat-course');
    
    if (!gradeLevelSelect || !courseSelect) return;
    
    gradeLevelSelect.addEventListener('change', async () => {
        await updateCoursesByGradeLevel();
    });
}

async function updateCoursesByGradeLevel() {
    const gradeLevelSelect = document.getElementById('stat-gradeLevel');
    const courseSelect = document.getElementById('stat-course');
    
    if (!gradeLevelSelect || !courseSelect) return;
    
    const gradeLevel = gradeLevelSelect.value;
    
    // Remove all options except the first one ("All Courses")
    while (courseSelect.options.length > 1) {
        courseSelect.remove(1);
    }
    courseSelect.value = '';
    
    // If no grade level selected, fetch all courses
    if (!gradeLevel) {
        const fd = new FormData();
        fd.append('ajax', '1');
        fd.append('action', 'getAvailableCourses');
        try {
            const res = await fetch(PAGE_URL, {method:'POST', body:fd});
            const data = await res.json();
            if (data.success) {
                data.data.forEach(row => {
                    const o = document.createElement('option');
                    o.value = row.track_course;
                    o.textContent = row.track_course;
                    courseSelect.appendChild(o);
                });
            }
        } catch(e) {
            console.warn('Failed to fetch all courses:', e);
        }
        return;
    }
    
    // Fetch courses for the selected grade level
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'getCoursesByGradeLevel');
    fd.append('gradeLevel', gradeLevel);
    
    try {
        const res = await fetch(PAGE_URL, {method:'POST', body:fd});
        const data = await res.json();
        if (data.success) {
            data.data.forEach(row => {
                const o = document.createElement('option');
                o.value = row.track_course;
                o.textContent = row.track_course;
                courseSelect.appendChild(o);
            });
        }
    } catch(e) {
        console.warn('Failed to fetch courses for grade level:', e);
    }
}

// ── Collect filter values ────────────────────────────────
function getFilters(type) {
    const g = id => document.getElementById(id)?.value ?? '';
    const map = {
        incident:   () => ({ reportType:g('inc-reportType'), caseId:g('inc-caseId'), dateFrom:g('inc-dateFrom'), dateTo:g('inc-dateTo'), severity:g('inc-severity'), status:g('inc-status') }),
        statistics: () => ({ year:g('stat-year'), month:g('stat-month'), view:g('stat-view'), severity:g('stat-severity'), gradeLevel:g('stat-gradeLevel'), course:g('stat-course') }),
        lostfound:  () => ({ dateFrom:g('lf-dateFrom'), dateTo:g('lf-dateTo'), status:g('lf-status'), category:g('lf-category') }),
        student:    () => ({ studentId:g('stu-studentId'), gradeLevel:g('stu-gradeLevel'), status:g('stu-status') }),
        audit:      () => ({ dateFrom:g('aud-dateFrom'), dateTo:g('aud-dateTo'), actionType:g('aud-actionType') }),
    };
    return (map[type] ?? (() => ({})))();
}

// ── Generate Report ─────────────────────────────────────
const actionMap = {
    incident:'generateIncidentReport', statistics:'generateStatisticsReport',
    lostfound:'generateLostFoundReport', student:'generateStudentReport', audit:'generateAuditReport'
};

async function generateReport(type) {
    const emptyEl   = document.getElementById(`${type}-empty`);
    const actionsEl = document.getElementById(`${type}-actions`);
    const contentEl = document.getElementById(`${type}-content`);

    emptyEl.innerHTML = `<div class="flex items-center justify-center p-16">
        <div class="text-center">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-600 border-t-transparent mx-auto mb-3"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Building report…</p>
        </div>
    </div>`;
    emptyEl.classList.remove('hidden');
    actionsEl.classList.add('hidden'); 
    actionsEl.classList.remove('flex');
    contentEl.innerHTML = '';

    const filters = getFilters(type);
    const fd = new FormData();
    fd.append('ajax', '1'); 
    fd.append('action', actionMap[type]);
    Object.entries(filters).forEach(([k,v]) => fd.append(k, v));

    try {
        const res  = await fetch(PAGE_URL, {method:'POST', body:fd});
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Unknown error');

        reportCache[type] = { data, filters };

        const html = buildHTML(type, data);
        const count = getCountStr(type, data);

        emptyEl.classList.add('hidden');
        actionsEl.classList.remove('hidden'); 
        actionsEl.classList.add('flex');
        document.getElementById(`${type}-count`).textContent = count;
        contentEl.innerHTML = `<div class="preview-wrap">${html}</div>`;

    } catch(e) {
        emptyEl.innerHTML = `<div class="p-10 text-center text-red-500 dark:text-red-400">
            <p class="font-medium mb-1">Failed to generate report</p>
            <p class="text-sm opacity-80">${e.message}</p>
        </div>`;
    }
}

function getCountStr(type, d) {
    if (type==='incident')   return `${d.cases.length} case(s)`;
    if (type==='statistics') return `${d.totals?.total??0} total cases`;
    if (type==='lostfound')  return `${d.items.length} item(s)`;
    if (type==='student')    return `${d.students.length} student(s)`;
    if (type==='audit')      return `${d.logs.length} log entries`;
    return '';
}

// ═══════════════════════════════════════════════════════
//  RENDERING HELPERS
// ═══════════════════════════════════════════════════════

const BADGE_MAP = {
    Major:'#fee2e2;color:#991b1b', 
    Minor:'#fef9c3;color:#78350f',
    Pending:'#fef3c7;color:#92400e', 
    'On Going':'#dbeafe;color:#1e40af',
    Resolved:'#d1fae5;color:#065f46', 
    Claimed:'#d1fae5;color:#065f46',
    Unclaimed:'#fef3c7;color:#92400e', 
    'Good Standing':'#d1fae5;color:#065f46',
    'On Watch':'#fef3c7;color:#92400e', 
    'On Probation':'#fee2e2;color:#991b1b',
    Login:'#ede9fe;color:#5b21b6', 
    Logout:'#fef3c7;color:#92400e',
    Created:'#d1fae5;color:#065f46', 
    Updated:'#dbeafe;color:#1e40af',
    Deleted:'#fee2e2;color:#991b1b', 
    Archived:'#f3e8ff;color:#6d28d9',
};

function badge(text) {
    const s = BADGE_MAP[text] ? `background:#${BADGE_MAP[text]}` : 'background:#f3f4f6;color:#374151';
    return `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap" style="${s}">${esc(text)}</span>`;
}

function esc(v) {
    if (v == null || v === '') return '—';
    const d = document.createElement('div'); 
    d.textContent = String(v); 
    return d.innerHTML;
}

function fmtDate(d) {
    if (!d) return '—';
    try { 
        return new Date(d).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); 
    } catch { 
        return String(d); 
    }
}

function statCards(cards) {
    return `<div class="flex flex-wrap gap-2.5 mb-5">
        ${cards.map(c=>`
            <div class="flex-1 min-w-28 p-3.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-center">
                <div class="text-2xl font-bold leading-none" style="color:${c.color}">${c.value}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">${c.label}</div>
            </div>
        `).join('')}
    </div>`;
}

function tbl(headers, rows) {
    const head = headers.map(h=>`<th class="bg-blue-900 text-white px-2.5 py-1.75 text-xs font-semibold text-left whitespace-nowrap">${h}</th>`).join('');
    const body = rows.length
        ? rows.map(r=>`<tr>${r.map(c=>`<td class="px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-slate-700 align-top">${c}</td>`).join('')}</tr>`).join('')
        : `<tr><td colspan="${headers.length}" class="px-2.5 py-4 text-center text-gray-400">No records found</td></tr>`;
    return `<div class="overflow-x-auto mb-4">
        <table class="w-full border-collapse border border-gray-300 dark:border-slate-700"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>
    </div>`;
}

function h2(t) { 
    return `<div class="text-sm font-bold text-blue-700 dark:text-blue-400 border-l-4 border-blue-700 dark:border-blue-400 pl-2.5 my-5 mb-2.5">${t}</div>`; 
}

function banner(title, subtitle, kv) {
    const filters = Object.entries(kv).filter(([,v])=>v)
        .map(([k,v])=>`<span class="mr-2.5"><b>${k}:</b> ${esc(v)}</span>`).join('');
    return `<div class="border-b-4 border-blue-700 dark:border-blue-500 pb-3.5 mb-5">
        <div class="flex justify-between items-start gap-3">
            <div>
                <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest">STI Discipline Office</div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 m-1 mt-1">${title}</h1>
                ${subtitle?`<div class="text-xs text-gray-600 dark:text-gray-400">${subtitle}</div>`:''}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400 text-right flex-shrink-0 pl-3">
                <div>${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</div>
                <div>By: ${esc(ADMIN_NAME)}</div>
            </div>
        </div>
        ${filters?`<div class="mt-2 pt-2 px-2.5 bg-gray-100 dark:bg-slate-800 rounded text-xs text-gray-700 dark:text-gray-300">${filters}</div>`:''}
    </div>`;
}

// ── Route to builder ─────────────────────────────────────
function buildHTML(type, data) {
    const builders = { incident:incHTML, statistics:statHTML, lostfound:lfHTML, student:stuHTML, audit:audHTML };
    return (builders[type] ?? (() => '<p>Unknown report type</p>'))(data);
}

// ── Incident ─────────────────────────────────────────────
function incHTML(d) {
    const { cases, stats, filters } = d;
    const detailed = filters.reportType === 'detailed';
    let html = banner(
        detailed ? 'Detailed Incident Report' : 'Incident Report Summary',
        filters.caseId ? `Case: ${filters.caseId}` : 'All Active Cases',
        { 'Date Range':(filters.dateFrom||filters.dateTo)?`${filters.dateFrom||'—'} → ${filters.dateTo||'—'}`:'',
          Severity:filters.severity, Status:filters.status }
    );
    html += statCards([
        {label:'Total',    value:stats.total,    color:'#1d4ed8'},
        {label:'Pending',  value:stats.pending,  color:'#d97706'},
        {label:'On Going', value:stats.ongoing,  color:'#2563eb'},
        {label:'Resolved', value:stats.resolved, color:'#16a34a'},
        {label:'Major',    value:stats.major,    color:'#dc2626'},
        {label:'Minor',    value:stats.minor,    color:'#ca8a04'},
    ]);
    html += h2('Case Records');
    if (!detailed) {
        html += tbl(
            ['Case ID','Student','Student ID','Case Type','Severity','Status','Date Reported','Assigned To'],
            cases.map(c=>[`<b>${esc(c.case_id)}</b>`,esc(c.student_name),esc(c.student_number),
                esc(c.case_type),badge(c.severity),badge(c.status),
                fmtDate(c.date_reported),esc(c.assigned_to_name)])
        );
    } else {
        cases.forEach(c => {
            html += `<div class="border border-gray-300 dark:border-slate-700 rounded mb-3.5 overflow-hidden">
                <div class="bg-blue-900 text-white px-3.5 py-2 flex justify-between items-center text-sm font-semibold">
                    <b>Case ${esc(c.case_id)}</b>
                    <span class="text-xs">${fmtDate(c.date_reported)}</span>
                </div>
                <div class="p-3.5 grid grid-cols-2 gap-2 text-xs text-gray-700 dark:text-gray-300">
                    <div><label class="text-gray-600 dark:text-gray-400">Student: </label><b>${esc(c.student_name)}</b> (${esc(c.student_number)})</div>
                    <div><label class="text-gray-600 dark:text-gray-400">Grade/Track: </label>${esc(c.grade_year)} — ${esc(c.track_course)}</div>
                    <div><label class="text-gray-600 dark:text-gray-400">Case Type: </label>${esc(c.case_type)}</div>
                    <div><label class="text-gray-600 dark:text-gray-400">Severity: </label>${badge(c.severity)} <label class="ml-2">Status: </label>${badge(c.status)}</div>
                    <div><label class="text-gray-600 dark:text-gray-400">Reported By: </label>${esc(c.reported_by_name)}</div>
                    <div><label class="text-gray-600 dark:text-gray-400">Assigned To: </label>${esc(c.assigned_to_name)}</div>
                    ${c.description?`<div class="col-span-2"><label class="text-gray-600 dark:text-gray-400">Description: </label>${esc(c.description)}</div>`:''}
                    ${c.notes?`<div class="col-span-2"><label class="text-gray-600 dark:text-gray-400">Notes: </label>${esc(c.notes)}</div>`:''}
                </div>
                ${c.sanctions?.length ? `
                <div class="bg-gray-50 dark:bg-slate-800 border-t border-gray-300 dark:border-slate-700 p-3.5">
                    <div class="text-xs font-semibold text-gray-800 dark:text-gray-300 mb-1.5">Applied Sanctions (${c.sanctions.length})</div>
                    ${c.sanctions.map(s=>`<div class="text-xs py-1 border-b border-gray-200 dark:border-slate-700 flex gap-3.5 flex-wrap text-gray-700 dark:text-gray-300">
                        <b>${esc(s.sanction_name)}</b>
                        ${s.duration_days?`<span class="text-gray-600 dark:text-gray-400">Duration: ${s.duration_days}d</span>`:''}
                        ${s.scheduled_date?`<span class="text-blue-600 dark:text-blue-400">Scheduled: ${fmtDate(s.scheduled_date)}</span>`:''}
                        <span class="text-gray-600 dark:text-gray-400">Applied: ${fmtDate(s.applied_date)}</span>
                    </div>`).join('')}
                </div>` : ''}
            </div>`;
        });
    }
    return html;
}

// ── Statistics ───────────────────────────────────────────
function statHTML(d) {
    const { monthly, byType, byGrade, byStatus, totals, repeatOffenders, filters } = d;
    const isMonthlyView = filters.view === 'monthly';
    const monthName = filters.month ? ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][parseInt(filters.month)] : null;
    
    let subtitle = `Year: ${filters.year}`;
    if (monthName) subtitle += ` • ${monthName}`;
    
    let html = banner('Case Statistics Report', subtitle,
        { View: isMonthlyView?'Monthly Breakdown':'Yearly Overview', Severity:filters.severity, 'Grade Level':filters.gradeLevel });
    
    html += statCards([
        {label:'Total Cases',      value:totals.total??0,        color:'#1d4ed8'},
        {label:'Major',            value:totals.major??0,        color:'#dc2626'},
        {label:'Minor',            value:totals.minor??0,        color:'#ca8a04'},
        {label:'Resolved',         value:totals.resolved??0,     color:'#16a34a'},
        {label:'Repeat Offenders', value:repeatOffenders.length, color:'#7c3aed'},
    ]);
    
    if (monthName) {
        html += h2(`Statistics for ${monthName} ${filters.year}`);
        const total = totals.total || 0;
        const major = totals.major || 0;
        const minor = totals.minor || 0;
        const resolved = totals.resolved || 0;
        const pending = total - resolved;
        html += tbl(['Metric','Value'], [
            ['Total Cases', total], ['Major Cases', major], ['Minor Cases', minor], 
            ['Resolved', resolved], ['Pending/On Going', pending]
        ]);
        html += h2('Cases by Type');
        html += tbl(['Case Type','Total','Major'], byType.map(r=>[esc(r.case_type),r.count,r.major_count]));
        if (byGrade.length) {
            html += h2('Cases by Grade / Year Level');
            html += tbl(['Grade / Year','Total Cases'], byGrade.map(r=>[esc(r.grade_year),r.count]));  
        }
        html += h2('Cases by Status');
        html += tbl(['Status','Count'], byStatus.map(r=>[badge(r.status),r.count]));
    } else if (isMonthlyView) {
        const monthlyData = MONTH_NAMES.map((m,i)=>({month:m, cases:monthly[i+1]??0}));
        const maxCases = Math.max(...monthlyData.map(x=>x.cases), 1);
        const avgCases = totals.total / 12;
        
        monthlyData.sort((a,b)=>b.cases-a.cases);
        const topMonths = monthlyData.slice(0,3);
        const slowMonths = monthlyData.reverse().slice(0,3);
        
        html += h2('Busiest Months');
        html += tbl(['Month','Cases','Activity Level'], 
            topMonths.map(m=>[m.month, m.cases, '█'.repeat(Math.round(m.cases/maxCases*10))]));
        
        html += h2('Slowest Months');
        html += tbl(['Month','Cases','Activity Level'], 
            slowMonths.map(m=>[m.month, m.cases, '█'.repeat(Math.max(1, Math.round(m.cases/maxCases*10)))]));
        
        html += h2('Monthly Distribution');
        html += tbl(['Month','Cases','vs Average'], 
            MONTH_NAMES.map((m,i)=>{const c = monthly[i+1]??0; return [m, c, c>avgCases?`+${(c-avgCases).toFixed(0)}`:`${(c-avgCases).toFixed(0)}`];}));
        
        html += h2('Cases by Status');
        html += tbl(['Status','Count'], byStatus.map(r=>[badge(r.status),r.count]));
        
        if (byGrade.length) {
            html += h2('Cases by Grade / Year Level');
            html += tbl(['Grade / Year','Total Cases'], byGrade.map(r=>[esc(r.grade_year),r.count]));
        }
    } else {
        html += h2('Monthly Case Volume');
        html += tbl(['Month','Cases'], MONTH_NAMES.map((m,i)=>[m, monthly[i+1]??0]));
        html += h2('Cases by Type');
        html += tbl(['Case Type','Total','Major'], byType.map(r=>[esc(r.case_type),r.count,r.major_count]));
        if (byGrade.length) {
            html += h2('Cases by Grade / Year Level');
            html += tbl(['Grade / Year','Total Cases'], byGrade.map(r=>[esc(r.grade_year),r.count]));
        }
        html += h2('Cases by Status');
        html += tbl(['Status','Count'], byStatus.map(r=>[badge(r.status),r.count]));
    }
    
    if (repeatOffenders.length) {
        html += h2('Repeat Offenders');
        html += tbl(['Student ID','Name','Grade/Year','Track','Offenses'],
            repeatOffenders.map(r=>[esc(r.student_id),esc(r.name),esc(r.grade_year),esc(r.track_course),r.offense_count]));
    }
    return html;
}

// ── Lost & Found ─────────────────────────────────────────
function lfHTML(d) {
    const { items, byCategory, totals, filters } = d;
    let html = banner('Lost & Found Report','Item Inventory',
        { 'Date Range':(filters.dateFrom||filters.dateTo)?`${filters.dateFrom||'—'} → ${filters.dateTo||'—'}`:'',
          Status:filters.status, Category:filters.category });
    html += statCards([
        {label:'Total',    value:totals.total??0,    color:'#1d4ed8'},
        {label:'Unclaimed',value:totals.unclaimed??0,color:'#d97706'},
        {label:'Claimed',  value:totals.claimed??0,  color:'#16a34a'},
    ]);
    html += h2('Summary by Category');
    html += tbl(['Category','Total','Claimed','Unclaimed'],
        byCategory.map(r=>[esc(r.category),r.total,r.claimed,r.unclaimed]));
    html += h2('Item Records');
    html += tbl(['Item ID','Name','Category','Location Found','Date Found','Status','Claimer'],
        items.map(i=>[esc(i.item_id),esc(i.item_name),esc(i.category),esc(i.found_location),
            fmtDate(i.date_found),badge(i.status),
            i.claimer_name?`${esc(i.claimer_name)}${i.date_claimed?' ('+fmtDate(i.date_claimed)+')':''}` : '—']));
    return html;
}

// ── Student ──────────────────────────────────────────────
function stuHTML(d) {
    const { students, statusDist, filters } = d;
    const get = s => (statusDist.find(x=>x.status===s)||{count:0}).count;
    let html = banner('Student Behavior Report',
        filters.studentId?`Student: ${filters.studentId}`:'All Students',
        { 'Grade Level':filters.gradeLevel, 'Standing Status':filters.status });
    html += statCards([
        {label:'Total Students', value:students.length,        color:'#1d4ed8'},
        {label:'Good Standing',  value:get('Good Standing'),   color:'#16a34a'},
        {label:'On Watch',       value:get('On Watch'),        color:'#d97706'},
        {label:'On Probation',   value:get('On Probation'),    color:'#dc2626'},
    ]);
    html += h2('Student Records');
    html += tbl(['Student ID','Name','Grade/Year','Track/Course','Status','Cases','Major','Minor','Last Incident'],
        students.map(s=>[esc(s.student_id),`${esc(s.first_name)} ${esc(s.last_name)}`,
            esc(s.grade_year),esc(s.track_course),badge(s.status||'Good Standing'),
            s.active_cases??0,s.major_count??0,s.minor_count??0,fmtDate(s.last_incident_date)]));
    return html;
}

// ── Audit ────────────────────────────────────────────────
function audHTML(d) {
    const { logs, byAction, filters } = d;
    let html = banner('Audit Log Report','System Activity',
        { 'Date Range':(filters.dateFrom||filters.dateTo)?`${filters.dateFrom||'—'} → ${filters.dateTo||'—'}`:'',
          'Action Type':filters.actionType });
    html += statCards([{label:'Total Entries', value:logs.length, color:'#1d4ed8'}]);
    html += h2('Activity by Action Type');
    html += tbl(['Action','Count'], byAction.map(r=>[badge(r.action),r.count]));
    html += h2('Log Entries');
    html += tbl(['Log ID','User','Role','Action','Table','Record ID','IP','Timestamp'],
        logs.map(l=>[esc(l.log_id),esc(l.user_name)||'System',esc(l.role),badge(l.action),
            esc(l.table_name),esc(l.record_id),esc(l.ip_address),
            l.timestamp?new Date(l.timestamp).toLocaleString('en-US',{dateStyle:'short',timeStyle:'short'}):'—']));
    return html;
}