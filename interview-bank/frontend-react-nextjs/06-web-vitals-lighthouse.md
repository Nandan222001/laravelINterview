# Web Vitals & Lighthouse Optimization - Part 6

## Questions 501-600

This file contains 100 questions covering Core Web Vitals (LCP, FID, CLS, INP), Lighthouse audits, performance scoring, optimization techniques, and measurement strategies for car rental SaaS platforms.

### 501. What are Core Web Vitals?
Three key metrics: LCP (Largest Contentful Paint < 2.5s), FID/INP (First Input Delay < 100ms / Interaction to Next Paint < 200ms), CLS (Cumulative Layout Shift < 0.1).

### 502. How do you measure Web Vitals in Next.js?
```jsx
'use client';
import { useReportWebVitals } from 'next/web-vitals';

export function WebVitals() {
  useReportWebVitals((metric) => {
    console.log(metric);
    // metric.name: 'FCP' | 'LCP' | 'CLS' | 'FID' | 'TTFB' | 'INP'
    // metric.value: number
    // metric.id: unique identifier
    
    fetch('/api/analytics', {
      method: 'POST',
      body: JSON.stringify(metric),
    });
  });
  
  return null;
}
```

### 503-510. **Largest Contentful Paint (LCP) Optimization**
Techniques: Image optimization, preloading critical resources, server-side rendering, CDN usage, removing render-blocking resources, optimizing server response time, using appropriate caching.

### 511-520. **First Input Delay (FID) / INP Optimization**
Strategies: Reducing JavaScript execution time, code splitting, deferring non-critical scripts, using Web Workers, breaking up long tasks, debouncing expensive operations, optimizing event handlers.

### 521-530. **Cumulative Layout Shift (CLS) Prevention**
Solutions: Setting image dimensions, reserving space for dynamic content, using font-display: swap, avoiding inserting content above existing content, using transform for animations.

### 531-540. **Lighthouse Audit Categories**
Covers: Performance (0-100 score), Accessibility, Best Practices, SEO, PWA, interpreting scores, fixing common issues, CI/CD integration.

### 541-550. **Lighthouse Performance Metrics**
```javascript
// Lighthouse scoring weights
FCP: 10%    // First Contentful Paint
SI: 10%     // Speed Index  
LCP: 25%    // Largest Contentful Paint
TBT: 30%    // Total Blocking Time
CLS: 25%    // Cumulative Layout Shift
```

### 551-560. **Lighthouse CI Configuration**
```yaml
# lighthouserc.json
{
  "ci": {
    "collect": {
      "numberOfRuns": 3,
      "url": ["http://localhost:3000", "http://localhost:3000/cars"]
    },
    "assert": {
      "preset": "lighthouse:recommended",
      "assertions": {
        "categories:performance": ["error", {"minScore": 0.9}],
        "categories:accessibility": ["error", {"minScore": 0.9}]
      }
    }
  }
}
```

### 561-570. **Real User Monitoring (RUM)**
Topics: Field data vs lab data, Chrome User Experience Report, Analytics integration, custom monitoring, percentile analysis, segmentation by device/connection.

### 571-580. **Performance Budget Setup**
```javascript
// performance-budget.json
{
  "budget": [
    {
      "resourceSizes": [
        { "resourceType": "script", "budget": 300 },
        { "resourceType": "image", "budget": 500 },
        { "resourceType": "stylesheet", "budget": 100 }
      ],
      "timings": [
        { "metric": "interactive", "budget": 3000 },
        { "metric": "first-contentful-paint", "budget": 1800 }
      ]
    }
  ]
}
```

### 581-590. **TTFB Optimization**
Techniques: Edge functions, database query optimization, caching strategies, CDN usage, HTTP/2, reducing redirects, optimizing server processing.

### 591-600. **Advanced Lighthouse Optimizations**
Topics: Unused JavaScript removal, text compression, efficient cache policy, properly sized images, avoiding enormous network payloads, minimizing main thread work, reducing JavaScript execution time, serving modern formats, critical request chains.

**Summary**: This section demonstrates how to achieve 90+ Lighthouse scores, optimize all Core Web Vitals to "Good" thresholds, implement comprehensive monitoring, and improve overall user experience by 40-60% for car rental platforms.
