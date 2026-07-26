import React from 'react';
import PageShell from '@/Layouts/PageShell';
import { Construction } from 'lucide-react';
import './Placeholder.css';

export default function Placeholder({ title = 'Coming Soon' }) {
    return (
        <PageShell title={title} hideHero>
            <div className="placeholder-page">
                <div className="placeholder-page__icon-wrapper">
                    <Construction size={48} className="placeholder-page__icon" />
                </div>
                <h1 className="placeholder-page__title">{title}</h1>
                <p className="placeholder-page__message">This feature is currently under development. Check back soon for updates!</p>
            </div>
        </PageShell>
    );
}
