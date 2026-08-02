/**
 * Shop Giày NMK - Main Javascript File
 */

document.addEventListener("DOMContentLoaded", function () {
    // Khởi tạo biểu đồ doanh thu nếu canvas #revenueChart tồn tại trên trang
    const chartCanvas = document.getElementById('revenueChart');
    if (chartCanvas && typeof revenueDataFromPHP !== 'undefined') {
        initRevenueChart(chartCanvas, revenueDataFromPHP);
    }
});

/**
 * @param {HTMLCanvasElement} canvasElement 
 * @param {Array} monthlyData 
 */
function initRevenueChart(canvasElement, monthlyData) {
    const ctx = canvasElement.getContext('2d');
    const labels = ['Thg 1', 'Thg 2', 'Thg 3', 'Thg 4', 'Thg 5', 'Thg 6', 'Thg 7', 'Thg 8', 'Thg 9', 'Thg 10', 'Thg 11', 'Thg 12'];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: monthlyData,
                backgroundColor: 'rgba(255, 120, 0, 0.75)',
                borderColor: 'rgba(255, 120, 0, 1)',
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let value = context.raw || 0;
                            return ' Doanh thu: ' + value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            }
        }
    });
}