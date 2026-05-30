/**
 * Sidebar Navigation Animations & Interactions
 * Professional, clean, modern animations for ONINS dashboard
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initSidebarAnimations();
        initPageTransitions();
        initLoadingIndicator();
    });

    /**
     * Sidebar menu item click animations
     */
    function initSidebarAnimations() {
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            // Add ripple effect on click
            item.addEventListener('click', function(e) {
                // Skip if it's already the active page
                if (this.classList.contains('active')) {
                    return;
                }

                // Create ripple
                const ripple = document.createElement('span');
                ripple.className = 'click-ripple';
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.4);
                    transform: scale(0);
                    animation: rippleEffect 0.5s ease-out;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                // Remove ripple after animation
                setTimeout(() => ripple.remove(), 500);
            });

            // Hover sound effect (optional - subtle click feedback)
            item.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            item.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rippleEffect {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .menu-item {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Optional top loading bar on internal navigation (no artificial delay).
     */
    function initPageTransitions() {
        const links = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])');

        links.forEach(link => {
            link.addEventListener('click', function () {
                const href = this.getAttribute('href');

                if (this.classList.contains('active')) {
                    return;
                }

                if (href && (href.startsWith('http') || href.startsWith('//'))) {
                    return;
                }

                if (href && !href.startsWith('#')) {
                    showLoadingIndicator();
                }
            });
        });
    }

    /**
     * Loading indicator for page transitions
     */
    function initLoadingIndicator() {
        // Create loading bar element
        const loadingBar = document.createElement('div');
        loadingBar.className = 'page-loading-bar';
        loadingBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 3px;
            background: transparent;
            z-index: 10000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        `;
        
        const loadingInner = document.createElement('div');
        loadingInner.style.cssText = `
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #f97316, #ea580c, #fb923c);
            transition: width 0.3s ease;
        `;
        
        loadingBar.appendChild(loadingInner);
        document.body.appendChild(loadingBar);
        
        // Store reference
        window.pageLoadingBar = {
            element: loadingBar,
            inner: loadingInner,
            show: function() {
                this.element.style.opacity = '1';
                this.inner.style.width = '30%';
            },
            progress: function() {
                this.inner.style.width = '70%';
            },
            hide: function() {
                this.inner.style.width = '100%';
                setTimeout(() => {
                    this.element.style.opacity = '0';
                    setTimeout(() => {
                        this.inner.style.width = '0%';
                    }, 200);
                }, 300);
            }
        };
        
        // Show loading on initial page load
        window.addEventListener('load', function() {
            if (window.pageLoadingBar) {
                window.pageLoadingBar.hide();
            }
        });
    }

    function showLoadingIndicator() {
        if (window.pageLoadingBar) {
            window.pageLoadingBar.show();
            setTimeout(() => window.pageLoadingBar.progress(), 100);
        }
    }

    /**
     * Staggered animation for dynamically added content
     */
    window.animateNewContent = function(elements) {
        elements.forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
    };

})();
