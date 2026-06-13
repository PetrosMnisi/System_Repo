/**
 * IDMA SMS/LMS - Main JavaScript File
 */

// Initialize tooltips and popovers
function initializeBootstrapComponents() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (form.checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
}

// Show alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Confirm action
function confirmAction(message = 'Are you sure?') {
    return confirm(message);
}

// File upload preview
function previewFile(input, previewId) {
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const preview = document.getElementById(previewId);
        if (preview) {
            if (file.type.startsWith('image/')) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" alt="Preview">';
            } else if (file.type === 'application/pdf') {
                preview.innerHTML = '<i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i><p>PDF Preview</p>';
            } else {
                preview.innerHTML = '<i class="fas fa-file" style="font-size: 48px; color: #17a2b8;"></i><p>' + file.name + '</p>';
            }
        }
    };
    
    if (file) {
        reader.readAsDataURL(file);
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-SZ', {
        style: 'currency',
        currency: 'SZL'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-SZ', options);
}

// API call helper
async function apiCall(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(endpoint, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'API request failed' };
    }
}

// Table search
function searchTable(tableId, searchInputId) {
    const input = document.getElementById(searchInputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const text = rows[i].textContent.toUpperCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

// Logout
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/idma-sms-lms/logout';
    }
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeBootstrapComponents();
});
