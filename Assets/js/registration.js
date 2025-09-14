// registration.js

// Toggle form visibility
function toggleForm(formType) {
    const incomingForm = document.getElementById('incoming-form');
    
    if (formType === 'incoming') {
        incomingForm.style.display = 'block';
        // Scroll to form
        incomingForm.scrollIntoView({ behavior: 'smooth' });
    }
}

// Terms modal functions
function showTerms(event) {
    event.preventDefault();
    document.getElementById('terms-modal').style.display = 'flex';
}

function closeTerms() {
    document.getElementById('terms-modal').style.display = 'none';
}

// Scroll to top function
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Form validation functions
function validateForm(formData) {
    const errors = [];
    
    // Required fields validation
    const requiredFields = [
        { key: 'name', message: 'Full Name is required' },
        { key: 'email', message: 'Email is required' },
        { key: 'contact', message: 'Contact Number is required' },
        { key: 'birthday', message: 'Birthday is required' },
        { key: 'address', message: 'Address is required' },
        { key: 'school', message: 'School/University is required' },
        { key: 'program', message: 'College Program is required' },
        { key: 'school_address', message: 'University Address is required' },
        { key: 'ojt_hours', message: 'OJT Hours is required' }
    ];
    
    requiredFields.forEach(field => {
        const value = formData.get(field.key);
        if (!value || value.toString().trim() === '') {
            errors.push(field.message);
        }
    });
    
    // Email validation
    const email = formData.get('email');
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Please enter a valid email address');
    }
    
    // Contact number validation
    const contact = formData.get('contact');
    if (contact && !/^[0-9+\-\s()]{7,}$/.test(contact)) {
        errors.push('Please enter a valid contact number');
    }
    
    // OJT hours validation
    const ojtHours = formData.get('ojt_hours');
    if (ojtHours && (isNaN(ojtHours) || parseInt(ojtHours) < 1)) {
        errors.push('OJT hours must be a positive number');
    }
    
    // Available days validation
    const days = formData.getAll('days[]');
    if (!days || days.length === 0) {
        errors.push('Please select at least one available day');
    }
    
    return errors;
}

function validateFiles(form) {
    const errors = [];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    // Required files
    const cvFile = form.querySelector('#incoming-cv').files[0];
    const pictureFile = form.querySelector('#incoming-picture').files[0];
    
    if (!cvFile) {
        errors.push('CV/Resume is required');
    } else {
        if (cvFile.size > maxSize) {
            errors.push('CV file is too large (max 10MB)');
        }
        if (!cvFile.type.includes('pdf')) {
            errors.push('CV must be a PDF file');
        }
    }
    
    if (!pictureFile) {
        errors.push('2x2 Picture is required');
    } else {
        if (pictureFile.size > maxSize) {
            errors.push('Picture file is too large (max 10MB)');
        }
        if (!['image/jpeg', 'image/jpg', 'image/png'].includes(pictureFile.type)) {
            errors.push('Picture must be JPG, JPEG, or PNG');
        }
    }
    
    // Optional files validation
    const endorsementFile = form.querySelector('#incoming-endorsement').files[0];
    if (endorsementFile && endorsementFile.size > maxSize) {
        errors.push('Endorsement letter file is too large (max 10MB)');
    }
    
    const moaFile = form.querySelector('#incoming-moa').files[0];
    if (moaFile && moaFile.size > maxSize) {
        errors.push('MOA file is too large (max 10MB)');
    }
    
    return errors;
}

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    // Scroll to error message
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Hide after 10 seconds
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 10000);
}

function showSuccess(message) {
    const successDiv = document.getElementById('success-message');
    successDiv.textContent = message || 'Registration submitted successfully! We will contact you soon.';
    successDiv.style.display = 'block';
    
    // Scroll to success message
    successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Hide after 5 seconds
    setTimeout(() => {
        successDiv.style.display = 'none';
    }, 5000);
}

function showLoading(show = true) {
    const loadingOverlay = document.getElementById('loading-overlay');
    loadingOverlay.style.display = show ? 'block' : 'none';
}

function resetForm(form) {
    form.reset();
    // Hide all error messages
    const errorDivs = form.querySelectorAll('.form-error');
    errorDivs.forEach(div => div.style.display = 'none');
}

// Main form submission handler - Updated Version
async function handleFormSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Hide previous messages
    document.getElementById('error-message').style.display = 'none';
    document.getElementById('success-message').style.display = 'none';
    
    // Validate form data
    const validationErrors = validateForm(formData);
    const fileErrors = validateFiles(form);
    const allErrors = [...validationErrors, ...fileErrors];
    
    if (allErrors.length > 0) {
        showError(allErrors.join('\n'));
        return;
    }
    
    // Check terms acceptance
    const termsCheckbox = document.getElementById('terms-checkbox');
    if (!termsCheckbox.checked) {
        showError('You must agree to the Terms and Conditions');
        return;
    }
    
    try {
        showLoading(true);
        
        // Fixed URL determination
        let submitUrl = 'submit-incoming.php'; // Default relative path
        
        // Only change if running on file:// protocol (which shouldn't be used)
        if (window.location.protocol === 'file:') {
            // For XAMPP default setup
            submitUrl = 'http://localhost/UIP-Version1/submit-incoming.php';
            // Show warning about file:// protocol
            console.warn('Running from file:// protocol. Please use a web server instead.');
        }
        
        console.log('Submitting to:', submitUrl);
        console.log('Form data fields:', Array.from(formData.keys()));
        
        const response = await fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Add this for better CORS handling
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Response status:', response.status);
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server error response:', errorText);
            throw new Error(`Server error (${response.status}): ${response.statusText}`);
        }
        
        // Try to parse JSON response
        const contentType = response.headers.get('content-type');
        let result;
        
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            // If not JSON, get text and try to parse
            const text = await response.text();
            console.log('Raw response:', text);
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                console.error('Response text:', text);
                
                // Show more specific error message
                if (text.includes('<br') || text.includes('<html') || text.includes('<!DOCTYPE')) {
                    throw new Error('Server returned HTML instead of JSON. This usually means there are PHP errors. Check the browser console and server logs.');
                } else {
                    throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
                }
            }
        }
        
        console.log('Parsed result:', result);
        
        if (result.success) {
            showSuccess(result.message);
            resetForm(form);
            
            // Scroll to top after successful submission
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 2000);
        } else {
            showError(result.message || 'Registration failed. Please try again.');
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        
        let errorMessage = 'An error occurred while submitting your registration. ';
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage += 'Please check your internet connection and ensure you are accessing this page through a web server (http://localhost), not file://.';
        } else if (error.message.includes('CORS')) {
            errorMessage += 'Please ensure you are accessing this page through a web server (http://localhost), not directly from file explorer.';
        } else if (error.message.includes('Server error')) {
            errorMessage += 'Server error occurred. Please check the PHP error logs and try again.';
        } else {
            errorMessage += error.message || 'Please try again or contact support if the problem persists.';
        }
        
        showError(errorMessage);
    } finally {
        showLoading(false);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Registration page loaded');
    
    // Attach form submission handler
    const form = document.getElementById('incoming-form-form');
    if (form) {
        form.addEventListener('submit', handleFormSubmission);
        console.log('Form submission handler attached');
    } else {
        console.error('Form not found!');
    }
    
    // Close modal when clicking outside
    const modal = document.getElementById('terms-modal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeTerms();
            }
        });
    }
    
    // Add real-time validation
    const inputs = form ? form.querySelectorAll('input[required]') : [];
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const errorDiv = document.getElementById(`error-${this.id}`);
            if (errorDiv) {
                if (this.value.trim() === '') {
                    errorDiv.style.display = 'block';
                } else {
                    errorDiv.style.display = 'none';
                }
            }
        });
    });
    
    // Email validation
    const emailInput = document.getElementById('incoming-email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const errorDiv = document.getElementById('error-incoming-email');
            if (errorDiv) {
                if (this.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                    errorDiv.textContent = 'Please enter a valid email address';
                    errorDiv.style.display = 'block';
                } else if (this.value) {
                    errorDiv.style.display = 'none';
                }
            }
        });
    }
    
    // Contact number validation
    const contactInput = document.getElementById('incoming-contact');
    if (contactInput) {
        contactInput.addEventListener('blur', function() {
            const errorDiv = document.getElementById('error-incoming-contact');
            if (errorDiv) {
                if (this.value && !/^[0-9+\-\s()]{7,}$/.test(this.value)) {
                    errorDiv.textContent = 'Please enter a valid contact number';
                    errorDiv.style.display = 'block';
                } else if (this.value) {
                    errorDiv.style.display = 'none';
                }
            }
        });
    }
    
    // File size validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                alert(`File "${file.name}" is too large. Maximum size is 10MB.`);
                this.value = '';
            }
        });
    });
    
    // Show floating action button on scroll
    const fab = document.querySelector('.fab');
        if (fab) {
            window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            fab.classList.add('show');
        } else {
            fab.classList.remove('show');
        }
    });
}

});

// Handle page visibility change (for debugging)
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        console.log('Page became visible');
    }
});

// Global error handler for unhandled errors
window.addEventListener('error', function(event) {
    console.error('Global error:', event.error);
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
});