document.addEventListener("DOMContentLoaded", function() {
    initUserGrowthChart();
    initJobTrendsChart();
});

function initUserGrowthChart() {
    fetchUserGrowthData().then(({ labels, data }) => {
        new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Users',
                    data: data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
}

function initJobTrendsChart() {
    fetchJobTrendsData().then(({ labels, data }) => {
        new Chart(document.getElementById('jobTrendsChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Job Postings',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
}

// Fetch data from PHP file
async function fetchUserGrowthData() {
    const response = await fetch('fetch_user_growth.php');
    return response.json();
}

async function fetchJobTrendsData() {
    const response = await fetch('fetch_job_trends.php');
    return response.json();
}
