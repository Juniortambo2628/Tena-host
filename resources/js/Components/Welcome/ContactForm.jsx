import React, { useState } from 'react';
import { notify } from '@/Components/Toast';
import { Send, Mail } from 'lucide-react';
import './ContactForm.css';

const initialState = { name: '', email: '', subject: '', message: '' };

export default function ContactForm() {
    const [form, setForm] = useState(initialState);
    const [submitting, setSubmitting] = useState(false);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm((prev) => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (submitting) return;
        setSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(form),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                notify.success(data.message || "Thanks — we'll get back to you shortly.");
                setForm(initialState);
            } else if (response.status === 422) {
                const firstError = Object.values(data.errors || {})[0];
                notify.error(Array.isArray(firstError) ? firstError[0] : (firstError || 'Please check the form.'));
            } else if (response.status === 429) {
                notify.error(data.message || 'Too many attempts. Please try again later.');
            } else {
                notify.error(data.message || 'Failed to send. Please try again.');
            }
        } catch (err) {
            notify.error('Network error. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <section className="contact-form-section" id="contact">
            <div className="contact-form-card">
                <div className="contact-form-header">
                    <span className="contact-form-badge">
                        <Mail size={14} className="mr-2 inline" /> Questions?
                    </span>
                    <h3 className="contact-form-title">Send us a message</h3>
                    <p className="contact-form-subtitle">
                        Drop a note and the Tena team will reply within 24 hours.
                    </p>
                </div>

                <form className="contact-form-grid" onSubmit={handleSubmit} noValidate>
                    <div className="contact-form-field">
                        <label htmlFor="contact-name" className="contact-form-label">Name</label>
                        <input
                            id="contact-name"
                            name="name"
                            type="text"
                            value={form.name}
                            onChange={handleChange}
                            className="contact-form-input"
                            placeholder="Your full name"
                            required
                            autoComplete="name"
                            maxLength={100}
                        />
                    </div>
                    <div className="contact-form-field">
                        <label htmlFor="contact-email" className="contact-form-label">Email</label>
                        <input
                            id="contact-email"
                            name="email"
                            type="email"
                            value={form.email}
                            onChange={handleChange}
                            className="contact-form-input"
                            placeholder="you@example.com"
                            required
                            autoComplete="email"
                            maxLength={150}
                        />
                    </div>
                    <div className="contact-form-field contact-form-field--full">
                        <label htmlFor="contact-subject" className="contact-form-label">Subject</label>
                        <input
                            id="contact-subject"
                            name="subject"
                            type="text"
                            value={form.subject}
                            onChange={handleChange}
                            className="contact-form-input"
                            placeholder="How can we help?"
                            required
                            maxLength={150}
                        />
                    </div>
                    <div className="contact-form-field contact-form-field--full">
                        <label htmlFor="contact-message" className="contact-form-label">Message</label>
                        <textarea
                            id="contact-message"
                            name="message"
                            rows={5}
                            value={form.message}
                            onChange={handleChange}
                            className="contact-form-textarea"
                            placeholder="Tell us about your property and what you're trying to solve."
                            required
                            maxLength={4000}
                        />
                    </div>
                    <div className="contact-form-actions">
                        <button
                            type="submit"
                            className="contact-form-submit"
                            disabled={submitting}
                        >
                            {submitting ? 'Sending...' : (
                                <>
                                    Send Message <Send size={14} className="ml-2 inline" />
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
