document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (username === '' || password === '') {
            e.preventDefault();
            alert('Please fill in all fields');
            return false;
        }
    });
    
    // Clear error message when user starts typing
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorMsg = document.querySelector('.error-message');
            const successMsg = document.querySelector('.success-message');
            if (errorMsg) errorMsg.style.display = 'none';
            if (successMsg) successMsg.style.display = 'none';
        });
    });
});