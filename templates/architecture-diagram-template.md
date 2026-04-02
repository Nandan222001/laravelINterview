# Architecture Diagram Template

Use this template to include architecture diagrams and system design illustrations in interview questions.

## Mermaid Diagram Templates

### Flowchart Example

```mermaid
flowchart TD
    A[Start] --> B{Decision}
    B -->|Yes| C[Process 1]
    B -->|No| D[Process 2]
    C --> E[End]
    D --> E
```

### Sequence Diagram Example

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant Database
    participant Cache
    
    Client->>API: Request Data
    API->>Cache: Check Cache
    
    alt Cache Hit
        Cache-->>API: Return Cached Data
    else Cache Miss
        API->>Database: Query Database
        Database-->>API: Return Data
        API->>Cache: Store in Cache
    end
    
    API-->>Client: Return Response
```

### System Architecture Example

```mermaid
graph TB
    subgraph "Client Layer"
        Web[Web App]
        Mobile[Mobile App]
    end
    
    subgraph "API Gateway"
        Gateway[Load Balancer]
    end
    
    subgraph "Application Layer"
        App1[App Server 1]
        App2[App Server 2]
        App3[App Server 3]
    end
    
    subgraph "Data Layer"
        DB[(Primary DB)]
        Replica1[(Replica 1)]
        Redis[(Redis Cache)]
    end
    
    Web --> Gateway
    Mobile --> Gateway
    Gateway --> App1
    Gateway --> App2
    Gateway --> App3
    App1 --> DB
    App2 --> DB
    App3 --> DB
    DB --> Replica1
    App1 --> Redis
    App2 --> Redis
    App3 --> Redis
```

### Class Diagram Example

```mermaid
classDiagram
    class User {
        +String id
        +String email
        +String name
        +login()
        +logout()
    }
    
    class Order {
        +String id
        +Date createdAt
        +Float total
        +calculateTotal()
        +process()
    }
    
    class Product {
        +String id
        +String name
        +Float price
        +getDetails()
    }
    
    User "1" -- "*" Order
    Order "*" -- "*" Product
```

### State Diagram Example

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Review: Submit
    Review --> Approved: Approve
    Review --> Rejected: Reject
    Rejected --> Draft: Revise
    Approved --> Published: Publish
    Published --> Archived: Archive
    Archived --> [*]
```

### Entity Relationship Diagram

```mermaid
erDiagram
    CUSTOMER ||--o{ ORDER : places
    ORDER ||--|{ LINE-ITEM : contains
    CUSTOMER }|..|{ DELIVERY-ADDRESS : uses
    
    CUSTOMER {
        string id PK
        string email UK
        string name
    }
    
    ORDER {
        string id PK
        string customer_id FK
        date order_date
        decimal total
    }
    
    LINE-ITEM {
        string id PK
        string order_id FK
        string product_id FK
        int quantity
    }
```

## ASCII Diagram Template

```
┌─────────────────────────────────────────────────────────────┐
│                     [Component/System Name]                  │
└─────────────────────────────────────────────────────────────┘

┌──────────────┐          ┌──────────────┐          ┌──────────────┐
│   [Client]   │ ────────>│  [Service]   │ ────────>│  [Database]  │
│              │          │              │          │              │
│  [Details]   │<─────── │  [Details]   │<─────── │  [Details]   │
└──────────────┘          └──────────────┘          └──────────────┘
       │                         │                         │
       │                         │                         │
       v                         v                         v
┌──────────────┐          ┌──────────────┐          ┌──────────────┐
│   [Cache]    │          │   [Queue]    │          │  [Storage]   │
└──────────────┘          └──────────────┘          └──────────────┘
```

## Flow Diagram

```
[Start Event]
      │
      v
┌─────────────┐
│   Step 1    │
│ [Details]   │
└─────────────┘
      │
      v
   ╱     ╲
  ╱ Check? ╲ ───── No ────> [Alternative Path]
  \       /                       │
   ╲     ╱                        │
      │ Yes                       │
      v                           │
┌─────────────┐                   │
│   Step 2    │                   │
│ [Details]   │                   │
└─────────────┘                   │
      │                           │
      └───────────┬───────────────┘
                  v
            [End Event]
```

## System Architecture

```
                          ┌─────────────────────┐
                          │    Load Balancer    │
                          └──────────┬──────────┘
                                     │
                ┌────────────────────┼────────────────────┐
                │                    │                    │
                v                    v                    v
        ┌───────────────┐    ┌───────────────┐   ┌───────────────┐
        │  App Server 1 │    │  App Server 2 │   │  App Server 3 │
        └───────┬───────┘    └───────┬───────┘   └───────┬───────┘
                │                    │                    │
                └────────────────────┼────────────────────┘
                                     │
                    ┌────────────────┼────────────────┐
                    │                │                │
                    v                v                v
            ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
            │    Cache      │  │   Database   │  │  Queue/Jobs  │
            │   (Redis)     │  │  (Primary)   │  │   (Redis)    │
            └──────────────┘  └───────┬──────┘  └──────────────┘
                                      │
                              ┌───────┴────────┐
                              │                │
                              v                v
                      ┌──────────────┐  ┌──────────────┐
                      │  DB Replica  │  │  DB Replica  │
                      │  (Read-only) │  │  (Read-only) │
                      └──────────────┘  └──────────────┘
```

## Sequence Diagram

```
Client          API Gateway         Service A         Service B        Database
  │                  │                  │                 │                │
  │─────Request─────>│                  │                 │                │
  │                  │                  │                 │                │
  │                  │────Validate─────>│                 │                │
  │                  │                  │                 │                │
  │                  │                  │─────Query──────>│                │
  │                  │                  │                 │                │
  │                  │                  │                 │────Read───────>│
  │                  │                  │                 │                │
  │                  │                  │                 │<────Data───────│
  │                  │                  │                 │                │
  │                  │                  │<────Result──────│                │
  │                  │                  │                 │                │
  │                  │<────Response─────│                 │                │
  │                  │                  │                 │                │
  │<────Response─────│                  │                 │                │
  │                  │                  │                 │                │
```

## Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Cloud Provider (AWS/GCP/Azure)          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    Kubernetes Cluster                    │   │
│  │                                                           │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │   │
│  │  │   Ingress   │  │   Ingress   │  │   Ingress   │     │   │
│  │  │ Controller  │  │ Controller  │  │ Controller  │     │   │
│  │  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘     │   │
│  │         │                │                │             │   │
│  │  ┌──────┴──────────────────────────┴──────┴──────┐     │   │
│  │  │              Service Layer                     │     │   │
│  │  └──────┬───────────────────────────────┬────────┘     │   │
│  │         │                                │              │   │
│  │  ┌──────┴──────┐                 ┌──────┴──────┐       │   │
│  │  │    Pods     │                 │    Pods     │       │   │
│  │  │  (Backend)  │                 │  (Workers)  │       │   │
│  │  └─────────────┘                 └─────────────┘       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │   Database   │  │    Cache     │  │  Object      │         │
│  │   (RDS)      │  │  (ElastiC)   │  │  Storage     │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
Input Data
    │
    v
┌─────────────────┐
│  Validation     │
│  Layer          │
└────────┬────────┘
         │
         v
┌─────────────────┐      ┌─────────────────┐
│  Processing     │─────>│  Cache Check    │
│  Layer          │      └────────┬────────┘
└────────┬────────┘               │
         │                     Hit │ Miss
         │                        │
         v                        v
┌─────────────────┐      ┌─────────────────┐
│  Business       │      │  Database       │
│  Logic          │      │  Query          │
└────────┬────────┘      └────────┬────────┘
         │                        │
         v                        v
┌─────────────────┐      ┌─────────────────┐
│  Data           │<─────│  Cache Update   │
│  Transformation │      └─────────────────┘
└────────┬────────┘
         │
         v
┌─────────────────┐
│  Response       │
│  Formatting     │
└────────┬────────┘
         │
         v
    Output Data
```

## Microservices Architecture

```
                    ┌─────────────────┐
                    │  API Gateway    │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        v                    v                    v
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│   Auth        │    │   User        │    │   Order       │
│   Service     │    │   Service     │    │   Service     │
└───────┬───────┘    └───────┬───────┘    └───────┬───────┘
        │                    │                    │
        v                    v                    v
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│   Auth DB     │    │   User DB     │    │   Order DB    │
└───────────────┘    └───────────────┘    └───────────────┘

                    ┌─────────────────┐
                    │  Message Queue  │
                    │    (RabbitMQ)   │
                    └─────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        v                    v                    v
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│  Notification │    │   Analytics   │    │   Logging     │
│   Service     │    │   Service     │    │   Service     │
└───────────────┘    └───────────────┘    └───────────────┘
```

## Component Interaction

```
┌────────────────────────────────────────────────────────┐
│                    Frontend Layer                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐            │
│  │  React   │  │  Next.js │  │  Mobile  │            │
│  │   App    │  │   App    │  │   App    │            │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘            │
└───────┼─────────────┼─────────────┼──────────────────┘
        │             │             │
        └─────────────┼─────────────┘
                      │
        ┌─────────────┴─────────────┐
        │      REST API / GraphQL    │
        └─────────────┬─────────────┘
                      │
┌─────────────────────┼─────────────────────────────────┐
│                Backend Layer                          │
│  ┌──────────────────┼──────────────────┐             │
│  │           Application Core           │             │
│  │  ┌──────────┐  ┌──────────┐         │             │
│  │  │  Domain  │  │ Business │         │             │
│  │  │  Logic   │  │   Rules  │         │             │
│  │  └──────────┘  └──────────┘         │             │
│  └──────────────────┬──────────────────┘             │
│                     │                                 │
│  ┌──────────────────┼──────────────────┐             │
│  │          Data Access Layer           │             │
│  │  ┌──────────┐  ┌──────────┐         │             │
│  │  │   ORM    │  │  Query   │         │             │
│  │  │ (Eloquent│  │ Builder  │         │             │
│  │  └──────────┘  └──────────┘         │             │
│  └──────────────────┬──────────────────┘             │
└────────────────────┼──────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        v            v            v
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Database │  │  Cache   │  │ External │
│          │  │  (Redis) │  │   APIs   │
└──────────┘  └──────────┘  └──────────┘
```

## Usage Instructions

1. Choose the appropriate diagram type for your question
2. Copy the template and customize it with your specific components
3. Use consistent naming conventions
4. Add labels and annotations to clarify complex interactions
5. Include a legend if using special symbols
6. Consider adding a text description below the diagram explaining key points

## Diagram Legend

```
Symbol Key:
───>   : Synchronous request/response
- - >  : Asynchronous message
═══>   : Data flow
│      : Connection/relationship
┌─┐    : Component/service
╱ ╲    : Decision point
```
