import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Laptop, Smartphone, Mail, ChevronLeft, ChevronRight } from 'lucide-react';
import { render } from '@react-email/render';
import WelcomeEmail from '@/Emails/WelcomeEmail';
import PaymentReceipt from '@/Emails/PaymentReceipt';
import ForgotPasswordEmail from '@/Emails/ForgotPasswordEmail';
import './EmailPreview.css';

const templates = [
    { id: 'welcome', name: 'Welcome Email', component: WelcomeEmail },
    { id: 'receipt', name: 'Payment Receipt', component: PaymentReceipt },
    { id: 'reset', name: 'Password Reset', component: ForgotPasswordEmail },
];

export default function EmailPreview({ settings }) {
    const [viewMode, setViewMode] = useState('desktop');
    const [activeTemplate, setActiveTemplate] = useState('welcome');
    const iframeRef = useRef(null);

    const templates = [
        { id: 'welcome', name: 'Welcome Email', component: WelcomeEmail },
        { id: 'receipt', name: 'Payment Receipt', component: PaymentReceipt },
        { id: 'reset', name: 'Password Reset', component: ForgotPasswordEmail },
    ];

    const emailProps = {
        primaryColor: settings.email_primary_color || '#000000',
        accentColor: settings.email_accent_color || '#FFD300',
        businessName: settings.site_name || 'Tena',
        businessAddress: settings.business_address || 'Nairobi, Kenya',
        logoUrl: settings.logo_url || '/legacy/assets/Tena-logo-square.jpg',
        heading: activeTemplate === 'welcome' ? settings.welcome_email_heading :
            activeTemplate === 'receipt' ? settings.receipt_email_heading :
                settings.forgot_password_email_heading,
        body: activeTemplate === 'welcome' ? settings.welcome_email_body :
            activeTemplate === 'receipt' ? settings.receipt_email_body :
                settings.forgot_password_email_body,
        name: "Empire Builder",
        customerName: "Valued Host",
        amount: "6,500",
        planName: "Pro Host Plan",
        transactionId: "TXN_12345678",
        userName: "Empire Builder",
        resetLink: "#",
    };

    useEffect(() => {
        if (!iframeRef.current) return;
        const doc = iframeRef.current.contentDocument;
        const SelectedTemplate = templates.find(t => t.id === activeTemplate).component;
        const html = render(React.createElement(SelectedTemplate, emailProps));
        doc.open();
        doc.write(html);
        doc.close();
    }, [activeTemplate, settings]);

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
