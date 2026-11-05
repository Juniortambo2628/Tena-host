import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, MapPin, Plus, Star, Cake, MailCheck, Wifi } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import GlassCard from '@/Components/Dashboard/GlassCard';
import ChartCard from '@/Components/Dashboard/ChartCard';
import DataTable from '@/Components/Dashboard/DataTable';
import EmptyState from '@/Components/Dashboard/EmptyState';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import { useTerms } from '@/Components/Dashboard/Terms';
import { BRAND_COLORS } from '@/constants';
import './BusinessHome.css';

const percent = (value) => (value == null ? '—' : `${value}%`);
const NETWORK = { online: 'Online', partial: 'Partial', offline: 'Offline', none: 'No access point' };
const NETWORK_COLORS = { online: 'bg-green-100 text-green-700', partial: 'bg-amber-100 text-amber-700', offline: 'bg-red-100 text-red-700', 'no access point': 'bg-black/5 text-black/40' };

/**
 * The home of a business (or events) account: customers, how many come
 * back, Google reviews and each site side by side. Data from
 * App\Services\BusinessInsights; words from the account (customers and
 * locations, or attendees and venues).
 */
export default function BusinessHome({ data, userName }) {
    const t = useTerms();
    const { auth } = usePage().props;
    const isOwner = !auth.team?.member;
    const { summary, sites, regions, busyDays, busyHours } = data;
    const [view, setView] = useState('overview');

    const heroStats = [
        { label: t('Guests'), value: summary.customers },
        { label: 'New this month', value: summary.newThisMonth },
        { label: 'Returning this month', value: summary.returningThisMonth },
        { label: 'Repeat visitors', value: percent(summary.repeatRate) },
    ];

    const views = [
        { id: 'overview', label: 'Overview', icon: <Activity size={14} /> },
        { id: 'sites', label: `Properties (${summary.sites})`, icon: <MapPin size={14} /> },
    ];

    const tiles = [
        { icon: Star, label: 'Google review requests this month', value: summary.reviewRequests, detail: `${summary.reviewClicks} opened the review link` },
        { icon: Cake, label: 'Birthday treats sent this year', value: summary.birthdayTreats },
        { icon: MailCheck, label: t('Guests who opted in to offers'), value: percent(summary.optInRate), detail: t('of guests who connected') },
        { icon: Wifi, label: 'Access points online', value: summary.accessPointsOnline, detail: `${summary.visits.toLocaleString()} WiFi visits in total` },
    ];

    const siteColumns = [
        {
            id: 'name',
            header: t('Property'),
            accessorFn: (s) => s.name,
            cell: ({ row: { original: s } }) => (
                <Link href={route('host.properties.show', s.id)} className="business-home__site">
                    <b>{s.name}</b>
                    {s.region && <small>{s.region}</small>}
                </Link>
            ),
        },
        { id: 'customers', header: t('Guests'), accessorFn: (s) => s.customers },
        { id: 'new', header: 'New this month', accessorFn: (s) => s.newThisMonth },
        { id: 'repeat', header: 'Repeat visitors', accessorFn: (s) => s.repeatRate ?? -1, cell: ({ row: { original: s } }) => percent(s.repeatRate) },
        { id: 'reviews', header: 'Review requests', accessorFn: (s) => s.reviewRequests, cell: ({ row: { original: s } }) => `${s.reviewRequests} · ${s.reviewClicks} opened` },
        {
            id: 'network',
            header: 'Network',
            accessorFn: (s) => s.network,
            cell: ({ row: { original: s } }) => <StatusBadge status={NETWORK[s.network]} colors={NETWORK_COLORS} />,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <DashboardHero
                title={`Welcome back, ${userName}`}
                breadcrumbs={[{ label: 'Overview' }]}
                actions={isOwner ? [{ label: t('Add Property'), variant: 'primary', icon: <Plus size={16} />, href: route('host.properties.index') }] : []}
                stats={heroStats}
            />

            {summary.sites === 0 ? (
                <GlassCard>
                    <EmptyState
                        icon={MapPin}
                        message={t('No properties yet')}
                        hint={t('Add your first property and its access point to start collecting guests.')}
                        action={isOwner ? { label: t('Add Property'), href: route('host.properties.index'), icon: <Plus size={16} /> } : null}
                    />
                </GlassCard>
            ) : (
                <>
                    <ViewSwitcher views={views} value={view} onChange={setView} />

                    {view === 'overview' && (
                        <div className="business-home">
                            <div className="business-home__tiles">
                                {tiles.map(({ icon: Icon, label, value, detail }) => (
                                    <GlassCard key={label} padding="p-6">
                                        <div className="business-home__tile">
                                            <span className="business-home__tile-icon"><Icon size={18} aria-hidden="true" /></span>
                                            <b>{value}</b>
                                            <span>{label}</span>
                                            {detail && <small>{detail}</small>}
                                        </div>
                                    </GlassCard>
                                ))}
                            </div>
                            <div className="business-home__charts">
                                <ChartCard data={busyDays} type="bar" dataKeys={['customers']} colors={[BRAND_COLORS.primary]} title="Busiest days" subtitle={t('When guests last connected, past 90 days')} height={260} />
                                <ChartCard data={busyHours} type="bar" dataKeys={['customers']} colors={[BRAND_COLORS.dark]} title="Busiest hours" subtitle={t('When guests last connected, past 90 days')} height={260} />
                            </div>
                        </div>
                    )}

                    {view === 'sites' && (
                        <div className="business-home">
                            {regions.length > 0 && (
                                <div className="business-home__regions">
                                    {regions.map((r) => (
                                        <GlassCard key={r.region} padding="p-5">
                                            <div className="business-home__region">
                                                <b>{r.region}</b>
                                                <span>{r.sites} {t(r.sites === 1 ? 'property' : 'properties')} · {r.customers.toLocaleString()} {t('guests')}</span>
                                                <small>{r.newThisMonth} new this month · {r.reviewRequests} review requests</small>
                                            </div>
                                        </GlassCard>
                                    ))}
                                </div>
                            )}
                            <GlassCard padding="p-0 overflow-hidden">
                                <DataTable data={sites} columns={siteColumns} searchPlaceholder={t('Search properties...')} emptyMessage={t('No properties yet')} />
                            </GlassCard>
                        </div>
                    )}
                </>
            )}
        </>
    );
}
