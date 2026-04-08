# General PHP Interview Questions

## Section 1: PHP Fundamentals (Questions 1-100)

### PHP Basics (Questions 1-20)

1. What is PHP and what does it stand for?

2. Explain the difference between `echo` and `print` in PHP.

3. How do you declare a variable in PHP and what are the naming conventions?

4. What are the different data types available in PHP?

5. Explain the difference between single quotes and double quotes in PHP strings.

6. What is the purpose of the `<?php` and `?>` tags?

7. How do you write comments in PHP? (single-line and multi-line)

8. What is the difference between `isset()` and `empty()` functions?

9. Explain type juggling (automatic type conversion) in PHP.

10. What are superglobals in PHP? Name at least 5.

11. How do you concatenate strings in PHP?

12. What is the difference between `include`, `require`, `include_once`, and `require_once`?

13. Explain the concept of variable scope in PHP (local, global, static).

14. What is the `global` keyword used for in PHP?

15. How do you define and use constants in PHP?

16. What is the difference between `define()` and `const` for constants?

17. Explain the purpose of the `$_SERVER` superglobal with examples.

18. What is the difference between `GET` and `POST` methods in PHP?

19. How do you access GET and POST data in PHP?

20. What is the purpose of the `$_REQUEST` superglobal?

### Variables and References (Questions 21-40)

21. What are variable variables in PHP? Provide an example.

22. Explain pass-by-value vs pass-by-reference in PHP.

23. How do you pass a variable by reference to a function?

24. What is the `&` operator used for in variable assignment?

25. Write code to swap two variables using references.

26. Explain the difference between `unset()` and setting a variable to `null`.

27. How do you check if a variable is set and not null?

28. What is the scope resolution operator `::` used for?

29. Explain the purpose of the `static` keyword in variables.

30. How do static variables behave inside functions?

31. What are reference counting and garbage collection in PHP?

32. How do you create a reference to an array element?

33. Explain the behavior of references with foreach loops.

34. What happens when you unset a reference variable?

35. How do you check the type of a variable in PHP?

36. What is the difference between `gettype()` and `var_dump()`?

37. Explain the `is_*()` family of functions (is_int, is_string, etc.).

38. How do you convert between different data types in PHP?

39. What is type casting and how is it done in PHP?

40. Explain the difference between `(int)` and `intval()`.

### Type Comparison (Questions 41-60)

41. Explain the difference between `==` and `===` operators.

42. What is the output of `0 == "0"` and `0 === "0"`?

43. What is the result of `"1" == true` and why?

44. Explain loose comparison vs strict comparison with examples.

45. What is the output of `null == false` and `null === false`?

46. How does PHP compare arrays using `==` and `===`?

47. What is the result of comparing two objects with `==` vs `===`?

48. Explain the spaceship operator `<=>` introduced in PHP 7.

49. What is the null coalescing operator `??` and how does it work?

50. Explain the difference between `??` and `?:` (ternary operator).

51. What is the output of `var_dump("10" + "20")`?

52. How does PHP handle type coercion in arithmetic operations?

53. What is the result of `"10 apples" + "5 oranges"`?

54. Explain the behavior of boolean conversion for strings.

55. What strings are considered false in boolean context?

56. How does PHP compare strings alphabetically?

57. What is the output of `strcmp("10", "2")` vs `"10" < "2"`?

58. Explain the `<>` operator and its relationship to `!=`.

59. What is the identity operator and when should it be used?

60. How do you compare floating-point numbers safely in PHP?

### Arrays (Questions 61-80)

61. How do you create an indexed array in PHP?

62. How do you create an associative array in PHP?

63. What is the difference between indexed and associative arrays?

64. Explain multidimensional arrays with an example.

65. How do you add elements to an array?

66. What is the difference between `array_push()` and `$array[] = value`?

67. How do you remove elements from an array?

68. Explain the difference between `unset()` and `array_splice()` for arrays.

69. What is the difference between `array_merge()` and `array_combine()`?

70. How does `array_merge()` handle numeric vs string keys?

71. What is `array_merge_recursive()` and when is it used?

72. Explain the `+` operator for array union vs `array_merge()`.

73. What is the output of `[1, 2] + [2, 3]` vs `array_merge([1, 2], [2, 3])`?

74. How do you combine two arrays into key-value pairs?

75. What does `array_combine()` do and what are its requirements?

76. How do you check if a key exists in an array?

77. What is the difference between `isset($array['key'])` and `array_key_exists('key', $array)`?

78. How do you get all keys or values from an array?

79. Explain the difference between `in_array()` and `array_search()`.

80. How do you sort arrays in PHP? Name at least 4 sorting functions.

### String Functions (Questions 81-100)

81. What is the difference between `strlen()` and `mb_strlen()`?

82. How do you split a string into an array? Name two methods.

83. What is the difference between `explode()` and `str_split()`?

84. How does `explode()` handle delimiters and limits?

85. What is `implode()` and how is it related to `join()`?

86. Provide an example of converting an array to a comma-separated string.

87. How do you find the position of a substring in a string?

88. What is the difference between `strpos()` and `stripos()`?

89. How do you replace text in a string?

90. What is the difference between `str_replace()` and `preg_replace()`?

91. How do you extract a portion of a string?

92. What is the difference between `substr()` and `mb_substr()`?

93. How do you convert a string to uppercase or lowercase?

94. What are `strtoupper()`, `strtolower()`, `ucfirst()`, and `ucwords()`?

95. How do you trim whitespace from a string?

96. What is the difference between `trim()`, `ltrim()`, and `rtrim()`?

97. How do you count the number of words in a string?

98. What does `str_word_count()` do and what are its return options?

99. How do you check if a string starts or ends with specific text?

100. Explain `str_starts_with()` and `str_ends_with()` (PHP 8+).

## Section 2: Functions & Control Structures (Questions 101-200)

### Functions (Questions 101-130)

101. How do you define a function in PHP?

102. What is the difference between function parameters and arguments?

103. How do you set default parameter values in functions?

104. Explain variable-length argument lists using `func_get_args()`.

105. What is the `...` operator (splat operator) for variadic functions?

106. How do you return multiple values from a function?

107. What is the purpose of return type declarations in PHP 7+?

108. How do you specify nullable return types?

109. What are anonymous functions (closures) in PHP?

110. How do you use the `use` keyword with closures?

111. What is the difference between `use` and `use (&$var)` in closures?

112. Explain arrow functions introduced in PHP 7.4.

113. What is the syntax difference between closures and arrow functions?

114. How do you pass a function as a callback?

115. What are callable types in PHP?

116. How do you use `call_user_func()` and `call_user_func_array()`?

117. What is recursion and how do you implement it in PHP?

118. Explain tail recursion and its limitations in PHP.

119. How do you create and use generator functions?

120. What is the `yield` keyword used for?

121. How do generators improve memory efficiency?

122. What is the difference between `return` and `yield` in generators?

123. How do you pass values to a generator using `send()`?

124. What is function type hinting in PHP?

125. How do you type hint scalar types (int, string, float, bool)?

126. What is `declare(strict_types=1)` and how does it work?

127. Explain the difference between strict and weak type checking.

128. How do you document functions using PHPDoc comments?

129. What are first-class callable objects in PHP 8.1?

130. How do you create named arguments in PHP 8?

### Control Structures (Questions 131-160)

131. What are the conditional statements available in PHP?

132. Explain the syntax of `if`, `elseif`, and `else`.

133. What is the alternative syntax for control structures?

134. How do you use `endif`, `endwhile`, `endfor` in templates?

135. What is the `switch` statement and when should it be used?

136. How does `switch` use comparison (loose vs strict)?

137. What is the purpose of `break` in switch statements?

138. What happens if you omit `break` in a switch case?

139. How do you use `match` expression introduced in PHP 8?

140. What is the difference between `switch` and `match`?

141. Does `match` use strict or loose comparison?

142. How does `match` handle multiple conditions?

143. What are the looping constructs in PHP?

144. Explain the `for` loop syntax and usage.

145. What is the `while` loop and when is it preferred?

146. How does `do-while` differ from `while`?

147. What is the `foreach` loop used for?

148. How do you iterate over array keys and values?

149. Explain the syntax `foreach($array as $key => $value)`.

150. Can you modify array values inside foreach? How?

151. What is the purpose of `break` in loops?

152. How do you break out of nested loops?

153. What does `continue` do in a loop?

154. What is the difference between `break` and `continue`?

155. How do you use `goto` in PHP? (Is it recommended?)

156. What is the `declare` statement used for?

157. How do you use `declare(ticks=1)` and what are ticks?

158. What is the `return` statement's behavior in included files?

159. How do you exit script execution in PHP?

160. What is the difference between `exit()`, `die()`, and `return`?

### Error Types and Handling (Questions 161-200)

161. What are the different error types in PHP?

162. Explain the difference between `E_ERROR`, `E_WARNING`, and `E_NOTICE`.

163. What is `E_PARSE` and when does it occur?

164. What is the difference between `E_STRICT` and `E_DEPRECATED`?

165. How do you configure error reporting in PHP?

166. What does `error_reporting(E_ALL)` do?

167. How do you display errors in development vs hide them in production?

168. What is the `display_errors` directive?

169. How do you log errors to a file?

170. What is the `error_log()` function used for?

171. Explain the difference between errors and exceptions.

172. What is exception handling in PHP?

173. How do you use `try`, `catch`, and `finally` blocks?

174. What is the base `Exception` class in PHP?

175. How do you create custom exception classes?

176. What is the difference between `Exception` and `Error` classes?

177. What are throwable objects in PHP 7+?

178. How do you catch multiple exception types in one catch block?

179. What is exception chaining (previous exception)?

180. How do you re-throw an exception?

181. What is the purpose of `set_error_handler()`?

182. How do you convert errors to exceptions?

183. What is `set_exception_handler()` used for?

184. How do you register a shutdown function?

185. What is `register_shutdown_function()` used for?

186. How do you detect fatal errors during shutdown?

187. What is the `ErrorException` class?

188. How do you get error information programmatically?

189. What is `error_get_last()` and when is it useful?

190. How do you suppress errors using the `@` operator?

191. Is using `@` operator considered a good practice? Why or why not?

192. How do you handle warnings without stopping execution?

193. What is the `trigger_error()` function used for?

194. How do you create custom error messages?

195. What is a parse error and how do you debug it?

196. What causes "unexpected end of file" errors?

197. How do you debug "headers already sent" errors?

198. What causes "cannot redeclare" errors?

199. How do you handle memory exhausted errors?

200. What is the `memory_limit` directive and how do you increase it?

## Section 3: Object-Oriented Programming (Questions 201-350)

### Classes and Objects (Questions 201-240)

201. How do you define a class in PHP?

202. What is the syntax for creating an object (instance)?

203. What is a constructor and how do you define it?

204. What is the `__construct()` magic method?

205. How do you define a destructor in PHP?

206. What is the `__destruct()` magic method used for?

207. What are properties (member variables) in a class?

208. How do you define and access class properties?

209. What are methods in a class?

210. How do you call methods on an object?

211. What is the `$this` keyword?

212. How do you access current object's properties and methods?

213. What is the difference between `->` and `::` operators?

214. What are access modifiers in PHP?

215. Explain `public`, `private`, and `protected` visibility.

216. What is the default visibility if not specified?

217. How do you create static properties?

218. What is the `self` keyword used for?

219. How do you access static properties and methods?

220. What is the difference between `self` and `$this`?

221. What is the `static` keyword in late static binding?

222. How does `static::` differ from `self::`?

223. What is constructor property promotion in PHP 8?

224. Provide an example of constructor property promotion.

225. What are readonly properties in PHP 8.1?

226. How do you declare a readonly property?

227. What are the restrictions on readonly properties?

228. What are readonly classes in PHP 8.2?

229. How do you clone an object in PHP?

230. What is the `__clone()` magic method?

231. What is shallow copy vs deep copy?

232. How do you compare objects in PHP?

233. What is object serialization?

234. What are `serialize()` and `unserialize()` functions?

235. What is the `__sleep()` magic method?

236. What is the `__wakeup()` magic method?

237. How do you convert an object to string?

238. What is the `__toString()` magic method?

239. What are anonymous classes in PHP 7?

240. Provide an example of an anonymous class.

### Inheritance and Polymorphism (Questions 241-270)

241. What is inheritance in OOP?

242. How do you extend a class in PHP?

243. What is the `extends` keyword?

244. Can a class extend multiple classes in PHP?

245. What is method overriding?

246. How do you override a parent method?

247. How do you call a parent class method?

248. What is the `parent` keyword used for?

249. What is the `final` keyword in PHP?

250. How does `final` prevent inheritance and overriding?

251. Can you override a final method?

252. What is an abstract class?

253. How do you define an abstract class?

254. Can you instantiate an abstract class?

255. What are abstract methods?

256. How do you define abstract methods?

257. Must child classes implement abstract methods?

258. What is the difference between abstract class and interface?

259. What is polymorphism in OOP?

260. How do you achieve polymorphism in PHP?

261. What is method signature compatibility?

262. What are covariance and contravariance in PHP?

263. How does PHP 7.4+ handle return type covariance?

264. How does PHP 7.4+ handle parameter type contravariance?

265. What is the Liskov Substitution Principle?

266. How do you type hint with parent/child classes?

267. What is type widening and narrowing?

268. Can you change method visibility when overriding?

269. What visibility changes are allowed in inheritance?

270. How do you prevent a method from being overridden?

### Interfaces and Traits (Questions 271-300)

271. What is an interface in PHP?

272. How do you define an interface?

273. How do you implement an interface?

274. Can a class implement multiple interfaces?

275. What is the `implements` keyword?

276. Can interfaces have method implementations?

277. Can interfaces contain properties?

278. What is interface inheritance?

279. How do you extend an interface?

280. What is the purpose of marker interfaces?

281. What are traits in PHP?

282. How do you define a trait?

283. How do you use a trait in a class?

284. What is the `use` keyword for traits?

285. Can a class use multiple traits?

286. What is trait method conflict resolution?

287. How do you resolve naming conflicts with `insteadof`?

288. How do you create aliases for trait methods?

289. What is the `as` keyword in trait usage?

290. Can traits have properties?

291. Can traits have constructors?

292. Can traits use other traits?

293. What is the trait precedence order?

294. How do traits interact with inheritance?

295. What is the difference between traits and abstract classes?

296. When should you use traits vs inheritance?

297. Can traits have abstract methods?

298. Can traits have static methods and properties?

299. How do you change visibility of trait methods?

300. What are the limitations of traits?

### Magic Methods and Constants (Questions 301-350)

301. What are magic methods in PHP?

302. List at least 10 magic methods.

303. What is `__construct()` used for?

304. What is `__destruct()` used for?

305. What is `__get()` for accessing inaccessible properties?

306. What is `__set()` for setting inaccessible properties?

307. What is `__isset()` used for?

308. What is `__unset()` used for?

309. How do `__get()` and `__set()` enable property overloading?

310. What is `__call()` for calling inaccessible methods?

311. What is `__callStatic()` used for?

312. How do `__call()` and `__callStatic()` enable method overloading?

313. What is `__toString()` used for?

314. What is `__invoke()` magic method?

315. How do you make an object callable?

316. What is `__set_state()` used for?

317. What is `__clone()` for object cloning?

318. What is `__debugInfo()` used for?

319. How does `__debugInfo()` affect `var_dump()` output?

320. What is `__sleep()` for serialization?

321. What is `__wakeup()` for unserialization?

322. What is `__serialize()` in PHP 7.4+?

323. What is `__unserialize()` in PHP 7.4+?

324. What is the difference between `__sleep()` and `__serialize()`?

325. What are magic constants in PHP?

326. What is `__LINE__` constant?

327. What is `__FILE__` constant?

328. What is `__DIR__` constant?

329. What is `__FUNCTION__` constant?

330. What is `__CLASS__` constant?

331. What is `__TRAIT__` constant?

332. What is `__METHOD__` constant?

333. What is `__NAMESPACE__` constant?

334. What is `::class` magic constant (PHP 5.5+)?

335. How do magic constants help in debugging?

336. How do you use `__DIR__` for file inclusion?

337. What is the difference between `__FILE__` and `__DIR__`?

338. How do `__CLASS__` and `get_class()` differ?

339. When would you use `__METHOD__` in logging?

340. How do magic constants behave in inherited classes?

341. What is the value of `__CLASS__` in a trait?

342. What is the value of `__TRAIT__` inside and outside a trait?

343. How do you get the fully qualified class name?

344. What is the `get_class()` function?

345. What is the `get_called_class()` function?

346. How do you check if a class exists?

347. What is the `class_exists()` function?

348. How do you get all methods of a class?

349. What is the `get_class_methods()` function?

350. How do you check if a method exists in a class?

## Section 4: Namespaces & Autoloading (Questions 351-400)

### Namespaces (Questions 351-375)

351. What are namespaces in PHP?

352. Why were namespaces introduced in PHP 5.3?

353. How do you declare a namespace?

354. What is the namespace separator in PHP?

355. How do you use a class from a different namespace?

356. What is the `use` keyword for namespaces?

357. How do you create namespace aliases?

358. What is the `as` keyword in namespace context?

359. Can you have multiple namespaces in one file?

360. What are sub-namespaces?

361. How do you access global functions/classes from namespace?

362. What is the global namespace prefix `\`?

363. How do you use `use function` for importing functions?

364. How do you use `use const` for importing constants?

365. What is the difference between qualified, fully qualified, and unqualified names?

366. How do namespace resolution rules work?

367. What is the `__NAMESPACE__` magic constant?

368. How do you organize code using namespaces?

369. What are namespace naming conventions?

370. How do namespaces relate to directory structure?

371. What is the PSR-4 autoloading standard?

372. How do you handle namespace collisions?

373. Can you have the same class name in different namespaces?

374. What are grouped `use` declarations (PHP 7+)?

375. How do you use trailing commas in grouped use (PHP 7.2+)?

### Autoloading (Questions 376-400)

376. What is autoloading in PHP?

377. Why is autoloading important?

378. What is the `__autoload()` function?

379. Is `__autoload()` deprecated? What replaced it?

380. What is `spl_autoload_register()`?

381. How do you register multiple autoload functions?

382. What is the order of autoloader execution?

383. How do you create a custom autoloader?

384. What is PSR-0 autoloading standard?

385. What is PSR-4 autoloading standard?

386. What is the difference between PSR-0 and PSR-4?

387. How does Composer implement autoloading?

388. What is the `composer.json` autoload section?

389. What is the difference between `autoload` and `autoload-dev`?

390. What is the `classmap` autoloading strategy?

391. What is the `files` autoloading strategy?

392. When should you use `files` vs `psr-4` autoloading?

393. How do you regenerate Composer's autoload files?

394. What is `composer dump-autoload`?

395. What is the difference between `dump-autoload` and `dump-autoload -o`?

396. What is autoload optimization?

397. How does the `--optimize` flag improve performance?

398. What is the autoloader's classmap?

399. How do you debug autoloading issues?

400. What are common autoloading mistakes?

## Section 5: File Handling & Sessions (Questions 401-500)

### File System Operations (Questions 401-440)

401. How do you open a file in PHP?

402. What is the `fopen()` function and its modes?

403. What is the difference between 'r', 'w', 'a', and 'x' modes?

404. How do you read a file line by line?

405. What is the difference between `fgets()` and `fgetc()`?

406. How do you read entire file content at once?

407. What is `file_get_contents()` used for?

408. How do you write to a file?

409. What is the difference between `fwrite()` and `file_put_contents()`?

410. How do you append to a file without overwriting?

411. How do you close a file handle?

412. What is the `fclose()` function?

413. Why is it important to close file handles?

414. How do you check if a file exists?

415. What is the `file_exists()` function?

416. How do you check if a path is a file or directory?

417. What is the difference between `is_file()` and `is_dir()`?

418. How do you get file size?

419. What is the `filesize()` function?

420. How do you delete a file?

421. What is the `unlink()` function?

422. How do you rename or move a file?

423. What is the `rename()` function?

424. How do you copy a file?

425. What is the `copy()` function?

426. How do you get file information?

427. What does `stat()` function return?

428. How do you check file permissions?

429. What are `is_readable()`, `is_writable()`, and `is_executable()`?

430. How do you change file permissions?

431. What is the `chmod()` function?

432. How do you create a directory?

433. What is the `mkdir()` function and its recursive option?

434. How do you delete a directory?

435. What is the `rmdir()` function?

436. How do you list files in a directory?

437. What are `scandir()` and `glob()` functions?

438. How do you recursively traverse directories?

439. What is the `RecursiveDirectoryIterator` class?

440. How do you get the current working directory?

### Include and Require (Questions 441-460)

441. What is the `include` statement used for?

442. What is the `require` statement used for?

443. What is the difference between `include` and `require`?

444. What happens when an included file is not found?

445. What is the difference between `require` and `require_once`?

446. When should you use `require_once` vs `require`?

447. What is the difference between `include` and `include_once`?

448. How does `include_once` prevent duplicate inclusions?

449. What is the performance impact of `_once` variants?

450. How do you return values from included files?

451. How do you pass variables to included files?

452. What is the scope of variables in included files?

453. Can you include a file from a URL?

454. What is the `allow_url_include` directive?

455. What are the security risks of including remote files?

456. How do you include files using absolute paths?

457. How do you include files using relative paths?

458. What is the `__DIR__` constant used for in includes?

459. How do you prevent path traversal attacks in includes?

460. What are the best practices for file inclusion?

### Sessions (Questions 461-490)

461. What are sessions in PHP?

462. How do sessions differ from cookies?

463. How do you start a session?

464. What is the `session_start()` function?

465. Where must `session_start()` be called in the script?

466. How do you store data in a session?

467. How do you access session variables?

468. What is the `$_SESSION` superglobal?

469. How do you remove a specific session variable?

470. How do you destroy a session?

471. What is the `session_destroy()` function?

472. What is the difference between `session_destroy()` and `unset($_SESSION)`?

473. How do you regenerate a session ID?

474. What is `session_regenerate_id()` and why is it important?

475. What are session fixation attacks?

476. How do you prevent session hijacking?

477. What is the session save path?

478. How do you configure session storage location?

479. What is the `session.save_path` directive?

480. How do you implement custom session handlers?

481. What is the `SessionHandlerInterface`?

482. How do you store sessions in a database?

483. How do you store sessions in Redis or Memcached?

484. What is the session garbage collection mechanism?

485. How do you configure session timeout?

486. What is the `session.gc_maxlifetime` directive?

487. How do you implement "remember me" functionality?

488. What are session cookies vs persistent cookies?

489. How do you make sessions secure (HTTPS only)?

490. What is the `session.cookie_secure` directive?

### Cookies (Questions 491-500)

491. What are cookies in PHP?

492. How do you set a cookie?

493. What is the `setcookie()` function signature?

494. When must `setcookie()` be called?

495. How do you read cookie values?

496. What is the `$_COOKIE` superglobal?

497. How do you delete a cookie?

498. What is the cookie expiration parameter?

499. How do you set cookie path and domain?

500. What is the HttpOnly flag and why is it important?

## Section 6: Database & SQL (Questions 501-600)

### MySQL Basics (Questions 501-530)

501. How do you connect to a MySQL database in PHP?

502. What is the mysqli extension?

503. What is the PDO extension?

504. What is the difference between mysqli and PDO?

505. How do you create a mysqli connection?

506. How do you create a PDO connection?

507. What is the DSN (Data Source Name) in PDO?

508. How do you handle connection errors?

509. How do you close a database connection?

510. What is a prepared statement?

511. Why should you use prepared statements?

512. How do prepared statements prevent SQL injection?

513. How do you create a prepared statement in mysqli?

514. How do you create a prepared statement in PDO?

515. What is parameter binding in prepared statements?

516. How do you bind parameters using mysqli?

517. How do you bind parameters using PDO?

518. What is the difference between positional and named placeholders?

519. How do you execute a prepared statement?

520. How do you fetch results from a prepared statement?

521. What is the difference between `fetch()`, `fetchAll()`, and `fetchColumn()`?

522. How do you get the number of affected rows?

523. How do you get the last inserted ID?

524. What is a database transaction?

525. How do you start a transaction in PDO?

526. How do you commit a transaction?

527. How do you rollback a transaction?

528. What is the autocommit mode?

529. How do you handle transaction errors?

530. What is the try-catch pattern for transactions?

### SQL Injection Prevention (Questions 531-560)

531. What is SQL injection?

532. How can SQL injection be exploited?

533. Provide an example of a vulnerable SQL query.

534. How do prepared statements prevent SQL injection?

535. What is the role of parameter binding?

536. How do you escape special characters in SQL?

537. What is the `mysqli_real_escape_string()` function?

538. Why is escaping alone not sufficient?

539. What is the difference between `addslashes()` and `mysqli_real_escape_string()`?

540. Should you use `mysql_real_escape_string()` in modern PHP?

541. What is the `quote()` method in PDO?

542. How do you validate user input before database queries?

543. What is whitelist validation?

544. How do you sanitize input for database queries?

545. What is the principle of least privilege in database access?

546. How do you use database user permissions to enhance security?

547. What are stored procedures and how do they help security?

548. How do you use parameterized stored procedures?

549. What is the `FILTER_SANITIZE_STRING` constant?

550. How do you validate integers before using in queries?

551. What is type casting for input validation?

552. How do you prevent second-order SQL injection?

553. What is blind SQL injection?

554. How do you detect SQL injection attempts?

555. What logging should you implement for security?

556. How do you test your application for SQL injection?

557. What tools can help detect SQL injection vulnerabilities?

558. What is the OWASP Top 10 and where does SQL injection rank?

559. How do you educate developers about SQL injection?

560. What are the best practices for database security?

### Query Building & ORM (Questions 561-590)

561. What is a query builder?

562. What advantages does a query builder provide?

563. How do you build a SELECT query using fluent interface?

564. How do you add WHERE conditions to a query?

565. How do you use AND/OR conditions in query builders?

566. How do you perform JOIN operations?

567. How do you implement pagination in queries?

568. What is LIMIT and OFFSET in SQL?

569. How do you order results in a query?

570. How do you group results?

571. What is the HAVING clause used for?

572. How do you perform aggregate functions (COUNT, SUM, AVG)?

573. What is an ORM (Object-Relational Mapping)?

574. What is Eloquent ORM in Laravel?

575. What is Doctrine ORM?

576. What are the benefits of using an ORM?

577. What are the drawbacks of using an ORM?

578. How do you define a model in an ORM?

579. How do you perform CRUD operations with ORM?

580. What is lazy loading in ORM?

581. What is eager loading in ORM?

582. What is the N+1 query problem?

583. How do you prevent N+1 queries?

584. What is the difference between `find()` and `where()`?

585. How do you handle relationships in ORM (one-to-many, many-to-many)?

586. What are model events in ORM?

587. How do you implement soft deletes?

588. What is mass assignment in ORM?

589. How do you protect against mass assignment vulnerabilities?

590. What is the `$fillable` and `$guarded` properties in models?

### Database Design & Optimization (Questions 591-600)

591. What is database normalization?

592. What are the normal forms (1NF, 2NF, 3NF)?

593. When should you denormalize a database?

594. What is an index in a database?

595. How do indexes improve query performance?

596. What is the cost of adding indexes?

597. What is the difference between clustered and non-clustered indexes?

598. What is a composite index?

599. How do you optimize slow queries?

600. What tools can you use to analyze query performance?

## Section 7: Web Development & Security (Questions 601-700)

### Form Handling (Questions 601-620)

601. How do you create an HTML form that submits to PHP?

602. What is the `action` attribute in forms?

603. What is the `method` attribute (GET vs POST)?

604. How do you access form data in PHP?

605. What is the difference between `$_GET` and `$_POST`?

606. How do you handle checkbox values?

607. How do you handle radio button values?

608. How do you handle select dropdown values?

609. How do you handle file uploads?

610. What is the `$_FILES` superglobal?

611. What are the components of the `$_FILES` array?

612. How do you validate file uploads?

613. How do you check file size before upload?

614. How do you check file type/extension?

615. What is MIME type validation?

616. How do you move uploaded files to permanent location?

617. What is the `move_uploaded_file()` function?

618. What is the `upload_tmp_dir` directive?

619. How do you handle multiple file uploads?

620. What are common security issues with file uploads?

### Cross-Site Scripting (XSS) Prevention (Questions 621-640)

621. What is Cross-Site Scripting (XSS)?

622. What are the types of XSS (reflected, stored, DOM-based)?

623. How can XSS attacks be exploited?

624. Provide an example of an XSS vulnerability.

625. How do you prevent XSS attacks?

626. What is output escaping?

627. What is the `htmlspecialchars()` function?

628. What is the `ENT_QUOTES` flag?

629. What is the difference between `htmlspecialchars()` and `htmlentities()`?

630. How do you escape JavaScript output?

631. What is the `json_encode()` function's role in preventing XSS?

632. How do you escape CSS output?

633. How do you escape URL output?

634. What is the `urlencode()` function?

635. What is Content Security Policy (CSP)?

636. How does CSP help prevent XSS?

637. What is the `X-XSS-Protection` header?

638. How do you validate and sanitize input to prevent XSS?

639. What is the `filter_var()` function?

640. What are the best practices for XSS prevention?

### Cross-Site Request Forgery (CSRF) Prevention (Questions 641-660)

641. What is Cross-Site Request Forgery (CSRF)?

642. How do CSRF attacks work?

643. Provide an example of a CSRF attack.

644. How do you prevent CSRF attacks?

645. What is a CSRF token?

646. How do you generate a CSRF token?

647. How do you validate a CSRF token?

648. Where should you store CSRF tokens (session vs cookie)?

649. How do you include CSRF tokens in forms?

650. How do you include CSRF tokens in AJAX requests?

651. What is the SameSite cookie attribute?

652. How does SameSite help prevent CSRF?

653. What is the difference between SameSite=Strict and SameSite=Lax?

654. Should you use CSRF protection for GET requests?

655. What is the double-submit cookie pattern?

656. How do you implement per-request CSRF tokens?

657. How do you implement per-session CSRF tokens?

658. What is token synchronization pattern?

659. How do you handle CSRF tokens in single-page applications?

660. What are the best practices for CSRF prevention?

### Password Security (Questions 661-680)

661. Why should you never store passwords in plain text?

662. What is password hashing?

663. What is the difference between encryption and hashing?

664. What is a one-way hash function?

665. What is the `password_hash()` function in PHP?

666. What hashing algorithms does PHP support?

667. What is bcrypt and why is it recommended?

668. What is Argon2 and how does it compare to bcrypt?

669. What is the `PASSWORD_DEFAULT` constant?

670. How do you verify a password hash?

671. What is the `password_verify()` function?

672. How do you check if a hash needs rehashing?

673. What is the `password_needs_rehash()` function?

674. What is a salt in password hashing?

675. Does `password_hash()` automatically handle salts?

676. What is password stretching/key derivation?

677. What is the cost parameter in bcrypt?

678. How do you determine appropriate cost value?

679. What are rainbow tables and how do salts prevent them?

680. What are the best practices for password storage?

### HTTPS and SSL/TLS (Questions 681-700)

681. What is HTTPS?

682. How does HTTPS differ from HTTP?

683. What is SSL/TLS?

684. How does SSL/TLS encryption work?

685. What is a digital certificate?

686. What is a Certificate Authority (CA)?

687. How do you force HTTPS in PHP?

688. How do you redirect HTTP to HTTPS?

689. What is the `$_SERVER['HTTPS']` variable?

690. How do you detect if a request is over HTTPS?

691. What is HSTS (HTTP Strict Transport Security)?

692. How do you implement HSTS headers?

693. What is certificate pinning?

694. What is mixed content and why is it a problem?

695. How do you ensure all resources are loaded over HTTPS?

696. What is Let's Encrypt?

697. How do you obtain a free SSL certificate?

698. What is the difference between SSL and TLS?

699. What are the common SSL/TLS vulnerabilities?

700. What are the best practices for HTTPS implementation?

## Section 8: Advanced PHP Concepts (Questions 701-800)

### Regular Expressions (Questions 701-730)

701. What are regular expressions (regex)?

702. What is the difference between PCRE and POSIX regex in PHP?

703. Which regex functions should you use in modern PHP?

704. What is the `preg_match()` function?

705. What is the `preg_match_all()` function?

706. What is the difference between `preg_match()` and `preg_match_all()`?

707. What is the `preg_replace()` function?

708. How do you perform case-insensitive matching?

709. What are regex modifiers/flags?

710. What is the `i` modifier?

711. What is the `m` modifier (multiline)?

712. What is the `s` modifier (dotall)?

713. What is the `x` modifier (extended)?

714. What are character classes in regex?

715. What is the difference between `[abc]` and `[^abc]`?

716. What are predefined character classes (`\d`, `\w`, `\s`)?

717. What are anchors in regex?

718. What is the difference between `^` and `$`?

719. What are quantifiers in regex?

720. What is the difference between `*`, `+`, and `?`?

721. What is the difference between `{n}`, `{n,}`, and `{n,m}`?

722. What is greedy vs lazy matching?

723. How do you make a quantifier non-greedy?

724. What are capturing groups in regex?

725. How do you use parentheses for grouping?

726. What are backreferences in regex?

727. What are non-capturing groups `(?:...)`?

728. What are lookahead and lookbehind assertions?

729. How do you validate an email address with regex?

730. What are the best practices for using regex?

### Date and Time (Questions 731-760)

731. How do you get the current timestamp in PHP?

732. What is the `time()` function?

733. What is a Unix timestamp?

734. What is the `date()` function?

735. How do you format a date string?

736. What are common date format characters (Y, m, d, H, i, s)?

737. What is the `strtotime()` function?

738. How do you convert a string to timestamp?

739. How do you parse relative date strings?

740. What is the `mktime()` function?

741. How do you create a timestamp from date components?

742. What is the DateTime class?

743. How do you create a DateTime object?

744. What are the advantages of DateTime over date functions?

745. How do you format dates with DateTime?

746. How do you modify dates (add/subtract)?

747. What is the `DateInterval` class?

748. How do you calculate the difference between two dates?

749. What is the `DatePeriod` class?

750. How do you work with timezones?

751. What is the `DateTimeZone` class?

752. How do you set the default timezone?

753. What is the `date_default_timezone_set()` function?

754. How do you convert between timezones?

755. What is UTC and why is it important?

756. How do you handle daylight saving time?

757. What is the `DateTimeImmutable` class?

758. How does `DateTimeImmutable` differ from `DateTime`?

759. When should you use immutable date objects?

760. What are the best practices for date/time handling?

### JSON and XML (Questions 761-790)

761. What is JSON?

762. How do you encode data to JSON in PHP?

763. What is the `json_encode()` function?

764. What options can you pass to `json_encode()`?

765. What is `JSON_PRETTY_PRINT`?

766. What is `JSON_UNESCAPED_UNICODE`?

767. What is `JSON_UNESCAPED_SLASHES`?

768. How do you decode JSON in PHP?

769. What is the `json_decode()` function?

770. What is the difference between decoding to object vs array?

771. How do you handle JSON errors?

772. What is the `json_last_error()` function?

773. What is the `json_last_error_msg()` function?

774. What are common JSON errors in PHP?

775. What is XML?

776. How do you parse XML in PHP?

777. What is SimpleXML?

778. How do you create a SimpleXMLElement object?

779. How do you access XML elements and attributes?

780. How do you iterate over XML elements?

781. What is DOMDocument?

782. How is DOMDocument different from SimpleXML?

783. When should you use DOMDocument vs SimpleXML?

784. How do you create XML documents in PHP?

785. How do you validate XML against a schema?

786. What is XPath?

787. How do you use XPath with SimpleXML?

788. How do you handle namespaces in XML?

789. How do you convert XML to array?

790. What are the best practices for working with JSON/XML?

### Composer and Dependency Management (Questions 791-800)

791. What is Composer?

792. What is the purpose of dependency management?

793. How do you install Composer?

794. What is the `composer.json` file?

795. What is the `composer.lock` file?

796. Why is `composer.lock` important?

797. How do you install dependencies with Composer?

798. What is the `composer install` command?

799. What is the `composer update` command?

800. What is the difference between `install` and `update`?

## Section 9: Modern PHP Features (Questions 801-900)

### PHP 7+ Features (Questions 801-830)

801. What are scalar type declarations in PHP 7?

802. How do you enable strict types?

803. What is the return type declaration syntax?

804. What is the null coalescing operator `??`?

805. What is the spaceship operator `<=>`?

806. What are anonymous classes in PHP 7?

807. What is the `Closure::call()` method?

808. What are group use declarations?

809. What is the `intdiv()` function?

810. What are generator delegation with `yield from`?

811. What is the `random_bytes()` function?

812. What is the `random_int()` function?

813. What are filtered `unserialize()` options?

814. What is the `assert()` function enhancement?

815. What are nullable types?

816. What is the void return type?

817. What is iterable type hint?

818. What are class constant visibility modifiers?

819. What are multi-catch exception handlers?

820. What is the `list()` shorthand syntax `[]`?

821. What are negative string offsets?

822. What is the `is_iterable()` function?

823. What are trailing commas in function calls?

824. What are numeric literal separators?

825. What is weak references?

826. What is array spread operator `...`?

827. What is arrow function syntax `fn`?

828. What is typed properties?

829. What is null coalescing assignment operator `??=`?

830. What are numeric literal separators `1_000_000`?

### PHP 8+ Features (Questions 831-860)

831. What are named arguments in PHP 8?

832. What are attributes (annotations) in PHP 8?

833. What is constructor property promotion?

834. What is the `match` expression?

835. What is union types?

836. What is the `mixed` type?

837. What is the `static` return type?

838. What is the `throw` expression?

839. What is the `str_contains()` function?

840. What is the `str_starts_with()` function?

841. What is the `str_ends_with()` function?

842. What is the `fdiv()` function?

843. What is the `get_debug_type()` function?

844. What are non-capturing catches?

845. What is the nullsafe operator `?->`?

846. What are trailing commas in parameter lists?

847. What is the `ValueError` exception?

848. What are readonly properties (PHP 8.1)?

849. What are enums (PHP 8.1)?

850. What are fibers (PHP 8.1)?

851. What is the `never` return type?

852. What is the `new` in initializers?

853. What are intersection types (PHP 8.1)?

854. What is first-class callable syntax `...`?

855. What are readonly classes (PHP 8.2)?

856. What are true, false, null standalone types?

857. What is Disjunctive Normal Form (DNF) types?

858. What are dynamic properties deprecation?

859. What is the `#[\AllowDynamicProperties]` attribute?

860. What are the major breaking changes in PHP 8?

### Performance Optimization (Questions 861-890)

861. What is OpCache and how does it work?

862. How do you enable OpCache?

863. What are the recommended OpCache settings?

864. What is the `opcache_reset()` function?

865. What is JIT (Just-In-Time) compilation in PHP 8?

866. How do you enable JIT?

867. What types of applications benefit from JIT?

868. What is bytecode caching?

869. How do you optimize autoloading?

870. What is the Composer autoload optimization?

871. What is the classmap authoritative mode?

872. How do you optimize database queries?

873. What is query result caching?

874. How do you implement in-memory caching?

875. What is Redis and how is it used for caching?

876. What is Memcached?

877. What is the difference between Redis and Memcached?

878. How do you optimize string operations?

879. What is the performance difference between concatenation methods?

880. How do you optimize loops?

881. What is lazy loading?

882. How do you reduce memory usage?

883. What is the `memory_get_usage()` function?

884. How do you profile PHP applications?

885. What is Xdebug and how is it used for profiling?

886. What is Blackfire?

887. How do you identify performance bottlenecks?

888. What are common PHP performance pitfalls?

889. How do you optimize file I/O operations?

890. What are the best practices for PHP performance?

### Testing and Debugging (Questions 891-900)

891. What is PHPUnit?

892. How do you write a basic unit test?

893. What is test-driven development (TDD)?

894. What are assertions in PHPUnit?

895. What is the difference between `assertEquals()` and `assertSame()`?

896. What is code coverage?

897. How do you generate code coverage reports?

898. What is Xdebug and how do you use it?

899. How do you set breakpoints in PHP?

900. What are the best practices for debugging PHP?

## Section 10: Security Best Practices & Miscellaneous (Questions 901-1000)

### General Security (Questions 901-930)

901. What are the OWASP Top 10 security risks?

902. How do you prevent directory traversal attacks?

903. What is remote code execution (RCE)?

904. How do you prevent command injection?

905. What is the `escapeshellarg()` function?

906. What is the `escapeshellcmd()` function?

907. How do you sanitize user input?

908. What is the `filter_input()` function?

909. What are the different filter types?

910. What is `FILTER_VALIDATE_EMAIL`?

911. What is `FILTER_SANITIZE_STRING`?

912. How do you prevent session hijacking?

913. What is secure session configuration?

914. How do you implement proper access control?

915. What is the principle of least privilege?

916. How do you protect against brute force attacks?

917. What is rate limiting?

918. How do you implement account lockout?

919. What is two-factor authentication (2FA)?

920. How do you securely store API keys?

921. What is environment variable management?

922. How do you use `.env` files securely?

923. What is the `$_ENV` superglobal?

924. How do you prevent information disclosure?

925. What is error message sanitization?

926. How do you implement security headers?

927. What is the `X-Content-Type-Options` header?

928. What is the `X-Frame-Options` header?

929. How do you implement Content Security Policy?

930. What are the best practices for secure PHP configuration?

### HTTP and APIs (Questions 931-960)

931. What are HTTP status codes?

932. What is the difference between 200, 201, and 204?

933. What is the difference between 301 and 302 redirects?

934. What is a 404 error?

935. What is a 500 error?

936. What are HTTP headers?

937. How do you set custom HTTP headers in PHP?

938. What is the `header()` function?

939. Why must `header()` be called before output?

940. How do you prevent "headers already sent" errors?

941. What is output buffering?

942. What are `ob_start()` and `ob_end_flush()`?

943. What is RESTful API design?

944. What are the REST architectural constraints?

945. What are HTTP methods (GET, POST, PUT, DELETE, PATCH)?

946. What is the difference between PUT and PATCH?

947. What is idempotency in HTTP?

948. How do you implement API authentication?

949. What is token-based authentication?

950. What is OAuth 2.0?

951. What is JWT (JSON Web Token)?

952. How do you implement API rate limiting?

953. What is CORS (Cross-Origin Resource Sharing)?

954. How do you handle CORS in PHP?

955. What is the `Access-Control-Allow-Origin` header?

956. What is API versioning?

957. How do you version APIs (URI, header, parameter)?

958. What is content negotiation?

959. How do you handle different response formats (JSON, XML)?

960. What are the best practices for API design?

### Email and Communication (Questions 961-980)

961. How do you send email in PHP?

962. What is the `mail()` function?

963. What are the limitations of the `mail()` function?

964. What is PHPMailer?

965. How do you use PHPMailer to send emails?

966. What is SMTP?

967. How do you configure SMTP settings?

968. What is email header injection?

969. How do you prevent email header injection?

970. How do you send HTML emails?

971. How do you send emails with attachments?

972. What is email validation?

973. How do you validate email addresses properly?

974. What is the `filter_var()` with `FILTER_VALIDATE_EMAIL`?

975. What is SPF (Sender Policy Framework)?

976. What is DKIM (DomainKeys Identified Mail)?

977. What is DMARC?

978. How do you handle email bounces?

979. What is email throttling?

980. What are the best practices for sending emails?

### Miscellaneous (Questions 981-1000)

981. What is the difference between `print_r()` and `var_dump()`?

982. What is the `var_export()` function?

983. How do you debug variables in PHP?

984. What is the `debug_backtrace()` function?

985. What is the difference between `exit()` and `die()`?

986. What is the `sleep()` function?

987. What is the `usleep()` function for microseconds?

988. How do you execute system commands in PHP?

989. What is the `exec()` function?

990. What is the `shell_exec()` function?

991. What is the difference between `exec()`, `system()`, and `passthru()`?

992. How do you get PHP configuration information?

993. What is the `phpinfo()` function?

994. What is the `ini_get()` function?

995. How do you change PHP configuration at runtime?

996. What is the `ini_set()` function?

997. What is the `php.ini` file?

998. What are some important `php.ini` directives?

999. How do you check the PHP version?

1000. What is the `phpversion()` function?
