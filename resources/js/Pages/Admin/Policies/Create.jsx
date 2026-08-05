import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, TextArea, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { FileText, ArrowLeft } from 'lucide-react';
import { safeRoute } from '@/lib/route';
import './CreateEdit.css';

export default function PolicyCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        content: '',
        type: 'privacy_policy',
        is_published: false,
        version: '1.0',
        effective_date: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.policies.store'));
    };

    return (
        <PageShell
            title="Create Policy"
            breadcrumbs={[
                { label: 'Policies', href: safeRoute('admin.policies.index') },
                { label: 'Create' },
            ]}
            rootRoute="admin.dashboard"
        >
            <Head title="Create Policy" />
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
                                    <p className="policy-create-edit__card-subtitle">Create a new policy document</p>
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
                                            label="Publish immediately"
                                            checked={data.is_published}
                                            onChange={() => setData('is_published', !data.is_published)}
                                        />
                                    </div>
                                </div>

                                <FormField label="Content" error={errors.content}>
                                    <TextArea
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        placeholder="Write your policy content here. HTML is supported..."
                                        rows={20}
                                        className="policy-create-edit__content-editor"
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
                            Create Policy
                        </PillButton>
                    </FormActions>
                </form>
            </div>
        </PageShell>
    );
}
