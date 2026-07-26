import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import './Marketing.css';

export default function MarketingBuilder() {
    const breadcrumbs = [
        { label: 'Marketing', href: '#' },
        { label: 'Campaign Builder' }
    ];

    const actions = [
        { label: 'Save Draft', variant: 'secondary' },
        { label: 'Send Campaign', variant: 'primary', icon: <i className="fas fa-paper-plane" /> },
    ];

    return (
        <DashboardLayout title="Campaign Builder">
            <Head title="Marketing Builder" />

            <DashboardHero
                title="Design Campaign"
                breadcrumbs={breadcrumbs}
                actions={actions}
            />

            <div className="host-marketing-grid">
                {/* Editor Side */}
                <div className="host-marketing-editor">
                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="host-marketing-editor-header">
                            <h3 className="host-marketing-editor-title">Visual Editor</h3>
                        </div>

                        <div className="host-marketing-editor-body">
                            <section>
                                <label className="host-marketing-label">Header Image</label>
                                <div className="host-marketing-upload-zone">
                                    <div className="host-marketing-upload-icon">
                                        <i className="fas fa-arrow-up text-xl"></i>
                                    </div>
                                    <p className="host-marketing-upload-text">Drag custom image or <span className="host-marketing-upload-browse">browse</span></p>
                                </div>
                            </section>

                            <section className="host-marketing-form-section">
                                <div>
                                    <label className="host-marketing-label">Email Subject</label>
                                    <input
                                        type="text"
                                        className="host-marketing-input"
                                        defaultValue="Exclusive offer for %FIRSTNAME%!"
                                    />
                                </div>

                                <div>
                                    <label className="host-marketing-label">Message Body</label>
                                    <textarea
                                        className="host-marketing-textarea"
                                        defaultValue="We noticed you recently stayed at %PROPERTYNAME%. We'd love to welcome you back with a special 15% discount on your next direct booking!"
                                    />
                                    <div className="host-marketing-tags">
                                        <button className="host-marketing-tag">%FIRSTNAME%</button>
                                        <button className="host-marketing-tag">%DIRECTLINK%</button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </GlassCard>
                </div>

                {/* Preview Side */}
                <div className="host-marketing-preview-container">
                    <div className="host-marketing-preview-frame">
                        {/* Device Frame Decorations */}
                        <div className="host-marketing-preview-decoration"></div>

                        <div className="host-marketing-preview-device">
                            <div className="host-marketing-preview-header">
                                <h4 className="host-marketing-preview-brand">TENA</h4>
                                <p className="host-marketing-preview-subtitle">Smart Branding</p>
                            </div>

                            <div className="host-marketing-preview-image">
                                <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=800&q=80" alt="Preview" />
                            </div>

                            <div className="host-marketing-preview-body">
                                <h3 className="host-marketing-preview-heading">Exclusive offer for you!</h3>
                                <p className="host-marketing-preview-desc">
                                    We noticed you recently stayed at Tena Residences. We'd love to welcome you back with a special 15% discount on your next direct booking!
                                </p>
                                <PillButton variant="primary" className="w-full py-4 text-xs">Claim Discount</PillButton>
                            </div>

                            <div className="host-marketing-preview-footer">
                                <p className="host-marketing-preview-footer-text">Sent via Tena Marketing</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
