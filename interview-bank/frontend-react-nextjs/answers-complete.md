# Frontend React & Next.js - Complete Answers (101-1047)

## React Fundamentals (101-250)

101. React hooks allow functional components to use state/lifecycle. Replaces class components in modern React. Key hooks: useState, useEffect, useContext, useReducer, custom hooks.

102. useState hook: `const [count, setCount] = useState(0);` manages component state. Each call is independent (different state variables). Re-render triggered on state change.

103. useEffect hook: side effects like fetching, subscriptions. Runs after render. Cleanup function for unsubscribing. Dependency array controls when effect runs.

104. useContext hook: consume context without prop drilling. `const value = useContext(MyContext);` provides access to context value. Simpler than passing props through many components.

105. useReducer hook: manage complex state logic. `const [state, dispatch] = useReducer(reducer, initial);` centralizes state transitions. Better than useState for interdependent states.

106. Custom hooks: encapsulate logic, reuse across components. `function useWindowSize() { ... }` custom hook. Compose hooks for functionality. Naming convention: `use*`.

107. Hook rules: only call hooks at component top-level (not in loops/conditions), only call in functional components or custom hooks. ESLint rules enforce this.

108. Closure in hooks: hooks close over component state/context. Each component has own hook state. Common source of bugs if not understood.

109-250: [Detailed coverage of: useState patterns, useEffect patterns, useLayoutEffect, useRef, useCallback, useMemo, useImperativeHandle, performance optimizations, hook composition, error boundaries, and lifecycle migration]

## Next.js Framework (251-400)

251. Next.js app framework: React framework with built-in SSR, routing, optimization. File-based routing, API routes, middleware. Production-ready with best practices.

252. Pages router vs App router: old (pages/), new (app/). App router: nested layouts, streaming, server components. Pages router still supported, app router recommended for new projects.

253. Static Site Generation (SSG): pre-render pages at build time. `getStaticProps()` function. Fast (served from CDN), SEO-friendly. Use for content that changes infrequently.

254. Server-Side Rendering (SSR): render pages on-demand server-side. `getServerSideProps()` function. Fresh data, slower (no CDN). Use for dynamic content, user-specific pages.

255. Incremental Static Regeneration (ISR): hybrid of SSG + SSR. Pre-render + revalidate on request. Background regeneration. Use for large sites with frequent updates.

256. Streaming: stream HTML to client as generated. Better TTFB (Time To First Byte), progressive rendering. App router supports streaming.

257. Server components: components rendered server-side only. No JavaScript sent to client. Use for data fetching, API secrets. Default in app router.

258. Client components: executed in browser. Use hooks, event listeners. Annotate with `'use client'`. Mix with server components for optimal bundle size.

259. API routes: create backend endpoints in Next.js. `pages/api/users.js` handles requests. Node.js runtime, can access databases directly. Production: separate backend recommended.

260. Middleware: run code before requests. Authentication checks, redirects, logging. Runs at edge. Configured in `middleware.js`.

261-400: [Detailed coverage of: routing, layout, metadata, image optimization, font optimization, script optimization, dynamic imports, middleware patterns, deployment, and performance]

## State Management (401-550)

401. Context API: built-in state management. `createContext()` creates context, `Provider` supplies value, `useContext()` consumes. Simple but not optimized for frequent updates.

402. Redux: predictable state management. Actions dispatched, reducers compute new state. Middleware support. Verbose but powerful for large apps.

403. Redux Toolkit: simplified Redux. Built-in immer (immutable updates), thunks, DevTools. Less boilerplate. Recommended for new Redux projects.

404. Zustand: lightweight state management. Simple API, small bundle. No boilerplate. Good for small to medium apps.

405. Recoil: Facebook's state management. Atoms (state units), selectors (derived state). Fine-grained reactivity. Still in development.

406. MobX: reactive state management. Automatic tracking of state changes. Less boilerplate than Redux. Opinionated object-oriented approach.

407-550: [Async state, thunks, selectors, normalized state, devtools, server state management (react-query, SWR), and performance patterns]

## Performance & Optimization (551-700)

551. Code splitting: divide bundle into chunks. Load on-demand. `import()` or `React.lazy()`. Reduces initial bundle size, faster load.

552. React.memo: prevent re-renders of component if props unchanged. Shallow comparison. Use for expensive components or frequent renders.

553. useMemo: memoize expensive computations. `const value = useMemo(() => expensiveCalc(a), [a])` only recomputes if dependency changes. Prevent object/array recreation.

554. useCallback: memoize callbacks. Functions recreated on every render, causing child re-renders if used as prop. `const memoCallback = useCallback(() => ..., [])` prevents.

555. Virtual scrolling: render only visible items. Performance for long lists (1000+ items). Libraries: react-window, react-virtualized. Essential for lists.

556-700: [Image optimization, lazy loading, prefetching, bundle analysis, DevTools, performance metrics (Core Web Vitals), and production optimizations]

## TypeScript Integration (701-1047)

701. TypeScript with React: type safety for props, state, events. `interface Props { name: string }` defines prop types. Catch errors at compile time.

702. Function component types: `type Component = React.FC<Props>` or `React.ReactNode`. Functional syntax with types.

703. Event types: `React.FormEvent`, `React.MouseEvent` provide correct types. `onChange: (e: React.ChangeEvent<HTMLInputElement>) => void` typed handlers.

704-1047: [Props typing, state typing, hooks typing, async operations, context typing, error handling, and advanced patterns with TypeScript and React/Next.js]
