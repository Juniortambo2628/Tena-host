/**
 * Form State Management System
 * Handles auto-save, resume functionality, and form analytics
 */

class FormStateManager {
    constructor() {
        this.storageKey = 'tena_waitlist_form_state';
        this.analyticsKey = 'tena_form_analytics';
        this.autoSaveInterval = 30000; // 30 seconds
        this.analyticsInterval = 5000; // 5 seconds
        this.form = document.getElementById('waitlistForm');
        this.startTime = Date.now();
        this.fieldInteractions = new Map();
        this.stepTimes = new Map();
        this.currentStep = 1;
        
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        this.setupAutoSave();
        this.setupAnalytics();
        this.setupResumeFunctionality();
        this.trackFormStart();
    }
    
    setupAutoSave() {
        // Auto-save every 30 seconds
        setInterval(() => {
            this.saveFormState();
        }, this.autoSaveInterval);
        
        // Save on form changes
        this.form.addEventListener('input', () => {
            this.debouncedSave();
        });
        
        this.form.addEventListener('change', () => {
            this.debouncedSave();
        });
        
        // Save on step changes
        document.addEventListener('stepChanged', (e) => {
            this.currentStep = e.detail.step;
            this.trackStepTime();
            this.saveFormState();
        });
    }
    
    debouncedSave() {
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveFormState();
        }, 2000); // Save 2 seconds after user stops typing
    }
    
    saveFormState() {
        const formData = new FormData(this.form);
        const state = {
            data: {},
            currentStep: this.currentStep,
            timestamp: Date.now(),
            sessionId: this.getSessionId()
        };
        
        // Extract form data
        for (let [key, value] of formData.entries()) {
            state.data[key] = value;
        }
        
        // Add analytics data
        state.analytics = {
            fieldInteractions: Object.fromEntries(this.fieldInteractions),
            stepTimes: Object.fromEntries(this.stepTimes),
            totalTime: Date.now() - this.startTime
        };
        
        localStorage.setItem(this.storageKey, JSON.stringify(state));
        this.showAutoSaveIndicator();
    }
    
    setupAnalytics() {
        // Track field interactions
        this.form.addEventListener('focus', (e) => {
            this.trackFieldInteraction(e.target, 'focus');
        });
        
        this.form.addEventListener('blur', (e) => {
            this.trackFieldInteraction(e.target, 'blur');
        });
        
        // Track step changes
        document.addEventListener('stepChanged', (e) => {
            this.trackStepChange(e.detail.step);
        });
        
        // Track form submission
        this.form.addEventListener('submit', (e) => {
            this.trackFormSubmission();
        });
        
        // Track form abandonment
        window.addEventListener('beforeunload', () => {
            this.trackFormAbandonment();
        });
    }
    
    trackFieldInteraction(field, action) {
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        const interactions = this.fieldInteractions.get(fieldName) || {
            focus: 0,
            blur: 0,
            firstFocus: null,
            lastInteraction: null
        };
        
        interactions[action]++;
        interactions.lastInteraction = Date.now();
        
        if (action === 'focus' && !interactions.firstFocus) {
            interactions.firstFocus = Date.now();
        }
        
        this.fieldInteractions.set(fieldName, interactions);
    }
    
    trackStepChange(step) {
        const now = Date.now();
        
        // Record time spent on previous step
        if (this.stepTimes.has(this.currentStep)) {
            const stepData = this.stepTimes.get(this.currentStep);
            stepData.endTime = now;
            stepData.duration = now - stepData.startTime;
        }
        
        // Start tracking new step
        this.stepTimes.set(step, {
            startTime: now,
            endTime: null,
            duration: null
        });
        
        this.currentStep = step;
    }
    
    trackStepTime() {
        const now = Date.now();
        if (this.stepTimes.has(this.currentStep)) {
            const stepData = this.stepTimes.get(this.currentStep);
            if (!stepData.endTime) {
                stepData.duration = now - stepData.startTime;
            }
        }
    }
    
    trackFormStart() {
        this.analytics = {
            startTime: this.startTime,
            sessionId: this.getSessionId(),
            userAgent: navigator.userAgent,
            screenResolution: `${screen.width}x${screen.height}`,
            viewportSize: `${window.innerWidth}x${window.innerHeight}`
        };
    }
    
    trackFormSubmission() {
        this.analytics.endTime = Date.now();
        this.analytics.totalDuration = this.analytics.endTime - this.analytics.startTime;
        this.analytics.completionStatus = 'completed';
        
        this.saveAnalytics();
        this.clearFormState();
    }
    
    trackFormAbandonment() {
        this.analytics.endTime = Date.now();
        this.analytics.totalDuration = this.analytics.endTime - this.analytics.startTime;
        this.analytics.completionStatus = 'abandoned';
        this.analytics.abandonmentStep = this.currentStep;
        
        this.saveAnalytics();
    }
    
    saveAnalytics() {
        const analytics = {
            ...this.analytics,
            fieldInteractions: Object.fromEntries(this.fieldInteractions),
            stepTimes: Object.fromEntries(this.stepTimes),
            formData: this.getFormData()
        };
        
        // Save to localStorage for now (in production, send to analytics service)
        const existingAnalytics = JSON.parse(localStorage.getItem(this.analyticsKey) || '[]');
        existingAnalytics.push(analytics);
        localStorage.setItem(this.analyticsKey, JSON.stringify(existingAnalytics));
    }
    
    setupResumeFunctionality() {
        // Check for saved state on page load
        const savedState = this.getSavedState();
        if (savedState && this.shouldShowResumePrompt(savedState)) {
            this.showResumeButton(savedState);
        }
        
        // Listen for modal show events to check for saved state
        const modal = document.getElementById('waitlistModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', () => {
                const currentSavedState = this.getSavedState();
                if (currentSavedState && this.shouldShowResumePrompt(currentSavedState)) {
                    this.showResumeButton(currentSavedState);
                }
            });
        }
    }
    
    getSavedState() {
        const saved = localStorage.getItem(this.storageKey);
        if (!saved) return null;
        
        try {
            return JSON.parse(saved);
        } catch {
            return null;
        }
    }
    
    shouldShowResumePrompt(state) {
        // Show resume prompt if:
        // 1. State is less than 24 hours old
        // 2. Form is not empty
        // 3. User hasn't completed the form
        
        const isRecent = Date.now() - state.timestamp < 24 * 60 * 60 * 1000;
        const hasData = Object.keys(state.data).length > 0;
        const isIncomplete = state.currentStep < 3;
        
        return isRecent && hasData && isIncomplete;
    }
    
    showResumeButton(state) {
        const resumeButton = document.getElementById('resumeButton');
        if (resumeButton) {
            resumeButton.style.display = 'inline-block';
            resumeButton.onclick = () => {
                this.resumeForm(state);
                resumeButton.style.display = 'none';
            };
        }
    }
    
    showResumePrompt(state) {
        const resumeModal = document.createElement('div');
        resumeModal.className = 'modal fade resume-dialog';
        resumeModal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glassmorphism-modal">
                    <div class="modal-header glassmorphism-header">
                        <h5 class="font-standard-heading">
                            <i class="fas fa-undo me-4"></i>Resume Your Application
                        </h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body glassmorphism-body">
                        <div class="resume-content text-center">
                            <div class="resume-icon mb-5">
                                <i class="fas fa-file-alt fa-3x text-primary card-icon"></i>
                            </div>
                                                        <h4 class="mt-5 font-standard-section-pill">Continue Where You Left Off</h4>

                            <p class="mb-4 font-standard-text">We found a saved draft of your waitlist application. Would you like to continue where you left off?</p>
                            <div class="resume-info outlined-content p-1">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    <span class="font-standard-text">Saved ${this.formatTimeAgo(state.timestamp)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer glassmorphism-footer">
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                            <i class="fas fa-refresh me-2"></i>Start Fresh
                        </button>
                        <button type="button" class="btn btn-primary" id="resumeForm">
                            <i class="fas fa-undo me-2"></i>Resume Application
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(resumeModal);
        
        const resumeBtn = resumeModal.querySelector('#resumeForm');
        resumeBtn.addEventListener('click', () => {
            this.resumeForm(state);
            bootstrap.Modal.getInstance(resumeModal).hide();
        });
        
        // Auto-show the modal
        const modal = new bootstrap.Modal(resumeModal);
        modal.show();
        
        // Remove modal from DOM after hiding
        resumeModal.addEventListener('hidden.bs.modal', () => {
            resumeModal.remove();
        });
    }
    
    resumeForm(state) {
        // Populate form with saved data
        Object.keys(state.data).forEach(key => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = state.data[key] === 'on' || state.data[key] === field.value;
                } else {
                    field.value = state.data[key];
                }
            }
        });
        
        // Restore step
        this.currentStep = state.currentStep;
        
        // Trigger step change event
        document.dispatchEvent(new CustomEvent('stepChanged', {
            detail: { step: this.currentStep }
        }));
        
        // Update form validator if available
        if (window.formValidatorInstance) {
            window.formValidatorInstance.currentStep = this.currentStep;
            window.formValidatorInstance.updateStepDisplay();
        }
        
        // Hide resume button
        const resumeButton = document.getElementById('resumeButton');
        if (resumeButton) {
            resumeButton.style.display = 'none';
        }
        
        this.showResumeSuccessMessage();
    }
    
    showResumeSuccessMessage() {
        const message = document.createElement('div');
        message.className = 'alert alert-success alert-dismissible fade show position-fixed';
        message.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        message.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>Form Restored!</strong> Your previous data has been loaded.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            if (message.parentNode) {
                message.remove();
            }
        }, 5000);
    }
    
    showAutoSaveIndicator() {
        // Remove existing indicator
        const existing = document.querySelector('.auto-save-indicator');
        if (existing) {
            existing.remove();
        }
        
        const indicator = document.createElement('div');
        indicator.className = 'auto-save-indicator position-fixed';
        indicator.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; background: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px;';
        indicator.innerHTML = '<i class="fas fa-save me-1"></i>Auto-saved';
        
        document.body.appendChild(indicator);
        
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 2000);
    }
    
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }
    
    clearFormState() {
        localStorage.removeItem(this.storageKey);
    }
    
    getSessionId() {
        let sessionId = sessionStorage.getItem('tena_form_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('tena_form_session_id', sessionId);
        }
        return sessionId;
    }
    
    formatTimeAgo(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        return 'just now';
    }
    
    // Public method to get analytics data
    getAnalyticsData() {
        return {
            fieldInteractions: Object.fromEntries(this.fieldInteractions),
            stepTimes: Object.fromEntries(this.stepTimes),
            totalTime: Date.now() - this.startTime,
            currentStep: this.currentStep
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.formStateManager = new FormStateManager();
});

// Export for manual use
window.FormStateManager = FormStateManager;
