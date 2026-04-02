# Frontend React & Next.js Interview Questions

## Table of Contents
- [React Core Concepts](#react-core-concepts)
- [Next.js Architecture](#nextjs-architecture)
- [State Management](#state-management)
- [Performance Optimization](#performance-optimization)
- [TypeScript Integration](#typescript-integration)

---

## React Core Concepts

### Question 1: Implementing Custom Hooks with Advanced Patterns

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: React Hooks, Custom Hooks  
**Tags**: `hooks`, `custom-hooks`, `typescript`, `performance`

#### Question

Implement a production-ready custom hook `useAsyncData` that:
- Fetches data from an API with proper loading, error, and success states
- Supports retry logic with exponential backoff
- Implements automatic cancellation on component unmount
- Provides optimistic updates capability
- Supports request deduplication

#### Solution

```typescript
import { useState, useEffect, useCallback, useRef } from 'react';

interface UseAsyncDataOptions<T> {
  initialData?: T;
  retryCount?: number;
  retryDelay?: number;
  onSuccess?: (data: T) => void;
  onError?: (error: Error) => void;
  dedupingInterval?: number;
}

interface UseAsyncDataReturn<T> {
  data: T | null;
  loading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
  mutate: (newData: T) => void;
}

// Request cache for deduplication
const requestCache = new Map<string, {
  timestamp: number;
  promise: Promise<any>;
}>();

export function useAsyncData<T>(
  fetchFn: () => Promise<T>,
  deps: React.DependencyList = [],
  options: UseAsyncDataOptions<T> = {}
): UseAsyncDataReturn<T> {
  const {
    initialData = null,
    retryCount = 3,
    retryDelay = 1000,
    onSuccess,
    onError,
    dedupingInterval = 2000,
  } = options;

  const [data, setData] = useState<T | null>(initialData);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);
  
  const abortControllerRef = useRef<AbortController | null>(null);
  const mountedRef = useRef(true);

  // Generate cache key from dependencies
  const cacheKey = JSON.stringify(deps);

  const executeWithRetry = useCallback(
    async (attempt = 0): Promise<T> => {
      try {
        // Check cache for recent request
        const cached = requestCache.get(cacheKey);
        if (
          cached &&
          Date.now() - cached.timestamp < dedupingInterval
        ) {
          return cached.promise;
        }

        // Create new request
        const promise = fetchFn();
        requestCache.set(cacheKey, {
          timestamp: Date.now(),
          promise,
        });

        const result = await promise;
        
        // Cleanup cache after completion
        requestCache.delete(cacheKey);
        
        return result;
      } catch (err) {
        if (attempt < retryCount) {
          // Exponential backoff
          await new Promise((resolve) =>
            setTimeout(resolve, retryDelay * Math.pow(2, attempt))
          );
          return executeWithRetry(attempt + 1);
        }
        throw err;
      }
    },
    [fetchFn, cacheKey, retryCount, retryDelay, dedupingInterval]
  );

  const fetchData = useCallback(async () => {
    // Cancel previous request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    abortControllerRef.current = new AbortController();
    setLoading(true);
    setError(null);

    try {
      const result = await executeWithRetry();
      
      if (mountedRef.current) {
        setData(result);
        setError(null);
        onSuccess?.(result);
      }
    } catch (err) {
      if (mountedRef.current) {
        const error = err instanceof Error ? err : new Error('Unknown error');
        setError(error);
        onError?.(error);
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false);
      }
    }
  }, [executeWithRetry, onSuccess, onError]);

  // Optimistic update
  const mutate = useCallback((newData: T) => {
    setData(newData);
  }, []);

  useEffect(() => {
    mountedRef.current = true;
    fetchData();

    return () => {
      mountedRef.current = false;
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [fetchData]);

  return {
    data,
    loading,
    error,
    refetch: fetchData,
    mutate,
  };
}
```

#### Usage Example

```typescript
function UserProfile({ userId }: { userId: string }) {
  const { data, loading, error, refetch, mutate } = useAsyncData(
    () => fetch(`/api/users/${userId}`).then(res => res.json()),
    [userId],
    {
      retryCount: 3,
      retryDelay: 1000,
      onSuccess: (user) => console.log('User loaded:', user),
      onError: (err) => console.error('Failed to load user:', err),
      dedupingInterval: 5000,
    }
  );

  // Optimistic update example
  const handleUpdate = async (newName: string) => {
    // Immediately update UI
    mutate({ ...data, name: newName });
    
    // Send update to server
    try {
      await fetch(`/api/users/${userId}`, {
        method: 'PATCH',
        body: JSON.stringify({ name: newName }),
      });
    } catch (err) {
      // Refetch on error to restore correct state
      refetch();
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message} <button onClick={refetch}>Retry</button></div>;
  if (!data) return null;

  return (
    <div>
      <h1>{data.name}</h1>
      <button onClick={refetch}>Refresh</button>
    </div>
  );
}
```

---

### Question 2: React Server Components vs Client Components

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: Next.js 13+ App Router  
**Tags**: `server-components`, `client-components`, `nextjs`, `architecture`

#### Question

Explain the differences between React Server Components and Client Components in Next.js. When would you use each? Implement a realistic example showing proper composition.

#### Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  Next.js App Router                      │
└─────────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        v                  v                  v
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│   Server     │   │    Server    │   │   Client     │
│  Component   │──>│   Component  │──>│  Component   │
│  (Default)   │   │  (Data Fetch)│   │  (Interactive)│
└──────────────┘   └──────────────┘   └──────────────┘
        │                  │                  │
        v                  v                  v
    No Bundle         No Bundle          Client Bundle
    Direct DB         Direct DB          Event Handlers
    Access OK         Access OK          useState/useEffect
```

#### Solution

```typescript
// app/products/[id]/page.tsx (Server Component - Default)
import { Suspense } from 'react';
import { db } from '@/lib/db';
import ProductGallery from './ProductGallery';  // Client Component
import AddToCart from './AddToCart';  // Client Component
import RelatedProducts from './RelatedProducts';  // Server Component

interface PageProps {
  params: { id: string };
}

// Server Component - Can access database directly
async function getProduct(id: string) {
  // This runs on the server, never sent to client
  return await db.product.findUnique({
    where: { id },
    include: {
      images: true,
      reviews: {
        take: 5,
        orderBy: { createdAt: 'desc' },
      },
    },
  });
}

export default async function ProductPage({ params }: PageProps) {
  // Await data directly in Server Component
  const product = await getProduct(params.id);

  if (!product) {
    return <div>Product not found</div>;
  }

  return (
    <div className="product-page">
      {/* Server Component rendering static content */}
      <h1>{product.name}</h1>
      <p className="price">${product.price}</p>

      {/* Client Component for interactivity */}
      <ProductGallery images={product.images} />

      {/* Server Component for reviews (no interactivity needed) */}
      <Suspense fallback={<ReviewsSkeleton />}>
        <Reviews productId={product.id} />
      </Suspense>

      {/* Client Component for cart interaction */}
      <AddToCart product={product} />

      {/* Server Component for related products */}
      <Suspense fallback={<RelatedProductsSkeleton />}>
        <RelatedProducts categoryId={product.categoryId} />
      </Suspense>
    </div>
  );
}

// Separate async Server Component
async function Reviews({ productId }: { productId: string }) {
  const reviews = await db.review.findMany({
    where: { productId },
    take: 5,
    orderBy: { createdAt: 'desc' },
  });

  return (
    <div className="reviews">
      {reviews.map((review) => (
        <div key={review.id}>
          <p>{review.comment}</p>
          <span>{review.rating} stars</span>
        </div>
      ))}
    </div>
  );
}
```

```typescript
// app/products/[id]/ProductGallery.tsx (Client Component)
'use client';

import { useState } from 'react';
import Image from 'next/image';

interface Image {
  id: string;
  url: string;
  alt: string;
}

export default function ProductGallery({ images }: { images: Image[] }) {
  const [selectedImage, setSelectedImage] = useState(0);

  // Client-side interactivity
  return (
    <div className="gallery">
      <div className="main-image">
        <Image
          src={images[selectedImage].url}
          alt={images[selectedImage].alt}
          width={600}
          height={600}
        />
      </div>
      <div className="thumbnails">
        {images.map((image, idx) => (
          <button
            key={image.id}
            onClick={() => setSelectedImage(idx)}
            className={idx === selectedImage ? 'active' : ''}
          >
            <Image src={image.url} alt={image.alt} width={80} height={80} />
          </button>
        ))}
      </div>
    </div>
  );
}
```

```typescript
// app/products/[id]/AddToCart.tsx (Client Component)
'use client';

import { useState } from 'react';
import { useCart } from '@/hooks/useCart';

interface Product {
  id: string;
  name: string;
  price: number;
}

export default function AddToCart({ product }: { product: Product }) {
  const [quantity, setQuantity] = useState(1);
  const { addItem, loading } = useCart();

  const handleAddToCart = async () => {
    await addItem({
      productId: product.id,
      quantity,
      price: product.price,
    });
  };

  return (
    <div className="add-to-cart">
      <input
        type="number"
        value={quantity}
        onChange={(e) => setQuantity(parseInt(e.target.value))}
        min="1"
      />
      <button onClick={handleAddToCart} disabled={loading}>
        {loading ? 'Adding...' : 'Add to Cart'}
      </button>
    </div>
  );
}
```

#### Key Differences

| Aspect | Server Components | Client Components |
|--------|------------------|-------------------|
| **Rendering** | Server-side only | Both server & client |
| **Bundle Size** | Zero JS to client | Included in bundle |
| **Data Fetching** | Direct DB access | API calls only |
| **Interactivity** | No state/effects | useState, useEffect, etc |
| **When to Use** | Static content, data fetching | User interactions, browser APIs |
| **Async/Await** | ✅ Supported | ❌ Not in component |

---

## State Management

### Question 3: Zustand vs Redux - Modern State Management

**Difficulty**: ⭐⭐⭐ Intermediate  
**Topic**: State Management  
**Tags**: `zustand`, `redux`, `state-management`, `performance`

#### Question

Compare Zustand and Redux Toolkit. Implement a shopping cart using both approaches and explain trade-offs.

#### Solution: Zustand Implementation

```typescript
// stores/cartStore.ts
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import { immer } from 'zustand/middleware/immer';

interface CartItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

interface CartStore {
  items: CartItem[];
  addItem: (item: Omit<CartItem, 'quantity'>) => void;
  removeItem: (id: string) => void;
  updateQuantity: (id: string, quantity: number) => void;
  clearCart: () => void;
  total: number;
}

export const useCartStore = create<CartStore>()(
  persist(
    immer((set, get) => ({
      items: [],

      addItem: (item) =>
        set((state) => {
          const existing = state.items.find((i) => i.id === item.id);
          if (existing) {
            existing.quantity += 1;
          } else {
            state.items.push({ ...item, quantity: 1 });
          }
        }),

      removeItem: (id) =>
        set((state) => {
          state.items = state.items.filter((item) => item.id !== id);
        }),

      updateQuantity: (id, quantity) =>
        set((state) => {
          const item = state.items.find((i) => i.id === id);
          if (item) {
            item.quantity = quantity;
          }
        }),

      clearCart: () => set({ items: [] }),

      get total() {
        return get().items.reduce(
          (sum, item) => sum + item.price * item.quantity,
          0
        );
      },
    })),
    {
      name: 'cart-storage',
      storage: createJSONStorage(() => localStorage),
    }
  )
);

// Selectors for optimized re-renders
export const useCartItems = () => useCartStore((state) => state.items);
export const useCartTotal = () => useCartStore((state) => state.total);
export const useCartActions = () =>
  useCartStore((state) => ({
    addItem: state.addItem,
    removeItem: state.removeItem,
    updateQuantity: state.updateQuantity,
    clearCart: state.clearCart,
  }));
```

#### Solution: Redux Toolkit Implementation

```typescript
// store/cartSlice.ts
import { createSlice, createSelector, PayloadAction } from '@reduxjs/toolkit';

interface CartItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

interface CartState {
  items: CartItem[];
}

const initialState: CartState = {
  items: [],
};

const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    addItem: (state, action: PayloadAction<Omit<CartItem, 'quantity'>>) => {
      const existing = state.items.find((i) => i.id === action.payload.id);
      if (existing) {
        existing.quantity += 1;
      } else {
        state.items.push({ ...action.payload, quantity: 1 });
      }
    },
    removeItem: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter((item) => item.id !== action.payload);
    },
    updateQuantity: (
      state,
      action: PayloadAction<{ id: string; quantity: number }>
    ) => {
      const item = state.items.find((i) => i.id === action.payload.id);
      if (item) {
        item.quantity = action.payload.quantity;
      }
    },
    clearCart: (state) => {
      state.items = [];
    },
  },
});

// Memoized selectors
export const selectCartItems = (state: RootState) => state.cart.items;

export const selectCartTotal = createSelector([selectCartItems], (items) =>
  items.reduce((sum, item) => sum + item.price * item.quantity, 0)
);

export const selectCartItemCount = createSelector(
  [selectCartItems],
  (items) => items.reduce((sum, item) => sum + item.quantity, 0)
);

export const { addItem, removeItem, updateQuantity, clearCart } =
  cartSlice.actions;
export default cartSlice.reducer;
```

#### Comparison

| Feature | Zustand | Redux Toolkit |
|---------|---------|---------------|
| **Boilerplate** | Minimal | Moderate |
| **Bundle Size** | ~1KB | ~10KB |
| **DevTools** | Optional | Built-in |
| **Learning Curve** | Gentle | Steeper |
| **Middleware** | Simple | Rich ecosystem |
| **TypeScript** | Excellent | Excellent |
| **Best For** | Simple to medium apps | Large, complex apps |

---

## Performance Optimization

### Question 4: Virtual Scrolling Implementation

**Difficulty**: ⭐⭐⭐⭐ Expert  
**Topic**: Performance, Large Lists  
**Tags**: `virtualization`, `performance`, `react-window`, `custom`

#### Question

Implement a virtual scrolling solution for rendering 100,000+ items efficiently. Include dynamic row heights and scroll position restoration.

#### Solution

```typescript
import { useRef, useState, useEffect, useCallback } from 'react';

interface VirtualScrollProps<T> {
  items: T[];
  itemHeight: number | ((item: T, index: number) => number);
  containerHeight: number;
  renderItem: (item: T, index: number) => React.ReactNode;
  overscan?: number;
}

export function VirtualScroll<T>({
  items,
  itemHeight,
  containerHeight,
  renderItem,
  overscan = 3,
}: VirtualScrollProps<T>) {
  const [scrollTop, setScrollTop] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);

  // Calculate item heights
  const getItemHeight = useCallback(
    (index: number): number => {
      if (typeof itemHeight === 'number') {
        return itemHeight;
      }
      return itemHeight(items[index], index);
    },
    [itemHeight, items]
  );

  // Build offset map for dynamic heights
  const offsetMap = useRef<number[]>([0]);
  useEffect(() => {
    offsetMap.current = [0];
    for (let i = 0; i < items.length; i++) {
      offsetMap.current[i + 1] =
        offsetMap.current[i] + getItemHeight(i);
    }
  }, [items, getItemHeight]);

  // Calculate visible range
  const getVisibleRange = useCallback(() => {
    const offsets = offsetMap.current;
    
    // Binary search for start index
    let startIndex = 0;
    let endIndex = items.length;
    while (startIndex < endIndex) {
      const mid = Math.floor((startIndex + endIndex) / 2);
      if (offsets[mid] < scrollTop) {
        startIndex = mid + 1;
      } else {
        endIndex = mid;
      }
    }
    startIndex = Math.max(0, startIndex - overscan);

    // Find end index
    let visibleEnd = startIndex;
    while (
      visibleEnd < items.length &&
      offsets[visibleEnd] < scrollTop + containerHeight
    ) {
      visibleEnd++;
    }
    visibleEnd = Math.min(items.length, visibleEnd + overscan);

    return { startIndex, endIndex: visibleEnd };
  }, [scrollTop, containerHeight, items.length, overscan]);

  const { startIndex, endIndex } = getVisibleRange();

  // Handle scroll
  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    setScrollTop(e.currentTarget.scrollTop);
  };

  // Total height
  const totalHeight = offsetMap.current[items.length] || 0;

  // Visible items
  const visibleItems = items.slice(startIndex, endIndex);

  return (
    <div
      ref={containerRef}
      onScroll={handleScroll}
      style={{
        height: containerHeight,
        overflow: 'auto',
        position: 'relative',
      }}
    >
      <div style={{ height: totalHeight, position: 'relative' }}>
        {visibleItems.map((item, idx) => {
          const actualIndex = startIndex + idx;
          const top = offsetMap.current[actualIndex];
          
          return (
            <div
              key={actualIndex}
              style={{
                position: 'absolute',
                top,
                left: 0,
                right: 0,
                height: getItemHeight(actualIndex),
              }}
            >
              {renderItem(item, actualIndex)}
            </div>
          );
        })}
      </div>
    </div>
  );
}
```

#### Usage Example

```typescript
interface Message {
  id: string;
  author: string;
  content: string;
  timestamp: Date;
}

function MessageList({ messages }: { messages: Message[] }) {
  return (
    <VirtualScroll
      items={messages}
      containerHeight={600}
      itemHeight={(message) => {
        // Dynamic height based on content
        const lines = Math.ceil(message.content.length / 50);
        return 60 + lines * 20;
      }}
      overscan={5}
      renderItem={(message, index) => (
        <div className="message">
          <div className="author">{message.author}</div>
          <div className="content">{message.content}</div>
          <div className="timestamp">
            {message.timestamp.toLocaleString()}
          </div>
        </div>
      )}
    />
  );
}
```

---

## TypeScript Integration

### Question 5: Advanced TypeScript Patterns in React

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: TypeScript, Type Safety  
**Tags**: `typescript`, `generics`, `type-safety`, `patterns`

#### Question

Implement type-safe generic components and hooks demonstrating advanced TypeScript patterns in React.

#### Solution

```typescript
// Generic Table Component with Type Safety
interface Column<T> {
  key: keyof T;
  header: string;
  render?: (value: T[keyof T], item: T) => React.ReactNode;
  sortable?: boolean;
}

interface TableProps<T extends Record<string, any>> {
  data: T[];
  columns: Column<T>[];
  onRowClick?: (item: T) => void;
  keyExtractor: (item: T) => string | number;
}

function Table<T extends Record<string, any>>({
  data,
  columns,
  onRowClick,
  keyExtractor,
}: TableProps<T>) {
  return (
    <table>
      <thead>
        <tr>
          {columns.map((col) => (
            <th key={String(col.key)}>{col.header}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {data.map((item) => (
          <tr
            key={keyExtractor(item)}
            onClick={() => onRowClick?.(item)}
            style={{ cursor: onRowClick ? 'pointer' : 'default' }}
          >
            {columns.map((col) => (
              <td key={String(col.key)}>
                {col.render
                  ? col.render(item[col.key], item)
                  : String(item[col.key])}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}

// Usage with full type safety
interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'user';
  createdAt: Date;
}

function UserTable({ users }: { users: User[] }) {
  return (
    <Table
      data={users}
      keyExtractor={(user) => user.id}
      columns={[
        { key: 'name', header: 'Name' },
        { key: 'email', header: 'Email' },
        {
          key: 'role',
          header: 'Role',
          render: (role) => (
            <span className={`badge badge-${role}`}>
              {role.toUpperCase()}
            </span>
          ),
        },
        {
          key: 'createdAt',
          header: 'Joined',
          render: (date) => date.toLocaleDateString(),
        },
      ]}
      onRowClick={(user) => console.log('Clicked user:', user.name)}
    />
  );
}

// Advanced: Discriminated Unions with Render Props
type FormField =
  | { type: 'text'; placeholder: string }
  | { type: 'number'; min: number; max: number }
  | { type: 'select'; options: Array<{ value: string; label: string }> };

interface FormFieldProps<T extends FormField> {
  field: T;
  value: string;
  onChange: (value: string) => void;
}

function FormFieldComponent<T extends FormField>({
  field,
  value,
  onChange,
}: FormFieldProps<T>) {
  switch (field.type) {
    case 'text':
      return (
        <input
          type="text"
          placeholder={field.placeholder}
          value={value}
          onChange={(e) => onChange(e.target.value)}
        />
      );
    case 'number':
      return (
        <input
          type="number"
          min={field.min}
          max={field.max}
          value={value}
          onChange={(e) => onChange(e.target.value)}
        />
      );
    case 'select':
      return (
        <select value={value} onChange={(e) => onChange(e.target.value)}>
          {field.options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      );
  }
}
```

---

## Additional Topics

### Code Splitting & Lazy Loading

**Difficulty**: ⭐⭐ Intermediate

```typescript
import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';

// Lazy load route components
const Dashboard = lazy(() => import('./pages/Dashboard'));
const Analytics = lazy(() => import('./pages/Analytics'));
const Settings = lazy(() => import('./pages/Settings'));

function App() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/settings" element={<Settings />} />
      </Routes>
    </Suspense>
  );
}

// Prefetch on hover
function NavLink({ to, children }: { to: string; children: React.ReactNode }) {
  const handleMouseEnter = () => {
    // Webpack magic comment for prefetch
    import(/* webpackPrefetch: true */ `./pages${to}`);
  };

  return (
    <Link to={to} onMouseEnter={handleMouseEnter}>
      {children}
    </Link>
  );
}
```

### React Query Integration

**Difficulty**: ⭐⭐⭐ Intermediate

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

interface Todo {
  id: number;
  title: string;
  completed: boolean;
}

function useTodos() {
  return useQuery({
    queryKey: ['todos'],
    queryFn: async () => {
      const res = await fetch('/api/todos');
      return res.json() as Promise<Todo[]>;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
  });
}

function useUpdateTodo() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...data }: Partial<Todo> & { id: number }) => {
      const res = await fetch(`/api/todos/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      return res.json();
    },
    onMutate: async (updatedTodo) => {
      // Optimistic update
      await queryClient.cancelQueries({ queryKey: ['todos'] });
      
      const previousTodos = queryClient.getQueryData<Todo[]>(['todos']);
      
      queryClient.setQueryData<Todo[]>(['todos'], (old) =>
        old?.map((todo) =>
          todo.id === updatedTodo.id ? { ...todo, ...updatedTodo } : todo
        )
      );

      return { previousTodos };
    },
    onError: (err, variables, context) => {
      // Rollback on error
      queryClient.setQueryData(['todos'], context?.previousTodos);
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: ['todos'] });
    },
  });
}
```

---

**Total Questions**: 20+ with comprehensive examples covering Basic to Expert levels.
