document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    
    if (form) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password requirements elements
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');
        const passwordMatch = document.getElementById('password-match');
        
        function checkPasswordStrength() {
            const value = password.value;
            
            // Check requirements
            const hasLength = value.length >= 8;
            const hasUppercase = /[A-Z]/.test(value);
            const hasLowercase = /[a-z]/.test(value);
            const hasNumber = /[0-9]/.test(value);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);
            
            // Update requirement indicators
            updateRequirement(reqLength, hasLength);
            updateRequirement(reqUppercase, hasUppercase);
            updateRequirement(reqLowercase, hasLowercase);
            updateRequirement(reqNumber, hasNumber);
            updateRequirement(reqSpecial, hasSpecial);
            
            return hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
        }
        
        function updateRequirement(element, met) {
            if (element) {
                element.style.color = met ? '#00C851' : '#ff4444';
                element.style.textDecoration = met ? 'line-through' : 'none';
            }
        }
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#ff4444';
                    if (passwordMatch) {
                        passwordMatch.textContent = 'Passwords do not match';
                        passwordMatch.style.color = '#ff4444';
                    }
                    return false;
                } else {
                    confirmPassword.style.borderColor = '#00C851';
                    if (passwordMatch) {
                        passwordMatch.textContent = 'Passwords match';
                        passwordMatch.style.color = '#00C851';
                    }
                    return true;
                }
            } else {
                confirmPassword.style.borderColor = '#e0e0e0';
                if (passwordMatch) {
                    passwordMatch.textContent = '';
                }
                return false;
            }
        }
        
        function validateForm() {
            const isStrong = checkPasswordStrength();
            const doMatch = checkPasswordMatch();
            const hasValue = password.value.length > 0 && confirmPassword.value.length > 0;
            
            if (submitBtn) {
                submitBtn.disabled = !(isStrong && doMatch && hasValue);
            }
        }
        
        password.addEventListener('keyup', validateForm);
        confirmPassword.addEventListener('keyup', validateForm);
        
        form.addEventListener('submit', function(e) {
            const newPassword = password.value;
            const confirm = confirmPassword.value;
            
            if (!newPassword || !confirm) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            // Password strength validation
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (!/[A-Z]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter');
                return false;
            }
            
            if (!/[a-z]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one lowercase letter');
                return false;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one number');
                return false;
            }
            
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one special character');
                return false;
            }
            
            if (newPassword !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-reset');
            submitBtn.textContent = 'Resetting Password...';
            submitBtn.disabled = true;
        });
    }
});