// assets/js/statistics/main.js

// Chart instances
let casesByTypeChart = null;
let casesByGradeChart = null;
let monthlyTrendsChart = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    populateFilters();
    updateStatistics();
    initializeCasesByTypeChart();
    initializeCasesByGradeChart();
    updateMonthlyTrends();
});

// Populate all filters
async function populateFilters() {
    try {
        // Fetch grade levels
        let formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getGradeLevels');
        
        let response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        let result = await response.json();
        if (result.success) {
            const gradeLevelSelect = document.getElementById('gradeLevelFilter');
            result.data.forEach(gradeLevel => {
                const option = document.createElement('option');
                option.value = gradeLevel.name;
                option.textContent = gradeLevel.name;
                gradeLevelSelect.appendChild(option);
            });
        }
        
        // Fetch year levels
        formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getYearLevels');
        
        response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        result = await response.json();
        if (result.success) {
            const yearLevelSelect = document.getElementById('yearLevelFilter');
            result.data.forEach(yearLevel => {
                const option = document.createElement('option');
                option.value = yearLevel.name;
                option.textContent = yearLevel.name;
                yearLevelSelect.appendChild(option);
            });
        }
        
        // Fetch strands
        formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getStrands');
        
        response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        result = await response.json();
        if (result.success) {
            const strandSelect = document.getElementById('strandFilter');
            result.data.forEach(strand => {
                const option = document.createElement('option');
                option.value = strand.name;
                option.textContent = strand.name;
                strandSelect.appendChild(option);
            });
        }
        
        // Fetch courses
        formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getCourses');
        
        response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        result = await response.json();
        if (result.success) {
            const courseSelect = document.getElementById('courseFilter');
            result.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.name;
                option.textContent = course.name;
                courseSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error populating filters:', error);
    }
}

// Update all charts and statistics when filters change
function updateAllCharts() {
    updateStatistics();
    initializeCasesByTypeChart();
    initializeCasesByGradeChart();
    updateMonthlyTrends();
}

// Update statistics cards
async function updateStatistics() {
    const dateRange = document.getElementById('dateRangeFilter').value;
    const gradeLevel = document.getElementById('gradeLevelFilter').value;
    const yearLevel = document.getElementById('yearLevelFilter').value;
    const strand = document.getElementById('strandFilter').value;
    const course = document.getElementById('courseFilter').value;
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getStatistics');
        formData.append('dateRange', dateRange);
        formData.append('gradeLevel', gradeLevel);
        formData.append('yearLevel', yearLevel);
        formData.append('strand', strand);
        formData.append('course', course);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalCases').textContent = data.stats.totalCases || 0;
            document.getElementById('resolvedCases').textContent = data.stats.resolvedCases || 0;
            document.getElementById('repeatOffenders').textContent = data.stats.repeatOffenders || 0;
            document.getElementById('lostItemsClaimed').innerHTML = (data.stats.lostItemsClaimed || 0) + '<span class="text-lg">%</span>';
        }
    } catch (error) {
        console.error('Error updating statistics:', error);
    }
}

// Initialize Cases by Type Chart
async function initializeCasesByTypeChart() {
    try {
        const gradeLevel = document.getElementById('gradeLevelFilter').value;
        const yearLevel = document.getElementById('yearLevelFilter').value;
        const strand = document.getElementById('strandFilter').value;
        const course = document.getElementById('courseFilter').value;
        
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getCasesByType');
        formData.append('gradeLevel', gradeLevel);
        formData.append('yearLevel', yearLevel);
        formData.append('strand', strand);
        formData.append('course', course);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const ctx = document.getElementById('casesByTypeChart').getContext('2d');
            
            const labels = result.data.map(item => item.case_type);
            const data = result.data.map(item => item.count);
            
            const colors = [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', 
                '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'
            ];
            
            if (casesByTypeChart) {
                casesByTypeChart.destroy();
            }
            
            casesByTypeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Cases',
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error initializing cases by type chart:', error);
    }
}

// Initialize Cases by Grade Level Chart
async function initializeCasesByGradeChart() {
    try {
        const gradeLevel = document.getElementById('gradeLevelFilter').value;
        const yearLevel = document.getElementById('yearLevelFilter').value;
        const strand = document.getElementById('strandFilter').value;
        const course = document.getElementById('courseFilter').value;
        
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getCasesByGrade');
        formData.append('gradeLevel', gradeLevel);
        formData.append('yearLevel', yearLevel);
        formData.append('strand', strand);
        formData.append('course', course);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const ctx = document.getElementById('casesByGradeChart').getContext('2d');
            
            const labels = result.data.map(item => item.grade_year);
            const data = result.data.map(item => item.count);
            
            if (casesByGradeChart) {
                casesByGradeChart.destroy();
            }
            
            casesByGradeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cases',
                        data: data,
                        backgroundColor: '#3B82F6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error initializing cases by grade chart:', error);
    }
}

// Update Monthly Trends Chart
async function updateMonthlyTrends() {
    const year = document.getElementById('yearFilter').value;
    const gradeLevel = document.getElementById('gradeLevelFilter').value;
    const yearLevel = document.getElementById('yearLevelFilter').value;
    const strand = document.getElementById('strandFilter').value;
    const course = document.getElementById('courseFilter').value;
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getMonthlyTrends');
        formData.append('year', year);
        formData.append('gradeLevel', gradeLevel);
        formData.append('yearLevel', yearLevel);
        formData.append('strand', strand);
        formData.append('course', course);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
            
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Fill in all months with 0 if no data
            const monthlyData = new Array(12).fill(0);
            result.data.forEach(item => {
                monthlyData[item.month - 1] = item.count;
            });
            
            if (monthlyTrendsChart) {
                monthlyTrendsChart.destroy();
            }
            
            monthlyTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthNames,
                    datasets: [{
                        label: 'Cases',
                        data: monthlyData,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error updating monthly trends:', error);
    }
}

// Export statistics
function exportStatistics() {
    alert('Export functionality - Generate PDF/Excel report with current statistics');
    // TODO: Implement actual export functionality
}