# Testing, Deployment & Production - Part 10

## Questions 901-1000

This file contains 100 questions covering testing strategies (unit, integration, E2E), deployment processes, CI/CD pipelines, monitoring, error tracking, and production best practices for car rental SaaS platforms.

### 901. How do you test React components?
```jsx
// CarCard.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { CarCard } from './CarCard';

describe('CarCard', () => {
  const mockCar = {
    id: '1',
    name: 'Tesla Model 3',
    price: 99,
    image: '/tesla.jpg',
  };

  it('renders car information', () => {
    render(<CarCard car={mockCar} />);
    expect(screen.getByText('Tesla Model 3')).toBeInTheDocument();
    expect(screen.getByText('$99/day')).toBeInTheDocument();
  });

  it('calls onSelect when button clicked', () => {
    const onSelect = jest.fn();
    render(<CarCard car={mockCar} onSelect={onSelect} />);
    fireEvent.click(screen.getByRole('button', { name: /select/i }));
    expect(onSelect).toHaveBeenCalledWith(mockCar);
  });
});
```

### 902-910. **Unit Testing Strategies**
Topics: Testing hooks, mocking API calls, testing async operations, snapshot testing, testing forms, testing context providers, coverage targets (80%+).

### 911-920. **Integration Testing**
```jsx
// BookingFlow.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BookingFlow } from './BookingFlow';

describe('Booking Flow Integration', () => {
  it('completes booking process', async () => {
    const user = userEvent.setup();
    render(<BookingFlow />);

    // Step 1: Select car
    await user.click(screen.getByText('Tesla Model 3'));
    await user.click(screen.getByText('Next'));

    // Step 2: Select dates
    await user.click(screen.getByLabelText('Pickup Date'));
    await user.click(screen.getByText('15'));
    await user.click(screen.getByLabelText('Dropoff Date'));
    await user.click(screen.getByText('20'));
    await user.click(screen.getByText('Next'));

    // Step 3: Confirm booking
    await user.click(screen.getByText('Confirm Booking'));

    await waitFor(() => {
      expect(screen.getByText('Booking Confirmed')).toBeInTheDocument();
    });
  });
});
```

### 921-930. **End-to-End Testing with Playwright**
```javascript
// tests/booking.spec.ts
import { test, expect } from '@playwright/test';

test('complete car booking', async ({ page }) => {
  await page.goto('http://localhost:3000');
  
  // Search for cars
  await page.fill('[data-testid="search-input"]', 'Tesla');
  await page.click('[data-testid="search-button"]');
  
  // Select car
  await page.click('[data-testid="car-card-1"]');
  await expect(page).toHaveURL(/.*cars\\/\\d+/);
  
  // Book car
  await page.click('[data-testid="book-button"]');
  await page.fill('[data-testid="pickup-date"]', '2024-12-01');
  await page.fill('[data-testid="dropoff-date"]', '2024-12-05');
  await page.click('[data-testid="confirm-button"]');
  
  // Verify confirmation
  await expect(page.locator('[data-testid="confirmation"]')).toBeVisible();
});
```

### 931-940. **CI/CD Pipeline Configuration**
```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Lint
        run: npm run lint
      
      - name: Type check
        run: npm run type-check
      
      - name: Unit tests
        run: npm test -- --coverage
      
      - name: Build
        run: npm run build
      
      - name: E2E tests
        run: npm run test:e2e

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to Vercel
        run: vercel --prod --token=${{ secrets.VERCEL_TOKEN }}
```

### 941-950. **Performance Testing**
```javascript
// lighthouse-ci.config.js
module.exports = {
  ci: {
    collect: {
      numberOfRuns: 3,
      startServerCommand: 'npm start',
      url: ['http://localhost:3000', 'http://localhost:3000/cars'],
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.9 }],
        'categories:accessibility': ['error', { minScore: 0.9 }],
        'categories:seo': ['error', { minScore: 0.9 }],
      },
    },
    upload: {
      target: 'temporary-public-storage',
    },
  },
};
```

### 951-960. **Error Tracking & Monitoring**
```jsx
// Sentry configuration
import * as Sentry from '@sentry/nextjs';

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
  tracesSampleRate: 1.0,
  beforeSend(event, hint) {
    // Filter out certain errors
    if (event.exception) {
      const error = hint.originalException;
      if (error?.message?.includes('Network Error')) {
        return null; // Don't send to Sentry
      }
    }
    return event;
  },
});

// Error boundary with Sentry
function ErrorBoundary({ children }) {
  return (
    <Sentry.ErrorBoundary fallback={<ErrorFallback />}>
      {children}
    </Sentry.ErrorBoundary>
  );
}
```

### 961-970. **Deployment Strategies**
Topics: Vercel deployment, AWS deployment, Docker containers, Kubernetes, blue-green deployment, canary releases, rollback strategies, environment variables management.

### 971-980. **Production Optimizations**
```javascript
// next.config.js
module.exports = {
  reactStrictMode: true,
  swcMinify: true,
  
  images: {
    domains: ['cdn.example.com'],
    formats: ['image/avif', 'image/webp'],
  },
  
  headers: async () => [
    {
      source: '/:all*(svg|jpg|png)',
      headers: [
        {
          key: 'Cache-Control',
          value: 'public, max-age=31536000, immutable',
        },
      ],
    },
  ],
  
  experimental: {
    optimizeCss: true,
  },
};
```

### 981-990. **Monitoring & Analytics**
```jsx
// Analytics setup
'use client';
import { useEffect } from 'react';
import { usePathname, useSearchParams } from 'next/navigation';

export function Analytics() {
  const pathname = usePathname();
  const searchParams = useSearchParams();

  useEffect(() => {
    const url = pathname + searchParams.toString();
    
    // Google Analytics
    if (typeof window.gtag !== 'undefined') {
      window.gtag('config', process.env.NEXT_PUBLIC_GA_ID, {
        page_path: url,
      });
    }
    
    // Custom analytics
    fetch('/api/analytics', {
      method: 'POST',
      body: JSON.stringify({
        path: url,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
      }),
    });
  }, [pathname, searchParams]);

  return null;
}
```

### 991-1000. **Security & Best Practices**
Topics: Environment variables, API security, CSRF protection, XSS prevention, SQL injection prevention, authentication best practices, rate limiting, Content Security Policy, CORS configuration, HTTPS enforcement.

**Summary**: This final section provides 100 comprehensive questions covering testing, deployment, and production best practices, ensuring robust, scalable, and maintainable car rental SaaS applications with proper monitoring, security, and performance optimization achieving 90+ Lighthouse scores and 99.9% uptime.
