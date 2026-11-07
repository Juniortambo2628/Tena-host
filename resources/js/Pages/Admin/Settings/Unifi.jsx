import React, { useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Wifi, Plug, CheckCircle2, AlertCircle, Circle, Loader2, Copy, RefreshCw, Radio, Activity, ScrollText, Settings2, ShieldCheck, Router,
} from 'lucide-react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import { FormField, TextInput, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import './Unifi.css';

const ago = (iso) => {
    if (!iso) return 'never';
    const s = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return `${Math.round(s / 60)} min ago`;
    if (s < 86400) return `${Math.round(s / 3600)} h ago`;
    return new Date(iso).toLocaleString();
};

const AP_LABEL = { online: 'Online', offline: 'Offline', never_seen: 'Not seen yet', unknown: 'Unknown' };
const AP_COLORS = { online: 'bg-green-100 text-green-700', offline: 'bg-red-100 text-red-700', 'not seen yet': 'bg-black/5 text-muted', unknown: 'bg-black/5 text-muted' };
const LEVEL_COLORS = { info: 'bg-black/5 text-dark', warning: 'bg-amber-100 text-amber-700', error: 'bg-red-100 text-red-700' };

/**
 * Admin -> WiFi Portal: connect the UniFi controller step by step (with a
 * test that says what to fix), watch its health and access points, and read
 * the WiFi log.
 */
export default function Unifi({ settings, portal_url, portal_host, status, accessPoints = [], events = [], eventTypes = {} }) {
    const [view, setView] = useState(status.configured ? 'monitor' : 'setup');
    const errors = events.filter((e) => e.level === 'error').length;

    return (
        <PageShell
            title="WiFi Portal"
            headTitle="WiFi Portal"
            breadcrumbs={[{ label: 'WiFi Portal' }]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Controller', value: status.configured ? (status.lastTest?.ok === false ? 'Needs attention' : 'Connected') : 'Not set up' },
                { label: 'Access points online', value: `${status.aps.online} of ${status.aps.total}` },
                { label: 'Guests connected (24h)', value: status.authorized24h },
                { label: 'Failed sign-ins (24h)', value: status.refused24h },
            ]}
        >
            <Head title="WiFi Portal" />
            <ViewSwitcher
                label="WiFi Portal view"
                value={view}
                onChange={setView}
                views={[
                    { id: 'setup', label: 'Connection', icon: <Settings2 size={16} /> },
                    { id: 'monitor', label: 'Monitoring', icon: <Activity size={16} /> },
                    { id: 'logs', label: errors ? `Logs (${errors} errors)` : 'Logs', icon: <ScrollText size={16} /> },
                ]}
            />
            {view === 'setup' && <Setup settings={settings} status={status} portalUrl={portal_url} portalHost={portal_host} accessPoints={accessPoints} />}
            {view === 'monitor' && <Monitor status={status} accessPoints={accessPoints} onShowLogs={() => setView('logs')} />}
            {view === 'logs' && <Logs events={events} eventTypes={eventTypes} />}
        </PageShell>
    );
}

/** A copyable value (portal URL, allowlist host). */
function CopyValue({ value }) {
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            notify.success('Copied');
        } catch {
            notify.error('Couldn’t copy. Select it and copy by hand.');
        }
    };
    return (
        <span className="wifi-admin__copy">
            <code>{value}</code>
            <button type="button" onClick={copy} aria-label={`Copy ${value}`}><Copy size={14} /></button>
        </span>
    );
}

function StepIcon({ state }) {
    if (state === 'done') return <CheckCircle2 size={20} className="wifi-admin__step-ic wifi-admin__step-ic--done" aria-label="Done" />;
    if (state === 'fail') return <AlertCircle size={20} className="wifi-admin__step-ic wifi-admin__step-ic--fail" aria-label="Needs attention" />;
    if (state === 'busy') return <Loader2 size={20} className="wifi-admin__step-ic animate-spin" aria-label="Checking" />;
    return <Circle size={20} className="wifi-admin__step-ic wifi-admin__step-ic--todo" aria-label="To do" />;
}

function Setup({ settings, status, portalUrl, portalHost, accessPoints }) {
    const { data, setData, post, processing, errors } = useForm({
        mode: settings.mode || 'local',
        api_key: '',
        console_id: settings.console_id || '',
        base_url: settings.base_url || '',
        username: settings.username || '',
        password: '',
        site: settings.site || 'default',
        is_os: settings.is_os ?? true,
        verify_ssl: settings.verify_ssl ?? false,
        auth_minutes: settings.auth_minutes ?? 1440,
        timeout: settings.timeout ?? 10,
    });
    const [testing, setTesting] = useState(false);
    const [result, setResult] = useState(null);

    const save = () => post(route('admin.wifi.update'), {
        preserveScroll: true,
        onSuccess: () => notify.success('Settings saved. Run the test to check them.'),
        onError: () => notify.error('Couldn’t save. Check the highlighted fields.'),
    });

    const test = async () => {
        setTesting(true);
        setResult(null);
        try {
            const { data: r } = await window.axios.post(route('admin.wifi.test'), {
                mode: data.mode, api_key: data.api_key, console_id: data.console_id,
                base_url: data.base_url, username: data.username, password: data.password, site: data.site,
                is_os: data.is_os, verify_ssl: data.verify_ssl, timeout: data.timeout,
            });
            setResult(r);
            r.ok ? notify.success('Connected to the controller') : notify.error('The test found a problem');
            router.reload({ only: ['status'], preserveScroll: true });
        } catch (e) {
            setResult({ ok: false, message: e?.response?.data?.message || 'The test couldn’t run.', steps: [] });
        } finally {
            setTesting(false);
        }
    };

    const tested = result ?? (status.lastTest ? { ok: status.lastTest.ok, message: status.lastTest.message, steps: [] } : null);
    const connected = tested?.ok === true;
    const seen = accessPoints.some((ap) => ap.status !== 'never_seen');
    const checklist = [
        { title: 'Enter the controller details', state: (settings.mode === 'cloud' ? settings.has_api_key : settings.base_url && settings.username && settings.has_password) ? 'done' : 'todo' },
        { title: 'Test the connection', state: testing ? 'busy' : tested ? (connected ? 'done' : 'fail') : 'todo' },
        { title: 'Point the guest network at TenaFi', state: connected ? 'done' : 'todo', note: 'Done on the controller; TenaFi can’t check it for you.' },
        { title: 'Register access points on each site', state: accessPoints.length ? 'done' : 'todo' },
        { title: 'See an access point come online', state: seen ? 'done' : 'todo' },
    ];

    return (
        <div className="wifi-admin__grid">
            <div className="wifi-admin__main">
                <GlassCard padding="p-6 sm:p-8">
                    <div className="wifi-admin__head">
                        <span className="wifi-admin__head-ic"><Wifi size={22} /></span>
                        <div>
                            <h3 className="wifi-admin__title">1 · UniFi controller</h3>
                            <p className="wifi-admin__subtitle">Guests tap “Connect” on the WiFi page and TenaFi lets their device online through this controller.</p>
                        </div>
                    </div>
                    <form onSubmit={(e) => { e.preventDefault(); save(); }} className="wifi-admin__form">
                        <FormField label="How TenaFi reaches the controller">
                            <Select id="unifi-mode" value={data.mode} onChange={(e) => setData('mode', e.target.value)}>
                                <option value="local">Directly (address and admin login)</option>
                                <option value="cloud">Through UniFi’s cloud (API key)</option>
                            </Select>
                        </FormField>
                        {data.mode === 'cloud' ? (
                            <>
                                <p className="wifi-admin__hint">For a console TenaFi can’t reach from the internet, such as one behind the internet provider’s router. Make the key at unifi.ui.com → Settings → API Keys.</p>
                                <div className="wifi-admin__row">
                                    <FormField label="API key" error={errors.api_key}>
                                        <TextInput id="unifi-api-key" type="password" value={data.api_key} onChange={(e) => setData('api_key', e.target.value)} autoComplete="new-password"
                                            placeholder={settings.has_api_key ? '•••••••• (blank keeps the saved one)' : 'Paste the API key'} />
                                    </FormField>
                                    <FormField label="Console ID" hint="Blank if the key reaches one console. Otherwise the part after /consoles/ in unifi.ui.com’s address.">
                                        <TextInput id="unifi-console" value={data.console_id} onChange={(e) => setData('console_id', e.target.value)} autoComplete="off" placeholder="Found automatically" />
                                    </FormField>
                                </div>
                            </>
                        ) : (
                            <>
                            <FormField label="Controller address" hint="Including https:// and the port if it isn’t 443, e.g. https://unifi.example.com:8443">
                                <TextInput id="unifi-url" value={data.base_url} onChange={(e) => setData('base_url', e.target.value)} placeholder="https://controller.example.com" />
                            </FormField>
                            <FormField label="Controller type">
                                <Select id="unifi-type" value={data.is_os ? 'os' : 'classic'} onChange={(e) => setData('is_os', e.target.value === 'os')}>
                                    <option value="os">UniFi OS: Dream Machine, Cloud Gateway, Cloud Key Gen2+</option>
                                    <option value="classic">Classic self-hosted Network application (port 8443)</option>
                                </Select>
                            </FormField>
                            <div className="wifi-admin__row">
                                <FormField label="Admin username" hint="A local admin without two-factor sign-in.">
                                    <TextInput id="unifi-user" value={data.username} onChange={(e) => setData('username', e.target.value)} autoComplete="off" />
                                </FormField>
                                <FormField label="Admin password">
                                    <TextInput id="unifi-pass" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} autoComplete="new-password"
                                        placeholder={settings.has_password ? '•••••••• (blank keeps the saved one)' : 'Controller admin password'} />
                                </FormField>
                            </div>
                            </>
                        )}
                        <div className="wifi-admin__row">
                            <FormField label="Site ID" hint={data.mode === 'cloud' ? '“default”, or the site’s name as shown in UniFi.' : 'From the controller’s address bar: …/site/<id>/…'}>
                                <TextInput id="unifi-site" value={data.site} onChange={(e) => setData('site', e.target.value)} placeholder="default" />
                            </FormField>
                            <FormField label="Guests stay online for (minutes)" hint="1440 = a day.">
                                <TextInput id="unifi-minutes" type="number" value={data.auth_minutes} onChange={(e) => setData('auth_minutes', e.target.value)} />
                            </FormField>
                        </div>
                        <details className="wifi-admin__advanced">
                            <summary>Advanced</summary>
                            <div className="wifi-admin__form">
                                <FormField label="Wait for the controller (seconds)">
                                    <TextInput id="unifi-timeout" type="number" value={data.timeout} onChange={(e) => setData('timeout', e.target.value)} />
                                </FormField>
                                {data.mode !== 'cloud' && (
                                    <CheckboxField label="Verify the controller’s SSL certificate (leave off for a self-signed controller)" checked={data.verify_ssl} onChange={() => setData('verify_ssl', !data.verify_ssl)} />
                                )}
                            </div>
                        </details>
                        <FormActions>
                            <PillButton variant="secondary" onClick={test} disabled={testing} icon={testing ? <Loader2 size={16} className="animate-spin" /> : <Plug size={16} />}>
                                {testing ? 'Testing…' : 'Test connection'}
                            </PillButton>
                            <PillButton variant="primary" type="submit" disabled={processing}>{processing ? 'Saving…' : 'Save settings'}</PillButton>
                        </FormActions>
                        <p className="wifi-admin__hint">The test uses what’s in the form, saved or not. Save once it passes.</p>
                    </form>

                    {(testing || result) && <TestResult testing={testing} result={result} />}
                </GlassCard>

                <GlassCard padding="p-6 sm:p-8">
                    <div className="wifi-admin__head">
                        <span className="wifi-admin__head-ic"><Router size={22} /></span>
                        <div>
                            <h3 className="wifi-admin__title">2 · On the controller</h3>
                            <p className="wifi-admin__subtitle">In UniFi Network → Settings → Hotspot (or WiFi → your guest network → Hotspot Portal):</p>
                        </div>
                    </div>
                    <ol className="wifi-admin__howto">
                        <li>Turn on the <b>Hotspot / guest portal</b> for the guest network.</li>
                        <li>Choose <b>External portal server</b> and enter <CopyValue value={portalUrl} /></li>
                        <li>Under <b>Pre-authorization access</b> (walled garden), allow <CopyValue value={portalHost} /> so guests can open the WiFi page before they’re online.</li>
                        <li>Turn on HTTPS redirection if offered, and save.</li>
                        <li>For a site with a separate staff network, leave that network without the portal, or name it under the site’s Staff WiFi.</li>
                    </ol>
                </GlassCard>
            </div>

            <aside className="wifi-admin__side">
                <GlassCard padding="p-6">
                    <h3 className="wifi-admin__side-title">Setup checklist</h3>
                    <ul className="wifi-admin__steps">
                        {checklist.map((step) => (
                            <li key={step.title}>
                                <StepIcon state={step.state} />
                                <span><b>{step.title}</b>{step.note && <small>{step.note}</small>}</span>
                            </li>
                        ))}
                    </ul>
                    {status.lastTest && (
                        <p className={`wifi-admin__last ${status.lastTest.ok ? 'is-ok' : 'is-fail'}`}>
                            Last test {ago(status.lastTest.at)}: {status.lastTest.message}
                        </p>
                    )}
                </GlassCard>
                <GlassCard padding="p-6">
                    <h3 className="wifi-admin__side-title"><ShieldCheck size={16} /> Good to know</h3>
                    <ul className="wifi-admin__tips">
                        <li>The password and API key are stored encrypted and never shown again.</li>
                        <li>Behind the internet provider’s router (“Upstream NAT detected” in UniFi)? Connect through UniFi’s cloud: no port forwarding needed.</li>
                        <li>Access points are registered by hosts (or you) under each site’s WiFi Access, using the AP’s MAC address.</li>
                        <li>TenaFi checks every access point every 5 minutes and tells the host when one goes offline.</li>
                    </ul>
                </GlassCard>
            </aside>
        </div>
    );
}

function TestResult({ testing, result }) {
    const steps = result?.steps ?? [];
    return (
        <div className={`wifi-admin__result ${testing ? '' : result.ok ? 'is-ok' : 'is-fail'}`} role="status" aria-live="polite">
            <p className="wifi-admin__result-title">
                {testing ? <><Loader2 size={18} className="animate-spin" /> Testing the connection…</>
                    : result.ok ? <><CheckCircle2 size={18} /> {result.message}</> : <><AlertCircle size={18} /> {result.message}</>}
            </p>
            {steps.length > 0 && (
                <ol className="wifi-admin__checks">
                    {steps.map((s) => (
                        <li key={s.key} className={s.ok === true ? 'is-ok' : s.ok === false ? 'is-fail' : 'is-skip'}>
                            <StepIcon state={s.ok === true ? 'done' : s.ok === false ? 'fail' : 'todo'} />
                            <span>
                                <b>{s.label}</b>
                                <small>{s.ok === null ? 'Not checked (an earlier step failed).' : s.detail}</small>
                                {s.hint && <em>What to do: {s.hint}</em>}
                            </span>
                        </li>
                    ))}
                </ol>
            )}
            {!testing && result.duration_ms != null && <p className="wifi-admin__hint">Took {(result.duration_ms / 1000).toFixed(1)} s.</p>}
        </div>
    );
}

function Monitor({ status, accessPoints, onShowLogs }) {
    const [syncing, setSyncing] = useState(false);
    const sync = () => router.post(route('admin.wifi.sync'), {}, {
        preserveScroll: true,
        onStart: () => setSyncing(true),
        onFinish: () => setSyncing(false),
    });
    const total = status.authorized24h + status.refused24h;
    const rate = total ? Math.round((status.authorized24h / total) * 100) : null;

    const columns = [
        {
            id: 'name',
            header: 'Access point',
            accessorFn: (ap) => ap.name,
            cell: ({ row: { original: ap } }) => <span className="wifi-admin__ap"><b>{ap.name}</b><small>{ap.mac}</small></span>,
        },
        { id: 'property', header: 'Site', accessorFn: (ap) => ap.property || '—' },
        { id: 'status', header: 'Status', accessorFn: (ap) => ap.status, cell: ({ row: { original: ap } }) => <StatusBadge status={AP_LABEL[ap.status] || ap.status} colors={AP_COLORS} /> },
        { id: 'clients', header: 'Devices on it', accessorFn: (ap) => ap.clients },
        { id: 'seen', header: 'Last seen', accessorFn: (ap) => ap.last_seen || '', cell: ({ row: { original: ap } }) => ago(ap.last_seen) },
    ];

    return (
        <>
            <div className="wifi-admin__tiles">
                <Tile icon={<Plug size={18} />} label="Controller" tone={!status.configured ? 'muted' : status.lastTest?.ok === false ? 'bad' : 'good'}
                    value={!status.configured ? 'Not set up' : status.lastTest?.ok === false ? 'Last test failed' : 'Connected'}
                    note={status.lastTest ? `Tested ${ago(status.lastTest.at)}` : 'Not tested yet'} />
                <Tile icon={<RefreshCw size={18} />} label="Access point check" tone={status.lastSyncAt ? 'good' : 'muted'}
                    value={status.lastSyncAt ? ago(status.lastSyncAt) : 'Not run yet'} note="Runs every 5 minutes" />
                <Tile icon={<Radio size={18} />} label="Access points" tone={status.aps.offline ? 'bad' : status.aps.online ? 'good' : 'muted'}
                    value={`${status.aps.online} online · ${status.aps.offline} offline`} note={status.aps.neverSeen ? `${status.aps.neverSeen} not seen yet` : `${status.aps.total} registered`} />
                <Tile icon={<Wifi size={18} />} label="Guest sign-ins (24h)" tone={status.refused24h > status.authorized24h ? 'bad' : total ? 'good' : 'muted'}
                    value={rate === null ? 'None yet' : `${rate}% connected`} note={`${status.authorized24h} connected · ${status.refused24h} failed`} />
            </div>

            {status.lastError && (
                <div className="wifi-admin__alert" role="alert">
                    <AlertCircle size={18} />
                    <span><b>Latest problem ({ago(status.lastError.created_at)}):</b> {status.lastError.message}</span>
                    <PillButton variant="ghost" onClick={onShowLogs}>See logs</PillButton>
                </div>
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <div className="wifi-admin__toolbar">
                    <div>
                        <h3>Access points</h3>
                        <p>Status from the controller, refreshed every 5 minutes.</p>
                    </div>
                    <PillButton variant="secondary" onClick={sync} disabled={syncing || !status.configured} icon={<RefreshCw size={14} className={syncing ? 'animate-spin' : ''} />}>
                        {syncing ? 'Checking…' : 'Check now'}
                    </PillButton>
                </div>
                <DataTable
                    data={accessPoints}
                    columns={columns}
                    searchPlaceholder="Search access points or sites..."
                    emptyIcon={Radio}
                    emptyMessage="No access points registered yet"
                />
            </GlassCard>
        </>
    );
}

function Tile({ icon, label, value, note, tone }) {
    return (
        <GlassCard padding="p-5">
            <div className={`wifi-admin__tile wifi-admin__tile--${tone}`}>
                <span className="wifi-admin__tile-ic">{icon}</span>
                <span className="wifi-admin__tile-label">{label}</span>
                <b>{value}</b>
                <small>{note}</small>
            </div>
        </GlassCard>
    );
}

function Logs({ events, eventTypes }) {
    const [filter, setFilter] = useState('all');
    const shown = useMemo(() => (filter === 'all' ? events : filter === 'problems' ? events.filter((e) => e.level !== 'info') : events.filter((e) => e.type === filter)), [events, filter]);
    const columns = [
        { id: 'at', header: 'When', accessorFn: (e) => e.at, cell: ({ row: { original: e } }) => <span title={new Date(e.at).toLocaleString()}>{ago(e.at)}</span> },
        { id: 'level', header: 'Level', accessorFn: (e) => e.level, cell: ({ row: { original: e } }) => <StatusBadge status={e.level} colors={LEVEL_COLORS} /> },
        { id: 'label', header: 'What', accessorFn: (e) => e.label },
        { id: 'message', header: 'Details', accessorFn: (e) => e.message, cell: ({ row: { original: e } }) => <span className="wifi-admin__msg">{e.message}</span> },
        { id: 'property', header: 'Site', accessorFn: (e) => e.property || '—' },
        { id: 'device', header: 'Device', accessorFn: (e) => e.device || '—', meta: { detailOnly: true } },
        { id: 'time', header: 'Exact time', accessorFn: (e) => new Date(e.at).toLocaleString(), meta: { detailOnly: true } },
    ];

    return (
        <GlassCard padding="p-0 overflow-hidden">
            <div className="wifi-admin__toolbar">
                <div>
                    <h3>WiFi log</h3>
                    <p>Tests, guest sign-ins, controller errors and access points going up or down.</p>
                </div>
                <div className="wifi-admin__log-actions">
                    <select aria-label="Filter the log" value={filter} onChange={(e) => setFilter(e.target.value)}>
                        <option value="all">Everything</option>
                        <option value="problems">Problems only</option>
                        {Object.entries(eventTypes).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                    </select>
                    <PillButton variant="ghost" onClick={() => router.reload({ only: ['events', 'status'], preserveScroll: true })} icon={<RefreshCw size={14} />}>Refresh</PillButton>
                </div>
            </div>
            <DataTable
                data={shown}
                columns={columns}
                searchPlaceholder="Search the log..."
                detailTitle="Log entry"
                emptyIcon={ScrollText}
                emptyMessage={events.length ? 'Nothing matches this filter' : 'Nothing logged yet. Tests, guest sign-ins and access point changes appear here.'}
            />
            <p className="wifi-admin__hint wifi-admin__hint--pad">Kept for 30 days. Shows the latest 300 entries.</p>
        </GlassCard>
    );
}
