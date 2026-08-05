import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, TextArea, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { FileText, ArrowLeft } from 'lucide-react';
import { safeRoute } from '@/lib/route';
import ContentField from '@/Components/CMS/ContentField';
import './CreateEdit.css';

export default function PolicyEdit({ policy }) {
    const { data, setData, put, processing, errors } = useForm({
        title: policy.title || '',
        description: policy.description || '',
        content: policy.content || '',
        type: policy.type || 'privacy_policy',
        is_published: policy.is_published || false,
        version: policy.version || '1.0',
        effective_date: policy.effective_date ? policy.effective_date.split('T')[0] : '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('admin.policies.update', policy.slug));
    };

    return (
        <PageShell
            title={`Edit: ${policy.title}`}
            breadcrumbs={[
                { label: 'Policies', href: safeRoute('admin.policies.index') },
                { label: policy.title },
            ]}
            rootRoute="admin.dashboard"
        >
            <Head title={`Edit ${policy.title}`} />
            <div className="policy-create-edit">
                <div className="policy-create-edit__header">
                    <PillButton
                        variant="ghost"
                        onClick={() => router.visit(safeRoute('admin.policies.index'))}
                        icon={<ArrowLeft size={16} />}
                    >
                        Back to Policies
                    </PillButton>
                </div>

                <form onSubmit={handleSubmit}>
                    <GlassCard padding="p-8">
                        <div className="policy-create-edit__card-inner">
                            <div className="policy-create-edit__card-header">
                                <div className="policy-create-edit__card-icon"><FileText size={24} /></div>
                                <div>
                                    <h3 className="policy-create-edit__card-title">Policy Information</h3>
                                    <p className="policy-create-edit__card-subtitle">
                                        Last reviewed: {policy.last_reviewed_at ? new Date(policy.last_reviewed_at).toLocaleDateString() : 'Never'}
                                        {policy.last_reviewed_by && ` by ${policy.last_reviewed_by}`}
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <FormField label="Title" error={errors.title}>
                                    <TextInput
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="e.g., Privacy Policy"
                                    />
                                </FormField>

                                <FormField label="Description" error={errors.description}>
                                    <TextArea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Brief description of this policy..."
                                        rows={2}
                                    />
                                </FormField>

                                <div className="grid grid-cols-2 gap-6">
                                    <FormField label="Type" error={errors.type}>
                                        <Select
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value)}
                                        >
                                            <option value="privacy_policy">Privacy Policy</option>
                                            <option value="terms_of_service">Terms of Service</option>
                                            <option value="cookie_policy">Cookie Policy</option>
                                            <option value="refund_policy">Refund Policy</option>
                                            <option value="acceptable_use">Acceptable Use</option>
                                            <option value="data_processing">Data Processing</option>
                                            <option value="other">Other</option>
                                        </Select>
                                    </FormField>

                                    <FormField label="Version" error={errors.version}>
                                        <TextInput
                                            value={data.version}
                                            onChange={(e) => setData('version', e.target.value)}
                                            placeholder="1.0"
                                        />
                                    </FormField>
                                </div>

                                <div className="grid grid-cols-2 gap-6">
                                    <FormField label="Effective Date" error={errors.effective_date}>
                                        <TextInput
                                            type="date"
                                            value={data.effective_date}
                                            onChange={(e) => setData('effective_date', e.target.value)}
                                        />
                                    </FormField>

                                    <div className="flex items-end pb-2">
                                        <CheckboxField
                                            label="Published"
                                            checked={data.is_published}
                                            onChange={() => setData('is_published', !data.is_published)}
                                        />
                                    </div>
                                </div>

                                <FormField label="Content" error={errors.content}>
                                    <ContentField
                                        type="richtext"
                                        value={data.content}
                                        onChange={(val) => setData('content', val)}
                                        placeholder="Write your policy content here..."
                                    />
                                </FormField>
                            </div>
                        </div>
                    </GlassCard>

                    <FormActions>
                        <PillButton
                            variant="ghost"
                            onClick={() => router.visit(safeRoute('admin.policies.index'))}
                        >
                            Cancel
                        </PillButton>
                        <PillButton
                            variant="primary"
                            type="submit"
                            processing={processing}
                        >
                            Update Policy
                        </PillButton>
                    </FormActions>
                </form>
            </div>
        </PageShell>
    );
}
