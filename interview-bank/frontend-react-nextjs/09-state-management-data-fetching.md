# State Management & Data Fetching - Part 9

## Questions 801-900

This file contains 100 questions covering state management solutions (Context, Redux, Zustand, Jotai), data fetching libraries (SWR, React Query), caching strategies, and real-time updates for car rental platforms.

### 801. Implement global state with Zustand
```jsx
// store/useCarStore.js
import create from 'zustand';
import { persist } from 'zustand/middleware';

export const useCarStore = create(
  persist(
    (set, get) => ({
      selectedCar: null,
      bookingDates: null,
      searchFilters: {},
      
      selectCar: (car) => set({ selectedCar: car }),
      setBookingDates: (dates) => set({ bookingDates: dates }),
      updateFilters: (filters) => set({ searchFilters: {...get().searchFilters, ...filters} }),
      clearBooking: () => set({ selectedCar: null, bookingDates: null }),
    }),
    { name: 'car-booking' }
  )
);

// Usage
function CarCard({ car }) {
  const selectCar = useCarStore(state => state.selectCar);
  return <button onClick={() => selectCar(car)}>Select</button>;
}
```

### 802. What is SWR and how to use it?
```jsx
import useSWR from 'swr';

const fetcher = (url) => fetch(url).then(r => r.json());

function CarList() {
  const { data: cars, error, isLoading, mutate } = useSWR('/api/cars', fetcher, {
    revalidateOnFocus: true,
    revalidateOnReconnect: true,
    refreshInterval: 30000, // 30 seconds
  });

  if (isLoading) return <Loading />;
  if (error) return <Error />;
  
  return <div>{cars.map(car => <CarCard key={car.id} car={car} />)}</div>;
}
```

### 803-810. **React Query (TanStack Query) Implementation**
```jsx
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

function useCars(filters) {
  return useQuery({
    queryKey: ['cars', filters],
    queryFn: () => fetch(`/api/cars?${new URLSearchParams(filters)}`).then(r => r.json()),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

function useBookCar() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (booking) => fetch('/api/bookings', {
      method: 'POST',
      body: JSON.stringify(booking),
    }).then(r => r.json()),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}
```

### 811-820. **Context API for App State**
```jsx
const AppContext = createContext();

export function AppProvider({ children }) {
  const [user, setUser] = useState(null);
  const [theme, setTheme] = useState('light');
  const [cart, setCart] = useState([]);
  
  const value = useMemo(() => ({
    user, setUser,
    theme, setTheme,
    cart, setCart,
    addToCart: (item) => setCart([...cart, item]),
    removeFromCart: (id) => setCart(cart.filter(i => i.id !== id)),
  }), [user, theme, cart]);
  
  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}

export const useApp = () => useContext(AppContext);
```

### 821-830. **Redux Toolkit Setup**
```jsx
// store/carsSlice.js
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';

export const fetchCars = createAsyncThunk('cars/fetch', async (filters) => {
  const response = await fetch(`/api/cars?${new URLSearchParams(filters)}`);
  return response.json();
});

const carsSlice = createSlice({
  name: 'cars',
  initialState: { items: [], loading: false, error: null },
  reducers: {
    addCar: (state, action) => {
      state.items.push(action.payload);
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchCars.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchCars.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload;
      })
      .addCase(fetchCars.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message;
      });
  },
});

export const { addCar } = carsSlice.actions;
export default carsSlice.reducer;
```

### 831-840. **Optimistic Updates**
```jsx
function useBookCar() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (booking) => fetch('/api/bookings', {
      method: 'POST',
      body: JSON.stringify(booking),
    }).then(r => r.json()),
    
    onMutate: async (newBooking) => {
      await queryClient.cancelQueries({ queryKey: ['bookings'] });
      const previous = queryClient.getQueryData(['bookings']);
      
      queryClient.setQueryData(['bookings'], (old) => [...old, {
        ...newBooking,
        id: 'temp-' + Date.now(),
        status: 'pending',
      }]);
      
      return { previous };
    },
    
    onError: (err, newBooking, context) => {
      queryClient.setQueryData(['bookings'], context.previous);
    },
    
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}
```

### 841-850. **Caching Strategies**
Topics: Memory cache, localStorage persistence, IndexedDB for large data, cache invalidation, stale-while-revalidate, cache-first vs network-first, TTL strategies.

### 851-860. **Real-time Data with WebSockets**
```jsx
function useRealtimeBookings() {
  const [bookings, setBookings] = useState([]);
  const ws = useRef(null);

  useEffect(() => {
    ws.current = new WebSocket('wss://api.example.com/bookings');
    
    ws.current.onmessage = (event) => {
      const update = JSON.parse(event.data);
      setBookings(prev => {
        const index = prev.findIndex(b => b.id === update.id);
        if (index >= 0) {
          const updated = [...prev];
          updated[index] = update;
          return updated;
        }
        return [...prev, update];
      });
    };

    return () => ws.current?.close();
  }, []);

  return bookings;
}
```

### 861-870. **Server State vs Client State**
Differentiating: Server state (API data, cached, synchronized), Client state (UI state, forms, local-only), when to use each, patterns for managing both.

### 871-880. **Data Normalization**
```jsx
// Normalized state structure
const state = {
  cars: {
    byId: {
      '1': { id: '1', name: 'Tesla Model 3', categoryId: 'electric' },
      '2': { id: '2', name: 'BMW X5', categoryId: 'suv' },
    },
    allIds: ['1', '2'],
  },
  categories: {
    byId: {
      'electric': { id: 'electric', name: 'Electric' },
      'suv': { id: 'suv', name: 'SUV' },
    },
    allIds: ['electric', 'suv'],
  },
};

// Selectors
const selectCarById = (state, id) => state.cars.byId[id];
const selectCarsByCategory = (state, categoryId) => 
  state.cars.allIds
    .map(id => state.cars.byId[id])
    .filter(car => car.categoryId === categoryId);
```

### 881-890. **Pagination & Infinite Scroll**
```jsx
import { useInfiniteQuery } from '@tanstack/react-query';

function useInfiniteCars() {
  return useInfiniteQuery({
    queryKey: ['cars'],
    queryFn: ({ pageParam = 1 }) => 
      fetch(`/api/cars?page=${pageParam}`).then(r => r.json()),
    getNextPageParam: (lastPage, pages) => 
      lastPage.hasMore ? pages.length + 1 : undefined,
  });
}

function CarList() {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteCars();
  
  return (
    <div>
      {data?.pages.map((page, i) => (
        <div key={i}>
          {page.cars.map(car => <CarCard key={car.id} car={car} />)}
        </div>
      ))}
      <button onClick={() => fetchNextPage()} disabled={!hasNextPage}>
        {isFetchingNextPage ? 'Loading...' : 'Load More'}
      </button>
    </div>
  );
}
```

### 891-900. **Advanced State Patterns**
Topics: Finite state machines with XState, state machines for booking flow, reducers with middleware, state persistence, hydration strategies, devtools integration, testing state management, performance optimization with selectors.

**Summary**: This section covers 100 comprehensive questions on state management and data fetching, demonstrating modern patterns for building scalable car rental applications with efficient data synchronization and caching.
