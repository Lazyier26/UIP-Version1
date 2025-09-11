// Assets/js/registration.js

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Main initialization function
function initializeApp() {
    initializeFormHandling();
    initializeUIFeatures();
    initializeValidation();
    initializeFAB();
}

// ===== FORM HANDLING =====
function initializeFormHandling() {
    const form = document.getElementById('incoming-form-form');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearFormErrors();
        
        // Validate form before submission
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        try {
            // Create FormData object
            const formData = new FormData(form);
            
            // Submit form via AJAX
            const response = await fetch('submit-incoming.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                showSuccessMessage('Registration submitted successfully! We\'ll contact you soon.');
                
                // Reset form
                form.reset();
                
                // Optionally redirect after delay
                setTimeout(() => {
                    window.location.href = 'registration-success.html';
                }, 3000);
                
            } else {
                throw new Error(result.message || 'Registration failed');
            }
            
        } catch (error) {
            console.error('Registration error:', error);
            showErrorMessage(error.message || 'An error occurred during registration. Please try again.');
            
        } finally {
            setLoadingState(false);
        }
    });
}

// ===== FORM VALIDATION =====
function initializeValidation() {
    const form = document.getElementById('incoming-form-form');
    if (!form) return;

    // File upload validation
    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateFileInput(this);
        });
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
}

function validateForm() {
    let isValid = true;
    
    // Validate required text fields
    const requiredFields = [
        { id: 'incoming-name', message: 'Full Name is required.' },
        { id: 'incoming-email', message: 'Valid Email is required.' },
        { id: 'incoming-contact', message: 'Contact Number is required.' },
        { id: 'incoming-birthday', message: 'Valid date is required.' },
        { id: 'incoming-address', message: 'Valid Address is required.' },
        { id: 'incoming-school', message: 'School/University is required.' },
        { id: 'incoming-program', message: 'College Program is required.' },
        { id: 'incoming-school-address', message: 'University Address is required.' },
        { id: 'incoming-ojt-hours', message: 'Total OJT Hours is required.' }
    ];
    
    requiredFields.forEach(field => {
        const input = document.getElementById(field.id);
        if (!input) return;
        
        if (!input.value.trim()) {
            showFieldError(field.id, field.message);
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });
    
    // Validate email format
    const email = document.getElementById('incoming-email');
    if (email && email.value && !isValidEmail(email.value)) {
        showFieldError('incoming-email', 'Please enter a valid email address.');
        isValid = false;
    }
    
    // Validate contact number
    const contact = document.getElementById('incoming-contact');
    if (contact && contact.value && !isValidPhone(contact.value)) {
        showFieldError('incoming-contact', 'Please enter a valid contact number.');
        isValid = false;
    }
    
    // Validate birthday
    const birthday = document.getElementById('incoming-birthday');
    if (birthday && birthday.value && !isValidAge(birthday.value)) {
        showFieldError('incoming-birthday', 'You must be at least 16 years old.');
        isValid = false;
    }
    
    // Validate OJT hours
    const ojtHours = document.getElementById('incoming-ojt-hours');
    if (ojtHours && ojtHours.value && (parseInt(ojtHours.value) <= 0 || parseInt(ojtHours.value) > 2000)) {
        showFieldError('incoming-ojt-hours', 'OJT hours must be between 1 and 2000.');
        isValid = false;
    }
    
    // Validate available days
    const days = document.querySelectorAll('input[name="days[]"]:checked');
    if (days.length === 0) {
        showFieldError('incoming-days', 'Please select at least one available day.');
        isValid = false;
    }
    
    // Validate required files
    const cvFile = document.getElementById('incoming-cv');
    if (cvFile && !cvFile.files.length) {
        showFieldError('incoming-cv', 'Please upload your CV or resume.');
        isValid = false;
    }
    
    const pictureFile = document.getElementById('incoming-picture');
    if (pictureFile && !pictureFile.files.length) {
        showFieldError('incoming-picture', 'Please upload your 2x2 picture.');
        isValid = false;
    }
    
    // Validate terms acceptance
    const termsCheckbox = document.getElementById('terms-checkbox');
    if (termsCheckbox && !termsCheckbox.checked) {
        showFieldError('terms', 'You must agree to the Terms and Conditions.');
        isValid = false;
    }
    
    return isValid;
}

function validateField(field) {
    const fieldId = field.id;
    const value = field.value.trim();
    
    clearFieldError(field);
    
    switch(fieldId) {
        case 'incoming-email':
            if (value && !isValidEmail(value)) {
                showFieldError(fieldId, 'Please enter a valid email address.');
            }
            break;
        case 'incoming-contact':
            if (value && !isValidPhone(value)) {
                showFieldError(fieldId, 'Please enter a valid contact number.');
            }
            break;
        case 'incoming-birthday':
            if (value && !isValidAge(value)) {
                showFieldError(fieldId, 'You must be at least 16 years old.');
            }
            break;
        case 'incoming-ojt-hours':
            const hours = parseInt(value);
            if (value && (hours <= 0 || hours > 2000)) {
                showFieldError(fieldId, 'OJT hours must be between 1 and 2000.');
            }
            break;
    }
}

function validateFileInput(input) {
    const file = input.files[0];
    if (!file) return;
    
    const maxSize = 10 * 1024 * 1024; // 10MB
    const fieldId = input.id;
    
    // Check file size
    if (file.size > maxSize) {
        showFieldError(fieldId, 'File size must be less than 10MB.');
        input.value = '';
        return;
    }
    
    // Check file type based on input
    let allowedTypes = [];
    if (fieldId === 'incoming-cv') {
        allowedTypes = ['pdf'];
    } else if (fieldId === 'incoming-picture') {
        allowedTypes = ['jpg', 'jpeg', 'png'];
    } else if (fieldId === 'incoming-endorsement' || fieldId === 'incoming-moa') {
        allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    }
    
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (allowedTypes.length && !allowedTypes.includes(fileExtension)) {
        showFieldError(fieldId, `Allowed file types: ${allowedTypes.join(', ')}`);
        input.value = '';
        return;
    }
    
    clearFieldError(input);
}

// ===== VALIDATION HELPER FUNCTIONS =====
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[0-9+\-\s()]{7,}$/.test(phone);
}

function isValidAge(birthdate) {
    const today = new Date();
    const birth = new Date(birthdate);
    const age = today.getFullYear() - birth.getFullYear();
    
    if (today.getMonth() < birth.getMonth() || 
        (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) {
        return age - 1 >= 16;
    }
    
    return age >= 16;
}

// ===== ERROR HANDLING =====
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const formGroup = field.closest('.form-group');
    const errorDiv = document.getElementById(`error-${fieldId}`);
    
    if (formGroup) formGroup.classList.add('error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
    }
}

function clearFieldError(field) {
    const fieldId = field.id;
    const formGroup = field.closest('.form-group');
    const errorDiv = document.getElementById(`error-${fieldId}`);
    
    if (formGroup) formGroup.classList.remove('error');
    if (errorDiv) {
        errorDiv.classList.remove('show');
    }
}

function clearFormErrors() {
    const errorDivs = document.querySelectorAll('.form-error');
    const formGroups = document.querySelectorAll('.form-group.error');
    
    errorDivs.forEach(div => div.classList.remove('show'));
    formGroups.forEach(group => group.classList.remove('error'));
    
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) errorMessage.style.display = 'none';
}

function showSuccessMessage(message) {
    const successDiv = document.getElementById('success-message');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.scrollIntoView({ behavior: 'smooth' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 5000);
    }
}

function showErrorMessage(message) {
    const errorDiv = document.getElementById('error-message');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.scrollIntoView({ behavior: 'smooth' });
    }
}

function setLoadingState(loading) {
    const submitButton = document.querySelector('#incoming-form-form button[type="submit"]');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    if (submitButton) {
        if (loading) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="loading"></span> Submitting...';
            if (loadingOverlay) loadingOverlay.style.display = 'block';
        } else {
            submitButton.disabled = false;
            submitButton.textContent = 'Submit Registration';
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        }
    }
}

// ===== UI FEATURES =====
function initializeUIFeatures() {
    // Initialize smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Initialize input focus animations
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
    
    // Initialize active navigation state
    const currentPage = window.location.hash || '#home';
    document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.style.color = '#a0c4ff';
        }
    });
    
    // Initialize terms and conditions modal
    initializeTermsModal();
}

// ===== FLOATING ACTION BUTTON (FAB) =====
function initializeFAB() {
    const fab = document.querySelector('.fab');
    if (!fab) return;
    
    // Initialize FAB visibility
    fab.style.opacity = '0';
    fab.style.pointerEvents = 'none';
    fab.style.transition = 'all 0.3s ease';
    
    // Show/hide FAB based on scroll position
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            fab.style.opacity = '1';
            fab.style.pointerEvents = 'all';
        } else {
            fab.style.opacity = '0';
            fab.style.pointerEvents = 'none';
        }
    });
}

//  Terms and Conditions Functions (Original Implementation)
function showTerms(e) {
    e.preventDefault();
    e.stopPropagation();
    const modal = document.getElementById('terms-modal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeTerms() {
    const modal = document.getElementById('terms-modal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function initializeTermsModal() {
    // Close modal when clicking outside the content
    document.getElementById('terms-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTerms();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTerms();
        }
    });
}

// ===== UTILITY FUNCTIONS =====
function toggleForm(type) {
    const form = document.getElementById(type + '-form');
    if (form) {
        form.classList.toggle('active');
        // Also support the original style-based approach
        if (form.style.display === 'none' || !form.style.display) {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// ===== GLOBAL EVENT HANDLERS =====
// Close modal when clicking outside (fallback)
window.onclick = function(event) {
    const modal = document.getElementById('terms-modal');
    if (event.target === modal) {
        closeTerms();
    }
}