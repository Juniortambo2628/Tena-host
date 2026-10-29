import React from 'react';
import { Inbox } from 'lucide-react';
import PillButton from './PillButton';
import { useTerms } from './Terms';
import './EmptyState.css';

// The one empty state for dashboard lists and panels: an icon, one line, an
// optional hint and an optional action ({ label, onClick | href, icon }).
export default function EmptyState({ icon: Icon = Inbox, message, hint = null, action = null, compact = false }) {
    const t = useTerms();

    return (
        <div className={`empty-state ${compact ? 'empty-state--compact' : ''}`}>
            <div className="empty-state__icon">
                <Icon size={compact ? 18 : 22} />
            </div>
            <p className="empty-state__message">{t(message)}</p>
            {hint && <p className="empty-state__hint">{t(hint)}</p>}
            {action && (
                <PillButton
                    variant="secondary"
                    href={action.href}
                    onClick={action.onClick}
                    icon={action.icon}
                    className="empty-state__action"
                >
                    {t(action.label)}
                </PillButton>
            )}
        </div>
    );
}
