// Form submission handler
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    
    const submitBtn = document.querySelector('.submit-btn');
    const originalBtnText = submitBtn.textContent;
    
    // Show loading state
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;
    
    // Get form data
    const formData = new FormData(this);
    
    // Send the form data to PHP backend
    fetch('Backend/send-email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, treat as text (for debugging)
            return response.text().then(text => {
                throw new Error(`Server returned non-JSON response: ${text}`);
            });
        }
    })
    .then(data => {
        // Reset button state
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
        
        if (data.success) {
            // Show success popup
            showSuccessPopup();
            // Clear form fields
            document.getElementById('contactForm').reset();
        } else {
            // Show error message
            showErrorMessage(data.message || 'An error occurred while sending your message.');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        
        // Reset button state
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
        
        // Show error message
        showErrorMessage('Network error. Please check your connection and try again.');
    });
});

// Show success popup
function showSuccessPopup() {
    const popup = document.getElementById('successPopup');
    popup.classList.add('show');
}

// Close success popup
function closeSuccessPopup() {
    const popup = document.getElementById('successPopup');
    popup.classList.remove('show');
}

// Show error message
function showErrorMessage(message) {
    // Create error popup if it doesn't exist
    let errorPopup = document.getElementById('errorPopup');
    if (!errorPopup) {
        errorPopup = createErrorPopup();
        document.body.appendChild(errorPopup);
    }
    
    // Update error message
    const errorMessage = errorPopup.querySelector('.error-message');
    errorMessage.textContent = message;
    
    // Show error popup
    errorPopup.classList.add('show');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        closeErrorPopup();
    }, 5000);
}

// Create error popup element
function createErrorPopup() {
    const popup = document.createElement('div');
    popup.id = 'errorPopup';
    popup.className = 'popup-overlay';
    popup.innerHTML = `
        <div class="popup-content error-popup">
            <div class="popup-icon error-icon">❌</div>
            <h2 class="popup-title">Error</h2>
            <p class="error-message"></p>
            <button class="popup-close-btn" onclick="closeErrorPopup()">OK</button>
        </div>
    `;
    
    // Add click outside to close functionality
    popup.addEventListener('click', function(e) {
        if (e.target === this) {
            closeErrorPopup();
        }
    });
    
    return popup;
}

// Close error popup
function closeErrorPopup() {
    const popup = document.getElementById('errorPopup');
    if (popup) {
        popup.classList.remove('show');
    }
}

// Close success popup when clicking outside the content
document.getElementById('successPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessPopup();
    }
});

// Form validation (optional enhancement)
function validateForm() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const message = document.getElementById('message').value.trim();
    
    // Basic validation
    if (!firstName || !lastName || !email || !message) {
        showErrorMessage('Please fill in all required fields.');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showErrorMessage('Please enter a valid email address.');
        return false;
    }
    
    return true;
}

// Add real-time validation feedback (optional)
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#contactForm input, #contactForm textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
});

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required.');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

// Show field-specific error
function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorElement = document.createElement('span');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.color = '#ff4444';
    errorElement.style.fontSize = '12px';
    errorElement.style.display = 'block';
    errorElement.style.marginTop = '5px';
    
    field.style.borderColor = '#ff4444';
    field.parentNode.appendChild(errorElement);
}

// Clear field error
function clearFieldError(field) {
    field.style.borderColor = '';
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}