# AI, LLM & Serverless - Complete Answers

1. Large Language Models (LLMs): neural networks trained on massive text datasets. Learn patterns in language, can generate text, answer questions, translate, summarize. Examples: GPT-4, Claude, Gemini.

2. LLM integration: use APIs (OpenAI, Anthropic, Google) to add AI features without training models. Send prompts, get completions. Costs per token (input + output). Stateless, no model management.

3. Prompt engineering: craft inputs to get desired outputs. Clear instructions, few-shot examples, role definition ("You are a helpful assistant"), constraint specification. Art and science.

4. Prompt templates: `f"Answer: {prompt}"` for simple, Langchain PromptTemplate for complex, complex substitutions. Reusable templates reduce code duplication.

5. Few-shot prompting: include examples in prompt to show desired behavior. Better than zero-shot for complex tasks. Trade-off: more tokens (higher cost).

6. Chain-of-thought prompting: ask model to "think step by step". Improves accuracy on reasoning tasks. Model explains reasoning, easier to debug.

7. Temperature parameter: 0 = deterministic, 1 = random. Lower for factual tasks, higher for creative. Controls diversity in outputs.

8. Max tokens: limit response length. Prevents runaway generations, controls cost. Set based on max expected output size.

9. Streaming responses: get output token-by-token, display to user as arrived. Better UX than waiting for full response. Enable with `stream=true` parameter.

10. Embeddings: convert text to vector representation. Similar texts have similar embeddings. Use for similarity search, clustering, recommendation. Model: `text-embedding-3-small`.

11. Vector databases: store embeddings, search by similarity. Pinecone, Weaviate, Milvus, pgvector (PostgreSQL). Enable semantic search without keyword matching.

12. Retrieval-Augmented Generation (RAG): retrieve relevant documents, pass to LLM with prompt. LLM generates answer grounded in documents. Reduces hallucinations.

13. RAG pipeline: (1) Ingest documents, (2) Split into chunks, (3) Generate embeddings, (4) Store in vector DB, (5) User query -> embed -> search -> retrieve docs -> LLM with context.

14. Fine-tuning: train model on custom data to specialize. Expensive (compute), requires labeled data, improves domain performance. Alternative: RAG for less cost.

15. Token limits: models have max context length. GPT-4 = 8k/128k tokens, Claude = 100k tokens. Split large documents, prioritize recent/relevant for context.

16. Cost optimization: use cheaper models (GPT-3.5 vs GPT-4), batch requests, cache common queries, compress prompts, use embeddings instead of LLM.

17. Hallucinations: LLM makes up facts confidently. Mitigate with: few-shot examples, explicit "I don't know" instruction, fact-checking against trusted sources, RAG.

18. Moderation: filter user inputs/outputs for harmful content. OpenAI Moderation API, third-party services. Compliance, safety, brand protection.

19. Function calling: tell LLM about functions, it decides to call them. LLM decides when/what functions to use. Enables tool use, API integration.

20. Tool use example: LLM decides to call weather API, parsing function arguments from response. Enable complex workflows, LLM as orchestrator.

21. Serverless computing: execute code without managing servers. AWS Lambda, Google Cloud Functions, Azure Functions. Pay per invocation, auto-scale, zero overhead.

22. Lambda functions: event-driven, stateless, timeout 15 min. Handler function receives event, returns result. Integrates with AWS services (S3, DynamoDB, API Gateway).

23. Lambda layers: shared code libraries, dependencies. Package once, use across functions. Reduces deployment package size, easier updates.

24. Lambda concurrency: function invocations run in parallel, limit concurrent executions. Reserved concurrency for critical functions, prevents other functions from starving.

25. Lambda cold starts: first invocation slow (~1s for Node.js, ~15s for Java). Provisioned concurrency keeps instances warm, eliminates cold starts.

26. Lambda cost optimization: minimize execution time, use efficient libraries, reduce memory (slower CPU), right-size memory allocation, monitor invocations.

27. API Gateway: create HTTP APIs, route requests to Lambda. Methods (GET, POST, PUT), path parameters, request/response transformation. Can rate limit, authenticate.

28. API Gateway caching: cache responses by URL/method, reduce backend calls. TTL for cache expiration. Useful for expensive computations, read-heavy APIs.

29. Google Cloud Functions: similar to Lambda, supports Node.js, Python, Go, Java. HTTP or event-driven, auto-scale, pay per invocation. Good GCP integration.

30. Cloud Run: serverless container execution. Deploy any container, Cloud Run manages scaling, routing. Pay per request. Flexible (any language/framework).

31. Event-driven architecture: components communicate via events (asynchronous). Decoupled, scalable, resilient. Challenges: eventual consistency, debugging complexity.

32. Event sourcing: store all events, rebuild state from event stream. Audit trail, time-travel debugging, event replay. Complex to implement, benefits for critical systems.

33. CQRS (Command Query Responsibility Segregation): separate read and write models. Commands modify state, queries read from optimized read model. Improves performance, complexity.

34. Message queues: decouple producers from consumers. SQS (AWS), Pub/Sub (GCP), RabbitMQ. Async processing, retry logic, durability.

35. Pub/Sub: publish messages to topic, multiple subscribers receive. Loose coupling, broadcast semantics. Useful for notifications, event broadcasting.

36. SNS (Simple Notification Service): pub/sub messaging. Topics, subscriptions (SQS, Lambda, HTTP). Fan-out messages, broadcast notifications.

37. SQS (Simple Queue Service): message queue, FIFO or standard. Process messages asynchronously, at-most-once or exactly-once delivery. Long polling reduces polling frequency.

38. Lambda with SQS: SQS triggers Lambda, batch processing. Failing messages go to DLQ (dead-letter queue). Automatic retries, visibility timeout.

39. DynamoDB: NoSQL database, serverless. Automatic scaling, pay per request. Streams for change data capture, triggers Lambda. Single partition key or composite key.

40. DynamoDB Global Tables: replicate across regions, multi-master replication. Low latency reads locally, disaster recovery. Built-in conflict resolution.

41. Firebase Firestore: cloud Firestore NoSQL database, real-time, offline support. Scales automatically, charges per operation, built-in auth/hosting.

42. Firebase Cloud Functions: event-driven functions triggered by Firestore, Auth, Storage. Integrates with Firebase ecosystem, simpler than managing Lambda.

43. Scheduled functions: cron job equivalent in serverless. AWS EventBridge -> Lambda, Google Cloud Scheduler -> Cloud Function. Periodic tasks without managing servers.

44. Monitoring serverless: CloudWatch (AWS), Cloud Logging (GCP) for logs, CloudWatch Metrics for metrics. Distributed tracing with X-Ray/Cloud Trace.

45. Debugging serverless: Local testing with SAM/serverless framework, CloudWatch Logs for production, synthetic monitoring for availability.

46. State management in serverless: state stored in databases (DynamoDB, Firestore) or caches (DynamoDB, Redis). Stateless functions call stateful backend.

47. Serverless patterns: fanout-and-aggregate, scheduler, stream processing. Combine functions for complex workflows. Step Functions (AWS) orchestrate.

48. Step Functions: orchestrate multi-step workflows. Visual workflow definition, state machine, retry/error handling. Serverless workflow engine.

49. Serverless framework: deploy, manage serverless apps. Supports AWS, GCP, Azure. Code infrastructure as code, easy deployment, local testing.

50. Terraform for serverless: define Lambda, API Gateway, DynamoDB with Terraform. Version control infrastructure, apply across environments.

51. Containerless to containers: migration path from serverless to containerized. Start serverless (simplicity), move to containers if need more control.

52. Serverless vs containers: serverless simpler (less ops), containers more control (dependencies, runtime). Serverless cheaper at low scale, containers cheaper at high scale.

53. Cold start mitigation: provisioned concurrency (keep instances warm), lightweight dependencies, efficient startup code, Node.js faster than Python/Java.

54. Vendor lock-in: serverless tied to specific cloud provider. Mitigate with abstraction layers, multi-cloud deployment, portability tools.

55. Scaling serverless: automatic by cloud provider, no manual intervention. Set concurrency limits to prevent runaway costs. Monitor and set budgets.

56. Security in serverless: use secrets manager for credentials, IAM for access control, VPC for network isolation, encrypt data at rest/in transit.

57. Permissions (IAM): Lambda needs permissions to access resources (DynamoDB read, S3 write). Principle of least privilege: grant minimum needed permissions.

58. VPC for Lambda: Lambda in VPC can access private databases. Trade-off: slower startup (ENI attachment), must be in same VPC as resources.

59. Lambda environment variables: store config, API keys (non-sensitive), feature flags. Use Secrets Manager for sensitive data.

60. LLM observability: log prompts, tokens, cost, latency. Monitor API quotas, detect abuse, analyze usage patterns. Crucial for cost control and debugging.

61. LLM caching: cache responses to common prompts. Redis for distributed cache, in-memory for single instance. Significant cost savings.

62. LLM rate limiting: prevent abuse, control costs. Rate limit per user, IP, API key. Exponential backoff for retries.

63. Prompt injection attacks: user input injected into prompt, model follows malicious instructions. Mitigate with input validation, prompt templates, sandboxing.

64. Data privacy with LLMs: don't send sensitive data to third-party LLM APIs. Use on-premise/self-hosted models, PII redaction, contracts with providers.

65. LLM-powered applications: chatbots, code assistants, content generation, summarization, translation, analysis. Different prompts/models for different tasks.

66. Chatbot with memory: store conversation history, include in context. Trimming for token limits, summarization of old messages.

67. RAG chatbot: user query -> embed -> retrieve docs -> LLM with docs + query. Grounds responses in documents, reduces hallucinations.

68. Code generation with LLMs: generate code snippets, tests, documentation. Mitigate: test generated code, review for security, don't blindly run.

69. Image generation: DALL-E, Midjourney, Stable Diffusion. Prompt-based generation, fine-tuning on custom data. Licensing concerns.

70. Vision LLMs: analyze images, answer questions about images. GPT-4 Vision, Claude Vision, Gemini Pro Vision. Useful for document analysis, object detection.

71. Multi-modal LLMs: process text + images + audio. Richer understanding, more capable than text-only. Higher cost per token.

72. LLM safety: filter toxic outputs, ensure factuality, prevent harm. Approaches: fine-tuning, RLHF (Reinforcement Learning from Human Feedback), constitutional AI.

73. Open-source LLMs: Llama 2, Mistral, Falcon. Self-host for privacy, fine-tune on custom data. Trade-off: must manage infrastructure, less capable than proprietary.

74. LLM APIs vs open-source: API: pay per token, simple, latest models. Open-source: control, privacy, higher operational cost, potentially less capable.

75. Langchain framework: Python/JS library for LLM apps. Components: models, prompts, chains, agents, memory. Abstracts provider differences.

76. Langchain agents: LLM decides which tools to use to answer questions. Tools: web search, calculators, APIs. Powerful but unpredictable (may use tools wrong).

77. LLMOps: manage, monitor, deploy LLM-powered applications. Versioning prompts/models, A/B testing, cost tracking, latency monitoring.

78. Prompt versioning: track prompt changes, revert if performance degrades. Git-like versioning for prompts, test before deploying.

79. A/B testing LLMs: compare model versions, measure performance metrics (accuracy, user preference). Statistical significance testing.

80. Cost tracking: track tokens used, model calls, costs. Alert on budget exceeded. Essential for cost control with LLM APIs.

81. LLM latency: request->response time. Depends on model, input/output length, queue time. Stream responses for perceived faster response.

82. Batch processing: batch API calls for lower cost/latency. Requires async processing, useful for non-real-time tasks.

83. Streaming output: show response token-by-token, better UX. Use WebSocket or Server-Sent Events for real-time updates.

84. Error handling with LLMs: API errors, timeouts, invalid responses. Retry with backoff, fallback models, graceful degradation.

85. Testing LLM outputs: LLMs non-deterministic, hard to test. Approaches: regex matching, semantic similarity, human evaluation, test with multiple runs.

86. Performance baselines: measure quality metrics (BLEU, ROUGE, user satisfaction). Before/after model changes, track regressions.

87. Compliance with AI: GDPR (data privacy), CCPA (user rights), emerging AI regulation. Document AI usage, obtain consent, right to explanation.

88. Responsible AI: fairness, transparency, accountability. Audit models for bias, explain decisions, human oversight for critical decisions.

89. Knowledge bases for LLMs: company data (documentation, customer data) indexed for RAG. Enables LLM to answer company-specific questions.

90. LLM fine-tuning: improve on specific tasks. Requires labeled examples (100-1000s), custom training. More control than few-shot prompting.

91. Embeddings for recommendations: user embeddings + item embeddings, similarity = recommendation score. Personalized, scalable recommendations.

92. Semantic search: embed documents and queries, find most similar documents. Better than keyword search for meaning-based retrieval.

93. Clustering with embeddings: group similar items (users, products) using embeddings. Unsupervised organization, customer segmentation.

94. Anomaly detection: detect unusual patterns using embeddings. Deviation from cluster center = anomaly. Fraud detection, system monitoring.

95. Transfer learning with embeddings: embeddings pre-trained on large dataset, fine-tune for specific task. Lower training data needs.

96. Real-time LLM applications: edge deployment, local models for latency. Trade-off: model size vs capability.

97. LLM security: input validation, output filtering, rate limiting, auth, audit logging. Prevent abuse, data leaks, unauthorized access.

98. Blockchain + LLM: decentralized AI models, incentive systems for data contribution. Emerging technology, mostly experimental.

99. LLM inference optimization: model quantization (lower precision), pruning (remove unused weights), distillation (smaller model learning from large).

100. Future of LLMs: larger models, multi-modal, faster inference, on-device models, better reasoning, improved safety. Rapidly evolving technology.

