/**
 * Micro-interactions and Touch-Friendly Design
 * Handles haptic feedback, smooth animations, and mobile optimizations
 */

class MicroInteractions {
    constructor() {
        this.isMobile = this.detectMobile();
        this.hapticSupported = this.detectHapticSupport();
        this.init();
    }
    
    init() {
        this.setupHapticFeedback();
        this.setupSmoothTransitions();
        this.setupLoadingAnimations();
        this.setupSuccessAnimations();
        this.optimizeForMobile();
    }
    
    detectMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               window.innerWidth <= 768;
    }
    
    detectHapticSupport() {
        return 'vibrate' in navigator || 'navigator' in window && 'vibrate' in navigator;
    }
    
    setupHapticFeedback() {
        if (!this.hapticSupported || !this.isMobile) return;
        
        // Add haptic feedback to buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('button, .btn, .dropdown-option, .step')) {
                this.triggerHapticFeedback('light');
            }
        });
        
        // Add haptic feedback to form interactions
        document.addEventListener('focus', (e) => {
            if (e.target.matches('input, select, textarea')) {
                this.triggerHapticFeedback('light');
            }
        }, true);
        
        // Add haptic feedback to validation
        document.addEventListener('validation', (e) => {
            if (e.detail.isValid) {
                this.triggerHapticFeedback('light');
            } else {
                this.triggerHapticFeedback('medium');
            }
        });
    }
    
    triggerHapticFeedback(type) {
        if (!this.hapticSupported) return;
        
        const patterns = {
            light: [10],
            medium: [20],
            heavy: [50],
            success: [10, 10, 10],
            error: [100, 50, 100]
        };
        
        navigator.vibrate(patterns[type] || patterns.light);
    }
    
    setupSmoothTransitions() {
        // Add smooth transitions to form steps
        this.observeStepChanges();
        
        // Add smooth transitions to dropdowns
        this.observeDropdownChanges();
        
        // Add smooth transitions to progress bar
        this.observeProgressChanges();
    }
    
    observeStepChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const element = mutation.target;
                    if (element.classList.contains('form-step') && element.classList.contains('active')) {
                        this.animateStepTransition(element);
                    }
                }
            });
        });
        
        document.querySelectorAll('.form-step').forEach(step => {
            observer.observe(step, { attributes: true });
        });
    }
    
    animateStepTransition(element) {
        // Add entrance animation
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px) scale(0.95)';
        
        requestAnimationFrame(() => {
            element.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0) scale(1)';
        });
    }
    
    observeDropdownChanges() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.searchable-dropdown-input')) {
                this.animateDropdownOpen(e.target.closest('.searchable-dropdown-container'));
            }
        });
    }
    
    animateDropdownOpen(container) {
        const menu = container.querySelector('.searchable-dropdown-menu');
        if (!menu) return;
        
        menu.style.opacity = '0';
        menu.style.transform = 'translateY(-10px) scale(0.95)';
        
        requestAnimationFrame(() => {
            menu.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            menu.style.opacity = '1';
            menu.style.transform = 'translateY(0) scale(1)';
        });
    }
    
    observeProgressChanges() {
        const progressFill = document.getElementById('progressFill');
        if (!progressFill) return;
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    this.animateProgressChange(progressFill);
                }
            });
        });
        
        observer.observe(progressFill, { attributes: true });
    }
    
    animateProgressChange(progressFill) {
        const currentWidth = progressFill.style.width;
        progressFill.style.width = '0%';
        
        requestAnimationFrame(() => {
            progressFill.style.transition = 'width 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            progressFill.style.width = currentWidth;
        });
    }
    
    setupLoadingAnimations() {
        // Add loading spinners to form submission
        document.addEventListener('formSubmission', (e) => {
            this.showLoadingAnimation(e.detail.button);
        });
        
        // Add loading spinners to validation
        document.addEventListener('validationStart', (e) => {
            this.showFieldLoading(e.detail.field);
        });
        
        document.addEventListener('validationEnd', (e) => {
            this.hideFieldLoading(e.detail.field);
        });
    }
    
    showLoadingAnimation(button) {
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        button.disabled = true;
        
        // Store original content for restoration
        button.dataset.originalContent = originalContent;
    }
    
    hideLoadingAnimation(button) {
        if (button.dataset.originalContent) {
            button.innerHTML = button.dataset.originalContent;
            button.disabled = false;
        }
    }
    
    showFieldLoading(field) {
        const container = field.closest('.input-group, .form-group');
        if (!container) return;
        
        const spinner = document.createElement('div');
        spinner.className = 'field-loading-spinner';
        spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        spinner.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 10;';
        
        container.style.position = 'relative';
        container.appendChild(spinner);
    }
    
    hideFieldLoading(field) {
        const container = field.closest('.input-group, .form-group');
        if (!container) return;
        
        const spinner = container.querySelector('.field-loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
    
    setupSuccessAnimations() {
        // Add success animation to form completion
        document.addEventListener('formSuccess', (e) => {
            this.showSuccessAnimation();
        });
        
        // Add success animation to field validation
        document.addEventListener('fieldValid', (e) => {
            this.animateFieldSuccess(e.detail.field);
        });
    }
    
    showSuccessAnimation() {
        const modal = document.querySelector('.modal-content');
        if (!modal) return;
        
        // Create success overlay
        const overlay = document.createElement('div');
        overlay.className = 'success-overlay';
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(40, 167, 69, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            border-radius: 12px;
        `;
        
        overlay.innerHTML = `
            <div class="success-content text-center text-white">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle" style="font-size: 4rem; animation: bounceIn 0.6s ease;"></i>
                </div>
                <h3 class="mb-2">Success!</h3>
                <p class="mb-0">Thank you for joining our waitlist!</p>
            </div>
        `;
        
        modal.style.position = 'relative';
        modal.appendChild(overlay);
        
        // Add bounce animation
        this.addBounceAnimation();
        
        // Remove overlay after animation
        setTimeout(() => {
            overlay.remove();
        }, 3000);
    }
    
    animateFieldSuccess(field) {
        const container = field.closest('.input-group, .form-group');
        if (!container) return;
        
        // Add success pulse animation
        container.style.animation = 'successPulse 0.6s ease';
        
        setTimeout(() => {
            container.style.animation = '';
        }, 600);
    }
    
    addBounceAnimation() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounceIn {
                0% { transform: scale(0.3); opacity: 0; }
                50% { transform: scale(1.05); }
                70% { transform: scale(0.9); }
                100% { transform: scale(1); opacity: 1; }
            }
            
            @keyframes successPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.3); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }
    
    optimizeForMobile() {
        if (!this.isMobile) return;
        
        // Increase touch targets
        this.increaseTouchTargets();
        
        // Optimize dropdowns for mobile
        this.optimizeDropdownsForMobile();
        
        // Add swipe gestures
        this.addSwipeGestures();
    }
    
    increaseTouchTargets() {
        const touchElements = document.querySelectorAll('button, .btn, .dropdown-option, .step, input[type="checkbox"], input[type="radio"]');
        
        touchElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            if (rect.height < 44) {
                element.style.minHeight = '44px';
                element.style.padding = '12px 16px';
            }
        });
    }
    
    optimizeDropdownsForMobile() {
        const dropdowns = document.querySelectorAll('.searchable-dropdown-container');
        
        dropdowns.forEach(dropdown => {
            const menu = dropdown.querySelector('.searchable-dropdown-menu');
            if (menu) {
                // Make dropdown full-width on mobile
                menu.style.width = '100vw';
                menu.style.left = '50%';
                menu.style.transform = 'translateX(-50%)';
                menu.style.maxWidth = 'none';
            }
        });
    }
    
    addSwipeGestures() {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;
            
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // Horizontal swipe
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    // Swipe left - next step
                    this.triggerSwipeAction('next');
                } else {
                    // Swipe right - previous step
                    this.triggerSwipeAction('prev');
                }
            }
        });
    }
    
    triggerSwipeAction(action) {
        if (action === 'next' && window.formValidatorInstance) {
            window.formValidatorInstance.nextStep();
            this.triggerHapticFeedback('light');
        } else if (action === 'prev' && window.formValidatorInstance) {
            window.formValidatorInstance.prevStep();
            this.triggerHapticFeedback('light');
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new MicroInteractions();
});

// Export for manual use
window.MicroInteractions = MicroInteractions;
