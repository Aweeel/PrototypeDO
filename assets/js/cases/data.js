// ====== Sample Cases Data ======
const allCases = [
    {
        id: 'C-1092',
        student: 'Alex Johnson',
        type: 'Tardiness',
        date: 'Oct 12, 2023',
        status: 'Pending',
        assignedTo: 'Ms. Parker',
        statusColor: 'yellow',
        description: 'Student was late to class for the third time this month.',
        notes: 'Parent has been contacted via email on Oct 11.'
    },
    {
        id: 'C-1091',
        student: 'Maria Garcia',
        type: 'Dress Code',
        date: 'Oct 11, 2023',
        status: 'Resolved',
        assignedTo: 'Mr. Thompson',
        statusColor: 'green',
        description: 'Inappropriate attire violation.',
        notes: 'Issue resolved, student complied.'
    },
    {
        id: 'C-1090',
        student: 'James Smith',
        type: 'Classroom Disruption',
        date: 'Oct 10, 2023',
        status: 'Under Review',
        assignedTo: 'Ms. Parker',
        statusColor: 'blue',
        description: 'Talking loudly during class.',
        notes: 'Reviewing incident with student.'
    },
    {
        id: 'C-1089',
        student: 'Emma Wilson',
        type: 'Academic Dishonesty',
        date: 'Oct 9, 2023',
        status: 'Escalated',
        assignedTo: 'Principal Davis',
        statusColor: 'red',
        description: 'Cheating on exam.',
        notes: 'Case escalated to principal.'
    },
    {
        id: 'C-1088',
        student: 'Daniel Lee',
        type: 'Attendance',
        date: 'Oct 8, 2023',
        status: 'Resolved',
        assignedTo: 'Mr. Thompson',
        statusColor: 'green',
        description: 'Multiple absences.',
        notes: 'Medical documentation provided.'
    },
    {
        id: 'C-1087',
        student: 'Sophia Brown',
        type: 'Tardiness',
        date: 'Oct 7, 2023',
        status: 'Pending',
        assignedTo: 'Ms. Parker',
        statusColor: 'yellow',
        description: 'Late to class.',
        notes: ''
    },
    {
        id: 'C-1086',
        student: 'Michael Wang',
        type: 'Dress Code',
        date: 'Oct 6, 2023',
        status: 'Resolved',
        assignedTo: 'Mr. Thompson',
        statusColor: 'green',
        description: 'Uniform violation.',
        notes: 'Corrected immediately.'
    },
    {
        id: 'C-1085',
        student: 'Olivia Martinez',
        type: 'Classroom Disruption',
        date: 'Oct 5, 2023',
        status: 'Under Review',
        assignedTo: 'Ms. Parker',
        statusColor: 'blue',
        description: 'Disruptive behavior.',
        notes: 'Meeting scheduled with parents.'
    }
];

let filteredCases = [...allCases];
let currentPage = 1;
const casesPerPage = 8;
let currentTab = 'current';

const statusColors = {
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
    blue: 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
    green: 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
    red: 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'
};