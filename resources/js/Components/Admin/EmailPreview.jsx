import React, { useState, useEffect, useRef, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Laptop, Smartphone, Mail, ChevronLeft, ChevronRight } from 'lucide-react';
import './EmailPreview.css';

const emailComponents = {
    welcome: () => import('@/Emails/WelcomeEmail'),
    receipt: () => import('@/Emails/PaymentReceipt'),
    reset: () => import('@/Emails/ForgotPasswordEmail'),
    waitlist_confirmation: () => import('@/Emails/WaitlistConfirmationEmail'),
    waitlist_welcome: () => import('@/Emails/WaitlistWelcomeEmail'),
};

function resolveBody(settings, template) {
    switch (template) {
        case 'welcome': return settings.welcome_email_body || '';
        case 'receipt': return settings.receipt_email_body || '';
        case 'waitlist_confirmation': return settings.waitlist_confirmation_body || '';
        case 'waitlist_welcome': return settings.waitlist_welcome_body || '';
        default: return settings.forgot_password_email_body || '';
    }
}

function resolveHeading(settings, template) {
    switch (template) {
        case 'welcome': return settings.welcome_email_heading || '';
        case 'receipt': return settings.receipt_email_heading || '';
        case 'waitlist_confirmation': return settings.waitlist_confirmation_heading || '';
        case 'waitlist_welcome': return settings.waitlist_welcome_heading || '';
        default: return settings.forgot_password_email_heading || '';
    }
}

export default function EmailPreview({ settings }) {
    const [viewMode, setViewMode] = useState('desktop');
    const [activeTemplate, setActiveTemplate] = useState('welcome');
    const iframeRef = useRef(null);

    const templates = [
        { id: 'welcome', name: 'Welcome' },
        { id: 'receipt', name: 'Receipt' },
        { id: 'reset', name: 'Password Reset' },
        { id: 'waitlist_confirmation', name: 'Waitlist Confirmation' },
        { id: 'waitlist_welcome', name: 'Waitlist Welcome' },
    ];

    const emailProps = useMemo(() => ({
        primaryColor: settings.email_primary_color || '#000000',
        accentColor: settings.email_accent_color || '#FFD300',
        businessName: settings.site_name || 'Tena',
        businessAddress: settings.business_address || 'Nairobi, Kenya',
        logoUrl: settings.logo_url || '/legacy/assets/Tena-logo-square.jpg',
        heading: resolveHeading(settings, activeTemplate),
        body: resolveBody(settings, activeTemplate),
        name: "Empire Builder",
        firstName: "Test",
        lastName: "User",
        email: "test@example.com",
        propertyType: "Vacation Rental",
        units: "5",
        primaryPlatform: "Airbnb",
        biggestChallenge: "Managing multiple platforms",
        customerName: "Valued Host",
        amount: "6,500",
        planName: "Pro Host Plan",
        transactionId: "TXN_12345678",
        userName: "Empire Builder",
        resetLink: "#",
    }), [settings.email_primary_color, settings.email_accent_color, settings.site_name,
        settings.business_address, settings.logo_url, settings.welcome_email_heading,
        settings.welcome_email_body, settings.receipt_email_heading, settings.receipt_email_body,
        settings.waitlist_confirmation_heading, settings.waitlist_confirmation_body,
        settings.waitlist_welcome_heading, settings.waitlist_welcome_body,
        settings.forgot_password_email_heading, settings.forgot_password_email_body,
        activeTemplate]);

    useEffect(() => {
        if (!iframeRef.current) return;
        const doc = iframeRef.current.contentDocument;
        if (!doc) return;

        let cancelled = false;

        const renderEmail = async () => {
            try {
                const [{ render }, { default: SelectedTemplate }] = await Promise.all([
                    import('@react-email/render'),
                    emailComponents[activeTemplate]()
                ]);
                if (cancelled) return;
                const html = await render(React.createElement(SelectedTemplate, emailProps));
                if (cancelled) return;
                doc.open();
                doc.write(html);
                doc.close();
            } catch (err) {
                if (cancelled) return;
                console.error('Email render failed:', err);
                doc.open();
                doc.write('<div style="padding:20px;color:#999;">Preview unavailable</div>');
                doc.close();
            }
        };

        renderEmail();
        return () => { cancelled = true; };
    }, [activeTemplate, emailProps]);

    return (
        <div className="email-preview">
            {/* Toolbar */}
            <div className="email-preview__toolbar">
                <div className="email-preview__toolbar-section">
                    <div className="email-preview__template-tabs">
                        {templates.map((t) => (
                            <button
                                key={t.id}
                                onClick={() => setActiveTemplate(t.id)}
                                className={`email-preview__template-button ${activeTemplate === t.id
                                    ? 'email-preview__template-button--active'
                                    : 'email-preview__template-button--inactive'
                                    }`}
                            >
                                {t.name}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="email-preview__view-tabs">
                    <button
                        onClick={() => setViewMode('desktop')}
                        className={`email-preview__view-button ${viewMode === 'desktop' ? 'email-preview__view-button--active' : 'email-preview__view-button--inactive'}`}
                    >
                        <Laptop size={18} />
                    </button>
                    <button
                        onClick={() => setViewMode('mobile')}
                        className={`email-preview__view-button ${viewMode === 'mobile' ? 'email-preview__view-button--active' : 'email-preview__view-button--inactive'}`}
                    >
                        <Smartphone size={18} />
                    </button>
                </div>
            </div>

            {/* Preview Area */}
            <div className="email-preview__stage">
                <motion.div
                    layout
                    initial={false}
                    animate={{
                        width: viewMode === 'desktop' ? '100%' : '375px',
                        maxWidth: viewMode === 'desktop' ? '800px' : '375px'
                    }}
                    className="email-preview__frame"
                >
                    {/* Inbox UI Header */}
                    <div className="email-preview__inbox-header">
                        <div className="email-preview__sender-avatar">
                            <Mail size={20} />
                        </div>
                        <div className="email-preview__sender-info">
                            <h4 className="email-preview__sender-name">{emailProps.businessName}</h4>
                            <p className="email-preview__recipient">To: user@example.com • Just now</p>
                        </div>
                    </div>

                    {/* Email Content Frame */}
                    <div className="email-preview__content-frame">
                        <iframe
                            ref={iframeRef}
                            title="Email Preview"
                            className="email-preview__iframe"
                        />
                    </div>
                </motion.div>
            </div>

            {/* Status Footer */}
            <div className="email-preview__footer">
                <p className="email-preview__footer-status">
                    Live Rendering: {templates.find(t => t.id === activeTemplate)?.name}
                </p>
                <div className="email-preview__live-indicator">
                    <span className="email-preview__live-dot"></span>
                    <span className="email-preview__live-label">Real-time sync</span>
                </div>
            </div>
        </div>
    );
}
