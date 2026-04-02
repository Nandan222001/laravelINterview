# AI, LLMs & Serverless - Question Index

## Quick Navigation

- [Section 1: AWS Lambda & Serverless Architecture (1-150)](#section-1-aws-lambda--serverless-architecture-questions-1-150)
- [Section 2: Transformer Architecture & Deep Learning (151-300)](#section-2-transformer-architecture--deep-learning-questions-151-300)
- [Section 3: Training & Hyperparameters (301-450)](#section-3-training--hyperparameters-questions-301-450)
- [Section 4: RAG (Retrieval-Augmented Generation) (451-600)](#section-4-rag-retrieval-augmented-generation-questions-451-600)
- [Section 5: MCP & LLM Integration (601-750)](#section-5-mcp--llm-integration-questions-601-750)
- [Section 6: Anthropic Claude & Token Management (751-850)](#section-6-anthropic-claude--token-management-questions-751-850)
- [Section 7: Advanced LLM Topics (851-1000)](#section-7-advanced-llm-topics-questions-851-1000)

## Section Breakdown

### Section 1: AWS Lambda & Serverless Architecture (Questions 1-150)

#### Lambda Event-Driven Architecture (1-30)
Event sources, execution model, concurrency, DynamoDB/SQS/S3 integration, retry logic, DLQ, Step Functions, EventBridge, Lambda destinations

#### Lambda Layers & Dependencies (31-50)
Layer creation, version management, cold start impact, shared utilities, deployment optimization, layer extraction order

#### Cold Start Optimization (51-80)
Cold start causes, provisioned concurrency, VPC impact, SnapStart, ARM64 Graviton2, lazy loading, connection pooling, container reuse, warm start strategies

#### API Gateway Integration (81-110)
REST API vs HTTP API vs WebSocket, proxy integration, throttling, caching, Lambda authorizers, VTL, CORS, stages, mutual TLS

#### Serverless Webhook Processing (111-150)
Razorpay/Stripe webhooks, signature verification, idempotency, retry handling, webhook security, event deduplication, async processing, audit trails

### Section 2: Transformer Architecture & Deep Learning (Questions 151-300)

#### Attention Mechanism (151-190)
Scaled dot-product attention, Q/K/V concepts, self-attention vs cross-attention, masked attention, computational complexity, attention variations

#### Multi-Head Attention (191-220)
Multiple representation subspaces, linear projections, head dimension, parallel computation, attention specialization, quantization

#### Positional Encoding (221-250)
Sinusoidal encoding, learned embeddings, absolute vs relative, ALiBi, RoPE, position interpolation, length extrapolation

#### Feed-Forward Networks & Layer Normalization (251-280)
Position-wise FFN, activation functions (ReLU, GELU, SwiGLU), LayerNorm, RMSNorm, Pre-LN vs Post-LN, residual connections

#### Encoder-Decoder Architecture (281-300)
Encoder-only vs decoder-only vs encoder-decoder, cross-attention, teacher forcing, beam search, sampling strategies

### Section 3: Training & Hyperparameters (Questions 301-450)

#### Learning Rate & Optimization (301-340)
Learning rate impact, warm-up strategies, cosine annealing, AdamW optimizer, gradient clipping, learning rate finder, momentum

#### Batch Size & Memory Optimization (341-370)
Batch size trade-offs, gradient accumulation, micro-batch vs global batch, gradient checkpointing, ZeRO optimization, mixed-precision

#### Epochs & Training Duration (371-400)
Training duration, early stopping, convergence detection, validation strategies, checkpoint saving, one-epoch pretraining

#### Regularization & Dropout (401-430)
Dropout rates, attention dropout, residual dropout, DropPath, stochastic depth, dropout alternatives, model calibration

#### Advanced Training Techniques (431-450)
Mixed-precision (FP16/BF16), gradient checkpointing, distributed training (DDP/FSDP), ZeRO stages, pipeline parallelism, curriculum learning

### Section 4: RAG (Retrieval-Augmented Generation) (Questions 451-600)

#### RAG Architecture & Concepts (451-480)
RAG components, knowledge incorporation, chunk size, metadata filtering, multi-hop reasoning, hybrid search, reranking, evaluation metrics

#### Vector Databases - Pinecone (481-505)
Pinecone architecture, pods, indexes, HNSW, ANN search, namespaces, metadata filtering, sparse-dense indexes

#### Vector Databases - Weaviate (506-530)
Schema design, modules, GraphQL API, multi-tenancy, hybrid search, generative module, cross-references

#### Vector Databases - pgvector (531-555)
PostgreSQL extension, IVFFlat, HNSW indexes, distance operators, k-NN search, integration with PostgreSQL features

#### Embeddings & Semantic Search (556-585)
Embedding models, dimensionality, cosine similarity, semantic search, embedding evaluation, multi-lingual embeddings, dense passage retrieval

#### RAG Implementation Patterns (586-600)
Banking document Q&A, document parsing, chunk strategies, tables handling, source attribution, access control, knowledge base updates

### Section 5: MCP & LLM Integration (Questions 601-750)

#### MCP (Model Context Protocol) (601-640)
MCP architecture, security benefits, authentication, PII protection, context isolation, rate limiting, audit logging, compliance

#### Fine-tuning Strategies (641-670)
Fine-tuning vs RAG vs prompt engineering, PEFT, LoRA, QLoRA, adapter-based fine-tuning, instruction tuning, RLHF

#### Prompt Engineering (671-700)
Zero-shot, few-shot, chain-of-thought, prompt injection, structured output, prompt chaining, temperature, ReAct, Reflexion

#### LangChain Framework (701-730)
Chains, agents, memory, LCEL, tool integration, document loaders, output parsers, callbacks, LangSmith

#### OpenAI API Integration (731-750)
Chat completions, models (GPT-3.5, GPT-4, GPT-4 Turbo), function calling, streaming, rate limiting, tiktoken, Assistants API

### Section 6: Anthropic Claude & Token Management (Questions 751-850)

#### Anthropic Claude API (751-780)
Claude models (Opus, Sonnet, Haiku), context window, tool use, constitutional AI, vision capabilities, pricing optimization

#### Token Limits & Context Windows (781-810)
Token counting, context window sizes, token budgeting, sliding window, conversation compression, prompt caching, lost in the middle

#### Embedding Generation (811-840)
Embedding models, dimensionality, batching, caching, normalization, multilingual embeddings, embedding fine-tuning

#### Semantic Search for Banking Documents (841-850)
Banking document search, domain terminology, compliance requirements, access control, version management, audit trails

### Section 7: Advanced LLM Topics (Questions 851-1000)

#### LLM System Design (851-880)
Scalable architecture, caching strategies, rate limiting, fallback strategies, monitoring, circuit breaker, A/B testing, multi-tenant

#### LLM Security & Safety (881-910)
Prompt injection, jailbreaking, PII detection, content moderation, bias/fairness, compliance, audit logging, constitutional AI

#### LLM Performance Optimization (911-940)
Quantization, batching, KV cache, continuous batching, pruning, speculative decoding, Flash Attention, knowledge distillation

#### Advanced RAG & Agent Patterns (941-970)
HyDE, Self-RAG, query expansion, reranking, parent-document retrieval, agentic RAG, multi-hop reasoning, graph-based RAG

#### Multimodal LLMs & Future Trends (971-1000)
Vision-language models, document analysis, OCR, multimodal embeddings, code generation, compound AI systems, multi-agent systems

## Topics Coverage

### AWS Lambda & Serverless (150 questions)
- Event-driven architecture
- Cold start optimization
- Lambda layers
- API Gateway integration
- Webhook processing (Razorpay/Stripe)

### Transformer Architecture (150 questions)
- Attention mechanism (scaled dot-product, multi-head)
- Positional encoding (sinusoidal, RoPE, ALiBi)
- Feed-forward networks
- Layer normalization
- Encoder-decoder architecture

### Training & Hyperparameters (150 questions)
- Learning rate & optimization (AdamW, warm-up, cosine annealing)
- Batch size & memory optimization
- Epochs & training duration
- Regularization & dropout
- Distributed training (DDP, FSDP, ZeRO)

### RAG Implementation (150 questions)
- RAG architecture & patterns
- Vector databases (Pinecone, Weaviate, pgvector)
- Embeddings & semantic search
- Banking document systems

### LLM Integration (150 questions)
- MCP (Model Context Protocol)
- Fine-tuning vs prompt engineering
- LangChain framework
- OpenAI API integration

### Token Management & APIs (100 questions)
- Anthropic Claude API
- Token limits & context windows
- Embedding generation
- Banking semantic search

### Advanced Topics (150 questions)
- LLM system design
- Security & safety
- Performance optimization
- Advanced RAG patterns
- Multimodal LLMs
- Future trends

## Difficulty Distribution

- **Beginner (Questions 1-300)**: Core concepts, basic implementations
- **Intermediate (Questions 301-700)**: Advanced techniques, practical applications
- **Advanced (Questions 701-1000)**: System design, optimization, production patterns

## Key Technologies Covered

### Serverless & Cloud
- AWS Lambda, API Gateway, EventBridge
- DynamoDB Streams, SQS, S3 events
- Lambda layers, provisioned concurrency
- Serverless webhooks (Razorpay, Stripe, PayPal)

### Deep Learning
- Transformer architecture
- Attention mechanisms
- Positional encoding techniques
- Training hyperparameters
- Optimization algorithms

### Vector Databases
- Pinecone (cloud-native)
- Weaviate (GraphQL, modules)
- pgvector (PostgreSQL extension)
- HNSW, IVFFlat indexes

### LLM Frameworks & APIs
- OpenAI (GPT-3.5, GPT-4)
- Anthropic Claude (Opus, Sonnet, Haiku)
- LangChain, LlamaIndex
- MCP (Model Context Protocol)

### RAG & Embeddings
- Retrieval-augmented generation
- Embedding models (OpenAI, Sentence Transformers)
- Semantic search
- Hybrid search (dense + sparse)

### Production Patterns
- System design for scale
- Security & compliance
- Performance optimization
- Cost management
- Monitoring & observability
