# Database General Interview Questions

## Section 1: Keys (Questions 1-50)

### Primary Keys (Questions 1-10)

1. What is a primary key and what are its characteristics?

2. Can a table have multiple primary keys?

3. What is the difference between a primary key and a unique key?

4. Can a primary key column contain NULL values?

5. How do you define a primary key in MySQL?

6. What is a composite primary key?

7. Should you use auto-increment integers or UUIDs as primary keys?

8. What are the performance implications of using UUIDs vs integers as primary keys?

9. How do primary keys affect table storage and index organization?

10. What happens when you try to insert a duplicate primary key value?

### Unique Keys (Questions 11-20)

11. What is a unique key constraint?

12. How does a unique key differ from a primary key?

13. Can a unique key column contain NULL values?

14. How many unique key constraints can a table have?

15. How do you create a unique constraint in MySQL?

16. What is the difference between a UNIQUE constraint and a UNIQUE index?

17. Can you have a composite unique key?

18. How do unique keys affect query performance?

19. What happens when you try to insert a duplicate value into a unique key column?

20. How do you drop a unique constraint from a table?

### Foreign Keys (Questions 21-35)

21. What is a foreign key and what is its purpose?

22. How do foreign keys enforce referential integrity?

23. What is the syntax for creating a foreign key constraint in MySQL?

24. What is the difference between CASCADE, SET NULL, RESTRICT, and NO ACTION?

25. How do you use ON DELETE CASCADE in a foreign key?

26. What is ON UPDATE CASCADE?

27. Can a foreign key reference a non-primary key column?

28. What are the performance implications of foreign key constraints?

29. How do foreign keys affect INSERT, UPDATE, and DELETE operations?

30. Can you have circular foreign key references?

31. How do you disable foreign key checks temporarily in MySQL?

32. What is the difference between foreign keys and application-level referential integrity?

33. How do you find all foreign keys referencing a specific table?

34. What happens if you try to delete a parent record with child records?

35. How do you handle orphaned records when foreign keys are not used?

### Composite Keys (Questions 36-50)

36. What is a composite key?

37. When should you use a composite primary key?

38. How do you define a composite primary key in MySQL?

39. What are the advantages of composite keys?

40. What are the disadvantages of composite keys?

41. How do composite keys affect index performance?

42. Can you have a foreign key reference a composite primary key?

43. How do you create a composite unique constraint?

44. What is the maximum number of columns recommended in a composite key?

45. How do composite keys compare to surrogate keys?

46. How do you order columns in a composite key for optimal performance?

47. Can you add a column to an existing composite key?

48. How do composite keys affect join performance?

49. What is a natural key vs a surrogate key?

50. How do you migrate from a composite key to a surrogate key?

## Section 2: Database Objects (Questions 51-150)

### Triggers (Questions 51-75)

51. What is a database trigger?

52. What are the different types of triggers (BEFORE, AFTER, INSTEAD OF)?

53. What are the common use cases for triggers?

54. How do you create a BEFORE INSERT trigger in MySQL?

55. How do you create an AFTER UPDATE trigger in MySQL?

56. What is a BEFORE DELETE trigger used for?

57. How do triggers affect database performance?

58. Can a trigger call another trigger?

59. What is trigger recursion and how do you prevent it?

60. How do you access OLD and NEW values in triggers?

61. Can you have multiple triggers on the same table for the same event?

62. What is the execution order of multiple triggers?

63. How do you debug triggers in MySQL?

64. What are the limitations of triggers?

65. How do triggers affect transaction rollback?

66. Can triggers be used for auditing purposes?

67. Write a trigger to automatically update a `updated_at` timestamp.

68. How do triggers interact with foreign key constraints?

69. What are the security implications of triggers?

70. How do you drop a trigger?

71. Can triggers prevent data modification operations?

72. What is the difference between row-level and statement-level triggers?

73. How do you handle errors in triggers?

74. What is the performance impact of having many triggers?

75. Should you use triggers or application logic for business rules?

### Views (Questions 76-100)

76. What is a database view?

77. What are the advantages of using views?

78. How do you create a view in MySQL?

79. What is the difference between a view and a table?

80. Can you update data through a view?

81. What is an updatable view?

82. What are the requirements for a view to be updatable?

83. How do you create a view with a JOIN?

84. What is the WITH CHECK OPTION in views?

85. How do views affect query performance?

86. What is the difference between a view and a materialized view?

87. Can you create an index on a view?

88. How do you modify an existing view?

89. What is CREATE OR REPLACE VIEW?

90. How do you drop a view?

91. Can views reference other views?

92. What is the maximum nesting level for views?

93. How do you use views for security and access control?

94. Write a view that calculates aggregate data from multiple tables.

95. What are the limitations of views in MySQL?

96. How do views interact with permissions and grants?

97. What happens to a view if the underlying table structure changes?

98. Can views improve code maintainability?

99. What is the performance difference between views and stored procedures?

100. How do you optimize queries that use views?

### Stored Procedures (Questions 101-125)

101. What is a stored procedure?

102. What are the advantages of stored procedures?

103. How do you create a stored procedure in MySQL?

104. What is the difference between stored procedures and functions?

105. How do you call a stored procedure?

106. What are IN, OUT, and INOUT parameters in stored procedures?

107. How do you return multiple result sets from a stored procedure?

108. What is the DELIMITER command and why is it needed?

109. How do you handle errors in stored procedures?

110. What is the DECLARE statement used for?

111. How do you use variables in stored procedures?

112. What are the control flow statements available in stored procedures (IF, CASE, LOOP, WHILE)?

113. How do you use cursors in stored procedures?

114. What are the performance implications of stored procedures?

115. How do stored procedures affect database portability?

116. Can stored procedures improve security?

117. How do you debug stored procedures in MySQL?

118. Write a stored procedure that performs bulk data operations.

119. What is the difference between stored procedures and prepared statements?

120. How do you optimize stored procedures for performance?

121. Can stored procedures call other stored procedures?

122. What is recursion in stored procedures?

123. How do you drop a stored procedure?

124. What is the SHOW PROCEDURE STATUS command?

125. How do stored procedures interact with transactions?

### Indexes - General (Questions 126-150)

126. What is a database index?

127. How do indexes improve query performance?

128. What is the cost of indexes on write operations?

129. How do you create an index in MySQL?

130. What columns should you index?

131. When should you NOT create an index?

132. What is index cardinality?

133. How does index selectivity affect performance?

134. What is a full table scan?

135. How do you force MySQL to use a specific index?

136. What is the FORCE INDEX hint?

137. How do you check if an index is being used?

138. What is the EXPLAIN statement?

139. How do you analyze query execution plans?

140. What is index fragmentation?

141. How do you rebuild or optimize indexes?

142. What is the difference between OPTIMIZE TABLE and ANALYZE TABLE?

143. How do you find unused indexes?

144. What is the overhead of maintaining too many indexes?

145. Can you have too many indexes on a table?

146. How do indexes affect backup and restore operations?

147. What is index statistics and why is it important?

148. How do you monitor index usage in production?

149. What is the key_len in EXPLAIN output?

150. How do you choose between creating a new index or modifying an existing one?

## Section 3: Clustered vs Non-Clustered Indexes (Questions 151-200)

### Clustered Indexes (Questions 151-175)

151. What is a clustered index?

152. How does a clustered index physically organize data?

153. How many clustered indexes can a table have?

154. What is the difference between a clustered index and a primary key?

155. In InnoDB, what is the default clustered index?

156. How do clustered indexes affect INSERT performance?

157. What is page splitting in clustered indexes?

158. How do clustered indexes improve range query performance?

159. What is the optimal data type for a clustered index key?

160. Why are sequential keys better for clustered indexes?

161. What are the disadvantages of using UUIDs as clustered index keys?

162. How do clustered indexes affect table storage size?

163. What is the leaf level of a clustered index?

164. How do clustered indexes store data pages?

165. What happens when you update a clustered index column?

166. How do you choose the right column for a clustered index?

167. Can you change the clustered index of a table?

168. What is the impact of a bad clustered index choice?

169. How do clustered indexes interact with secondary indexes?

170. What is the clustered index key in secondary indexes?

171. How do clustered indexes affect join performance?

172. What is index scan vs index seek on clustered indexes?

173. How do you monitor clustered index fragmentation?

174. What is the fill factor in clustered indexes?

175. How do clustered indexes affect backup performance?

### Non-Clustered Indexes (Questions 176-200)

176. What is a non-clustered index?

177. How does a non-clustered index differ from a clustered index?

178. How many non-clustered indexes can a table have?

179. How do non-clustered indexes store data?

180. What is a covering index?

181. How do covering indexes eliminate table lookups?

182. What is a key lookup (or bookmark lookup)?

183. How do key lookups affect query performance?

184. When should you create a non-clustered index?

185. How do you create a covering index in MySQL?

186. What is the INCLUDE clause in indexes (SQL Server concept)?

187. How do non-clustered indexes point to data rows in InnoDB?

188. What is the difference between a unique and non-unique non-clustered index?

189. How do non-clustered indexes affect memory usage?

190. What is the optimal number of non-clustered indexes per table?

191. How do you determine if a non-clustered index is beneficial?

192. What is the index intersection technique?

193. Can the query optimizer use multiple indexes for a single query?

194. What is index merging in MySQL?

195. How do filtered indexes work (partial indexes)?

196. How do you create a partial index in MySQL 8?

197. What are the benefits of partial indexes?

198. How do you maintain non-clustered indexes?

199. What is the relationship between non-clustered indexes and the clustered index?

200. How do you convert a non-clustered index to a covering index?

## Section 4: Joins (Questions 201-275)

### Join Types (Questions 201-230)

201. What is a JOIN in SQL?

202. What is an INNER JOIN?

203. Write a query using INNER JOIN to fetch users and their orders.

204. What is a LEFT JOIN (or LEFT OUTER JOIN)?

205. What is the difference between LEFT JOIN and INNER JOIN?

206. Write a query using LEFT JOIN to find users with no orders.

207. What is a RIGHT JOIN (or RIGHT OUTER JOIN)?

208. When would you use a RIGHT JOIN?

209. What is a FULL OUTER JOIN?

210. Does MySQL support FULL OUTER JOIN natively?

211. How do you simulate a FULL OUTER JOIN in MySQL?

212. What is a CROSS JOIN?

213. What is the result of a CROSS JOIN?

214. When is a CROSS JOIN useful?

215. What is a SELF JOIN?

216. Write a query using SELF JOIN to find employees and their managers.

217. What is a natural join?

218. Why should you avoid NATURAL JOINs?

219. What is an equi-join vs a non-equi-join?

220. What is a theta join?

221. What is an anti-join?

222. How do you write an anti-join using LEFT JOIN?

223. What is a semi-join?

224. How do you implement a semi-join using IN or EXISTS?

225. What is the difference between IN and EXISTS in joins?

226. Which performs better: IN or EXISTS?

227. What is a lateral join (MySQL 8.0+)?

228. How do lateral joins differ from regular joins?

229. Write a query using a lateral join for complex subqueries.

230. What is the performance impact of different join types?

### Join Optimization (Questions 231-260)

231. How do indexes affect join performance?

232. What is the join order and why does it matter?

233. How does the query optimizer determine join order?

234. What is a nested loop join?

235. When is nested loop join efficient?

236. What is a hash join?

237. When does MySQL use hash joins (MySQL 8.0.18+)?

238. What is a sort-merge join?

239. How do you optimize a query with multiple joins?

240. What is the driving table in a join?

241. How do you choose the optimal driving table?

242. What is join cardinality?

243. How does join cardinality affect performance?

244. What is a join buffer?

245. How does join_buffer_size affect join performance?

246. What is the Block Nested Loop (BNL) algorithm?

247. What is the Batched Key Access (BKA) join?

248. How do you enable BKA joins in MySQL?

249. What is index condition pushdown in joins?

250. How do you use STRAIGHT_JOIN to control join order?

251. What are the risks of forcing join order?

252. How do you analyze join performance using EXPLAIN?

253. What is the join type in EXPLAIN output?

254. What does "Using join buffer" mean in EXPLAIN?

255. How do you identify missing indexes for joins?

256. What is the cost of joining large tables?

257. How do you optimize joins with millions of rows?

258. Should you denormalize to avoid joins?

259. What is the trade-off between normalization and join performance?

260. How do you benchmark join query performance?

### Complex Join Scenarios (Questions 261-275)

261. How do you join three or more tables efficiently?

262. Write a query that joins users, orders, and products tables.

263. How do you join tables with composite keys?

264. What are the challenges of joining on multiple columns?

265. How do you join tables with different character sets or collations?

266. How do you perform a join with calculated fields?

267. How do you join tables from different databases?

268. What are the performance implications of cross-database joins?

269. How do you join temporal tables (with date ranges)?

270. Write a query to find overlapping date ranges using joins.

271. How do you join hierarchical data?

272. What is a recursive join (recursive CTE)?

273. Write a recursive query to traverse a tree structure.

274. How do you optimize queries with subqueries in joins?

275. What is the difference between JOIN and subquery performance?

## Section 5: Normalization (Questions 276-325)

### Normal Forms (Questions 276-305)

276. What is database normalization?

277. Why is normalization important?

278. What is First Normal Form (1NF)?

279. What are the rules for 1NF?

280. Give an example of a table that violates 1NF.

281. How do you convert a table to 1NF?

282. What is Second Normal Form (2NF)?

283. What is partial dependency?

284. How do you identify partial dependencies?

285. Give an example of a table in 1NF but not 2NF.

286. How do you convert a table to 2NF?

287. What is Third Normal Form (3NF)?

288. What is transitive dependency?

289. Give an example of transitive dependency.

290. How do you identify transitive dependencies?

291. How do you convert a table to 3NF?

292. What is Boyce-Codd Normal Form (BCNF)?

293. How does BCNF differ from 3NF?

294. Give an example where 3NF is satisfied but not BCNF.

295. What is Fourth Normal Form (4NF)?

296. What is a multi-valued dependency?

297. Give an example of a 4NF violation.

298. What is Fifth Normal Form (5NF)?

299. What is join dependency?

300. Is it necessary to normalize to 5NF in practice?

301. What is Domain-Key Normal Form (DKNF)?

302. What are the benefits of normalization?

303. What are the drawbacks of excessive normalization?

304. How far should you normalize in real-world applications?

305. What is denormalization and when should you use it?

### Practical Normalization (Questions 306-325)

306. Design a normalized database schema for a blog system.

307. Design a normalized schema for an e-commerce system.

308. Design a normalized schema for a banking system.

309. How do you normalize a table with repeating groups?

310. How do you normalize a table with multi-valued attributes?

311. What is the relationship between normalization and data redundancy?

312. How does normalization affect storage requirements?

313. How does normalization affect data integrity?

314. How does normalization affect insert/update/delete operations?

315. How does normalization affect query performance?

316. When should you denormalize for performance?

317. What is the impact of normalization on application complexity?

318. How do you balance normalization and performance?

319. What is the relationship between normalization and foreign keys?

320. How do you refactor an unnormalized database?

321. What tools can help with database normalization?

322. How do you document normalized database schemas?

323. What is the role of normalization in data warehousing?

324. How does normalization relate to OLTP vs OLAP systems?

325. What are the common normalization mistakes to avoid?

## Section 6: Data Types (Questions 326-375)

### Numeric Data Types (Questions 326-345)

326. What are the integer data types in MySQL (TINYINT, SMALLINT, MEDIUMINT, INT, BIGINT)?

327. What is the storage size and range of each integer type?

328. What is the UNSIGNED modifier for integer types?

329. When should you use TINYINT vs INT?

330. What is the AUTO_INCREMENT attribute?

331. What are the floating-point data types (FLOAT, DOUBLE)?

332. What is the difference between FLOAT and DOUBLE?

333. What are the precision issues with floating-point numbers?

334. What is the DECIMAL (or NUMERIC) data type?

335. When should you use DECIMAL instead of FLOAT?

336. How do you specify precision and scale in DECIMAL?

337. What is the storage requirement for DECIMAL columns?

338. How should you store monetary values in a database?

339. What is the ZEROFILL attribute?

340. What is the BIT data type used for?

341. How do you store boolean values in MySQL?

342. What is the difference between BOOLEAN and TINYINT(1)?

343. What are the performance implications of different numeric types?

344. How do you choose the right numeric data type?

345. What happens when you exceed the range of a numeric type?

### String Data Types (Questions 346-365)

346. What are the string data types in MySQL (CHAR, VARCHAR, TEXT, BLOB)?

347. What is the difference between CHAR and VARCHAR?

348. When should you use CHAR vs VARCHAR?

349. What is the maximum length of VARCHAR?

350. What are the TEXT data types (TINYTEXT, TEXT, MEDIUMTEXT, LONGTEXT)?

351. What is the difference between VARCHAR and TEXT?

352. When should you use TEXT instead of VARCHAR?

353. What are the BLOB data types (TINYBLOB, BLOB, MEDIUMBLOB, LONGBLOB)?

354. What is the difference between TEXT and BLOB?

355. What is the BINARY and VARBINARY data types?

356. How do character sets and collations affect string storage?

357. What is UTF-8 encoding in MySQL (utf8mb4)?

358. What is the difference between utf8 and utf8mb4?

359. How do you store emoji in MySQL?

360. What is the storage overhead of different string types?

361. How do indexes work on string columns?

362. What is the maximum index length for string columns?

363. What are the performance implications of long string columns?

364. How do you handle large text data efficiently?

365. What is the ENUM data type and when should you use it?

### Date, Time and Other Data Types (Questions 366-375)

366. What are the date and time data types (DATE, DATETIME, TIMESTAMP, TIME, YEAR)?

367. What is the difference between DATETIME and TIMESTAMP?

368. What is the range of DATETIME vs TIMESTAMP?

369. How does TIMESTAMP handle time zones?

370. What is the storage size of date and time types?

371. How do you store dates for international applications?

372. What is the JSON data type in MySQL?

373. How do you query JSON columns efficiently?

374. What are the advantages of using JSON columns?

375. When should you use JSON vs normalized tables?

## Section 7: Query Optimization (Questions 376-450)

### Query Analysis (Questions 376-405)

376. What is query optimization?

377. What is the EXPLAIN statement?

378. How do you read EXPLAIN output?

379. What is the SELECT type in EXPLAIN (SIMPLE, PRIMARY, SUBQUERY, etc.)?

380. What is the table type (or type column) in EXPLAIN output?

381. What does "type: ALL" mean in EXPLAIN?

382. What is "type: index" in EXPLAIN?

383. What is "type: range" in EXPLAIN?

384. What is "type: ref" in EXPLAIN?

385. What is "type: eq_ref" in EXPLAIN?

386. What is "type: const" in EXPLAIN?

387. Which join type is the fastest?

388. What is the possible_keys column in EXPLAIN?

389. What is the key column in EXPLAIN?

390. What is the key_len column in EXPLAIN?

391. What is the ref column in EXPLAIN?

392. What is the rows column in EXPLAIN?

393. What is the filtered column in EXPLAIN?

394. What is the Extra column in EXPLAIN?

395. What does "Using index" mean in EXPLAIN?

396. What does "Using where" mean in EXPLAIN?

397. What does "Using temporary" mean in EXPLAIN?

398. What does "Using filesort" mean in EXPLAIN?

399. What does "Using join buffer" mean in EXPLAIN?

400. How do you use EXPLAIN ANALYZE (MySQL 8.0.18+)?

401. What is the difference between EXPLAIN and EXPLAIN ANALYZE?

402. How do you identify slow queries?

403. What is the slow query log?

404. How do you enable and configure the slow query log?

405. What tools can you use for query analysis (pt-query-digest, etc.)?

### Query Performance Optimization (Questions 406-435)

406. How do you optimize SELECT queries?

407. How do you optimize WHERE clauses?

408. What is index selectivity and how does it affect queries?

409. How do you optimize ORDER BY clauses?

410. What causes filesort and how do you avoid it?

411. How do you optimize GROUP BY clauses?

412. How do you optimize LIMIT with OFFSET for pagination?

413. What is the keyset pagination technique?

414. How do you optimize queries with subqueries?

415. Should you use subqueries or joins?

416. How do you optimize correlated subqueries?

417. How do you optimize DISTINCT queries?

418. How do you optimize aggregate functions (COUNT, SUM, AVG, MAX, MIN)?

419. What is the cost of COUNT(*) on large tables?

420. How do you efficiently count rows in large tables?

421. How do you optimize queries with LIKE?

422. What are the limitations of using LIKE with leading wildcards?

423. How do full-text search indexes help with text queries?

424. How do you optimize queries with IN clauses?

425. What is the performance difference between IN and EXISTS?

426. How do you optimize queries with OR conditions?

427. How do you rewrite OR conditions using UNION?

428. How do you optimize queries with NOT IN?

429. How do you use indexes to optimize range queries?

430. What is covering index optimization?

431. How do you optimize INSERT operations?

432. How do you optimize bulk inserts?

433. How do you optimize UPDATE operations?

434. How do you optimize DELETE operations?

435. How do you handle large batch delete operations?

### Advanced Query Optimization (Questions 436-450)

436. What is query caching and how does it work?

437. Is query cache deprecated in MySQL 8.0?

438. What is the query execution plan cache?

439. How do you use query hints to optimize queries?

440. What is the FORCE INDEX hint?

441. What is the USE INDEX hint?

442. What is the IGNORE INDEX hint?

443. When should you use query hints?

444. What are the risks of using query hints?

445. How do you optimize queries in stored procedures?

446. How do you optimize queries with temporary tables?

447. How do you use Common Table Expressions (CTEs) for optimization?

448. What is the performance difference between CTEs and subqueries?

449. How do you optimize recursive queries?

450. How do you benchmark query performance?

## Section 8: MySQL Storage Engines (Questions 451-500)

### InnoDB Storage Engine (Questions 451-475)

451. What is InnoDB?

452. What are the key features of InnoDB?

453. What is ACID compliance?

454. How does InnoDB implement transactions?

455. What is InnoDB's multi-versioning (MVCC)?

456. How does MVCC improve concurrency?

457. What is the InnoDB buffer pool?

458. How do you configure the buffer pool size?

459. What is the optimal buffer pool size?

460. What is the InnoDB log file (redo log)?

461. How does the redo log ensure durability?

462. What is the undo log in InnoDB?

463. How does InnoDB handle row-level locking?

464. What is the difference between shared and exclusive locks?

465. What are intention locks in InnoDB?

466. What is gap locking in InnoDB?

467. How do you handle deadlocks in InnoDB?

468. What is the InnoDB deadlock detection mechanism?

469. How do you view InnoDB status information?

470. What is the doublewrite buffer in InnoDB?

471. What is the change buffer in InnoDB?

472. How does InnoDB store data physically (tablespaces)?

473. What is the system tablespace vs file-per-table?

474. What is the InnoDB page size?

475. How do you optimize InnoDB performance?

### MyISAM Storage Engine (Questions 476-490)

476. What is MyISAM?

477. What are the key features of MyISAM?

478. How does MyISAM differ from InnoDB?

479. Does MyISAM support transactions?

480. Does MyISAM support foreign keys?

481. What type of locking does MyISAM use?

482. What is table-level locking?

483. What are the advantages of MyISAM?

484. What are the disadvantages of MyISAM?

485. When should you use MyISAM over InnoDB?

486. Is MyISAM still recommended in modern MySQL?

487. How does MyISAM store data (MYD, MYI, frm files)?

488. What is the MyISAM key cache?

489. How do you optimize MyISAM tables?

490. How do you convert a MyISAM table to InnoDB?

### Other Storage Engines (Questions 491-500)

491. What is the MEMORY (HEAP) storage engine?

492. When should you use the MEMORY storage engine?

493. What are the limitations of MEMORY tables?

494. What is the CSV storage engine?

495. What is the ARCHIVE storage engine?

496. What is the BLACKHOLE storage engine?

497. What is the FEDERATED storage engine?

498. How do you choose the right storage engine?

499. Can you have different storage engines for different tables?

500. How do you check what storage engine a table uses?

## Section 9: SQL Query Writing Exercises (Questions 501-600)

### Basic Queries (Questions 501-525)

501. Write a query to select all columns from a users table.

502. Write a query to select only email and name columns from users.

503. Write a query to select users where age is greater than 18.

504. Write a query to select users where country is 'USA' or 'Canada'.

505. Write a query to select users where email is not NULL.

506. Write a query to find users whose name starts with 'A'.

507. Write a query to find users whose email contains 'gmail'.

508. Write a query to select users ordered by name in ascending order.

509. Write a query to select users ordered by created_at in descending order.

510. Write a query to select the first 10 users.

511. Write a query to select users from offset 20 with limit 10.

512. Write a query to count the total number of users.

513. Write a query to count users by country.

514. Write a query to find the maximum age in the users table.

515. Write a query to find the minimum, maximum, and average age.

516. Write a query to calculate the total sum of order amounts.

517. Write a query to select distinct countries from the users table.

518. Write a query to select users where age is between 18 and 30.

519. Write a query to select users where country is in ('USA', 'UK', 'Canada').

520. Write a query to find users created in the last 7 days.

521. Write a query to find users created in the current year.

522. Write a query to select users and format their created_at date.

523. Write a query to concatenate first_name and last_name.

524. Write a query to convert email to lowercase.

525. Write a query to extract the year from a date column.

### Intermediate Queries (Questions 526-560)

526. Write a query to find duplicate emails in the users table.

527. Write a query to delete duplicate rows keeping only one.

528. Write a query to find users who have never placed an order.

529. Write a query to find the top 5 customers by order count.

530. Write a query to find the second highest salary.

531. Write a query to find the nth highest salary using LIMIT.

532. Write a query to calculate running total of sales.

533. Write a query to rank users by their total spending.

534. Write a query to find users with above-average order amounts.

535. Write a query to find orders placed on weekends.

536. Write a query to calculate month-over-month growth.

537. Write a query to find users who placed orders in consecutive months.

538. Write a query to pivot data (convert rows to columns).

539. Write a query to unpivot data (convert columns to rows).

540. Write a query using CASE statement to categorize users by age.

541. Write a query to find gaps in sequential IDs.

542. Write a query to generate a date range between two dates.

543. Write a query to find overlapping date ranges.

544. Write a query to calculate the median value.

545. Write a query to find the mode (most frequent value).

546. Write a query to calculate percentiles.

547. Write a query to find users who haven't logged in for 30 days.

548. Write a query to calculate customer lifetime value.

549. Write a query to find the longest streak of consecutive days.

550. Write a query to transpose rows and columns.

551. Write a query using window functions to calculate moving average.

552. Write a query to find the first and last order for each customer.

553. Write a query to calculate the difference between consecutive rows.

554. Write a query to find islands of consecutive dates.

555. Write a query to aggregate data with multiple GROUP BY levels.

556. Write a query to find users with specific activity patterns.

557. Write a query using self-join to compare rows within the same table.

558. Write a query to calculate cohort analysis.

559. Write a query to find seasonal trends in data.

560. Write a query to implement lead and lag functions manually (pre-MySQL 8).

### Advanced Queries (Questions 561-600)

561. Write a query to find hierarchical data (employee-manager relationship).

562. Write a recursive CTE to traverse a tree structure.

563. Write a query to calculate the depth of each node in a hierarchy.

564. Write a query to find all ancestors of a given node.

565. Write a query to find all descendants of a given node.

566. Write a query to find the path from root to a specific node.

567. Write a query to find siblings in a hierarchical structure.

568. Write a query to balance a tree structure.

569. Write a query using nested sets model for hierarchical data.

570. Write a query to convert adjacency list to nested sets.

571. Write a query to implement full-text search without FULLTEXT index.

572. Write a query to calculate edit distance between strings.

573. Write a query to find similar records using fuzzy matching.

574. Write a query to implement pagination with cursor-based approach.

575. Write a query to detect anomalies in time-series data.

576. Write a query to calculate exponential moving average.

577. Write a query to implement A/B test analysis.

578. Write a query to calculate statistical significance (chi-square test).

579. Write a query to find market basket associations.

580. Write a query to implement collaborative filtering.

581. Write a query to calculate Jaccard similarity between sets.

582. Write a query to implement text search ranking.

583. Write a query to find common table patterns.

584. Write a query to implement data quality checks.

585. Write a query to identify and fix referential integrity issues.

586. Write a query to migrate data between schemas.

587. Write a query to implement slowly changing dimensions (Type 2).

588. Write a query to calculate retention rates.

589. Write a query to implement funnel analysis.

590. Write a query to calculate attribution models.

591. Write a query using JSON functions to query nested data.

592. Write a query to flatten nested JSON arrays.

593. Write a query to aggregate JSON data.

594. Write a query to update specific JSON fields.

595. Write a query to validate JSON structure.

596. Write a query to implement geospatial calculations.

597. Write a query to find nearest neighbors using latitude/longitude.

598. Write a query to implement time-zone conversions.

599. Write a query to handle sparse data efficiently.

600. Write a query to implement dynamic SQL based on conditions.
