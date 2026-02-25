document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verifyForm');
    const codeInput = document.getElementById('verification_code');
    
    if (form) {
        // Format code input
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });
        
        // Handle paste events
        codeInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                if (this.value.length === 6) {
                    // Optional: auto-submit on paste
                    // document.querySelector('button[name="verify_code"]').click();
                }
            }, 100);
        });
        
        // Show loading state on submit
        form.addEventListener('submit', function(e) {
            const code = codeInput.value.trim();
            
            if (code.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit verification code');
                return false;
            }
            
            if (!/^\d+$/.test(code)) {
                e.preventDefault();
                alert('Verification code must contain only numbers');
                return false;
            }
            
            const submitBtn = e.submitter;
            if (submitBtn && submitBtn.name === 'verify_code') {
                submitBtn.textContent = 'Verifying...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Countdown timer for code expiry
    const expiryElement = document.querySelector('.expiry');
    if (expiryElement) {
        const expiryText = expiryElement.textContent;
        const expiryTime = expiryText.split(': ')[1];
        
        if (expiryTime) {
            const expiryDate = new Date();
            const [time, period] = expiryTime.split(' ');
            const [hours, minutes] = time.split(':');
            
            let hour = parseInt(hours);
            if (period === 'PM' && hour !== 12) hour += 12;
            if (period === 'AM' && hour === 12) hour = 0;
            
            expiryDate.setHours(hour, parseInt(minutes), 0);
            
            function updateCountdown() {
                const now = new Date();
                const distance = expiryDate - now;
                
                if (distance < 0) {
                    expiryElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Code expired';
                    expiryElement.style.color = '#ff4444';
                    return;
                }
                
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                expiryElement.innerHTML = `<i class="fas fa-clock"></i> Code expires in: ${minutes}m ${seconds}s`;
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
    }
});