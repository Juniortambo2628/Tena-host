import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import PillButton from './PillButton';
import './TabbedModal.css';

export default function TabbedModal({
    show,
    onClose,
    title,
    description,
    tabs = [],
    onConfirm,
    confirmLabel = "Save Changes",
    processing = false,
    maxWidth = '2xl'
}) {
    const [activeTab, setActiveTab] = useState(0);

    return (
        <Modal show={show} onClose={onClose} maxWidth={maxWidth}>
            <div className="tabbed-modal__container">
                {/* Sidebar Navigation */}
                <div className="tabbed-modal__sidebar">
                    <div className="tabbed-modal__header">
                        <h3 className="tabbed-modal__title">{title}</h3>
                        <p className="tabbed-modal__description">
                            {description}
                        </p>
                    </div>

                    <div className="tabbed-modal__tabs">
                        {tabs.map((tab, idx) => (
                            <button
                                key={idx}
                                onClick={() => setActiveTab(idx)}
                                className={`tabbed-modal__tab-button group ${activeTab === idx
                                    ? 'tabbed-modal__tab-button--active'
                                    : 'tabbed-modal__tab-button--inactive'
                                    }`}
                            >
                                <span className={`tabbed-modal__tab-number ${activeTab === idx ? 'tabbed-modal__tab-number--active' : 'tabbed-modal__tab-number--inactive'}`}>
                                    {String(idx + 1).padStart(2, '0')}
                                </span>
                                <span className="tabbed-modal__tab-label">{tab.label}</span>
                            </button>
                        ))}
                    </div>

                    <div className="tabbed-modal__footer">
                        <PillButton onClick={onClose} variant="ghost" className="tabbed-modal__cancel">
                            Cancel
                        </PillButton>
                    </div>
                </div>

                {/* Content Area */}
                <div className="tabbed-modal__content-area">
                    <div className="tabbed-modal__content">
                        <div className="tabbed-modal__step-header">
                            <span className="tabbed-modal__step-counter">Step {activeTab + 1} of {tabs.length}</span>
                            <h4 className="tabbed-modal__step-title">{tabs[activeTab].label}</h4>
                        </div>

                        <div className="animate-fade-in">
                            {tabs[activeTab].content}
                        </div>
                    </div>

                    <div className="tabbed-modal__action-bar">
                        <div className="tabbed-modal__indicators">
                            {tabs.map((_, idx) => (
                                <div
                                    key={idx}
                                    className={`tabbed-modal__step-indicator ${activeTab === idx ? 'tabbed-modal__step-indicator--active' : 'tabbed-modal__step-indicator--inactive'
                                        }`}
                                />
                            ))}
                        </div>

                        <div className="tabbed-modal__actions">
                            {activeTab > 0 && (
                                <PillButton onClick={() => setActiveTab(activeTab - 1)} variant="white">
                                    Previous
                                </PillButton>
                            )}
                            {activeTab < tabs.length - 1 ? (
                                <PillButton onClick={() => setActiveTab(activeTab + 1)} variant="secondary">
                                    Next Step
                                </PillButton>
                            ) : (
                                <PillButton onClick={onConfirm} variant="primary" disabled={processing}>
                                    {confirmLabel}
                                </PillButton>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
