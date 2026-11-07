import React, { useState, useEffect, useCallback, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    UserCog,
    CalendarDays,
    DoorOpen,
    Wifi,
    Smartphone,
    Megaphone,
    Settings,
    AlignLeft,
    AlignJustify,
    Search,
    Bell,
    HelpCircle,
    Sun,
    Moon,
    Monitor,
    Check,
    BookOpen,
    LifeBuoy,
    User,
    LogOut,
    CheckCircle2,
    X,
    Plus,
    Building2,
    Activity,
    CreditCard,
    Globe,
    FileText,
    Home,
    Clipboard,
    Loader2,
} from 'lucide-react';
import { Menu, Transition, Dialog } from '@headlessui/react';
import { safeRoute, hasRoute } from '@/lib/route';
import './DashboardLayout.css';
import { useTerms } from '@/Components/Dashboard/Terms';
import BrandLogo from '@/Components/BrandLogo';
import { useMediaQuery } from '@/hooks/useMediaQuery';
import { useTheme } from '@/hooks/useTheme';

// Mounted once as the persistent layout of every dashboard page (see app.jsx), so
// the sidebar, header and search keep their state while only the page swaps.
export default function DashboardLayout({ children }) {
    const t = useTerms();
    const { auth } = usePage().props;
    const { component, url } = usePage();
    // Wide screens collapse the sidebar in place; narrower ones open it as a drawer.
    const isWide = useMediaQuery('(min-width: 1024px)');
    const { theme, setTheme, dark } = useTheme();
    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const sidebarRef = useRef(null);
    const userRole = auth.user.role || 'host';

    // Billing, marketing and the team belong to the account owner, not to a manager they invited.
    const isOwner = !auth.team?.member;
    const hostNavItems = [
        { name: 'Dashboard', icon: LayoutDashboard, route: 'host.dashboard' },
        auth.account?.type === 'event' && { name: 'Events', icon: CalendarDays, route: 'host.events.index', match: ['host.events.*'] },
        { name: 'Guests', icon: Users, route: 'host.guests.index', match: ['host.guests.*'] },
        auth.visitorLog && { name: 'Visitors', icon: DoorOpen, route: 'host.visitors.index' },
        { name: 'WiFi Access', icon: Wifi, route: 'host.properties.index', match: ['host.properties.*', 'host.access-points.*', 'host.amenities.*', 'host.orders.*'] },
        isOwner && { name: 'Marketing', icon: Megaphone, route: 'host.marketing.index', match: ['host.marketing.*'] },
        isOwner && auth.team?.enabled && { name: 'Team', icon: UserCog, route: 'host.team.index' },
        isOwner && { name: 'Billing', icon: CreditCard, route: 'host.billing.index' },
        { name: 'Notifications', icon: Bell, route: 'profile.notifications' },
        { name: 'Settings', icon: Settings, route: 'profile.edit' },
    ].filter(Boolean);

    const adminNavItems = [
        { name: 'Overview', icon: LayoutDashboard, route: 'admin.dashboard' },
        { name: 'Accounts', icon: Building2, route: 'admin.hosts.index', match: ['admin.hosts.*'] },
        { name: 'Users', icon: Users, route: 'admin.users.index', match: ['admin.users.*'] },
        { name: 'Sign-ups', icon: AlignLeft, route: 'admin.registrations.index', match: ['admin.registrations.*'] },
        { name: 'Payments', icon: CreditCard, route: 'admin.payments.index', match: ['admin.payments.*'] },
        auth.eventBilling && { name: 'Event quotes', icon: CalendarDays, route: 'admin.event-quotes.index' },
        { name: 'Public Pages', icon: Globe, route: 'admin.landing.index', match: ['admin.landing.*'] },
        { name: 'Policies', icon: FileText, route: 'admin.policies.index', match: ['admin.policies.*'] },
        { name: 'System', icon: Activity, route: 'admin.system.index' },
        { name: 'WiFi Portal', icon: Wifi, route: 'admin.wifi.edit' },
        { name: 'M-Pesa', icon: Smartphone, route: 'admin.mpesa.edit' },
        { name: 'Settings', icon: Settings, route: 'admin.settings.index' },
    ].filter(Boolean);

    // An item stays active on every page of its section (e.g. a guest's detail page under Guests).
    const isItemActive = (item) => (item.match ?? [item.route]).some((name) => route().current(name));

    const staffNavItems = [
        { name: 'Overview', icon: LayoutDashboard, route: 'staff.dashboard' },
        { name: 'Settings', icon: Settings, route: 'profile.edit' },
    ];

    const navItems = userRole === 'admin' ? adminNavItems : (userRole === 'staff' ? staffNavItems : hostNavItems);

    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searchLoading, setSearchLoading] = useState(false);
    const [recentSearches, setRecentSearches] = useState(() => {
        try { return JSON.parse(localStorage.getItem('tena_recent_searches') || '[]'); } catch { return []; }
    });
    const searchTimeoutRef = useRef(null);
    const searchInputRef = useRef(null);

    const iconMap = {
        user: User,
        building: Building2,
        home: Home,
        users: Users,
        clipboard: Clipboard,
        file: FileText,
        globe: Globe,
    };

    const performSearch = useCallback(async (q) => {
        if (!q || q.length < 2) {
            setSearchResults([]);
            setSearchLoading(false);
            return;
        }
        setSearchLoading(true);
        try {
            const res = await fetch(route(userRole === 'admin' ? 'admin.search' : 'host.search') + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                const data = await res.json();
                setSearchResults(data.results || []);
            }
        } catch {
            setSearchResults([]);
        } finally {
            setSearchLoading(false);
        }
    }, [userRole]);

    useEffect(() => {
        if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
        if (searchQuery.length >= 2) {
            setSearchLoading(true);
            searchTimeoutRef.current = setTimeout(() => performSearch(searchQuery), 300);
        } else {
            setSearchResults([]);
            setSearchLoading(false);
        }
        return () => { if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current); };
    }, [searchQuery, performSearch]);

    useEffect(() => {
        if (isSearchOpen && searchInputRef.current) {
            setTimeout(() => searchInputRef.current?.focus(), 100);
        }
        if (!isSearchOpen) {
            setSearchQuery('');
            setSearchResults([]);
        }
    }, [isSearchOpen]);

    const handleSearchSelect = (result) => {
        const recent = recentSearches.filter(r => !(r.id === result.id && r.type === result.type));
        recent.unshift(result);
        setRecentSearches(recent.slice(0, 5));
        localStorage.setItem('tena_recent_searches', JSON.stringify(recent.slice(0, 5)));
        setIsSearchOpen(false);
        router.get(result.url);
    };

    // Search list: results while typing, otherwise recent picks and quick actions.
    // One flat list so the arrow keys move through every row in order.
    const [activeIndex, setActiveIndex] = useState(0);
    const quickActions = (userRole === 'admin'
        ? [
            { title: 'Manage hosts', Icon: Building2, href: safeRoute('admin.hosts.index') },
            { title: 'Manage users', Icon: Users, href: safeRoute('admin.users.index') },
            { title: 'Public pages', Icon: Globe, href: safeRoute('admin.landing.index') },
            { title: 'WiFi portal', Icon: Wifi, href: safeRoute('admin.wifi.edit') },
        ]
        : [
            { title: t('Guests'), Icon: Users, href: safeRoute('host.guests.index') },
            { title: 'Properties', Icon: Home, href: safeRoute('host.properties.index') },
            { title: 'Marketing', Icon: Megaphone, href: safeRoute('host.marketing.index') },
        ]).filter(a => a.href && a.href !== '#');
    const searchGroups = [];
    let flatIndex = 0;
    const addGroup = (title, items) => {
        if (items.length) searchGroups.push({ title, items: items.map(item => ({ item, index: flatIndex++ })) });
    };
    if (searchQuery.length >= 2) {
        addGroup('Results', searchResults);
    } else {
        addGroup('Recent', recentSearches.map(r => ({ ...r, recent: true, type: null })));
        addGroup('Go to', quickActions);
    }
    const searchItems = searchGroups.flatMap(g => g.items.map(({ item }) => item));

    const runSearchItem = (item) => {
        if (item.href) {
            setIsSearchOpen(false);
            router.get(item.href);
        } else {
            handleSearchSelect(item);
        }
    };

    const onSearchKeyDown = (e) => {
        if (!searchItems.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const step = e.key === 'ArrowDown' ? 1 : -1;
            const next = (activeIndex + step + searchItems.length) % searchItems.length;
            setActiveIndex(next);
            document.getElementById(`dashboard-search-item-${next}`)?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const item = searchItems[Math.min(activeIndex, searchItems.length - 1)];
            if (item) runSearchItem(item);
        }
    };

    const logout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    const notifications = auth.notifications || [];

    const toggleNavigation = () => (isWide ? setIsSidebarOpen((open) => !open) : setIsDrawerOpen((open) => !open));
    const isNavigationShown = isWide ? isSidebarOpen : isDrawerOpen;

    // A hidden sidebar must not take keyboard focus (React 18 has no `inert` prop).
    useEffect(() => {
        if (sidebarRef.current) sidebarRef.current.inert = !isNavigationShown;
    }, [isNavigationShown]);

    // The drawer closes once a link inside it has navigated, on Escape, and when the screen widens.
    useEffect(() => setIsDrawerOpen(false), [url, isWide]);
    useEffect(() => {
        if (!isDrawerOpen) return undefined;
        const onKey = (e) => e.key === 'Escape' && setIsDrawerOpen(false);
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isDrawerOpen]);

    return (
        <div className="dashboard-layout">
            {/* Drawer backdrop (narrow screens only) */}
            {isDrawerOpen && (
                <div className="dashboard-layout__backdrop" onClick={() => setIsDrawerOpen(false)} aria-hidden="true" />
            )}

            {/* Sidebar: in-place column on wide screens, slide-in drawer below 1024px */}
            <aside
                id="dashboard-navigation"
                className={`dashboard-layout__sidebar ${isSidebarOpen ? 'dashboard-layout__sidebar--open' : 'dashboard-layout__sidebar--closed'} ${isDrawerOpen ? 'dashboard-layout__sidebar--drawer-open' : ''}`}
                ref={sidebarRef}
            >
                <div className="dashboard-layout__brand">
                    <Link href="/" className="dashboard-layout__brand-link">
                        <BrandLogo variant={dark ? 'dark' : 'header'} className="dashboard-layout__logo" />
                    </Link>
                    <button
                        type="button"
                        onClick={() => setIsDrawerOpen(false)}
                        className="dashboard-layout__drawer-close"
                        aria-label="Close navigation"
                    >
                        <X size={20} />
                    </button>
                </div>

                <nav className="dashboard-layout__nav custom-scrollbar">
                    {navItems.map((item) => {
                        if (item.route && !hasRoute(item.route)) return null;
                        const isActive = isItemActive(item);
                        return (
                            <Link
                                key={item.name}
                                href={item.route ? safeRoute(item.route) : '#'}
                                prefetch
                                aria-current={isActive ? 'page' : undefined}
                                className={`dashboard-layout__nav-link group ${isActive
                                    ? 'dashboard-layout__nav-link--active'
                                    : 'dashboard-layout__nav-link--inactive'
                                    }`}
                            >
                                <item.icon size={20} className={`dashboard-layout__nav-icon ${isActive ? 'dashboard-layout__nav-icon--active' : 'dashboard-layout__nav-icon--inactive'}`} />
                                <span className="dashboard-layout__nav-label">{t(item.name)}</span>
                                {isActive && <div className="dashboard-layout__nav-indicator" />}
                            </Link>
                        );
                    })}
                </nav>

                <div className="dashboard-layout__user">
                    <div className="dashboard-layout__user-row">
                        <div className="dashboard-layout__user-avatar">
                            {auth.user.first_name?.charAt(0) || auth.user.username.charAt(0)}
                        </div>
                        <div>
                            <p className="dashboard-layout__user-name">{auth.user.first_name} {auth.user.last_name}</p>
                            <p className="dashboard-layout__user-role">{auth.user.role === 'host' && auth.account ? auth.account.label : auth.user.role}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="dashboard-layout__main">
                <div className="dashboard-layout__content group">
                    {/* Inner content scroller. scroll-region: the layout persists, so Inertia resets this on each visit. */}
                    <div className="dashboard-layout__scroller custom-scrollbar" scroll-region="">
                        {/* Top Navbar Header */}
                        <header className="dashboard-layout__header">
                            <div className="dashboard-layout__header-left">
                                <button
                                    type="button"
                                    onClick={toggleNavigation}
                                    className="dashboard-layout__sidebar-toggle"
                                    aria-label={isNavigationShown ? 'Hide navigation' : 'Show navigation'}
                                    aria-expanded={isNavigationShown}
                                    aria-controls="dashboard-navigation"
                                >
                                    {isNavigationShown ? <AlignLeft size={20} className="dashboard-layout__sidebar-toggle-icon" /> : <AlignJustify size={20} className="dashboard-layout__sidebar-toggle-icon" />}
                                </button>
                            </div>

                            <div className="dashboard-layout__header-right">
                                <div className="dashboard-layout__header-actions">
                                    {/* Search Button */}
                                    <button
                                        onClick={() => setIsSearchOpen(true)}
                                        className="dashboard-layout__search-button"
                                        aria-label="Search"
                                    >
                                        <Search size={14} />
                                        <span className="dashboard-layout__action-label">Search</span>
                                    </button>

                                    {/* Notifications Dropdown */}
                                    <Menu as="div" className="dashboard-layout__menu">
                                        <Menu.Button className="dashboard-layout__menu-button" aria-label="Notifications">
                                                <div className="dashboard-layout__menu-icon-wrapper">
                                                <Bell size={14} />
                                                {notifications.some(n => !n.read_at) && <div className="dashboard-layout__menu-badge"></div>}
                                            </div>
                                            <span className="dashboard-layout__action-label">Notifications</span>
                                        </Menu.Button>
                                        <Transition
                                            as={React.Fragment}
                                            enter="transition ease-out duration-200"
                                            enterFrom="opacity-0 translate-y-1"
                                            enterTo="opacity-100 translate-y-0"
                                            leave="transition ease-in duration-150"
                                            leaveFrom="opacity-100 translate-y-0"
                                            leaveTo="opacity-0 translate-y-1"
                                        >
                                            <Menu.Items className="dashboard-layout__menu-items">
                                                <div className="dashboard-layout__menu-header">
                                                    <h3 className="dashboard-layout__menu-title">Recent Activity</h3>
                                                </div>
                                                <div className="dashboard-layout__menu-list">
                                                    {notifications.length === 0 ? (
                                                        <div className="dashboard-layout__menu-empty">
                                                            <Bell size={20} className="text-gray-300 mb-2" />
                                                            <p className="text-xs text-gray-400">No notifications yet</p>
                                                        </div>
                                                    ) : notifications.map((n) => (
                                                        <Menu.Item key={n.id}>
                                                            {({ active }) => (
                                                                <button className={`dashboard-layout__menu-item ${active ? 'dashboard-layout__menu-item--active' : ''}`}>
                                                                    <div className={`dashboard-layout__menu-dot ${n.type === 'success' ? 'dashboard-layout__menu-dot--success' : 'dashboard-layout__menu-dot--warning'}`} />
                                                                    <div className="dashboard-layout__menu-item-content">
                                                                        <p className="dashboard-layout__menu-item-title">{n.title}</p>
                                                                        <p className="dashboard-layout__menu-item-time">{n.time}</p>
                                                                    </div>
                                                                </button>
                                                            )}
                                                        </Menu.Item>
                                                    ))}
                                                </div>
                                                <div className="dashboard-layout__menu-footer">
                                                    <button className="dashboard-layout__menu-footer-link">View All Notifications</button>
                                                </div>
                                            </Menu.Items>
                                        </Transition>
                                    </Menu>

                                    {/* Theme: light, dark or follow the device (remembered on this device). */}
                                    <Menu as="div" className="dashboard-layout__menu">
                                        <Menu.Button className="dashboard-layout__menu-button" aria-label={`Theme: ${theme}`}>
                                            {dark ? <Moon size={14} /> : <Sun size={14} />}
                                            <span className="dashboard-layout__action-label">Theme</span>
                                        </Menu.Button>
                                        <Transition
                                            as={React.Fragment}
                                            enter="transition ease-out duration-200"
                                            enterFrom="opacity-0 translate-y-1"
                                            enterTo="opacity-100 translate-y-0"
                                            leave="transition ease-in duration-150"
                                            leaveFrom="opacity-100 translate-y-0"
                                            leaveTo="opacity-0 translate-y-1"
                                        >
                                            <Menu.Items className="dashboard-layout__menu-items--narrow">
                                                <div className="dashboard-layout__menu-list">
                                                    {[['light', 'Light', Sun], ['dark', 'Dark', Moon], ['system', 'Match device', Monitor]].map(([value, label, Icon]) => (
                                                        <Menu.Item key={value}>
                                                            {({ active }) => (
                                                                <button type="button" onClick={() => setTheme(value)} aria-pressed={theme === value}
                                                                    className={`dashboard-layout__help-item ${active ? 'dashboard-layout__help-item--active' : ''} ${theme === value ? 'dashboard-layout__help-item--current' : ''}`}>
                                                                    <div className="dashboard-layout__help-item-icon"><Icon size={14} className="dashboard-layout__help-item-icon-svg" /></div>
                                                                    {label}
                                                                    {theme === value && <Check size={14} className="ml-auto" aria-hidden="true" />}
                                                                </button>
                                                            )}
                                                        </Menu.Item>
                                                    ))}
                                                </div>
                                            </Menu.Items>
                                        </Transition>
                                    </Menu>

                                    {/* Help Dropdown */}
                                    <Menu as="div" className="dashboard-layout__menu">
                                        <Menu.Button className="dashboard-layout__menu-button" aria-label="Help">
                                            <HelpCircle size={14} />
                                            <span className="dashboard-layout__action-label">Help</span>
                                        </Menu.Button>
                                        <Transition
                                            as={React.Fragment}
                                            enter="transition ease-out duration-200"
                                            enterFrom="opacity-0 translate-y-1"
                                            enterTo="opacity-100 translate-y-0"
                                            leave="transition ease-in duration-150"
                                            leaveFrom="opacity-100 translate-y-0"
                                            leaveTo="opacity-0 translate-y-1"
                                        >
                                            <Menu.Items className="dashboard-layout__menu-items--narrow">
                                                <div className="dashboard-layout__menu-list">
                                                    <Menu.Item>
                                                        {({ active }) => (
                                                            <Link href={route('help')} className={`dashboard-layout__help-item ${active ? 'dashboard-layout__help-item--active' : ''}`}>
                                                                <div className="dashboard-layout__help-item-icon"><BookOpen size={14} className="dashboard-layout__help-item-icon-svg" /></div>
                                                                Help Center
                                                            </Link>
                                                        )}
                                                    </Menu.Item>
                                                    <Menu.Item>
                                                        {({ active }) => (
                                                            <Link href={`${route('help')}?a=contact`} className={`dashboard-layout__help-item ${active ? 'dashboard-layout__help-item--active' : ''}`}>
                                                                <div className="dashboard-layout__help-item-icon"><LifeBuoy size={14} className="dashboard-layout__help-item-icon-svg" /></div>
                                                                Contact Support
                                                            </Link>
                                                        )}
                                                    </Menu.Item>
                                                </div>
                                            </Menu.Items>
                                        </Transition>
                                    </Menu>
                                </div>

                                {/* Profile Dropdown */}
                                <Menu as="div" className="dashboard-layout__menu">
                                    <Menu.Button className="dashboard-layout__profile-button">
                                        {auth.user.first_name?.charAt(0) || auth.user.username.charAt(0)}
                                    </Menu.Button>
                                    <Transition
                                        as={React.Fragment}
                                        enter="transition ease-out duration-200"
                                        enterFrom="opacity-0 translate-y-1"
                                        enterTo="opacity-100 translate-y-0"
                                        leave="transition ease-in duration-150"
                                        leaveFrom="opacity-100 translate-y-0"
                                        leaveTo="opacity-0 translate-y-1"
                                    >
                                        <Menu.Items className="dashboard-layout__menu-items--profile">
                                            <div className="dashboard-layout__profile-header">
                                                <p className="dashboard-layout__profile-name">{auth.user.first_name} {auth.user.last_name}</p>
                                                <p className="dashboard-layout__profile-email">{auth.user.email}</p>
                                            </div>
                                            <div className="dashboard-layout__menu-list">
                                                <Menu.Item>
                                                    {({ active }) => (
                                                        <Link
                                                            href={route('profile.edit')}
                                                            className={`dashboard-layout__profile-item ${active ? 'dashboard-layout__profile-item--active' : ''}`}
                                                        >
                                                            <div className="dashboard-layout__profile-item-icon"><User size={14} className="dashboard-layout__profile-item-icon-svg" /></div>
                                                            Account Settings
                                                        </Link>
                                                    )}
                                                </Menu.Item>
                                                <Menu.Item>
                                                    {({ active }) => (
                                                        <button
                                                            onClick={logout}
                                                            className={`dashboard-layout__sign-out group ${active ? 'dashboard-layout__sign-out--active' : 'dashboard-layout__sign-out--inactive'}`}
                                                        >
                                                            <div className={`dashboard-layout__sign-out-icon ${active ? 'dashboard-layout__sign-out-icon--active' : 'dashboard-layout__sign-out-icon--inactive'}`}><LogOut size={14} /></div>
                                                            Sign Out
                                                        </button>
                                                    )}
                                                </Menu.Item>
                                            </div>
                                        </Menu.Items>
                                    </Transition>
                                </Menu>
                            </div>
                        </header>

                        {/* Content Container */}
                        <div key={component} className="dashboard-layout__page animate-fade-in">
                            {children}
                        </div>
                    </div>

                    {/* Subtle Overlay Gradients */}
                    <div className="dashboard-layout__overlay-top" />
                    <div className="dashboard-layout__overlay-bottom" />
                </div>
            </main>

            {/* Global Search Dialog */}
            <Transition show={isSearchOpen} as={React.Fragment}>
                <Dialog as="div" className="dashboard-layout__search-dialog" onClose={() => setIsSearchOpen(false)}>
                    <Transition.Child
                        as={React.Fragment}
                        enter="ease-out duration-300"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="ease-in duration-200"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="dashboard-layout__search-overlay" />
                    </Transition.Child>

                    <div className="dashboard-layout__search-container">
                        <div className="dashboard-layout__search-panel-wrapper">
                            <Transition.Child
                                as={React.Fragment}
                                enter="ease-out duration-300"
                                enterFrom="opacity-0 scale-95 -translate-y-8"
                                enterTo="opacity-100 scale-100 translate-y-0"
                                leave="ease-in duration-200"
                                leaveFrom="opacity-100 scale-100 translate-y-0"
                                leaveTo="opacity-0 scale-95 -translate-y-8"
                            >
                                <Dialog.Panel className="dashboard-layout__search-panel">
                                    <div className="dashboard-layout__search-head">
                                        <Search size={18} className="dashboard-layout__search-icon" />
                                        <input
                                            ref={searchInputRef}
                                            autoFocus
                                            type="text"
                                            value={searchQuery}
                                            onChange={(e) => { setSearchQuery(e.target.value); setActiveIndex(0); }}
                                            onKeyDown={onSearchKeyDown}
                                            placeholder={userRole === 'admin' ? 'Search hosts, guests, properties…' : `Search ${t('Guests').toLowerCase()}, properties, campaigns…`}
                                            className="dashboard-layout__search-input"
                                            aria-label="Search"
                                            role="combobox"
                                            aria-expanded="true"
                                            aria-controls="dashboard-search-list"
                                            aria-activedescendant={searchItems[activeIndex] ? `dashboard-search-item-${activeIndex}` : undefined}
                                        />
                                        {searchLoading && <Loader2 size={16} className="dashboard-layout__search-spinner animate-spin" />}
                                        <button type="button" onClick={() => setIsSearchOpen(false)} className="dashboard-layout__search-close" aria-label="Close search">
                                            <X size={16} />
                                        </button>
                                    </div>

                                    <div id="dashboard-search-list" role="listbox" className="dashboard-layout__search-sections">
                                        {searchQuery.length >= 2 && !searchLoading && searchResults.length === 0 ? (
                                            <div className="dashboard-layout__search-empty">
                                                <Search size={24} />
                                                <p>No results for “{searchQuery}”</p>
                                            </div>
                                        ) : (
                                            searchGroups.map(group => (
                                                <div key={group.title} role="group" aria-label={group.title}>
                                                    <h3 className="dashboard-layout__search-section-title">{group.title}</h3>
                                                    {group.items.map(({ item, index }) => {
                                                        const Icon = item.Icon || iconMap[item.icon] || User;
                                                        return (
                                                            <button
                                                                type="button"
                                                                key={`${group.title}-${index}`}
                                                                id={`dashboard-search-item-${index}`}
                                                                role="option"
                                                                aria-selected={index === activeIndex}
                                                                onMouseMove={() => setActiveIndex(index)}
                                                                onClick={() => runSearchItem(item)}
                                                                className="dashboard-layout__result-item"
                                                            >
                                                                <span className="dashboard-layout__result-icon"><Icon size={15} /></span>
                                                                <span className="dashboard-layout__result-info">
                                                                    <span className="dashboard-layout__result-title">{item.title}</span>
                                                                    {item.subtitle && <span className="dashboard-layout__result-subtitle">{item.subtitle}</span>}
                                                                </span>
                                                                {item.type && <span className="dashboard-layout__result-type">{item.type}</span>}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            ))
                                        )}
                                    </div>

                                    <div className="dashboard-layout__search-footer">
                                        <span><kbd>↑</kbd><kbd>↓</kbd> to move</span>
                                        <span><kbd>Enter</kbd> to open</span>
                                        <span><kbd>Esc</kbd> to close</span>
                                    </div>
                                </Dialog.Panel>
                            </Transition.Child>
                        </div>
                    </div>
                </Dialog>
            </Transition>
        </div >
    );
}
