import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Search, ArrowLeft, ArrowRight, MessageCircle, Mail, LifeBuoy, BookOpen, Lightbulb } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import EmptyState from '@/Components/Dashboard/EmptyState';
import { hasRoute } from '@/lib/route';
import { HELP_ARTICLES, HELP_CATEGORIES } from '@/lib/helpArticles';
import './Help.css';

const readArticle = () => {
    if (typeof window === 'undefined') return null;
    return new URLSearchParams(window.location.search).get('a');
};

/**
 * The Help Center: how-to guides for the reader's kind of account (admins
 * see everything), searchable, one guide at a time, and how to reach us.
 */
export default function Help({ support = {} }) {
    const { auth } = usePage().props;
    const role = auth.user?.role;
    const isAdmin = role === 'admin';
    const type = auth.account?.type || 'host';
    const isTeamMember = !!auth.team?.member;

    const articles = useMemo(() => HELP_ARTICLES.filter((a) => isAdmin || a.for.includes('all') || a.for.includes(type)), [isAdmin, type]);
    const [query, setQuery] = useState('');
    const [slug, setSlug] = useState(readArticle);
    const article = articles.find((a) => a.slug === slug) || null;

    // Keep ?a=<guide> in the address so a guide can be shared and Back works.
    useEffect(() => {
        const onPop = () => setSlug(readArticle());
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, []);
    const open = (next) => {
        setSlug(next);
        const url = next ? `${route('help')}?a=${next}` : route('help');
        window.history.pushState({}, '', url);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // A link is shown only where the reader can actually open it.
    const canOpen = (name) => {
        if (!hasRoute(name)) return false;
        if (name.startsWith('admin.')) return isAdmin;
        if (name.startsWith('host.')) {
            if (role !== 'host') return false;
            if (isTeamMember && /^host\.(marketing|billing|team)\./.test(name)) return false;
            if (name.startsWith('host.events.') && type !== 'event') return false;
            if (name.startsWith('host.visitors.') && !auth.visitorLog) return false;
            if (name.startsWith('host.team.') && !auth.team?.enabled) return false;
        }
        return true;
    };

    const q = query.trim().toLowerCase();
    const results = q
        ? articles.filter((a) => [a.title, a.summary, ...(a.body || []), ...(a.steps || []), ...(a.tips || [])].join(' ').toLowerCase().includes(q))
        : null;

    return (
        <>
            <Head title={article ? `${article.title} · Help` : 'Help'} />
            <DashboardHero
                title={article ? article.title : 'Help Center'}
                subtitle={article ? article.summary : 'How everything in TenaFi works, step by step.'}
                breadcrumbs={article ? [{ label: 'Help', href: route('help') }, { label: article.title }] : [{ label: 'Help' }]}
                rootRoute={isAdmin ? 'admin.dashboard' : role === 'staff' ? 'staff.dashboard' : 'host.dashboard'}
            />

            <div className="help-page">
                <aside className="help-page__side" aria-label="Help topics">
                    <label className="help-page__search">
                        <Search size={16} aria-hidden="true" />
                        <input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search help…" aria-label="Search help" />
                    </label>
                    <nav className="help-page__toc">
                        {HELP_CATEGORIES.map((cat) => {
                            const list = articles.filter((a) => a.category === cat.id);
                            if (!list.length) return null;
                            return (
                                <div key={cat.id} className="help-page__toc-group">
                                    <p>{cat.label}</p>
                                    <ul>
                                        {list.map((a) => (
                                            <li key={a.slug}>
                                                <button type="button" onClick={() => { setQuery(''); open(a.slug); }} aria-current={a.slug === slug ? 'page' : undefined}>{a.title}</button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            );
                        })}
                    </nav>
                </aside>

                <main className="help-page__main">
                    {results ? (
                        <GlassCard padding="p-6">
                            <h2 className="help-page__h2">{results.length} {results.length === 1 ? 'guide' : 'guides'} for “{query.trim()}”</h2>
                            {results.length ? <ArticleList list={results} onOpen={(s) => { setQuery(''); open(s); }} /> : (
                                <EmptyState icon={Search} message="Nothing found" hint="Try another word, or contact support below." compact />
                            )}
                        </GlassCard>
                    ) : article ? (
                        <Article article={article} articles={articles} canOpen={canOpen} support={support} onOpen={open} />
                    ) : (
                        <Home articles={articles} onOpen={open} />
                    )}
                    {!article?.contact && <SupportCard support={support} />}
                </main>
            </div>
        </>
    );
}

function Home({ articles, onOpen }) {
    return HELP_CATEGORIES.map((cat) => {
        const list = articles.filter((a) => a.category === cat.id);
        if (!list.length) return null;
        return (
            <GlassCard key={cat.id} padding="p-6">
                <h2 className="help-page__h2">{cat.label}</h2>
                <ArticleList list={list} onOpen={onOpen} />
            </GlassCard>
        );
    });
}

function ArticleList({ list, onOpen }) {
    return (
        <ul className="help-page__list">
            {list.map((a) => (
                <li key={a.slug}>
                    <button type="button" onClick={() => onOpen(a.slug)}>
                        <BookOpen size={18} aria-hidden="true" />
                        <span><b>{a.title}</b><small>{a.summary}</small></span>
                        <ArrowRight size={16} aria-hidden="true" className="help-page__list-go" />
                    </button>
                </li>
            ))}
        </ul>
    );
}

function Article({ article, articles, canOpen, support, onOpen }) {
    const links = (article.links || []).filter((l) => canOpen(l.route));
    const related = (article.related || []).map((s) => articles.find((a) => a.slug === s)).filter(Boolean);

    return (
        <>
            <GlassCard padding="p-6 sm:p-8">
                <button type="button" className="help-page__back" onClick={() => onOpen(null)}><ArrowLeft size={14} /> All guides</button>
                <article className="help-article">
                    {(article.body || []).map((p, i) => <p key={i}>{p}</p>)}
                    {article.steps?.length > 0 && (
                        <>
                            <h3>Step by step</h3>
                            <ol>{article.steps.map((s, i) => <li key={i}>{s}</li>)}</ol>
                        </>
                    )}
                    {article.tips?.length > 0 && (
                        <div className="help-article__tips">
                            <p><Lightbulb size={16} aria-hidden="true" /> Good to know</p>
                            <ul>{article.tips.map((t, i) => <li key={i}>{t}</li>)}</ul>
                        </div>
                    )}
                    {article.contact && <SupportActions support={support} />}
                </article>
                {links.length > 0 && (
                    <div className="help-article__links">
                        {links.map((l) => (
                            <Link key={l.route} href={route(l.route)} className="help-article__link" prefetch>{l.label} <ArrowRight size={14} /></Link>
                        ))}
                    </div>
                )}
            </GlassCard>
            {related.length > 0 && (
                <GlassCard padding="p-6">
                    <h2 className="help-page__h2">Related guides</h2>
                    <ArticleList list={related} onOpen={onOpen} />
                </GlassCard>
            )}
        </>
    );
}

function SupportActions({ support }) {
    const wa = support.whatsapp ? support.whatsapp.replace(/[^\d]/g, '') : '';
    return (
        <div className="help-support__actions">
            {wa && <a className="help-support__btn help-support__btn--wa" href={`https://wa.me/${wa}`} target="_blank" rel="noopener noreferrer"><MessageCircle size={16} /> WhatsApp {support.whatsapp}</a>}
            {support.email && <a className="help-support__btn" href={`mailto:${support.email}`}><Mail size={16} /> {support.email}</a>}
        </div>
    );
}

function SupportCard({ support }) {
    if (!support.email && !support.whatsapp) return null;
    return (
        <GlassCard padding="p-6">
            <div className="help-support">
                <span className="help-support__ic"><LifeBuoy size={20} /></span>
                <div>
                    <h2 className="help-page__h2">Still stuck?</h2>
                    <p>TenaFi runs the WiFi for you. Tell us the site and what you saw, and we’ll sort it out.</p>
                    <SupportActions support={support} />
                </div>
            </div>
        </GlassCard>
    );
}
