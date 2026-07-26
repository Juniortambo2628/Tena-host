import React from 'react';
import GlassModal from './GlassModal';
import { ScrollText, ShieldCheck, Database, Info } from 'lucide-react';
import './TermsModal.css';

export default function TermsModal({ isOpen, onClose }) {
    return (
        <GlassModal
            isOpen={isOpen}
            onClose={onClose}
            title="Policy & Transparency"
            maxWidth="2xl"
        >
            <div className="terms-modal__content">
                {/* Header Section */}
                <div className="terms-modal__header">
                    <ScrollText size={32} className="terms-modal__header-icon" />
                    <div>
                        <h3 className="terms-modal__heading">Terms of Service</h3>
                        <p className="terms-modal__subtitle">Last updated: February 10, 2026</p>
                    </div>
                </div>

                {/* Content Sections */}
                <div className="terms-modal__sections">
                    <PolicySection
                        icon={<ShieldCheck size={18} />}
                        title="User Responsibilities"
                        content="As a Tena host, you are responsible for maintaining the accuracy of your property data and guest communications. Dashboards must be used in compliance with local laws."
                    />

                    <PolicySection
                        icon={<Database size={18} />}
                        title="What we collect"
                        content="We collect property details, guest contact information (encrypted), and transaction logs to power your analytics and automate your hosting workflow. We do not sell your data."
                    />

                    <PolicySection
                        icon={<Info size={18} />}
                        title="Subscription Terms"
                        content="Billing is processed via M-Pesa or Stripe. Subscriptions can be canceled at any time from the billing portal. Refunds are subject to our 14-day policy."
                    />
                </div>

                <div className="terms-modal__footer">
                    <p className="terms-modal__footer-text">
                        By using Tena, you agree to our full Terms and Conditions. This summary is intended to provide clarity on our core pillars of operation.
                    </p>
                </div>
            </div>
        </GlassModal>
    );
}

function PolicySection({ icon, title, content }) {
    return (
        <div className="terms-modal__section group">
            <div className="terms-modal__section-icon">
                {icon}
            </div>
            <div className="terms-modal__section-content">
                <h4 className="terms-modal__section-title">{title}</h4>
                <p className="terms-modal__section-text">{content}</p>
            </div>
        </div>
    );
}
