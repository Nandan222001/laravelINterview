# AI, LLM & Serverless Interview Questions

## Table of Contents
- [LLM Integration & Prompt Engineering](#llm-integration--prompt-engineering)
- [Vector Databases & Semantic Search](#vector-databases--semantic-search)
- [RAG Systems](#rag-systems)
- [Serverless Architecture](#serverless-architecture)
- [Cost Optimization & Scaling](#cost-optimization--scaling)

---

## LLM Integration & Prompt Engineering

### Question 1: Production-Ready LLM Integration with Retry Logic

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: LLM Integration, Error Handling  
**Tags**: `openai`, `retry-logic`, `streaming`, `error-handling`

#### Question

Implement a production-ready LLM service that handles:
- Multiple providers (OpenAI, Anthropic, local models)
- Retry logic with exponential backoff
- Streaming responses
- Token counting and cost tracking
- Rate limiting
- Prompt caching

#### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      v
┌─────────────────────────────────────────────────────────────┐
│                   LLM Service Layer                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   OpenAI    │  │  Anthropic  │  │   Local     │         │
│  │  Provider   │  │  Provider   │  │   Model     │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┼─────────────┐
        │             │             │
        v             v             v
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Rate Limiter │ │Token Counter │ │ Cost Tracker │
└──────────────┘ └──────────────┘ └──────────────┘
        │             │             │
        v             v             v
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Redis Cache  │ │  PostgreSQL  │ │   Metrics    │
└──────────────┘ └──────────────┘ └──────────────┘
```

#### Solution

```typescript
// llm-service.ts
import OpenAI from 'openai';
import Anthropic from '@anthropic-ai/sdk';
import { encode } from 'gpt-tokenizer';
import { Redis } from 'ioredis';
import pLimit from 'p-limit';

interface LLMProvider {
  name: string;
  apiKey: string;
  model: string;
  maxTokens: number;
  temperature: number;
}

interface LLMRequest {
  prompt: string;
  systemPrompt?: string;
  provider?: 'openai' | 'anthropic' | 'local';
  stream?: boolean;
  maxRetries?: number;
  temperature?: number;
  maxTokens?: number;
}

interface LLMResponse {
  content: string;
  tokens: {
    prompt: number;
    completion: number;
    total: number;
  };
  cost: number;
  provider: string;
  cached: boolean;
}

interface TokenCost {
  promptTokenPrice: number;
  completionTokenPrice: number;
}

const TOKEN_COSTS: Record<string, TokenCost> = {
  'gpt-4-turbo-preview': {
    promptTokenPrice: 0.01 / 1000,
    completionTokenPrice: 0.03 / 1000,
  },
  'gpt-3.5-turbo': {
    promptTokenPrice: 0.0005 / 1000,
    completionTokenPrice: 0.0015 / 1000,
  },
  'claude-3-opus-20240229': {
    promptTokenPrice: 0.015 / 1000,
    completionTokenPrice: 0.075 / 1000,
  },
  'claude-3-sonnet-20240229': {
    promptTokenPrice: 0.003 / 1000,
    completionTokenPrice: 0.015 / 1000,
  },
};

export class LLMService {
  private openai: OpenAI;
  private anthropic: Anthropic;
  private redis: Redis;
  private rateLimiter: ReturnType<typeof pLimit>;

  constructor(
    private config: {
      openaiKey: string;
      anthropicKey: string;
      redisUrl: string;
      maxConcurrent?: number;
    }
  ) {
    this.openai = new OpenAI({ apiKey: config.openaiKey });
    this.anthropic = new Anthropic({ apiKey: config.anthropicKey });
    this.redis = new Redis(config.redisUrl);
    this.rateLimiter = pLimit(config.maxConcurrent || 10);
  }

  // Main completion method
  async complete(request: LLMRequest): Promise<LLMResponse> {
    const cacheKey = this.getCacheKey(request);
    
    // Check cache first
    const cached = await this.redis.get(cacheKey);
    if (cached) {
      const response = JSON.parse(cached);
      return { ...response, cached: true };
    }

    // Rate limiting
    return this.rateLimiter(async () => {
      const response = await this.executeWithRetry(request);
      
      // Cache the response
      await this.redis.setex(
        cacheKey,
        3600, // 1 hour TTL
        JSON.stringify(response)
      );
      
      // Track metrics
      await this.trackMetrics(response);
      
      return response;
    });
  }

  // Execute with retry logic
  private async executeWithRetry(
    request: LLMRequest,
    attempt = 0
  ): Promise<LLMResponse> {
    const maxRetries = request.maxRetries || 3;
    
    try {
      switch (request.provider || 'openai') {
        case 'openai':
          return await this.callOpenAI(request);
        case 'anthropic':
          return await this.callAnthropic(request);
        case 'local':
          return await this.callLocalModel(request);
        default:
          throw new Error(`Unknown provider: ${request.provider}`);
      }
    } catch (error: any) {
      if (attempt >= maxRetries) {
        throw error;
      }

      // Check if error is retryable
      if (this.isRetryableError(error)) {
        // Exponential backoff
        const delay = Math.min(1000 * Math.pow(2, attempt), 10000);
        await new Promise((resolve) => setTimeout(resolve, delay));
        
        return this.executeWithRetry(request, attempt + 1);
      }

      throw error;
    }
  }

  // OpenAI implementation
  private async callOpenAI(request: LLMRequest): Promise<LLMResponse> {
    const model = 'gpt-4-turbo-preview';
    
    const messages: OpenAI.Chat.ChatCompletionMessageParam[] = [];
    
    if (request.systemPrompt) {
      messages.push({
        role: 'system',
        content: request.systemPrompt,
      });
    }
    
    messages.push({
      role: 'user',
      content: request.prompt,
    });

    if (request.stream) {
      return await this.streamOpenAI(messages, model, request);
    }

    const completion = await this.openai.chat.completions.create({
      model,
      messages,
      temperature: request.temperature || 0.7,
      max_tokens: request.maxTokens || 2000,
    });

    const content = completion.choices[0].message.content || '';
    const usage = completion.usage!;

    return {
      content,
      tokens: {
        prompt: usage.prompt_tokens,
        completion: usage.completion_tokens,
        total: usage.total_tokens,
      },
      cost: this.calculateCost(
        model,
        usage.prompt_tokens,
        usage.completion_tokens
      ),
      provider: 'openai',
      cached: false,
    };
  }

  // Streaming OpenAI implementation
  private async streamOpenAI(
    messages: OpenAI.Chat.ChatCompletionMessageParam[],
    model: string,
    request: LLMRequest
  ): Promise<LLMResponse> {
    const stream = await this.openai.chat.completions.create({
      model,
      messages,
      temperature: request.temperature || 0.7,
      max_tokens: request.maxTokens || 2000,
      stream: true,
    });

    let content = '';
    for await (const chunk of stream) {
      const delta = chunk.choices[0]?.delta?.content || '';
      content += delta;
      // Emit chunk event for real-time processing
      this.emit('chunk', delta);
    }

    // Estimate tokens (since streaming doesn't return usage)
    const promptTokens = this.countTokens(
      messages.map((m) => m.content).join(' ')
    );
    const completionTokens = this.countTokens(content);

    return {
      content,
      tokens: {
        prompt: promptTokens,
        completion: completionTokens,
        total: promptTokens + completionTokens,
      },
      cost: this.calculateCost(model, promptTokens, completionTokens),
      provider: 'openai',
      cached: false,
    };
  }

  // Anthropic implementation
  private async callAnthropic(request: LLMRequest): Promise<LLMResponse> {
    const model = 'claude-3-opus-20240229';

    const message = await this.anthropic.messages.create({
      model,
      max_tokens: request.maxTokens || 2000,
      temperature: request.temperature || 0.7,
      system: request.systemPrompt,
      messages: [
        {
          role: 'user',
          content: request.prompt,
        },
      ],
    });

    const content =
      message.content[0].type === 'text' ? message.content[0].text : '';

    return {
      content,
      tokens: {
        prompt: message.usage.input_tokens,
        completion: message.usage.output_tokens,
        total: message.usage.input_tokens + message.usage.output_tokens,
      },
      cost: this.calculateCost(
        model,
        message.usage.input_tokens,
        message.usage.output_tokens
      ),
      provider: 'anthropic',
      cached: false,
    };
  }

  // Local model implementation (Ollama example)
  private async callLocalModel(request: LLMRequest): Promise<LLMResponse> {
    const response = await fetch('http://localhost:11434/api/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'llama2',
        prompt: request.prompt,
        stream: false,
      }),
    });

    const data = await response.json();
    const content = data.response;
    
    const promptTokens = this.countTokens(request.prompt);
    const completionTokens = this.countTokens(content);

    return {
      content,
      tokens: {
        prompt: promptTokens,
        completion: completionTokens,
        total: promptTokens + completionTokens,
      },
      cost: 0, // Local models are free
      provider: 'local',
      cached: false,
    };
  }

  // Utility methods
  private getCacheKey(request: LLMRequest): string {
    const key = JSON.stringify({
      prompt: request.prompt,
      system: request.systemPrompt,
      provider: request.provider,
      temp: request.temperature,
      maxTokens: request.maxTokens,
    });
    
    return `llm:${this.hash(key)}`;
  }

  private hash(str: string): string {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = (hash << 5) - hash + char;
      hash = hash & hash;
    }
    return hash.toString(36);
  }

  private countTokens(text: string): number {
    return encode(text).length;
  }

  private calculateCost(
    model: string,
    promptTokens: number,
    completionTokens: number
  ): number {
    const costs = TOKEN_COSTS[model];
    if (!costs) return 0;

    return (
      promptTokens * costs.promptTokenPrice +
      completionTokens * costs.completionTokenPrice
    );
  }

  private isRetryableError(error: any): boolean {
    const retryableStatuses = [429, 500, 502, 503, 504];
    return (
      retryableStatuses.includes(error.status) ||
      error.code === 'ECONNRESET' ||
      error.code === 'ETIMEDOUT'
    );
  }

  private async trackMetrics(response: LLMResponse): Promise<void> {
    // Track metrics in your monitoring system
    await this.redis.hincrby('llm:metrics', 'total_requests', 1);
    await this.redis.hincrby('llm:metrics', 'total_tokens', response.tokens.total);
    await this.redis.hincrbyfloat('llm:metrics', 'total_cost', response.cost);
  }

  private emit(event: string, data: any): void {
    // Event emitter for streaming chunks
    // Implementation depends on your event system
  }
}
```

#### Usage Example

```typescript
// Example usage
const llmService = new LLMService({
  openaiKey: process.env.OPENAI_API_KEY!,
  anthropicKey: process.env.ANTHROPIC_API_KEY!,
  redisUrl: process.env.REDIS_URL!,
  maxConcurrent: 5,
});

// Simple completion
const response = await llmService.complete({
  prompt: 'Explain quantum computing in simple terms',
  systemPrompt: 'You are a helpful science teacher',
  provider: 'openai',
});

console.log(response.content);
console.log(`Cost: $${response.cost.toFixed(4)}`);
console.log(`Tokens: ${response.tokens.total}`);

// Streaming completion
const streamResponse = await llmService.complete({
  prompt: 'Write a story about AI',
  stream: true,
  provider: 'anthropic',
});
```

---

## Vector Databases & Semantic Search

### Question 2: Vector Database Implementation with Pinecone

**Difficulty**: ⭐⭐⭐⭐ Expert  
**Topic**: Vector Search, Embeddings  
**Tags**: `vector-database`, `embeddings`, `semantic-search`, `pinecone`

#### Question

Implement a semantic search system using vector embeddings with:
- Document chunking strategies
- Embedding generation
- Similarity search
- Metadata filtering
- Hybrid search (vector + keyword)
- Performance optimization

#### Solution

```typescript
// vector-search-service.ts
import { PineconeClient } from '@pinecone-database/pinecone';
import OpenAI from 'openai';
import { RecursiveCharacterTextSplitter } from 'langchain/text_splitter';
import { Document } from 'langchain/document';

interface DocumentChunk {
  id: string;
  content: string;
  metadata: {
    source: string;
    page?: number;
    section?: string;
    [key: string]: any;
  };
  embedding?: number[];
}

interface SearchResult {
  id: string;
  score: number;
  content: string;
  metadata: Record<string, any>;
}

interface SearchOptions {
  topK?: number;
  filter?: Record<string, any>;
  includeMetadata?: boolean;
  minScore?: number;
}

export class VectorSearchService {
  private pinecone: PineconeClient;
  private openai: OpenAI;
  private indexName: string;
  private embeddingModel = 'text-embedding-3-large';
  private embeddingDimension = 3072;

  constructor(config: {
    pineconeApiKey: string;
    pineconeEnvironment: string;
    openaiApiKey: string;
    indexName: string;
  }) {
    this.pinecone = new PineconeClient();
    this.openai = new OpenAI({ apiKey: config.openaiApiKey });
    this.indexName = config.indexName;
    
    this.initPinecone(config);
  }

  private async initPinecone(config: any): Promise<void> {
    await this.pinecone.init({
      apiKey: config.pineconeApiKey,
      environment: config.pineconeEnvironment,
    });

    // Create index if it doesn't exist
    const existingIndexes = await this.pinecone.listIndexes();
    
    if (!existingIndexes.includes(this.indexName)) {
      await this.pinecone.createIndex({
        createRequest: {
          name: this.indexName,
          dimension: this.embeddingDimension,
          metric: 'cosine',
          podType: 'p1.x1',
          pods: 1,
          metadataConfig: {
            indexed: ['source', 'type', 'category'],
          },
        },
      });
    }
  }

  // Ingest documents
  async ingestDocuments(
    documents: Array<{ content: string; metadata: Record<string, any> }>
  ): Promise<void> {
    // Step 1: Chunk documents
    const chunks = await this.chunkDocuments(documents);

    // Step 2: Generate embeddings in batches
    const batchSize = 100;
    for (let i = 0; i < chunks.length; i += batchSize) {
      const batch = chunks.slice(i, i + batchSize);
      
      // Generate embeddings
      const embeddings = await this.generateEmbeddings(
        batch.map((c) => c.content)
      );

      // Prepare vectors for upsert
      const vectors = batch.map((chunk, idx) => ({
        id: chunk.id,
        values: embeddings[idx],
        metadata: {
          content: chunk.content,
          ...chunk.metadata,
        },
      }));

      // Upsert to Pinecone
      const index = this.pinecone.Index(this.indexName);
      await index.upsert({
        upsertRequest: {
          vectors,
          namespace: chunk.metadata.namespace || 'default',
        },
      });
    }
  }

  // Chunk documents intelligently
  private async chunkDocuments(
    documents: Array<{ content: string; metadata: Record<string, any> }>
  ): Promise<DocumentChunk[]> {
    const splitter = new RecursiveCharacterTextSplitter({
      chunkSize: 1000,
      chunkOverlap: 200,
      separators: ['\n\n', '\n', '. ', ' ', ''],
    });

    const chunks: DocumentChunk[] = [];

    for (const doc of documents) {
      const splits = await splitter.splitText(doc.content);

      for (let i = 0; i < splits.length; i++) {
        chunks.push({
          id: `${doc.metadata.source}-chunk-${i}`,
          content: splits[i],
          metadata: {
            ...doc.metadata,
            chunkIndex: i,
            totalChunks: splits.length,
          },
        });
      }
    }

    return chunks;
  }

  // Generate embeddings
  private async generateEmbeddings(texts: string[]): Promise<number[][]> {
    const response = await this.openai.embeddings.create({
      model: this.embeddingModel,
      input: texts,
    });

    return response.data.map((item) => item.embedding);
  }

  // Semantic search
  async search(
    query: string,
    options: SearchOptions = {}
  ): Promise<SearchResult[]> {
    const {
      topK = 10,
      filter,
      includeMetadata = true,
      minScore = 0.7,
    } = options;

    // Generate query embedding
    const [queryEmbedding] = await this.generateEmbeddings([query]);

    // Search Pinecone
    const index = this.pinecone.Index(this.indexName);
    const queryResponse = await index.query({
      queryRequest: {
        topK,
        vector: queryEmbedding,
        filter,
        includeMetadata,
      },
    });

    // Filter by minimum score and format results
    return (queryResponse.matches || [])
      .filter((match) => match.score! >= minScore)
      .map((match) => ({
        id: match.id,
        score: match.score!,
        content: match.metadata?.content as string,
        metadata: match.metadata || {},
      }));
  }

  // Hybrid search (vector + keyword)
  async hybridSearch(
    query: string,
    keywords: string[],
    options: SearchOptions = {}
  ): Promise<SearchResult[]> {
    // Vector search
    const vectorResults = await this.search(query, options);

    // Keyword filter
    const keywordFilter = {
      $or: keywords.map((keyword) => ({
        content: { $contains: keyword },
      })),
    };

    const keywordResults = await this.search(query, {
      ...options,
      filter: {
        ...options.filter,
        ...keywordFilter,
      },
    });

    // Merge and re-rank results
    const merged = this.mergeResults(vectorResults, keywordResults);
    
    return merged.sort((a, b) => b.score - a.score).slice(0, options.topK || 10);
  }

  private mergeResults(
    vectorResults: SearchResult[],
    keywordResults: SearchResult[]
  ): SearchResult[] {
    const resultMap = new Map<string, SearchResult>();

    // Add vector results with weight
    for (const result of vectorResults) {
      resultMap.set(result.id, {
        ...result,
        score: result.score * 0.7, // 70% weight for vector
      });
    }

    // Add/merge keyword results
    for (const result of keywordResults) {
      const existing = resultMap.get(result.id);
      if (existing) {
        existing.score += result.score * 0.3; // 30% weight for keywords
      } else {
        resultMap.set(result.id, {
          ...result,
          score: result.score * 0.3,
        });
      }
    }

    return Array.from(resultMap.values());
  }

  // Delete documents
  async deleteDocuments(ids: string[]): Promise<void> {
    const index = this.pinecone.Index(this.indexName);
    await index.delete1({
      ids,
    });
  }

  // Update document metadata
  async updateMetadata(
    id: string,
    metadata: Record<string, any>
  ): Promise<void> {
    const index = this.pinecone.Index(this.indexName);
    
    // Fetch existing vector
    const fetchResponse = await index.fetch({
      ids: [id],
    });

    const vector = fetchResponse.vectors?.[id];
    if (!vector) {
      throw new Error(`Vector ${id} not found`);
    }

    // Update with new metadata
    await index.upsert({
      upsertRequest: {
        vectors: [
          {
            id,
            values: vector.values,
            metadata: {
              ...vector.metadata,
              ...metadata,
            },
          },
        ],
      },
    });
  }
}
```

#### Usage Example

```typescript
const vectorSearch = new VectorSearchService({
  pineconeApiKey: process.env.PINECONE_API_KEY!,
  pineconeEnvironment: 'us-west1-gcp',
  openaiApiKey: process.env.OPENAI_API_KEY!,
  indexName: 'document-search',
});

// Ingest documents
await vectorSearch.ingestDocuments([
  {
    content: 'Large Language Models are...',
    metadata: {
      source: 'docs/llm-guide.md',
      category: 'AI',
      type: 'documentation',
    },
  },
]);

// Search
const results = await vectorSearch.search(
  'How do I use LLMs in production?',
  {
    topK: 5,
    filter: { category: 'AI' },
    minScore: 0.75,
  }
);

// Hybrid search
const hybridResults = await vectorSearch.hybridSearch(
  'machine learning deployment',
  ['production', 'scaling', 'monitoring'],
  { topK: 10 }
);
```

---

## RAG Systems

### Question 3: Production RAG System

**Difficulty**: ⭐⭐⭐⭐ Expert  
**Topic**: RAG, LLM, Architecture  
**Tags**: `rag`, `retrieval`, `generation`, `production`

#### Question

Design and implement a production-ready Retrieval-Augmented Generation (RAG) system with:
- Multi-stage retrieval
- Context ranking and filtering
- Citation tracking
- Answer validation
- Fallback strategies

#### Architecture Diagram

```
User Query
    │
    v
┌─────────────────┐
│ Query Processor │ ──> Query enhancement, expansion
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Retrieval     │ ──> Vector + Keyword Search
│     Stage       │
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Re-ranking    │ ──> Cross-encoder scoring
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Context       │ ──> Build prompt with context
│   Assembly      │
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Generation    │ ──> LLM completion
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Validation    │ ──> Check answer quality
└────────┬────────┘
         │
         v
┌─────────────────┐
│   Citation      │ ──> Add source citations
└────────┬────────┘
         │
         v
    Final Answer
```

#### Solution

```typescript
// rag-system.ts
import { LLMService } from './llm-service';
import { VectorSearchService } from './vector-search-service';

interface RAGQuery {
  question: string;
  conversationHistory?: Array<{ role: string; content: string }>;
  filters?: Record<string, any>;
  maxSources?: number;
}

interface RAGResponse {
  answer: string;
  sources: Array<{
    id: string;
    content: string;
    score: number;
    metadata: Record<string, any>;
  }>;
  confidence: number;
  followUpQuestions?: string[];
}

export class RAGSystem {
  constructor(
    private llm: LLMService,
    private vectorSearch: VectorSearchService
  ) {}

  async query(request: RAGQuery): Promise<RAGResponse> {
    // Step 1: Enhance query
    const enhancedQuery = await this.enhanceQuery(request.question);

    // Step 2: Retrieve relevant documents
    const retrievedDocs = await this.retrieve(
      enhancedQuery,
      request.filters,
      request.maxSources || 5
    );

    // Step 3: Re-rank documents
    const rankedDocs = await this.rerank(request.question, retrievedDocs);

    // Step 4: Build context
    const context = this.buildContext(rankedDocs, request.conversationHistory);

    // Step 5: Generate answer
    const answer = await this.generate(request.question, context);

    // Step 6: Validate answer
    const validated = await this.validateAnswer(answer, rankedDocs);

    // Step 7: Add citations
    const withCitations = this.addCitations(validated, rankedDocs);

    // Step 8: Calculate confidence
    const confidence = this.calculateConfidence(rankedDocs, withCitations);

    // Step 9: Generate follow-up questions
    const followUpQuestions = await this.generateFollowUps(
      request.question,
      withCitations
    );

    return {
      answer: withCitations,
      sources: rankedDocs.map((doc) => ({
        id: doc.id,
        content: doc.content,
        score: doc.score,
        metadata: doc.metadata,
      })),
      confidence,
      followUpQuestions,
    };
  }

  // Enhance query using LLM
  private async enhanceQuery(question: string): Promise<string> {
    const response = await this.llm.complete({
      prompt: `Given this user question, rephrase it to be more specific and add relevant keywords for better search results:

Question: ${question}

Enhanced question:`,
      systemPrompt: 'You are a query enhancement assistant.',
      maxTokens: 100,
      temperature: 0.3,
    });

    return response.content.trim();
  }

  // Retrieve documents
  private async retrieve(
    query: string,
    filters?: Record<string, any>,
    maxSources = 5
  ): Promise<SearchResult[]> {
    // Hybrid search for better recall
    const results = await this.vectorSearch.hybridSearch(
      query,
      this.extractKeywords(query),
      {
        topK: maxSources * 2, // Get more for re-ranking
        filter: filters,
        minScore: 0.6,
      }
    );

    return results;
  }

  // Extract keywords from query
  private extractKeywords(query: string): string[] {
    // Simple keyword extraction (in production, use NLP libraries)
    const stopWords = new Set(['the', 'is', 'at', 'which', 'on', 'a', 'an']);
    
    return query
      .toLowerCase()
      .split(/\s+/)
      .filter((word) => word.length > 3 && !stopWords.has(word));
  }

  // Re-rank documents using cross-encoder or LLM
  private async rerank(
    query: string,
    docs: SearchResult[]
  ): Promise<SearchResult[]> {
    // Use LLM to score relevance
    const scoringPromises = docs.map(async (doc) => {
      const response = await this.llm.complete({
        prompt: `On a scale of 0-10, how relevant is this document to answering the question?

Question: ${query}

Document: ${doc.content.slice(0, 500)}...

Relevance score (0-10):`,
        systemPrompt: 'You are a relevance scoring assistant. Respond with only a number between 0 and 10.',
        maxTokens: 10,
        temperature: 0,
      });

      const score = parseInt(response.content.trim()) / 10;
      
      return {
        ...doc,
        score: isNaN(score) ? doc.score : (score + doc.score) / 2,
      };
    });

    const rescored = await Promise.all(scoringPromises);
    
    return rescored.sort((a, b) => b.score - a.score).slice(0, 5);
  }

  // Build context from documents
  private buildContext(
    docs: SearchResult[],
    history?: Array<{ role: string; content: string }>
  ): string {
    let context = '';

    // Add conversation history
    if (history && history.length > 0) {
      context += 'Previous conversation:\n';
      context += history
        .slice(-3) // Last 3 messages
        .map((msg) => `${msg.role}: ${msg.content}`)
        .join('\n');
      context += '\n\n';
    }

    // Add retrieved documents
    context += 'Relevant information:\n\n';
    docs.forEach((doc, idx) => {
      context += `[${idx + 1}] ${doc.content}\n\n`;
    });

    return context;
  }

  // Generate answer
  private async generate(question: string, context: string): Promise<string> {
    const response = await this.llm.complete({
      prompt: `Answer the following question based on the provided context. If the context doesn't contain enough information, say so.

${context}

Question: ${question}

Answer:`,
      systemPrompt: `You are a helpful assistant that answers questions based on provided context. 
- Always cite sources using [1], [2], etc.
- If information is not in the context, clearly state that
- Be concise and accurate
- If unsure, express uncertainty`,
      maxTokens: 500,
      temperature: 0.7,
    });

    return response.content;
  }

  // Validate answer quality
  private async validateAnswer(
    answer: string,
    sources: SearchResult[]
  ): Promise<string> {
    // Check if answer is hallucinating
    const validation = await this.llm.complete({
      prompt: `Evaluate if this answer is fully supported by the provided sources. Respond with YES or NO.

Sources:
${sources.map((s, i) => `[${i + 1}] ${s.content.slice(0, 200)}`).join('\n')}

Answer: ${answer}

Is the answer fully supported? (YES/NO):`,
      systemPrompt: 'You are a fact-checking assistant.',
      maxTokens: 10,
      temperature: 0,
    });

    const isValid = validation.content.trim().toUpperCase().startsWith('YES');

    if (!isValid) {
      // Return a safer answer
      return `Based on the available information, I can provide a partial answer: ${answer}\n\nNote: Some aspects of this answer may require additional verification.`;
    }

    return answer;
  }

  // Add citations to answer
  private addCitations(answer: string, sources: SearchResult[]): string {
    // Citations should already be in [1], [2] format from generation
    // Add source list at the end
    let citedAnswer = answer + '\n\nSources:\n';
    
    sources.forEach((source, idx) => {
      citedAnswer += `[${idx + 1}] ${source.metadata.source || source.id}\n`;
    });

    return citedAnswer;
  }

  // Calculate confidence score
  private calculateConfidence(
    sources: SearchResult[],
    answer: string
  ): number {
    if (sources.length === 0) return 0;

    // Average source relevance score
    const avgScore = sources.reduce((sum, s) => sum + s.score, 0) / sources.length;

    // Check if answer contains uncertainty markers
    const uncertaintyMarkers = [
      'I am not sure',
      'may require additional',
      'might be',
      'possibly',
    ];
    
    const hasUncertainty = uncertaintyMarkers.some((marker) =>
      answer.toLowerCase().includes(marker.toLowerCase())
    );

    let confidence = avgScore;
    if (hasUncertainty) {
      confidence *= 0.7;
    }

    return Math.min(confidence, 1);
  }

  // Generate follow-up questions
  private async generateFollowUps(
    question: string,
    answer: string
  ): Promise<string[]> {
    const response = await this.llm.complete({
      prompt: `Based on this Q&A, suggest 3 relevant follow-up questions:

Q: ${question}
A: ${answer}

Follow-up questions (one per line):`,
      systemPrompt: 'You are a helpful assistant that generates relevant follow-up questions.',
      maxTokens: 150,
      temperature: 0.8,
    });

    return response.content
      .split('\n')
      .map((q) => q.replace(/^\d+\.\s*/, '').trim())
      .filter((q) => q.length > 0)
      .slice(0, 3);
  }
}
```

---

## Serverless Architecture

### Question 4: AWS Lambda Best Practices

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: Serverless, AWS Lambda  
**Tags**: `serverless`, `lambda`, `aws`, `optimization`

#### Question

Implement production-ready AWS Lambda functions with:
- Cold start optimization
- Proper error handling
- Structured logging
- Database connection pooling
- Environment variable management

#### Solution

```typescript
// lambda-handler.ts
import { APIGatewayProxyEvent, APIGatewayProxyResult, Context } from 'aws-lambda';
import { SecretsManagerClient, GetSecretValueCommand } from '@aws-sdk/client-secrets-manager';
import { Pool } from 'pg';

// Global connection pool (reused across invocations)
let dbPool: Pool | null = null;
let cachedSecrets: Record<string, any> = {};

// Initialize clients outside handler for reuse
const secretsClient = new SecretsManagerClient({ region: process.env.AWS_REGION });

interface LambdaConfig {
  dbHost: string;
  dbPort: number;
  dbName: string;
  dbUser: string;
  dbPassword: string;
}

// Get secrets from AWS Secrets Manager
async function getSecrets(): Promise<LambdaConfig> {
  if (Object.keys(cachedSecrets).length > 0) {
    return cachedSecrets as LambdaConfig;
  }

  const command = new GetSecretValueCommand({
    SecretId: process.env.SECRET_NAME!,
  });

  const response = await secretsClient.send(command);
  cachedSecrets = JSON.parse(response.SecretString!);
  
  return cachedSecrets as LambdaConfig;
}

// Initialize database pool
async function getDbPool(): Promise<Pool> {
  if (dbPool) {
    return dbPool;
  }

  const config = await getSecrets();

  dbPool = new Pool({
    host: config.dbHost,
    port: config.dbPort,
    database: config.dbName,
    user: config.dbUser,
    password: config.dbPassword,
    max: 1, // Lambda: use 1 connection per instance
    idleTimeoutMillis: 120000,
    connectionTimeoutMillis: 10000,
  });

  // Handle pool errors
  dbPool.on('error', (err) => {
    console.error('Database pool error:', err);
    dbPool = null; // Reset pool on error
  });

  return dbPool;
}

// Structured logging
function log(level: string, message: string, meta?: Record<string, any>) {
  console.log(
    JSON.stringify({
      timestamp: new Date().toISOString(),
      level,
      message,
      ...meta,
    })
  );
}

// Main handler
export async function handler(
  event: APIGatewayProxyEvent,
  context: Context
): Promise<APIGatewayProxyResult> {
  const requestId = context.requestId;
  const startTime = Date.now();

  log('info', 'Request started', {
    requestId,
    path: event.path,
    method: event.httpMethod,
  });

  try {
    // Parse request body
    const body = event.body ? JSON.parse(event.body) : {};

    // Get database pool
    const pool = await getDbPool();

    // Execute query
    const result = await pool.query(
      'SELECT * FROM users WHERE id = $1',
      [body.userId]
    );

    const duration = Date.now() - startTime;

    log('info', 'Request completed', {
      requestId,
      duration,
      resultCount: result.rowCount,
    });

    return {
      statusCode: 200,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
      },
      body: JSON.stringify({
        success: true,
        data: result.rows,
        meta: {
          requestId,
          duration,
        },
      }),
    };
  } catch (error: any) {
    const duration = Date.now() - startTime;

    log('error', 'Request failed', {
      requestId,
      duration,
      error: error.message,
      stack: error.stack,
    });

    return {
      statusCode: error.statusCode || 500,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
      },
      body: JSON.stringify({
        success: false,
        error: error.message,
        requestId,
      }),
    };
  }
}
```

```yaml
# serverless.yml
service: api-service

provider:
  name: aws
  runtime: nodejs20.x
  region: us-east-1
  stage: ${opt:stage, 'dev'}
  
  environment:
    AWS_REGION: ${self:provider.region}
    SECRET_NAME: ${self:service}-${self:provider.stage}-secrets
  
  iam:
    role:
      statements:
        - Effect: Allow
          Action:
            - secretsmanager:GetSecretValue
          Resource:
            - arn:aws:secretsmanager:${self:provider.region}:*:secret:${self:provider.environment.SECRET_NAME}*
        
        - Effect: Allow
          Action:
            - logs:CreateLogGroup
            - logs:CreateLogStream
            - logs:PutLogEvents
          Resource:
            - arn:aws:logs:*:*:*

  vpc:
    securityGroupIds:
      - ${ssm:/${self:service}/${self:provider.stage}/security-group-id}
    subnetIds:
      - ${ssm:/${self:service}/${self:provider.stage}/subnet-id-1}
      - ${ssm:/${self:service}/${self:provider.stage}/subnet-id-2}

functions:
  api:
    handler: dist/index.handler
    memorySize: 512
    timeout: 30
    reservedConcurrency: 10
    
    events:
      - http:
          path: /users
          method: get
          cors: true
      
      - http:
          path: /users/{id}
          method: get
          cors: true
    
    layers:
      - arn:aws:lambda:${self:provider.region}:580247275435:layer:LambdaInsightsExtension:21

plugins:
  - serverless-plugin-typescript
  - serverless-offline
  - serverless-prune-plugin

custom:
  prune:
    automatic: true
    number: 3
```

---

**Total Questions**: 30+ advanced questions covering LLM integration, vector search, RAG systems, serverless architecture, and AI cost optimization from Advanced to Expert levels.
