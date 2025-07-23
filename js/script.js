/**
 * SimpleEats - Main JavaScript file
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Enable Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }
    
    // Confirm deletion
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
    
    // Quantity input validation in order form
    const quantityInputs = document.querySelectorAll('input[type="number"][name="quantity"]');
    if (quantityInputs) {
        quantityInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const val = parseInt(this.value);
                if (isNaN(val) || val < 1) {
                    this.value = 1;
                }
            });
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    if (forms) {
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Filter dropdowns auto-submit
    const filterSelects = document.querySelectorAll('select[data-autosubmit]');
    if (filterSelects) {
        filterSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    }
    
    // Price formatter for inputs
    const priceInputs = document.querySelectorAll('input[data-type="price"]');
    if (priceInputs) {
        priceInputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                const value = parseFloat(this.value);
                if (!isNaN(value)) {
                    this.value = value.toFixed(2);
                }
            });
        });
    }
});
