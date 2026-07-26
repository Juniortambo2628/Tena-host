/**
 * Enhanced Validation Service
 * Provides real-time email validation, phone formatting, and website validation
 */

class EnhancedValidator {
    constructor() {
        this.emailValidationCache = new Map();
        this.websiteValidationCache = new Map();
        this.init();
    }
    
    init() {
        this.setupEmailValidation();
        this.setupPhoneValidation();
        this.setupWebsiteValidation();
    }
    
    setupEmailValidation() {
        const emailField = document.querySelector('input[name="email"]');
        if (!emailField) return;
        
        emailField.addEventListener('blur', (e) => {
            this.validateEmailDomain(e.target);
        });
    }
    
    async validateEmailDomain(input) {
        const email = input.value.trim();
        if (!email || !this.isValidEmailFormat(email)) return;
        
        const domain = email.split('@')[1];
        if (!domain) return;
        
        // Check cache first
        if (this.emailValidationCache.has(domain)) {
            this.showEmailValidationResult(input, this.emailValidationCache.get(domain));
            return;
        }
        
        // Show loading state
        this.showEmailValidationLoading(input);
        
        try {
            const isValid = await this.checkEmailDomain(domain);
            this.emailValidationCache.set(domain, isValid);
            this.showEmailValidationResult(input, isValid);
        } catch (error) {
            console.log('Email validation failed:', error);
            this.hideEmailValidation(input);
        }
    }
    
    async checkEmailDomain(domain) {
        // Use a free email validation service
        const response = await fetch(`https://api.email-validator.net/validate?email=test@${domain}&api_key=YOUR_API_KEY`);
        const data = await response.json();
        return data.status === 'valid';
    }
    
    showEmailValidationLoading(input) {
        this.hideEmailValidation(input);
        
        const loadingIcon = document.createElement('i');
        loadingIcon.className = 'fas fa-spinner fa-spin email-validation-icon loading';
        loadingIcon.style.color = '#6c757d';
        input.parentNode.appendChild(loadingIcon);
    }
    
    showEmailValidationResult(input, isValid) {
        this.hideEmailValidation(input);
        
        const icon = document.createElement('i');
        icon.className = `fas ${isValid ? 'fa-check-circle' : 'fa-exclamation-triangle'} email-validation-icon ${isValid ? 'valid' : 'invalid'}`;
        icon.style.color = isValid ? '#28a745' : '#dc3545';
        input.parentNode.appendChild(icon);
        
        if (!isValid) {
            this.showEmailValidationMessage(input, 'This email domain may not exist or be invalid');
        }
    }
    
    showEmailValidationMessage(input, message) {
        const existingMessage = input.parentNode.querySelector('.email-validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'email-validation-message text-warning small mt-1';
        messageDiv.innerHTML = `<i class="fas fa-info-circle me-1"></i>${message}`;
        input.parentNode.appendChild(messageDiv);
    }
    
    hideEmailValidation(input) {
        const existingIcon = input.parentNode.querySelector('.email-validation-icon');
        if (existingIcon) {
            existingIcon.remove();
        }
        
        const existingMessage = input.parentNode.querySelector('.email-validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
    }
    
    setupPhoneValidation() {
        const phoneField = document.querySelector('input[name="phone_number"]');
        if (!phoneField) return;
        
        phoneField.addEventListener('input', (e) => {
            this.formatPhoneNumber(e.target);
        });
        
        phoneField.addEventListener('blur', (e) => {
            this.validatePhoneNumber(e.target);
        });
    }
    
    formatPhoneNumber(input) {
        const countryCodeSelect = document.getElementById('countryCodeSelect');
        const countryCode = countryCodeSelect ? countryCodeSelect.value : '+1';
        
        let value = input.value.replace(/\D/g, '');
        
        // Format based on country code
        const formatters = {
            '+1': this.formatUSPhone,
            '+44': this.formatUKPhone,
            '+49': this.formatGermanPhone,
            '+33': this.formatFrenchPhone,
            '+39': this.formatItalianPhone,
            '+34': this.formatSpanishPhone,
            '+61': this.formatAustralianPhone,
            '+81': this.formatJapanesePhone,
            '+86': this.formatChinesePhone,
            '+91': this.formatIndianPhone
        };
        
        const formatter = formatters[countryCode] || this.formatGenericPhone;
        input.value = formatter(value);
    }
    
    formatUSPhone(value) {
        if (value.length >= 6) {
            return `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
        } else if (value.length >= 3) {
            return `(${value.slice(0, 3)}) ${value.slice(3)}`;
        }
        return value;
    }
    
    formatUKPhone(value) {
        if (value.length >= 7) {
            return `${value.slice(0, 4)} ${value.slice(4, 7)} ${value.slice(7, 11)}`;
        } else if (value.length >= 4) {
            return `${value.slice(0, 4)} ${value.slice(4)}`;
        }
        return value;
    }
    
    formatGermanPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 3)} ${value.slice(3, 10)}`;
        }
        return value;
    }
    
    formatFrenchPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 2)} ${value.slice(2, 4)} ${value.slice(4, 6)} ${value.slice(6, 8)} ${value.slice(8, 10)}`;
        }
        return value;
    }
    
    formatItalianPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 3)} ${value.slice(3, 6)} ${value.slice(6, 10)}`;
        }
        return value;
    }
    
    formatSpanishPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 3)} ${value.slice(3, 6)} ${value.slice(6, 9)}`;
        }
        return value;
    }
    
    formatAustralianPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 4)} ${value.slice(4, 7)} ${value.slice(7, 10)}`;
        }
        return value;
    }
    
    formatJapanesePhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 3)}-${value.slice(3, 7)}-${value.slice(7, 11)}`;
        }
        return value;
    }
    
    formatChinesePhone(value) {
        if (value.length >= 7) {
            return `${value.slice(0, 3)} ${value.slice(3, 7)} ${value.slice(7, 11)}`;
        }
        return value;
    }
    
    formatIndianPhone(value) {
        if (value.length >= 6) {
            return `${value.slice(0, 5)} ${value.slice(5, 10)}`;
        }
        return value;
    }
    
    formatGenericPhone(value) {
        // Generic formatting for other countries
        if (value.length >= 6) {
            const groups = [];
            for (let i = 0; i < value.length; i += 3) {
                groups.push(value.slice(i, i + 3));
            }
            return groups.join(' ');
        }
        return value;
    }
    
    validatePhoneNumber(input) {
        const value = input.value.replace(/\D/g, '');
        const countryCodeSelect = document.getElementById('countryCodeSelect');
        const countryCode = countryCodeSelect ? countryCodeSelect.value : '+1';
        
        const minLengths = {
            '+1': 10,
            '+44': 10,
            '+49': 10,
            '+33': 10,
            '+39': 10,
            '+34': 9,
            '+61': 9,
            '+81': 10,
            '+86': 11,
            '+91': 10
        };
        
        const minLength = minLengths[countryCode] || 7;
        
        if (value.length < minLength) {
            this.showPhoneValidationError(input, `Phone number must be at least ${minLength} digits`);
        } else {
            this.hidePhoneValidationError(input);
        }
    }
    
    showPhoneValidationError(input, message) {
        this.hidePhoneValidationError(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'phone-validation-error text-danger small mt-1';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${message}`;
        input.parentNode.appendChild(errorDiv);
    }
    
    hidePhoneValidationError(input) {
        const existingError = input.parentNode.querySelector('.phone-validation-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    setupWebsiteValidation() {
        const websiteField = document.querySelector('input[name="business_website"]');
        if (!websiteField) return;
        
        websiteField.addEventListener('blur', (e) => {
            this.validateWebsite(e.target);
        });
    }
    
    async validateWebsite(input) {
        const url = input.value.trim();
        if (!url || !this.isValidUrlFormat(url)) return;
        
        const domain = this.extractDomain(url);
        if (!domain) return;
        
        // Check cache first
        if (this.websiteValidationCache.has(domain)) {
            this.showWebsiteValidationResult(input, this.websiteValidationCache.get(domain));
            return;
        }
        
        // Show loading state
        this.showWebsiteValidationLoading(input);
        
        try {
            const isValid = await this.checkWebsiteExists(domain);
            this.websiteValidationCache.set(domain, isValid);
            this.showWebsiteValidationResult(input, isValid);
        } catch (error) {
            console.log('Website validation failed:', error);
            this.hideWebsiteValidation(input);
        }
    }
    
    async checkWebsiteExists(domain) {
        try {
            const response = await fetch(`https://${domain}`, { 
                method: 'HEAD',
                mode: 'no-cors',
                cache: 'no-cache'
            });
            return true;
        } catch (error) {
            return false;
        }
    }
    
    showWebsiteValidationLoading(input) {
        this.hideWebsiteValidation(input);
        
        const loadingIcon = document.createElement('i');
        loadingIcon.className = 'fas fa-spinner fa-spin website-validation-icon loading';
        loadingIcon.style.color = '#6c757d';
        input.parentNode.appendChild(loadingIcon);
    }
    
    showWebsiteValidationResult(input, isValid) {
        this.hideWebsiteValidation(input);
        
        const icon = document.createElement('i');
        icon.className = `fas ${isValid ? 'fa-check-circle' : 'fa-exclamation-triangle'} website-validation-icon ${isValid ? 'valid' : 'invalid'}`;
        icon.style.color = isValid ? '#28a745' : '#dc3545';
        input.parentNode.appendChild(icon);
        
        if (!isValid) {
            this.showWebsiteValidationMessage(input, 'This website may not be accessible');
        }
    }
    
    showWebsiteValidationMessage(input, message) {
        const existingMessage = input.parentNode.querySelector('.website-validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'website-validation-message text-warning small mt-1';
        messageDiv.innerHTML = `<i class="fas fa-info-circle me-1"></i>${message}`;
        input.parentNode.appendChild(messageDiv);
    }
    
    hideWebsiteValidation(input) {
        const existingIcon = input.parentNode.querySelector('.website-validation-icon');
        if (existingIcon) {
            existingIcon.remove();
        }
        
        const existingMessage = input.parentNode.querySelector('.website-validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
    }
    
    extractDomain(url) {
        try {
            const urlObj = new URL(url.startsWith('http') ? url : `https://${url}`);
            return urlObj.hostname;
        } catch {
            return null;
        }
    }
    
    isValidEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    isValidUrlFormat(url) {
        try {
            new URL(url.startsWith('http') ? url : `https://${url}`);
            return true;
        } catch {
            return false;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new EnhancedValidator();
});

// Export for manual use
window.EnhancedValidator = EnhancedValidator;
