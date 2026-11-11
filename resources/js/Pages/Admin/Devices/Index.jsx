import React, { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Router, Plus, Banknote, Save } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, TextArea, Select, FormActions } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import './Index.css';

const kes = (n) => `KES ${Number(n || 0).toLocaleString()}`;
const COLORS = {
    active: 'bg-green-100 text-green-700',
    overdue: 'bg-amber-100 text-amber-700',
    suspended: 'bg-red-100 text-red-700',
    'return requested': 'bg-amber-100 text-amber-700',
    returned: 'bg-black/5 text-muted',
    'paid off': 'bg-primary/20 text-dark',
    'written off': 'bg-black/5 text-muted',
    'lost or stolen': 'bg-red-100 text-red-700',
};
const INSTALMENT = { due: 'Due', partly_paid: 'Part paid', paid: 'Paid', waived: 'Waived' };
const EVENT = { recorded: 'Recorded', payment: 'Payment', paid_off: 'Paid off', status: 'Status changed', note: 'Note', transferred: 'Moved to a new owner' };
const today = () => new Date().toISOString().slice(0, 10);

/**
 * Admin -> Devices: the device register (docs/plans/device-monthly-payments.md,
 * step 1). Every TenaFi device paid for monthly: client, site, serial number,
 * instalments, payments and status, recorded by hand. A late instalment shows
 * how many days overdue; changing the status is a separate, logged step.
 */
export default function Devices({ agreements, clients, sites, devices, statuses, enabled }) {
    const late = agreements.filter((a) => a.days_overdue > 0 && !['paid_off', 'returned', 'written_off'].includes(a.status));
    const open = agreements.filter((a) => !['paid_off', 'returned', 'written_off', 'lost_or_stolen'].includes(a.status));
    const [view, setView] = useState('open');
    const [recording, setRecording] = useState(false);
    const [openId, setOpenId] = useState(null);
    const opened = agreements.find((a) => a.id === openId) || null;

    const shown = useMemo(() => ({
        open,
        late,
        paid_off: agreements.filter((a) => a.status === 'paid_off'),
        all: agreements,
    }[view] || agreements), [agreements, view]); // eslint-disable-line react-hooks/exhaustive-deps

    const columns = [
        {
            id: 'client',
            header: 'Client',
            accessorFn: (a) => `${a.client} ${a.site || ''}`,
            cell: ({ row: { original: a } }) => <span className="devices__stack"><b>{a.client}</b><small>{a.site || 'No site yet'}</small></span>,
        },
        {
            id: 'device',
            header: 'Device',
            accessorFn: (a) => `${a.device_type} ${a.serial_number || ''}`,
            cell: ({ row: { original: a } }) => <span className="devices__stack"><b>{deviceName(devices, a.device_type)}</b><small>{a.serial_number || 'No serial'}</small></span>,
        },
        { id: 'next', header: 'Next due', accessorFn: (a) => a.next_due_on || '', cell: ({ row: { original: a } }) => a.next_due_on || '—' },
        { id: 'balance', header: 'Balance', accessorFn: (a) => a.balance_kes, cell: ({ row: { original: a } }) => kes(a.balance_kes) },
        {
            id: 'late',
            header: 'Late',
            accessorFn: (a) => a.days_overdue,
            cell: ({ row: { original: a } }) => (a.days_overdue > 0 ? <span className="devices__late">{a.days_overdue} days</span> : '—'),
        },
        { id: 'status', header: 'Status', accessorFn: (a) => a.status, cell: ({ row: { original: a } }) => <StatusBadge status={statuses[a.status]} colors={COLORS} /> },
        { id: 'contact', header: 'Contact', accessorFn: (a) => a.contact || '—', meta: { detailOnly: true } },
        { id: 'mac', header: 'MAC address', accessorFn: (a) => a.mac_address || '—', meta: { detailOnly: true } },
        { id: 'start', header: 'Installed', accessorFn: (a) => a.starts_on, meta: { detailOnly: true } },
        {
            id: 'open',
            header: '',
            enableSorting: false,
            cell: ({ row: { original: a } }) => <PillButton variant="ghost" onClick={() => setOpenId(a.id)} className="whitespace-nowrap">Open</PillButton>,
        },
    ];

    return (
        <>
            <Head title="Devices" />
            <DashboardHero
                title="Devices"
                subtitle={enabled
                    ? 'TenaFi devices paid for monthly: who has them, where, and what’s left to pay. Record payments here until M-Pesa collection is automatic.'
                    : 'The device register is switched off in Admin → Settings, so it’s hidden from the menu. Records are kept.'}
                breadcrumbs={[{ label: 'Devices' }]}
                rootRoute="admin.dashboard"
                actions={[{ label: 'Record a device', icon: <Plus size={16} />, onClick: () => setRecording(true), variant: 'primary' }]}
                stats={[
                    { label: 'Paying monthly', value: open.length },
                    { label: 'Late', value: late.length },
                    { label: 'Still to collect', value: kes(open.reduce((sum, a) => sum + a.balance_kes, 0)) },
                ]}
            />

            {agreements.length > 0 && (
                <ViewSwitcher
                    label="Devices"
                    views={[
                        { id: 'open', label: `Paying (${open.length})` },
                        { id: 'late', label: `Late (${late.length})` },
                        { id: 'paid_off', label: 'Paid off' },
                        { id: 'all', label: `All (${agreements.length})` },
                    ]}
                    value={view}
                    onChange={setView}
                />
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={shown}
                    columns={columns}
                    searchPlaceholder="Search clients, sites or serial numbers..."
                    detailTitle="Device"
                    emptyIcon={Router}
                    emptyMessage={agreements.length ? 'Nothing here' : 'No devices recorded yet'}
                    emptyAction={agreements.length ? undefined : { label: 'Record a device', onClick: () => setRecording(true) }}
                />
            </GlassCard>

            <RecordDevice isOpen={recording} onClose={() => setRecording(false)} clients={clients} sites={sites} devices={devices} />
            <AgreementDetails key={opened?.id || 'none'} agreement={opened} onClose={() => setOpenId(null)} devices={devices} statuses={statuses} />
        </>
    );
}

const deviceName = (devices, id) => devices.find((d) => d.id === id)?.name || id;

/** "Record a device": the client, site, device and its schedule (prices filled from /pricing). */
function RecordDevice({ isOpen, onClose, clients, sites, devices }) {
    const first = devices[0];
    const form = useForm({
        user_id: '', property_id: '', device_type: first?.id || '', serial_number: '', mac_address: '',
        price_kes: first?.price_kes || '', monthly_kes: first?.monthly_kes || '', months: first?.months || 12,
        starts_on: today(), notes: '',
    });
    const clientSites = sites.filter((s) => String(s.user_id) === String(form.data.user_id));

    const pickDevice = (id) => {
        const d = devices.find((x) => x.id === id);
        form.setData((data) => ({ ...data, device_type: id, ...(d ? { price_kes: d.price_kes, monthly_kes: d.monthly_kes, months: d.months } : {}) }));
    };
    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.devices.store'), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Device recorded'); form.reset(); onClose(); },
        });
    };

    return (
        <GlassModal isOpen={isOpen} onClose={onClose} title="Record a device" maxWidth="lg">
            <form onSubmit={submit} className="devices__form">
                <FormField label="Client" required error={form.errors.user_id}>
                    <Select id="device-client" value={form.data.user_id} onChange={(e) => form.setData((d) => ({ ...d, user_id: e.target.value, property_id: '' }))} required>
                        <option value="">Choose a client…</option>
                        {clients.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.email})</option>)}
                    </Select>
                </FormField>
                <FormField label="Site" hint={form.data.user_id && !clientSites.length ? 'This client has no sites yet; you can add it later.' : undefined} error={form.errors.property_id}>
                    <Select id="device-site" value={form.data.property_id} onChange={(e) => form.setData('property_id', e.target.value)}>
                        <option value="">No site yet</option>
                        {clientSites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                    </Select>
                </FormField>
                <div className="devices__row">
                    <FormField label="Device" required error={form.errors.device_type}>
                        <Select id="device-type" value={form.data.device_type} onChange={(e) => pickDevice(e.target.value)} required>
                            {devices.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            {!devices.length && <option value="">Add devices on /pricing first</option>}
                        </Select>
                    </FormField>
                    <FormField label="Installed on" required error={form.errors.starts_on}>
                        <TextInput id="device-start" type="date" value={form.data.starts_on} onChange={(e) => form.setData('starts_on', e.target.value)} required />
                    </FormField>
                </div>
                <div className="devices__row">
                    <FormField label="Serial number" error={form.errors.serial_number}>
                        <TextInput id="device-serial" value={form.data.serial_number} onChange={(e) => form.setData('serial_number', e.target.value)} />
                    </FormField>
                    <FormField label="MAC address" error={form.errors.mac_address}>
                        <TextInput id="device-mac" value={form.data.mac_address} onChange={(e) => form.setData('mac_address', e.target.value)} placeholder="aa:bb:cc:dd:ee:ff" />
                    </FormField>
                </div>
                <div className="devices__row devices__row--three">
                    <FormField label="Device price (KES)" required error={form.errors.price_kes}>
                        <TextInput id="device-price" type="number" value={form.data.price_kes} onChange={(e) => form.setData('price_kes', e.target.value)} required />
                    </FormField>
                    <FormField label="Each month (KES)" required error={form.errors.monthly_kes}>
                        <TextInput id="device-monthly" type="number" value={form.data.monthly_kes} onChange={(e) => form.setData('monthly_kes', e.target.value)} required />
                    </FormField>
                    <FormField label="Months" required error={form.errors.months}>
                        <TextInput id="device-months" type="number" value={form.data.months} onChange={(e) => form.setData('months', e.target.value)} required />
                    </FormField>
                </div>
                <FormField label="Notes" hint="Anything useful later: where it's mounted, who signed, the asset sticker number." error={form.errors.notes}>
                    <TextArea id="device-notes" rows={3} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                </FormField>
                <FormActions>
                    <PillButton variant="secondary" onClick={onClose}>Cancel</PillButton>
                    <PillButton variant="primary" type="submit" disabled={form.processing} icon={<Save size={16} />}>Record device</PillButton>
                </FormActions>
            </form>
        </GlassModal>
    );
}

/** One device: its instalments (record a payment), status (with a note) and log. */
function AgreementDetails({ agreement: a, onClose, devices, statuses }) {
    const [paying, setPaying] = useState(null);
    const pay = useForm({ amount_kes: '', reference: '' });
    const status = useForm({ status: a?.status || 'active', note: '' });

    const startPaying = (i) => { pay.setData({ amount_kes: i.remaining_kes, reference: '' }); pay.clearErrors(); setPaying(i); };
    const submitPay = (e) => {
        e.preventDefault();
        pay.post(route('admin.devices.pay', [a.id, paying.id]), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Payment recorded'); setPaying(null); },
        });
    };
    const submitStatus = (e) => {
        e.preventDefault();
        status.put(route('admin.devices.status', a.id), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Status changed'); status.setData('note', ''); },
        });
    };

    return (
        <GlassModal
            isOpen={!!a}
            onClose={() => { setPaying(null); onClose(); }}
            title={a ? `${deviceName(devices, a.device_type)} · ${a.client}` : 'Device'}
            maxWidth="2xl"
        >
            {a && (
                <div className="devices__details" key={a.id}>
                    <p className="devices__summary">
                        {a.site || 'No site yet'} · installed {a.starts_on} · {a.months} × {kes(a.monthly_kes)}
                        {a.serial_number && <> · serial {a.serial_number}</>}
                        <br />Still to pay: <b>{kes(a.balance_kes)}</b>{a.days_overdue > 0 && <span className="devices__late"> · {a.days_overdue} days late</span>}
                    </p>

                    <h3 className="devices__heading">Instalments</h3>
                    <ul className="devices__instalments">
                        {a.instalments.map((i) => (
                            <li key={i.id} className={`devices__instalment devices__instalment--${i.status}`}>
                                <span className="devices__stack">
                                    <b>#{i.number} · due {i.due_on}</b>
                                    <small>
                                        {kes(i.amount_kes)} · {INSTALMENT[i.status] || i.status}
                                        {i.paid_kes > 0 && i.status !== 'paid' && <> · {kes(i.paid_kes)} paid</>}
                                        {i.reference && <> · {i.reference}</>}
                                    </small>
                                </span>
                                {i.remaining_kes > 0 && (
                                    <PillButton variant={paying?.id === i.id ? 'secondary' : 'ghost'} onClick={() => startPaying(i)} className="whitespace-nowrap">Record payment</PillButton>
                                )}
                                {/* The payment form opens right under its instalment. */}
                                {paying?.id === i.id && (
                                    <form onSubmit={submitPay} className="devices__inline-form">
                                        <p className="devices__summary">Payment for instalment #{paying.number} (due {paying.due_on}, {kes(paying.remaining_kes)} left)</p>
                                        <div className="devices__row">
                                            <FormField label="Amount (KES)" required error={pay.errors.amount_kes}>
                                                <TextInput id="pay-amount" type="number" value={pay.data.amount_kes} onChange={(e) => pay.setData('amount_kes', e.target.value)} required />
                                            </FormField>
                                            <FormField label="M-Pesa code or receipt" error={pay.errors.reference}>
                                                <TextInput id="pay-reference" value={pay.data.reference} onChange={(e) => pay.setData('reference', e.target.value)} />
                                            </FormField>
                                        </div>
                                        <FormActions>
                                            <PillButton variant="secondary" onClick={() => setPaying(null)}>Cancel</PillButton>
                                            <PillButton variant="primary" type="submit" disabled={pay.processing} icon={<Banknote size={16} />}>Record payment</PillButton>
                                        </FormActions>
                                    </form>
                                )}
                            </li>
                        ))}
                    </ul>


                    <h3 className="devices__heading">Status</h3>
                    <form onSubmit={submitStatus} className="devices__inline-form">
                        <div className="devices__row">
                            <FormField label="Status" required error={status.errors.status}>
                                <Select id="device-status" value={status.data.status} onChange={(e) => status.setData('status', e.target.value)} required>
                                    {Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                </Select>
                            </FormField>
                            <FormField label="Why (kept in the log)" error={status.errors.note}>
                                <TextInput id="device-status-note" value={status.data.note} onChange={(e) => status.setData('note', e.target.value)} placeholder="e.g. Called the client, promised to pay Friday" />
                            </FormField>
                        </div>
                        <FormActions>
                            <PillButton variant="primary" type="submit" disabled={status.processing || status.data.status === a.status}>Change status</PillButton>
                        </FormActions>
                    </form>

                    {a.notes && <><h3 className="devices__heading">Notes</h3><p className="devices__summary">{a.notes}</p></>}

                    <h3 className="devices__heading">Log</h3>
                    <ul className="devices__log">
                        {a.events.map((e) => (
                            <li key={e.id}>
                                <b>{EVENT[e.type] || e.type}</b>
                                {e.type === 'status' && e.meta && <> · {statuses[e.meta.from] || e.meta.from} → {statuses[e.meta.to] || e.meta.to}</>}
                                {e.type === 'payment' && e.meta && <> · {kes(e.meta.amount_kes)} for #{e.meta.instalment}</>}
                                {e.note && <> · {e.note}</>}
                                <small>{new Date(e.at).toLocaleString()}{e.by && ` · ${e.by}`}</small>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </GlassModal>
    );
}
