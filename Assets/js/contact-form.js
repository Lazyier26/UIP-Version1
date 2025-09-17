            // Form submission handler
            document.getElementById('contactForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent actual form submission
                
                // Show success popup
                showSuccessPopup();
                
                // Clear form fields
                this.reset();
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

            // Close popup when clicking outside the content
            document.getElementById('successPopup').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeSuccessPopup();
                }
            });