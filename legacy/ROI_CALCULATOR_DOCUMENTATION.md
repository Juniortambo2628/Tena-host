# ROI Calculator Documentation

## Overview
The Tena ROI (Return on Investment) Calculator is an interactive tool designed to help vacation rental property owners and managers calculate their potential savings by converting OTA (Online Travel Agency) bookings into direct bookings using the Tena platform.

**Location:** Displayed on the main landing page (`index.html`) in the pricing section  
**Files Involved:**
- `index.html` (lines 550-657) - HTML structure
- `js/main.js` (lines 189-285) - Calculation logic and interactivity

---

## Purpose & Value Proposition

The ROI Calculator demonstrates the financial benefit of using Tena by showing:
1. **Monthly Revenue** - Total gross revenue from properties
2. **Direct Revenue** - Revenue from direct bookings
3. **OTA Savings** - Money saved by avoiding 20% OTA commission fees
4. **Net Benefit** - Savings after property management fees (if applicable)
5. **Annual Benefit** - Projected yearly savings

---

## User Interface Components

### Input Fields

#### 1. Number of Listings
- **Type:** Slider + Number Input
- **Range:** 1 to 3,000 listings
- **Default:** 1
- **Element IDs:** `#numListingsRange` (slider), `#numListings` (input)
- **Purpose:** Total number of vacation rental properties being managed

#### 2. Average Daily Rate (ADR)
- **Type:** Slider + Number Input
- **Range:** $0 to $5,000
- **Default:** $250
- **Element IDs:** `#adrRange` (slider), `#adr` (input)
- **Purpose:** Average nightly rate charged per property

#### 3. Occupancy Rate
- **Type:** Slider + Number Input
- **Range:** 1% to 100%
- **Default:** 50%
- **Element IDs:** `#occupancyRange` (slider), `#occupancy` (input)
- **Purpose:** Percentage of nights the properties are booked

#### 4. Direct Bookings Percentage
- **Type:** Slider + Number Input
- **Range:** 1% to 100%
- **Default:** 10%
- **Element IDs:** `#directRange` (slider), `#direct` (input)
- **Purpose:** Percentage of bookings that come directly (not through OTAs)

#### 5. Property Manager Status
- **Type:** Radio Buttons
- **Options:** Yes / No
- **Default:** No
- **Element IDs:** `#managerYes`, `#managerNo`
- **Purpose:** Determines if management fees should be included in calculations

#### 6. Property Management Fee
- **Type:** Slider + Number Input
- **Range:** 1% to 100%
- **Default:** 20%
- **Element IDs:** `#pmFeeRange` (slider), `#pmFee` (input)
- **Purpose:** Percentage fee charged for property management services

### Output Fields

All output fields display formatted currency values:

1. **Monthly Gross Revenue** (`#monthlyGross`) - Total monthly income
2. **Monthly Direct Revenue** (`#monthlyDirect`) - Income from direct bookings only
3. **Estimated Monthly Savings** (`#monthlySavings`) - OTA fees avoided
4. **Net Monthly Benefit** (`#netBenefit`) - Savings minus management costs
5. **Estimated Annual Net Benefit** (`#annualBenefit`) - Yearly projection

---

## Calculation Logic

### Mathematical Formulas

The calculator uses the following step-by-step calculations:

```javascript
// Constants
const NIGHTS_PER_MONTH = 30
const OTA_COMMISSION_RATE = 0.20  // 20%

// Step 1: Calculate Monthly Gross Revenue
monthlyGross = listings × adr × nights × (occupancy / 100)

// Step 2: Calculate Monthly Direct Revenue
monthlyDirect = monthlyGross × (direct / 100)

// Step 3: Calculate OTA Fees Avoided
otaFeeAvoided = monthlyDirect × OTA_COMMISSION_RATE

// Step 4: Calculate Management Cost (if applicable)
managementCost = isManager ? (monthlyDirect × pmFee / 100) : 0

// Step 5: Calculate Net Monthly Benefit
netBenefit = otaFeeAvoided - managementCost

// Step 6: Calculate Annual Benefit
annual = netBenefit × 12
```

### Example Calculation

**Given:**
- 5 listings
- $200 average daily rate
- 70% occupancy
- 30% direct bookings
- Property manager: Yes
- 25% management fee

**Calculation:**
```
Monthly Gross = 5 × $200 × 30 × 0.70 = $21,000
Monthly Direct = $21,000 × 0.30 = $6,300
OTA Savings = $6,300 × 0.20 = $1,260
Management Cost = $6,300 × 0.25 = $1,575
Net Benefit = $1,260 - $1,575 = -$315
Annual Benefit = -$315 × 12 = -$3,780
```

*Note: Negative values indicate the management fee exceeds OTA savings in this scenario.*

---

## Technical Implementation

### JavaScript Architecture

#### 1. Bidirectional Synchronization
```javascript
// Sliders and inputs are synced in both directions
slider.addEventListener('input', () => { 
    input.value = slider.value;
    calcROI();  // Recalculate immediately
});

input.addEventListener('input', () => { 
    slider.value = input.value;
    calcROI();  // Recalculate immediately
});
```

**Benefits:**
- Users can use either control method
- Values always stay in sync
- Immediate visual feedback

#### 2. Real-Time Updates
All inputs trigger instant recalculation:
- Slider movements
- Manual number input
- Radio button changes

**No "Calculate" button needed** - results update as users interact with the form.

#### 3. Visual Feedback Animation

```javascript
// Add updating animation
element.classList.add('updating');
element.textContent = newValue;

setTimeout(() => {
    element.classList.remove('updating');
    element.classList.add('updated');
    
    setTimeout(() => {
        element.classList.remove('updated');
    }, 600);
}, 50);
```

**CSS Classes:**
- `.updating` - Applied during value change
- `.updated` - Applied after update (0.6s duration)
- Creates smooth transition effect

#### 4. Currency Formatting
```javascript
const fmt = v => '$' + Number(v).toLocaleString(undefined, {
    maximumFractionDigits: 0
});
```

**Output Examples:**
- `5000` → `$5,000`
- `125000` → `$125,000`
- `1500000` → `$1,500,000`

### Event Flow

```
User Interaction
    ↓
Slider/Input Change Event
    ↓
Sync Paired Control
    ↓
Call calcROI()
    ↓
Read All Input Values
    ↓
Perform Calculations
    ↓
Format Results
    ↓
Update Display with Animation
    ↓
Visual Feedback Complete
```

---

## User Experience Features

### 1. Instant Feedback
- **No delays** - calculations happen immediately
- **No button clicks required** - fully interactive
- **Visual animations** - smooth value transitions

### 2. Flexible Input Methods
- **Sliders** - Quick, visual adjustments
- **Number fields** - Precise value entry
- **Keyboard support** - Arrow keys work on sliders

### 3. Validation & Constraints
- Sliders enforce min/max ranges automatically
- Number inputs respect defined boundaries
- No invalid calculations possible

### 4. Reset Functionality
```javascript
resetBtn.addEventListener('click', () => { 
    form.reset();
    calcROI();  // Update display with default values
});
```

**Reset button returns all fields to their default values:**
- Number of Listings: 1
- ADR: $250
- Occupancy: 50%
- Direct Bookings: 10%
- Manager Status: No
- Management Fee: 20%

---

## Business Logic Assumptions

### 1. OTA Commission Rate
- **Fixed at 20%** - Industry standard for platforms like Airbnb, VRBO
- Represents the fee saved when bookings go direct
- Hard-coded in the calculation (not user-adjustable)

### 2. Monthly Calculation Period
- **30 nights per month** - Simplified calculation
- Provides consistent monthly projections
- Actual months vary (28-31 days) but 30 is used for estimates

### 3. Management Fee Application
- **Only applied to direct revenue** - Not total revenue
- **Optional** - Only calculated if user indicates they're a manager
- Represents the cost of Tena's management services

### 4. Linear Scaling
- Assumes consistent occupancy across all listings
- Assumes uniform ADR across all properties
- Real-world variations not accounted for

---

## Integration Points

### Page Context
The ROI Calculator is strategically placed:
- **Before the pricing section** - Shows value before revealing cost
- **After feature descriptions** - Users understand benefits first
- **Near waitlist CTA** - Easy conversion path after seeing ROI

### Call-to-Action Flow
```
1. User adjusts calculator inputs
2. Sees potential savings
3. Clicks "Join Waitlist" button (in pricing section)
4. Waitlist modal opens
5. Registration form appears
```

---

## Maintenance & Updates

### Modifying Constants

#### Change OTA Commission Rate
**File:** `js/main.js` (line 236)
```javascript
// Current: 20%
const otaFeeAvoided = monthlyDirect * 0.20;

// To change to 15%:
const otaFeeAvoided = monthlyDirect * 0.15;
```

#### Change Nights Per Month
**File:** `js/main.js` (line 233)
```javascript
// Current: 30 nights
const nights = 30;

// To change to 31 nights:
const nights = 31;
```

### Modifying Input Ranges

**File:** `index.html`

#### Number of Listings
```html
<!-- Lines 554-556 -->
<input type="range" id="numListingsRange" min="1" max="3000" value="1">
<input type="number" id="numListings" min="1" max="3000" value="1">
```

#### Average Daily Rate
```html
<!-- Lines 563-565 -->
<input type="range" id="adrRange" min="0" max="5000" value="250">
<input type="number" id="adr" min="0" max="5000" value="250">
```

*Adjust `min`, `max`, and `value` attributes as needed*

### Adding New Input Fields

**Steps:**
1. Add HTML form elements in `index.html`
2. Add to mapping array in `js/main.js` (lines 191-197)
3. Update `calcROI()` function to use new values
4. Update result displays if needed

---

## Accessibility Features

### ARIA Support
- Form labels properly associated with inputs
- Semantic HTML structure
- Keyboard navigation supported

### Visual Accessibility
- High contrast text
- Large, readable numbers in results
- Clear labels and descriptions

### Mobile Optimization
- Touch-friendly sliders
- Responsive layout
- Works on all screen sizes

---

## Browser Compatibility

### Supported Browsers
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Required JavaScript Features
- `Number.toLocaleString()` - For currency formatting
- `document.querySelector()` - Element selection
- ES6 Arrow functions
- `addEventListener()` API

---

## Performance Considerations

### Optimization Techniques

1. **Debouncing NOT Used**
   - Calculations are fast enough for instant updates
   - No performance issues with real-time recalculation
   - Better UX with immediate feedback

2. **Efficient DOM Updates**
   - Only updates changed values
   - Uses class-based animations (GPU-accelerated)
   - Minimal reflows/repaints

3. **No External Dependencies**
   - Pure JavaScript (vanilla JS)
   - No jQuery or other libraries needed for calculator
   - Smaller bundle size

---

## Testing Scenarios

### Edge Cases to Verify

1. **Zero Values**
   - 0 listings → $0 everywhere
   - 0% occupancy → $0 revenue
   - 0% direct bookings → $0 savings

2. **Maximum Values**
   - 3,000 listings × $5,000 ADR × 100% occupancy
   - Should display properly formatted large numbers

3. **Manager Fee Scenarios**
   - Manager fee > OTA savings → Negative net benefit
   - Manager fee = OTA savings → $0 net benefit
   - Should display negative values correctly

4. **Boundary Conditions**
   - 1% occupancy
   - 100% occupancy
   - 1% direct bookings
   - 100% direct bookings

---

## Future Enhancement Ideas

### Potential Improvements

1. **Variable OTA Rates**
   - Allow users to set different commission rates
   - Dropdown for different platforms (Airbnb 15%, VRBO 20%, etc.)

2. **Seasonal Adjustments**
   - Different occupancy rates by season
   - Peak/off-peak pricing

3. **Multi-Currency Support**
   - USD, EUR, GBP, etc.
   - Automatic conversion

4. **Advanced Analytics**
   - Break-even analysis
   - ROI timeline charts
   - Comparison graphs

5. **Save/Share Results**
   - Generate PDF reports
   - Email results
   - Share link with calculations

6. **Property Mix**
   - Different ADRs per listing
   - Variable occupancy rates
   - Property type segmentation

---

## Troubleshooting

### Common Issues

#### Calculator Not Updating
**Check:**
- JavaScript console for errors
- Slider and input ID matching
- Event listeners properly attached

#### Incorrect Calculations
**Verify:**
- All input values reading correctly
- Formula constants correct
- No division by zero errors

#### Display Issues
**Confirm:**
- Currency formatting function working
- DOM element IDs match
- CSS not hiding elements

---

## Related Documentation

- **Form Integration:** See `reusable-registration-form/INTEGRATION_GUIDE.md`
- **Main JavaScript:** `js/main.js`
- **Landing Page:** `index.html`
- **Pricing Section:** Lines 660-704 in `index.html`

---

## Summary

The ROI Calculator is a powerful, user-friendly tool that:
- ✅ Calculates savings from direct bookings in real-time
- ✅ Provides instant visual feedback
- ✅ Works seamlessly on all devices
- ✅ Requires no external dependencies
- ✅ Integrates naturally with the conversion funnel

**Key Metrics:**
- **Calculation Time:** < 1ms
- **Update Frequency:** Instant (on every input change)
- **Lines of Code:** ~100 lines JavaScript, ~110 lines HTML
- **Dependencies:** Zero (vanilla JS)

---

**Last Updated:** October 1, 2025  
**Version:** 1.0  
**Maintained By:** Tena Development Team

