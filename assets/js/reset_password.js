document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    
    if (form) {
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
            
            // You can add visual feedback here
            const strength = Object.values(requirements).filter(Boolean).length;
            
            // Simple border color based on strength
            if (value.length > 0) {
                if (strength < 3) {
                    password.style.borderColor = '#ff4444';
                } else if (strength < 5) {
                    password.style.borderColor = '#ffbb33';
                } else {
                    password.style.borderColor = '#00C851';
                }
            }
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
            submitBtn.textContent = 'Resetting...';
            submitBtn.disabled = true;
        });
    }
});