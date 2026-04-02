# Code Snippet Template

Use this template for consistent code block formatting across all interview questions.

## Difficulty Level Indicators

Use these badges to indicate question difficulty:

- **Basic**: ⭐ Basic
- **Intermediate**: ⭐⭐ Intermediate  
- **Advanced**: ⭐⭐⭐ Advanced
- **Expert**: ⭐⭐⭐⭐ Expert

## Basic Code Block

```[language]
// Brief description of what this code does

[Code here]
```

## TypeScript/JavaScript Example

```typescript
/**
 * Service for handling user authentication
 * @class AuthService
 */
export class AuthService {
  private readonly jwtSecret: string;
  
  constructor(secret: string) {
    this.jwtSecret = secret;
  }
  
  /**
   * Generates a JWT token for the user
   * @param userId - The user's unique identifier
   * @returns JWT token string
   */
  async generateToken(userId: string): Promise<string> {
    return jwt.sign({ userId }, this.jwtSecret, {
      expiresIn: '24h',
    });
  }
}
```

## Python Example

```python
from typing import List, Optional
from dataclasses import dataclass

@dataclass
class User:
    """Represents a user in the system"""
    id: int
    email: str
    name: str
    is_active: bool = True
    
    def deactivate(self) -> None:
        """Deactivates the user account"""
        self.is_active = False
    
    @classmethod
    def from_dict(cls, data: dict) -> 'User':
        """Creates a User instance from a dictionary"""
        return cls(
            id=data['id'],
            email=data['email'],
            name=data['name'],
            is_active=data.get('is_active', True)
        )
```

## Go Example

```go
package main

import (
    "context"
    "fmt"
    "time"
)

// User represents a user in the system
type User struct {
    ID        string    `json:"id"`
    Email     string    `json:"email"`
    Name      string    `json:"name"`
    CreatedAt time.Time `json:"created_at"`
}

// UserService handles user operations
type UserService struct {
    repo UserRepository
}

// GetUser retrieves a user by ID
func (s *UserService) GetUser(ctx context.Context, id string) (*User, error) {
    user, err := s.repo.FindByID(ctx, id)
    if err != nil {
        return nil, fmt.Errorf("failed to get user: %w", err)
    }
    return user, nil
}
```

## Annotated Code Block

Use comments to explain key concepts:

```[language]
// === [Section Title] ===

// [Explanation of the following code]
[Code block 1]

// === [Another Section Title] ===

// [Explanation of this section]
[Code block 2]
```

## Before/After Comparison

### ❌ Problematic Approach

```[language]
// Why this approach is problematic
[Code showing the issue]
```

### ✅ Recommended Approach

```[language]
// Why this approach is better
[Code showing the solution]
```

## Multi-file Examples

### File: `[filename1.ext]`

```[language]
// Purpose of this file
[Code content]
```

### File: `[filename2.ext]`

```[language]
// Purpose of this file
[Code content]
```

## Configuration Examples

### Environment Variables

```bash
# .env
[ENV_VAR_1]=value1
[ENV_VAR_2]=value2
```

### Configuration File

```[language]
// config/[service].php or similar
[Configuration array or object]
```

## API Request/Response Examples

### Request

```http
[METHOD] /api/endpoint
Content-Type: application/json
Authorization: Bearer [token]

{
  "key": "value"
}
```

### Response

```json
{
  "status": "success",
  "data": {
    "key": "value"
  }
}
```

## Shell Commands

```bash
# Description of what these commands do
command1 --option value
command2 --flag
```

## Database Schema

```sql
-- Table: [table_name]
-- Purpose: [description]

CREATE TABLE [table_name] (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- [column descriptions]
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_[name] ON [table_name]([column]);
```

## Docker/Kubernetes YAML

```yaml
# Description of this configuration
apiVersion: [version]
kind: [Kind]
metadata:
  name: [name]
spec:
  # Configuration details
```

## Test Examples

### Unit Test

```[language]
// Test: [what is being tested]

test('[test description]', function () {
    // Arrange
    [Setup code]
    
    // Act
    [Code under test]
    
    // Assert
    [Assertions]
});
```

### Integration Test

```[language]
// Integration Test: [what scenario is being tested]

test('[test description]', function () {
    // Given
    [Initial state]
    
    // When
    [Action performed]
    
    // Then
    [Expected outcome]
});
```

## Performance Comparison

### Benchmark Setup

```[language]
// Scenario: [description]
// Iterations: [number]

// Approach 1: [name]
[Code for approach 1]

// Approach 2: [name]
[Code for approach 2]

// Results:
// Approach 1: [timing]
// Approach 2: [timing]
// Winner: [which is faster and why]
```

## Notes for Code Examples

- Always include context comments explaining WHY the code works this way
- Highlight security considerations with `// SECURITY:` comments
- Mark performance-critical sections with `// PERFORMANCE:` comments
- Use `// TODO:` for areas that would need additional implementation in production
- Use `// NOTE:` for important caveats or considerations
- Include error handling where relevant
- Show both the happy path and error scenarios when applicable
