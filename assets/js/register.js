document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    
    if (form) {
        // Password strength checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function checkPasswordStrength() {
            const value = password.value;
            const requirements = {
                length: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(value)
            };
            
            // Update UI for requirements (you can add visual indicators)
            const strength = Object.values(requirements).filter(Boolean).length;
            return strength;
        }
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#ff4444';
                    return false;
                } else {
                    confirmPassword.style.borderColor = '#00C851';
                    return true;
                }
            }
            return true;
        }
        
        password.addEventListener('keyup', function() {
            checkPasswordStrength();
            if (confirmPassword.value.length > 0) {
                checkPasswordMatch();
            }
        });
        
        confirmPassword.addEventListener('keyup', checkPasswordMatch);
        
        // Form submission
        form.addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const terms = document.querySelector('input[name="terms"]').checked;
            
            // Validation
            if (!firstName || !lastName || !username || !email || !password || !confirm) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Password validation
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter');
                return false;
            }
            
            if (!/[a-z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one lowercase letter');
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number');
                return false;
            }
            
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one special character');
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-register');
            submitBtn.textContent = 'Creating Account...';
            submitBtn.disabled = true;
        });
    }
});