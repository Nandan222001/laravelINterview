# Frontend React Nextjs Interview Questions

1. Implementing Custom Hooks with Advanced Patterns

2. React Server Components vs Client Components

3. Zustand vs Redux - Modern State Management

4. Virtual Scrolling Implementation

5. Advanced TypeScript Patterns in React

6. Show automatic batching behavior with car booking example

7. How do you opt out of automatic batching? Use `flushSync` from 'react-dom' to opt out:

8. What is Suspense in React 18? Suspense is a component that lets you specify fallback content while waiting for some code or data to load, enabling declarative loading states throughout your application.

9. Demonstrate Suspense with lazy loading car detail component

10. What are the benefits of Suspense for data fetching? Suspense enables: 1) Declarative loading states, 2) Parallel data fetching, 3) Better UX with coordinated loading, 4) Avoids waterfall requests, 5) Prevents layout shifts.

11. How does React.lazy work with Suspense?

12. What is startTransition API? startTransition marks updates as non-urgent transitions, allowing React to interrupt them for more urgent updates:

13. When should you use startTransition vs useTransition? Use useTransition hook when you need the isPending state. Use startTransition function when you only need to mark updates as transitions without tracking pending state.

14. Create a car filter component using useTransition

15. What is the relationship between Suspense and Error Boundaries? Error Boundaries catch errors during rendering, while Suspense handles async loading. They work together:

16. How do you create a Suspense-compatible data source?

17. What is the useId hook in React 18? useId generates unique IDs that are stable across server and client rendering, useful for accessibility attributes:

18. How does concurrent rendering improve perceived performance? Concurrent rendering keeps the UI responsive by interrupting long renders to handle user input, showing immediate feedback, and prioritizing visible updates over off-screen work.

19. What are transition updates vs urgent updates? Urgent updates: Direct user interactions (typing, clicking) that need immediate feedback. Transition updates: UI changes that can be delayed (search results, filters) without hurting UX.

20. Implement a debounced search with useTransition

21. What is the new root API in React 18?

22. What's the difference between createRoot and legacy render? createRoot enables concurrent features and automatic batching. Legacy render doesn't support concurrent rendering:

23. How do you handle Suspense boundaries strategically? Place Suspense boundaries at route levels, feature boundaries, or around expensive components:

24. What is selective hydration in React 18? Selective hydration allows React to hydrate parts of the page before the entire JavaScript bundle loads, prioritizing components based on user interaction.

25. Demonstrate streaming SSR with Suspense

26. How does useDeferredValue help with performance? It defers updating expensive UI parts until after urgent updates complete:

27. What is the useSyncExternalStore hook? It safely subscribes to external stores with concurrent rendering:

28. Create a custom hook for concurrent search

29. How do you prioritize updates in React 18? Use useTransition for low-priority updates, keep urgent updates (input changes) outside transitions, and let React handle the scheduling:

30. What are the performance benefits of concurrent rendering? 1) Better responsiveness during heavy renders, 2) Reduced input lag, 3) Smoother animations, 4) Better perceived performance, 5) Ability to abandon outdated work.

31. Explain render phases in concurrent mode Concurrent mode has two phases: 1) Render phase (interruptible, can be paused/resumed), 2) Commit phase (synchronous, applies DOM changes).

32. How do you measure concurrent rendering performance?

33. What is time slicing in React? Time slicing is React's ability to split rendering work into chunks and spread it across multiple frames, preventing the main thread from blocking.

34. Create a transition-aware loading indicator

35. How does Suspense work with React Router?

36. What are the best practices for using useTransition? 1) Use for non-urgent updates, 2) Show visual feedback with isPending, 3) Don't wrap user input updates, 4) Combine with useMemo for expensive computations, 5) Keep transitions independent.

37. Implement optimistic UI updates with transitions

38. How do you handle race conditions with concurrent rendering? Use cleanup functions and track request IDs:

39. What is the purpose of React.memo with concurrent features? React.memo prevents unnecessary re-renders, which is crucial with concurrent rendering to avoid redundant work:

40. Create a progressive enhancement pattern with Suspense

41. How do you use useDeferredValue for list rendering?

42. What are the limitations of concurrent rendering? 1) Not all updates should be transitions, 2) Requires proper component structure, 3) Effects still run synchronously after commit, 4) Suspense boundaries need careful placement.

43. Implement a custom Suspense-like component

44. How do you handle loading states in nested Suspense boundaries?

45. What is the useInsertionEffect hook? useInsertionEffect fires before DOM mutations, useful for CSS-in-JS libraries:

46. Create a virtualized list with concurrent rendering

47. How does automatic batching affect useEffect timing? With automatic batching, multiple state updates are batched, so useEffect runs once after all updates instead of after each one.

48. Implement a loading skeleton with Suspense

49. What are the differences between React 17 and React 18 event handling? React 18 attaches events to the root instead of document, fixes issues with stopPropagation, and maintains consistent behavior across frameworks.

50. Create a concurrent-safe context provider

51. How do you handle form submissions with transitions?

52. What is the purpose of React.StrictMode with concurrent features? StrictMode helps identify components with side effects by double-invoking render and effects in development, crucial for concurrent rendering safety.

53. Implement error handling with Suspense

54. How do you coordinate multiple transitions?

55. What are the server rendering improvements in React 18? React 18 introduces: 1) Streaming HTML, 2) Selective hydration, 3) Suspense on the server, 4) Progressive hydration, 5) Better error handling.

56. Create a preloading strategy with Suspense

57. How do you test components with useTransition?

58. What is the impact of concurrent rendering on third-party libraries? Libraries need to: 1) Use useSyncExternalStore, 2) Handle render interruptions, 3) Avoid relying on render order, 4) Clean up effects properly.

59. Implement a pagination system with transitions

60. How do you optimize Suspense boundary placement? Place boundaries at: 1) Route level for page transitions, 2) Feature level for independent sections, 3) Component level for lazy-loaded parts, 4) Avoid too many nested boundaries.

61. Create a multi-step form with transitions

62. What are hydration mismatches and how to prevent them? Hydration mismatches occur when server-rendered HTML differs from client render. Prevent by: 1) Avoiding browser-only APIs during SSR, 2) Using useEffect for client-only code, 3) Consistent data.

63. Implement a real-time search with useDeferredValue

64. How does React 18 handle memory leaks? React 18 improves memory management with automatic cleanup, better handling of abandoned work, and stricter effects in StrictMode.

65. Create a concurrent-safe custom hook for API calls

66. What is the relationship between Suspense and lazy loading? Suspense handles the loading state while lazy() creates dynamically imported components:

67. Implement a tab system with transitions

68. How do you handle SSR with concurrent features? Use renderToPipeableStream for streaming, wrap async components in Suspense, handle hydration with priority-based loading.

69. Create a data fetching hook with Suspense

70. What are the best practices for error boundaries with Suspense? Place error boundaries above Suspense boundaries, handle both sync and async errors, provide retry mechanisms, log errors appropriately.

71. Implement a concurrent-safe counter

72. How do you optimize React 18 apps for mobile devices? Use transitions for expensive operations, implement proper Suspense boundaries, lazy load routes, optimize images, use service workers.

73. Create a complex filter system with concurrent rendering

74. What is the impact of concurrent rendering on animations? Concurrent rendering can improve animation performance by prioritizing animation frames, but requires proper use of transitions to avoid janky animations.

75. Implement a search autocomplete with useDeferredValue

76. How do you handle offline mode with concurrent features?

77. What are the testing strategies for concurrent React apps? Use React Testing Library with act(), test transitions separately, mock concurrent features in tests, use waitFor for async updates.

78. Create a progressive image loader with Suspense

79. How do you migrate from React 17 to React 18? 1) Update React and ReactDOM, 2) Replace ReactDOM.render with createRoot, 3) Update testing library, 4) Test automatic batching, 5) Adopt concurrent features gradually.

80. Implement a debounced filter with transitions

81. What is the purpose of the useMutableSource hook? Note: useMutableSource was replaced by useSyncExternalStore in React 18 for safely reading from external mutable sources.

82. Create a modal system with Suspense and portals

83. How do you handle route transitions in React 18?

84. What are the performance implications of nested Suspense? Nested Suspense can cause loading waterfalls if not careful. Use parallel data fetching and strategic boundary placement to avoid sequential loading.

85. Implement a concurrent-safe shopping cart

86. How do you optimize large lists with concurrent rendering? Use windowing/virtualization, implement pagination with transitions, use useDeferredValue for filtering, memoize expensive computations.

87. Create a real-time notification system with concurrent features

88. What is the role of React Profiler with concurrent rendering? Profiler measures render performance, identifies slow components, tracks concurrent rendering phases, helps optimize transitions.

89. Implement a wizard form with transitions and validation

90. How do you handle memory-intensive operations with concurrent rendering? Use transitions to allow interruption, implement pagination, use Web Workers for heavy computation, optimize data structures.

91. Create a comparison tool using concurrent features

92. What are the caching strategies for Suspense data fetching? Implement request deduplication, use SWR or React Query, cache at resource level, implement stale-while-revalidate pattern.

93. Implement infinite scroll with concurrent rendering

94. How do you handle focus management with concurrent rendering? Use refs to maintain focus, implement focus trapping in modals, handle focus restoration after transitions, use focus-visible for accessibility.

95. Create a dynamic dashboard with lazy-loaded widgets

96. What are the SEO implications of Suspense and lazy loading? Suspense with SSR provides full HTML for SEO, lazy loading doesn't affect SEO with proper SSR, ensure critical content renders on server.

97. Implement a calendar view with concurrent date selection

98. How do you debug concurrent rendering issues? Use React DevTools Profiler, enable concurrent mode flags, check for race conditions, verify proper cleanup, test with CPU throttling.

99. Create a collaborative booking system with real-time updates

100. What are the future developments expected in React concurrent rendering? Improved automatic optimization, better server components integration, enhanced Suspense features, more granular priority control, better dev tools.

101. What is the Next.js 14 App Router? The App Router is a new routing system in Next.js 14 built on React Server Components, supporting layouts, nested routes, loading states, and error handling with a file-system based approach in the `app` directory.

102. What are Server Components in Next.js 14? Server Components render on the server by default, reducing JavaScript bundle size, enabling direct database access, and improving initial page load performance.

103. How do you create a Client Component in Next.js 14?

104. Demonstrate nested layouts in Next.js 14

105. How does file-based routing work? File structure determines routes: `app/page.tsx` → `/`, `app/cars/page.tsx` → `/cars`, `app/cars/[id]/page.tsx` → `/cars/:id`

106. Create a dynamic route for car details

107. What are loading.tsx files?

108. How do error.tsx files work?

109. Implement route groups Route groups organize routes without affecting URL structure using parentheses:

110. What are parallel routes?

111. How do you fetch data in Server Components?

112. How do you implement Server Actions?

113. Implement Route Handlers

114. What is middleware in Next.js 14?

115. How do you implement streaming with Suspense?

116. What are generateStaticParams for?

117. How do you implement dynamic metadata?

118. What is route segment config?

119. How do you use search params?

120. What is useSearchParams in Client Components?

121. What is the useRouter hook?

122. How do you implement prefetching?

123. Implement optimistic updates with Server Actions

124. How do you handle cookies?

125. What is the redirect function?

126. Implement authentication in App Router

127. What are the caching strategies? 1) Full Route Cache (static), 2) Request Memoization, 3) Data Cache (fetch), 4) Router Cache (client-side).

128. How do you opt out of caching?

129. Implement pagination

130. How do you implement sitemap.xml?

131. How do you create robots.txt?

132. Implement i18n

133. What are route handlers with edge runtime?

134. How do you handle webhooks?

135. What is useSelectedLayoutSegment?

136. Implement draft mode

137. How do you share data between Server Components?

138. Implement multi-step form

139. How do you handle file uploads?

140. What is usePathname?

141. Implement rate limiting

142. How do you implement CORS?

143. What is notFound()?

144. Implement CSP

145. How do you implement A/B testing?

146. What are catch-all routes?

147. What are optional catch-all routes?

148. Implement infinite scroll

149. How do you implement form validation?

150. Implement session management

151. How do you implement progressive enhancement?

152. Implement feature flags

153. How do you implement request deduplication?

154. Implement protected routes

155. How do you implement RBAC?

156. Implement cache tags

157. How do you handle loading skeletons?

158. Implement custom error pages

159. What is permanentRedirect?

160. How do you implement opengraph images?

161. Implement analytics tracking

162. How do you implement theme switching?

163. Implement scroll restoration

164. How do you implement data mutations?

165. Implement conditional redirects

166. How do you implement content negotiation?

167. Implement custom 404 per route

168. How do you implement background jobs?

169. What is template.tsx? template.tsx creates a new instance on navigation, unlike layout.tsx which preserves state. Use for animations.

170. Implement modal with intercepting routes

171. How do you use headers in Server Components?

172. Implement streaming data

173. How do you handle revalidation?

174. Implement server-side cookies manipulation

175. How do you implement search functionality?

176. Implement sorting and filtering

177. How do you implement breadcrumbs?

178. Implement data prefetching

179. How do you handle query parameters?

180. Implement lazy loading components

181. How do you implement API rate limiting?

182. Implement webhook verification

183. How do you handle environment variables?

184. Implement custom metadata

185. How do you implement RSS feed?

186. Implement API versioning

187. How do you implement pagination metadata?

188. Implement image uploads with validation

189. How do you implement database transactions?

190. Implement email sending

191. How do you implement soft deletes?

192. Implement audit logging

193. How do you implement search with filters?

194. Implement API authentication

195. How do you implement API pagination?

196. Implement data export

197. How do you implement batch operations?

198. Implement full-text search

199. How do you implement data validation middleware?

200. Implement complex data relationships

201. What is code-splitting in React? Code-splitting breaks your bundle into smaller chunks loaded on demand, reducing initial load time. React supports it via dynamic imports and React.lazy().

202. Implement dynamic imports in Next.js

203. How does React.lazy() work?

204. Demonstrate route-based code-splitting

205. What are the benefits of code-splitting? 1) Reduced initial bundle size (20-40% smaller), 2) Faster initial page load, 3) Better Time to Interactive (TTI), 4) Improved Core Web Vitals scores, 5) Better caching strategy.

206. How do you lazy load images?

207. Implement component-level code-splitting

208. How do you preload critical resources?

209. Demonstrate lazy loading with Intersection Observer

210. What is tree shaking? Tree shaking eliminates dead code from the bundle. Import only what you need:

211. How do you optimize bundle size? 1) Use dynamic imports, 2) Tree shake unused code, 3) Use production builds, 4) Minimize dependencies, 5) Use lighter alternatives (date-fns vs moment).

212. Implement conditional loading

213. How do you split vendor bundles?

214. What is the impact of code-splitting on performance? Reduces First Contentful Paint by 30-50%, improves Time to Interactive by 40%, reduces initial bundle from 500KB to 150KB, improves Lighthouse score by 20-30 points.

215. Implement module preloading

216. How do you analyze bundle size?

217. Demonstrate lazy loading third-party scripts

218. What is prefetching vs preloading? Preload: High priority, needed soon (fonts, critical images). Prefetch: Low priority, might be needed later (next page resources).

219. Implement smart prefetching

220. How do you optimize CSS delivery?

221. Implement critical CSS extraction

222. How do you implement progressive hydration?

223. Demonstrate font optimization

224. What is selective hydration? Selective hydration allows React to prioritize hydrating components based on user interaction, hydrating interactive parts first while deferring others.

225. Implement resource hints

226. How do you optimize JavaScript execution? 1) Use Web Workers for heavy computation, 2) Debounce expensive operations, 3) Use requestIdleCallback, 4) Break up long tasks, 5) Use memoization.

227. Implement Web Worker for search

228. What are performance budgets? Set limits for bundle sizes, load times, and metrics. Example: JS bundle < 200KB, FCP < 1.8s, LCP < 2.5s.

229. Implement performance monitoring

230. How do you optimize third-party scripts?

231. Demonstrate deferred component loading

232. What is render-as-you-fetch? Fetch data concurrently with rendering, not sequentially. Start fetching before component renders.

233. Implement optimistic data fetching

234. How do you minimize main thread blocking? Break long tasks into chunks using scheduler:

235. Implement virtualized list for performance

236. What is the impact of removing unused CSS? Reduces CSS bundle by 50-80%, improves FCP by 200-500ms, reduces page weight by 100-300KB.

237. Implement CSS purging

238. How do you implement skeleton screens?

239. Demonstrate image optimization strategies

240. What is componen-level caching? Cache expensive component renders to avoid re-computation:

241. How do you implement request waterfall prevention?

242. Implement data prefetching on hover

243. What are the benefits of SSG over SSR? SSG: Faster (served from CDN), cheaper (no compute), better SEO, cacheable. Use for static content. SSR: Dynamic data, personalization, real-time updates.

244. Implement incremental static regeneration

245. How do you optimize for mobile devices? 1) Reduce bundle size, 2) Optimize images for mobile, 3) Use responsive images, 4) Minimize main thread work, 5) Use service workers.

246. Implement responsive image loading

247. Demonstrate code splitting by route

248. What is the impact of memoization? Prevents unnecessary re-renders and recalculations. Can improve render performance by 40-60% for expensive components.

249. Implement comprehensive memoization

250. How do you optimize re-renders? 1) Use React.memo, 2) Use useMemo/useCallback, 3) Split components, 4) Use proper key props, 5) Avoid inline objects/functions.

251. Implement render optimization

252. What is bundle splitting strategy? Split by: 1) Routes (automatic in Next.js), 2) Vendors (React, libraries), 3) Common chunks (shared code), 4) Async components.

253. Implement efficient data fetching

254. How do you measure component performance?

255. Implement performance logging

256. What is the impact of reducing JavaScript? Every 100KB of JS removed improves TTI by 200-300ms on mobile, reduces parse time by 50-100ms, improves FID score.

257. Implement lightweight alternatives

258. How do you optimize asset delivery? 1) Use CDN, 2) Enable compression (gzip/brotli), 3) Set cache headers, 4) Use modern formats (WebP, AVIF), 5) Implement lazy loading.

259. Implement service worker for caching

260. What is the 50ms budget for interactions? User interactions should respond within 50ms to feel instant. Use debouncing and transitions for expensive updates.

261. Implement debounced search

262. How do you reduce Time to First Byte (TTFB)? 1) Use edge functions, 2) Implement caching, 3) Optimize database queries, 4) Use CDN, 5) Enable HTTP/2 or HTTP/3.

263. Implement edge caching

264. What is resource prioritization? Load critical resources first: fonts, above-fold images, critical CSS. Defer non-critical: analytics, chat widgets, below-fold content.

265. Implement priority hints

266. How do you optimize font loading?

267. Demonstrate compression configuration

268. What is the impact of HTTP/2? HTTP/2 enables multiplexing (multiple requests over one connection), header compression, server push. Reduces latency by 20-30%.

269. Implement connection optimization

270. How do you handle slow networks? 1) Show loading states, 2) Implement offline mode, 3) Use service workers, 4) Progressive enhancement, 5) Reduce payload size.

271. Implement network-aware loading

272. What is adaptive loading? Serve different experiences based on device capabilities, network speed, and user preferences.

273. Implement adaptive component loading

274. How do you prevent layout shifts? 1) Set dimensions for images/videos, 2) Reserve space for dynamic content, 3) Use font fallbacks, 4) Avoid inserting content above existing content.

275. Implement CLS prevention

276. What is the Rail Model? Response (50ms), Animation (16ms per frame), Idle (50ms chunks), Load (< 1000ms to interactive).

277. Implement RAIL-optimized interactions

278. How do you optimize for Core Web Vitals? LCP: Optimize images, remove render-blocking resources. FID: Minimize JS, use web workers. CLS: Set dimensions, avoid dynamic content insertion.

279. Implement Web Vitals monitoring

280. What is the impact of reducing payload size? Every 100KB reduction improves load time by 200-400ms on 3G, reduces data costs for users, improves mobile performance by 30-50%.

281. Implement payload optimization

282. How do you implement streaming responses?

283. Demonstrate batch processing for performance

284. What is the importance of First Input Delay (FID)? FID measures responsiveness. Target < 100ms. Long tasks block main thread causing poor FID. Break up JavaScript execution.

285. Implement FID optimization

286. How do you optimize Largest Contentful Paint (LCP)? 1) Optimize images, 2) Preload key resources, 3) Remove render-blocking CSS/JS, 4) Use CDN, 5) Implement SSR/SSG.

287. Implement LCP optimization

288. What is Cumulative Layout Shift (CLS)? CLS measures visual stability. Target < 0.1. Caused by images without dimensions, dynamic content insertion, web fonts.

289. Implement CLS optimization

290. How do you optimize Time to Interactive (TTI)? 1) Reduce JavaScript, 2) Code-split, 3) Defer non-critical scripts, 4) Minimize main thread work, 5) Use server rendering.

291. Implement TTI optimization

292. What is Interaction to Next Paint (INP)? INP measures responsiveness to user interactions throughout page lifetime. Target < 200ms. Replaces FID.

293. Implement INP optimization

294. How do you measure bundle size impact? Use bundle analyzer, measure before/after, track over time, set budgets, monitor in CI/CD.

295. Implement bundle size monitoring

296. What is the 14KB initial budget? Aim for < 14KB of critical CSS/JS (gzipped) for first render to fit in first TCP roundtrip (14KB in slow 3G).

297. Implement critical CSS extraction

298. How do you optimize database queries? 1) Use indexes, 2) Limit selected fields, 3) Batch requests, 4) Use caching, 5) Implement pagination.

299. Implement optimized data fetching

300. What is the performance impact summary? Implementing all optimizations: 40-60% faster load times, 30-50% smaller bundles, 20-30 point Lighthouse improvement, 50-70% better mobile performance, $20-$30% reduction in hosting costs.

301. How do you set up Webpack Bundle Analyzer in Next.js?

302. What insights does bundle analyzer provide? Shows: 1) Parsed size (actual code), 2) Stat size (before minification), 3) Gzipped size (transferred), 4) Module composition, 5) Duplicate dependencies, 6) Large modules.

401. What is the next/image component?

402. What are the benefits of WebP format? WebP provides 25-35% smaller file sizes than JPEG, supports transparency like PNG, provides better compression, faster loading, and is supported by 95%+ browsers.

403. What is AVIF format? AVIF provides 50% better compression than JPEG, 20% better than WebP, excellent quality at low bitrates, supports HDR, but has limited browser support (90%).

501. What are Core Web Vitals? Three key metrics: LCP (Largest Contentful Paint < 2.5s), FID/INP (First Input Delay < 100ms / Interaction to Next Paint < 200ms), CLS (Cumulative Layout Shift < 0.1).

502. How do you measure Web Vitals in Next.js?

601. How do you implement metadata in Next.js 14?

602. What is structured data for car rentals?

701. Create a custom useCarSearch hook

801. Implement global state with Zustand

802. What is SWR and how to use it?

901. How do you test React components?

