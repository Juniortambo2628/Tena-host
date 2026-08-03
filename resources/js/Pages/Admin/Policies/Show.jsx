import React from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FileText, ArrowLeft, Edit3, ToggleRight, Shield, Cookie, AlertCircle, HandshakeIcon } from 'lucide-react';
import './Show.css';

const typeLabels = {
    privacy_policy: 'Privacy Policy',
    terms_of_service: 'Terms of Service',
    cookie_policy: 'Cookie Policy',
    refund_policy: 'Refund Policy',
    acceptable_use: 'Acceptable Use',
    data_processing: 'Data Processing',
    other: 'Other',
};

const typeIcons = {
    privacy_policy: <Shield size={24} />,
    terms_of_service: <FileText size={24} />,
    cookie_policy: <Cookie size={24} />,
    refund_policy: <AlertCircle size={24} />,
    acceptable_use: <HandshakeIcon size={24} />,
    data_processing: <FileText size={24} />,
    other: <FileText size={24} />,
};

export default function PolicyShow({ policy }) {
    return (
        <PageShell
            title={policy.title}
            breadcrumbs={[
                { label: 'Policies', href: route('admin.policies.index') },
                { label: policy.title },
            ]}
            rootRoute="admin.dashboard"
            actions={[
                { label: 'Edit', onClick: () => router.visit(route('admin.policies.edit', policy.slug)), variant: 'black', icon: <Edit3 size={16} /> },
            ]}
        >
            <Head title={policy.title} />
            <div className="policy-show">
                <div className="policy-show__header">
                    <PillButton
                        variant="ghost"
                        onClick={() => router.visit(route('admin.policies.index'))}
                        icon={<ArrowLeft size={16} />}
                    >
                        Back to Policies
                    </PillButton>
                </div>

                <div className="policy-show__meta-grid">
                    <GlassCard padding="p-6">
                        <div className="policy-show__meta-item">
                            <span className="policy-show__meta-label">Type</span>
                            <div className="policy-show__meta-value-row">
                                <span className="policy-show__meta-icon">{typeIcons[policy.type]}</span>
                                <span className="policy-show__meta-value">{typeLabels[policy.type]}</span>
                            </div>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="policy-show__meta-item">
                            <span className="policy-show__meta-label">Version</span>
                            <span className="policy-show__meta-value">v{policy.version}</span>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="policy-show__meta-item">
                            <span className="policy-show__meta-label">Status</span>
                            <span className={`policy-show__status-badge ${policy.is_published ? 'policy-show__status-badge--published' : 'policy-show__status-badge--draft'}`}>
                                {policy.is_published ? 'Published' : 'Draft'}
                            </span>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="policy-show__meta-item">
                            <span className="policy-show__meta-label">Effective Date</span>
                            <span className="policy-show__meta-value">
                                {policy.effective_date ? new Date(policy.effective_date).toLocaleDateString() : 'Not set'}
                            </span>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="policy-show__meta-item">
                            <span className="policy-show__meta-label">Last Reviewed</span>
                            <span className="policy-show__meta-value">
                                {policy.last_reviewed_at ? new Date(policy.last_reviewed_at).toLocaleDateString() : 'Never'}
                                {policy.last_reviewed_by && ` by ${policy.last_reviewed_by}`}
                            </span>
                        </div>
                    </GlassCard>
                </div>

                {policy.description && (
                    <GlassCard padding="p-6">
                        <div className="policy-show__description">
                            <span className="policy-show__section-label">Description</span>
                            <p className="policy-show__description-text">{policy.description}</p>
                        </div>
                    </GlassCard>
                )}

                <GlassCard padding="p-8">
                    <div className="policy-show__content">
                        <span className="policy-show__section-label">Content</span>
                        <div
                            className="policy-show__content-body"
                            dangerouslySetInnerHTML={{ __html: policy.content }}
                        />
                    </div>
                </GlassCard>
            </div>
        </PageShell>
    );
}
