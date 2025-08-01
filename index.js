// Simple client-side validation for incoming-form
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('incoming-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;
            // Name
            var name = document.getElementById('incoming-name');
            var nameError = document.getElementById('error-incoming-name');
            if (!name.value.trim()) {
                nameError.style.display = 'block';
                valid = false;
            } else {
                nameError.style.display = 'none';
            }
            // Email
            var email = document.getElementById('incoming-email');
            var emailError = document.getElementById('error-incoming-email');
            if (!email.value.trim() || !email.checkValidity()) {
                emailError.style.display = 'block';
                valid = false;
            } else {
                emailError.style.display = 'none';
            }
            // Phone
            var phone = document.getElementById('incoming-phone');
            var phoneError = document.getElementById('error-incoming-phone');
            if (!phone.value.trim() || !phone.checkValidity()) {
                phoneError.style.display = 'block';
                valid = false;
            } else {
                phoneError.style.display = 'none';
            }
            // School
            var school = document.getElementById('incoming-school');
            var schoolError = document.getElementById('error-incoming-school');
            if (!school.value.trim()) {
                schoolError.style.display = 'block';
                valid = false;
            } else {
                schoolError.style.display = 'none';
            }
            // Course
            var course = document.getElementById('incoming-course');
            if (!course.value) {
                course.classList.add('invalid');
                valid = false;
            } else {
                course.classList.remove('invalid');
            }
            if (!valid) e.preventDefault();
        });
        // Hide error on input
        ['incoming-name','incoming-email','incoming-phone','incoming-school','incoming-course'].forEach(function(id){
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function(){
                    var err = document.getElementById('error-' + id);
                    if (err) err.style.display = 'none';
                });
            }
        });
    }
});
