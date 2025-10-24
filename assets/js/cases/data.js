// Sample cases
const allCases = [
  {
    id: 'C-1093',
    student: 'John Doe',
    type: 'Bullying',
    date: 'Oct 10, 2025',
    status: 'Pending',
    assignedTo: 'Mr. Cruz',
    statusColor: 'yellow',
    description: 'Bullying incident reported in hallway.',
    notes: 'Needs follow-up.'
  },
  // more...
];

let filteredCases = [...allCases];
let currentPage = 1;
const casesPerPage = 8;

const statusColors = {
  yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
};
