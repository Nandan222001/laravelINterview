# RAG (Retrieval-Augmented Generation) Pipeline Implementation

## 1. Complete RAG System with Pinecone

```python
import openai
import pinecone
from typing import List, Dict
import os
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain.document_loaders import PyPDFLoader, TextLoader

# Configuration
openai.api_key = os.environ['OPENAI_API_KEY']
pinecone.init(
    api_key=os.environ['PINECONE_API_KEY'],
    environment=os.environ['PINECONE_ENVIRONMENT']
)

class RAGPipeline:
    """
    Complete RAG pipeline for document Q&A
    Supports document ingestion, embedding, retrieval, and generation
    """
    
    def __init__(self, index_name: str, embedding_model: str = "text-embedding-ada-002"):
        self.index_name = index_name
        self.embedding_model = embedding_model
        self.chunk_size = 1000
        self.chunk_overlap = 200
        
        # Initialize or connect to Pinecone index
        if index_name not in pinecone.list_indexes():
            pinecone.create_index(
                name=index_name,
                dimension=1536,  # ada-002 embedding dimension
                metric='cosine'
            )
        
        self.index = pinecone.Index(index_name)
        
        # Text splitter for chunking
        self.text_splitter = RecursiveCharacterTextSplitter(
            chunk_size=self.chunk_size,
            chunk_overlap=self.chunk_overlap,
            separators=["\n\n", "\n", ". ", " ", ""]
        )
    
    def ingest_documents(self, file_paths: List[str]):
        """
        Ingest documents from file paths
        Supports PDF and text files
        """
        all_chunks = []
        
        for file_path in file_paths:
            print(f"Processing: {file_path}")
            
            # Load document
            if file_path.endswith('.pdf'):
                loader = PyPDFLoader(file_path)
            else:
                loader = TextLoader(file_path)
            
            documents = loader.load()
            
            # Split into chunks
            chunks = self.text_splitter.split_documents(documents)
            
            for i, chunk in enumerate(chunks):
                all_chunks.append({
                    'id': f"{file_path}_{i}",
                    'text': chunk.page_content,
                    'metadata': {
                        'source': file_path,
                        'chunk_index': i,
                        **chunk.metadata
                    }
                })
        
        # Generate embeddings and upsert to Pinecone
        self._embed_and_upsert(all_chunks)
        
        print(f"Ingested {len(all_chunks)} chunks from {len(file_paths)} documents")
    
    def _embed_and_upsert(self, chunks: List[Dict], batch_size: int = 100):
        """
        Generate embeddings and upsert to Pinecone in batches
        """
        for i in range(0, len(chunks), batch_size):
            batch = chunks[i:i + batch_size]
            
            # Generate embeddings
            texts = [chunk['text'] for chunk in batch]
            embeddings = self._generate_embeddings(texts)
            
            # Prepare vectors for Pinecone
            vectors = []
            for chunk, embedding in zip(batch, embeddings):
                vectors.append({
                    'id': chunk['id'],
                    'values': embedding,
                    'metadata': {
                        'text': chunk['text'],
                        **chunk['metadata']
                    }
                })
            
            # Upsert to Pinecone
            self.index.upsert(vectors=vectors)
    
    def _generate_embeddings(self, texts: List[str]) -> List[List[float]]:
        """Generate embeddings using OpenAI API"""
        response = openai.Embedding.create(
            input=texts,
            model=self.embedding_model
        )
        return [item['embedding'] for item in response['data']]
    
    def retrieve(self, query: str, top_k: int = 5, filters: Dict = None) -> List[Dict]:
        """
        Retrieve relevant documents for a query
        
        Args:
            query: Search query
            top_k: Number of results to return
            filters: Optional metadata filters
        """
        # Generate query embedding
        query_embedding = self._generate_embeddings([query])[0]
        
        # Search Pinecone
        results = self.index.query(
            vector=query_embedding,
            top_k=top_k,
            include_metadata=True,
            filter=filters
        )
        
        # Format results
        documents = []
        for match in results['matches']:
            documents.append({
                'text': match['metadata']['text'],
                'score': match['score'],
                'source': match['metadata'].get('source', 'unknown'),
                'metadata': match['metadata']
            })
        
        return documents
    
    def generate_answer(self, query: str, top_k: int = 5, 
                       model: str = "gpt-4-turbo-preview") -> Dict:
        """
        Generate answer using RAG
        
        Returns:
            Dict with answer, sources, and retrieved context
        """
        # Retrieve relevant documents
        documents = self.retrieve(query, top_k)
        
        if not documents:
            return {
                'answer': "I don't have enough information to answer this question.",
                'sources': [],
                'context': []
            }
        
        # Build context from retrieved documents
        context = "\n\n".join([
            f"Source {i+1} (Score: {doc['score']:.3f}):\n{doc['text']}"
            for i, doc in enumerate(documents)
        ])
        
        # Create prompt
        prompt = f"""Answer the following question based on the provided context. 
If the context doesn't contain enough information, say so.

Context:
{context}

Question: {query}

Answer:"""
        
        # Generate answer
        response = openai.ChatCompletion.create(
            model=model,
            messages=[
                {"role": "system", "content": "You are a helpful assistant that answers questions based on the provided context. Always cite your sources."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7,
            max_tokens=500
        )
        
        answer = response['choices'][0]['message']['content']
        
        # Extract unique sources
        sources = list(set([doc['source'] for doc in documents]))
        
        return {
            'answer': answer,
            'sources': sources,
            'context': documents,
            'model': model
        }
    
    def query(self, question: str, top_k: int = 5) -> str:
        """
        Convenience method for simple Q&A
        Returns just the answer string
        """
        result = self.generate_answer(question, top_k)
        return result['answer']


# Example usage
if __name__ == '__main__':
    # Initialize RAG pipeline
    rag = RAGPipeline(index_name='banking-documents')
    
    # Ingest documents
    documents = [
        'docs/loan_policy.pdf',
        'docs/credit_guidelines.pdf',
        'docs/regulatory_compliance.pdf'
    ]
    rag.ingest_documents(documents)
    
    # Query the system
    question = "What are the eligibility criteria for a personal loan?"
    result = rag.generate_answer(question)
    
    print(f"Question: {question}")
    print(f"\nAnswer: {result['answer']}")
    print(f"\nSources: {', '.join(result['sources'])}")
```

## 2. Advanced RAG with Reranking

```python
from sentence_transformers import CrossEncoder
import numpy as np

class AdvancedRAGPipeline(RAGPipeline):
    """
    Enhanced RAG with two-stage retrieval and reranking
    """
    
    def __init__(self, index_name: str, embedding_model: str = "text-embedding-ada-002"):
        super().__init__(index_name, embedding_model)
        
        # Load cross-encoder for reranking
        self.reranker = CrossEncoder('cross-encoder/ms-marco-MiniLM-L-6-v2')
    
    def retrieve_with_reranking(self, query: str, initial_k: int = 20, 
                               final_k: int = 5, filters: Dict = None) -> List[Dict]:
        """
        Two-stage retrieval with reranking
        
        1. Retrieve initial_k candidates using vector similarity
        2. Rerank using cross-encoder
        3. Return top final_k results
        """
        # Stage 1: Initial retrieval
        candidates = self.retrieve(query, top_k=initial_k, filters=filters)
        
        if not candidates:
            return []
        
        # Stage 2: Reranking with cross-encoder
        pairs = [[query, doc['text']] for doc in candidates]
        rerank_scores = self.reranker.predict(pairs)
        
        # Combine scores (weighted average)
        for i, doc in enumerate(candidates):
            doc['retrieval_score'] = doc['score']
            doc['rerank_score'] = float(rerank_scores[i])
            doc['combined_score'] = 0.5 * doc['retrieval_score'] + 0.5 * doc['rerank_score']
        
        # Sort by combined score
        candidates.sort(key=lambda x: x['combined_score'], reverse=True)
        
        return candidates[:final_k]
    
    def generate_answer_with_reranking(self, query: str, initial_k: int = 20,
                                      final_k: int = 5, model: str = "gpt-4-turbo-preview") -> Dict:
        """Generate answer using reranked retrieval"""
        
        # Retrieve and rerank
        documents = self.retrieve_with_reranking(query, initial_k, final_k)
        
        if not documents:
            return {
                'answer': "I don't have enough information to answer this question.",
                'sources': [],
                'context': []
            }
        
        # Build context with scores
        context = "\n\n".join([
            f"[Relevance: {doc['combined_score']:.3f}] {doc['text']}"
            for doc in documents
        ])
        
        # Generate answer
        prompt = f"""Answer the question based on the provided context. 
Cite specific sources when making claims.

Context:
{context}

Question: {query}

Answer:"""
        
        response = openai.ChatCompletion.create(
            model=model,
            messages=[
                {"role": "system", "content": "You are a helpful assistant. Answer based on the context and cite sources."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7
        )
        
        return {
            'answer': response['choices'][0]['message']['content'],
            'sources': list(set([doc['source'] for doc in documents])),
            'context': documents
        }
```

## 3. RAG with Metadata Filtering

```python
class FilteredRAGPipeline(RAGPipeline):
    """RAG with advanced metadata filtering for multi-tenant systems"""
    
    def ingest_document_with_metadata(self, file_path: str, metadata: Dict):
        """
        Ingest document with custom metadata
        Useful for access control and filtering
        """
        # Load and chunk document
        if file_path.endswith('.pdf'):
            loader = PyPDFLoader(file_path)
        else:
            loader = TextLoader(file_path)
        
        documents = loader.load()
        chunks = self.text_splitter.split_documents(documents)
        
        # Add custom metadata to all chunks
        enriched_chunks = []
        for i, chunk in enumerate(chunks):
            enriched_chunks.append({
                'id': f"{file_path}_{i}",
                'text': chunk.page_content,
                'metadata': {
                    'source': file_path,
                    'chunk_index': i,
                    **metadata,  # Add custom metadata
                    **chunk.metadata
                }
            })
        
        self._embed_and_upsert(enriched_chunks)
    
    def query_with_access_control(self, query: str, user_id: str, 
                                  organization_id: str, top_k: int = 5) -> Dict:
        """
        Query with access control based on user and organization
        """
        # Build metadata filter
        filters = {
            "$or": [
                {"organization_id": {"$eq": organization_id}},
                {"visibility": {"$eq": "public"}}
            ]
        }
        
        # Retrieve with filters
        documents = self.retrieve(query, top_k, filters)
        
        # Generate answer
        return self.generate_answer_from_docs(query, documents)
    
    def query_by_document_type(self, query: str, doc_type: str, 
                              date_range: Dict = None, top_k: int = 5) -> Dict:
        """
        Query filtered by document type and optional date range
        """
        filters = {"document_type": {"$eq": doc_type}}
        
        if date_range:
            filters["date"] = {
                "$gte": date_range.get('start'),
                "$lte": date_range.get('end')
            }
        
        documents = self.retrieve(query, top_k, filters)
        return self.generate_answer_from_docs(query, documents)
    
    def generate_answer_from_docs(self, query: str, documents: List[Dict],
                                  model: str = "gpt-4-turbo-preview") -> Dict:
        """Generate answer from pre-filtered documents"""
        
        if not documents:
            return {
                'answer': "No relevant documents found.",
                'sources': [],
                'context': []
            }
        
        context = "\n\n".join([doc['text'] for doc in documents])
        
        prompt = f"""Based on the context below, answer the question.

Context:
{context}

Question: {query}

Answer:"""
        
        response = openai.ChatCompletion.create(
            model=model,
            messages=[
                {"role": "system", "content": "Answer questions based on provided context."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7
        )
        
        return {
            'answer': response['choices'][0]['message']['content'],
            'sources': list(set([doc['source'] for doc in documents])),
            'context': documents
        }


# Example usage with metadata
if __name__ == '__main__':
    rag = FilteredRAGPipeline(index_name='banking-docs')
    
    # Ingest with metadata
    rag.ingest_document_with_metadata(
        file_path='loan_policy.pdf',
        metadata={
            'organization_id': 'org_123',
            'document_type': 'policy',
            'department': 'lending',
            'visibility': 'internal',
            'version': '2.1',
            'effective_date': '2024-01-01'
        }
    )
    
    # Query with access control
    result = rag.query_with_access_control(
        query="What is the maximum loan amount?",
        user_id="user_456",
        organization_id="org_123"
    )
    
    print(result['answer'])
```

## 4. Conversational RAG with Memory

```python
from typing import List, Tuple

class ConversationalRAG(RAGPipeline):
    """
    RAG with conversation history for multi-turn interactions
    """
    
    def __init__(self, index_name: str, embedding_model: str = "text-embedding-ada-002"):
        super().__init__(index_name, embedding_model)
        self.conversation_history: List[Tuple[str, str]] = []
        self.max_history = 5
    
    def query_with_history(self, query: str, top_k: int = 5,
                          model: str = "gpt-4-turbo-preview") -> Dict:
        """
        Answer query considering conversation history
        """
        # Retrieve relevant documents
        documents = self.retrieve(query, top_k)
        
        if not documents:
            answer = "I don't have enough information to answer this question."
            self.conversation_history.append((query, answer))
            return {'answer': answer, 'sources': [], 'context': []}
        
        # Build context from documents
        context = "\n\n".join([doc['text'] for doc in documents])
        
        # Build conversation history
        history = "\n".join([
            f"Human: {q}\nAssistant: {a}"
            for q, a in self.conversation_history[-self.max_history:]
        ])
        
        # Create prompt with history
        prompt = f"""You are a helpful assistant. Answer the question based on the context and conversation history.

Conversation History:
{history}

Context:
{context}

Current Question: {query}

Answer:"""
        
        response = openai.ChatCompletion.create(
            model=model,
            messages=[
                {"role": "system", "content": "You are a helpful assistant with memory of the conversation."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.7
        )
        
        answer = response['choices'][0]['message']['content']
        
        # Update history
        self.conversation_history.append((query, answer))
        
        return {
            'answer': answer,
            'sources': list(set([doc['source'] for doc in documents])),
            'context': documents
        }
    
    def reset_conversation(self):
        """Clear conversation history"""
        self.conversation_history = []
```

## 5. RAG Evaluation

```python
from typing import List
import json

class RAGEvaluator:
    """
    Evaluate RAG system performance
    """
    
    def __init__(self, rag_pipeline: RAGPipeline):
        self.rag = rag_pipeline
    
    def evaluate_retrieval(self, test_cases: List[Dict]) -> Dict:
        """
        Evaluate retrieval quality
        
        test_cases format:
        [
            {
                'query': 'What is...',
                'relevant_docs': ['doc1.pdf', 'doc2.pdf']
            }
        ]
        """
        total_precision = 0
        total_recall = 0
        total_mrr = 0
        
        for case in test_cases:
            query = case['query']
            relevant_docs = set(case['relevant_docs'])
            
            # Retrieve documents
            retrieved = self.rag.retrieve(query, top_k=10)
            retrieved_sources = [doc['source'] for doc in retrieved]
            
            # Calculate metrics
            retrieved_relevant = set(retrieved_sources) & relevant_docs
            
            # Precision: relevant retrieved / total retrieved
            precision = len(retrieved_relevant) / len(retrieved_sources) if retrieved_sources else 0
            
            # Recall: relevant retrieved / total relevant
            recall = len(retrieved_relevant) / len(relevant_docs) if relevant_docs else 0
            
            # MRR: Mean Reciprocal Rank
            mrr = 0
            for i, source in enumerate(retrieved_sources):
                if source in relevant_docs:
                    mrr = 1 / (i + 1)
                    break
            
            total_precision += precision
            total_recall += recall
            total_mrr += mrr
        
        n = len(test_cases)
        return {
            'average_precision': total_precision / n,
            'average_recall': total_recall / n,
            'mean_reciprocal_rank': total_mrr / n,
            'num_test_cases': n
        }
    
    def evaluate_answer_quality(self, test_cases: List[Dict], 
                               model: str = "gpt-4-turbo-preview") -> Dict:
        """
        Evaluate answer quality using LLM as judge
        
        test_cases format:
        [
            {
                'query': 'What is...',
                'expected_answer': 'The answer is...',
                'context': ['relevant context']
            }
        ]
        """
        scores = []
        
        for case in test_cases:
            query = case['query']
            expected = case['expected_answer']
            
            # Generate answer
            result = self.rag.generate_answer(query)
            actual = result['answer']
            
            # Use LLM to evaluate
            eval_prompt = f"""Evaluate the quality of the answer on a scale of 1-5:

Question: {query}

Expected Answer: {expected}

Actual Answer: {actual}

Score (1-5, where 5 is perfect):"""
            
            response = openai.ChatCompletion.create(
                model=model,
                messages=[
                    {"role": "system", "content": "You are an objective evaluator. Return only a number 1-5."},
                    {"role": "user", "content": eval_prompt}
                ],
                temperature=0
            )
            
            try:
                score = int(response['choices'][0]['message']['content'].strip())
                scores.append(score)
            except:
                scores.append(0)
        
        return {
            'average_score': sum(scores) / len(scores),
            'scores': scores,
            'num_test_cases': len(test_cases)
        }
```
