# Performance Optimization & Code-Splitting - Part 3

## Questions 201-300

### 201. What is code-splitting in React?
Code-splitting breaks your bundle into smaller chunks loaded on demand, reducing initial load time. React supports it via dynamic imports and React.lazy().

### 202. Implement dynamic imports in Next.js
```jsx
import dynamic from 'next/dynamic';

const DynamicCarMap = dynamic(() => import('./CarMap'), {
  loading: () => <MapSkeleton />,
  ssr: false, // Disable server-side rendering
});

export default function CarPage() {
  return <div><h1>Car Location</h1><DynamicCarMap /></div>;
}
```

### 203. How does React.lazy() work?
```jsx
import { lazy, Suspense } from 'react';

const HeavyComponent = lazy(() => import('./HeavyComponent'));

function App() {
  return (
    <Suspense fallback={<Loading />}>
      <HeavyComponent />
    </Suspense>
  );
}
```

### 204. Demonstrate route-based code-splitting
```jsx
// app/layout.tsx
import { Suspense } from 'react';

const HomePage = lazy(() => import('./pages/Home'));
const SearchPage = lazy(() => import('./pages/Search'));
const BookingPage = lazy(() => import('./pages/Booking'));

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Suspense fallback={<PageLoader />}><HomePage /></Suspense>} />
      <Route path="/search" element={<Suspense fallback={<PageLoader />}><SearchPage /></Suspense>} />
      <Route path="/booking" element={<Suspense fallback={<PageLoader />}><BookingPage /></Suspense>} />
    </Routes>
  );
}
```

### 205. What are the benefits of code-splitting?
1) Reduced initial bundle size (20-40% smaller), 2) Faster initial page load, 3) Better Time to Interactive (TTI), 4) Improved Core Web Vitals scores, 5) Better caching strategy.

### 206. How do you lazy load images?
```jsx
import Image from 'next/image';

export default function CarGallery({ images }) {
  return images.map((src, i) => (
    <Image
      key={i}
      src={src}
      width={800}
      height={600}
      loading="lazy"
      placeholder="blur"
      blurDataURL="data:image/jpeg;base64,/9j/4AAQSkZJRg..."
    />
  ));
}
```

### 207. Implement component-level code-splitting
```jsx
import dynamic from 'next/dynamic';

const CarReviews = dynamic(() => import('./CarReviews'));
const CarSpecs = dynamic(() => import('./CarSpecs'));
const SimilarCars = dynamic(() => import('./SimilarCars'));

export default function CarDetailPage({ car }) {
  return (
    <div>
      <CarHeader car={car} />
      <CarReviews carId={car.id} />
      <CarSpecs specs={car.specs} />
      <SimilarCars category={car.category} />
    </div>
  );
}
```

### 208. How do you preload critical resources?
```jsx
// app/layout.tsx
export default function RootLayout({ children }) {
  return (
    <html>
      <head>
        <link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossOrigin="anonymous" />
        <link rel="preload" href="/api/cars/featured" as="fetch" crossOrigin="anonymous" />
        <link rel="prefetch" href="/cars" />
      </head>
      <body>{children}</body>
    </html>
  );
}
```

### 209. Demonstrate lazy loading with Intersection Observer
```jsx
'use client';
import { useEffect, useRef, useState } from 'react';

export default function LazyCarCard({ car }) {
  const [isVisible, setIsVisible] = useState(false);
  const cardRef = useRef(null);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true);
          observer.disconnect();
        }
      },
      { rootMargin: '100px' }
    );

    if (cardRef.current) {
      observer.observe(cardRef.current);
    }

    return () => observer.disconnect();
  }, []);

  return (
    <div ref={cardRef}>
      {isVisible ? (
        <CarCardFull car={car} />
      ) : (
        <CarCardSkeleton />
      )}
    </div>
  );
}
```

### 210. What is tree shaking?
Tree shaking eliminates dead code from the bundle. Import only what you need:
```jsx
// Bad - imports entire library
import _ from 'lodash';

// Good - imports only needed function
import debounce from 'lodash/debounce';

// Better - use ES6 imports
import { debounce } from 'lodash-es';
```

### 211. How do you optimize bundle size?
1) Use dynamic imports, 2) Tree shake unused code, 3) Use production builds, 4) Minimize dependencies, 5) Use lighter alternatives (date-fns vs moment).

### 212. Implement conditional loading
```jsx
import dynamic from 'next/dynamic';

const AdminPanel = dynamic(() => import('./AdminPanel'), {
  ssr: false,
});

export default function Dashboard({ user }) {
  return (
    <div>
      <h1>Dashboard</h1>
      {user.role === 'admin' && <AdminPanel />}
    </div>
  );
}
```

### 213. How do you split vendor bundles?
```jsx
// next.config.js
module.exports = {
  webpack: (config, { isServer }) => {
    if (!isServer) {
      config.optimization.splitChunks = {
        chunks: 'all',
        cacheGroups: {
          default: false,
          vendors: false,
          commons: {
            name: 'commons',
            chunks: 'all',
            minChunks: 2,
          },
          react: {
            name: 'react',
            test: /[\\\\/]node_modules[\\\\/](react|react-dom)[\\\\/]/,
            priority: 10,
          },
          lib: {
            test: /[\\\\/]node_modules[\\\\/]/,
            name(module) {
              const packageName = module.context.match(
                /[\\\\/]node_modules[\\\\/](.*?)([\\\\/]|$)/
              )[1];
              return `npm.${packageName.replace('@', '')}`;
            },
            priority: 5,
          },
        },
      };
    }
    return config;
  },
};
```

### 214. What is the impact of code-splitting on performance?
Reduces First Contentful Paint by 30-50%, improves Time to Interactive by 40%, reduces initial bundle from 500KB to 150KB, improves Lighthouse score by 20-30 points.

### 215. Implement module preloading
```jsx
import { useEffect } from 'react';

export default function CarCard({ car }) {
  const handleMouseEnter = () => {
    // Preload detail page
    import('./CarDetailPage');
  };

  return (
    <Link 
      href={`/cars/${car.id}`}
      onMouseEnter={handleMouseEnter}
    >
      <h3>{car.name}</h3>
    </Link>
  );
}
```

### 216. How do you analyze bundle size?
```bash
# Install bundle analyzer
npm install --save-dev @next/bundle-analyzer

# Configure in next.config.js
const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
});

module.exports = withBundleAnalyzer({
  // config
});

# Run analysis
ANALYZE=true npm run build
```

### 217. Demonstrate lazy loading third-party scripts
```jsx
import Script from 'next/script';

export default function Page() {
  return (
    <>
      <Script
        src="https://www.googletagmanager.com/gtag/js"
        strategy="lazyOnload"
        onLoad={() => console.log('Script loaded')}
      />
      <Script id="google-analytics" strategy="lazyOnload">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'GA_MEASUREMENT_ID');
        `}
      </Script>
    </>
  );
}
```

### 218. What is prefetching vs preloading?
Preload: High priority, needed soon (fonts, critical images). Prefetch: Low priority, might be needed later (next page resources).

### 219. Implement smart prefetching
```jsx
'use client';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

export default function SmartPrefetch({ routes }) {
  const router = useRouter();

  useEffect(() => {
    // Prefetch likely next routes
    if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
      requestIdleCallback(() => {
        routes.forEach(route => router.prefetch(route));
      });
    }
  }, [routes, router]);

  return null;
}

// Usage
<SmartPrefetch routes={['/cars', '/booking', '/checkout']} />
```

### 220. How do you optimize CSS delivery?
```jsx
// next.config.js - CSS optimization
module.exports = {
  experimental: {
    optimizeCss: true, // Enable CSS optimization
  },
};

// Use CSS modules for component-specific styles
import styles from './CarCard.module.css';

export default function CarCard({ car }) {
  return <div className={styles.card}>{car.name}</div>;
}
```

### 221. Implement critical CSS extraction
```jsx
// app/layout.tsx
export default function RootLayout({ children }) {
  return (
    <html>
      <head>
        <style dangerouslySetInnerHTML={{
          __html: `
            /* Critical CSS for above-the-fold content */
            body { margin: 0; font-family: system-ui; }
            .hero { height: 100vh; background: #000; }
          `
        }} />
      </head>
      <body>{children}</body>
    </html>
  );
}
```

### 222. How do you implement progressive hydration?
```jsx
import dynamic from 'next/dynamic';

const HeavyInteractive = dynamic(() => import('./HeavyInteractive'), {
  loading: () => <StaticVersion />,
  ssr: true, // Render on server
});

export default function Page() {
  return (
    <div>
      <StaticHeader />
      <HeavyInteractive /> {/* Hydrates when visible */}
    </div>
  );
}
```

### 223. Demonstrate font optimization
```jsx
// app/layout.tsx
import { Inter, Roboto_Mono } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
});

const robotoMono = Roboto_Mono({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-roboto-mono',
});

export default function RootLayout({ children }) {
  return (
    <html className={`${inter.variable} ${robotoMono.variable}`}>
      <body className={inter.className}>{children}</body>
    </html>
  );
}
```

### 224. What is selective hydration?
Selective hydration allows React to prioritize hydrating components based on user interaction, hydrating interactive parts first while deferring others.

### 225. Implement resource hints
```jsx
export default function Head() {
  return (
    <>
      <link rel="dns-prefetch" href="https://api.example.com" />
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      <link rel="prefetch" href="/cars" />
    </>
  );
}
```

### 226. How do you optimize JavaScript execution?
1) Use Web Workers for heavy computation, 2) Debounce expensive operations, 3) Use requestIdleCallback, 4) Break up long tasks, 5) Use memoization.

### 227. Implement Web Worker for search
```jsx
// workers/search.worker.js
self.addEventListener('message', (e) => {
  const { cars, query } = e.data;
  const results = cars.filter(car => 
    car.name.toLowerCase().includes(query.toLowerCase())
  );
  self.postMessage(results);
});

// Component
'use client';
export default function Search({ cars }) {
  const [results, setResults] = useState(cars);
  const workerRef = useRef();

  useEffect(() => {
    workerRef.current = new Worker(new URL('./workers/search.worker.js', import.meta.url));
    workerRef.current.onmessage = (e) => setResults(e.data);
    return () => workerRef.current?.terminate();
  }, []);

  const handleSearch = (query) => {
    workerRef.current?.postMessage({ cars, query });
  };

  return <SearchBar onSearch={handleSearch} results={results} />;
}
```

### 228. What are performance budgets?
Set limits for bundle sizes, load times, and metrics. Example: JS bundle < 200KB, FCP < 1.8s, LCP < 2.5s.

### 229. Implement performance monitoring
```jsx
'use client';
import { useReportWebVitals } from 'next/web-vitals';

export function WebVitals() {
  useReportWebVitals((metric) => {
    // Log metrics exceeding budget
    const budgets = {
      FCP: 1800,
      LCP: 2500,
      FID: 100,
      CLS: 0.1,
      TTFB: 600,
    };

    if (metric.value > budgets[metric.name]) {
      console.warn(`${metric.name} exceeded budget:`, metric);
      
      // Send to analytics
      fetch('/api/metrics', {
        method: 'POST',
        body: JSON.stringify(metric),
      });
    }
  });

  return null;
}
```

### 230. How do you optimize third-party scripts?
```jsx
import Script from 'next/script';

export default function Page() {
  return (
    <>
      {/* Load after page interactive */}
      <Script src="https://www.google-analytics.com/analytics.js" strategy="lazyOnload" />
      
      {/* Load after hydration */}
      <Script src="https://widget.intercom.io/widget/123" strategy="afterInteractive" />
      
      {/* Load before interactive */}
      <Script src="/critical-script.js" strategy="beforeInteractive" />
    </>
  );
}
```

### 231. Demonstrate deferred component loading
```jsx
'use client';
import { useState, useEffect } from 'react';
import dynamic from 'next/dynamic';

const HeavyChart = dynamic(() => import('./HeavyChart'), { ssr: false });

export default function Dashboard() {
  const [showChart, setShowChart] = useState(false);

  useEffect(() => {
    // Load chart after initial render
    const timer = setTimeout(() => setShowChart(true), 1000);
    return () => clearTimeout(timer);
  }, []);

  return (
    <div>
      <DashboardHeader />
      <QuickStats />
      {showChart && <HeavyChart />}
    </div>
  );
}
```

### 232. What is render-as-you-fetch?
Fetch data concurrently with rendering, not sequentially. Start fetching before component renders.

### 233. Implement optimistic data fetching
```jsx
// Start fetching immediately
const carDataPromise = fetch('/api/cars/123').then(r => r.json());

// Pass promise to Server Component
export default async function CarPage() {
  const car = await carDataPromise; // Await when needed
  return <CarDetail car={car} />;
}
```

### 234. How do you minimize main thread blocking?
Break long tasks into chunks using scheduler:
```jsx
async function processLargeDataset(data) {
  const chunks = chunkArray(data, 100);
  
  for (const chunk of chunks) {
    await new Promise(resolve => {
      requestIdleCallback(() => {
        processChunk(chunk);
        resolve();
      });
    });
  }
}
```

### 235. Implement virtualized list for performance
```jsx
'use client';
import { FixedSizeList } from 'react-window';

export default function CarList({ cars }) {
  const Row = ({ index, style }) => (
    <div style={style}>
      <CarCard car={cars[index]} />
    </div>
  );

  return (
    <FixedSizeList
      height={800}
      itemCount={cars.length}
      itemSize={200}
      width="100%"
    >
      {Row}
    </FixedSizeList>
  );
}
```

### 236. What is the impact of removing unused CSS?
Reduces CSS bundle by 50-80%, improves FCP by 200-500ms, reduces page weight by 100-300KB.

### 237. Implement CSS purging
```jsx
// postcss.config.js
module.exports = {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
    ...(process.env.NODE_ENV === 'production'
      ? {
          '@fullhuman/postcss-purgecss': {
            content: [
              './app/**/*.{js,jsx,ts,tsx}',
              './components/**/*.{js,jsx,ts,tsx}',
            ],
            defaultExtractor: content => content.match(/[\\w-/:]+(?<!:)/g) || [],
          },
        }
      : {}),
  },
};
```

### 238. How do you implement skeleton screens?
```jsx
export default function CarCardSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="bg-gray-300 h-48 rounded" />
      <div className="mt-2 bg-gray-300 h-4 w-3/4 rounded" />
      <div className="mt-2 bg-gray-300 h-4 w-1/2 rounded" />
    </div>
  );
}

// Usage with Suspense
<Suspense fallback={<CarCardSkeleton />}>
  <CarCard carId={id} />
</Suspense>
```

### 239. Demonstrate image optimization strategies
```jsx
import Image from 'next/image';

export default function OptimizedCarImage({ car }) {
  return (
    <Image
      src={car.image}
      alt={car.name}
      width={800}
      height={600}
      quality={85} // 85% quality (default 75)
      priority={car.featured} // Preload if featured
      placeholder="blur"
      blurDataURL={car.blurDataURL}
      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
    />
  );
}
```

### 240. What is componen-level caching?
Cache expensive component renders to avoid re-computation:
```jsx
import { memo, useMemo } from 'react';

const ExpensiveCarCard = memo(({ car }) => {
  const processedData = useMemo(() => {
    return expensiveCalculation(car);
  }, [car]);

  return <div>{processedData.name}</div>;
}, (prevProps, nextProps) => prevProps.car.id === nextProps.car.id);
```

### 241. How do you implement request waterfall prevention?
```jsx
// Bad - Sequential waterfalls
async function BadComponent() {
  const user = await fetchUser();
  const bookings = await fetchBookings(user.id);
  const cars = await fetchCars(bookings);
  return <View data={{ user, bookings, cars }} />;
}

// Good - Parallel fetching
async function GoodComponent() {
  const [user, bookings, cars] = await Promise.all([
    fetchUser(),
    fetchBookings(),
    fetchCars(),
  ]);
  return <View data={{ user, bookings, cars }} />;
}
```

### 242. Implement data prefetching on hover
```jsx
'use client';
import { useRouter } from 'next/navigation';

export default function CarCard({ car }) {
  const router = useRouter();
  const [prefetched, setPrefetched] = useState(false);

  const handleMouseEnter = () => {
    if (!prefetched) {
      router.prefetch(`/cars/${car.id}`);
      fetch(`/api/cars/${car.id}`); // Prefetch data
      setPrefetched(true);
    }
  };

  return (
    <Link href={`/cars/${car.id}`} onMouseEnter={handleMouseEnter}>
      <CarCardContent car={car} />
    </Link>
  );
}
```

### 243. What are the benefits of SSG over SSR?
SSG: Faster (served from CDN), cheaper (no compute), better SEO, cacheable. Use for static content. SSR: Dynamic data, personalization, real-time updates.

### 244. Implement incremental static regeneration
```jsx
// app/cars/[id]/page.tsx
export const revalidate = 3600; // Revalidate every hour

export default async function CarPage({ params }) {
  const car = await getCar(params.id);
  return <CarDetail car={car} />;
}

export async function generateStaticParams() {
  const cars = await getPopularCars(); // Generate only popular cars
  return cars.map(car => ({ id: car.id }));
}
```

### 245. How do you optimize for mobile devices?
1) Reduce bundle size, 2) Optimize images for mobile, 3) Use responsive images, 4) Minimize main thread work, 5) Use service workers.

### 246. Implement responsive image loading
```jsx
import Image from 'next/image';

export default function ResponsiveCarImage({ car }) {
  return (
    <Image
      src={car.image}
      alt={car.name}
      width={1200}
      height={800}
      sizes="(max-width: 640px) 100vw, 
             (max-width: 1024px) 50vw, 
             33vw"
      quality={85}
    />
  );
}
```

### 247. Demonstrate code splitting by route
```jsx
// Next.js automatically splits by route
// app/cars/page.tsx - Separate bundle
// app/booking/page.tsx - Separate bundle
// app/profile/page.tsx - Separate bundle

// Each route loads only its required JavaScript
```

### 248. What is the impact of memoization?
Prevents unnecessary re-renders and recalculations. Can improve render performance by 40-60% for expensive components.

### 249. Implement comprehensive memoization
```jsx
import { memo, useMemo, useCallback } from 'react';

const CarList = memo(({ cars, onSelect }) => {
  const sortedCars = useMemo(() => {
    return [...cars].sort((a, b) => a.price - b.price);
  }, [cars]);

  const handleSelect = useCallback((carId) => {
    onSelect(carId);
  }, [onSelect]);

  return sortedCars.map(car => (
    <CarCard key={car.id} car={car} onSelect={handleSelect} />
  ));
});
```

### 250. How do you optimize re-renders?
1) Use React.memo, 2) Use useMemo/useCallback, 3) Split components, 4) Use proper key props, 5) Avoid inline objects/functions.

### 251. Implement render optimization
```jsx
// Bad
function Parent() {
  return <Child style={{ color: 'red' }} onClick={() => console.log('click')} />;
}

// Good
const childStyle = { color: 'red' };

function Parent() {
  const handleClick = useCallback(() => console.log('click'), []);
  return <Child style={childStyle} onClick={handleClick} />;
}

const Child = memo(({ style, onClick }) => {
  return <div style={style} onClick={onClick}>Child</div>;
});
```

### 252. What is bundle splitting strategy?
Split by: 1) Routes (automatic in Next.js), 2) Vendors (React, libraries), 3) Common chunks (shared code), 4) Async components.

### 253. Implement efficient data fetching
```jsx
// Use React cache for deduplication
import { cache } from 'react';

const getCar = cache(async (id) => {
  return fetch(`/api/cars/${id}`).then(r => r.json());
});

// Multiple components can call getCar(id) - only one fetch
async function CarHeader({ id }) {
  const car = await getCar(id);
  return <h1>{car.name}</h1>;
}

async function CarPrice({ id }) {
  const car = await getCar(id);
  return <p>${car.price}</p>;
}
```

### 254. How do you measure component performance?
```jsx
import { Profiler } from 'react';

function onRenderCallback(id, phase, actualDuration) {
  if (actualDuration > 16) { // > 1 frame
    console.warn(`Slow render: ${id}`, actualDuration);
  }
}

export default function App() {
  return (
    <Profiler id="App" onRender={onRenderCallback}>
      <CarList />
    </Profiler>
  );
}
```

### 255. Implement performance logging
```jsx
'use client';

export function PerformanceLogger() {
  useEffect(() => {
    const observer = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry) => {
        console.log({
          name: entry.name,
          duration: entry.duration,
          startTime: entry.startTime,
        });

        // Log slow operations
        if (entry.duration > 50) {
          fetch('/api/performance', {
            method: 'POST',
            body: JSON.stringify(entry),
          });
        }
      });
    });

    observer.observe({ entryTypes: ['measure', 'navigation'] });

    return () => observer.disconnect();
  }, []);

  return null;
}
```

### 256. What is the impact of reducing JavaScript?
Every 100KB of JS removed improves TTI by 200-300ms on mobile, reduces parse time by 50-100ms, improves FID score.

### 257. Implement lightweight alternatives
```jsx
// Heavy (moment.js ~72KB)
import moment from 'moment';
const date = moment().format('YYYY-MM-DD');

// Light (date-fns ~15KB)
import { format } from 'date-fns';
const date = format(new Date(), 'yyyy-MM-dd');

// Lighter (native)
const date = new Date().toISOString().split('T')[0];
```

### 258. How do you optimize asset delivery?
1) Use CDN, 2) Enable compression (gzip/brotli), 3) Set cache headers, 4) Use modern formats (WebP, AVIF), 5) Implement lazy loading.

### 259. Implement service worker for caching
```jsx
// public/sw.js
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open('car-rental-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/cars',
        '/manifest.json',
        '/offline.html',
      ]);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});

// app/layout.tsx
export default function RootLayout({ children }) {
  useEffect(() => {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js');
    }
  }, []);

  return <html><body>{children}</body></html>;
}
```

### 260. What is the 50ms budget for interactions?
User interactions should respond within 50ms to feel instant. Use debouncing and transitions for expensive updates.

### 261. Implement debounced search
```jsx
'use client';
import { useState, useCallback } from 'react';
import { debounce } from 'lodash';

export default function SearchBar() {
  const [results, setResults] = useState([]);

  const search = useCallback(
    debounce(async (query) => {
      const data = await fetch(`/api/search?q=${query}`).then(r => r.json());
      setResults(data);
    }, 300),
    []
  );

  return (
    <div>
      <input onChange={(e) => search(e.target.value)} />
      <Results items={results} />
    </div>
  );
}
```

### 262. How do you reduce Time to First Byte (TTFB)?
1) Use edge functions, 2) Implement caching, 3) Optimize database queries, 4) Use CDN, 5) Enable HTTP/2 or HTTP/3.

### 263. Implement edge caching
```jsx
// middleware.ts
import { NextResponse } from 'next/server';

export function middleware(request) {
  const response = NextResponse.next();
  
  // Cache static pages for 1 hour
  if (request.nextUrl.pathname.startsWith('/cars/')) {
    response.headers.set(
      'Cache-Control',
      'public, s-maxage=3600, stale-while-revalidate=86400'
    );
  }
  
  return response;
}
```

### 264. What is resource prioritization?
Load critical resources first: fonts, above-fold images, critical CSS. Defer non-critical: analytics, chat widgets, below-fold content.

### 265. Implement priority hints
```jsx
import Image from 'next/image';

export default function HomePage() {
  return (
    <div>
      {/* High priority - above fold */}
      <Image src="/hero.jpg" alt="Hero" priority width={1920} height={1080} />
      
      {/* Normal priority */}
      <Image src="/car1.jpg" alt="Car 1" width={800} height={600} />
      
      {/* Low priority - below fold */}
      <Image src="/footer-logo.jpg" alt="Logo" loading="lazy" width={200} height={50} />
    </div>
  );
}
```

### 266. How do you optimize font loading?
```jsx
// app/layout.tsx
import { Inter } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap', // Avoid FOIT (Flash of Invisible Text)
  preload: true,
  fallback: ['system-ui', 'arial'],
  adjustFontFallback: true, // Reduce CLS
});

export default function RootLayout({ children }) {
  return (
    <html className={inter.className}>
      <body>{children}</body>
    </html>
  );
}
```

### 267. Demonstrate compression configuration
```jsx
// next.config.js
module.exports = {
  compress: true, // Enable gzip compression
  
  async headers() {
    return [
      {
        source: '/:all*(svg|jpg|png|webp|avif)',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
    ];
  },
};
```

### 268. What is the impact of HTTP/2?
HTTP/2 enables multiplexing (multiple requests over one connection), header compression, server push. Reduces latency by 20-30%.

### 269. Implement connection optimization
```jsx
export default function Head() {
  return (
    <>
      {/* Early connection to API */}
      <link rel="preconnect" href="https://api.example.com" />
      <link rel="dns-prefetch" href="https://cdn.example.com" />
      
      {/* Preload critical resources */}
      <link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossOrigin="" />
      <link rel="preload" href="/hero.jpg" as="image" />
    </>
  );
}
```

### 270. How do you handle slow networks?
1) Show loading states, 2) Implement offline mode, 3) Use service workers, 4) Progressive enhancement, 5) Reduce payload size.

### 271. Implement network-aware loading
```jsx
'use client';

export default function NetworkAwareImage({ src, alt }) {
  const [connection, setConnection] = useState(null);

  useEffect(() => {
    if ('connection' in navigator) {
      const conn = navigator.connection;
      setConnection(conn);
      
      const handleChange = () => setConnection({...conn});
      conn.addEventListener('change', handleChange);
      return () => conn.removeEventListener('change', handleChange);
    }
  }, []);

  const quality = connection?.effectiveType === '4g' ? 90 : 50;

  return (
    <Image
      src={src}
      alt={alt}
      quality={quality}
      loading={connection?.saveData ? 'lazy' : 'eager'}
      width={800}
      height={600}
    />
  );
}
```

### 272. What is adaptive loading?
Serve different experiences based on device capabilities, network speed, and user preferences.

### 273. Implement adaptive component loading
```jsx
'use client';

export default function AdaptiveCarGallery({ images }) {
  const [deviceCapability, setDeviceCapability] = useState('high');

  useEffect(() => {
    const memory = navigator.deviceMemory || 4;
    const connection = navigator.connection?.effectiveType || '4g';
    const saveData = navigator.connection?.saveData || false;

    if (memory < 4 || saveData || connection === 'slow-2g' || connection === '2g') {
      setDeviceCapability('low');
    } else if (memory < 8 || connection === '3g') {
      setDeviceCapability('medium');
    }
  }, []);

  if (deviceCapability === 'low') {
    return <SimpleThumbnailGallery images={images.slice(0, 4)} />;
  }

  if (deviceCapability === 'medium') {
    return <StandardGallery images={images.slice(0, 12)} />;
  }

  return <HighResGallery images={images} />;
}
```

### 274. How do you prevent layout shifts?
1) Set dimensions for images/videos, 2) Reserve space for dynamic content, 3) Use font fallbacks, 4) Avoid inserting content above existing content.

### 275. Implement CLS prevention
```jsx
// Set explicit dimensions
<Image 
  src="/car.jpg" 
  width={800} 
  height={600} 
  alt="Car"
/>

// Reserve space for dynamic content
<div style={{ minHeight: '400px' }}>
  <Suspense fallback={<Skeleton height={400} />}>
    <DynamicContent />
  </Suspense>
</div>

// Use aspect ratio
<div style={{ aspectRatio: '16/9', width: '100%' }}>
  <video src="/video.mp4" />
</div>
```

### 276. What is the Rail Model?
Response (50ms), Animation (16ms per frame), Idle (50ms chunks), Load (< 1000ms to interactive).

### 277. Implement RAIL-optimized interactions
```jsx
'use client';

export default function OptimizedSearch() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);

  // Response: Update input immediately (< 50ms)
  const handleInput = (e) => {
    setQuery(e.target.value);
    scheduleSearch(e.target.value);
  };

  // Idle: Defer heavy work
  const scheduleSearch = useCallback(
    debounce((value) => {
      if ('requestIdleCallback' in window) {
        requestIdleCallback(() => performSearch(value));
      } else {
        setTimeout(() => performSearch(value), 0);
      }
    }, 300),
    []
  );

  const performSearch = async (value) => {
    const data = await fetch(`/api/search?q=${value}`).then(r => r.json());
    // Animation: Update UI smoothly
    requestAnimationFrame(() => setResults(data));
  };

  return (
    <div>
      <input value={query} onChange={handleInput} />
      <Results items={results} />
    </div>
  );
}
```

### 278. How do you optimize for Core Web Vitals?
LCP: Optimize images, remove render-blocking resources. FID: Minimize JS, use web workers. CLS: Set dimensions, avoid dynamic content insertion.

### 279. Implement Web Vitals monitoring
```jsx
'use client';
import { useReportWebVitals } from 'next/web-vitals';

export function WebVitalsReporter() {
  useReportWebVitals((metric) => {
    // Grades based on Core Web Vitals thresholds
    const grades = {
      FCP: { good: 1800, poor: 3000 },
      LCP: { good: 2500, poor: 4000 },
      FID: { good: 100, poor: 300 },
      CLS: { good: 0.1, poor: 0.25 },
      TTFB: { good: 800, poor: 1800 },
      INP: { good: 200, poor: 500 },
    };

    const threshold = grades[metric.name];
    let grade = 'good';
    
    if (metric.value > threshold.poor) grade = 'poor';
    else if (metric.value > threshold.good) grade = 'needs-improvement';

    console.log(`${metric.name}: ${metric.value.toFixed(2)} (${grade})`);

    // Send to analytics
    if (typeof window.gtag !== 'undefined') {
      window.gtag('event', metric.name, {
        value: Math.round(metric.value),
        metric_id: metric.id,
        metric_grade: grade,
      });
    }
  });

  return null;
}
```

### 280. What is the impact of reducing payload size?
Every 100KB reduction improves load time by 200-400ms on 3G, reduces data costs for users, improves mobile performance by 30-50%.

### 281. Implement payload optimization
```jsx
// Use compression
// next.config.js
module.exports = {
  compress: true,
  
  webpack: (config) => {
    if (process.env.NODE_ENV === 'production') {
      config.optimization.minimize = true;
      config.optimization.usedExports = true; // Tree shaking
    }
    return config;
  },
};

// Optimize API responses
export async function GET() {
  const cars = await db.car.findMany({
    select: {
      id: true,
      name: true,
      price: true,
      image: true,
      // Exclude large fields like description
    },
  });
  
  return NextResponse.json(cars);
}
```

### 282. How do you implement streaming responses?
```jsx
// app/api/cars/stream/route.ts
export async function GET() {
  const encoder = new TextEncoder();
  
  const stream = new ReadableStream({
    async start(controller) {
      const cars = await db.car.findMany();
      
      for (const car of cars) {
        const chunk = encoder.encode(JSON.stringify(car) + '\\n');
        controller.enqueue(chunk);
        
        // Small delay to demonstrate streaming
        await new Promise(r => setTimeout(r, 100));
      }
      
      controller.close();
    },
  });

  return new Response(stream, {
    headers: {
      'Content-Type': 'text/plain',
      'Transfer-Encoding': 'chunked',
    },
  });
}
```

### 283. Demonstrate batch processing for performance
```jsx
'use server';

export async function batchProcessBookings(bookings) {
  const BATCH_SIZE = 10;
  const results = [];

  for (let i = 0; i < bookings.length; i += BATCH_SIZE) {
    const batch = bookings.slice(i, i + BATCH_SIZE);
    
    const batchResults = await Promise.all(
      batch.map(booking => processBooking(booking))
    );
    
    results.push(...batchResults);
    
    // Yield to main thread between batches
    if (i + BATCH_SIZE < bookings.length) {
      await new Promise(resolve => setTimeout(resolve, 0));
    }
  }

  return results;
}
```

### 284. What is the importance of First Input Delay (FID)?
FID measures responsiveness. Target < 100ms. Long tasks block main thread causing poor FID. Break up JavaScript execution.

### 285. Implement FID optimization
```jsx
// Break up long tasks
async function processLargeList(items) {
  const CHUNK_SIZE = 50;
  
  for (let i = 0; i < items.length; i += CHUNK_SIZE) {
    const chunk = items.slice(i, i + CHUNK_SIZE);
    
    await new Promise(resolve => {
      setTimeout(() => {
        processChunk(chunk);
        resolve();
      }, 0);
    });
  }
}

// Use scheduler API (when available)
async function scheduleWork(callback) {
  if ('scheduler' in window && 'yield' in scheduler) {
    await scheduler.yield();
    callback();
  } else {
    setTimeout(callback, 0);
  }
}
```

### 286. How do you optimize Largest Contentful Paint (LCP)?
1) Optimize images, 2) Preload key resources, 3) Remove render-blocking CSS/JS, 4) Use CDN, 5) Implement SSR/SSG.

### 287. Implement LCP optimization
```jsx
// app/page.tsx
import Image from 'next/image';

export default function HomePage() {
  return (
    <div>
      {/* Hero image is LCP element - prioritize it */}
      <Image
        src="/hero-car.jpg"
        alt="Featured Car"
        width={1920}
        height={1080}
        priority // Preload this image
        quality={90}
        placeholder="blur"
        blurDataURL="data:image/jpeg;base64,..."
      />
      <h1>Rent the Perfect Car</h1>
    </div>
  );
}

// Preload critical resources
export function HeroPreload() {
  return (
    <link
      rel="preload"
      as="image"
      href="/hero-car.jpg"
      imageSrcSet="/hero-car-800.jpg 800w, /hero-car-1200.jpg 1200w, /hero-car-1920.jpg 1920w"
      imageSizes="100vw"
    />
  );
}
```

### 288. What is Cumulative Layout Shift (CLS)?
CLS measures visual stability. Target < 0.1. Caused by images without dimensions, dynamic content insertion, web fonts.

### 289. Implement CLS optimization
```jsx
// Always set image dimensions
import Image from 'next/image';

<Image 
  src="/car.jpg"
  width={800}
  height={600}
  alt="Car"
  style={{ width: '100%', height: 'auto' }}
/>

// Reserve space for dynamic content
<div style={{ minHeight: '200px' }}>
  <Suspense fallback={<Skeleton height={200} />}>
    <DynamicWidget />
  </Suspense>
</div>

// Use font-display: swap with fallback
import { Inter } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  adjustFontFallback: true, // Match fallback metrics
});
```

### 290. How do you optimize Time to Interactive (TTI)?
1) Reduce JavaScript, 2) Code-split, 3) Defer non-critical scripts, 4) Minimize main thread work, 5) Use server rendering.

### 291. Implement TTI optimization
```jsx
// Lazy load non-critical components
import dynamic from 'next/dynamic';

const ChatWidget = dynamic(() => import('./ChatWidget'), {
  ssr: false,
  loading: () => null,
});

const Analytics = dynamic(() => import('./Analytics'), {
  ssr: false,
});

export default function Layout({ children }) {
  return (
    <div>
      {children}
      <ChatWidget />
      <Analytics />
    </div>
  );
}

// Defer third-party scripts
import Script from 'next/script';

<Script
  src="https://www.googletagmanager.com/gtag/js"
  strategy="lazyOnload" // Load after page is interactive
/>
```

### 292. What is Interaction to Next Paint (INP)?
INP measures responsiveness to user interactions throughout page lifetime. Target < 200ms. Replaces FID.

### 293. Implement INP optimization
```jsx
'use client';

export default function OptimizedButton({ onClick }) {
  const handleClick = async (e) => {
    // Show immediate feedback
    e.target.classList.add('active');
    
    // Defer heavy work
    await new Promise(resolve => {
      requestAnimationFrame(() => {
        setTimeout(() => {
          onClick();
          resolve();
        }, 0);
      });
    });
    
    e.target.classList.remove('active');
  };

  return (
    <button onClick={handleClick} className="transition-transform active:scale-95">
      Book Now
    </button>
  );
}
```

### 294. How do you measure bundle size impact?
Use bundle analyzer, measure before/after, track over time, set budgets, monitor in CI/CD.

### 295. Implement bundle size monitoring
```bash
# package.json
{
  "scripts": {
    "analyze": "ANALYZE=true next build",
    "size": "size-limit"
  },
  "size-limit": [
    {
      "path": ".next/static/chunks/pages/index.js",
      "limit": "150 KB"
    },
    {
      "path": ".next/static/chunks/pages/_app.js",
      "limit": "200 KB"
    }
  ]
}
```

### 296. What is the 14KB initial budget?
Aim for < 14KB of critical CSS/JS (gzipped) for first render to fit in first TCP roundtrip (14KB in slow 3G).

### 297. Implement critical CSS extraction
```jsx
// Inline critical CSS
export default function RootLayout({ children }) {
  return (
    <html>
      <head>
        <style dangerouslySetInnerHTML={{
          __html: `
            body{margin:0;font-family:system-ui}
            .header{background:#000;color:#fff;padding:1rem}
            .hero{height:100vh;background:url(/hero.jpg)}
          `
        }} />
      </head>
      <body>{children}</body>
    </html>
  );
}
```

### 298. How do you optimize database queries?
1) Use indexes, 2) Limit selected fields, 3) Batch requests, 4) Use caching, 5) Implement pagination.

### 299. Implement optimized data fetching
```jsx
// Select only needed fields
export async function getCars() {
  return db.car.findMany({
    select: {
      id: true,
      name: true,
      price: true,
      image: true,
    },
    take: 20, // Limit results
    where: {
      available: true,
    },
  });
}

// Use caching
import { cache } from 'react';

export const getCachedCars = cache(async () => {
  return fetch('/api/cars', {
    next: { revalidate: 3600, tags: ['cars'] }
  }).then(r => r.json());
});
```

### 300. What is the performance impact summary?
Implementing all optimizations: 40-60% faster load times, 30-50% smaller bundles, 20-30 point Lighthouse improvement, 50-70% better mobile performance, $20-$30% reduction in hosting costs.
