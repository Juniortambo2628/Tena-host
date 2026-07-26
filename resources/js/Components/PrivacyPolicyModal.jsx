import React from 'react';
import GlassModal from './GlassModal';
import { Shield, Eye, Lock, Server } from 'lucide-react';
import './PrivacyPolicyModal.css';

export default function PrivacyPolicyModal({ isOpen, onClose }) {
    return (
        <GlassModal
            isOpen={isOpen}
            onClose={onClose}
            title="Privacy Policy"
            maxWidth="2xl"
        >
            <div className="privacy-policy-modal__content">
                <div className="privacy-policy-modal__header">
                    <Shield size={32} className="privacy-policy-modal__header-icon" />
                    <div>
                        <h3 className="privacy-policy-modal__heading">Your Data, Protected.</h3>
                        <p className="privacy-policy-modal__subtitle">How we handle your information</p>
                    </div>
                </div>

                <div className="privacy-policy-modal__points">
                    <PolicyPoint
                        icon={<Lock size={18} />}
                        title="Secure Storage"
                        content="All sensitive data, including guest contact details and host credentials, is encrypted at rest using industry-standard AES-256 encryption. Our databases are hosted on enterprise-grade secure servers with restricted access."
                    />

                    <PolicyPoint
                        icon={<Eye size={18} />}
                        title="Data Usage"
                        content="We use collected information solely to power your dashboard analytics, automate guest communications, and improve platform performance. We never sell, rent, or trade your data to third parties."
                    />

                    <PolicyPoint
                        icon={<Server size={18} />}
                        title="Authorized Access"
                        content="Access to data is strictly limited to authorized Tena personnel only when necessary for technical support or system maintenance. All access is logged and audited for security."
                    />
                </div>

                <div className="privacy-policy-modal__footer">
                    <p className="privacy-policy-modal__footer-text">
                        For more detailed information, please contact our security team at security@tena.app.
                    </p>
                </div>
            </div>
        </GlassModal>
    );
}

function PolicyPoint({ icon, title, content }) {
    return (
        <div className="privacy-policy-modal__point group">
            <div className="privacy-policy-modal__point-icon">
                {icon}
            </div>
            <div className="privacy-policy-modal__point-content">
                <h4 className="privacy-policy-modal__point-title">{title}</h4>
                <p className="privacy-policy-modal__point-text">{content}</p>
            </div>
        </div>
    );
}
