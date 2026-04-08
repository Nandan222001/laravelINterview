# Web Technologies Interview Questions

## HTML/CSS/JavaScript - Comprehensive Question Bank

This document contains interview questions covering HTML, CSS, JavaScript, AJAX, jQuery, DOM manipulation, event handling, promises, async/await, semantics, HTML5, form validation, checkbox handling, and third-party API integration.

---

## Table of Contents

1. [HTML Fundamentals & Semantics](#html-fundamentals--semantics)
2. [HTML5 Features & Differences](#html5-features--differences)
3. [CSS Fundamentals & Advanced](#css-fundamentals--advanced)
4. [JavaScript Core Concepts](#javascript-core-concepts)
5. [DOM Manipulation](#dom-manipulation)
6. [Event Handling](#event-handling)
7. [AJAX & HTTP Requests](#ajax--http-requests)
8. [jQuery](#jquery)
9. [Promises & Async Programming](#promises--async-programming)
10. [Form Validation](#form-validation)
11. [Checkbox & Input Handling](#checkbox--input-handling)
12. [Third-Party API Integration](#third-party-api-integration)
13. [Modern JavaScript Features](#modern-javascript-features)
14. [Performance & Optimization](#performance--optimization)

---

## HTML Fundamentals & Semantics

### ⭐ Question 1: What is semantic HTML and why is it important?

**Expected Answer:**
Semantic HTML uses HTML elements that clearly describe their meaning to both the browser and developer. Instead of using generic `<div>` and `<span>` tags, semantic elements like `<header>`, `<nav>`, `<article>`, `<section>`, `<aside>`, and `<footer>` convey the structure and purpose of content.

**Benefits:**
- Better accessibility for screen readers
- Improved SEO as search engines understand content structure
- Easier code maintenance and readability
- Better default styling and behavior

**Example:**
```html
<!-- Non-semantic -->
<div class="header">
  <div class="nav">
    <a href="/">Home</a>
  </div>
</div>

<!-- Semantic -->
<header>
  <nav>
    <a href="/">Home</a>
  </nav>
</header>
```

---

## HTML5 Features & Differences

### ⭐⭐ Question 2: What are the major differences between HTML4 and HTML5?

**Expected Answer:**

**New Features in HTML5:**
1. **Semantic Elements**: `<header>`, `<nav>`, `<article>`, `<section>`, `<footer>`, etc.
2. **Multimedia Support**: `<video>` and `<audio>` elements without plugins
3. **Graphics**: `<canvas>` for 2D drawing, `<svg>` for scalable graphics
4. **Form Controls**: New input types (email, url, date, color, range, etc.)
5. **APIs**: Geolocation, Local Storage, Session Storage, Web Workers, WebSocket
6. **Offline Support**: Application cache, Service Workers

**Removed Elements:**
- `<font>`, `<center>`, `<frame>`, `<frameset>`, `<big>`, `<strike>`

**Doctype Simplification:**
```html
<!-- HTML4 -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">

<!-- HTML5 -->
<!DOCTYPE html>
```

### ⭐⭐ Question 3: Explain HTML5 Web Storage (localStorage vs sessionStorage).

**Expected Answer:**

Both are part of the Web Storage API for storing key-value pairs in the browser.

**localStorage:**
- Persists even after browser is closed
- No expiration time
- Shared across all tabs/windows of same origin
- Storage limit: ~5-10MB

**sessionStorage:**
- Data persists only for the page session
- Cleared when tab/window is closed
- Separate for each tab/window
- Storage limit: ~5-10MB

```javascript
// localStorage
localStorage.setItem('username', 'john_doe');
const user = localStorage.getItem('username');
localStorage.removeItem('username');
localStorage.clear();

// sessionStorage
sessionStorage.setItem('sessionId', '12345');
const session = sessionStorage.getItem('sessionId');

// Store objects (JSON)
localStorage.setItem('user', JSON.stringify({name: 'John', age: 30}));
const user = JSON.parse(localStorage.getItem('user'));
```

---

## CSS Fundamentals & Advanced

### ⭐ Question 4: What is the CSS Box Model?

**Expected Answer:**

The CSS box model describes the rectangular boxes generated for elements. Each box has four areas:

1. **Content**: The actual content (text, images)
2. **Padding**: Space between content and border
3. **Border**: Border around padding
4. **Margin**: Space outside the border

```css
.box {
  width: 300px;
  height: 200px;
  padding: 20px;
  border: 5px solid black;
  margin: 10px;
}

/* Total width = 300 + 20*2 + 5*2 + 10*2 = 370px */

.box-border-box {
  box-sizing: border-box;
  width: 300px; /* Total width is 300px */
  padding: 20px;
  border: 5px solid black;
}
```

### ⭐⭐ Question 5: Explain CSS Flexbox and its main properties.

**Expected Answer:**

Flexbox is a one-dimensional layout method for arranging items in rows or columns.

**Container Properties:**
```css
.container {
  display: flex;
  flex-direction: row | column;
  justify-content: flex-start | center | space-between;
  align-items: flex-start | center | flex-end | stretch;
  flex-wrap: nowrap | wrap;
  gap: 10px;
}
```

**Item Properties:**
```css
.item {
  flex-grow: 1;
  flex-shrink: 1;
  flex-basis: 200px;
  flex: 1 1 200px; /* Shorthand */
  align-self: center;
  order: 2;
}
```

---

## JavaScript Core Concepts

### ⭐ Question 6: What are the differences between `var`, `let`, and `const`?

**Expected Answer:**

| Feature | var | let | const |
|---------|-----|-----|-------|
| Scope | Function | Block | Block |
| Hoisting | Yes (undefined) | Yes (TDZ) | Yes (TDZ) |
| Re-declaration | Yes | No | No |
| Re-assignment | Yes | Yes | No |

```javascript
// var - function scoped
function varExample() {
  console.log(x); // undefined (hoisted)
  var x = 5;
  if (true) {
    var x = 10; // Same variable
  }
  console.log(x); // 10
}

// let - block scoped
function letExample() {
  let y = 5;
  if (true) {
    let y = 10; // Different variable
    console.log(y); // 10
  }
  console.log(y); // 5
}

// const - immutable binding
const PI = 3.14159;
const obj = { name: 'John' };
obj.name = 'Jane'; // OK
```

### ⭐⭐ Question 7: Explain closures in JavaScript with examples.

**Expected Answer:**

A closure is a function that has access to variables in its outer lexical scope, even after the outer function has returned.

```javascript
function createCounter() {
  let count = 0;
  
  return {
    increment: function() {
      count++;
      return count;
    },
    decrement: function() {
      count--;
      return count;
    },
    getCount: function() {
      return count;
    }
  };
}

const counter = createCounter();
console.log(counter.increment()); // 1
console.log(counter.increment()); // 2
console.log(counter.getCount()); // 2
```

---

## DOM Manipulation

### ⭐ Question 8: How do you select elements in the DOM?

**Expected Answer:**

```javascript
// By ID
const element = document.getElementById('myId');

// By class name
const elements = document.getElementsByClassName('myClass');

// By tag name
const divs = document.getElementsByTagName('div');

// Query selector
const first = document.querySelector('.myClass');
const all = document.querySelectorAll('.myClass');

// Convert to array
const array = Array.from(document.querySelectorAll('.item'));
```

### ⭐⭐ Question 9: How do you create, modify, and remove DOM elements?

**Expected Answer:**

```javascript
// Creating elements
const div = document.createElement('div');
div.id = 'myDiv';
div.className = 'container';
div.classList.add('active');
div.textContent = 'Plain text';
div.innerHTML = '<strong>HTML content</strong>';

// Appending elements
const parent = document.getElementById('parent');
parent.appendChild(div);
parent.append(div, 'text'); // Can append multiple

// Removing elements
element.remove();
parent.removeChild(element);

// Cloning
const clone = element.cloneNode(true); // Deep clone
```

### ⭐⭐⭐ Question 10: Explain the difference between `innerHTML`, `textContent`, and `innerText`.

**Expected Answer:**

```javascript
// innerHTML - parses HTML, includes tags
div.innerHTML = '<p>New <em>content</em></p>';

// textContent - all text, faster, safer
div.textContent = '<p>New content</p>'; // Shows as text

// innerText - visible text only, respects CSS (slower)
console.log(div.innerText); // Only visible text

// Security comparison
const userInput = '<img src=x onerror=alert(1)>';
div.innerHTML = userInput; // XSS vulnerability!
div.textContent = userInput; // Safe - displays as text
```

---

## Event Handling

### ⭐ Question 11: How do you add event listeners to elements?

**Expected Answer:**

```javascript
const button = document.getElementById('myButton');

// Modern approach
button.addEventListener('click', function(event) {
  console.log('Clicked!', event);
});

// Named function (easier to remove)
function handleClick(event) {
  console.log('Clicked!');
}
button.addEventListener('click', handleClick);
button.removeEventListener('click', handleClick);

// Options
button.addEventListener('click', handleClick, {
  once: true,
  passive: true,
  capture: true
});
```

### ⭐⭐ Question 12: Explain event bubbling, capturing, and delegation.

**Expected Answer:**

**Event Propagation Phases:**
1. **Capturing phase**: Event goes from window down to target
2. **Target phase**: Event reaches target element
3. **Bubbling phase**: Event bubbles up from target to window

```javascript
// Bubbling (default)
button.addEventListener('click', () => console.log('Button'));
inner.addEventListener('click', () => console.log('Inner'));
outer.addEventListener('click', () => console.log('Outer'));
// Output: Button -> Inner -> Outer

// Stop propagation
inner.addEventListener('click', (e) => {
  e.stopPropagation();
  console.log('Inner - stopped');
});

// Event Delegation
const list = document.getElementById('list');
list.addEventListener('click', function(e) {
  if (e.target.matches('li.item')) {
    console.log('Item clicked:', e.target.textContent);
  }
});
```

### ⭐⭐⭐ Question 13: Implement debouncing and throttling for event handlers.

**Expected Answer:**

```javascript
// DEBOUNCING - Execute after waiting period
function debounce(func, delay) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      func.apply(this, args);
    }, delay);
  };
}

// Usage
const handleSearch = debounce(function(e) {
  console.log('Searching for:', e.target.value);
  fetch(`/api/search?q=${e.target.value}`);
}, 300);

// THROTTLING - Execute at most once per time period
function throttle(func, limit) {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => {
        inThrottle = false;
      }, limit);
    }
  };
}

// Usage
const handleScroll = throttle(function() {
  console.log('Scroll position:', window.scrollY);
}, 100);
```

---

## AJAX & HTTP Requests

### ⭐⭐ Question 14: What is AJAX and how does it work?

**Expected Answer:**

AJAX (Asynchronous JavaScript and XML) allows web pages to update asynchronously by exchanging data with a server without reloading the page.

```javascript
// XMLHttpRequest
const xhr = new XMLHttpRequest();
xhr.onreadystatechange = function() {
  if (xhr.readyState === 4 && xhr.status === 200) {
    const data = JSON.parse(xhr.responseText);
    console.log(data);
  }
};
xhr.open('GET', '/api/data', true);
xhr.setRequestHeader('Content-Type', 'application/json');
xhr.send();

// POST request
xhr.open('POST', '/api/users', true);
xhr.setRequestHeader('Content-Type', 'application/json');
const userData = { name: 'John', email: 'john@example.com' };
xhr.send(JSON.stringify(userData));
```

### ⭐⭐⭐ Question 15: Compare XMLHttpRequest, Fetch API, and Axios.

**Expected Answer:**

```javascript
// Fetch API (Modern)
async function fetchRequest() {
  try {
    const response = await fetch('/api/users', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer token123'
      }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Fetch error:', error);
    throw error;
  }
}

// Fetch POST
async function fetchPost(userData) {
  const response = await fetch('/api/users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(userData)
  });
  return await response.json();
}

// Axios (requires library)
async function axiosRequest() {
  try {
    const response = await axios.get('/api/users', {
      headers: { 'Authorization': 'Bearer token123' },
      timeout: 5000
    });
    return response.data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}
```

### ⭐⭐ Question 16: How do you handle CORS issues?

**Expected Answer:**

CORS (Cross-Origin Resource Sharing) is a security mechanism that restricts web pages from making requests to a different domain.

```javascript
// Client-side
fetch('https://api.example.com/data', {
  credentials: 'include' // Send cookies
})
  .then(response => response.json())
  .catch(error => console.error('CORS error:', error));

// Server-side headers required (Node.js/Express example)
/*
app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', 'https://myapp.com');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  
  if (req.method === 'OPTIONS') {
    return res.status(204).end();
  }
  next();
});
*/
```

---

## jQuery

### ⭐ Question 17: What is jQuery and when would you use it?

**Expected Answer:**

jQuery is a fast, small JavaScript library that simplifies DOM manipulation, event handling, animation, and AJAX.

```javascript
// jQuery
$('#myElement').addClass('active');
$('.item').hide();
$('#form').on('submit', handleSubmit);

// Vanilla JS equivalent
document.getElementById('myElement').classList.add('active');
document.querySelectorAll('.item').forEach(el => el.style.display = 'none');
document.getElementById('form').addEventListener('submit', handleSubmit);
```

### ⭐⭐ Question 18: Compare jQuery AJAX with Fetch API.

**Expected Answer:**

```javascript
// jQuery AJAX
$.ajax({
  url: '/api/users',
  method: 'GET',
  dataType: 'json',
  success: function(data) {
    console.log('Success:', data);
  },
  error: function(xhr, status, error) {
    console.error('Error:', status, error);
  }
});

// jQuery shorthand
$.get('/api/users', function(data) {
  console.log(data);
});

// Fetch API
fetch('/api/users')
  .then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch(error => console.error('Error:', error));

// Async/await with Fetch
async function fetchData() {
  try {
    const response = await fetch('/api/users');
    const data = await response.json();
    console.log(data);
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

## Promises & Async Programming

### ⭐⭐ Question 19: Explain Promises and their states.

**Expected Answer:**

A Promise is an object representing the eventual completion or failure of an asynchronous operation.

**Three states:**
1. **Pending**: Initial state
2. **Fulfilled**: Operation completed successfully
3. **Rejected**: Operation failed

```javascript
// Creating a Promise
const promise = new Promise((resolve, reject) => {
  setTimeout(() => {
    const success = true;
    if (success) {
      resolve('Success!');
    } else {
      reject(new Error('Failed!'));
    }
  }, 1000);
});

// Consuming
promise
  .then(result => console.log('Fulfilled:', result))
  .catch(error => console.error('Rejected:', error))
  .finally(() => console.log('Cleanup'));

// Promise.all - Wait for all
Promise.all([p1, p2, p3])
  .then(([r1, r2, r3]) => console.log('All loaded'))
  .catch(error => console.error('One failed:', error));

// Promise.race - First to settle
Promise.race([p1, p2, p3])
  .then(first => console.log('First:', first));
```

### ⭐⭐⭐ Question 20: Explain async/await and how it relates to Promises.

**Expected Answer:**

`async/await` is syntactic sugar over Promises, making asynchronous code look synchronous.

```javascript
// Promise-based
function fetchUserData() {
  return fetch('/api/user')
    .then(response => response.json())
    .then(user => fetch(`/api/posts?userId=${user.id}`))
    .then(response => response.json());
}

// Async/await (cleaner)
async function fetchUserData() {
  try {
    const userResponse = await fetch('/api/user');
    const user = await userResponse.json();
    
    const postsResponse = await fetch(`/api/posts?userId=${user.id}`);
    const posts = await postsResponse.json();
    
    return posts;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}

// Parallel execution
async function parallelRequests() {
  const [users, posts] = await Promise.all([
    fetch('/api/users').then(r => r.json()),
    fetch('/api/posts').then(r => r.json())
  ]);
  return { users, posts };
}
```

---

## Form Validation

### ⭐⭐ Question 21: How do you implement client-side form validation?

**Expected Answer:**

```javascript
// HTML5 Built-in Validation
<form id="myForm">
  <input type="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
  <input type="number" min="1" max="100">
  <input type="text" minlength="3" maxlength="20">
  <button type="submit">Submit</button>
</form>

// JavaScript Validation
const form = document.getElementById('myForm');
form.addEventListener('submit', function(e) {
  e.preventDefault();
  
  const email = form.email.value;
  const password = form.password.value;
  
  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showError('email', 'Invalid email address');
    return;
  }
  
  // Password validation
  if (password.length < 8) {
    showError('password', 'Password must be at least 8 characters');
    return;
  }
  
  // Submit form
  submitForm(new FormData(form));
});

function showError(field, message) {
  const errorElement = document.getElementById(`${field}-error`);
  errorElement.textContent = message;
  errorElement.style.display = 'block';
}
```

### ⭐⭐ Question 22: Implement real-time form validation.

**Expected Answer:**

```javascript
const emailInput = document.getElementById('email');
const emailError = document.getElementById('email-error');

// Real-time validation with debounce
const validateEmail = debounce(function() {
  const email = emailInput.value;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  
  if (!email) {
    emailError.textContent = 'Email is required';
    emailInput.classList.add('invalid');
    emailInput.classList.remove('valid');
  } else if (!emailRegex.test(email)) {
    emailError.textContent = 'Invalid email format';
    emailInput.classList.add('invalid');
    emailInput.classList.remove('valid');
  } else {
    emailError.textContent = '';
    emailInput.classList.remove('invalid');
    emailInput.classList.add('valid');
  }
}, 300);

emailInput.addEventListener('input', validateEmail);

// Password strength indicator
const passwordInput = document.getElementById('password');
const strengthIndicator = document.getElementById('strength');

passwordInput.addEventListener('input', function() {
  const password = this.value;
  let strength = 0;
  
  if (password.length >= 8) strength++;
  if (password.match(/[a-z]+/)) strength++;
  if (password.match(/[A-Z]+/)) strength++;
  if (password.match(/[0-9]+/)) strength++;
  if (password.match(/[$@#&!]+/)) strength++;
  
  const strengths = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
  strengthIndicator.textContent = strengths[strength - 1] || 'Too Short';
});
```

### ⭐⭐⭐ Question 23: Implement async form validation (check username availability).

**Expected Answer:**

```javascript
const usernameInput = document.getElementById('username');
const usernameStatus = document.getElementById('username-status');

const checkUsernameAvailability = debounce(async function() {
  const username = usernameInput.value.trim();
  
  if (username.length < 3) {
    usernameStatus.textContent = 'Username must be at least 3 characters';
    usernameStatus.className = 'error';
    return;
  }
  
  usernameStatus.textContent = 'Checking...';
  usernameStatus.className = 'checking';
  
  try {
    const response = await fetch(`/api/check-username?username=${username}`);
    const data = await response.json();
    
    if (data.available) {
      usernameStatus.textContent = '✓ Username available';
      usernameStatus.className = 'success';
    } else {
      usernameStatus.textContent = '✗ Username already taken';
      usernameStatus.className = 'error';
    }
  } catch (error) {
    usernameStatus.textContent = 'Error checking username';
    usernameStatus.className = 'error';
  }
}, 500);

usernameInput.addEventListener('input', checkUsernameAvailability);
```

---

## Checkbox & Input Handling

### ⭐ Question 24: How do you handle checkbox selection?

**Expected Answer:**

```javascript
// Single checkbox
const checkbox = document.getElementById('agree');
checkbox.addEventListener('change', function() {
  console.log('Checked:', this.checked);
  if (this.checked) {
    console.log('User agreed to terms');
  }
});

// Get checkbox value
const isChecked = checkbox.checked; // true or false
checkbox.checked = true; // Set checked
```

### ⭐⭐ Question 25: Implement "Select All" checkbox functionality.

**Expected Answer:**

```javascript
const selectAllCheckbox = document.getElementById('selectAll');
const itemCheckboxes = document.querySelectorAll('.item-checkbox');

// Select/Deselect all
selectAllCheckbox.addEventListener('change', function() {
  itemCheckboxes.forEach(checkbox => {
    checkbox.checked = this.checked;
  });
  updateSelectedCount();
});

// Update "Select All" based on individual checkboxes
itemCheckboxes.forEach(checkbox => {
  checkbox.addEventListener('change', function() {
    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
    const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
    
    selectAllCheckbox.checked = allChecked;
    selectAllCheckbox.indeterminate = someChecked && !allChecked;
    
    updateSelectedCount();
  });
});

function updateSelectedCount() {
  const count = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
  document.getElementById('selectedCount').textContent = `${count} selected`;
}
```

### ⭐⭐ Question 26: Get all selected checkbox values.

**Expected Answer:**

```javascript
// Get all checked values
function getSelectedValues() {
  const checkboxes = document.querySelectorAll('.item-checkbox:checked');
  const values = Array.from(checkboxes).map(cb => cb.value);
  return values;
}

// Usage
const selected = getSelectedValues();
console.log('Selected items:', selected);

// Submit selected items
form.addEventListener('submit', function(e) {
  e.preventDefault();
  
  const selectedItems = getSelectedValues();
  
  if (selectedItems.length === 0) {
    alert('Please select at least one item');
    return;
  }
  
  fetch('/api/process', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ items: selectedItems })
  });
});

// Check/uncheck programmatically
function selectByIds(ids) {
  document.querySelectorAll('.item-checkbox').forEach(checkbox => {
    checkbox.checked = ids.includes(checkbox.value);
  });
}
```

---

## Third-Party API Integration

### ⭐⭐ Question 27: How do you integrate with third-party REST APIs?

**Expected Answer:**

```javascript
// Basic API integration
class ApiClient {
  constructor(baseURL, apiKey) {
    this.baseURL = baseURL;
    this.apiKey = apiKey;
  }
  
  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.apiKey}`,
        ...options.headers
      }
    };
    
    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }
  
  async get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }
  
  async post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }
}

// Usage
const api = new ApiClient('https://api.example.com', 'your-api-key');

async function fetchUserData() {
  try {
    const users = await api.get('/users');
    console.log('Users:', users);
  } catch (error) {
    console.error('Failed to fetch users:', error);
  }
}
```

### ⭐⭐⭐ Question 28: Implement API integration with rate limiting and caching.

**Expected Answer:**

```javascript
class ApiClientAdvanced {
  constructor(baseURL, apiKey, options = {}) {
    this.baseURL = baseURL;
    this.apiKey = apiKey;
    this.cache = new Map();
    this.cacheDuration = options.cacheDuration || 300000; // 5 minutes
    this.rateLimitDelay = options.rateLimitDelay || 100;
    this.lastRequestTime = 0;
  }
  
  async request(endpoint, options = {}) {
    // Check cache
    if (options.method === 'GET' || !options.method) {
      const cached = this.getFromCache(endpoint);
      if (cached) {
        console.log('Returning cached data for:', endpoint);
        return cached;
      }
    }
    
    // Rate limiting
    await this.waitForRateLimit();
    
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.apiKey}`,
        ...options.headers
      }
    };
    
    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        if (response.status === 429) {
          throw new Error('Rate limit exceeded');
        }
        throw new Error(`HTTP ${response.status}`);
      }
      
      const data = await response.json();
      
      // Cache GET requests
      if (options.method === 'GET' || !options.method) {
        this.saveToCache(endpoint, data);
      }
      
      return data;
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }
  
  getFromCache(key) {
    const cached = this.cache.get(key);
    if (cached && Date.now() - cached.timestamp < this.cacheDuration) {
      return cached.data;
    }
    this.cache.delete(key);
    return null;
  }
  
  saveToCache(key, data) {
    this.cache.set(key, {
      data,
      timestamp: Date.now()
    });
  }
  
  async waitForRateLimit() {
    const now = Date.now();
    const timeSinceLastRequest = now - this.lastRequestTime;
    
    if (timeSinceLastRequest < this.rateLimitDelay) {
      await new Promise(resolve => 
        setTimeout(resolve, this.rateLimitDelay - timeSinceLastRequest)
      );
    }
    
    this.lastRequestTime = Date.now();
  }
  
  clearCache() {
    this.cache.clear();
  }
}

// Usage
const api = new ApiClientAdvanced('https://api.example.com', 'api-key', {
  cacheDuration: 300000,
  rateLimitDelay: 100
});
```

### ⭐⭐⭐ Question 29: How do you handle API authentication (OAuth 2.0)?

**Expected Answer:**

```javascript
class OAuthApiClient {
  constructor(config) {
    this.clientId = config.clientId;
    this.clientSecret = config.clientSecret;
    this.redirectUri = config.redirectUri;
    this.authEndpoint = config.authEndpoint;
    this.tokenEndpoint = config.tokenEndpoint;
    this.apiBaseUrl = config.apiBaseUrl;
    this.accessToken = null;
    this.refreshToken = null;
  }
  
  // Step 1: Redirect to authorization
  authorize() {
    const params = new URLSearchParams({
      client_id: this.clientId,
      redirect_uri: this.redirectUri,
      response_type: 'code',
      scope: 'read write'
    });
    
    window.location.href = `${this.authEndpoint}?${params}`;
  }
  
  // Step 2: Exchange code for token
  async getAccessToken(code) {
    try {
      const response = await fetch(this.tokenEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          grant_type: 'authorization_code',
          code: code,
          redirect_uri: this.redirectUri,
          client_id: this.clientId,
          client_secret: this.clientSecret
        })
      });
      
      const data = await response.json();
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      
      // Store tokens
      localStorage.setItem('access_token', this.accessToken);
      localStorage.setItem('refresh_token', this.refreshToken);
      
      return data;
    } catch (error) {
      console.error('Failed to get access token:', error);
      throw error;
    }
  }
  
  // Step 3: Refresh token
  async refreshAccessToken() {
    try {
      const response = await fetch(this.tokenEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          grant_type: 'refresh_token',
          refresh_token: this.refreshToken,
          client_id: this.clientId,
          client_secret: this.clientSecret
        })
      });
      
      const data = await response.json();
      this.accessToken = data.access_token;
      localStorage.setItem('access_token', this.accessToken);
      
      return data;
    } catch (error) {
      console.error('Failed to refresh token:', error);
      throw error;
    }
  }
  
  // Make authenticated API request
  async request(endpoint, options = {}) {
    if (!this.accessToken) {
      this.accessToken = localStorage.getItem('access_token');
    }
    
    try {
      const response = await fetch(`${this.apiBaseUrl}${endpoint}`, {
        ...options,
        headers: {
          'Authorization': `Bearer ${this.accessToken}`,
          'Content-Type': 'application/json',
          ...options.headers
        }
      });
      
      // Handle token expiration
      if (response.status === 401) {
        await this.refreshAccessToken();
        return this.request(endpoint, options); // Retry
      }
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }
}

// Usage
const oauthClient = new OAuthApiClient({
  clientId: 'your-client-id',
  clientSecret: 'your-client-secret',
  redirectUri: 'https://yourapp.com/callback',
  authEndpoint: 'https://api.example.com/oauth/authorize',
  tokenEndpoint: 'https://api.example.com/oauth/token',
  apiBaseUrl: 'https://api.example.com'
});

// Handle callback
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');
if (code) {
  await oauthClient.getAccessToken(code);
}
```

---

## Modern JavaScript Features

### ⭐⭐ Question 30: Explain destructuring and spread operator.

**Expected Answer:**

```javascript
// Array destructuring
const [first, second, ...rest] = [1, 2, 3, 4, 5];
console.log(first); // 1
console.log(rest);  // [3, 4, 5]

// Object destructuring
const { name, age, email = 'N/A' } = user;
const { name: userName, age: userAge } = user; // Rename

// Nested destructuring
const { address: { city, country } } = user;

// Spread operator - Arrays
const arr1 = [1, 2, 3];
const arr2 = [4, 5, 6];
const combined = [...arr1, ...arr2]; // [1, 2, 3, 4, 5, 6]

// Spread operator - Objects
const obj1 = { a: 1, b: 2 };
const obj2 = { c: 3, d: 4 };
const merged = { ...obj1, ...obj2 }; // { a: 1, b: 2, c: 3, d: 4 }

// Function parameters
function sum(...numbers) {
  return numbers.reduce((a, b) => a + b, 0);
}
sum(1, 2, 3, 4); // 10
```

### ⭐⭐ Question 31: Explain arrow functions and their differences from regular functions.

**Expected Answer:**

```javascript
// Regular function
function regular(a, b) {
  return a + b;
}

// Arrow function
const arrow = (a, b) => a + b;

// Key differences:

// 1. 'this' binding
const obj = {
  name: 'Object',
  regularFunc: function() {
    console.log(this.name); // 'Object'
  },
  arrowFunc: () => {
    console.log(this.name); // undefined (lexical this)
  }
};

// 2. No arguments object
function regular() {
  console.log(arguments); // Available
}
const arrow = () => {
  // console.log(arguments); // ReferenceError
};

// 3. Cannot be used as constructor
const Func = () => {};
// new Func(); // TypeError

// 4. Implicit return
const square = x => x * x; // Returns x * x
const multi = x => ({ value: x }); // Return object (wrap in parentheses)
```

### ⭐⭐ Question 32: Explain template literals and their features.

**Expected Answer:**

```javascript
// Basic template literal
const name = 'John';
const greeting = `Hello, ${name}!`;

// Multi-line strings
const html = `
  <div>
    <h1>${title}</h1>
    <p>${content}</p>
  </div>
`;

// Expression evaluation
const price = 19.99;
const tax = 0.08;
const total = `Total: $${(price * (1 + tax)).toFixed(2)}`;

// Tagged templates
function highlight(strings, ...values) {
  return strings.reduce((result, str, i) => {
    return result + str + (values[i] ? `<mark>${values[i]}</mark>` : '');
  }, '');
}

const name = 'John';
const age = 30;
const message = highlight`Name: ${name}, Age: ${age}`;
// "Name: <mark>John</mark>, Age: <mark>30</mark>"

// Nesting
const users = ['Alice', 'Bob', 'Charlie'];
const list = `
  <ul>
    ${users.map(user => `<li>${user}</li>`).join('')}
  </ul>
`;
```

---

## Performance & Optimization

### ⭐⭐ Question 33: How do you optimize DOM manipulation for performance?

**Expected Answer:**

```javascript
// 1. Minimize DOM access
// BAD
for (let i = 0; i < 100; i++) {
  document.getElementById('list').innerHTML += `<li>Item ${i}</li>`;
}

// GOOD
const list = document.getElementById('list');
let html = '';
for (let i = 0; i < 100; i++) {
  html += `<li>Item ${i}</li>`;
}
list.innerHTML = html;

// 2. Use DocumentFragment
const fragment = document.createDocumentFragment();
for (let i = 0; i < 1000; i++) {
  const li = document.createElement('li');
  li.textContent = `Item ${i}`;
  fragment.appendChild(li);
}
list.appendChild(fragment); // Single reflow

// 3. Batch style changes
element.style.cssText = 'color: red; background: blue; font-size: 20px;';

// Or use classes
element.classList.add('styled');

// 4. Use event delegation
document.getElementById('list').addEventListener('click', function(e) {
  if (e.target.matches('li')) {
    console.log('Clicked:', e.target.textContent);
  }
});

// 5. Avoid layout thrashing
// BAD (read-write-read-write)
const h1 = element1.clientHeight;
element1.style.height = h1 * 2 + 'px';
const h2 = element2.clientHeight;
element2.style.height = h2 * 2 + 'px';

// GOOD (read-read-write-write)
const h1 = element1.clientHeight;
const h2 = element2.clientHeight;
element1.style.height = h1 * 2 + 'px';
element2.style.height = h2 * 2 + 'px';
```

### ⭐⭐⭐ Question 34: Implement lazy loading for images.

**Expected Answer:**

```javascript
// Modern: Intersection Observer API
const imageObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const img = entry.target;
      img.src = img.dataset.src;
      img.classList.remove('lazy');
      observer.unobserve(img);
    }
  });
}, {
  rootMargin: '50px' // Load 50px before entering viewport
});

// Observe all lazy images
const lazyImages = document.querySelectorAll('img.lazy');
lazyImages.forEach(img => imageObserver.observe(img));

// HTML:
// <img class="lazy" data-src="image.jpg" src="placeholder.jpg" alt="">

// Native lazy loading (modern browsers)
<img src="image.jpg" loading="lazy" alt="">

// Complete implementation with fallback
function lazyLoad() {
  if ('loading' in HTMLImageElement.prototype) {
    // Native lazy loading
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
      img.src = img.dataset.src;
    });
  } else {
    // Fallback to Intersection Observer
    const script = document.createElement('script');
    script.src = 'intersection-observer-polyfill.js';
    script.onload = () => initIntersectionObserver();
    document.body.appendChild(script);
  }
}
```

### ⭐⭐ Question 35: How do you implement infinite scroll?

**Expected Answer:**

```javascript
class InfiniteScroll {
  constructor(container, options = {}) {
    this.container = container;
    this.loading = false;
    this.page = 1;
    this.hasMore = true;
    this.threshold = options.threshold || 200;
    this.onLoad = options.onLoad;
    
    this.init();
  }
  
  init() {
    window.addEventListener('scroll', this.handleScroll.bind(this));
  }
  
  handleScroll() {
    if (this.loading || !this.hasMore) return;
    
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollHeight = document.documentElement.scrollHeight;
    const clientHeight = document.documentElement.clientHeight;
    
    if (scrollTop + clientHeight >= scrollHeight - this.threshold) {
      this.loadMore();
    }
  }
  
  async loadMore() {
    if (this.loading) return;
    
    this.loading = true;
    this.showLoader();
    
    try {
      const data = await this.onLoad(this.page);
      
      if (data && data.length > 0) {
        this.appendItems(data);
        this.page++;
      } else {
        this.hasMore = false;
      }
    } catch (error) {
      console.error('Failed to load more:', error);
    } finally {
      this.loading = false;
      this.hideLoader();
    }
  }
  
  appendItems(items) {
    items.forEach(item => {
      const element = this.createItemElement(item);
      this.container.appendChild(element);
    });
  }
  
  createItemElement(item) {
    const div = document.createElement('div');
    div.className = 'item';
    div.innerHTML = `
      <h3>${item.title}</h3>
      <p>${item.description}</p>
    `;
    return div;
  }
  
  showLoader() {
    document.getElementById('loader').style.display = 'block';
  }
  
  hideLoader() {
    document.getElementById('loader').style.display = 'none';
  }
}

// Usage
const infiniteScroll = new InfiniteScroll(
  document.getElementById('content'),
  {
    threshold: 200,
    onLoad: async (page) => {
      const response = await fetch(`/api/items?page=${page}`);
      return await response.json();
    }
  }
);
```

---

## Summary

This comprehensive question bank covers:

- **HTML**: Semantic elements, HTML5 features, data attributes, canvas
- **CSS**: Box model, Flexbox, Grid, specificity
- **JavaScript Core**: Variables, closures, this, prototypes, equality
- **DOM**: Selection, manipulation, innerHTML vs textContent
- **Events**: Listeners, bubbling, delegation, debouncing, throttling
- **AJAX**: XMLHttpRequest, Fetch API, Axios, error handling, CORS
- **jQuery**: Basics, AJAX, comparison with vanilla JS
- **Async**: Promises, async/await, error handling
- **Forms**: Validation, real-time validation, async validation
- **Checkboxes**: Selection, select all, getting values
- **APIs**: Integration, OAuth, rate limiting, caching
- **Modern JS**: Destructuring, spread, arrow functions, template literals
- **Performance**: DOM optimization, lazy loading, infinite scroll

**Total Questions: 35 covering all requested topics**
