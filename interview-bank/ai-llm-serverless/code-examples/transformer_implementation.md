# Transformer Architecture Implementation

## 1. Scaled Dot-Product Attention

```python
import torch
import torch.nn as nn
import math

def scaled_dot_product_attention(query, key, value, mask=None, dropout=None):
    """
    Compute scaled dot-product attention
    
    Args:
        query: Query tensor of shape (batch, heads, seq_len, d_k)
        key: Key tensor of shape (batch, heads, seq_len, d_k)
        value: Value tensor of shape (batch, heads, seq_len, d_v)
        mask: Optional mask tensor
        dropout: Optional dropout layer
        
    Returns:
        output: Attention output
        attention_weights: Attention weights for visualization
    """
    d_k = query.size(-1)
    
    # Compute attention scores: Q * K^T / sqrt(d_k)
    scores = torch.matmul(query, key.transpose(-2, -1)) / math.sqrt(d_k)
    
    # Apply mask if provided (for padding or causal attention)
    if mask is not None:
        scores = scores.masked_fill(mask == 0, -1e9)
    
    # Apply softmax to get attention weights
    attention_weights = torch.softmax(scores, dim=-1)
    
    # Apply dropout if provided
    if dropout is not None:
        attention_weights = dropout(attention_weights)
    
    # Compute weighted sum of values
    output = torch.matmul(attention_weights, value)
    
    return output, attention_weights


class AttentionHead(nn.Module):
    """Single attention head"""
    
    def __init__(self, d_model, d_k):
        super().__init__()
        self.d_k = d_k
        
        # Linear projections for Q, K, V
        self.query = nn.Linear(d_model, d_k)
        self.key = nn.Linear(d_model, d_k)
        self.value = nn.Linear(d_model, d_k)
        
    def forward(self, query, key, value, mask=None):
        # Project inputs
        Q = self.query(query)
        K = self.key(key)
        V = self.value(value)
        
        # Compute attention
        output, attn_weights = scaled_dot_product_attention(Q, K, V, mask)
        
        return output, attn_weights
```

## 2. Multi-Head Attention

```python
class MultiHeadAttention(nn.Module):
    """
    Multi-head attention mechanism
    Allows model to attend to information from different representation subspaces
    """
    
    def __init__(self, d_model=512, num_heads=8, dropout=0.1):
        super().__init__()
        
        assert d_model % num_heads == 0, "d_model must be divisible by num_heads"
        
        self.d_model = d_model
        self.num_heads = num_heads
        self.d_k = d_model // num_heads
        
        # Linear projections for Q, K, V
        self.W_q = nn.Linear(d_model, d_model)
        self.W_k = nn.Linear(d_model, d_model)
        self.W_v = nn.Linear(d_model, d_model)
        
        # Output projection
        self.W_o = nn.Linear(d_model, d_model)
        
        self.dropout = nn.Dropout(dropout)
        
    def split_heads(self, x, batch_size):
        """
        Split the last dimension into (num_heads, d_k)
        Transpose to shape: (batch, num_heads, seq_len, d_k)
        """
        x = x.view(batch_size, -1, self.num_heads, self.d_k)
        return x.transpose(1, 2)
    
    def forward(self, query, key, value, mask=None):
        batch_size = query.size(0)
        
        # Linear projections
        Q = self.W_q(query)
        K = self.W_k(key)
        V = self.W_v(value)
        
        # Split into multiple heads
        Q = self.split_heads(Q, batch_size)
        K = self.split_heads(K, batch_size)
        V = self.split_heads(V, batch_size)
        
        # Apply attention
        attn_output, attn_weights = scaled_dot_product_attention(
            Q, K, V, mask, self.dropout
        )
        
        # Concatenate heads
        attn_output = attn_output.transpose(1, 2).contiguous()
        attn_output = attn_output.view(batch_size, -1, self.d_model)
        
        # Final linear projection
        output = self.W_o(attn_output)
        
        return output, attn_weights
```

## 3. Positional Encoding

```python
class PositionalEncoding(nn.Module):
    """
    Sinusoidal positional encoding
    PE(pos, 2i) = sin(pos / 10000^(2i/d_model))
    PE(pos, 2i+1) = cos(pos / 10000^(2i/d_model))
    """
    
    def __init__(self, d_model, max_len=5000, dropout=0.1):
        super().__init__()
        self.dropout = nn.Dropout(p=dropout)
        
        # Create positional encoding matrix
        pe = torch.zeros(max_len, d_model)
        position = torch.arange(0, max_len, dtype=torch.float).unsqueeze(1)
        div_term = torch.exp(
            torch.arange(0, d_model, 2).float() * 
            (-math.log(10000.0) / d_model)
        )
        
        # Apply sin to even indices
        pe[:, 0::2] = torch.sin(position * div_term)
        # Apply cos to odd indices
        pe[:, 1::2] = torch.cos(position * div_term)
        
        pe = pe.unsqueeze(0)  # Add batch dimension
        
        # Register as buffer (not a parameter, but part of state)
        self.register_buffer('pe', pe)
        
    def forward(self, x):
        """
        Args:
            x: Tensor of shape (batch, seq_len, d_model)
        """
        x = x + self.pe[:, :x.size(1)]
        return self.dropout(x)


class LearnedPositionalEmbedding(nn.Module):
    """Learned positional embeddings (alternative to sinusoidal)"""
    
    def __init__(self, max_len, d_model):
        super().__init__()
        self.embedding = nn.Embedding(max_len, d_model)
        
    def forward(self, x):
        """
        Args:
            x: Tensor of shape (batch, seq_len, d_model)
        """
        batch_size, seq_len = x.size(0), x.size(1)
        positions = torch.arange(seq_len, device=x.device).unsqueeze(0)
        positions = positions.expand(batch_size, -1)
        
        pos_embeddings = self.embedding(positions)
        return x + pos_embeddings


class RotaryPositionalEmbedding(nn.Module):
    """
    Rotary Position Embedding (RoPE)
    Used in models like LLaMA, GPT-Neo-X
    """
    
    def __init__(self, dim, max_len=2048, base=10000):
        super().__init__()
        inv_freq = 1.0 / (base ** (torch.arange(0, dim, 2).float() / dim))
        self.register_buffer('inv_freq', inv_freq)
        self.max_len = max_len
        
    def forward(self, x, seq_len=None):
        """Apply rotary embeddings to input tensor"""
        if seq_len is None:
            seq_len = x.shape[-2]
        
        t = torch.arange(seq_len, device=x.device).type_as(self.inv_freq)
        freqs = torch.einsum('i,j->ij', t, self.inv_freq)
        emb = torch.cat((freqs, freqs), dim=-1)
        
        return emb.cos(), emb.sin()
    
    def rotate_half(self, x):
        """Rotate half the dimensions"""
        x1, x2 = x[..., :x.shape[-1] // 2], x[..., x.shape[-1] // 2:]
        return torch.cat((-x2, x1), dim=-1)
    
    def apply_rotary_pos_emb(self, q, k, cos, sin):
        """Apply rotary embeddings to queries and keys"""
        q_embed = (q * cos) + (self.rotate_half(q) * sin)
        k_embed = (k * cos) + (self.rotate_half(k) * sin)
        return q_embed, k_embed
```

## 4. Feed-Forward Network

```python
class PositionWiseFeedForward(nn.Module):
    """
    Position-wise Feed-Forward Network
    FFN(x) = max(0, xW1 + b1)W2 + b2
    """
    
    def __init__(self, d_model, d_ff, dropout=0.1, activation='relu'):
        super().__init__()
        
        self.linear1 = nn.Linear(d_model, d_ff)
        self.linear2 = nn.Linear(d_ff, d_model)
        self.dropout = nn.Dropout(dropout)
        
        # Activation function
        if activation == 'relu':
            self.activation = nn.ReLU()
        elif activation == 'gelu':
            self.activation = nn.GELU()
        else:
            raise ValueError(f"Unsupported activation: {activation}")
    
    def forward(self, x):
        """
        Args:
            x: Input tensor of shape (batch, seq_len, d_model)
        Returns:
            Output tensor of same shape
        """
        x = self.linear1(x)
        x = self.activation(x)
        x = self.dropout(x)
        x = self.linear2(x)
        return x


class SwiGLU(nn.Module):
    """
    SwiGLU activation (used in LLaMA, PaLM)
    SwiGLU(x, W, V) = Swish(xW) ⊗ xV
    """
    
    def __init__(self, d_model, d_ff, dropout=0.1):
        super().__init__()
        
        self.w1 = nn.Linear(d_model, d_ff, bias=False)
        self.w2 = nn.Linear(d_ff, d_model, bias=False)
        self.w3 = nn.Linear(d_model, d_ff, bias=False)
        self.dropout = nn.Dropout(dropout)
        
    def forward(self, x):
        """SwiGLU: Swish(xW1) * xW3 then project with W2"""
        swish_out = torch.nn.functional.silu(self.w1(x))
        gated = swish_out * self.w3(x)
        gated = self.dropout(gated)
        return self.w2(gated)
```

## 5. Layer Normalization

```python
class LayerNorm(nn.Module):
    """
    Layer Normalization
    Normalizes across the feature dimension
    """
    
    def __init__(self, d_model, eps=1e-6):
        super().__init__()
        self.gamma = nn.Parameter(torch.ones(d_model))
        self.beta = nn.Parameter(torch.zeros(d_model))
        self.eps = eps
        
    def forward(self, x):
        """
        Args:
            x: Input tensor of shape (batch, seq_len, d_model)
        """
        mean = x.mean(dim=-1, keepdim=True)
        std = x.std(dim=-1, keepdim=True)
        
        # Normalize
        normalized = (x - mean) / (std + self.eps)
        
        # Scale and shift
        return self.gamma * normalized + self.beta


class RMSNorm(nn.Module):
    """
    Root Mean Square Layer Normalization
    More efficient than LayerNorm, used in T5, LLaMA
    """
    
    def __init__(self, d_model, eps=1e-6):
        super().__init__()
        self.weight = nn.Parameter(torch.ones(d_model))
        self.eps = eps
        
    def forward(self, x):
        """
        Args:
            x: Input tensor of shape (batch, seq_len, d_model)
        """
        # Compute RMS
        rms = torch.sqrt(torch.mean(x ** 2, dim=-1, keepdim=True) + self.eps)
        
        # Normalize and scale
        return self.weight * x / rms
```

## 6. Transformer Encoder Layer

```python
class TransformerEncoderLayer(nn.Module):
    """
    Single Transformer encoder layer
    Consists of multi-head self-attention and feed-forward network
    """
    
    def __init__(self, d_model=512, num_heads=8, d_ff=2048, dropout=0.1):
        super().__init__()
        
        # Multi-head self-attention
        self.self_attn = MultiHeadAttention(d_model, num_heads, dropout)
        
        # Feed-forward network
        self.ffn = PositionWiseFeedForward(d_model, d_ff, dropout)
        
        # Layer normalization
        self.norm1 = LayerNorm(d_model)
        self.norm2 = LayerNorm(d_model)
        
        # Dropout
        self.dropout1 = nn.Dropout(dropout)
        self.dropout2 = nn.Dropout(dropout)
        
    def forward(self, x, mask=None):
        """
        Args:
            x: Input tensor (batch, seq_len, d_model)
            mask: Optional attention mask
        """
        # Multi-head attention with residual connection
        attn_output, _ = self.self_attn(x, x, x, mask)
        x = x + self.dropout1(attn_output)
        x = self.norm1(x)
        
        # Feed-forward with residual connection
        ffn_output = self.ffn(x)
        x = x + self.dropout2(ffn_output)
        x = self.norm2(x)
        
        return x


class TransformerEncoder(nn.Module):
    """Complete Transformer encoder (stack of encoder layers)"""
    
    def __init__(self, num_layers=6, d_model=512, num_heads=8, 
                 d_ff=2048, dropout=0.1, max_len=5000):
        super().__init__()
        
        # Positional encoding
        self.pos_encoding = PositionalEncoding(d_model, max_len, dropout)
        
        # Stack of encoder layers
        self.layers = nn.ModuleList([
            TransformerEncoderLayer(d_model, num_heads, d_ff, dropout)
            for _ in range(num_layers)
        ])
        
        self.norm = LayerNorm(d_model)
        
    def forward(self, x, mask=None):
        """
        Args:
            x: Input embeddings (batch, seq_len, d_model)
            mask: Optional attention mask
        """
        # Add positional encoding
        x = self.pos_encoding(x)
        
        # Pass through encoder layers
        for layer in self.layers:
            x = layer(x, mask)
        
        return self.norm(x)
```

## 7. Transformer Decoder Layer

```python
class TransformerDecoderLayer(nn.Module):
    """
    Single Transformer decoder layer
    Includes masked self-attention, cross-attention, and feed-forward
    """
    
    def __init__(self, d_model=512, num_heads=8, d_ff=2048, dropout=0.1):
        super().__init__()
        
        # Masked multi-head self-attention
        self.self_attn = MultiHeadAttention(d_model, num_heads, dropout)
        
        # Multi-head cross-attention (attend to encoder output)
        self.cross_attn = MultiHeadAttention(d_model, num_heads, dropout)
        
        # Feed-forward network
        self.ffn = PositionWiseFeedForward(d_model, d_ff, dropout)
        
        # Layer normalization
        self.norm1 = LayerNorm(d_model)
        self.norm2 = LayerNorm(d_model)
        self.norm3 = LayerNorm(d_model)
        
        # Dropout
        self.dropout1 = nn.Dropout(dropout)
        self.dropout2 = nn.Dropout(dropout)
        self.dropout3 = nn.Dropout(dropout)
        
    def forward(self, x, encoder_output, src_mask=None, tgt_mask=None):
        """
        Args:
            x: Decoder input (batch, tgt_seq_len, d_model)
            encoder_output: Encoder output (batch, src_seq_len, d_model)
            src_mask: Source attention mask
            tgt_mask: Target attention mask (causal mask)
        """
        # Masked self-attention
        self_attn_output, _ = self.self_attn(x, x, x, tgt_mask)
        x = x + self.dropout1(self_attn_output)
        x = self.norm1(x)
        
        # Cross-attention to encoder output
        cross_attn_output, _ = self.cross_attn(x, encoder_output, encoder_output, src_mask)
        x = x + self.dropout2(cross_attn_output)
        x = self.norm2(x)
        
        # Feed-forward
        ffn_output = self.ffn(x)
        x = x + self.dropout3(ffn_output)
        x = self.norm3(x)
        
        return x


def create_causal_mask(seq_len):
    """
    Create causal mask for autoregressive generation
    Prevents attending to future positions
    """
    mask = torch.triu(torch.ones(seq_len, seq_len), diagonal=1).bool()
    return ~mask  # Invert: True means can attend
```

## 8. Complete Transformer Model

```python
class Transformer(nn.Module):
    """
    Complete Transformer model (encoder-decoder)
    """
    
    def __init__(self, src_vocab_size, tgt_vocab_size, d_model=512, 
                 num_heads=8, num_encoder_layers=6, num_decoder_layers=6,
                 d_ff=2048, max_len=5000, dropout=0.1):
        super().__init__()
        
        # Embeddings
        self.src_embedding = nn.Embedding(src_vocab_size, d_model)
        self.tgt_embedding = nn.Embedding(tgt_vocab_size, d_model)
        
        # Positional encoding
        self.pos_encoding = PositionalEncoding(d_model, max_len, dropout)
        
        # Encoder
        self.encoder = TransformerEncoder(
            num_encoder_layers, d_model, num_heads, d_ff, dropout, max_len
        )
        
        # Decoder
        self.decoder_layers = nn.ModuleList([
            TransformerDecoderLayer(d_model, num_heads, d_ff, dropout)
            for _ in range(num_decoder_layers)
        ])
        
        # Output projection
        self.output_projection = nn.Linear(d_model, tgt_vocab_size)
        
        self.d_model = d_model
        
    def forward(self, src, tgt, src_mask=None, tgt_mask=None):
        """
        Args:
            src: Source sequences (batch, src_seq_len)
            tgt: Target sequences (batch, tgt_seq_len)
            src_mask: Source attention mask
            tgt_mask: Target attention mask
        """
        # Embed and encode source
        src_embedded = self.src_embedding(src) * math.sqrt(self.d_model)
        encoder_output = self.encoder(src_embedded, src_mask)
        
        # Embed target
        tgt_embedded = self.tgt_embedding(tgt) * math.sqrt(self.d_model)
        tgt_embedded = self.pos_encoding(tgt_embedded)
        
        # Decode
        decoder_output = tgt_embedded
        for layer in self.decoder_layers:
            decoder_output = layer(decoder_output, encoder_output, src_mask, tgt_mask)
        
        # Project to vocabulary
        output = self.output_projection(decoder_output)
        
        return output
```
