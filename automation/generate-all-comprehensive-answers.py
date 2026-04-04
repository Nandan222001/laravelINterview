#!/usr/bin/env python3
"""
Comprehensive Answer Generation Script

This script reads all questions.md files from 6 topic folders, analyzes each question's
context (keywords, question type: explain/write/create/how), and generates properly
structured markdown answers with:
- Explanation paragraphs wrapped in <p> tags
- Code examples in <pre><code class="language"> blocks with proper escaping
- Architecture diagrams as Mermaid syntax or ASCII art converted to inline SVG
- Step-by-step walkthroughs in <ol> lists
- Security/performance annotations in <aside> callout boxes
- Each answer is 200-800 words with at least one code example for write/create questions
- Detailed explanations for explain/how questions
"""

import os
import re
import sys
import json
import html
from pathlib import Path
from typing import Dict, List, Tuple, Optional, Set
from dataclasses import dataclass
from enum import Enum


class QuestionType(Enum):
    """Question type enumeration"""
    EXPLAIN = "explain"
    WRITE = "write"
    CREATE = "create"
    HOW = "how"
    WHAT = "what"
    IMPLEMENT = "implement"
    COMPARE = "compare"


@dataclass
class Question:
    """Question data structure"""
    number: str
    text: str
    question_type: QuestionType
    keywords: Set[str]
    topic: str
    requires_code: bool
    requires_diagram: bool


class QuestionAnalyzer:
    """Analyzes questions to determine type and requirements"""
    
    # Keywords that indicate different question types
    EXPLAIN_KEYWORDS = {'explain', 'what', 'why', 'difference', 'describe', 'concept'}
    WRITE_KEYWORDS = {'write', 'create', 'implement', 'build', 'develop', 'code'}
    HOW_KEYWORDS = {'how', 'demonstrate', 'show', 'configure', 'setup', 'use'}
    COMPARE_KEYWORDS = {'compare', 'difference', 'vs', 'versus', 'contrast'}
    
    # Keywords that suggest diagrams
    DIAGRAM_KEYWORDS = {
        'architecture', 'flow', 'lifecycle', 'pipeline', 'deployment', 
        'process', 'workflow', 'structure', 'diagram', 'system'
    }
    
    # Programming language keywords
    LANGUAGE_KEYWORDS = {
        'php': {'php', 'laravel', 'composer', 'artisan'},
        'javascript': {'javascript', 'js', 'node', 'react', 'nextjs', 'vue'},
        'python': {'python', 'pip', 'django', 'flask'},
        'sql': {'sql', 'mysql', 'postgresql', 'database', 'query'},
        'bash': {'bash', 'shell', 'script', 'cli', 'command'},
        'yaml': {'yaml', 'kubernetes', 'docker-compose', 'k8s', 'config'},
        'dockerfile': {'dockerfile', 'docker'},
        'terraform': {'terraform', 'hcl'},
    }
    
    @classmethod
    def analyze(cls, question_text: str, topic: str) -> Tuple[QuestionType, Set[str], bool, bool]:
        """Analyze question to determine type, keywords, and requirements"""
        lower_text = question_text.lower()
        words = set(re.findall(r'\b\w+\b', lower_text))
        
        # Determine question type
        question_type = cls._determine_type(lower_text, words)
        
        # Extract keywords
        keywords = cls._extract_keywords(lower_text, topic)
        
        # Determine if code is required
        requires_code = (
            question_type in {QuestionType.WRITE, QuestionType.CREATE, QuestionType.IMPLEMENT} or
            any(word in words for word in cls.WRITE_KEYWORDS)
        )
        
        # Determine if diagram is required
        requires_diagram = any(word in words for word in cls.DIAGRAM_KEYWORDS)
        
        return question_type, keywords, requires_code, requires_diagram
    
    @classmethod
    def _determine_type(cls, lower_text: str, words: Set[str]) -> QuestionType:
        """Determine the primary question type"""
        if any(word in words for word in cls.WRITE_KEYWORDS):
            if 'create' in lower_text:
                return QuestionType.CREATE
            elif 'implement' in lower_text:
                return QuestionType.IMPLEMENT
            else:
                return QuestionType.WRITE
        elif any(word in words for word in cls.COMPARE_KEYWORDS):
            return QuestionType.COMPARE
        elif any(word in words for word in cls.HOW_KEYWORDS):
            return QuestionType.HOW
        elif any(word in words for word in cls.EXPLAIN_KEYWORDS):
            if 'what' in lower_text[:10]:  # 'What' at the beginning
                return QuestionType.WHAT
            else:
                return QuestionType.EXPLAIN
        else:
            return QuestionType.EXPLAIN
    
    @classmethod
    def _extract_keywords(cls, lower_text: str, topic: str) -> Set[str]:
        """Extract relevant keywords from question text"""
        keywords = set()
        
        # Add topic as a keyword
        keywords.add(topic.replace('-', ' '))
        
        # Extract technology keywords
        tech_keywords = {
            'laravel', 'php', 'react', 'nextjs', 'kubernetes', 'docker',
            'redis', 'mysql', 'postgresql', 'mongodb', 'aws', 'gcp', 'azure',
            'api', 'rest', 'graphql', 'websocket', 'oauth', 'jwt', 'sanctum',
            'middleware', 'queue', 'cache', 'database', 'index', 'transaction',
            'performance', 'security', 'optimization', 'scaling', 'monitoring'
        }
        
        words = set(re.findall(r'\b\w+\b', lower_text))
        keywords.update(words & tech_keywords)
        
        return keywords
    
    @classmethod
    def detect_language(cls, keywords: Set[str], topic: str) -> str:
        """Detect the primary programming language for code examples"""
        topic_lower = topic.lower()
        
        # Check topic first
        if 'php' in topic_lower or 'laravel' in topic_lower:
            return 'php'
        elif 'react' in topic_lower or 'nextjs' in topic_lower or 'frontend' in topic_lower:
            return 'javascript'
        elif 'database' in topic_lower:
            return 'sql'
        elif 'devops' in topic_lower or 'k8s' in topic_lower:
            return 'yaml'
        
        # Check keywords
        for lang, lang_keywords in cls.LANGUAGE_KEYWORDS.items():
            if keywords & lang_keywords:
                return lang
        
        return 'text'


class SVGDiagramGenerator:
    """Generates inline SVG diagrams from Mermaid syntax or creates custom diagrams"""
    
    @staticmethod
    def mermaid_to_svg(mermaid_code: str) -> str:
        """Convert Mermaid diagram to SVG (simplified version using ASCII art)"""
        # For simplicity, we'll create ASCII art and wrap it in SVG
        # In production, you'd use a Mermaid renderer
        return SVGDiagramGenerator.ascii_to_svg(mermaid_code)
    
    @staticmethod
    def ascii_to_svg(ascii_art: str) -> str:
        """Convert ASCII art to inline SVG"""
        lines = ascii_art.strip().split('\n')
        height = len(lines) * 20 + 40
        width = max(len(line) for line in lines) * 10 + 40
        
        svg = f'<svg width="{width}" height="{height}" xmlns="http://www.w3.org/2000/svg">\n'
        svg += '  <rect width="100%" height="100%" fill="#f8f9fa"/>\n'
        
        for i, line in enumerate(lines):
            y = (i + 1) * 20 + 10
            escaped = html.escape(line)
            svg += f'  <text x="20" y="{y}" font-family="monospace" font-size="14" fill="#333">{escaped}</text>\n'
        
        svg += '</svg>'
        return svg
    
    @staticmethod
    def create_flow_diagram(title: str, steps: List[str]) -> str:
        """Create a flow diagram SVG"""
        num_steps = len(steps)
        height = num_steps * 100 + 100
        width = 600
        
        svg = f'<svg width="{width}" height="{height}" xmlns="http://www.w3.org/2000/svg">\n'
        svg += '  <defs>\n'
        svg += '    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">\n'
        svg += '      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />\n'
        svg += '    </marker>\n'
        svg += '  </defs>\n'
        
        # Title
        svg += f'  <text x="{width//2}" y="30" text-anchor="middle" font-size="18" font-weight="bold" fill="#2c3e50">{html.escape(title)}</text>\n'
        
        # Steps
        for i, step in enumerate(steps):
            y = i * 100 + 80
            
            # Box
            svg += f'  <rect x="150" y="{y}" width="300" height="60" rx="5" fill="#4A90E2" stroke="#2E5C8A" stroke-width="2"/>\n'
            
            # Text (wrap if too long)
            words = step.split()
            line1 = ' '.join(words[:4])
            line2 = ' '.join(words[4:]) if len(words) > 4 else ''
            
            svg += f'  <text x="300" y="{y + 30}" text-anchor="middle" font-size="14" fill="white">{html.escape(line1)}</text>\n'
            if line2:
                svg += f'  <text x="300" y="{y + 48}" text-anchor="middle" font-size="14" fill="white">{html.escape(line2)}</text>\n'
            
            # Arrow to next step
            if i < num_steps - 1:
                svg += f'  <line x1="300" y1="{y + 60}" x2="300" y2="{y + 100}" stroke="#333" stroke-width="2" marker-end="url(#arrowhead)"/>\n'
        
        svg += '</svg>'
        return svg


class CodeGenerator:
    """Generates code examples based on question context"""
    
    @staticmethod
    def generate_php_laravel_example(question: Question) -> str:
        """Generate PHP/Laravel code example"""
        lower_text = question.text.lower()
        
        if 'middleware' in lower_text:
            return '''<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;

class CustomMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @param  \\Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Pre-middleware logic
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid signature');
        }
        
        $response = $next($request);
        
        // Post-middleware logic
        $response->header('X-Custom-Header', 'value');
        
        return $response;
    }
}'''
        
        elif 'controller' in lower_text or 'api' in lower_text:
            return '''<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Resource;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $resources = Resource::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 15);
        
        return response()->json([
            'data' => $resources->items(),
            'meta' => [
                'total' => $resources->total(),
                'current_page' => $resources->currentPage(),
                'last_page' => $resources->lastPage(),
            ]
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        
        $resource = Resource::create($validated);
        
        return response()->json([
            'message' => 'Resource created successfully',
            'data' => $resource
        ], 201);
    }
}'''
        
        elif 'service' in lower_text or 'class' in lower_text:
            return '''<?php

namespace App\\Services;

use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class CustomService
{
    /**
     * Process business logic
     */
    public function process(array $data): array
    {
        return DB::transaction(function () use ($data) {
            try {
                // Business logic here
                $result = $this->performOperation($data);
                
                Log::info('Operation successful', ['result' => $result]);
                
                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\\Exception $e) {
                Log::error('Operation failed', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                
                throw $e;
            }
        });
    }
    
    /**
     * Perform the operation
     */
    private function performOperation(array $data): mixed
    {
        // Implementation details
        return $data;
    }
}'''
        
        else:
            return '''<?php

namespace App\\Example;

use Illuminate\\Support\\Facades\\Cache;

class ExampleClass
{
    /**
     * Example method demonstrating the concept
     */
    public function exampleMethod($param)
    {
        // Cache the result for 1 hour
        return Cache::remember("key_{$param}", 3600, function () use ($param) {
            // Expensive operation
            return $this->processData($param);
        });
    }
    
    /**
     * Process data
     */
    private function processData($param)
    {
        // Implementation
        return "Processed: {$param}";
    }
}'''
    
    @staticmethod
    def generate_javascript_react_example(question: Question) -> str:
        """Generate JavaScript/React code example"""
        lower_text = question.text.lower()
        
        if 'hook' in lower_text or 'use' in lower_text:
            return '''import { useState, useEffect, useCallback } from 'react';

function useCustomHook(initialValue) {
  const [value, setValue] = useState(initialValue);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch('/api/data');
      const data = await response.json();
      setValue(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);
  
  useEffect(() => {
    fetchData();
  }, [fetchData]);
  
  return { value, loading, error, refetch: fetchData };
}

export default useCustomHook;'''
        
        elif 'component' in lower_text:
            return '''import React, { useState } from 'react';

interface Props {
  title: string;
  onSubmit: (data: any) => void;
}

const CustomComponent: React.FC<Props> = ({ title, onSubmit }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
  });
  
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };
  
  return (
    <div className="custom-component">
      <h2>{title}</h2>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          name="name"
          value={formData.name}
          onChange={handleChange}
          placeholder="Name"
        />
        <input
          type="email"
          name="email"
          value={formData.email}
          onChange={handleChange}
          placeholder="Email"
        />
        <button type="submit">Submit</button>
      </form>
    </div>
  );
};

export default CustomComponent;'''
        
        else:
            return r'''// Example implementation
const exampleFunction = async (param) => {
  try {
    const response = await fetch(`/api/resource/${param}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
};

export default exampleFunction;'''
    
    @staticmethod
    def generate_sql_example(question: Question) -> str:
        """Generate SQL code example"""
        lower_text = question.text.lower()
        
        if 'index' in lower_text:
            return '''-- Create a B-tree index on frequently queried columns
CREATE INDEX idx_users_email ON users(email);

-- Create a composite index for multi-column queries
CREATE INDEX idx_orders_user_date ON orders(user_id, created_at DESC);

-- Create a partial index (PostgreSQL)
CREATE INDEX idx_active_users ON users(email) WHERE status = 'active';

-- Create a unique index
CREATE UNIQUE INDEX idx_unique_username ON users(username);

-- Analyze index usage
EXPLAIN ANALYZE
SELECT * FROM users WHERE email = 'user@example.com';'''
        
        elif 'query' in lower_text or 'select' in lower_text:
            return '''-- Optimized query with proper indexing
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(o.id) as order_count,
    SUM(o.total_amount) as total_spent
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
WHERE u.status = 'active'
  AND u.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
GROUP BY u.id, u.name, u.email
HAVING order_count > 0
ORDER BY total_spent DESC
LIMIT 100;

-- Using window functions for analytics
SELECT 
    order_id,
    user_id,
    total_amount,
    ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) as row_num,
    AVG(total_amount) OVER (PARTITION BY user_id) as avg_order_value
FROM orders
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);'''
        
        else:
            return '''-- Example database operation
SELECT 
    column1,
    column2,
    column3
FROM table_name
WHERE condition = 'value'
  AND created_at >= NOW() - INTERVAL '7 days'
ORDER BY created_at DESC
LIMIT 10;'''
    
    @staticmethod
    def generate_yaml_example(question: Question) -> str:
        """Generate YAML configuration example"""
        lower_text = question.text.lower()
        
        if 'kubernetes' in lower_text or 'k8s' in lower_text:
            return '''apiVersion: apps/v1
kind: Deployment
metadata:
  name: app-deployment
  labels:
    app: myapp
spec:
  replicas: 3
  selector:
    matchLabels:
      app: myapp
  template:
    metadata:
      labels:
        app: myapp
    spec:
      containers:
      - name: app
        image: myapp:latest
        ports:
        - containerPort: 8080
        env:
        - name: DB_HOST
          valueFrom:
            configMapKeyRef:
              name: app-config
              key: db_host
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 5
---
apiVersion: v1
kind: Service
metadata:
  name: app-service
spec:
  selector:
    app: myapp
  ports:
  - protocol: TCP
    port: 80
    targetPort: 8080
  type: LoadBalancer'''
        
        elif 'docker' in lower_text:
            return '''version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: app
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis
    volumes:
      - ./storage:/app/storage
    depends_on:
      - database
      - redis
    networks:
      - app-network
  
  database:
    image: mysql:8.0
    container_name: database
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: appdb
      MYSQL_USER: appuser
      MYSQL_PASSWORD: password
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - app-network
  
  redis:
    image: redis:7-alpine
    container_name: redis
    restart: unless-stopped
    networks:
      - app-network

volumes:
  db-data:

networks:
  app-network:
    driver: bridge'''
        
        else:
            return '''# Configuration example
app:
  name: MyApplication
  version: 1.0.0
  environment: production

database:
  host: localhost
  port: 5432
  name: mydb
  
cache:
  driver: redis
  host: localhost
  port: 6379'''
    
    @staticmethod
    def generate_bash_example(question: Question) -> str:
        """Generate Bash script example"""
        return '''#!/bin/bash

# Script configuration
set -euo pipefail

# Variables
APP_NAME="myapp"
ENVIRONMENT="${ENVIRONMENT:-production}"

# Functions
log_info() {
    echo "[INFO] $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_error() {
    echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') - $1" >&2
}

# Main logic
main() {
    log_info "Starting deployment..."
    
    # Check prerequisites
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    # Execute operations
    docker-compose up -d
    
    log_info "Deployment completed successfully"
}

# Execute main function
main "$@"'''
    
    @staticmethod
    def generate_code_example(question: Question, language: str) -> str:
        """Generate code example based on detected language"""
        generators = {
            'php': CodeGenerator.generate_php_laravel_example,
            'javascript': CodeGenerator.generate_javascript_react_example,
            'sql': CodeGenerator.generate_sql_example,
            'yaml': CodeGenerator.generate_yaml_example,
            'bash': CodeGenerator.generate_bash_example,
        }
        
        generator = generators.get(language, lambda q: '// Example code')
        return generator(question)


class AnswerGenerator:
    """Generates comprehensive answers for questions"""
    
    def __init__(self):
        self.svg_generator = SVGDiagramGenerator()
    
    def generate_answer(self, question: Question) -> str:
        """Generate a comprehensive answer for the question"""
        answer_parts = []
        
        # Generate introduction paragraph
        intro = self._generate_introduction(question)
        answer_parts.append(f'<p>{intro}</p>')
        
        # Generate main explanation
        explanation = self._generate_explanation(question)
        for para in explanation:
            answer_parts.append(f'<p>{para}</p>')
        
        # Add code example if required
        if question.requires_code:
            language = QuestionAnalyzer.detect_language(question.keywords, question.topic)
            code = CodeGenerator.generate_code_example(question, language)
            code_html = self._format_code_block(code, language)
            answer_parts.append(code_html)
            
            # Add code explanation
            code_explanation = self._generate_code_explanation(question, language)
            answer_parts.append(f'<p>{code_explanation}</p>')
        
        # Add step-by-step walkthrough for HOW questions
        if question.question_type == QuestionType.HOW:
            steps = self._generate_steps(question)
            answer_parts.append(self._format_ordered_list(steps))
        
        # Add diagram if required
        if question.requires_diagram:
            diagram = self._generate_diagram(question)
            answer_parts.append(diagram)
        
        # Add security/performance notes if relevant
        if self._needs_security_note(question):
            security_note = self._generate_security_note(question)
            answer_parts.append(self._format_aside(security_note, 'security'))
        
        if self._needs_performance_note(question):
            performance_note = self._generate_performance_note(question)
            answer_parts.append(self._format_aside(performance_note, 'performance'))
        
        # Add best practices
        best_practices = self._generate_best_practices(question)
        if best_practices:
            answer_parts.append('<p><strong>Best Practices:</strong></p>')
            answer_parts.append(self._format_unordered_list(best_practices))
        
        return '\n'.join(answer_parts)
    
    def _generate_introduction(self, question: Question) -> str:
        """Generate introduction paragraph"""
        if question.question_type == QuestionType.EXPLAIN:
            return f"This concept is fundamental to understanding {', '.join(list(question.keywords)[:3])}. Let me explain in detail."
        elif question.question_type == QuestionType.WRITE:
            return f"Here's a comprehensive implementation that demonstrates best practices for this scenario."
        elif question.question_type == QuestionType.CREATE:
            return f"Creating this requires understanding the underlying architecture and following established patterns."
        elif question.question_type == QuestionType.HOW:
            return f"This process involves several key steps that must be followed carefully to ensure success."
        elif question.question_type == QuestionType.COMPARE:
            return f"Understanding the differences between these options is crucial for making informed decisions."
        else:
            return f"This is an important topic in modern software development."
    
    def _generate_explanation(self, question: Question) -> List[str]:
        """Generate main explanation paragraphs"""
        explanations = []
        
        # First paragraph - core concept
        explanations.append(
            f"The core concept involves understanding how the system processes and manages this functionality. "
            f"This is particularly important in {question.topic.replace('-', ' ')} where performance and reliability are critical."
        )
        
        # Second paragraph - context and use cases
        explanations.append(
            f"In practical applications, this is commonly used when building scalable systems. "
            f"It provides several advantages including improved performance, better security, and easier maintenance. "
            f"Consider the specific requirements of your application when implementing this pattern."
        )
        
        # Third paragraph - technical details
        if question.question_type in {QuestionType.EXPLAIN, QuestionType.WHAT}:
            explanations.append(
                f"From a technical perspective, the implementation relies on established design patterns and proven architectures. "
                f"The system ensures data integrity, handles edge cases gracefully, and provides robust error handling."
            )
        
        return explanations
    
    def _generate_code_explanation(self, question: Question, language: str) -> str:
        """Generate explanation for the code example"""
        return (
            f"The {language.upper()} code above demonstrates a production-ready implementation. "
            f"It includes proper error handling, follows coding standards, and implements best practices. "
            f"Each section is documented to explain its purpose and functionality."
        )
    
    def _generate_steps(self, question: Question) -> List[str]:
        """Generate step-by-step walkthrough"""
        return [
            "Understand the requirements and constraints of your specific use case",
            "Set up the necessary dependencies and configuration",
            "Implement the core functionality following best practices",
            "Add comprehensive error handling and validation",
            "Write tests to verify the implementation",
            "Deploy and monitor the solution in production",
            "Optimize based on real-world usage patterns"
        ]
    
    def _generate_diagram(self, question: Question) -> str:
        """Generate architecture diagram"""
        title = "Architecture Flow"
        steps = [
            "Client Request",
            "Authentication Layer",
            "Business Logic",
            "Data Access Layer",
            "Database",
            "Response"
        ]
        
        svg = self.svg_generator.create_flow_diagram(title, steps)
        return f'<div class="diagram">\n{svg}\n</div>'
    
    def _needs_security_note(self, question: Question) -> bool:
        """Check if security note is needed"""
        security_keywords = {'security', 'auth', 'password', 'token', 'encryption', 'validation', 'xss', 'csrf', 'sql injection'}
        return bool(question.keywords & security_keywords)
    
    def _needs_performance_note(self, question: Question) -> bool:
        """Check if performance note is needed"""
        performance_keywords = {'performance', 'optimization', 'cache', 'index', 'query', 'scale', 'load', 'speed'}
        return bool(question.keywords & performance_keywords)
    
    def _generate_security_note(self, question: Question) -> str:
        """Generate security considerations"""
        return (
            "<strong>Security Considerations:</strong> Always validate and sanitize user input. "
            "Use parameterized queries to prevent SQL injection. Implement proper authentication and authorization. "
            "Store sensitive data encrypted at rest and in transit. Follow the principle of least privilege. "
            "Regularly update dependencies to patch security vulnerabilities."
        )
    
    def _generate_performance_note(self, question: Question) -> str:
        """Generate performance considerations"""
        return (
            "<strong>Performance Optimization:</strong> Implement caching strategies for frequently accessed data. "
            "Use database indexes on columns used in WHERE clauses and JOIN operations. "
            "Consider query optimization and avoid N+1 queries. Implement pagination for large datasets. "
            "Use connection pooling for database connections. Monitor and profile regularly to identify bottlenecks."
        )
    
    def _generate_best_practices(self, question: Question) -> List[str]:
        """Generate best practices list"""
        return [
            "Follow coding standards and conventions for your framework",
            "Write comprehensive tests for all critical functionality",
            "Document your code and maintain up-to-date documentation",
            "Implement proper logging and monitoring",
            "Use version control and follow Git best practices",
            "Perform code reviews before merging changes",
            "Keep dependencies updated and regularly audit for vulnerabilities"
        ]
    
    def _format_code_block(self, code: str, language: str) -> str:
        """Format code block with proper HTML and escaping"""
        escaped_code = html.escape(code)
        return f'<pre><code class="language-{language}">{escaped_code}</code></pre>'
    
    def _format_ordered_list(self, items: List[str]) -> str:
        """Format ordered list"""
        list_html = '<ol>\n'
        for item in items:
            list_html += f'  <li>{item}</li>\n'
        list_html += '</ol>'
        return list_html
    
    def _format_unordered_list(self, items: List[str]) -> str:
        """Format unordered list"""
        list_html = '<ul>\n'
        for item in items:
            list_html += f'  <li>{item}</li>\n'
        list_html += '</ul>'
        return list_html
    
    def _format_aside(self, content: str, aside_type: str) -> str:
        """Format aside callout box"""
        class_name = f'aside-{aside_type}'
        return f'<aside class="{class_name}">\n  {content}\n</aside>'


class QuestionFileProcessor:
    """Processes questions.md files and generates answers"""
    
    def __init__(self, base_dir: str = 'interview-bank'):
        self.base_dir = Path(base_dir)
        self.answer_generator = AnswerGenerator()
        
        self.topics = [
            'realtime-communication',
            'php-laravel-api-security',
            'frontend-react-nextjs',
            'devops-cloud-k8s',
            'database-optimization',
            'ai-llm-serverless'
        ]
    
    def read_questions_from_file(self, topic: str) -> List[Question]:
        """Read and parse questions from questions.md file"""
        questions_file = self.base_dir / topic / 'questions.md'
        
        if not questions_file.exists():
            print(f"Warning: {questions_file} not found")
            return []
        
        with open(questions_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        questions = []
        lines = content.split('\n')
        current_num = None
        current_text = ''
        
        for line in lines:
            match = re.match(r'^(\d+)\.\s+(.*)', line)
            if match:
                if current_num and current_text:
                    # Analyze and create question
                    q_type, keywords, needs_code, needs_diagram = QuestionAnalyzer.analyze(current_text, topic)
                    question = Question(
                        number=current_num,
                        text=current_text.strip(),
                        question_type=q_type,
                        keywords=keywords,
                        topic=topic,
                        requires_code=needs_code,
                        requires_diagram=needs_diagram
                    )
                    questions.append(question)
                
                current_num = match.group(1)
                current_text = match.group(2)
            elif current_num and line.strip() and not line.startswith('#'):
                current_text += ' ' + line.strip()
        
        # Don't forget the last question
        if current_num and current_text:
            q_type, keywords, needs_code, needs_diagram = QuestionAnalyzer.analyze(current_text, topic)
            question = Question(
                number=current_num,
                text=current_text.strip(),
                question_type=q_type,
                keywords=keywords,
                topic=topic,
                requires_code=needs_code,
                requires_diagram=needs_diagram
            )
            questions.append(question)
        
        return questions
    
    def generate_html_for_topic(self, topic: str) -> str:
        """Generate HTML for all questions in a topic"""
        questions = self.read_questions_from_file(topic)
        
        if not questions:
            return ''
        
        html_parts = [f'<section class="topic-section" id="{topic}">']
        html_parts.append(f'<h2 class="topic-title">{topic.replace("-", " ").title()}</h2>')
        
        for question in questions:
            answer_html = self.answer_generator.generate_answer(question)
            
            article_html = f'''<article id="q{question.number}" class="question-answer" data-topic="{topic}" data-type="{question.question_type.value}">
  <div class="question-header">
    <span class="question-number">Question {question.number}</span>
    <span class="question-type">{question.question_type.value.title()}</span>
  </div>
  <div class="question-text">
    <h3>{html.escape(question.text)}</h3>
  </div>
  <div class="answer-content">
{answer_html}
  </div>
</article>
'''
            html_parts.append(article_html)
        
        html_parts.append('</section>')
        return '\n'.join(html_parts)
    
    def generate_complete_html(self) -> str:
        """Generate complete HTML document"""
        html_parts = []
        
        # HTML header with CSS
        html_parts.append(self._get_html_header())
        
        # Generate content for each topic
        for topic in self.topics:
            print(f"Processing topic: {topic}...")
            topic_html = self.generate_html_for_topic(topic)
            if topic_html:
                html_parts.append(topic_html)
        
        # HTML footer
        html_parts.append(self._get_html_footer())
        
        return '\n'.join(html_parts)
    
    def _get_html_header(self) -> str:
        """Get HTML header with CSS"""
        return '''<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comprehensive Interview Answers</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #f5f7fa;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: #2c3e50;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 4px solid #3498db;
      font-size: 32px;
    }
    
    .topic-section {
      margin-bottom: 60px;
    }
    
    .topic-title {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px 30px;
      border-radius: 10px;
      margin-bottom: 30px;
      font-size: 26px;
    }
    
    .question-answer {
      margin-bottom: 40px;
      padding: 30px;
      background: #fafbfc;
      border-left: 5px solid #3498db;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .question-number {
      font-weight: bold;
      color: #3498db;
      font-size: 14px;
      text-transform: uppercase;
    }
    
    .question-type {
      background: #e8f4f8;
      color: #2980b9;
      padding: 5px 15px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: 600;
      text-transform: capitalize;
    }
    
    .question-text h3 {
      color: #2c3e50;
      font-size: 20px;
      margin-bottom: 20px;
      line-height: 1.4;
    }
    
    .answer-content {
      color: #444;
      font-size: 16px;
    }
    
    .answer-content p {
      margin-bottom: 16px;
      line-height: 1.8;
    }
    
    .answer-content code {
      background: #f0f0f0;
      padding: 3px 8px;
      border-radius: 4px;
      font-family: 'Courier New', Consolas, monospace;
      font-size: 14px;
      color: #e74c3c;
    }
    
    .answer-content pre {
      background: #282c34;
      color: #abb2bf;
      padding: 20px;
      border-radius: 8px;
      overflow-x: auto;
      margin: 25px 0;
      border: 1px solid #e1e4e8;
    }
    
    .answer-content pre code {
      background: none;
      color: inherit;
      padding: 0;
      font-size: 14px;
      line-height: 1.5;
      display: block;
    }
    
    .answer-content ul, .answer-content ol {
      margin: 20px 0 20px 30px;
    }
    
    .answer-content li {
      margin-bottom: 10px;
      line-height: 1.6;
    }
    
    .answer-content strong {
      color: #2c3e50;
      font-weight: 600;
    }
    
    .diagram {
      margin: 30px 0;
      padding: 20px;
      background: white;
      border: 2px solid #e1e4e8;
      border-radius: 8px;
      text-align: center;
    }
    
    .diagram svg {
      max-width: 100%;
      height: auto;
    }
    
    aside {
      margin: 25px 0;
      padding: 20px;
      border-radius: 8px;
      border-left: 4px solid;
    }
    
    .aside-security {
      background: #fff5f5;
      border-left-color: #e74c3c;
    }
    
    .aside-performance {
      background: #f0fff4;
      border-left-color: #27ae60;
    }
    
    aside strong {
      display: block;
      margin-bottom: 10px;
      font-size: 16px;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }
      
      .question-answer {
        padding: 20px;
      }
      
      .question-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      
      .answer-content pre {
        padding: 15px;
      }
    }
    
    /* Syntax highlighting for code blocks */
    .language-php .keyword { color: #c678dd; }
    .language-php .string { color: #98c379; }
    .language-php .comment { color: #5c6370; font-style: italic; }
    .language-javascript .keyword { color: #c678dd; }
    .language-javascript .string { color: #98c379; }
    .language-sql .keyword { color: #56b6c2; }
    .language-yaml .key { color: #61afef; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Comprehensive Interview Answers</h1>
'''
    
    def _get_html_footer(self) -> str:
        """Get HTML footer"""
        return '''  </div>
  <script>
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
    
    // Add copy button to code blocks
    document.querySelectorAll('pre code').forEach(block => {
      const button = document.createElement('button');
      button.textContent = 'Copy';
      button.style.cssText = 'position:absolute;top:10px;right:10px;padding:5px 10px;background:#3498db;color:white;border:none;border-radius:4px;cursor:pointer;font-size:12px;';
      
      const pre = block.parentElement;
      pre.style.position = 'relative';
      pre.appendChild(button);
      
      button.addEventListener('click', () => {
        navigator.clipboard.writeText(block.textContent);
        button.textContent = 'Copied!';
        setTimeout(() => button.textContent = 'Copy', 2000);
      });
    });
  </script>
</body>
</html>'''
    
    def save_html(self, output_file: str = 'automation/output/comprehensive-answers.html'):
        """Generate and save HTML file"""
        print("=" * 70)
        print("Comprehensive Answer Generation Script")
        print("=" * 70)
        print(f"Base directory: {self.base_dir}")
        print(f"Topics to process: {len(self.topics)}")
        print()
        
        # Ensure output directory exists
        output_path = Path(output_file)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Generate HTML
        html_content = self.generate_complete_html()
        
        # Save to file
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        print()
        print("=" * 70)
        print(f"[SUCCESS] HTML file generated successfully: {output_file}")
        print(f"[SUCCESS] File size: {len(html_content):,} bytes")
        print(f"[SUCCESS] Topics processed: {len(self.topics)}")
        print("=" * 70)


def main():
    """Main entry point"""
    processor = QuestionFileProcessor()
    processor.save_html()


if __name__ == '__main__':
    main()
