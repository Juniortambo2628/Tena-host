import React, { useState, useEffect } from 'react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent } from '@/lib/cms';
import './ROICalculator.css';

export default function ROICalculator({ section }) {
    const title = section ? getContent(section, 'title', 'Return on Investment Calculator') : 'Return on Investment Calculator';
    const subtitle = section ? getContent(section, 'subtitle', 'Answer a few simple questions to calculate how much more you could be earning with direct bookings through Tena.') : '';

    const [values, setValues] = useState({
        listings: 1,
        adr: 250,
        occupancy: 50,
        direct: 10,
        isManager: false,
        pmFee: 20
    });

    const [results, setResults] = useState({
        monthlyGross: 0,
        monthlyDirect: 0,
        monthlySavings: 0,
        netBenefit: 0,
        annualBenefit: 0
    });

    const [isUpdating, setIsUpdating] = useState(false);

    const handleChange = (field, value) => {
        setValues(prev => ({
            ...prev,
            [field]: field === 'isManager' ? value : Number(value)
        }));
        triggerUpdateAnimation();
    };

    const triggerUpdateAnimation = () => {
        setIsUpdating(true);
        setTimeout(() => setIsUpdating(false), 300);
    };

    useEffect(() => {
        const calculateROI = () => {
            const listings = values.listings || 0;
            const adr = values.adr || 0;
            const occupancy = (values.occupancy || 0) / 100;
            const direct = (values.direct || 0) / 100;
            const pmFee = (values.pmFee || 0) / 100;

            const nights = 30;
            const monthlyGross = listings * adr * nights * occupancy;
            const monthlyDirect = monthlyGross * direct;
            const otaFeeAvoided = monthlyDirect * 0.20;

            let managementCost = 0;
            if (values.isManager) {
                managementCost = monthlyDirect * pmFee;
            }

            const netBenefit = otaFeeAvoided - managementCost;
            const annual = netBenefit * 12;

            setResults({
                monthlyGross,
                monthlyDirect,
                monthlySavings: otaFeeAvoided,
                netBenefit,
                annualBenefit: annual
            });
        };

        calculateROI();
    }, [values]);

    const formatCurrency = (val) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(val);
    };

    return (
        <SectionWrapper id="roi-calculator" bg={section?.bg || 'gray'}>
            <SectionHeader title={title} subtitle={subtitle} />

            <div className="roi-card">
                <div className="roi-layout">
                    <div className="roi-form">
                        <div>
                            <label className="roi-label">Number of Listings</label>
                            <input type="range" min="1" max="3000" value={values.listings} onChange={(e) => handleChange('listings', e.target.value)} className="roi-range" />
                            <div className="roi-input-row">
                                <input type="number" min="1" max="3000" value={values.listings} onChange={(e) => handleChange('listings', e.target.value)} className="roi-number-input" />
                                <span className="roi-range-label">1 - 3,000</span>
                            </div>
                        </div>

                        <div>
                            <label className="roi-label">Average Daily Rate ($)</label>
                            <input type="range" min="0" max="5000" value={values.adr} onChange={(e) => handleChange('adr', e.target.value)} className="roi-range" />
                            <div className="roi-input-row">
                                <input type="number" min="0" max="5000" value={values.adr} onChange={(e) => handleChange('adr', e.target.value)} className="roi-number-input" />
                                <span className="roi-range-label">$0 - $5,000</span>
                            </div>
                        </div>

                        <div>
                            <label className="roi-label">Occupancy Rate (%)</label>
                            <input type="range" min="1" max="100" value={values.occupancy} onChange={(e) => handleChange('occupancy', e.target.value)} className="roi-range" />
                            <div className="roi-input-row">
                                <input type="number" min="1" max="100" value={values.occupancy} onChange={(e) => handleChange('occupancy', e.target.value)} className="roi-number-input" />
                                <span className="roi-range-label">1% - 100%</span>
                            </div>
                        </div>

                        <div>
                            <label className="roi-label">Direct Bookings (%)</label>
                            <input type="range" min="1" max="100" value={values.direct} onChange={(e) => handleChange('direct', e.target.value)} className="roi-range" />
                            <div className="roi-input-row">
                                <input type="number" min="1" max="100" value={values.direct} onChange={(e) => handleChange('direct', e.target.value)} className="roi-number-input" />
                                <span className="roi-range-label">1% - 100%</span>
                            </div>
                        </div>

                        <div>
                            <label className="roi-label">Are you a property manager?</label>
                            <div className="roi-radio-group">
                                <label className="roi-radio-label">
                                    <input type="radio" name="isManager" checked={values.isManager === true} onChange={() => handleChange('isManager', true)} className="roi-radio-input" />
                                    <span className="roi-radio-text">Yes</span>
                                </label>
                                <label className="roi-radio-label">
                                    <input type="radio" name="isManager" checked={values.isManager === false} onChange={() => handleChange('isManager', false)} className="roi-radio-input" />
                                    <span className="roi-radio-text">No</span>
                                </label>
                            </div>
                        </div>

                        <div className={`roi-manager-section ${values.isManager ? 'roi-manager-section-active' : 'roi-manager-section-inactive'}`}>
                            <label className="roi-label">Property Management Fee (%)</label>
                            <input type="range" min="1" max="100" value={values.pmFee} onChange={(e) => values.isManager && handleChange('pmFee', e.target.value)} disabled={!values.isManager} className="roi-range" />
                            <div className="roi-input-row">
                                <input type="number" min="1" max="100" value={values.pmFee} onChange={(e) => values.isManager && handleChange('pmFee', e.target.value)} disabled={!values.isManager} className="roi-number-input" />
                                <span className="roi-range-label">1% - 100%</span>
                            </div>
                        </div>

                        <button onClick={() => setValues({ listings: 1, adr: 250, occupancy: 50, direct: 10, isManager: false, pmFee: 20 })} className="roi-reset-btn">
                            Reset Calculator
                        </button>
                    </div>

                    <div className="roi-results-panel">
                        <h4 className="roi-results-title">Results</h4>
                        <div className="roi-results-list">
                            <ResultCard label="Monthly Gross Revenue" value={formatCurrency(results.monthlyGross)} isUpdating={isUpdating} />
                            <ResultCard label="Monthly Direct Revenue" value={formatCurrency(results.monthlyDirect)} isUpdating={isUpdating} />
                            <ResultCard label="Estimated Monthly Savings (OTA fees avoided)" value={formatCurrency(results.monthlySavings)} isUpdating={isUpdating} />
                            <ResultCard label="Net Monthly Benefit (after management fee)" value={formatCurrency(results.netBenefit)} isUpdating={isUpdating} />
                            <div className="roi-results-divider">
                                <ResultCard label="Estimated Annual Net Benefit" value={formatCurrency(results.annualBenefit)} isUpdating={isUpdating} isTotal />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SectionWrapper>
    );
}

const ResultCard = ({ label, value, isUpdating, isTotal = false }) => (
    <div className={`roi-result-card ${isUpdating ? 'roi-result-card-updating' : 'roi-result-card-idle'}`}>
        <h6 className="roi-result-label">{label}</h6>
        <div className={isTotal ? 'roi-result-value-total' : 'roi-result-value'}>
            {value}
        </div>
    </div>
);
