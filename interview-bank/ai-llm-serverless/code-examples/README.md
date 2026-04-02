# Code Examples for AI, LLMs & Serverless

This directory contains practical code examples referenced in the interview questions.

## Contents

1. **Lambda Examples** - AWS Lambda function implementations
2. **Transformer Implementations** - Attention mechanism and architecture code
3. **RAG Systems** - Complete RAG pipeline implementations
4. **LLM Integration** - OpenAI, Claude, LangChain examples
5. **Vector Database Operations** - Pinecone, Weaviate, pgvector examples
6. **Webhook Processing** - Razorpay/Stripe webhook handlers

## Files

- `lambda_examples.md` - Lambda function patterns and cold start optimization
- `transformer_implementation.md` - Transformer architecture components
- `rag_pipeline.md` - Complete RAG system implementation
- `llm_api_integration.md` - LLM API integration patterns
- `vector_database_examples.md` - Vector database operations
- `webhook_handlers.md` - Payment webhook processing

## Usage

Each file contains:
- Complete working code examples
- Detailed explanations
- Best practices
- Common pitfalls
- Production considerations

## Prerequisites

```bash
# Python dependencies
pip install boto3 openai anthropic langchain pinecone-client weaviate-client psycopg2-binary pgvector torch transformers

# Node.js dependencies
npm install @aws-sdk/client-lambda @aws-sdk/client-s3 openai @anthropic-ai/sdk stripe razorpay
```

## Running Examples

Refer to individual files for specific setup and execution instructions.
