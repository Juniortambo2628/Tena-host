import React from 'react';
import { motion } from 'framer-motion';
import './StatsCard.css';

export default function StatsCard({ title, value, icon, trend, trendValue }) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            whileHover={{ scale: 1.02, translateY: -5 }}
            className="stats-card group"
        >
            <div className="stats-card__background" />
            <div className="stats-card__content">
                <div className="stats-card__header">
                    <div className="stats-card__icon">
                        {icon}
                    </div>
                    {trend && (
                        <span className={`stats-card__trend ${trend === 'up' ? 'stats-card__trend--up' : 'stats-card__trend--down'}`}>
                            {trend === 'up' ? '↑' : '↓'} {trendValue}
                        </span>
                    )}
                </div>
                    <h3 className="stats-card__title">{title}</h3>
                <div className="stats-card__value-wrapper">
                    <p className="stats-card__value">{value}</p>
                </div>
            </div>
            <div className="stats-card__bar" />
        </motion.div>
    );
}
