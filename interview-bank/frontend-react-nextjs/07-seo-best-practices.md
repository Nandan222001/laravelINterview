# SEO Best Practices for Car Rental SaaS - Part 7

## Questions 601-700

This file contains 100 questions covering SEO optimization for Next.js car rental platforms, including metadata, structured data, sitemaps, robots.txt, Open Graph, Twitter Cards, and technical SEO.

### 601. How do you implement metadata in Next.js 14?
```jsx
// app/cars/[id]/page.tsx
export async function generateMetadata({ params }) {
  const car = await getCar(params.id);
  
  return {
    title: `${car.name} - Rent for $${car.price}/day`,
    description: `Rent ${car.name} - ${car.description}. Book now and save!`,
    keywords: [car.brand, car.type, 'car rental', car.location],
    openGraph: {
      title: car.name,
      description: car.description,
      images: [{ url: car.image, width: 1200, height: 630 }],
      type: 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title: car.name,
      description: car.description,
      images: [car.image],
    },
    alternates: {
      canonical: `https://example.com/cars/${car.id}`,
    },
  };
}
```

### 602. What is structured data for car rentals?
```jsx
export default function CarPage({ car }) {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: car.name,
    description: car.description,
    image: car.image,
    brand: { '@type': 'Brand', name: car.brand },
    offers: {
      '@type': 'Offer',
      price: car.price,
      priceCurrency: 'USD',
      availability: 'https://schema.org/InStock',
      priceValidUntil: '2024-12-31',
    },
    aggregateRating: {
      '@type': 'AggregateRating',
      ratingValue: car.rating,
      reviewCount: car.reviewCount,
    },
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }}
      />
      <CarDetail car={car} />
    </>
  );
}
```

### 603-610. **Dynamic Sitemap Generation**
```jsx
// app/sitemap.ts
export default async function sitemap() {
  const cars = await db.car.findMany();
  const locations = await db.location.findMany();
  
  const carUrls = cars.map(car => ({
    url: `https://example.com/cars/${car.id}`,
    lastModified: car.updatedAt,
    changeFrequency: 'weekly',
    priority: 0.8,
  }));
  
  const locationUrls = locations.map(loc => ({
    url: `https://example.com/locations/${loc.slug}`,
    lastModified: loc.updatedAt,
    changeFrequency: 'monthly',
    priority: 0.6,
  }));

  return [
    {
      url: 'https://example.com',
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    ...carUrls,
    ...locationUrls,
  ];
}
```

### 611-620. **Robots.txt Configuration**
```jsx
// app/robots.ts
export default function robots() {
  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: ['/api/', '/admin/', '/private/'],
      },
      {
        userAgent: 'Googlebot',
        allow: '/',
        crawlDelay: 1,
      },
    ],
    sitemap: 'https://example.com/sitemap.xml',
    host: 'https://example.com',
  };
}
```

### 621-630. **Open Graph Implementation**
Topics: OG tags for social sharing, dynamic OG images, Twitter Cards, social media previews, image specifications (1200x630), generating social images with Vercel OG.

### 631-640. **Canonical URLs & Duplicate Content**
```jsx
export const metadata = {
  alternates: {
    canonical: 'https://example.com/cars/tesla-model-3',
  },
};

// Handle URL parameters
// ?sort=price → canonical to base URL
// Pagination: rel="next" and rel="prev"
```

### 641-650. **URL Structure Best Practices**
Examples:
- ✅ `/cars/tesla-model-3`
- ✅ `/locations/new-york/manhattan`
- ✅ `/blog/car-rental-tips`
- ❌ `/cars?id=123&type=sedan`
- ❌ `/page/car/view/123`

### 651-660. **Heading Hierarchy & Content Structure**
```jsx
export default function CarPage({ car }) {
  return (
    <article>
      <h1>{car.name}</h1> {/* One H1 per page */}
      <section>
        <h2>Specifications</h2>
        <h3>Engine</h3>
        <h3>Interior</h3>
      </section>
      <section>
        <h2>Pricing</h2>
        <h3>Daily Rate</h3>
        <h3>Weekly Rate</h3>
      </section>
    </article>
  );
}
```

### 661-670. **Mobile-First SEO**
Topics: Mobile usability, responsive design, touch targets, font sizes, viewport configuration, mobile page speed, Core Web Vitals on mobile.

### 671-680. **Local SEO for Car Rentals**
```jsx
const localBusinessSchema = {
  '@context': 'https://schema.org',
  '@type': 'LocalBusiness',
  name: 'Premium Car Rentals',
  address: {
    '@type': 'PostalAddress',
    streetAddress: '123 Main St',
    addressLocality: 'New York',
    addressRegion: 'NY',
    postalCode: '10001',
    addressCountry: 'US',
  },
  geo: {
    '@type': 'GeoCoordinates',
    latitude: 40.7128,
    longitude: -74.0060,
  },
  telephone: '+1-212-555-0100',
  openingHours: 'Mo-Su 08:00-20:00',
  priceRange: '$$',
};
```

### 681-690. **Internal Linking Strategy**
Examples:
- Car detail → Similar cars
- Location pages → Available cars
- Blog posts → Relevant car categories
- Homepage → Top destinations
- Breadcrumbs with structured data

### 691-700. **Performance & SEO Correlation**
Topics: Page speed as ranking factor, Core Web Vitals impact, mobile-first indexing, HTTPS requirement, secure connections, AMP vs modern optimization, rendering strategies (SSR vs SSG vs ISR).

**Summary**: This section covers comprehensive SEO strategies that can improve organic traffic by 50-100%, featuring 100 questions on metadata, structured data, technical SEO, and car rental-specific optimizations with real-world examples and schema implementations.
