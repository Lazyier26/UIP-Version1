    <?php
    // Registration Section
    ?>
    <section id="registration" class="registration">
        <div class="container registration-container">
            <div class="section-header">
                <h2>Ready to Start Your Journey?</h2>
                <p>Choose your path and begin your professional development with UIP's comprehensive internship program.</p>
            </div>
            <div class="registration-grid">
                <div class="registration-card">
                    <h3><i class="fas fa-user-plus"></i> Incoming Interns</h3>
                    <p>Are you a student looking to gain practical experience? Register now to join our comprehensive internship program and connect with leading companies across the Philippines.</p>
                    <button class="register-btn" onclick="toggleForm('incoming')">Register as Incoming Intern</button>
                    <div class="registration-form" id="incoming-form">
                        <form id="incoming-form" method="POST" action="/submit-incoming">
                            <div class="form-group">
                                <label for="incoming-name">Full Name <span class="required-asterisk">*</span></label>
                                <input type="text" id="incoming-name" name="name" placeholder="Enter your full name" required>
                                <div class="form-error" id="error-incoming-name">Full Name is required.</div>
                            </div>
                            <div class="form-group">
                                <label for="incoming-email">Email Address <span class="required-asterisk">*</span></label>
                                <input type="email" id="incoming-email" name="email" placeholder="Enter your email" required>
                                <div class="form-error" id="error-incoming-email">Valid Email is required.</div>
                            </div>
                            <div class="form-group">
                                <label for="incoming-phone">Phone Number <span class="required-asterisk">*</span></label>
                                <input type="tel" id="incoming-phone" name="phone" placeholder="Enter your phone number" required pattern="^[0-9+\-\s()]{7,}$">
                                <div class="form-error" id="error-incoming-phone">Phone Number is required.</div>
                            </div>
                            <div class="form-group">
                                <label for="incoming-school">School/University <span class="required-asterisk">*</span></label>
                                <input type="text" id="incoming-school" name="school" placeholder="Enter your school name" required>
                                <div class="form-error" id="error-incoming-school">School/University is required.</div>
                            </div>
                            <div class="form-group">
                                <label for="incoming-course">Course/Program <span class="required-asterisk">*</span></label>
                                <select id="incoming-course" name="course" required>
                                    <option value="">Select your course</option>
                                    <option value="accountancy">Accountancy</option>
                                    <option value="civil-engineering">Civil Engineering</option>
                                    <option value="computer-engineering">Computer Engineering</option>
                                    <option value="electrical-engineering">Electrical Engineering</option>
                                    <option value="electronics-engineering">Electronics Engineering</option>
                                    <option value="marketing-management">Marketing Management</option>
                                    <option value="mechanical-engineering">Mechanical Engineering</option>
                                    <!-- Add more courses as needed -->
                                </select>
                                <div class="form-error" id="error-incoming-course">Course/Program is required.</div>
                            </div>
                            <button type="submit" class="register-btn">Submit Registration</button>
                        </form>
                    </div>
                </div>
                <!-- You can add more registration cards for other user types here -->
            </div>
        </div>
    </section>
    <script>
    // Toggle registration form visibility
    function toggleForm(type) {
        var form = document.getElementById(type + '-form');
        if (form) {
            form.classList.toggle('active');
        }
    }
    </script>
