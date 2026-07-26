/**
 * Enhanced Form Validation and Step Navigation
 * Handles multi-step form with live validation and FontAwesome icons
 */

class FormValidator {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 3;
        this.form = document.getElementById('waitlistForm');
        this.nextBtn = document.getElementById('nextStep');
        this.prevBtn = document.getElementById('prevStep');
        this.submitBtn = document.getElementById('submitForm');
        
        // Debounce settings
        this.debounceDelay = 300;
        this.validationTimeouts = new Map();
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateStepDisplay();
        this.loadDarkModePreference();
    }
    
    bindEvents() {
        // Step navigation
        this.nextBtn.addEventListener('click', () => this.nextStep());
        this.prevBtn.addEventListener('click', () => this.prevStep());
        
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Live validation with debouncing
        this.form.addEventListener('input', (e) => this.debouncedValidateField(e.target));
        this.form.addEventListener('blur', (e) => this.validateField(e.target));
        
        // Checkbox validation
        this.form.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox') {
                this.validateField(e.target);
            }
        });
        
        // Keyboard navigation
        this.form.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                this.toggleDarkMode();
            });
        }
    }
    
    nextStep() {
        if (this.validateCurrentStep()) {
            this.currentStep++;
            this.updateStepDisplay();
            // Trigger step change event
            document.dispatchEvent(new CustomEvent('stepChanged', { detail: { step: this.currentStep } }));
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepDisplay();
            // Trigger step change event
            document.dispatchEvent(new CustomEvent('stepChanged', { detail: { step: this.currentStep } }));
        }
    }
    
    updateStepDisplay() {
        // Update progress steps
        document.querySelectorAll('.step').forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 < this.currentStep) {
                step.classList.add('completed');
            } else if (index + 1 === this.currentStep) {
                step.classList.add('active');
            }
        });
        
        // Update enhanced progress indicator
        this.updateProgressIndicator();
        
        // Lazy load form steps - only load current step
        this.loadCurrentStep();
        
        // Update footer buttons based on current step
        this.updateFooterButtons();
    }
    
    updateFooterButtons() {
        // Get button elements
        const prevBtn = this.prevBtn;
        const nextBtn = this.nextBtn;
        const submitBtn = this.submitBtn;
        
        // Step 1: Show only Next button
        if (this.currentStep === 1) {
            prevBtn.style.setProperty('display', 'none', 'important');
            nextBtn.style.setProperty('display', 'inline-flex', 'important');
            submitBtn.style.setProperty('display', 'none', 'important');
            nextBtn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Next';
        }
        // Step 2 (middle steps): Show both Previous and Next
        else if (this.currentStep > 1 && this.currentStep < this.totalSteps) {
            prevBtn.style.setProperty('display', 'inline-flex', 'important');
            nextBtn.style.setProperty('display', 'inline-flex', 'important');
            submitBtn.style.setProperty('display', 'none', 'important');
            prevBtn.innerHTML = '<i class="fas fa-arrow-left me-2"></i>Previous';
            nextBtn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Next';
        }
        // Last step: Show Previous and Submit (Join Waitlist)
        else if (this.currentStep === this.totalSteps) {
            prevBtn.style.setProperty('display', 'inline-flex', 'important');
            nextBtn.style.setProperty('display', 'none', 'important');
            submitBtn.style.setProperty('display', 'inline-flex', 'important');
            prevBtn.innerHTML = '<i class="fas fa-arrow-left me-2"></i>Previous';
            submitBtn.innerHTML = '<i class="fas fa-rocket me-2"></i>Join Waitlist';
        }
        
        // Add smooth transition animation
        [prevBtn, nextBtn, submitBtn].forEach(btn => {
            btn.style.transition = 'all 0.3s ease';
        });
    }
    
    updateProgressIndicator() {
        // Update step counter
        const stepNumber = document.getElementById('currentStepNumber');
        if (stepNumber) {
            stepNumber.textContent = this.currentStep;
        }
        
        // Update percentage
        const percentage = Math.round((this.currentStep / this.totalSteps) * 100);
        const percentageElement = document.getElementById('progressPercentage');
        if (percentageElement) {
            percentageElement.textContent = `${percentage}%`;
        }
        
        // Update progress bar
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            progressFill.className = `progress-fill step-${this.currentStep}`;
        }
        
        // Update estimated time
        const estimatedTime = document.getElementById('estimatedTime');
        if (estimatedTime) {
            const remainingSteps = this.totalSteps - this.currentStep + 1;
            const timePerStep = 1; // minutes
            const totalMinutes = remainingSteps * timePerStep;
            
            if (totalMinutes === 1) {
                estimatedTime.textContent = '~1 min remaining';
            } else {
                estimatedTime.textContent = `~${totalMinutes} min remaining`;
            }
        }
    }
    
    loadCurrentStep() {
        // Hide all steps first
        document.querySelectorAll('.form-step').forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        
        // Load current step
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        if (currentStepElement) {
            currentStepElement.style.display = 'block';
            currentStepElement.classList.add('active');
            
            // Add smooth transition
            currentStepElement.style.opacity = '0';
            currentStepElement.style.transform = 'translateY(20px)';
            
            requestAnimationFrame(() => {
                currentStepElement.style.transition = 'all 0.3s ease';
                currentStepElement.style.opacity = '1';
                currentStepElement.style.transform = 'translateY(0)';
            });
        }
    }
    
    validateCurrentStep() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        const requiredFields = currentStepElement.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    handleKeyboardNavigation(e) {
        // Tab navigation between steps
        if (e.key === 'Tab') {
            const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
            const focusableElements = currentStepElement.querySelectorAll(
                'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
        
        // Arrow key navigation between steps
        if (e.key === 'ArrowLeft' && this.currentStep > 1) {
            e.preventDefault();
            this.prevStep();
        } else if (e.key === 'ArrowRight' && this.currentStep < this.totalSteps) {
            e.preventDefault();
            this.nextStep();
        }
        
        // Enter key to submit on last step
        if (e.key === 'Enter' && this.currentStep === this.totalSteps && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            this.submitForm();
        }
    }
    
    toggleDarkMode() {
        const modal = document.querySelector('.modal-content');
        const darkModeIcon = document.getElementById('darkModeIcon');
        
        if (modal) {
            const isDark = modal.getAttribute('data-theme') === 'dark';
            
            if (isDark) {
                modal.removeAttribute('data-theme');
                darkModeIcon.className = 'fas fa-moon';
            } else {
                modal.setAttribute('data-theme', 'dark');
                darkModeIcon.className = 'fas fa-sun';
            }
            
            // Save preference
            localStorage.setItem('tena_form_dark_mode', !isDark);
        }
    }
    
    loadDarkModePreference() {
        const isDarkMode = localStorage.getItem('tena_form_dark_mode') === 'true';
        const modal = document.querySelector('.modal-content');
        const darkModeIcon = document.getElementById('darkModeIcon');
        
        if (isDarkMode && modal) {
            modal.setAttribute('data-theme', 'dark');
            darkModeIcon.className = 'fas fa-sun';
        }
    }
    
    debouncedValidateField(field) {
        // Clear existing timeout for this field
        if (this.validationTimeouts.has(field)) {
            clearTimeout(this.validationTimeouts.get(field));
        }
        
        // Set new timeout
        const timeout = setTimeout(() => {
            this.validateField(field);
            this.validationTimeouts.delete(field);
        }, this.debounceDelay);
        
        this.validationTimeouts.set(field, timeout);
    }
    
    validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';
        
        // Remove existing validation classes and icons
        field.classList.remove('is-valid', 'is-invalid');
        const existingIcon = field.parentNode.querySelector('.validation-icon');
        if (existingIcon) {
            existingIcon.remove();
        }
        
        // Skip validation icons for select elements
        const isSelectElement = field.tagName === 'SELECT';
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = this.getRequiredMessage(fieldName);
        }
        
        // Type-specific validation
        if (value && isValid) {
            switch (fieldType) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address.';
                    }
                    break;
                case 'tel':
                    if (!this.isValidPhone(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid phone number.';
                    }
                    break;
                case 'url':
                    if (!this.isValidUrl(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid URL.';
                    }
                    break;
                case 'number':
                    if (field.hasAttribute('min') && parseFloat(value) < parseFloat(field.getAttribute('min'))) {
                        isValid = false;
                        errorMessage = `Value must be at least ${field.getAttribute('min')}.`;
                    }
                    break;
            }
        }
        
        // Custom validation for specific fields
        if (value && isValid) {
            switch (fieldName) {
                case 'business_website':
                    if (value) {
                        // Auto-add https:// if missing
                        if (!value.startsWith('http')) {
                            if (value.startsWith('www.') || (!value.includes('://') && value.includes('.'))) {
                                field.value = 'https://' + value;
                                isValid = true;
                            } else {
                                isValid = false;
                                errorMessage = 'Please enter a valid website URL';
                            }
                        } else {
                            // Validate URL format
                            const urlPattern = /^https?:\/\/(www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(\/.*)?$/;
                            if (!urlPattern.test(value)) {
                                isValid = false;
                                errorMessage = 'Please enter a valid website URL';
                            }
                        }
                    }
                    break;
                case 'expected_launch_date':
                    if (value && new Date(value) < new Date()) {
                        isValid = false;
                        errorMessage = 'Launch date must be in the future.';
                    }
                    break;
            }
        }
        
        // Apply validation styling
        if (isValid) {
            field.classList.add('is-valid');
            // Only add validation icons for non-select elements
            if (!isSelectElement) {
                this.addValidationIcon(field, 'valid', 'fas fa-check-circle');
            }
            // Trigger validation success event
            field.dispatchEvent(new CustomEvent('fieldValid', { detail: { field, isValid } }));
        } else {
            field.classList.add('is-invalid');
            // Only add validation icons for non-select elements
            if (!isSelectElement) {
                this.addValidationIcon(field, 'invalid', 'fas fa-exclamation-circle');
            }
            // Trigger validation error event
            field.dispatchEvent(new CustomEvent('fieldInvalid', { detail: { field, isValid } }));
        }
        
        // Ensure no validation icons are added to select elements
        if (isSelectElement) {
            const existingIcon = field.parentNode.querySelector('.validation-icon');
            if (existingIcon) {
                existingIcon.remove();
            }
        }
        
        // Show/hide error message
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = errorMessage;
            feedback.style.display = isValid ? 'none' : 'block';
        }
        
        return isValid;
    }
    
    addValidationIcon(field, type, iconClass) {
        const icon = document.createElement('i');
        icon.className = `validation-icon ${type} ${iconClass}`;
        field.parentNode.appendChild(icon);
    }
    
    getRequiredMessage(fieldName) {
        const messages = {
            'first_name': 'Please provide your first name.',
            'last_name': 'Please provide your last name.',
            'email': 'Please provide a valid email address.',
            'phone_number': 'Please provide a valid phone number.',
            'country_code': 'Please select a country code.',
            'country': 'Please select your country.',
            'business_name': 'Please provide your business name.',
            'years_in_business': 'Please select years in business.',
            'property_type': 'Please select a property type.',
            'property_count': 'Please enter number of properties.',
            'preferred_contact_method': 'Please select preferred contact method.',
            'gdpr_consent': 'You must agree to the terms and privacy policy.'
        };
        return messages[fieldName] || 'This field is required.';
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        // Validate all steps
        let allValid = true;
        for (let step = 1; step <= this.totalSteps; step++) {
            const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
            const requiredFields = stepElement.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!this.validateField(field)) {
                    allValid = false;
                }
            });
        }
        
        if (allValid) {
            this.submitForm();
        } else {
            // Go to first invalid step
            for (let step = 1; step <= this.totalSteps; step++) {
                const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
                const invalidFields = stepElement.querySelectorAll('.is-invalid');
                if (invalidFields.length > 0) {
                    this.currentStep = step;
                    this.updateStepDisplay();
                    break;
                }
            }
        }
    }
    
    async submitForm() {
        const submitBtn = this.submitBtn;
        const originalText = submitBtn.innerHTML;
        
        // Trigger form submission start event
        document.dispatchEvent(new CustomEvent('formSubmission', { detail: { button: submitBtn } }));
        
        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        
        try {
            // Collect form data
            const formData = new FormData(this.form);
            
            // Removed JSON fields as per client requirements
            
            // Submit form
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Trigger form success event
                document.dispatchEvent(new CustomEvent('formSuccess', { detail: { response } }));
                this.showSuccessMessage();
            } else {
                throw new Error('Submission failed');
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            // Trigger form error event
            document.dispatchEvent(new CustomEvent('formError', { detail: { error } }));
            this.showErrorMessage('Failed to submit form. Please try again.');
        } finally {
            // Reset button state
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
    
    showSuccessMessage() {
        // Close modal and show success message
        const modal = bootstrap.Modal.getInstance(document.getElementById('waitlistModal'));
        modal.hide();
        
        // Show success notification
        this.showNotification('success', 'Success!', 'Thank you for joining our waitlist. We\'ll be in touch soon!');
    }
    
    showErrorMessage(message) {
        this.showNotification('error', 'Error', message);
    }
    
    showNotification(type, title, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <strong>${title}</strong><br>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize form validator when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new FormValidator();
});
