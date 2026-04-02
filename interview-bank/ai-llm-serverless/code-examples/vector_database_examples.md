# Vector Database Examples (Pinecone, Weaviate, pgvector)

## 1. Pinecone Operations

```python
import pinecone
import openai
import os
from typing import List, Dict

# Initialize Pinecone
pinecone.init(
    api_key=os.environ['PINECONE_API_KEY'],
    environment=os.environ['PINECONE_ENVIRONMENT']
)

class PineconeVectorDB:
    """
    Pinecone vector database operations
    """
    
    def __init__(self, index_name: str, dimension: int = 1536):
        self.index_name = index_name
        self.dimension = dimension
        
        # Create index if it doesn't exist
        if index_name not in pinecone.list_indexes():
            pinecone.create_index(
                name=index_name,
                dimension=dimension,
                metric='cosine',
                pod_type='p1.x1'
            )
        
        self.index = pinecone.Index(index_name)
    
    def upsert_vectors(self, vectors: List[Dict]):
        """
        Upsert vectors to Pinecone
        
        Args:
            vectors: List of dicts with 'id', 'values', and 'metadata'
        """
        self.index.upsert(vectors=vectors)
    
    def upsert_with_embeddings(self, documents: List[Dict]):
        """
        Generate embeddings and upsert to Pinecone
        
        Args:
            documents: List of dicts with 'id', 'text', and 'metadata'
        """
        # Extract texts
        texts = [doc['text'] for doc in documents]
        
        # Generate embeddings
        response = openai.Embedding.create(
            input=texts,
            model="text-embedding-ada-002"
        )
        
        # Prepare vectors
        vectors = []
        for doc, embedding_data in zip(documents, response['data']):
            vectors.append({
                'id': doc['id'],
                'values': embedding_data['embedding'],
                'metadata': {
                    'text': doc['text'],
                    **doc.get('metadata', {})
                }
            })
        
        # Upsert to Pinecone
        self.index.upsert(vectors=vectors)
    
    def query(self, query_vector: List[float], top_k: int = 5, 
             filter: Dict = None, include_metadata: bool = True) -> Dict:
        """
        Query Pinecone index
        
        Args:
            query_vector: Query embedding
            top_k: Number of results
            filter: Metadata filters
            include_metadata: Whether to include metadata
        """
        return self.index.query(
            vector=query_vector,
            top_k=top_k,
            filter=filter,
            include_metadata=include_metadata
        )
    
    def query_by_text(self, query_text: str, top_k: int = 5, 
                     filter: Dict = None) -> List[Dict]:
        """
        Query using text (generates embedding automatically)
        """
        # Generate query embedding
        response = openai.Embedding.create(
            input=[query_text],
            model="text-embedding-ada-002"
        )
        query_vector = response['data'][0]['embedding']
        
        # Query Pinecone
        results = self.query(query_vector, top_k, filter)
        
        # Format results
        matches = []
        for match in results['matches']:
            matches.append({
                'id': match['id'],
                'score': match['score'],
                'text': match['metadata'].get('text', ''),
                'metadata': match['metadata']
            })
        
        return matches
    
    def delete(self, ids: List[str]):
        """Delete vectors by IDs"""
        self.index.delete(ids=ids)
    
    def delete_by_filter(self, filter: Dict):
        """Delete vectors matching filter"""
        self.index.delete(filter=filter)
    
    def fetch(self, ids: List[str]) -> Dict:
        """Fetch vectors by IDs"""
        return self.index.fetch(ids=ids)
    
    def describe_index_stats(self) -> Dict:
        """Get index statistics"""
        return self.index.describe_index_stats()
    
    def create_namespace(self, namespace: str):
        """
        Namespaces are automatically created when upserting
        This is just for documentation
        """
        pass
    
    def query_with_namespace(self, query_vector: List[float], 
                            namespace: str, top_k: int = 5) -> Dict:
        """Query specific namespace"""
        return self.index.query(
            vector=query_vector,
            top_k=top_k,
            namespace=namespace,
            include_metadata=True
        )


# Example usage
def example_pinecone_operations():
    """Pinecone operations example"""
    
    # Initialize
    db = PineconeVectorDB(index_name='banking-docs')
    
    # Upsert documents
    documents = [
        {
            'id': 'doc1',
            'text': 'Personal loan eligibility requires minimum 2 years employment.',
            'metadata': {'category': 'loans', 'doc_type': 'policy'}
        },
        {
            'id': 'doc2',
            'text': 'Home loan interest rates start from 8.5% per annum.',
            'metadata': {'category': 'loans', 'doc_type': 'rates'}
        }
    ]
    
    db.upsert_with_embeddings(documents)
    
    # Query
    results = db.query_by_text(
        query_text='What are the loan eligibility criteria?',
        top_k=3,
        filter={'category': {'$eq': 'loans'}}
    )
    
    for result in results:
        print(f"Score: {result['score']:.3f}")
        print(f"Text: {result['text']}")
        print()
    
    # Get stats
    stats = db.describe_index_stats()
    print(f"Total vectors: {stats['total_vector_count']}")
```

## 2. Weaviate Operations

```python
import weaviate
from weaviate.util import generate_uuid5
import openai
from typing import List, Dict

class WeaviateVectorDB:
    """
    Weaviate vector database operations
    """
    
    def __init__(self, url: str = "http://localhost:8080"):
        self.client = weaviate.Client(url=url)
    
    def create_schema(self, class_name: str, properties: List[Dict]):
        """
        Create Weaviate schema
        
        Example properties:
        [
            {'name': 'content', 'dataType': ['text']},
            {'name': 'category', 'dataType': ['string']},
            {'name': 'date', 'dataType': ['date']}
        ]
        """
        schema = {
            'class': class_name,
            'properties': properties,
            'vectorizer': 'text2vec-openai',
            'moduleConfig': {
                'text2vec-openai': {
                    'model': 'ada',
                    'modelVersion': '002',
                    'type': 'text'
                }
            }
        }
        
        # Delete if exists
        if self.client.schema.exists(class_name):
            self.client.schema.delete_class(class_name)
        
        self.client.schema.create_class(schema)
    
    def add_documents(self, class_name: str, documents: List[Dict]):
        """
        Add documents to Weaviate with automatic vectorization
        """
        with self.client.batch as batch:
            batch.batch_size = 100
            
            for doc in documents:
                # Generate UUID
                uuid = generate_uuid5(doc)
                
                # Add object
                batch.add_data_object(
                    data_object=doc,
                    class_name=class_name,
                    uuid=uuid
                )
    
    def semantic_search(self, class_name: str, query: str, 
                       limit: int = 5, properties: List[str] = None) -> List[Dict]:
        """
        Semantic search using nearText
        """
        if properties is None:
            properties = ['content']
        
        result = (
            self.client.query
            .get(class_name, properties)
            .with_near_text({'concepts': [query]})
            .with_limit(limit)
            .with_additional(['certainty', 'id'])
            .do()
        )
        
        return result['data']['Get'][class_name]
    
    def hybrid_search(self, class_name: str, query: str, 
                     alpha: float = 0.5, limit: int = 5) -> List[Dict]:
        """
        Hybrid search (combines BM25 and vector search)
        
        Args:
            alpha: 0 = BM25 only, 1 = vector only, 0.5 = balanced
        """
        result = (
            self.client.query
            .get(class_name, ['content', 'category'])
            .with_hybrid(query, alpha=alpha)
            .with_limit(limit)
            .with_additional(['score'])
            .do()
        )
        
        return result['data']['Get'][class_name]
    
    def filtered_search(self, class_name: str, query: str, 
                       where_filter: Dict, limit: int = 5) -> List[Dict]:
        """
        Search with metadata filtering
        
        Example filter:
        {
            'path': ['category'],
            'operator': 'Equal',
            'valueString': 'loans'
        }
        """
        result = (
            self.client.query
            .get(class_name, ['content'])
            .with_near_text({'concepts': [query]})
            .with_where(where_filter)
            .with_limit(limit)
            .with_additional(['certainty'])
            .do()
        )
        
        return result['data']['Get'][class_name]
    
    def generative_search(self, class_name: str, query: str, 
                         prompt: str, limit: int = 3) -> Dict:
        """
        Generative search (RAG with Weaviate)
        Uses Weaviate's generative module to combine search + generation
        """
        result = (
            self.client.query
            .get(class_name, ['content'])
            .with_near_text({'concepts': [query]})
            .with_generate(single_prompt=prompt)
            .with_limit(limit)
            .do()
        )
        
        return result['data']['Get'][class_name]
    
    def aggregate_search(self, class_name: str) -> Dict:
        """Get aggregate statistics"""
        result = (
            self.client.query
            .aggregate(class_name)
            .with_meta_count()
            .do()
        )
        
        return result['data']['Aggregate'][class_name]


# Example usage
def example_weaviate_operations():
    """Weaviate operations example"""
    
    # Initialize
    db = WeaviateVectorDB(url="http://localhost:8080")
    
    # Create schema
    properties = [
        {'name': 'content', 'dataType': ['text']},
        {'name': 'category', 'dataType': ['string']},
        {'name': 'documentType', 'dataType': ['string']},
        {'name': 'date', 'dataType': ['date']}
    ]
    
    db.create_schema('BankingDocument', properties)
    
    # Add documents
    documents = [
        {
            'content': 'Personal loan eligibility requires minimum 2 years employment.',
            'category': 'loans',
            'documentType': 'policy',
            'date': '2024-01-01T00:00:00Z'
        },
        {
            'content': 'Home loan interest rates start from 8.5% per annum.',
            'category': 'loans',
            'documentType': 'rates',
            'date': '2024-01-15T00:00:00Z'
        }
    ]
    
    db.add_documents('BankingDocument', documents)
    
    # Semantic search
    results = db.semantic_search(
        class_name='BankingDocument',
        query='loan eligibility requirements',
        limit=3,
        properties=['content', 'category']
    )
    
    for result in results:
        print(f"Certainty: {result['_additional']['certainty']:.3f}")
        print(f"Content: {result['content']}")
        print()
    
    # Hybrid search
    hybrid_results = db.hybrid_search(
        class_name='BankingDocument',
        query='loan rates',
        alpha=0.5,
        limit=3
    )
    
    print("Hybrid search results:")
    for result in hybrid_results:
        print(f"Score: {result['_additional']['score']}")
        print(f"Content: {result['content']}")
        print()
```

## 3. pgvector Operations

```python
import psycopg2
from psycopg2.extras import execute_values
import openai
from typing import List, Dict, Tuple
import numpy as np

class PgVectorDB:
    """
    PostgreSQL with pgvector extension operations
    """
    
    def __init__(self, connection_string: str):
        self.conn = psycopg2.connect(connection_string)
        self.cur = self.conn.cursor()
        
        # Enable pgvector extension
        self.cur.execute("CREATE EXTENSION IF NOT EXISTS vector")
        self.conn.commit()
    
    def create_table(self, table_name: str, vector_dim: int = 1536):
        """
        Create table with vector column
        """
        self.cur.execute(f"""
            CREATE TABLE IF NOT EXISTS {table_name} (
                id SERIAL PRIMARY KEY,
                content TEXT,
                embedding vector({vector_dim}),
                metadata JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        self.conn.commit()
    
    def create_ivfflat_index(self, table_name: str, lists: int = 100):
        """
        Create IVFFlat index for approximate nearest neighbor search
        
        Args:
            lists: Number of clusters (rule of thumb: rows / 1000)
        """
        self.cur.execute(f"""
            CREATE INDEX IF NOT EXISTS {table_name}_embedding_idx
            ON {table_name}
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = {lists})
        """)
        self.conn.commit()
    
    def create_hnsw_index(self, table_name: str, m: int = 16, ef_construction: int = 64):
        """
        Create HNSW index for approximate nearest neighbor search
        
        Args:
            m: Maximum number of connections per layer
            ef_construction: Size of dynamic candidate list
        """
        self.cur.execute(f"""
            CREATE INDEX IF NOT EXISTS {table_name}_embedding_hnsw_idx
            ON {table_name}
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = {m}, ef_construction = {ef_construction})
        """)
        self.conn.commit()
    
    def insert_documents(self, table_name: str, documents: List[Dict]):
        """
        Insert documents with embeddings
        
        Args:
            documents: List of dicts with 'content', 'embedding', 'metadata'
        """
        data = [
            (
                doc['content'],
                doc['embedding'],
                psycopg2.extras.Json(doc.get('metadata', {}))
            )
            for doc in documents
        ]
        
        execute_values(
            self.cur,
            f"""
            INSERT INTO {table_name} (content, embedding, metadata)
            VALUES %s
            """,
            data,
            template="(%s, %s, %s)"
        )
        
        self.conn.commit()
    
    def insert_with_embeddings(self, table_name: str, documents: List[Dict]):
        """
        Generate embeddings and insert documents
        """
        # Extract texts
        texts = [doc['content'] for doc in documents]
        
        # Generate embeddings
        response = openai.Embedding.create(
            input=texts,
            model="text-embedding-ada-002"
        )
        
        # Prepare documents with embeddings
        docs_with_embeddings = []
        for doc, embedding_data in zip(documents, response['data']):
            docs_with_embeddings.append({
                'content': doc['content'],
                'embedding': embedding_data['embedding'],
                'metadata': doc.get('metadata', {})
            })
        
        # Insert
        self.insert_documents(table_name, docs_with_embeddings)
    
    def similarity_search(self, table_name: str, query_embedding: List[float],
                         limit: int = 5, distance_metric: str = 'cosine') -> List[Tuple]:
        """
        Similarity search using vector distance
        
        Args:
            distance_metric: 'cosine', 'l2', or 'inner_product'
        """
        # Distance operators
        operators = {
            'cosine': '<=>',
            'l2': '<->',
            'inner_product': '<#>'
        }
        
        operator = operators.get(distance_metric, '<=>')
        
        self.cur.execute(f"""
            SELECT id, content, metadata, 
                   embedding {operator} %s::vector AS distance
            FROM {table_name}
            ORDER BY embedding {operator} %s::vector
            LIMIT %s
        """, (query_embedding, query_embedding, limit))
        
        return self.cur.fetchall()
    
    def search_by_text(self, table_name: str, query_text: str, 
                      limit: int = 5) -> List[Dict]:
        """
        Search using text query (generates embedding automatically)
        """
        # Generate query embedding
        response = openai.Embedding.create(
            input=[query_text],
            model="text-embedding-ada-002"
        )
        query_embedding = response['data'][0]['embedding']
        
        # Search
        results = self.similarity_search(table_name, query_embedding, limit)
        
        # Format results
        formatted = []
        for row in results:
            formatted.append({
                'id': row[0],
                'content': row[1],
                'metadata': row[2],
                'distance': float(row[3])
            })
        
        return formatted
    
    def hybrid_search(self, table_name: str, query_text: str, 
                     query_embedding: List[float], limit: int = 5) -> List[Dict]:
        """
        Hybrid search combining full-text search and vector similarity
        """
        self.cur.execute(f"""
            WITH vector_search AS (
                SELECT id, content, metadata,
                       embedding <=> %s::vector AS vector_distance,
                       1.0 / (1.0 + (embedding <=> %s::vector)) AS vector_score
                FROM {table_name}
                ORDER BY embedding <=> %s::vector
                LIMIT 20
            ),
            text_search AS (
                SELECT id, content, metadata,
                       ts_rank(to_tsvector('english', content), 
                              plainto_tsquery('english', %s)) AS text_score
                FROM {table_name}
                WHERE to_tsvector('english', content) @@ plainto_tsquery('english', %s)
                ORDER BY text_score DESC
                LIMIT 20
            )
            SELECT DISTINCT ON (v.id)
                   v.id, v.content, v.metadata,
                   v.vector_distance,
                   COALESCE(t.text_score, 0) AS text_score,
                   (v.vector_score * 0.5 + COALESCE(t.text_score, 0) * 0.5) AS combined_score
            FROM vector_search v
            LEFT JOIN text_search t ON v.id = t.id
            ORDER BY v.id, combined_score DESC
            LIMIT %s
        """, (query_embedding, query_embedding, query_embedding, 
              query_text, query_text, limit))
        
        results = self.cur.fetchall()
        
        formatted = []
        for row in results:
            formatted.append({
                'id': row[0],
                'content': row[1],
                'metadata': row[2],
                'vector_distance': float(row[3]),
                'text_score': float(row[4]),
                'combined_score': float(row[5])
            })
        
        return formatted
    
    def filtered_search(self, table_name: str, query_embedding: List[float],
                       metadata_filter: Dict, limit: int = 5) -> List[Dict]:
        """
        Vector search with metadata filtering
        """
        # Build WHERE clause from metadata filter
        where_conditions = []
        params = [query_embedding, query_embedding]
        
        for key, value in metadata_filter.items():
            where_conditions.append(f"metadata->>'{key}' = %s")
            params.append(value)
        
        where_clause = " AND ".join(where_conditions)
        params.append(limit)
        
        self.cur.execute(f"""
            SELECT id, content, metadata,
                   embedding <=> %s::vector AS distance
            FROM {table_name}
            WHERE {where_clause}
            ORDER BY embedding <=> %s::vector
            LIMIT %s
        """, params)
        
        results = self.cur.fetchall()
        
        formatted = []
        for row in results:
            formatted.append({
                'id': row[0],
                'content': row[1],
                'metadata': row[2],
                'distance': float(row[3])
            })
        
        return formatted
    
    def close(self):
        """Close database connection"""
        self.cur.close()
        self.conn.close()


# Example usage
def example_pgvector_operations():
    """pgvector operations example"""
    
    # Initialize
    db = PgVectorDB("postgresql://user:password@localhost/dbname")
    
    # Create table and index
    db.create_table('banking_documents', vector_dim=1536)
    db.create_hnsw_index('banking_documents')
    
    # Insert documents
    documents = [
        {
            'content': 'Personal loan eligibility requires minimum 2 years employment.',
            'metadata': {'category': 'loans', 'type': 'policy'}
        },
        {
            'content': 'Home loan interest rates start from 8.5% per annum.',
            'metadata': {'category': 'loans', 'type': 'rates'}
        }
    ]
    
    db.insert_with_embeddings('banking_documents', documents)
    
    # Search
    results = db.search_by_text(
        table_name='banking_documents',
        query_text='What are the loan requirements?',
        limit=3
    )
    
    for result in results:
        print(f"Distance: {result['distance']:.3f}")
        print(f"Content: {result['content']}")
        print(f"Metadata: {result['metadata']}")
        print()
    
    # Close connection
    db.close()
```
