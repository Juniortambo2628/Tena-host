import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { notify } from '@/Components/Toast';
import './WaitlistModal.css';

export default function WaitlistModal({ show, onClose }) {
    const [step, setStep] = useState(1);
    const [submitting, setSubmitting] = useState(false);
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        property_type: 'Entire Place',
        units: '1-5',
        primary_platform: 'Airbnb',
        biggest_challenge: 'Getting more direct bookings',
        years_hosting: '0-1',
        agree_updates: false
    });

    if (!show) return null;

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const nextStep = () => setStep(prev => prev + 1);
    const prevStep = () => setStep(prev => prev - 1);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (submitting) return;
        setSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/waitlist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'text/html, application/xhtml+xml',
                },
                body: JSON.stringify({
                    first_name: formData.first_name,
                    last_name: formData.last_name,
                    email: formData.email,
                    phone: formData.phone,
                    property_type: formData.property_type,
                    units: formData.units,
                    primary_platform: formData.primary_platform,
                    biggest_challenge: formData.biggest_challenge,
                    agree_updates: formData.agree_updates,
                }),
            });

            if (response.ok || response.redirected) {
                notify.success("Thanks! You're on the list.");
                onClose();
            } else if (response.status === 422) {
                const data = await response.json();
                const firstError = Object.values(data.errors || data)[0];
                notify.error(Array.isArray(firstError) ? firstError[0] : (firstError || 'Validation failed.'));
            } else if (response.status === 419) {
                notify.error('Session expired. Please refresh the page and try again.');
            } else {
                const data = await response.json().catch(() => ({}));
                notify.error(data.message || data.error || 'Submission failed. Please try again.');
            }
        } catch (err) {
            notify.error('Network error. Please check your connection and try again.');
        } finally {
            setSubmitting(false);
        }
    };

    // Sidebar Step Icon Component
    const StepIndicator = ({ number, title, activeStep }) => {
        const isActive = activeStep === number;
        const isCompleted = activeStep > number;

        return (
            <div className="waitlist-step">
                <div
                    className={`waitlist-step-number ${isActive ? 'waitlist-step-number-active' :
                            isCompleted ? 'waitlist-step-number-completed' : 'waitlist-step-number-inactive'}`}
                >
                    {isCompleted ? <i className="fas fa-check"></i> : number}
                </div>
                <div className={`waitlist-step-title ${isActive ? 'waitlist-step-title-active' : 'waitlist-step-title-inactive'}`}>
                    {title}
                </div>
                {/* Vertical Line Connector (optional, for visual flow if needed) */}
            </div>
        );
    };

    return (
        <div className="waitlist-overlay">
            <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="waitlist-backdrop"
                onClick={onClose}
            ></motion.div>

            <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                className="waitlist-modal"
            >
                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="waitlist-close-btn"
                >
                    <i className="fas fa-times text-lg"></i>
                </button>

                {/* Left Sidebar */}
                <div className="waitlist-sidebar">
                    <div>
                        <div className="waitlist-sidebar-logo">
                            <img src="/legacy/assets/Tena-logo-square.jpg" className="w-8 h-8 rounded" alt="Logo" />
                            <span>Tena</span>
                        </div>

                        <div className="waitlist-steps">
                            <StepIndicator number={1} title="Personal Details" activeStep={step} />
                            <StepIndicator number={2} title="Hosting Profile" activeStep={step} />
                            <StepIndicator number={3} title="Confirmation" activeStep={step} />
                        </div>
                    </div>

                    <div className="waitlist-sidebar-footer">
                        Step {step} of 3
                    </div>
                </div>

                {/* Right Content Area */}
                <div className="waitlist-content">
                    <form onSubmit={handleSubmit} className="waitlist-form">
                        <div className="waitlist-form-scroll">
                            <AnimatePresence mode="wait">
                                {step === 1 && (
                                    <motion.div
                                        key="step1"
                                        initial={{ x: 20, opacity: 0 }}
                                        animate={{ x: 0, opacity: 1 }}
                                        exit={{ x: -20, opacity: 0 }}
                                        transition={{ duration: 0.3 }}
                                    >
                                    <h2 className="waitlist-form-heading">Let's get started</h2>
                                    <p className="waitlist-form-subheading">Join the waiting list to get early access.</p>

                                    <div className="waitlist-form-fields">
                                        <div className="waitlist-form-row">
                                            <div>
                                                <label className="waitlist-field-label">First Name</label>
                                                <input
                                                    type="text"
                                                    name="first_name"
                                                    value={formData.first_name}
                                                    onChange={handleInputChange}
                                                    className="waitlist-input"
                                                    placeholder="Jane"
                                                />
                                            </div>
                                            <div>
                                                <label className="waitlist-field-label">Last Name</label>
                                                <input
                                                    type="text"
                                                    name="last_name"
                                                    value={formData.last_name}
                                                    onChange={handleInputChange}
                                                    className="waitlist-input"
                                                    placeholder="Doe"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label className="waitlist-field-label">Email Address</label>
                                            <input
                                                type="email"
                                                name="email"
                                                value={formData.email}
                                                onChange={handleInputChange}
                                                className="waitlist-input"
                                                placeholder="jane@example.com"
                                            />
                                        </div>
                                        <div>
                                            <label className="waitlist-field-label">Phone Number</label>
                                            <input
                                                type="tel"
                                                name="phone"
                                                value={formData.phone}
                                                onChange={handleInputChange}
                                                className="waitlist-input"
                                                placeholder="+1 (555) 000-0000"
                                            />
                                        </div>
                                    </div>
                                </motion.div>
                            )}

                            {step === 2 && (
                                <motion.div
                                    key="step2"
                                    initial={{ x: 20, opacity: 0 }}
                                    animate={{ x: 0, opacity: 1 }}
                                    exit={{ x: -20, opacity: 0 }}
                                    transition={{ duration: 0.3 }}
                                >
                                    <h2 className="waitlist-form-heading">Tell us about your hosting</h2>
                                    <p className="waitlist-form-subheading">Help us tailor the experience for you.</p>

                                    <div className="waitlist-form-fields-step2">
                                        <div>
                                            <label className="waitlist-field-label">Property Type</label>
                                            <select
                                                name="property_type"
                                                value={formData.property_type}
                                                onChange={handleInputChange}
                                                className="waitlist-select"
                                            >
                                                <option>Entire Place</option>
                                                <option>Private Room</option>
                                                <option>Shared Room</option>
                                                <option>Hotel / Boutique</option>
                                           </select>
                                       </div>
                                        <div>
                                            <label className="waitlist-field-label">Number of Units</label>
                                            <div className="waitlist-units-grid">
                                                {['1-5', '6-20', '21+'].map((opt) => (
                                                    <button
                                                        key={opt}
                                                        type="button"
                                                        onClick={() => setFormData({ ...formData, units: opt })}
                                                        className={`waitlist-unit-btn ${formData.units === opt ? 'waitlist-unit-btn-active' : 'waitlist-unit-btn-inactive'}`}
                                                    >
                                                        {opt}
                                                   </button>
                                                ))}
                                           </div>
                                       </div>
                                        <div>
                                            <label className="waitlist-field-label">Primary Booking Platform</label>
                                            <div className="waitlist-platform-grid">
                                                {['Airbnb', 'Booking.com', 'Vrbo', 'Multiple', 'Direct Bookings'].map((opt) => (
                                                    <button
                                                        key={opt}
                                                        type="button"
                                                        onClick={() => setFormData({ ...formData, primary_platform: opt })}
                                                        className={`waitlist-platform-btn ${formData.primary_platform === opt ? 'waitlist-platform-btn-active' : 'waitlist-platform-btn-inactive'}`}
                                                    >
                                                        {opt}
                                                   </button>
                                                ))}
                                           </div>
                                       </div>
                                        <div>
                                            <label className="waitlist-field-label">What's your biggest challenge today</label>
                                            <div className="waitlist-challenge-list">
                                                {[
                                                    'Getting more direct bookings',
                                                    'Getting repeat bookings',
                                                    'OTA (Airbnb, Booking.com etc commissions)',
                                                    'Guest communication',
                                                    'Other'
                                                ].map((opt) => (
                                                    <button
                                                        key={opt}
                                                        type="button"
                                                        onClick={() => setFormData({ ...formData, biggest_challenge: opt })}
                                                        className={`waitlist-challenge-btn ${formData.biggest_challenge === opt ? 'waitlist-challenge-btn-active' : 'waitlist-challenge-btn-inactive'}`}
                                                    >
                                                        <span className={`waitlist-challenge-radio ${formData.biggest_challenge === opt ? 'waitlist-challenge-radio-active' : 'waitlist-challenge-radio-inactive'}`}>
                                                            {formData.biggest_challenge === opt && <span className="waitlist-challenge-radio-dot" />}
                                                       </span>
                                                        <span>{opt}</span>
                                                   </button>
                                                ))}
                                           </div>
                                       </div>
                                        <div>
                                            <label className="waitlist-field-label">GDPR Consent</label>
                                            <label className="waitlist-consent-label">
                                                <input
                                                    type="checkbox"
                                                    name="agree_updates"
                                                    checked={formData.agree_updates}
                                                    onChange={handleInputChange}
                                                    className="waitlist-consent-checkbox"
                                                />
                                                <span className="waitlist-consent-text">
                                                    I agree to receive updates about Tena.
                                               </span>
                                           </label>
                                       </div>
                                   </div>
                                </motion.div>
                            )}

                            {step === 3 && (
                                <motion.div
                                    key="step3"
                                    initial={{ x: 20, opacity: 0 }}
                                    animate={{ x: 0, opacity: 1 }}
                                    exit={{ x: -20, opacity: 0 }}
                                    transition={{ duration: 0.3 }}
                                    className="waitlist-confirmation"
                                >
                                    <div className="waitlist-confirmation-icon">
                                        <i className="fas fa-paper-plane text-3xl"></i>
                                    </div>
                                    <h2 className="waitlist-confirmation-title">Almost There!</h2>
                                    <p className="waitlist-confirmation-text">Click submit to confirm your spot on the waitlist. We'll be in touch soon.</p>

                                    <div className="waitlist-confirmation-summary">
                                        <div className="waitlist-confirmation-row">
                                            <span className="waitlist-confirmation-row-label">Name</span>
                                            <span className="waitlist-confirmation-row-value">{formData.first_name} {formData.last_name}</span>
                                        </div>
                                        <div className="waitlist-confirmation-row">
                                            <span className="waitlist-confirmation-row-label">Email</span>
                                            <span className="waitlist-confirmation-row-value">{formData.email}</span>
                                        </div>
                                        <div className="waitlist-confirmation-row">
                                            <span className="waitlist-confirmation-row-label">Units</span>
                                            <span className="waitlist-confirmation-row-value">{formData.units}</span>
                                       </div>
                                        <div className="waitlist-confirmation-row">
                                            <span className="waitlist-confirmation-row-label">Platform</span>
                                            <span className="waitlist-confirmation-row-value">{formData.primary_platform}</span>
                                       </div>
                                        <div className="waitlist-confirmation-row">
                                            <span className="waitlist-confirmation-row-label">Challenge</span>
                                            <span className="waitlist-confirmation-row-value">{formData.biggest_challenge}</span>
                                       </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                        </div>

                        {/* Footer / Buttons */}
                        <div className="waitlist-footer">
                            {step > 1 ? (
                                <button type="button" onClick={prevStep} className="waitlist-back-btn">
                                    Back
                                </button>
                            ) : (
                                <div></div>
                            )}

                            {step < 3 ? (
                                <button type="button" onClick={nextStep} className="waitlist-next-btn">
                                    Next Step <i className="fas fa-arrow-right text-xs"></i>
                                </button>
                            ) : (
                                <button type="button" onClick={handleSubmit} className="waitlist-submit-btn" disabled={submitting}>
                                    {submitting ? 'Submitting...' : 'Submit'}
                                </button>
                            )}
                        </div>
                    </form>
                </div>
            </motion.div>
        </div>
    );
}
