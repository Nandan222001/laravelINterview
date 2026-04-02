# Next.js 14 App Router Architecture - Part 2

## Questions 101-200

### 101. What is the Next.js 14 App Router?
The App Router is a new routing system in Next.js 14 built on React Server Components, supporting layouts, nested routes, loading states, and error handling with a file-system based approach in the `app` directory.

### 102. What are Server Components in Next.js 14?
Server Components render on the server by default, reducing JavaScript bundle size, enabling direct database access, and improving initial page load performance.

### 103. How do you create a Client Component in Next.js 14?
```jsx
'use client';

import { useState } from 'react';

export default function CarFilter() {
  const [filters, setFilters] = useState({});
  return <input onChange={(e) => setFilters({ search: e.target.value })} />;
}
```

### 104. Demonstrate nested layouts in Next.js 14
```jsx
// app/layout.tsx - Root layout
export default function RootLayout({ children }) {
  return <html><body><Header />{children}<Footer /></body></html>;
}

// app/dashboard/layout.tsx - Nested layout
export default function DashboardLayout({ children }) {
  return <div className="dashboard"><Sidebar /><main>{children}</main></div>;
}
```

### 105. How does file-based routing work?
File structure determines routes: `app/page.tsx` → `/`, `app/cars/page.tsx` → `/cars`, `app/cars/[id]/page.tsx` → `/cars/:id`

### 106. Create a dynamic route for car details
```jsx
// app/cars/[id]/page.tsx
export default async function CarDetailPage({ params }) {
  const car = await db.car.findUnique({ where: { id: params.id } });
  return <div><h1>{car.name}</h1><p>${car.price}/day</p></div>;
}

export async function generateMetadata({ params }) {
  const car = await getCarById(params.id);
  return { title: `${car.name} - Car Rental`, description: car.description };
}
```

### 107. What are loading.tsx files?
```jsx
// app/cars/loading.tsx
export default function Loading() {
  return <div className="skeleton"><div className="skeleton-card" /></div>;
}
```

### 108. How do error.tsx files work?
```jsx
// app/cars/error.tsx
'use client';

export default function Error({ error, reset }) {
  return <div><h2>Something went wrong!</h2><button onClick={reset}>Try again</button></div>;
}
```

### 109. Implement route groups
Route groups organize routes without affecting URL structure using parentheses:
```
app/(marketing)/about/page.tsx → /about
app/(shop)/cars/page.tsx → /cars
```

### 110. What are parallel routes?
```jsx
// app/dashboard/layout.tsx
export default function Layout({ children, analytics, team }) {
  return <>{children}{analytics}{team}</>;
}
// app/dashboard/@analytics/page.tsx
// app/dashboard/@team/page.tsx
```

### 111. How do you fetch data in Server Components?
```jsx
async function getCars() {
  const res = await fetch('https://api.example.com/cars', { next: { revalidate: 3600 } });
  return res.json();
}

export default async function CarsPage() {
  const cars = await getCars();
  return <CarGrid cars={cars} />;
}
```

### 112. How do you implement Server Actions?
```jsx
'use server';
import { revalidatePath } from 'next/cache';

export async function bookCar(formData) {
  const carId = formData.get('carId');
  await db.booking.create({ data: { carId, status: 'pending' } });
  revalidatePath('/bookings');
}
```

### 113. Implement Route Handlers
```jsx
// app/api/cars/route.ts
export async function GET(request) {
  const cars = await db.car.findMany();
  return NextResponse.json(cars);
}

export async function POST(request) {
  const body = await request.json();
  const car = await db.car.create({ data: body });
  return NextResponse.json(car, { status: 201 });
}
```

### 114. What is middleware in Next.js 14?
```jsx
// middleware.ts
export function middleware(request) {
  const token = request.cookies.get('token');
  if (!token && request.nextUrl.pathname.startsWith('/dashboard')) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
  return NextResponse.next();
}

export const config = { matcher: ['/dashboard/:path*'] };
```

### 115. How do you implement streaming with Suspense?
```jsx
export default function CarsPage() {
  return (
    <div>
      <Suspense fallback={<FeaturedSkeleton />}><FeaturedCars /></Suspense>
      <Suspense fallback={<CarListSkeleton />}><CarList /></Suspense>
    </div>
  );
}
```

### 116. What are generateStaticParams for?
```jsx
export async function generateStaticParams() {
  const cars = await db.car.findMany();
  return cars.map((car) => ({ id: car.id }));
}
```

### 117. How do you implement dynamic metadata?
```jsx
export async function generateMetadata({ params }) {
  const car = await getCarById(params.id);
  return {
    title: car.name,
    description: car.description,
    openGraph: { images: [car.image] },
  };
}
```

### 118. What is route segment config?
```jsx
export const dynamic = 'force-dynamic';
export const revalidate = 3600;
export const runtime = 'nodejs';
```

### 119. How do you use search params?
```jsx
export default async function CarsPage({ searchParams }) {
  const cars = await db.car.findMany({ where: { type: searchParams.type } });
  return <CarList cars={cars} />;
}
```

### 120. What is useSearchParams in Client Components?
```jsx
'use client';
import { useSearchParams, useRouter } from 'next/navigation';

export default function CarFilters() {
  const router = useRouter();
  const searchParams = useSearchParams();
  
  const updateFilter = (key, value) => {
    const params = new URLSearchParams(searchParams);
    params.set(key, value);
    router.push(`?${params.toString()}`);
  };
  
  return <select onChange={(e) => updateFilter('type', e.target.value)} />;
}
```

### 121. What is the useRouter hook?
```jsx
'use client';
import { useRouter } from 'next/navigation';

export default function BookingButton({ carId }) {
  const router = useRouter();
  const handleBook = async () => {
    await bookCar(carId);
    router.push('/bookings');
    router.refresh();
  };
  return <button onClick={handleBook}>Book Now</button>;
}
```

### 122. How do you implement prefetching?
```jsx
import Link from 'next/link';
<Link href={`/cars/${car.id}`} prefetch={true}><h3>{car.name}</h3></Link>
```

### 123. Implement optimistic updates with Server Actions
```jsx
'use client';
import { useOptimistic } from 'react';

export default function BookingList({ bookings }) {
  const [optimisticBookings, addOptimistic] = useOptimistic(
    bookings,
    (state, newBooking) => [...state, { ...newBooking, pending: true }]
  );
  return <div>{optimisticBookings.map(b => <div key={b.id}>{b.carId}</div>)}</div>;
}
```

### 124. How do you handle cookies?
```jsx
import { cookies } from 'next/headers';

export default async function ProfilePage() {
  const cookieStore = cookies();
  const token = cookieStore.get('token');
  return <div>Token: {token?.value}</div>;
}
```

### 125. What is the redirect function?
```jsx
import { redirect } from 'next/navigation';

export default async function CarPage({ params }) {
  const car = await getCarById(params.id);
  if (!car) redirect('/cars');
  return <CarDetail car={car} />;
}
```

### 126. Implement authentication in App Router
```jsx
// middleware.ts
export async function middleware(request) {
  const token = request.cookies.get('token')?.value;
  if (!token) return NextResponse.redirect(new URL('/login', request.url));
  return NextResponse.next();
}

export const config = { matcher: ['/dashboard/:path*'] };
```

### 127. What are the caching strategies?
1) Full Route Cache (static), 2) Request Memoization, 3) Data Cache (fetch), 4) Router Cache (client-side).

### 128. How do you opt out of caching?
```jsx
fetch('https://api.example.com/cars', { cache: 'no-store' });
export const dynamic = 'force-dynamic';
export const revalidate = 0;
```

### 129. Implement pagination
```jsx
export default async function CarsPage({ searchParams }) {
  const page = parseInt(searchParams.page || '1');
  const cars = await db.car.findMany({ skip: (page - 1) * 20, take: 20 });
  return <div><CarGrid cars={cars} /><Pagination page={page} /></div>;
}
```

### 130. How do you implement sitemap.xml?
```jsx
// app/sitemap.ts
export default async function sitemap() {
  const cars = await db.car.findMany();
  return cars.map(car => ({
    url: `https://example.com/cars/${car.id}`,
    lastModified: car.updatedAt,
  }));
}
```

### 131. How do you create robots.txt?
```jsx
// app/robots.ts
export default function robots() {
  return { rules: { userAgent: '*', allow: '/', disallow: '/admin/' } };
}
```

### 132. Implement i18n
```jsx
// middleware.ts
export function middleware(request) {
  const locale = getLocale(request);
  request.nextUrl.pathname = `/${locale}${request.nextUrl.pathname}`;
  return NextResponse.redirect(request.nextUrl);
}
```

### 133. What are route handlers with edge runtime?
```jsx
export const runtime = 'edge';
export async function GET() {
  return new Response(JSON.stringify({ message: 'Hello' }));
}
```

### 134. How do you handle webhooks?
```jsx
export async function POST(req) {
  const body = await req.text();
  const signature = req.headers.get('stripe-signature');
  const event = stripe.webhooks.constructEvent(body, signature, secret);
  return NextResponse.json({ received: true });
}
```

### 135. What is useSelectedLayoutSegment?
```jsx
'use client';
import { useSelectedLayoutSegment } from 'next/navigation';

export default function Navigation() {
  const segment = useSelectedLayoutSegment();
  return <Link className={segment === 'cars' ? 'active' : ''}>Cars</Link>;
}
```

### 136. Implement draft mode
```jsx
import { draftMode } from 'next/headers';

export default async function CarPage({ params }) {
  const { isEnabled } = draftMode();
  const car = await getCar(params.id, isEnabled);
  return <CarDetail car={car} />;
}
```

### 137. How do you share data between Server Components?
```jsx
import { cache } from 'react';

const getUser = cache(async (id) => db.user.findUnique({ where: { id } }));
```

### 138. Implement multi-step form
```jsx
'use server';
import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

export async function saveStep(formData) {
  const data = { carId: formData.get('carId') };
  cookies().set('booking-data', JSON.stringify(data));
  redirect('/booking/dates');
}
```

### 139. How do you handle file uploads?
```jsx
'use server';
import { writeFile } from 'fs/promises';

export async function uploadImage(formData) {
  const file = formData.get('file');
  const bytes = await file.arrayBuffer();
  await writeFile(`/uploads/${file.name}`, Buffer.from(bytes));
  return { url: `/uploads/${file.name}` };
}
```

### 140. What is usePathname?
```jsx
'use client';
import { usePathname } from 'next/navigation';

export default function Breadcrumbs() {
  const pathname = usePathname();
  return <nav>{pathname.split('/').map(s => <Link href={s}>{s}</Link>)}</nav>;
}
```

### 141. Implement rate limiting
```jsx
const rateLimit = new Map();

export function checkRateLimit(request, limit = 10) {
  const ip = request.ip || 'unknown';
  const record = rateLimit.get(ip) || { count: 0, resetTime: Date.now() + 60000 };
  if (record.count >= limit) return { allowed: false };
  record.count++;
  rateLimit.set(ip, record);
  return { allowed: true };
}
```

### 142. How do you implement CORS?
```jsx
export async function GET() {
  return NextResponse.json(data, {
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST',
    },
  });
}
```

### 143. What is notFound()?
```jsx
import { notFound } from 'next/navigation';

export default async function CarPage({ params }) {
  const car = await getCar(params.id);
  if (!car) notFound();
  return <CarDetail car={car} />;
}
```

### 144. Implement CSP
```jsx
export function middleware(request) {
  const csp = "default-src 'self'; script-src 'self' 'nonce-123';";
  const response = NextResponse.next();
  response.headers.set('Content-Security-Policy', csp);
  return response;
}
```

### 145. How do you implement A/B testing?
```jsx
export function middleware(request) {
  const bucket = Math.random() < 0.5 ? 'A' : 'B';
  const response = NextResponse.next();
  response.cookies.set('bucket', bucket);
  return response;
}
```

### 146. What are catch-all routes?
```jsx
// app/docs/[...slug]/page.tsx
export default function DocsPage({ params }) {
  const path = params.slug.join('/');
  return <Doc path={path} />;
}
```

### 147. What are optional catch-all routes?
```jsx
// app/shop/[[...slug]]/page.tsx
export default function ShopPage({ params }) {
  if (!params.slug) return <AllCategories />;
  return <Category category={params.slug.join('/')} />;
}
```

### 148. Implement infinite scroll
```jsx
'use client';
export default function InfiniteCarList({ initialCars }) {
  const [cars, setCars] = useState(initialCars);
  const loadMore = async () => {
    const newCars = await fetch(`/api/cars?page=${page}`).then(r => r.json());
    setCars([...cars, ...newCars]);
  };
  return <div>{cars.map(c => <CarCard car={c} />)}</div>;
}
```

### 149. How do you implement form validation?
```jsx
'use server';
import { z } from 'zod';

const schema = z.object({ email: z.string().email() });

export async function createBooking(formData) {
  const result = schema.safeParse({ email: formData.get('email') });
  if (!result.success) return { errors: result.error.flatten() };
  await db.booking.create({ data: result.data });
}
```

### 150. Implement session management
```jsx
import { cookies } from 'next/headers';
import { SignJWT, jwtVerify } from 'jose';

export async function createSession(userId) {
  const token = await new SignJWT({ userId }).setExpirationTime('7d').sign(secret);
  cookies().set('session', token, { httpOnly: true });
}
```

### 151. How do you implement progressive enhancement?
```jsx
// Works without JS
<form action="/api/bookings" method="POST">
  <input name="carId" required />
  <button type="submit">Book</button>
</form>

// Enhanced with Server Actions when JS available
'use client';
export default function EnhancedForm() {
  return <form action={createBooking}><button>Book</button></form>;
}
```

### 152. Implement feature flags
```jsx
export async function isFeatureEnabled(feature) {
  const flag = await db.featureFlag.findUnique({ where: { name: feature } });
  return flag?.enabled || false;
}

export default async function HomePage() {
  const showNewDesign = await isFeatureEnabled('new-hero');
  return <div>{showNewDesign ? <NewHero /> : <OldHero />}</div>;
}
```

### 153. How do you implement request deduplication?
```jsx
import { cache } from 'react';

const getCar = cache(async (id) => {
  console.log('Fetching car:', id); // Only logs once per request
  return db.car.findUnique({ where: { id } });
});
```

### 154. Implement protected routes
```jsx
// app/dashboard/layout.tsx
import { getSession } from '@/lib/session';

export default async function DashboardLayout({ children }) {
  const session = await getSession();
  if (!session) redirect('/login');
  return <div className="dashboard">{children}</div>;
}
```

### 155. How do you implement RBAC?
```jsx
export async function requireRole(role) {
  const session = await getSession();
  if (!session || session.user.role !== role) redirect('/unauthorized');
  return session;
}

export default async function AdminPage() {
  await requireRole('admin');
  return <AdminDashboard />;
}
```

### 156. Implement cache tags
```jsx
async function getCars() {
  return fetch('https://api.example.com/cars', {
    next: { tags: ['cars'], revalidate: 3600 }
  });
}

export async function updateCar(id, data) {
  await db.car.update({ where: { id }, data });
  revalidateTag(`car-${id}`);
  revalidateTag('cars');
}
```

### 157. How do you handle loading skeletons?
```jsx
// app/cars/loading.tsx
export default function Loading() {
  return Array(12).fill(null).map((_, i) => <CarCardSkeleton key={i} />);
}
```

### 158. Implement custom error pages
```jsx
// app/error.tsx
'use client';
export default function Error({ error, reset }) {
  return <div><h2>Error!</h2><button onClick={reset}>Retry</button></div>;
}
```

### 159. What is permanentRedirect?
```jsx
import { permanentRedirect } from 'next/navigation';
export default async function OldPage() {
  permanentRedirect('/new-page'); // 308 redirect
}
```

### 160. How do you implement opengraph images?
```jsx
// app/cars/[id]/opengraph-image.tsx
import { ImageResponse } from 'next/og';

export default async function Image({ params }) {
  const car = await getCarById(params.id);
  return new ImageResponse(<div style={{ fontSize: 60 }}>{car.name}</div>);
}
```

### 161. Implement analytics tracking
```jsx
'use client';
import { usePathname } from 'next/navigation';

export function Analytics() {
  const pathname = usePathname();
  useEffect(() => {
    gtag('config', 'GA_ID', { page_path: pathname });
  }, [pathname]);
  return null;
}
```

### 162. How do you implement theme switching?
```jsx
'use client';
import { ThemeProvider } from 'next-themes';

export function Providers({ children }) {
  return <ThemeProvider attribute="class">{children}</ThemeProvider>;
}
```

### 163. Implement scroll restoration
```jsx
'use client';
const scrollPositions = new Map();

export function ScrollManager() {
  const pathname = usePathname();
  useEffect(() => {
    const saved = scrollPositions.get(pathname);
    if (saved) window.scrollTo(0, saved);
  }, [pathname]);
  return null;
}
```

### 164. How do you implement data mutations?
```jsx
'use server';
export async function updateCar(id, data) {
  const car = await db.car.update({ where: { id }, data });
  revalidateTag(`car-${id}`);
  return car;
}
```

### 165. Implement conditional redirects
```jsx
export default async function MobileApp() {
  const ua = headers().get('user-agent') || '';
  if (/iphone/i.test(ua)) redirect('https://apps.apple.com/app');
  return <DesktopInfo />;
}
```

### 166. How do you implement content negotiation?
```jsx
export async function GET(request) {
  const accept = request.headers.get('accept');
  if (accept?.includes('application/xml')) {
    return new Response(xmlData, { headers: { 'content-type': 'application/xml' } });
  }
  return NextResponse.json(data);
}
```

### 167. Implement custom 404 per route
```jsx
// app/cars/not-found.tsx
export default function CarsNotFound() {
  return <div><h1>Car not found</h1><Link href="/cars">Browse all</Link></div>;
}
```

### 168. How do you implement background jobs?
```jsx
'use server';
import { Queue } from 'bullmq';

export async function sendConfirmation(bookingId) {
  await emailQueue.add('confirmation', { bookingId });
  return { success: true };
}
```

### 169. What is template.tsx?
template.tsx creates a new instance on navigation, unlike layout.tsx which preserves state. Use for animations.

### 170. Implement modal with intercepting routes
```jsx
// app/@modal/(.)cars/[id]/page.tsx
export default async function CarModal({ params }) {
  const car = await getCarById(params.id);
  return <Modal><CarDetail car={car} /></Modal>;
}
```

### 171. How do you use headers in Server Components?
```jsx
import { headers } from 'next/headers';

export default async function Page() {
  const headersList = headers();
  const userAgent = headersList.get('user-agent');
  return <div>UA: {userAgent}</div>;
}
```

### 172. Implement streaming data
```jsx
export async function GET() {
  const stream = new ReadableStream({
    async start(controller) {
      const data = await fetchData();
      controller.enqueue(new TextEncoder().encode(JSON.stringify(data)));
      controller.close();
    },
  });
  return new Response(stream);
}
```

### 173. How do you handle revalidation?
```jsx
import { revalidatePath, revalidateTag } from 'next/cache';

export async function updateCar(id) {
  await db.car.update({ where: { id }, data });
  revalidatePath('/cars');
  revalidateTag('cars');
}
```

### 174. Implement server-side cookies manipulation
```jsx
'use server';
import { cookies } from 'next/headers';

export async function login(formData) {
  const token = await authenticate(formData);
  cookies().set('token', token, { httpOnly: true, secure: true, maxAge: 604800 });
}
```

### 175. How do you implement search functionality?
```jsx
export default async function SearchPage({ searchParams }) {
  const query = searchParams.q || '';
  const cars = await db.car.findMany({
    where: { name: { contains: query, mode: 'insensitive' } }
  });
  return <SearchResults cars={cars} />;
}
```

### 176. Implement sorting and filtering
```jsx
export default async function CarsPage({ searchParams }) {
  const { sort, type, price } = searchParams;
  const cars = await db.car.findMany({
    where: { type, price: price ? { lte: parseInt(price) } : undefined },
    orderBy: sort ? { [sort]: 'asc' } : undefined,
  });
  return <CarList cars={cars} />;
}
```

### 177. How do you implement breadcrumbs?
```jsx
export default function Breadcrumbs() {
  const pathname = usePathname();
  const segments = pathname.split('/').filter(Boolean);
  return <nav>{segments.map((s, i) => <Link href={`/${segments.slice(0, i+1).join('/')}`}>{s}</Link>)}</nav>;
}
```

### 178. Implement data prefetching
```jsx
import Link from 'next/link';

export default function CarCard({ car }) {
  return (
    <Link href={`/cars/${car.id}`} prefetch={true}>
      <h3>{car.name}</h3>
    </Link>
  );
}
```

### 179. How do you handle query parameters?
```jsx
'use client';
export default function Filters() {
  const router = useRouter();
  const searchParams = useSearchParams();
  
  const updateFilter = (key, value) => {
    const params = new URLSearchParams(searchParams);
    params.set(key, value);
    router.push(`?${params}`);
  };
  
  return <select onChange={(e) => updateFilter('type', e.target.value)} />;
}
```

### 180. Implement lazy loading components
```jsx
import { lazy, Suspense } from 'react';

const HeavyComponent = lazy(() => import('./HeavyComponent'));

export default function Page() {
  return <Suspense fallback={<Loading />}><HeavyComponent /></Suspense>;
}
```

### 181. How do you implement API rate limiting?
```jsx
const limits = new Map();

export async function GET(request) {
  const ip = request.ip;
  const limit = limits.get(ip) || { count: 0, reset: Date.now() + 60000 };
  if (limit.count >= 100) return NextResponse.json({ error: 'Too many requests' }, { status: 429 });
  limit.count++;
  limits.set(ip, limit);
  return NextResponse.json(data);
}
```

### 182. Implement webhook verification
```jsx
export async function POST(request) {
  const signature = request.headers.get('x-signature');
  const body = await request.text();
  const valid = verifySignature(body, signature, secret);
  if (!valid) return NextResponse.json({ error: 'Invalid signature' }, { status: 401 });
  return NextResponse.json({ success: true });
}
```

### 183. How do you handle environment variables?
```jsx
// Access in Server Components
const apiKey = process.env.API_KEY;

// Expose to client with NEXT_PUBLIC_ prefix
const publicKey = process.env.NEXT_PUBLIC_KEY;
```

### 184. Implement custom metadata
```jsx
export const metadata = {
  title: 'Car Rental - Best Prices',
  description: 'Rent cars at affordable prices',
  keywords: ['car rental', 'cheap cars'],
  openGraph: { images: ['/og-image.jpg'] },
};
```

### 185. How do you implement RSS feed?
```jsx
// app/rss.xml/route.ts
export async function GET() {
  const cars = await db.car.findMany();
  const rss = generateRSS(cars);
  return new Response(rss, { headers: { 'Content-Type': 'application/xml' } });
}
```

### 186. Implement API versioning
```jsx
// app/api/v1/cars/route.ts
export async function GET() {
  return NextResponse.json({ version: 'v1', data: cars });
}

// app/api/v2/cars/route.ts
export async function GET() {
  return NextResponse.json({ version: 'v2', data: enhancedCars });
}
```

### 187. How do you implement pagination metadata?
```jsx
export default async function CarsPage({ searchParams }) {
  const page = parseInt(searchParams.page || '1');
  const [cars, total] = await Promise.all([
    db.car.findMany({ skip: (page - 1) * 20, take: 20 }),
    db.car.count()
  ]);
  return <div><CarList cars={cars} /><Pagination total={total} page={page} /></div>;
}
```

### 188. Implement image uploads with validation
```jsx
'use server';
export async function uploadImage(formData) {
  const file = formData.get('image');
  if (!file || !file.type.startsWith('image/')) throw new Error('Invalid file');
  if (file.size > 5 * 1024 * 1024) throw new Error('File too large');
  const buffer = Buffer.from(await file.arrayBuffer());
  await writeFile(`/uploads/${file.name}`, buffer);
  return { url: `/uploads/${file.name}` };
}
```

### 189. How do you implement database transactions?
```jsx
'use server';
export async function createBookingWithPayment(data) {
  return await db.$transaction(async (tx) => {
    const booking = await tx.booking.create({ data });
    const payment = await tx.payment.create({ data: { bookingId: booking.id } });
    return { booking, payment };
  });
}
```

### 190. Implement email sending
```jsx
'use server';
import { Resend } from 'resend';

const resend = new Resend(process.env.RESEND_API_KEY);

export async function sendBookingEmail(to, booking) {
  await resend.emails.send({
    from: 'bookings@example.com',
    to,
    subject: 'Booking Confirmation',
    html: `<p>Your booking #${booking.id} is confirmed</p>`,
  });
}
```

### 191. How do you implement soft deletes?
```jsx
'use server';
export async function deleteCar(id) {
  await db.car.update({ where: { id }, data: { deletedAt: new Date() } });
  revalidateTag('cars');
}

export async function getCars() {
  return db.car.findMany({ where: { deletedAt: null } });
}
```

### 192. Implement audit logging
```jsx
'use server';
export async function updateCar(id, data, userId) {
  const car = await db.car.update({ where: { id }, data });
  await db.auditLog.create({
    data: { userId, action: 'UPDATE_CAR', entityId: id, changes: data }
  });
  return car;
}
```

### 193. How do you implement search with filters?
```jsx
export default async function SearchPage({ searchParams }) {
  const { q, type, minPrice, maxPrice } = searchParams;
  const cars = await db.car.findMany({
    where: {
      AND: [
        q ? { OR: [{ name: { contains: q } }, { description: { contains: q } }] } : {},
        type ? { type } : {},
        minPrice ? { price: { gte: parseInt(minPrice) } } : {},
        maxPrice ? { price: { lte: parseInt(maxPrice) } } : {},
      ],
    },
  });
  return <SearchResults cars={cars} />;
}
```

### 194. Implement API authentication
```jsx
export async function GET(request) {
  const auth = request.headers.get('authorization');
  if (!auth || !auth.startsWith('Bearer ')) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }
  const token = auth.split(' ')[1];
  const user = await verifyToken(token);
  if (!user) return NextResponse.json({ error: 'Invalid token' }, { status: 401 });
  return NextResponse.json(data);
}
```

### 195. How do you implement API pagination?
```jsx
export async function GET(request) {
  const { searchParams } = new URL(request.url);
  const page = parseInt(searchParams.get('page') || '1');
  const limit = parseInt(searchParams.get('limit') || '20');
  const [cars, total] = await Promise.all([
    db.car.findMany({ skip: (page - 1) * limit, take: limit }),
    db.car.count()
  ]);
  return NextResponse.json({ data: cars, page, limit, total, pages: Math.ceil(total / limit) });
}
```

### 196. Implement data export
```jsx
export async function GET() {
  const cars = await db.car.findMany();
  const csv = cars.map(c => `${c.id},${c.name},${c.price}`).join('\\n');
  return new Response(csv, { headers: { 'Content-Type': 'text/csv' } });
}
```

### 197. How do you implement batch operations?
```jsx
'use server';
export async function batchUpdateCars(updates) {
  return await db.$transaction(
    updates.map(({ id, data }) => db.car.update({ where: { id }, data }))
  );
}
```

### 198. Implement full-text search
```jsx
export default async function SearchPage({ searchParams }) {
  const query = searchParams.q;
  const cars = await db.$queryRaw`
    SELECT * FROM cars 
    WHERE to_tsvector('english', name || ' ' || description) @@ plainto_tsquery('english', ${query})
  `;
  return <SearchResults cars={cars} />;
}
```

### 199. How do you implement data validation middleware?
```jsx
import { z } from 'zod';

const carSchema = z.object({
  name: z.string().min(1).max(100),
  price: z.number().positive(),
});

export async function POST(request) {
  const body = await request.json();
  const validated = carSchema.parse(body);
  const car = await db.car.create({ data: validated });
  return NextResponse.json(car);
}
```

### 200. Implement complex data relationships
```jsx
export default async function CarPage({ params }) {
  const car = await db.car.findUnique({
    where: { id: params.id },
    include: {
      reviews: { include: { user: true } },
      bookings: { where: { status: 'confirmed' } },
      images: true,
      features: true,
    },
  });
  return <CarDetailView car={car} />;
}
```
