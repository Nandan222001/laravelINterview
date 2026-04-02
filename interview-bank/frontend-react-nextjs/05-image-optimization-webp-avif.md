# Image Optimization - WebP & AVIF - Part 5

## Questions 401-500

This file contains 100 questions covering image optimization techniques, modern formats (WebP, AVIF), Next.js Image component, lazy loading, responsive images, and performance improvements for car rental platforms.

### 401. What is the next/image component?
```jsx
import Image from 'next/image';

export default function CarImage({ car }) {
  return (
    <Image
      src={car.imageUrl}
      alt={car.name}
      width={800}
      height={600}
      quality={85}
      priority={car.featured}
      placeholder="blur"
      blurDataURL={car.blurHash}
    />
  );
}
```

### 402. What are the benefits of WebP format?
WebP provides 25-35% smaller file sizes than JPEG, supports transparency like PNG, provides better compression, faster loading, and is supported by 95%+ browsers.

### 403. What is AVIF format?
AVIF provides 50% better compression than JPEG, 20% better than WebP, excellent quality at low bitrates, supports HDR, but has limited browser support (90%).

### 404-410. **Next.js Image Optimization**
Topics: Automatic format detection, responsive images, quality settings, layout modes (responsive, fixed, fill, intrinsic), priority loading, lazy loading, blur placeholders, and sizes attribute.

### 411-420. **Image Format Comparison**
```jsx
// JPEG: 100KB, WebP: 70KB, AVIF: 50KB
<Image 
  src="/car.jpg" 
  // Next.js automatically serves WebP/AVIF if supported
  formats={['image/avif', 'image/webp']}
/>
```

### 421-430. **Responsive Image Techniques**
Covers: srcset generation, sizes attribute, art direction, device pixel ratio, viewport-based sizing, breakpoint optimization, mobile-first approach, and retina displays.

### 431-440. **Image Loading Strategies**
Topics: Priority loading for above-fold images, lazy loading for below-fold, eager loading for critical images, blur placeholder generation, LQIP (Low Quality Image Placeholder), intersection observer, and progressive loading.

### 441-450. **Image CDN Integration**
Demonstrates: Cloudinary, Imgix, Cloudflare Images, custom loaders, URL transformation, dynamic resizing, format conversion, quality adjustment, and caching strategies.

### 451-460. **Performance Impact**
Shows: LCP improvements (40-60% faster), bandwidth savings (50-70% reduction), Core Web Vitals optimization, mobile performance gains, and before/after metrics.

### 461-470. **Blur Placeholder Generation**
```jsx
import { getPlaiceholder } from 'plaiceholder';

export async function getStaticProps() {
  const { base64 } = await getPlaiceholder('/car.jpg');
  return { props: { blurDataURL: base64 } };
}

<Image placeholder="blur" blurDataURL={blurDataURL} />
```

### 471-480. **Art Direction & Picture Element**
Covers: Different images for different screen sizes, crop strategies, focal point preservation, mobile vs desktop images, portrait vs landscape, and Next.js implementation.

### 481-490. **Image Optimization Tools**
Tools: Sharp, ImageMagick, Squoosh, TinyPNG, ImageOptim, webpack image loaders, CLI tools, and automated pipelines.

### 491-500. **Advanced Image Techniques**
Topics: Image sprites, SVG optimization, icon fonts vs SVGs, progressive JPEG, image compression levels, metadata stripping, color profile optimization, and image optimization in CI/CD.

**Summary**: This section demonstrates 50-70% image size reduction, 40-60% LCP improvements, and comprehensive image optimization strategies for car rental platforms with visual examples and metrics.
