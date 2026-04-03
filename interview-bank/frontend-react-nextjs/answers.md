# Frontend React & Next.js - Complete Answers

1. React hooks enable functional components with state/effects. `useState` for state, `useEffect` for side effects, `useContext` for context, `useReducer` for complex state. Replaced class components in modern React.

2. useState hook: `const [count, setCount] = useState(0)`. Initial value passed to useState, returns current state and setter function. Setter triggers re-render with new state.

3. useEffect hook: `useEffect(() => { /* side effect */ }, [dependencies])`. Runs after render, array of dependencies determines when to re-run. Empty array runs once on mount, omit for every render.

4. Custom hooks: encapsulate reusable logic. `function useLocalStorage(key, initial) { const [value, setValue] = useState(initial); useEffect(() => { localStorage.setItem(key, JSON.stringify(value)); }, [value, key]); return [value, setValue]; }`.

5. useReducer: for complex state with multiple actions. Dispatches actions, reducer function computes next state. Better than multiple useState for related state changes.

6. useContext: subscribe to context value without prop drilling. `const theme = useContext(ThemeContext)`. Provider wraps component tree, consumers access value.

7. useCallback: memoize function reference. Prevents unnecessary re-renders of child components expecting same function. `const memoized = useCallback(() => { ... }, [deps])`.

8. useMemo: memoize expensive computation. `const memoized = useMemo(() => expensiveCalculation(a, b), [a, b])`. Recomputes only when dependencies change.

9. useRef: access DOM directly or store mutable value. `const inputRef = useRef(null); inputRef.current.focus()`. Doesn't trigger re-render when updated.

10. useLayoutEffect: like useEffect but fires before browser paints. Use for layout measurements, DOM mutations that affect layout. Blocks painting, use sparingly.

11. Props drilling: passing props through many component levels. Solution: Context API or state management library (Redux, Zustand).

12. React Context: `const MyContext = createContext()`. Provider: `<MyContext.Provider value={value}>`. Consumer: `useContext(MyContext)`. Built-in state management.

13. Controlled components: form input value controlled by React state. `<input value={state} onChange={e => setState(e.target.value)} />`. React source of truth for form state.

14. Uncontrolled components: form input value managed by DOM. `<input defaultValue="initial" />`. Access via refs. Useful for file inputs (always uncontrolled).

15. Form handling: handle submit, validation, error messages. Libraries: Formik, React Hook Form simplify complex forms. Pre-fill, auto-save, multi-step forms.

16. Event bubbling: events propagate up DOM tree. Stop with `event.stopPropagation()` or `event.preventDefault()`. Important for nested components, click handlers.

17. React keys: help React identify which items changed in lists. `<li key={item.id}>{item.name}</li>`. Without keys, React may re-render unnecessarily, lose component state.

18. Conditional rendering: `{condition ? <A /> : <B />}` or `{condition && <A />}`. Render different components based on state/props.

19. List rendering: `{items.map(item => <Item key={item.id} {...item} />)}`. Keys important, filter/sort before rendering, avoid inline arrow functions.

20. React lazy loading: `const LazyComponent = lazy(() => import('./Component')); <Suspense fallback={<div>Loading...</div>}><LazyComponent /></Suspense>`. Code splitting.

21. Fragment: `<>content</>` or `<React.Fragment>content</React.Fragment>`. Avoids extra wrapper div, useful for returning multiple elements.

22. Strict Mode: `<StrictMode>` wraps app in development. Highlights potential issues (side effects, unsafe lifecycle methods). Removed in production build.

23. Refs in class components: `this.inputRef = React.createRef()` in constructor, `this.inputRef.current` to access. Avoid refs in functional components (use useRef instead).

24. Forward refs: pass ref through component: `const Input = forwardRef((props, ref) => <input ref={ref} {...props} />)`. Exposes DOM element from custom component.

25. Higher-order components (HOC): function taking component, returning enhanced component. `function withTheme(Component) { return (props) => <Component theme={useTheme()} {...props} />; }`. Alternative to hooks.

26. Render props: component accepts function as prop that returns elements. `<Mouse render={mouse => <Cat {...mouse} />} />`. Alternative to HOC, better TypeScript support.

27. Error boundaries: catch errors in child component tree, display fallback UI. Class component only: `getDerivedStateFromError`, `componentDidCatch`. Prevents white screen.

28. Performance optimization: React.memo (memoize component), useMemo (memoize value), useCallback (memoize function), lazy loading, code splitting.

29. React DevTools: browser extension for debugging. Component hierarchy, props/state inspection, performance profiling, highlight re-renders.

30. Server-Side Rendering (SSR): render React on server, send HTML to browser. Better SEO, faster initial load, enables dynamic rendering. Requires server (Node.js).

31. Static Site Generation (SSG): pre-render pages at build time, serve static HTML. Fastest, great for static content, no server needed. Limited to pre-built routes.

32. Incremental Static Regeneration (ISR): pre-render at build time, regenerate on-demand when stale. Best of both worlds: static performance + dynamic content.

33. Client-side rendering (CSR): render in browser using JavaScript. Slower initial load, worse SEO, enables rich interactivity. Suitable for SPAs.

34. Next.js Pages Router: files in `pages/` directory become routes. `pages/users/[id].js` maps to `/users/:id`. Enables SSR, SSG, ISR per page.

35. Next.js App Router: newer routing system using file system. `app/users/[id]/page.js` maps to `/users/:id`. Enables React Server Components, better organization.

36. React Server Components: render on server, no JavaScript sent to client. Reduces bundle size, direct database access. Mix with Client Components for interactivity.

37. getServerSideProps: fetch data on server per request. `export async function getServerSideProps(context) { return { props: { data } }; }`. Pages Router only, SSR.

38. getStaticProps: fetch data at build time. `export async function getStaticProps() { return { props: { data }, revalidate: 60 }; }`. ISR with revalidation time. Pages Router.

39. getStaticPaths: define which dynamic routes to pre-render at build time. `export async function getStaticPaths() { return { paths: [{params: {id: '1'}}], fallback: 'blocking' }; }`. Pages Router.

40. Image optimization: Next.js Image component lazy loads, serves responsive sizes, modern formats (WebP). `import Image from 'next/image'; <Image src="/pic.jpg" alt="pic" width={200} height={200} />`.

41. Font optimization: self-host fonts, async load, prevent layout shift. Next.js Font Module: `import { Inter } from 'next/font/google'; const inter = Inter();`.

42. API routes: create backend endpoints in `pages/api/users.js`. Becomes `/api/users` endpoint. Use for webhooks, data fetching, authentication.

43. Middleware in Next.js: run code before request is processed. `middleware.js` in root, pattern matching for routes. Use for auth, logging, redirects.

44. Environment variables: `.env.local` for secrets (not committed), `.env` for public variables. Access with `process.env.NEXT_PUBLIC_*` (client), `process.env.*` (server).

45. Dynamic imports: `import dynamic from 'next/dynamic'; const Component = dynamic(() => import('./Component'));` for code splitting, lazy loading.

46. Redux state management: centralized state store, reducers compute next state, actions describe changes. `store.subscribe()` notifies of changes. Useful for complex state.

47. Redux toolkit: simplified Redux with immer for immutable updates, redux-thunk for async. `createSlice` reduces boilerplate, `createAsyncThunk` for API calls.

48. Redux DevTools: time-travel debugging, action history, state inspection. Powerful debugging tool for Redux apps.

49. Zustand: lightweight state management. `create()` creates store, `useStore()` hook subscribes. Simpler than Redux, great for smaller apps.

50. Recoil state management: atomic state, selectors for derived state. `RecoilRoot` provider wraps app, `useRecoilState()` hook for state. Facebook's alternative to Redux.

51. MobX: reactive state management via decorators. Auto-track dependencies, re-render on change. Simpler than Redux for some use cases.

52. SWR: data fetching library by Vercel. `useSWR(key, fetcher)` returns data, error, loading. Built-in caching, revalidation, optimistic updates.

53. TanStack Query (React Query): powerful data fetching. Background refetching, caching with versioning, mutations. Better for complex API interactions than SWR.

54. Axios vs Fetch: Axios auto-transforms JSON, supports interceptors, timeout built-in. Fetch native API, simpler, no extra dependency.

55. Error handling in React: try-catch in async functions, error boundaries for component errors, global error handler in API client.

56. Loading states: show spinner/skeleton while data loads. `loading ? <Spinner /> : <Component />`. Improves UX, shows app is working.

57. Optimistic updates: update UI immediately, sync server async. If server rejects, rollback UI. Better UX, risky if server error.

58. Pagination in React: load page on button click or scroll. Infinite scroll vs pagination. Store page in URL (`?page=2`) for bookmarking.

59. Search implementation: debounce user input to avoid excessive queries. `useEffect` with debounce, only search when user stops typing. Filters or API search.

60. Filtering and sorting: filter client-side if small dataset, server-side if large. Store filter state in URL or Redux, restore on page reload.

61. CSS modules: scoped CSS per component. `import styles from './Button.module.css'; <button className={styles.primary}>`. Avoids CSS class conflicts.

62. Tailwind CSS: utility-first CSS framework. `<button className="bg-blue-500 hover:bg-blue-600 px-4 py-2">`. Reduces CSS writing, consistent design.

63. Styled components: CSS-in-JS, scoped styles, dynamic styling with props. `const Button = styled.button\`background: ${props => props.primary ? 'blue' : 'gray'};\``.

64. CSS-in-JS performance: generate CSS string per render (slower) vs generate once (faster). Libraries use caching, server-side extraction for SSR.

65. Responsive design: media queries for different screen sizes. Mobile-first approach (`mobile -> tablet -> desktop`). Tailwind responsive classes: `sm:`, `md:`, `lg:`, `xl:`.

66. Dark mode: store preference in localStorage or system preference. Toggle button switches theme. CSS variable override or Tailwind dark mode class.

67. Accessibility: semantic HTML, ARIA attributes, keyboard navigation, color contrast, alt text for images. Essential for inclusive apps.

68. Testing React components: Jest (unit tests), React Testing Library (component tests), Cypress (e2e tests). Test behavior not implementation.

69. Mocking API calls: jest.mock() for module mocking, MSW (Mock Service Worker) for API mocking. Enables testing without network.

70. Component composition: build complex UIs from simple reusable components. Props for customization, children for content. Improves maintainability.

71. Container vs Presentational: container handles logic and state, presentational renders UI. Separation of concerns, reusability.

72. Render optimization: only re-render when props/state change. React.memo prevents re-render if props same. Expensive renders need optimization.

73. Virtual scrolling: render only visible items in large lists. Libraries: react-window, react-virtualized. Essential for 1000+ item lists.

74. Code splitting: split code into chunks, load on-demand. Lazy loading, dynamic imports, Webpack handles. Reduces initial bundle size.

75. Bundle analysis: check bundle size, identify large dependencies. webpack-bundle-analyzer, source-map-explorer show breakdown.

76. Tree shaking: remove unused code during bundling. ESM modules required, side-effect-free libraries enable tree shaking.

77. SEO for SPAs: Next.js Server Components or SSR, dynamic sitemap, structured data (Schema.org). Client-side only renders without SEO.

78. Open Graph tags: meta tags for social sharing. Define title, description, image. Improves appearance when shared on social media.

79. Web vitals: Core Web Vitals measure user experience. LCP (loading), FID (interactivity), CLS (stability). Use Next.js analytics or web-vitals library.

80. Service Workers: offline support, caching, background sync. workbox-webpack-plugin auto-generates service workers, next-pwa for PWA in Next.js.

81. PWA (Progressive Web App): web app with native app features. Offline, add to home screen, push notifications. manifest.json + service worker.

82. Preloading: `<link rel="preload" href="/font.woff2" as="font">` loads resource early. Preconnect for DNS lookup: `<link rel="preconnect" href="https://api.example.com">`.

83. Type safety: TypeScript for static type checking. Catch errors at compile-time, better IDE support, excellent for large codebases.

84. TypeScript with React: type props: `interface Props { name: string; age: number; }`, type children: `children?: ReactNode`, type events: `onChange: (e: ChangeEvent<HTMLInputElement>) => void`.

85. Dependency management: keep dependencies up-to-date for security patches. npm audit finds vulnerabilities, dependabot auto-updates. Remove unused dependencies.

86. Environment-specific configs: different API endpoints, keys per environment (dev, staging, production). Use environment variables, build-time constants.

87. Build optimization: tree shaking, minification, code splitting, image optimization. Webpack/Vite optimizes during build, serve smaller assets.

88. CI/CD for React: run tests, lint, build on commit. Publish to staging/production on approval. GitHub Actions, GitLab CI, Vercel auto-deploy from Git.

89. Monitoring: error tracking (Sentry), performance monitoring (Datadog), analytics (Mixpanel). Catch bugs in production, understand user behavior.

90. Debugging: React DevTools, browser DevTools, logging. VS Code debugger for Node.js APIs. Source maps for debugging transpiled code.

91. Component libraries: Storybook for component development and documentation. Build UI components in isolation, visual regression testing.

92. Design systems: shared components, design tokens, guidelines. Ensures consistency across apps, reduces design/dev overhead.

93. Animation: CSS animations for simple, Framer Motion for complex. Lottie for After Effects animations. Improve UX, but test performance.

94. Accessibility testing: axe DevTools finds issues, Lighthouse audits. WebAIM guidelines, WCAG standards. Essential for inclusive apps.

95. Web Components: reusable custom elements, shadow DOM, scoped styles. Interop with React via custom elements wrapper. Framework agnostic.

96. Micro frontends: split large apps into smaller pieces developed independently. Module federation in Webpack enables sharing code. Complexity vs scalability trade-off.

97. Electron for desktop: Electron wraps React in Chromium, enables native desktop apps. Example: VS Code. Node.js backend, HTML/CSS/JS frontend in one codebase.

98. React Native: write mobile apps with React. iOS and Android from same codebase. Bridge translates to native components. Useful for code sharing.

99. Testing libraries: Vitest (faster Jest), Playwright (e2e cross-browser), Puppeteer (headless Chrome automation). Different tools for different testing levels.

100. Production deployment: optimize bundle, enable compression, caching headers, CDN for static assets, monitoring/alerting. Vercel auto-deploys Next.js with optimization.

