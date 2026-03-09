// Main JavaScript for SBATS

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirm delete actions
    document.querySelectorAll('.confirm-delete').forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic search/filter
    var searchInput = document.getElementById('liveSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var searchTerm = this.value.toLowerCase();
            var tableRows = document.querySelectorAll('#searchableTable tbody tr');
            
            tableRows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                if (text.indexOf(searchTerm) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Refresh data function
    window.refreshData = function(url, targetId) {
        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById(targetId).innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
    };
    
    // Blockchain validation animation
    var validateButton = document.getElementById('validateChain');
    if (validateButton) {
        validateButton.addEventListener('click', function() {
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validating...';
            this.disabled = true;
            
            setTimeout(function() {
                validateButton.innerHTML = 'Validate Chain';
                validateButton.disabled = false;
            }, 2000);
        });
    }
});

// QR Code scanner simulation (for demo purposes)
function scanQRCode() {
    alert('In a production environment, this would activate the device camera to scan QR codes.');
}

// Print asset label
function printLabel(assetId) {
    var printWindow = window.open('', '_blank');
    var content = document.getElementById('label-' + assetId).innerHTML;
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Asset Label</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    .label { border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
                </style>
            </head>
            <body>
                <div class="label">${content}</div>
                <script>
                    window.onload = function() { window.print(); window.close(); }
                </script>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Export data to CSV
function exportToCSV(data, filename) {
    var csv = '';
    var rows = data;
    
    rows.forEach(function(row) {
        csv += row.join(',') + '\n';
    });
    
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
}

// Toggle sidebar on mobile
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}