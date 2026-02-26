document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    
    // Toggle sidebar collapse
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
            
            // Save sidebar state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Change icon direction
            const icon = this.querySelector('i');
            if (icon) {
                if (isCollapsed) {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
            }
        });
    }
    
    // Check for saved sidebar state
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
        if (sidebarToggle) {
            const icon = sidebarToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            }
        }
    }
    
    // Mobile menu toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('show');
            
            // Add overlay for mobile
            if (sidebar.classList.contains('show')) {
                createMobileOverlay();
            } else {
                removeMobileOverlay();
            }
        });
    }
    
    // Create mobile overlay
    function createMobileOverlay() {
        if (!document.getElementById('mobileOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'mobileOverlay';
            overlay.className = 'mobile-overlay';
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                removeMobileOverlay();
            });
            document.body.appendChild(overlay);
        }
    }
    
    // Remove mobile overlay
    function removeMobileOverlay() {
        const overlay = document.getElementById('mobileOverlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 1024;
        if (isMobile && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('show');
                removeMobileOverlay();
            }
        }
    });
    
    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.has-submenu');
            
            // Toggle current submenu
            parent.classList.toggle('open');
            
            // Save submenu state to localStorage
            const submenuId = parent.querySelector('span') ? 
                parent.querySelector('span').textContent.trim().replace(/\s+/g, '_') : 
                'submenu_' + Math.random().toString(36).substr(2, 9);
            
            const isOpen = parent.classList.contains('open');
            localStorage.setItem('submenu_' + submenuId, isOpen);
        });
    });
    
    // Restore submenu states from localStorage
    function restoreSubmenuStates() {
        const submenus = document.querySelectorAll('.has-submenu');
        submenus.forEach(submenu => {
            const span = submenu.querySelector('span');
            if (span) {
                const submenuId = span.textContent.trim().replace(/\s+/g, '_');
                const savedState = localStorage.getItem('submenu_' + submenuId);
                
                // Only restore if saved state exists
                if (savedState !== null) {
                    if (savedState === 'true') {
                        submenu.classList.add('open');
                    } else {
                        submenu.classList.remove('open');
                    }
                }
            }
        });
    }
    
    // Restore submenu states after page load
    restoreSubmenuStates();
    
    // Handle active menu highlighting
    function highlightActiveMenu() {
        const currentPage = window.location.pathname.split('/').pop();
        
        // Remove active class from all nav items and submenu links
        document.querySelectorAll('.nav-item, .submenu a').forEach(el => {
            el.classList.remove('active');
        });
        
        // Find and highlight the current page link
        const currentLink = document.querySelector(`.submenu a[href="${currentPage}"]`);
        if (currentLink) {
            currentLink.classList.add('active');
            
            // Open parent submenu
            const parentSubmenu = currentLink.closest('.has-submenu');
            if (parentSubmenu) {
                parentSubmenu.classList.add('open');
            }
        }
        
        // Highlight main nav items that are active
        document.querySelectorAll('.nav-item > a[href="' + currentPage + '"]').forEach(link => {
            link.parentElement.classList.add('active');
        });
    }
    
    // Call highlight function
    highlightActiveMenu();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            if (sidebar) {
                sidebar.classList.remove('show');
                removeMobileOverlay();
            }
        }
        
        // Adjust collapsed state on resize
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            localStorage.setItem('sidebarCollapsed', false);
        }
    });
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Alt + S to toggle sidebar
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            if (sidebarToggle) {
                sidebarToggle.click();
            }
        }
        
        // Escape to close mobile menu
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            removeMobileOverlay();
        }
    });
    
    // Initialize tooltips for collapsed sidebar
    if (sidebar && sidebar.classList.contains('collapsed')) {
        initializeTooltips();
    }
    
    function initializeTooltips() {
        const navLinks = document.querySelectorAll('.sidebar.collapsed .nav-link');
        navLinks.forEach(link => {
            const span = link.querySelector('span');
            if (span && span.textContent) {
                link.setAttribute('title', span.textContent);
            }
        });
    }
    
    // Observe DOM changes for dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Re-initialize tooltips if needed
                if (sidebar && sidebar.classList.contains('collapsed')) {
                    initializeTooltips();
                }
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Add CSS for mobile overlay
const style = document.createElement('style');
style.textContent = `
    .mobile-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }
    
    @media (max-width: 1024px) {
        .mobile-overlay {
            display: block;
        }
    }
    
    .sidebar.collapsed .nav-link[title] {
        position: relative;
    }
    
    .sidebar.collapsed .nav-link[title]:hover::after {
        content: attr(title);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 12px;
        white-space: nowrap;
        margin-left: 10px;
        z-index: 1001;
    }
    
    .sidebar.collapsed .nav-link[title]:hover::before {
        content: '';
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        border-width: 5px;
        border-style: solid;
        border-color: transparent #333 transparent transparent;
        margin-left: 0;
    }
`;
document.head.appendChild(style);