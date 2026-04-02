# LLM API Integration Examples

## 1. OpenAI API Integration with Best Practices

```python
import openai
import os
import time
import json
from typing import List, Dict, Optional, Generator
from functools import wraps
import tiktoken

openai.api_key = os.environ['OPENAI_API_KEY']

class OpenAIClient:
    """
    Production-ready OpenAI API client with error handling and retries
    """
    
    def __init__(self, model: str = "gpt-4-turbo-preview", max_retries: int = 3):
        self.model = model
        self.max_retries = max_retries
        self.encoding = tiktoken.encoding_for_model(model)
    
    def count_tokens(self, text: str) -> int:
        """Count tokens in text"""
        return len(self.encoding.encode(text))
    
    def retry_with_exponential_backoff(self, func):
        """Decorator for exponential backoff retry logic"""
        @wraps(func)
        def wrapper(*args, **kwargs):
            for attempt in range(self.max_retries):
                try:
                    return func(*args, **kwargs)
                except openai.error.RateLimitError as e:
                    if attempt == self.max_retries - 1:
                        raise
                    wait_time = 2 ** attempt
                    print(f"Rate limit hit. Retrying in {wait_time}s...")
                    time.sleep(wait_time)
                except openai.error.APIError as e:
                    if attempt == self.max_retries - 1:
                        raise
                    wait_time = 2 ** attempt
                    print(f"API error. Retrying in {wait_time}s...")
                    time.sleep(wait_time)
        return wrapper
    
    @retry_with_exponential_backoff
    def chat_completion(self, messages: List[Dict], temperature: float = 0.7,
                       max_tokens: Optional[int] = None, **kwargs) -> Dict:
        """
        Chat completion with retry logic
        """
        response = openai.ChatCompletion.create(
            model=self.model,
            messages=messages,
            temperature=temperature,
            max_tokens=max_tokens,
            **kwargs
        )
        
        return {
            'content': response.choices[0].message.content,
            'finish_reason': response.choices[0].finish_reason,
            'usage': {
                'prompt_tokens': response.usage.prompt_tokens,
                'completion_tokens': response.usage.completion_tokens,
                'total_tokens': response.usage.total_tokens
            },
            'model': response.model
        }
    
    def stream_completion(self, messages: List[Dict], **kwargs) -> Generator:
        """
        Streaming chat completion
        """
        response = openai.ChatCompletion.create(
            model=self.model,
            messages=messages,
            stream=True,
            **kwargs
        )
        
        for chunk in response:
            if chunk.choices[0].delta.get('content'):
                yield chunk.choices[0].delta.content
    
    def function_calling(self, messages: List[Dict], functions: List[Dict],
                        function_call: str = "auto") -> Dict:
        """
        Function calling (tool use)
        """
        response = openai.ChatCompletion.create(
            model=self.model,
            messages=messages,
            functions=functions,
            function_call=function_call
        )
        
        message = response.choices[0].message
        
        if message.get('function_call'):
            return {
                'type': 'function_call',
                'name': message.function_call.name,
                'arguments': json.loads(message.function_call.arguments),
                'usage': response.usage
            }
        else:
            return {
                'type': 'message',
                'content': message.content,
                'usage': response.usage
            }
    
    def json_mode(self, messages: List[Dict]) -> Dict:
        """
        JSON mode for structured output
        """
        response = openai.ChatCompletion.create(
            model=self.model,
            messages=messages,
            response_format={"type": "json_object"}
        )
        
        content = response.choices[0].message.content
        
        return {
            'data': json.loads(content),
            'usage': response.usage
        }


# Example usage
def example_chat_completion():
    """Basic chat completion example"""
    client = OpenAIClient(model="gpt-4-turbo-preview")
    
    messages = [
        {"role": "system", "content": "You are a helpful banking assistant."},
        {"role": "user", "content": "What documents do I need for a personal loan?"}
    ]
    
    response = client.chat_completion(messages, temperature=0.7)
    print(f"Response: {response['content']}")
    print(f"Tokens used: {response['usage']['total_tokens']}")


def example_streaming():
    """Streaming response example"""
    client = OpenAIClient()
    
    messages = [
        {"role": "system", "content": "You are a helpful assistant."},
        {"role": "user", "content": "Explain how transformers work in 3 paragraphs."}
    ]
    
    print("Streaming response:")
    for chunk in client.stream_completion(messages):
        print(chunk, end='', flush=True)
    print()


def example_function_calling():
    """Function calling example"""
    client = OpenAIClient()
    
    functions = [
        {
            "name": "get_account_balance",
            "description": "Get the current balance of a bank account",
            "parameters": {
                "type": "object",
                "properties": {
                    "account_number": {
                        "type": "string",
                        "description": "The account number"
                    }
                },
                "required": ["account_number"]
            }
        }
    ]
    
    messages = [
        {"role": "user", "content": "What's my balance for account 123456?"}
    ]
    
    response = client.function_calling(messages, functions)
    
    if response['type'] == 'function_call':
        print(f"Function: {response['name']}")
        print(f"Arguments: {response['arguments']}")
        
        # Execute function and return result
        balance = get_account_balance(**response['arguments'])
        
        # Continue conversation
        messages.append({
            "role": "function",
            "name": response['name'],
            "content": json.dumps({"balance": balance})
        })
        
        final_response = client.chat_completion(messages)
        print(f"Final response: {final_response['content']}")


def get_account_balance(account_number: str) -> float:
    """Mock function to get account balance"""
    return 15234.50
```

## 2. Anthropic Claude API Integration

```python
import anthropic
import os
from typing import List, Dict, Generator

class ClaudeClient:
    """
    Anthropic Claude API client
    """
    
    def __init__(self, model: str = "claude-3-sonnet-20240229"):
        self.client = anthropic.Anthropic(api_key=os.environ['ANTHROPIC_API_KEY'])
        self.model = model
    
    def create_message(self, messages: List[Dict], system: str = None,
                      max_tokens: int = 1024, temperature: float = 1.0) -> Dict:
        """
        Create a message with Claude
        """
        kwargs = {
            'model': self.model,
            'max_tokens': max_tokens,
            'temperature': temperature,
            'messages': messages
        }
        
        if system:
            kwargs['system'] = system
        
        response = self.client.messages.create(**kwargs)
        
        return {
            'content': response.content[0].text,
            'stop_reason': response.stop_reason,
            'usage': {
                'input_tokens': response.usage.input_tokens,
                'output_tokens': response.usage.output_tokens
            },
            'model': response.model
        }
    
    def stream_message(self, messages: List[Dict], system: str = None,
                      max_tokens: int = 1024) -> Generator:
        """
        Stream message response
        """
        kwargs = {
            'model': self.model,
            'max_tokens': max_tokens,
            'messages': messages
        }
        
        if system:
            kwargs['system'] = system
        
        with self.client.messages.stream(**kwargs) as stream:
            for text in stream.text_stream:
                yield text
    
    def vision_analysis(self, image_url: str, prompt: str, 
                       max_tokens: int = 1024) -> Dict:
        """
        Analyze image with Claude
        """
        message = self.client.messages.create(
            model=self.model,
            max_tokens=max_tokens,
            messages=[
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "image",
                            "source": {
                                "type": "url",
                                "url": image_url,
                            },
                        },
                        {
                            "type": "text",
                            "text": prompt
                        }
                    ],
                }
            ],
        )
        
        return {
            'content': message.content[0].text,
            'usage': {
                'input_tokens': message.usage.input_tokens,
                'output_tokens': message.usage.output_tokens
            }
        }
    
    def tool_use(self, messages: List[Dict], tools: List[Dict],
                system: str = None, max_tokens: int = 1024) -> Dict:
        """
        Tool use (function calling) with Claude
        """
        kwargs = {
            'model': self.model,
            'max_tokens': max_tokens,
            'tools': tools,
            'messages': messages
        }
        
        if system:
            kwargs['system'] = system
        
        response = self.client.messages.create(**kwargs)
        
        # Check if tool was called
        if response.stop_reason == "tool_use":
            tool_use = next(
                block for block in response.content 
                if block.type == "tool_use"
            )
            
            return {
                'type': 'tool_use',
                'tool_name': tool_use.name,
                'tool_input': tool_use.input,
                'usage': {
                    'input_tokens': response.usage.input_tokens,
                    'output_tokens': response.usage.output_tokens
                }
            }
        else:
            return {
                'type': 'message',
                'content': response.content[0].text,
                'usage': {
                    'input_tokens': response.usage.input_tokens,
                    'output_tokens': response.usage.output_tokens
                }
            }


# Example usage
def example_claude_chat():
    """Basic Claude chat example"""
    client = ClaudeClient(model="claude-3-sonnet-20240229")
    
    response = client.create_message(
        messages=[
            {"role": "user", "content": "Explain RAG in one paragraph."}
        ],
        system="You are a helpful AI assistant.",
        max_tokens=200
    )
    
    print(f"Response: {response['content']}")
    print(f"Tokens: {response['usage']}")


def example_claude_streaming():
    """Claude streaming example"""
    client = ClaudeClient()
    
    print("Streaming response:")
    for chunk in client.stream_message(
        messages=[{"role": "user", "content": "Write a haiku about AI."}],
        system="You are a poetic assistant."
    ):
        print(chunk, end='', flush=True)
    print()
```

## 3. LangChain Integration

```python
from langchain.chat_models import ChatOpenAI
from langchain.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain.schema import HumanMessage, SystemMessage
from langchain.memory import ConversationBufferMemory
from langchain.chains import ConversationChain, LLMChain
from langchain.agents import AgentExecutor, create_openai_functions_agent
from langchain.tools import Tool

class LangChainRAGAgent:
    """
    LangChain-based RAG agent with tools
    """
    
    def __init__(self, model_name: str = "gpt-4-turbo-preview"):
        self.llm = ChatOpenAI(
            model_name=model_name,
            temperature=0.7,
            streaming=True
        )
        
        self.memory = ConversationBufferMemory(
            return_messages=True,
            memory_key="chat_history"
        )
    
    def create_tools(self) -> List[Tool]:
        """Define tools for the agent"""
        return [
            Tool(
                name="search_documents",
                func=self.search_documents,
                description="Search for relevant documents given a query"
            ),
            Tool(
                name="get_account_info",
                func=self.get_account_info,
                description="Get account information for a customer"
            ),
            Tool(
                name="calculate",
                func=self.calculate,
                description="Perform mathematical calculations"
            )
        ]
    
    def search_documents(self, query: str) -> str:
        """Search documents (mock implementation)"""
        # In production, this would call your RAG system
        return f"Found relevant documents for: {query}"
    
    def get_account_info(self, account_id: str) -> str:
        """Get account info (mock implementation)"""
        return f"Account {account_id}: Balance $10,000, Status: Active"
    
    def calculate(self, expression: str) -> str:
        """Safe calculation"""
        try:
            result = eval(expression, {"__builtins__": {}}, {})
            return str(result)
        except:
            return "Error: Invalid expression"
    
    def create_agent(self) -> AgentExecutor:
        """Create agent with tools"""
        tools = self.create_tools()
        
        prompt = ChatPromptTemplate.from_messages([
            SystemMessage(content="You are a helpful banking assistant with access to tools."),
            MessagesPlaceholder(variable_name="chat_history"),
            ("human", "{input}"),
            MessagesPlaceholder(variable_name="agent_scratchpad"),
        ])
        
        agent = create_openai_functions_agent(self.llm, tools, prompt)
        
        return AgentExecutor(
            agent=agent,
            tools=tools,
            memory=self.memory,
            verbose=True
        )
    
    def chat(self, message: str) -> str:
        """Chat with the agent"""
        agent_executor = self.create_agent()
        response = agent_executor.invoke({"input": message})
        return response['output']


# Example usage
def example_langchain_agent():
    """LangChain agent example"""
    agent = LangChainRAGAgent()
    
    # First query
    response1 = agent.chat("Search for loan eligibility criteria")
    print(f"Response 1: {response1}\n")
    
    # Follow-up query with memory
    response2 = agent.chat("What's the balance for account 12345?")
    print(f"Response 2: {response2}\n")
    
    # Calculation
    response3 = agent.chat("Calculate the monthly payment for a $100,000 loan at 5% for 30 years")
    print(f"Response 3: {response3}")
```

## 4. Cost Tracking and Monitoring

```python
import time
from dataclasses import dataclass
from typing import Dict, List
import json

@dataclass
class APICall:
    """Record of API call"""
    timestamp: float
    model: str
    prompt_tokens: int
    completion_tokens: int
    total_tokens: int
    cost: float
    endpoint: str
    user_id: str = None

class LLMCostTracker:
    """
    Track and analyze LLM API costs
    """
    
    # Pricing per 1k tokens (as of 2024)
    PRICING = {
        'gpt-4-turbo-preview': {'input': 0.01, 'output': 0.03},
        'gpt-4': {'input': 0.03, 'output': 0.06},
        'gpt-3.5-turbo': {'input': 0.0005, 'output': 0.0015},
        'claude-3-opus': {'input': 0.015, 'output': 0.075},
        'claude-3-sonnet': {'input': 0.003, 'output': 0.015},
        'claude-3-haiku': {'input': 0.00025, 'output': 0.00125},
        'text-embedding-ada-002': {'input': 0.0001, 'output': 0},
    }
    
    def __init__(self):
        self.calls: List[APICall] = []
    
    def calculate_cost(self, model: str, prompt_tokens: int, 
                      completion_tokens: int) -> float:
        """Calculate cost for API call"""
        pricing = self.PRICING.get(model, {'input': 0, 'output': 0})
        
        input_cost = (prompt_tokens / 1000) * pricing['input']
        output_cost = (completion_tokens / 1000) * pricing['output']
        
        return input_cost + output_cost
    
    def track_call(self, model: str, usage: Dict, endpoint: str = "chat",
                  user_id: str = None):
        """Track an API call"""
        cost = self.calculate_cost(
            model, 
            usage['prompt_tokens'], 
            usage.get('completion_tokens', 0)
        )
        
        call = APICall(
            timestamp=time.time(),
            model=model,
            prompt_tokens=usage['prompt_tokens'],
            completion_tokens=usage.get('completion_tokens', 0),
            total_tokens=usage['total_tokens'],
            cost=cost,
            endpoint=endpoint,
            user_id=user_id
        )
        
        self.calls.append(call)
    
    def get_total_cost(self, start_time: float = None, end_time: float = None) -> float:
        """Get total cost in time range"""
        filtered_calls = self.calls
        
        if start_time:
            filtered_calls = [c for c in filtered_calls if c.timestamp >= start_time]
        if end_time:
            filtered_calls = [c for c in filtered_calls if c.timestamp <= end_time]
        
        return sum(call.cost for call in filtered_calls)
    
    def get_stats(self) -> Dict:
        """Get usage statistics"""
        if not self.calls:
            return {}
        
        total_calls = len(self.calls)
        total_cost = sum(call.cost for call in self.calls)
        total_tokens = sum(call.total_tokens for call in self.calls)
        
        # Cost by model
        cost_by_model = {}
        for call in self.calls:
            cost_by_model[call.model] = cost_by_model.get(call.model, 0) + call.cost
        
        # Cost by user
        cost_by_user = {}
        for call in self.calls:
            if call.user_id:
                cost_by_user[call.user_id] = cost_by_user.get(call.user_id, 0) + call.cost
        
        return {
            'total_calls': total_calls,
            'total_cost': total_cost,
            'total_tokens': total_tokens,
            'average_cost_per_call': total_cost / total_calls,
            'cost_by_model': cost_by_model,
            'cost_by_user': cost_by_user
        }
    
    def export_to_json(self, filename: str):
        """Export tracking data to JSON"""
        data = [
            {
                'timestamp': call.timestamp,
                'model': call.model,
                'prompt_tokens': call.prompt_tokens,
                'completion_tokens': call.completion_tokens,
                'total_tokens': call.total_tokens,
                'cost': call.cost,
                'endpoint': call.endpoint,
                'user_id': call.user_id
            }
            for call in self.calls
        ]
        
        with open(filename, 'w') as f:
            json.dump(data, f, indent=2)


# Example usage
def example_cost_tracking():
    """Cost tracking example"""
    tracker = LLMCostTracker()
    client = OpenAIClient()
    
    # Make API call
    messages = [
        {"role": "user", "content": "Explain quantum computing"}
    ]
    
    response = client.chat_completion(messages)
    
    # Track the call
    tracker.track_call(
        model=response['model'],
        usage=response['usage'],
        endpoint='chat',
        user_id='user_123'
    )
    
    # Get statistics
    stats = tracker.get_stats()
    print(f"Total cost: ${stats['total_cost']:.4f}")
    print(f"Total tokens: {stats['total_tokens']}")
    print(f"Cost by model: {stats['cost_by_model']}")
    
    # Export data
    tracker.export_to_json('llm_usage.json')
```
