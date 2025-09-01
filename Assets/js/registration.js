 // Toggle registration form visibility
    function toggleForm(type) {
        var form = document.getElementById(type + '-form');
        if (form) {
            form.classList.toggle('active');
        }
    }
    // Scroll to top function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    // Show/hide FAB based on scroll position
    window.addEventListener('scroll', function() {
        const fab = document.querySelector('.fab');
        if (window.scrollY > 300) {
            fab.style.opacity = '1';
            fab.style.pointerEvents = 'all';
        } else {
            fab.style.opacity = '0';
            fab.style.pointerEvents = 'none';
        }
    });
    //  Terms and Conditions Functions
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
    // Form validation and submission
    document.getElementById('incoming-form-form').addEventListener('submit', function(e) {
        e.preventDefault();
        // Check if terms are agreed
        const termsCheckbox = document.getElementById('terms-checkbox');
        const errorTerms = document.getElementById('error-terms');
        if (!termsCheckbox.checked) {
            errorTerms.classList.add('show');
            termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        } else {
            errorTerms.classList.remove('show');
        }
        // Simulate form submission with loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loading"></span> Submitting...';
        submitBtn.disabled = true;
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            const successMessage = document.getElementById('success-message');
            successMessage.style.display = 'block';
            // Reset form
            this.reset();
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 5000);
        }, 2000);
    });
    // Add smooth scrolling for anchor links
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
    // Add active state management
    const currentPage = window.location.hash || '#home';
    document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.style.color = '#a0c4ff';
        }
    });
    // Input focus animations
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
    // Initialize FAB visibility
    document.addEventListener('DOMContentLoaded', function() {
        const fab = document.querySelector('.fab');
        fab.style.opacity = '0';
        fab.style.pointerEvents = 'none';
        fab.style.transition = 'all 0.3s ease';
    });
    // Form validation for required fields
    function validateForm() {
        let isValid = true;
        const requiredFields = document.querySelectorAll('input[required]');
        requiredFields.forEach(field => {
            const errorElement = document.getElementById('error-' + field.id.replace('incoming-', ''));
            if (!field.value.trim()) {
                if (errorElement) {
                    errorElement.classList.add('show');
                }
                isValid = false;
            } else {
                if (errorElement) {
                    errorElement.classList.remove('show');
                }
            }
        });
        // Validate checkboxes for days
        const daysCheckboxes = document.querySelectorAll('input[name="days[]"]:checked');
        const errorDays = document.getElementById('error-incoming-days');
        if (daysCheckboxes.length === 0) {
            errorDays.classList.add('show');
            isValid = false;
        } else {
            errorDays.classList.remove('show');
        }
        return isValid;
    }
    // Real-time validation on input
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('blur', function() {
            const errorElement = document.getElementById('error-' + this.id.replace('incoming-', ''));
            if (this.hasAttribute('required') && !this.value.trim()) {
                if (errorElement) {
                    errorElement.classList.add('show');
                }
            } else {
                if (errorElement) {
                    errorElement.classList.remove('show');
                }
            }
        });
    });