import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    LayoutDashboard,
    Users,
    Wifi,
    Megaphone,
    Settings,
    AlignLeft,
    AlignJustify,
    Search,
    Bell,
    HelpCircle,
    User,
    LogOut,
    CheckCircle2,
    X,
    Plus,
    Building2,
    Activity,
    CreditCard,
    Globe
} from 'lucide-react';
import { Menu, Transition, Dialog } from '@headlessui/react';
import { router } from '@inertiajs/react';
import './DashboardLayout.css';

export default function DashboardLayout({ children, title, bgImage = "https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&w=2000&q=80" }) {
    const { auth } = usePage().props;
    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const userRole = auth.user.role || 'host';

    const hostNavItems = [
        { name: 'Dashboard', icon: LayoutDashboard, route: 'host.dashboard' },
        { name: 'Guests', icon: Users, route: 'host.guests.index' },
        { name: 'WiFi Access', icon: Wifi, route: 'host.properties.index' },
        { name: 'Marketing', icon: Megaphone, route: 'host.marketing.index' },
        { name: 'Billing', icon: CreditCard, route: 'host.billing.index' },
        { name: 'Notifications', icon: Bell, route: 'profile.notifications' },
        { name: 'Settings', icon: Settings, route: 'profile.edit' },
    ];

    const adminNavItems = [
        { name: 'Overview', icon: LayoutDashboard, route: 'admin.dashboard' },
        { name: 'Hosts', icon: Building2, route: 'admin.hosts.index' },
        { name: 'Users', icon: Users, route: 'admin.users.index' },
        { name: 'Registrations', icon: AlignLeft, route: 'admin.registrations.index' },
        { name: 'Landing Page', icon: Globe, route: 'admin.landing.index' },
        { name: 'System', icon: Activity, route: 'admin.system.index' },
        { name: 'Settings', icon: Settings, route: 'admin.settings.index' },
    ];

    const staffNavItems = [
        { name: 'Overview', icon: LayoutDashboard, route: 'staff.dashboard' },
        { name: 'Settings', icon: Settings, route: 'profile.edit' },
    ];

    const navItems = userRole === 'admin' ? adminNavItems : (userRole === 'staff' ? staffNavItems : hostNavItems);

    const [isSearchOpen, setIsSearchOpen] = useState(false);

    const logout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    const notifications = auth.notifications || [];

    return (
        <div className="dashboard-layout">
            {/* Background Image/Gradient Layer */}
            <div
                className="dashboard-layout__bg"
                style={{ backgroundImage: `url('${bgImage}')` }}
            />

            {/* Sidebar */}
            <aside className={`dashboard-layout__sidebar ${isSidebarOpen ? 'dashboard-layout__sidebar--open' : 'dashboard-layout__sidebar--closed'}`}>
                <div className="dashboard-layout__brand">
                    <Link href="/" className="dashboard-layout__brand-link">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="TENA Logo"
                            className="dashboard-layout__logo"
                        />
                        <span className="dashboard-layout__brand-text">TENA</span>
                    </Link>
                </div>

                <nav className="dashboard-layout__nav custom-scrollbar">
                    {navItems.map((item) => {
                        const isActive = item.route && route().current(item.route);
                        return (
                            <Link
                                key={item.name}
                                href={item.route ? route(item.route) : '#'}
                                className={`dashboard-layout__nav-link group ${isActive
                                    ? 'dashboard-layout__nav-link--active'
                                    : 'dashboard-layout__nav-link--inactive'
                                    }`}
                            >
                                <item.icon size={20} className={`dashboard-layout__nav-icon ${isActive ? 'dashboard-layout__nav-icon--active' : 'dashboard-layout__nav-icon--inactive'}`} />
                                <span className="dashboard-layout__nav-label">{item.name}</span>
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
                            <p className="dashboard-layout__user-role">{auth.user.role}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="dashboard-layout__main">
                <div className="dashboard-layout__content group">
                    {/* Inner Content Scroller */}
                    <div className="dashboard-layout__scroller custom-scrollbar">
                        {/* Top Navbar Header */}
                        <header className="dashboard-layout__header">
                            <div className="dashboard-layout__header-left">
                                <button
                                    onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                                    className="dashboard-layout__sidebar-toggle"
                                >
                                    {isSidebarOpen ? <AlignLeft size={20} className="dashboard-layout__sidebar-toggle-icon" /> : <AlignJustify size={20} className="dashboard-layout__sidebar-toggle-icon" />}
                                </button>
                                <span className="dashboard-layout__nav-heading">Navigation</span>
                            </div>

                            <div className="dashboard-layout__header-right">
                                <div className="dashboard-layout__header-actions">
                                    {/* Search Button */}
                                    <button
                                        onClick={() => setIsSearchOpen(true)}
                                        className="dashboard-layout__search-button"
                                    >
                                        <Search size={14} />
                                        Search
                                    </button>

                                    {/* Notifications Dropdown */}
                                    <Menu as="div" className="dashboard-layout__menu">
                                        <Menu.Button className="dashboard-layout__menu-button">
                                            <div className="dashboard-layout__menu-icon-wrapper">
                                                <Bell size={14} />
                                                <div className="dashboard-layout__menu-badge"></div>
                                            </div>
                                            Notifications
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
                                                    {notifications.map((n) => (
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

                                    {/* Help Dropdown */}
                                    <Menu as="div" className="dashboard-layout__menu">
                                        <Menu.Button className="dashboard-layout__menu-button">
                                            <HelpCircle size={14} />
                                            Help
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
                                                            <button className={`dashboard-layout__help-item ${active ? 'dashboard-layout__help-item--active' : ''}`}>
                                                                <div className="dashboard-layout__help-item-icon"><Search size={14} className="dashboard-layout__help-item-icon-svg" /></div>
                                                                Knowledge Base
                                                            </button>
                                                        )}
                                                    </Menu.Item>
                                                    <Menu.Item>
                                                        {({ active }) => (
                                                            <button className={`dashboard-layout__help-item ${active ? 'dashboard-layout__help-item--active' : ''}`}>
                                                                <div className="dashboard-layout__help-item-icon"><Bell size={14} className="dashboard-layout__help-item-icon-svg" /></div>
                                                                Contact Support
                                                            </button>
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
                        <div className="dashboard-layout__page animate-fade-in">
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
                                    <div className="dashboard-layout__search-body">
                                        <div className="dashboard-layout__search-input-group group">
                                            <Search size={24} className="dashboard-layout__search-icon" />
                                            <input
                                                autoFocus
                                                type="text"
                                                placeholder="Search for guests, properties, or campaigns..."
                                                className="dashboard-layout__search-input"
                                            />
                                            <button
                                                onClick={() => setIsSearchOpen(false)}
                                                className="dashboard-layout__search-close"
                                            >
                                                <X size={20} />
                                            </button>
                                        </div>

                                        <div className="dashboard-layout__search-sections">
                                            <div>
                                                <h3 className="dashboard-layout__search-section-title">Recent Searches</h3>
                                                <div className="dashboard-layout__quick-search-grid">
                                                    <QuickSearchItem icon={<Users size={14} />} label="John Doe (Guest)" />
                                                    <QuickSearchItem icon={<Megaphone size={14} />} label="Welcome Email" />
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="dashboard-layout__search-section-title">Quick Commands</h3>
                                                <div className="dashboard-layout__command-list">
                                                    <CommandItem icon={<Plus size={14} />} label="Add New Property" shortcut="⌘P" />
                                                    <CommandItem icon={<Plus size={14} />} label="Create Campaign" shortcut="⌘C" />
                                                    <CommandItem icon={<Wifi size={14} />} label="Check WiFi Status" shortcut="⌘W" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="dashboard-layout__search-footer">
                                        <div className="dashboard-layout__search-footer-hints">
                                            <span className="dashboard-layout__search-footer-hint">Enter to select</span>
                                            <span className="dashboard-layout__search-footer-hint">Esc to close</span>
                                        </div>
                                        <div className="dashboard-layout__search-footer-powered">
                                            <span className="dashboard-layout__search-footer-label">Powered by</span>
                                            <span className="dashboard-layout__search-footer-brand">TENA SEARCH</span>
                                        </div>
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

function QuickSearchItem({ icon, label }) {
    return (
        <button className="dashboard-layout__quick-search-item group">
            <div className="dashboard-layout__quick-search-icon">
                {icon}
            </div>
            <span className="dashboard-layout__quick-search-label">{label}</span>
        </button>
    );
}

function CommandItem({ icon, label, shortcut }) {
    return (
        <button className="dashboard-layout__command-item group">
            <div className="dashboard-layout__command-item-left">
                <div className="dashboard-layout__command-item-icon">{icon}</div>
                <span className="dashboard-layout__command-item-label">{label}</span>
            </div>
            <span className="dashboard-layout__command-item-shortcut">{shortcut}</span>
        </button>
    );
}
