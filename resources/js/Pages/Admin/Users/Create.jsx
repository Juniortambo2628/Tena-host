import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import PillButton from '@/Components/Dashboard/PillButton';
import { ChevronLeft } from 'lucide-react';
import { FormField, TextInput, FormActions } from '@/Components/Forms/FormPrimitives';
import './Create.css';

const ROLES = [
    { value: 'host', label: 'Host', description: 'Can manage properties, guests, and marketing' },
    { value: 'admin', label: 'Admin', description: 'Full platform access and management' },
    { value: 'staff', label: 'Staff', description: 'Limited access to assigned properties' },
    { value: 'guest', label: 'Guest', description: 'Basic guest portal access' },
];

export default function CreateUser() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        role: 'host',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => notify.success('User created. Invitation email sent.'),
            onError: (errs) => {
                const first = Object.values(errs)[0];
                notify.error(Array.isArray(first) ? first[0] : 'Failed to create user.');
            },
        });
    };

    const breadcrumbs = [
        { label: 'Users', href: route('admin.users.index') },
        { label: 'Create User' },
    ];

    return (
        <DashboardLayout title="Create User">
            <Head title="Create User" />

            <DashboardHero
                title="Create New User"
                breadcrumbs={breadcrumbs}
                rootRoute="admin.dashboard"
                actions={[
                    { label: 'Back', variant: 'secondary', icon: <ChevronLeft size={16} />, onClick: () => router.get(route('admin.users.index')) },
                ]}
            />

            <div className="create-user-grid">
                <GlassCard padding="p-8">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="create-user-info">
                            <p className="create-user-info-text">
                                An invitation email will be sent to the user with a link to set up their password.
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField label="First Name" error={errors.first_name} required>
                                <TextInput
                                    value={data.first_name}
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    placeholder="John"
                                    autoFocus
                                />
                            </FormField>

                            <FormField label="Last Name" error={errors.last_name} required>
                                <TextInput
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    placeholder="Doe"
                                />
                            </FormField>
                        </div>

                        <FormField label="Email Address" error={errors.email} required>
                            <TextInput
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="john@example.com"
                            />
                        </FormField>

                        <FormField label="Role" error={errors.role} required>
                            <div className="create-user-roles">
                                {ROLES.map((role) => (
                                    <label
                                        key={role.value}
                                        className={`create-user-role ${data.role === role.value ? 'create-user-role--active' : ''}`}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={role.value}
                                            checked={data.role === role.value}
                                            onChange={(e) => setData('role', e.target.value)}
                                            className="sr-only"
                                        />
                                        <div className="create-user-role-content">
                                            <span className="create-user-role-label">{role.label}</span>
                                            <span className="create-user-role-desc">{role.description}</span>
                                        </div>
                                    </label>
                                ))}
                            </div>
                        </FormField>

                        <FormActions>
                            <Link
                                href={route('admin.users.index')}
                                className="create-user-cancel"
                            >
                                Cancel
                            </Link>
                            <PillButton variant="primary" processing={processing}>
                                Create User & Send Invitation
                            </PillButton>
                        </FormActions>
                    </form>
                </GlassCard>
            </div>
        </DashboardLayout>
    );
}
