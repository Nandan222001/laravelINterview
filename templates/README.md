# Interview Question Templates

This directory contains reusable markdown templates for creating consistent, high-quality interview questions across all topic areas.

## 📋 Available Templates

### 1. Question Format Template
**File**: `question-format-template.md`

A comprehensive template for structuring interview questions with:
- Clear question statement and scenario
- Expected discussion points
- Sample solutions
- Key concepts to assess
- Follow-up questions
- Common pitfalls
- Evaluation criteria by seniority level

**Use for**: All interview questions to ensure consistency

---

### 2. Code Snippet Template
**File**: `code-snippet-template.md`

Standardized formats for code examples including:
- Basic code blocks with syntax highlighting
- Annotated code with explanatory comments
- Before/after comparisons
- Multi-file examples
- Configuration examples
- API request/response examples
- Database schema
- Docker/Kubernetes YAML
- Test examples
- Performance benchmarks

**Use for**: Any code examples within interview questions

---

### 3. Architecture Diagram Template
**File**: `architecture-diagram-template.md`

ASCII-based diagram templates for:
- System architecture
- Flow diagrams
- Sequence diagrams
- Deployment architecture
- Data flow diagrams
- Microservices architecture
- Component interactions

**Use for**: Visualizing system design and architecture in questions

---

### 4. Answer Flow Template
**File**: `answer-flow-template.md`

Structured approach for candidates to answer complex questions:
- Clarifying requirements
- Stating assumptions
- High-level approach
- Implementation details
- Trade-off discussions
- Scalability considerations
- Testing strategy
- Monitoring and observability
- Security considerations
- Summary and next steps

**Use for**: Providing candidates with a framework for thorough answers

---

## 🎯 How to Use These Templates

### For Creating New Questions

1. **Copy the question format template**
   ```bash
   cp templates/question-format-template.md interview-bank/[topic]/[question-name].md
   ```

2. **Fill in the template fields**
   - Replace placeholders with actual content
   - Add code examples using the code snippet template
   - Include diagrams using the architecture template
   - Reference the answer flow template in the question

3. **Review for completeness**
   - Ensure all sections are filled
   - Verify code examples are accurate
   - Check that evaluation criteria is clear

### For Candidates Preparing for Interviews

1. **Study the answer flow template** to understand how to structure comprehensive answers
2. **Review question format** to understand what interviewers are looking for
3. **Practice using the templates** to develop systematic problem-solving approaches

### For Interviewers

1. **Use question format template** to ensure consistency across interviews
2. **Reference evaluation criteria** to fairly assess candidates at different levels
3. **Prepare follow-up questions** using the template structure

---

## 🔧 Customization Guidelines

When customizing templates:

- **Maintain structure**: Keep the core sections for consistency
- **Add context**: Include domain-specific details relevant to your questions
- **Be specific**: Replace generic placeholders with concrete examples
- **Stay realistic**: Use real-world scenarios and practical constraints
- **Update regularly**: Incorporate feedback and improve templates over time

---

## 📚 Best Practices

### Writing Questions
- Start with a real-world problem or scenario
- Provide enough context but leave room for candidates to ask clarifying questions
- Include multiple layers of depth for different seniority levels
- Consider both technical and soft skill assessment

### Code Examples
- Use production-quality code, not pseudocode
- Include error handling and edge cases
- Add comments to explain complex logic
- Show both what to do and what not to do

### Diagrams
- Keep diagrams simple and focused
- Label all components clearly
- Show data flow and interactions
- Include a legend if using special symbols

### Answer Flows
- Encourage systematic thinking
- Allow flexibility in approach
- Promote discussion over memorization
- Focus on reasoning and trade-offs

---

## 🤝 Contributing to Templates

If you find ways to improve these templates:

1. Test your changes with actual questions
2. Ensure backward compatibility with existing questions
3. Document your changes
4. Consider creating specialized templates for specific question types

---

## 📖 Examples

For examples of these templates in use, check the interview question directories:
- `/interview-bank/php-laravel-api-security/`
- `/interview-bank/realtime-communication/`
- `/interview-bank/database-optimization/`
- `/interview-bank/frontend-react-nextjs/`
- `/interview-bank/devops-cloud-k8s/`
- `/interview-bank/ai-llm-serverless/`

---

## Quick Reference

| Template | Primary Use | Key Sections |
|----------|-------------|--------------|
| Question Format | Creating interview questions | Scenario, Solution, Evaluation |
| Code Snippet | Code examples | Syntax highlighting, annotations |
| Architecture Diagram | System design | ASCII diagrams, flow charts |
| Answer Flow | Candidate guidance | Structured problem-solving |
