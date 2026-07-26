/**
 * Smart Defaults & Auto-completion Service
 * Handles IP-based country detection, localStorage, and auto-formatting
 */

class SmartDefaults {
    constructor() {
        this.storageKey = 'tena_waitlist_form_data';
        this.countryDetectionApi = 'https://ipapi.co/json/';
        this.init();
    }
    
    init() {
        this.loadSavedData();
        this.detectCountry();
        this.setupAutoFormatting();
        this.setupAutoSave();
    }
    
    async detectCountry() {
        try {
            const response = await fetch(this.countryDetectionApi);
            const data = await response.json();
            
            if (data.country_code) {
                this.setCountryDefaults(data);
            }
        } catch (error) {
            console.log('Country detection failed, using fallback');
            this.setFallbackDefaults();
        }
    }
    
    setCountryDefaults(data) {
        // Set country code
        const countryCodeSelect = document.getElementById('countryCodeSelect');
        if (countryCodeSelect) {
            const option = countryCodeSelect.querySelector(`option[value="+${data.country_calling_code}"]`);
            if (option) {
                countryCodeSelect.value = option.value;
                // Trigger change event for searchable dropdown
                countryCodeSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Set country
        const countrySelect = document.querySelector('select[name="country"]');
        if (countrySelect) {
            const option = countrySelect.querySelector(`option[value="${data.country_name}"]`);
            if (option) {
                countrySelect.value = option.value;
                // Trigger change event for searchable dropdown
                countrySelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Set timezone
        const timezoneField = document.querySelector('input[name="timezone"]');
        if (timezoneField && data.timezone) {
            timezoneField.value = data.timezone;
        }
    }
    
    setFallbackDefaults() {
        // Set common defaults for US users
        const countryCodeSelect = document.getElementById('countryCodeSelect');
        if (countryCodeSelect) {
            countryCodeSelect.value = '+1';
            countryCodeSelect.dispatchEvent(new Event('change'));
        }
        
        const countrySelect = document.querySelector('select[name="country"]');
        if (countrySelect) {
            countrySelect.value = 'United States';
            countrySelect.dispatchEvent(new Event('change'));
        }
    }
    
    setupAutoFormatting() {
        // Phone number formatting
        const phoneInput = document.querySelector('input[name="phone_number"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                this.formatPhoneNumber(e.target);
            });
        }
        
        // Business website formatting
        const websiteInput = document.querySelector('input[name="business_website"]');
        if (websiteInput) {
            websiteInput.addEventListener('blur', (e) => {
                this.formatWebsite(e.target);
            });
        }
    }
    
    formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        // Get country code to determine formatting
        const countryCodeSelect = document.getElementById('countryCodeSelect');
        const countryCode = countryCodeSelect ? countryCodeSelect.value : '+1';
        
        // Format based on country code
        if (countryCode === '+1') {
            // US/Canada format: (XXX) XXX-XXXX
            if (value.length >= 6) {
                value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
            }
        } else if (countryCode === '+44') {
            // UK format: XXXX XXX XXXX
            if (value.length >= 7) {
                value = `${value.slice(0, 4)} ${value.slice(4, 7)} ${value.slice(7, 11)}`;
            } else if (value.length >= 4) {
                value = `${value.slice(0, 4)} ${value.slice(4)}`;
            }
        } else if (countryCode === '+49') {
            // Germany format: XXX XXXXXXX
            if (value.length >= 6) {
                value = `${value.slice(0, 3)} ${value.slice(3, 10)}`;
            }
        }
        
        input.value = value;
    }
    
    formatWebsite(input) {
        let value = input.value.trim();
        
        if (value && !value.startsWith('http')) {
            if (value.startsWith('www.') || value.includes('.')) {
                input.value = 'https://' + value;
            }
        }
    }
    
    setupAutoSave() {
        const form = document.getElementById('waitlistForm');
        if (!form) return;
        
        // Save data every 30 seconds
        setInterval(() => {
            this.saveFormData();
        }, 30000);
        
        // Save on form changes
        form.addEventListener('input', () => {
            this.debounceSave();
        });
        
        form.addEventListener('change', () => {
            this.debounceSave();
        });
    }
    
    debounceSave() {
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveFormData();
        }, 1000);
    }
    
    saveFormData() {
        const form = document.getElementById('waitlistForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Add current step
        data.currentStep = this.getCurrentStep();
        
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    }
    
    loadSavedData() {
        const savedData = localStorage.getItem(this.storageKey);
        if (!savedData) return;
        
        try {
            const data = JSON.parse(savedData);
            this.populateForm(data);
        } catch (error) {
            console.log('Failed to load saved form data');
        }
    }
    
    populateForm(data) {
        // Populate form fields
        Object.keys(data).forEach(key => {
            if (key === 'currentStep') return;
            
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = data[key] === 'on' || data[key] === field.value;
                } else {
                    field.value = data[key];
                }
            }
        });
        
        // Restore current step
        if (data.currentStep && window.FormValidator) {
            const formValidator = window.formValidatorInstance;
            if (formValidator) {
                formValidator.currentStep = data.currentStep;
                formValidator.updateStepDisplay();
            }
        }
    }
    
    getCurrentStep() {
        const formValidator = window.formValidatorInstance;
        return formValidator ? formValidator.currentStep : 1;
    }
    
    clearSavedData() {
        localStorage.removeItem(this.storageKey);
    }
    
    // Method to show resume prompt - now handled by header button
    showResumePrompt() {
        // This functionality is now handled by the header resume button
        // Keeping method for backward compatibility but removing redundant button
        return;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.smartDefaults = new SmartDefaults();
    
    // Show resume prompt if there's saved data
    setTimeout(() => {
        window.smartDefaults.showResumePrompt();
    }, 1000);
});

// Export for manual use
window.SmartDefaults = SmartDefaults;
