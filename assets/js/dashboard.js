document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    // Add logout confirmation
    const logoutBtn = document.querySelector('.btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    }
    
    // Add smooth hover effects for cards
    const cards = document.querySelectorAll('.stat-card, .action-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.3s ease';
        });
    });
    
    // Add click handlers for action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Check if the link is active (not just a placeholder)
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                showNotification('This feature is coming soon!', 'info');
            }
        });
    });
    
    // Update time-based greeting
    updateGreeting();
    
    // Check for new notifications (simulated)
    checkNotifications();
});

function updateGreeting() {
    const hour = new Date().getHours();
    const welcomeText = document.querySelector('.welcome-text');
    
    if (welcomeText) {
        let greeting;
        if (hour < 12) {
            greeting = 'Good morning';
        } else if (hour < 18) {
            greeting = 'Good afternoon';
        } else {
            greeting = 'Good evening';
        }
        
        const currentText = welcomeText.textContent;
        welcomeText.textContent = currentText.replace('Welcome', greeting);
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'error' ? '#ff4444' : type === 'success' ? '#00C851' : '#33b5e5'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function checkNotifications() {
    // Simulate checking for notifications
    // In a real application, you would make an AJAX call here
    setTimeout(() => {
        // Example: showNotification('You have new pending leave requests', 'info');
    }, 2000);
}