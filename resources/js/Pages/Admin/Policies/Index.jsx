import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import { FileText, Plus, Eye, Edit3, Trash2, ToggleLeft, ToggleRight, Shield, Cookie, AlertCircle, HandshakeIcon } from 'lucide-react';
import { notify } from '@/Components/Toast';
import { useConfirm } from '@/hooks/useConfirm';
import { safeRoute } from '@/lib/route';
import './Index.css';

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
    privacy_policy: <Shield size={16} />,
    terms_of_service: <FileText size={16} />,
    cookie_policy: <Cookie size={16} />,
    refund_policy: <AlertCircle size={16} />,
    acceptable_use: <HandshakeIcon size={16} />,
    data_processing: <FileText size={16} />,
    other: <FileText size={16} />,
};

export default function PolicyIndex({ policies }) {
    const [selectedIds, setSelectedIds] = useState([]);
    const { confirm, ConfirmDialogEl } = useConfirm();

    const handleDelete = async (policy) => {
        const result = await confirm({
            title: 'Delete Policy',
            message: `Are you sure you want to delete "${policy.title}"?`,
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (result) {
            router.delete(route('admin.policies.destroy', policy.slug), {
                onSuccess: () => notify.success('Policy deleted.'),
                onError: () => notify.error('Failed to delete policy.'),
            });
        }
    };

    const handleTogglePublish = (policy) => {
        router.post(route('admin.policies.toggle', policy.slug), {}, {
            preserveScroll: true,
            onSuccess: () => notify.success(`Policy ${policy.is_published ? 'unpublished' : 'published'}.`),
            onError: () => notify.error('Failed to update policy status.'),
        });
    };

    const columns = [
        {
            key: 'type',
            label: 'Type',
            render: (item) => (
                <div className="policies-page__type-cell">
                    <span className="policies-page__type-icon">{typeIcons[item.type]}</span>
                    <span className="policies-page__type-label">{typeLabels[item.type]}</span>
                </div>
            ),
        },
        {
            key: 'title',
            label: 'Title',
            render: (item) => (
                <span className="policies-page__title">{item.title}</span>
            ),
        },
        {
            key: 'version',
            label: 'Version',
            render: (item) => (
                <span className="policies-page__version">v{item.version}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (item) => (
                <span className={`policies-page__status-badge ${item.is_published ? 'policies-page__status-badge--published' : 'policies-page__status-badge--draft'}`}>
                    {item.is_published ? 'Published' : 'Draft'}
                </span>
            ),
        },
        {
            key: 'effective_date',
            label: 'Effective Date',
            render: (item) => (
                <span className="policies-page__date">
                    {item.effective_date ? new Date(item.effective_date).toLocaleDateString() : '-'}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'view',
            label: 'View',
            icon: <Eye size={14} />,
            onClick: (item) => router.visit(route('admin.policies.show', item.slug)),
        },
        {
            key: 'edit',
            label: 'Edit',
            icon: <Edit3 size={14} />,
            onClick: (item) => router.visit(route('admin.policies.edit', item.slug)),
        },
        {
            key: 'toggle',
            label: 'Toggle Publish',
            icon: <ToggleRight size={14} />,
            onClick: (item) => handleTogglePublish(item),
        },
        {
            key: 'delete',
            label: 'Delete',
            icon: <Trash2 size={14} />,
            variant: 'delete',
            onClick: (item) => handleDelete(item),
        },
    ];

    const publishedCount = policies.filter(p => p.is_published).length;
    const draftCount = policies.filter(p => !p.is_published).length;

    const bulkActions = [
        {
            label: 'Publish',
            icon: <ToggleRight size={16} />,
            variant: 'success',
            onClick: async () => {
                for (const id of selectedIds) {
                    const policy = policies.find(p => p.id === id);
                    if (policy && !policy.is_published) {
                        await new Promise((resolve, reject) => {
                            router.post(route('admin.policies.toggle', policy.slug), {}, {
                                preserveScroll: true,
                                onSuccess: resolve,
                                onError: reject,
                            });
                        });
                    }
                }
                notify.success(`${selectedIds.length} policy(ies) published`);
                setSelectedIds([]);
            },
        },
        {
            label: 'Delete',
            icon: <Trash2 size={16} />,
            variant: 'danger',
            onClick: async () => {
                const result = await confirm({
                    title: 'Delete Policies',
                    message: `Delete ${selectedIds.length} policy(ies)?`,
                    confirmLabel: 'Delete',
                    variant: 'danger',
                });
                if (result) {
                    for (const id of selectedIds) {
                        const policy = policies.find(p => p.id === id);
                        if (policy) {
                            await new Promise((resolve, reject) => {
                                router.delete(route('admin.policies.destroy', policy.slug), {
                                    preserveScroll: true,
                                    onSuccess: resolve,
                                    onError: reject,
                                });
                            });
                        }
                    }
                    notify.success(`${selectedIds.length} policy(ies) deleted`);
                    setSelectedIds([]);
                }
            },
        },
    ];

    return (
        <PageShell
            title="Policies & Terms"
            breadcrumbs={[{ label: 'Policies', href: safeRoute('admin.policies.index') }]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Total', value: policies.length },
                { label: 'Published', value: publishedCount },
                { label: 'Drafts', value: draftCount },
            ]}
            actions={[
                { label: 'New Policy', onClick: () => router.visit(route('admin.policies.create')), variant: 'black', icon: <Plus size={16} /> },
            ]}
        >
            <Head title="Policies & Terms" />
            <GlassCard padding="p-0 overflow-hidden">
                <ResponsiveTable
                    data={policies}
                    columns={columns}
                    actions={tableActions}
                    primaryField="title"
                    subtitleField={(item) => typeLabels[item.type]}
                    detailTitle="Policy Details"
                    emptyMessage="No policies created yet"
                    enableSelection
                    selectedIds={selectedIds}
                    onSelectionChange={setSelectedIds}
                    searchPlaceholder="Search policies..."
                />
            </GlassCard>

            <BulkActions
                selectedCount={selectedIds.length}
                onClearSelection={() => setSelectedIds([])}
                actions={bulkActions}
            />
            {ConfirmDialogEl}
        </PageShell>
    );
}
