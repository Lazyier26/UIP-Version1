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
    const cvFile = form.querySelector('#incoming-cv')?.files[0];
    const pictureFile = form.querySelector('#incoming-picture')?.files[0];
    
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
    const endorsementFile = form.querySelector('#incoming-endorsement')?.files[0];
    if (endorsementFile && endorsementFile.size > maxSize) {
        errors.push('Endorsement letter file is too large (max 10MB)');
    }
    
    const moaFile = form.querySelector('#incoming-moa')?.files[0];
    if (moaFile && moaFile.size > maxSize) {
        errors.push('MOA file is too large (max 10MB)');
    }
    
    return errors;
}

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    if (!errorDiv) {
        console.error('Error message div not found');
        alert('Error: ' + message);
        return;
    }
    
    errorDiv.innerHTML = message.replace(/\n/g, '<br>');
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
    if (!successDiv) {
        console.error('Success message div not found');
        alert('Success: ' + message);
        return;
    }
    
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
    if (loadingOverlay) {
        loadingOverlay.style.display = show ? 'block' : 'none';
    }
}

function resetForm(form) {
    form.reset();
    // Hide all error messages
    const errorDivs = form.querySelectorAll('.form-error');
    errorDivs.forEach(div => div.style.display = 'none');
}

// Improved form submission handler with better error handling
async function handleFormSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Hide previous messages
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
    
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
    if (!termsCheckbox?.checked) {
        showError('You must agree to the Terms and Conditions');
        return;
    }
    
    try {
        showLoading(true);
        
        // Determine submit URL with better logic
        const currentUrl = window.location.href;
        let submitUrl;
        
        if (window.location.protocol === 'file:') {
            // File protocol - show error
            throw new Error('This form cannot be submitted when opened directly from file explorer. Please use a web server like XAMPP, WAMP, or run "php -S localhost:8000" in your project folder.');
        } else {
            // Use relative path - works for both localhost and production
            submitUrl = '/Backend/submit-incoming.php';
        }
        
        console.log('Current URL:', currentUrl);
        console.log('Submitting to:', submitUrl);
        console.log('Form data fields:', Array.from(formData.keys()));
        
        // Log form data for debugging
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}: File - ${value.name} (${value.size} bytes)`);
            } else {
                console.log(`${key}: ${value}`);
            }
        }
        
        const response = await fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        // Get the raw response text first
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Check if response is OK
        if (!response.ok) {
            console.error('Server error response:', responseText);
            
            // More specific error messages based on status code
            let errorMessage = '';
            switch (response.status) {
                case 404:
                    errorMessage = `File not found: ${submitUrl}. Please check if the PHP file exists.`;
                    break;
                case 500:
                    errorMessage = 'Server internal error. Please check PHP error logs.';
                    break;
                case 413:
                    errorMessage = 'File too large. Please reduce file sizes.';
                    break;
                default:
                    errorMessage = `Server error (${response.status}): ${response.statusText}`;
            }
            
            throw new Error(errorMessage);
        }
        
        // Try to parse JSON response
        let result;
        try {
            // Check if response looks like JSON
            const trimmedResponse = responseText.trim();
            if (trimmedResponse.startsWith('{') || trimmedResponse.startsWith('[')) {
                result = JSON.parse(trimmedResponse);
            } else {
                // Response is not JSON - likely HTML error page
                console.error('Non-JSON response received:', trimmedResponse);
                
                // Check for common PHP errors in the HTML
                if (trimmedResponse.includes('Parse error') || 
                    trimmedResponse.includes('Fatal error') || 
                    trimmedResponse.includes('Warning:') ||
                    trimmedResponse.includes('Notice:')) {
                    throw new Error('PHP error detected. Please check the submit-incoming.php file for syntax errors. Check browser console for details.');
                } else if (trimmedResponse.includes('<!DOCTYPE') || 
                          trimmedResponse.includes('<html')) {
                    throw new Error('Server returned HTML instead of JSON. This usually means the PHP file has errors or is not found.');
                } else {
                    throw new Error(`Unexpected response format: ${trimmedResponse.substring(0, 200)}...`);
                }
            }
        } catch (parseError) {
            console.error('Failed to parse JSON:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid server response. Please check the PHP file and server logs.');
        }
        
        console.log('Parsed result:', result);
        
        // Handle the response
        if (result && result.success) {
            showSuccess(result.message || 'Registration submitted successfully!');
            resetForm(form);
            
            // Scroll to top after successful submission
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 2000);
        } else {
            const errorMessage = result?.message || result?.error || 'Registration failed. Please try again.';
            showError(errorMessage);
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        
        let errorMessage = 'An error occurred while submitting your registration. ';
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage += 'Network error. Please check your internet connection and try again.';
        } else if (error.message.includes('CORS')) {
            errorMessage += 'Please ensure you are accessing this page through a web server (http://localhost), not directly from file explorer.';
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
    console.log('Current URL:', window.location.href);
    console.log('Protocol:', window.location.protocol);
    
    // Check if running from file://
    if (window.location.protocol === 'file:') {
        console.warn('⚠️ Running from file:// protocol. Form submission will not work. Please use a web server.');
        showError('Please open this page through a web server (http://localhost) instead of opening the HTML file directly.');
    }
    
    // Attach form submission handler
    const form = document.getElementById('incoming-form-form');
    if (form) {
        form.addEventListener('submit', handleFormSubmission);
        console.log('Form submission handler attached');
    } else {
        console.error('Form with ID "incoming-form-form" not found!');
        console.log('Available forms:', document.querySelectorAll('form'));
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
    showError('An unexpected error occurred. Please refresh the page and try again.');
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    showError('An unexpected error occurred. Please refresh the page and try again.');
});