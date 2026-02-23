document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (email === '') {
                e.preventDefault();
                alert('Please enter your email address');
                return false;
            }
            
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
        });
    }
});