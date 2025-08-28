// Frontend JavaScript for CNW Charts
// This file handles any frontend chart interactions

document.addEventListener('DOMContentLoaded', function() {
    // Add responsive behavior to charts
    var charts = document.querySelectorAll('[id^="cnw-chart-"]');
    
    charts.forEach(function(chartCanvas) {
        // Ensure charts are responsive
        if (chartCanvas.style.width && chartCanvas.style.height) {
            var container = chartCanvas.parentNode;
            container.style.position = 'relative';
        }
    });
    
    // Handle window resize for better responsiveness
    window.addEventListener('resize', function() {
        // Chart.js handles responsiveness automatically
        // This is just a placeholder for any custom resize logic
    });
});