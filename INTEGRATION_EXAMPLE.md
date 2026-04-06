# Code Snippet Management - Integration Examples

This document provides practical integration examples for using the code snippet management system.

## Table of Contents
1. [Basic Usage](#basic-usage)
2. [Question with Code Snippets](#question-with-code-snippets)
3. [Frontend Integration](#frontend-integration)
4. [Advanced Use Cases](#advanced-use-cases)

---

## Basic Usage

### 1. Creating a Question with Code Snippets

```javascript
// Step 1: Create the question
const questionResponse = await fetch('/api/questions', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        difficulty_level_id: 2,
        title: 'Array Manipulation in JavaScript',
        question_text: 'How do you filter and map arrays efficiently?',
        explanation: 'Arrays can be manipulated using built-in methods like filter and map.',
        options: {
            'A': 'Using for loops',
            'B': 'Using array methods',
            'C': 'Using while loops',
            'D': 'None of the above'
        },
        correct_answer: ['B'],
        is_published: true
    })
});

const question = await questionResponse.json();
const questionId = question.data.id;

// Step 2: Add code snippets
const snippetsResponse = await fetch('/api/code-snippets/bulk-create', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        question_id: questionId,
        snippets: [
            {
                title: 'Example: Filter Even Numbers',
                description: 'This example shows how to filter even numbers from an array',
                code: `const numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
const evenNumbers = numbers.filter(num => num % 2 === 0);
console.log(evenNumbers); // [2, 4, 6, 8, 10]`,
                type: 'example',
                order: 0
            },
            {
                title: 'Example: Map to Squares',
                description: 'Transform array elements by squaring them',
                code: `const numbers = [1, 2, 3, 4, 5];
const squares = numbers.map(num => num ** 2);
console.log(squares); // [1, 4, 9, 16, 25]`,
                type: 'example',
                order: 1
            },
            {
                title: 'Solution: Combined Filter and Map',
                description: 'Complete solution using both methods',
                code: `const numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Filter even numbers and square them
const result = numbers
    .filter(num => num % 2 === 0)
    .map(num => num ** 2);

console.log(result); // [4, 16, 36, 64, 100]`,
                type: 'solution',
                is_executable: true,
                expected_output: '[4, 16, 36, 64, 100]',
                order: 2
            }
        ]
    })
});

console.log('Question with snippets created!');
```

---

## Question with Code Snippets

### 2. Fetching Question with Highlighted Snippets

```javascript
async function fetchQuestionWithSnippets(questionId) {
    // Fetch question with relationships
    const questionResponse = await fetch(`/api/questions/${questionId}`, {
        headers: {
            'Authorization': 'Bearer YOUR_TOKEN',
            'Accept': 'application/json'
        }
    });
    
    const questionData = await questionResponse.json();
    
    // Fetch code snippets with pre-rendered HTML
    const snippetsResponse = await fetch(
        `/api/code-snippets?question_id=${questionId}&render_html=true`,
        {
            headers: {
                'Authorization': 'Bearer YOUR_TOKEN',
                'Accept': 'application/json'
            }
        }
    );
    
    const snippetsData = await snippetsResponse.json();
    
    return {
        question: questionData.data,
        snippets: snippetsData.data
    };
}

// Usage
const data = await fetchQuestionWithSnippets(5);
console.log(data.question.title);
console.log(data.snippets.length + ' code snippets');
```

---

## Frontend Integration

### 3. React Component Example

```jsx
import React, { useState, useEffect } from 'react';

function QuestionWithCodeSnippets({ questionId }) {
    const [question, setQuestion] = useState(null);
    const [snippets, setSnippets] = useState([]);
    const [selectedTheme, setSelectedTheme] = useState('atom-one-dark');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchQuestionAndSnippets();
    }, [questionId]);

    const fetchQuestionAndSnippets = async () => {
        try {
            // Fetch question
            const questionRes = await fetch(`/api/questions/${questionId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            const questionData = await questionRes.json();
            
            // Fetch snippets with pre-rendered HTML
            const snippetsRes = await fetch(
                `/api/code-snippets?question_id=${questionId}&render_html=true`,
                {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                }
            );
            const snippetsData = await snippetsRes.json();
            
            setQuestion(questionData.data);
            setSnippets(snippetsData.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching data:', error);
            setLoading(false);
        }
    };

    const changeTheme = (theme) => {
        setSelectedTheme(theme);
    };

    if (loading) return <div>Loading...</div>;

    return (
        <div className="question-container">
            {/* Question Section */}
            <div className="question-header">
                <h1>{question.title}</h1>
                <div className="question-meta">
                    <span className="difficulty">
                        {question.difficulty_level?.name}
                    </span>
                    <span className="points">{question.points} points</span>
                </div>
            </div>

            <div className="question-text">
                <p>{question.question_text}</p>
            </div>

            {/* Theme Selector */}
            <div className="theme-selector">
                <label>Theme: </label>
                <select value={selectedTheme} onChange={(e) => changeTheme(e.target.value)}>
                    <option value="atom-one-dark">Atom One Dark</option>
                    <option value="github">GitHub</option>
                    <option value="monokai">Monokai</option>
                    <option value="vs2015">VS 2015</option>
                    <option value="dracula">Dracula</option>
                </select>
            </div>

            {/* Code Snippets */}
            <link 
                rel="stylesheet" 
                href={`https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/${selectedTheme}.min.css`}
            />

            <div className="code-snippets">
                {snippets.map((snippet, index) => (
                    <div key={snippet.id} className="snippet-card">
                        <div className="snippet-header">
                            <h3>{snippet.title}</h3>
                            <span className="language-badge">{snippet.language}</span>
                        </div>
                        
                        {snippet.description && (
                            <p className="snippet-description">{snippet.description}</p>
                        )}
                        
                        <pre>
                            <code 
                                className="hljs"
                                dangerouslySetInnerHTML={{ __html: snippet.highlighted_code }}
                            />
                        </pre>
                        
                        {snippet.expected_output && (
                            <div className="expected-output">
                                <strong>Expected Output:</strong>
                                <code>{snippet.expected_output}</code>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* Question Options */}
            <div className="question-options">
                {Object.entries(question.options).map(([key, value]) => (
                    <div key={key} className="option">
                        <input type="radio" name="answer" value={key} id={`option-${key}`} />
                        <label htmlFor={`option-${key}`}>{key}. {value}</label>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default QuestionWithCodeSnippets;
```

### 4. Vue.js Component Example

```vue
<template>
    <div class="question-view">
        <div v-if="loading">Loading...</div>
        
        <div v-else class="question-container">
            <!-- Question Header -->
            <div class="question-header">
                <h1>{{ question.title }}</h1>
                <div class="meta">
                    <span class="difficulty">{{ question.difficulty_level?.name }}</span>
                    <span class="points">{{ question.points }} points</span>
                </div>
            </div>

            <!-- Question Text -->
            <p class="question-text">{{ question.question_text }}</p>

            <!-- Code Snippets -->
            <div class="snippets-section">
                <div 
                    v-for="snippet in snippets" 
                    :key="snippet.id"
                    class="snippet-card"
                >
                    <div class="snippet-header">
                        <h3>{{ snippet.title }}</h3>
                        <span class="language">{{ snippet.language }}</span>
                    </div>
                    
                    <p v-if="snippet.description">{{ snippet.description }}</p>
                    
                    <pre><code 
                        class="hljs" 
                        v-html="snippet.highlighted_code"
                    ></code></pre>
                </div>
            </div>

            <!-- Answer Options -->
            <div class="options">
                <div 
                    v-for="(text, key) in question.options" 
                    :key="key"
                    class="option"
                >
                    <input 
                        type="radio" 
                        :value="key" 
                        v-model="selectedAnswer"
                        :id="`option-${key}`"
                    />
                    <label :for="`option-${key}`">{{ key }}. {{ text }}</label>
                </div>
            </div>

            <button @click="submitAnswer" class="submit-btn">
                Submit Answer
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'QuestionView',
    props: {
        questionId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            question: null,
            snippets: [],
            selectedAnswer: null,
            loading: true
        }
    },
    mounted() {
        this.loadQuestion();
        this.loadStylesheet();
    },
    methods: {
        async loadQuestion() {
            try {
                const questionRes = await fetch(`/api/questions/${this.questionId}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                const questionData = await questionRes.json();
                
                const snippetsRes = await fetch(
                    `/api/code-snippets?question_id=${this.questionId}&render_html=true`,
                    {
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('token')}`
                        }
                    }
                );
                const snippetsData = await snippetsRes.json();
                
                this.question = questionData.data;
                this.snippets = snippetsData.data;
                this.loading = false;
            } catch (error) {
                console.error('Error loading question:', error);
                this.loading = false;
            }
        },
        
        loadStylesheet() {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css';
            document.head.appendChild(link);
        },
        
        async submitAnswer() {
            // Submit answer logic
            console.log('Selected answer:', this.selectedAnswer);
        }
    }
}
</script>
```

---

## Advanced Use Cases

### 5. Code Snippet Editor Component

```javascript
class CodeSnippetEditor {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.snippets = [];
        this.questionId = null;
    }

    async initialize(questionId) {
        this.questionId = questionId;
        await this.loadSnippets();
        this.render();
    }

    async loadSnippets() {
        const response = await fetch(
            `/api/code-snippets?question_id=${this.questionId}`,
            {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );
        const data = await response.json();
        this.snippets = data.data;
    }

    async addSnippet() {
        const code = document.getElementById('new-snippet-code').value;
        const title = document.getElementById('new-snippet-title').value;
        const type = document.getElementById('new-snippet-type').value;

        const response = await fetch('/api/code-snippets', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: this.questionId,
                title,
                code,
                type,
                render_html: true
            })
        });

        const data = await response.json();
        this.snippets.push(data.data);
        this.render();
    }

    async updateSnippet(snippetId, updates) {
        const response = await fetch(`/api/code-snippets/${snippetId}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updates)
        });

        const data = await response.json();
        const index = this.snippets.findIndex(s => s.id === snippetId);
        this.snippets[index] = data.data;
        this.render();
    }

    async deleteSnippet(snippetId) {
        await fetch(`/api/code-snippets/${snippetId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        this.snippets = this.snippets.filter(s => s.id !== snippetId);
        this.render();
    }

    async reorderSnippets(newOrder) {
        await fetch('/api/code-snippets/reorder', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: this.questionId,
                snippet_orders: newOrder
            })
        });

        await this.loadSnippets();
        this.render();
    }

    render() {
        const html = `
            <div class="snippet-editor">
                <h3>Code Snippets</h3>
                ${this.snippets.map(snippet => this.renderSnippet(snippet)).join('')}
                <button onclick="editor.showAddForm()">+ Add Snippet</button>
            </div>
        `;
        this.container.innerHTML = html;
    }

    renderSnippet(snippet) {
        return `
            <div class="snippet-item" data-id="${snippet.id}">
                <div class="snippet-controls">
                    <button onclick="editor.editSnippet(${snippet.id})">Edit</button>
                    <button onclick="editor.deleteSnippet(${snippet.id})">Delete</button>
                </div>
                <h4>${snippet.title}</h4>
                <span class="language">${snippet.language}</span>
                <pre><code class="hljs">${snippet.highlighted_code || snippet.code}</code></pre>
            </div>
        `;
    }
}

// Usage
const editor = new CodeSnippetEditor('editor-container');
editor.initialize(5);
```

### 6. Language Detection Helper

```javascript
async function detectAndHighlightCode(code) {
    // Step 1: Detect language
    const detectResponse = await fetch('/api/code-snippets/detect-language', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ code })
    });
    const detectData = await detectResponse.json();
    const language = detectData.data.language;

    // Step 2: Highlight code
    const highlightResponse = await fetch('/api/code-snippets/highlight', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ code, language })
    });
    const highlightData = await highlightResponse.json();

    return {
        language,
        highlighted: highlightData.data.highlighted,
        relevance: highlightData.data.relevance
    };
}

// Usage
const result = await detectAndHighlightCode('console.log("Hello");');
console.log(`Detected: ${result.language} (${result.relevance}% confidence)`);
```

### 7. Batch Operations

```javascript
async function batchCreateSnippetsForMultipleQuestions(questionsData) {
    const results = [];

    for (const questionData of questionsData) {
        // Create question
        const questionRes = await fetch('/api/questions', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(questionData.question)
        });
        const question = await questionRes.json();

        // Create snippets
        const snippetsRes = await fetch('/api/code-snippets/bulk-create', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: question.data.id,
                snippets: questionData.snippets
            })
        });
        const snippets = await snippetsRes.json();

        results.push({
            question: question.data,
            snippets: snippets.data
        });
    }

    return results;
}
```

---

## Best Practices

1. **Always use pre-rendered HTML for static content** - Better performance and SEO
2. **Enable language auto-detection** - More accurate than manual specification
3. **Cache highlighted code on the client** - Reduce server requests
4. **Use appropriate snippet types** - Helps organize and categorize code
5. **Provide theme selection** - Better user experience and accessibility
6. **Validate code before submission** - Ensure quality and security
7. **Use bulk operations when possible** - More efficient than individual calls

---

## Common Patterns

### Pattern 1: Progressive Enhancement
```javascript
// Load without highlighting first for fast render
const snippets = await fetch('/api/code-snippets?render_html=false');
// Then apply highlighting
hljs.highlightAll();
```

### Pattern 2: Lazy Loading
```javascript
const observer = new IntersectionObserver((entries) => {
    entries.forEach(async (entry) => {
        if (entry.isIntersecting) {
            const snippetId = entry.target.dataset.snippetId;
            // Load snippet on demand
            const snippet = await fetchSnippet(snippetId);
            renderSnippet(entry.target, snippet);
        }
    });
});
```

### Pattern 3: Real-time Preview
```javascript
let debounceTimer;
codeInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        const highlighted = await highlightCode(e.target.value);
        preview.innerHTML = highlighted;
    }, 300);
});
```

---

This integration guide provides comprehensive examples for implementing the code snippet management system in your application.
