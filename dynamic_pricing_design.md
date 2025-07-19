# Dynamic Pricing System Design

## Overview
The dynamic pricing system will allow car rental businesses to set flexible pricing based on various factors including day types, seasons, and specific calendar dates with override capabilities.

## Core Features

### 1. Base Pricing Rules
- **Default Daily Rate**: Base price for the vehicle
- **Weekday Pricing**: Monday-Thursday rates
- **Weekend Pricing**: Friday-Sunday rates  
- **Holiday Pricing**: Special rates for holidays
- **Peak Season Pricing**: High-demand periods (summer, holidays)
- **Off-Season Pricing**: Low-demand periods

### 1.1. Duration Discount Rules
- **Daily Rate**: 1-2 days (no discount)
- **3-6 Day Discount**: 5-10% discount for short-term rentals
- **Weekly Discount**: 15-20% discount for 7-13 day rentals
- **Bi-Weekly Discount**: 25-30% discount for 14-20 day rentals
- **Monthly Discount**: 35-40% discount for 21+ day rentals
- **Extended Discount**: 45-50% discount for 60+ day rentals
- **Custom Duration Tiers**: Configurable discount brackets

### 2. Calendar Override System
- **Specific Date Pricing**: Set exact prices for individual dates
- **Date Range Pricing**: Set prices for consecutive date ranges
- **Event-Based Pricing**: Special rates for conferences, festivals, etc.
- **Priority System**: Specific dates override general rules

### 3. Pricing Rules Hierarchy (Priority Order)
1. **Specific Date Override** (highest priority)
2. **Date Range Override**
3. **Holiday Pricing**
4. **Peak/Off Season Pricing**
5. **Weekend/Weekday Pricing**
6. **Duration Discounts** (applied to calculated daily rate)
7. **Default Daily Rate** (lowest priority)

### 3.1. Pricing Calculation Flow
1. **Determine Base Daily Rate**: Apply date-based rules (overrides, holidays, seasons, weekdays/weekends)
2. **Calculate Total Days**: Count rental duration
3. **Apply Duration Discount**: Reduce daily rate based on rental length
4. **Calculate Final Price**: (Discounted Daily Rate × Number of Days)

## User Interface Design

### 1. Vehicle Pricing Management
- Enhanced vehicle form with pricing section
- Base rate configuration
- Duration discount tier setup
- Quick pricing rule setup
- Link to detailed pricing calendar

### 2. Duration Discount Manager
- Configure discount tiers (3-day, weekly, monthly, etc.)
- Set percentage or fixed amount discounts
- Preview discount calculations
- Bulk discount operations across vehicle fleet

### 3. Pricing Calendar Interface
- Monthly calendar view with pricing display
- Color-coded pricing levels (low, medium, high)
- Click-to-edit individual dates
- Bulk date selection for range pricing
- Visual indicators for different pricing rules
- Duration discount preview on hover

### 4. Pricing Rules Manager
- Create and manage pricing templates
- Seasonal pricing configuration
- Holiday calendar integration
- Duration discount templates
- Bulk pricing operations

### 5. Pricing Preview & Calculator
- Real-time pricing calculation with duration discounts
- Customer-facing pricing calculator
- Rental duration slider with live price updates
- Savings display for longer rentals
- Availability and pricing display
- Booking integration with dynamic pricing

## Technical Implementation

### 1. Data Structure
```javascript
vehiclePricing: {
  vehicleId: string,
  baseRate: number,
  pricingRules: {
    weekday: number,
    weekend: number,
    holiday: number,
    peakSeason: { rate: number, startDate: date, endDate: date },
    offSeason: { rate: number, startDate: date, endDate: date }
  },
  durationDiscounts: [
    { minDays: 3, maxDays: 6, discountType: 'percentage', discountValue: 5, name: '3-6 Day Discount' },
    { minDays: 7, maxDays: 13, discountType: 'percentage', discountValue: 15, name: 'Weekly Discount' },
    { minDays: 14, maxDays: 20, discountType: 'percentage', discountValue: 25, name: 'Bi-Weekly Discount' },
    { minDays: 21, maxDays: 59, discountType: 'percentage', discountValue: 35, name: 'Monthly Discount' },
    { minDays: 60, maxDays: null, discountType: 'percentage', discountValue: 45, name: 'Extended Discount' }
  ],
  dateOverrides: [
    { date: date, price: number, reason: string }
  ],
  rangeOverrides: [
    { startDate: date, endDate: date, price: number, reason: string }
  ]
}
```

### 2. Pricing Calculation Logic
```javascript
function calculatePrice(vehicleId, startDate, endDate) {
  const days = calculateDays(startDate, endDate);
  let dailyRates = [];
  
  // Calculate daily rate for each day
  for (let date = startDate; date <= endDate; date++) {
    let dailyRate = getDailyRate(vehicleId, date);
    dailyRates.push(dailyRate);
  }
  
  // Calculate base total
  const baseTotal = dailyRates.reduce((sum, rate) => sum + rate, 0);
  
  // Apply duration discount
  const durationDiscount = getDurationDiscount(vehicleId, days);
  const discountedTotal = applyDurationDiscount(baseTotal, durationDiscount);
  
  return {
    days: days,
    baseTotal: baseTotal,
    durationDiscount: durationDiscount,
    finalTotal: discountedTotal,
    dailyBreakdown: dailyRates,
    savings: baseTotal - discountedTotal
  };
}

function getDailyRate(vehicleId, date) {
  // 1. Check for specific date override
  // 2. Check for date range override  
  // 3. Check if holiday
  // 4. Check if peak/off season
  // 5. Check if weekend/weekday
  // 6. Return base rate
}

function getDurationDiscount(vehicleId, days) {
  const discounts = getVehicleDurationDiscounts(vehicleId);
  
  // Find applicable discount tier
  for (let discount of discounts) {
    if (days >= discount.minDays && 
        (discount.maxDays === null || days <= discount.maxDays)) {
      return discount;
    }
  }
  
  return null; // No discount applicable
}

function applyDurationDiscount(baseTotal, discount) {
  if (!discount) return baseTotal;
  
  if (discount.discountType === 'percentage') {
    return baseTotal * (1 - discount.discountValue / 100);
  } else if (discount.discountType === 'fixed') {
    return Math.max(0, baseTotal - discount.discountValue);
  }
  
  return baseTotal;
}
```

### 3. Calendar Integration
- Interactive calendar component
- Date selection and pricing input
- Visual pricing indicators
- Responsive design for mobile

## Business Benefits

### 1. Revenue Optimization
- Maximize revenue during high-demand periods
- Competitive pricing during low-demand periods
- Event-based pricing for special occasions
- **Encourage longer rentals** with attractive duration discounts
- **Increase average rental value** through tiered pricing

### 2. Customer Acquisition & Retention
- **Transparent pricing structure** builds customer trust
- **Volume discounts** attract business and long-term customers
- **Competitive advantage** with flexible pricing options
- **Clear savings display** motivates longer bookings

### 3. Operational Efficiency
- Automated pricing based on rules
- Easy override for special circumstances
- Bulk pricing operations
- **Reduced fleet turnover** with longer rentals
- **Lower administrative costs** per rental day

### 4. Market Positioning
- **Premium pricing** for peak periods
- **Value pricing** for extended rentals
- **Competitive rates** for standard durations
- **Flexible pricing** for corporate clients

## Implementation Phases

### Phase 1: Design and Planning
- Create UI mockups
- Define data structures
- Plan user workflows

### Phase 2: Core Implementation
- Build pricing calendar interface
- Implement pricing calculation logic
- Create pricing management forms

### Phase 3: Integration and Testing
- Integrate with existing vehicle management
- Test pricing calculations
- Validate user interface
- Deploy and demonstrate functionality

## Success Metrics
- Ability to set different prices for different day types
- Calendar override functionality working
- **Duration discount tiers configurable and functional**
- **Pricing calculator shows savings for longer rentals**
- Visual pricing calendar displaying correctly
- Pricing calculations accurate based on rules hierarchy
- **Duration discounts applied correctly in final pricing**
- User-friendly interface for pricing management
- **Customer-facing pricing calculator with duration benefits**
- **Real-time pricing updates with discount previews**

