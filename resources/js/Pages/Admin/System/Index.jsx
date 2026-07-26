import React from 'react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import { PageGrid } from '@/Layouts/LayoutPrimitives';
import { Activity, Server, Database, Clock } from 'lucide-react';
import './Index.css';

export default function Index({ phpVersion, laravelVersion, dbSize, serverTime }) {
    return (
        <PageShell
            title="System Status"
            subtitle="Monitor server health and application metrics"
            breadcrumbs={[{ label: 'System', href: route('admin.system.index') }]}
            rootRoute="admin.dashboard"
        >
            <PageGrid cols={4} gap="gap-6" className="system-page__grid">
                <GlassCard padding="p-6">
                    <div className="system-page__stat-card">
                        <div className="system-page__stat-icon system-page__stat-icon--php">
                            <Server size={24} />
                        </div>
                        <div>
                            <p className="system-page__stat-label">PHP Version</p>
                            <p className="system-page__stat-value">{phpVersion}</p>
                        </div>
                    </div>
                </GlassCard>
                <GlassCard padding="p-6">
                    <div className="system-page__stat-card">
                        <div className="system-page__stat-icon system-page__stat-icon--laravel">
                            <Activity size={24} />
                        </div>
                        <div>
                            <p className="system-page__stat-label">Laravel</p>
                            <p className="system-page__stat-value">v{laravelVersion}</p>
                        </div>
                    </div>
                </GlassCard>
                <GlassCard padding="p-6">
                    <div className="system-page__stat-card">
                        <div className="system-page__stat-icon system-page__stat-icon--database">
                            <Database size={24} />
                        </div>
                        <div>
                            <p className="system-page__stat-label">Database</p>
                            <p className="system-page__stat-value">{dbSize}</p>
                        </div>
                    </div>
                </GlassCard>
                <GlassCard padding="p-6">
                    <div className="system-page__stat-card">
                        <div className="system-page__stat-icon system-page__stat-icon--server-time">
                            <Clock size={24} />
                        </div>
                        <div>
                            <p className="system-page__stat-label">Server Time</p>
                            <p className="system-page__stat-value--small">{serverTime}</p>
                        </div>
                    </div>
                </GlassCard>
            </PageGrid>

            <GlassCard padding="p-8">
                <h3 className="system-page__logs-title">Application Logs</h3>
                <div className="system-page__logs">
                    <p className="system-page__logs-command">root@server:~# tail -f laravel.log</p>
                    <p className="system-page__logs-line mt-2">[2026-02-08 10:00:01] local.INFO: System check initialized.</p>
                    <p className="system-page__logs-line">[2026-02-08 10:05:23] local.INFO: User login successful (ID: 1).</p>
                    <p className="system-page__logs-line">[2026-02-08 10:15:00] local.INFO: Scheduled task completed.</p>
                    {/* Mock logs for now */}
                </div>
            </GlassCard>
        </PageShell>
    );
}
