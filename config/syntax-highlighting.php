<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Syntax Highlighting Library
    |--------------------------------------------------------------------------
    |
    | This option determines which syntax highlighting library is used
    | by default for client-side rendering instructions.
    |
    | Supported: "highlight.js", "prism.js"
    |
    */

    'default_library' => env('SYNTAX_HIGHLIGHTING_LIBRARY', 'highlight.js'),

    /*
    |--------------------------------------------------------------------------
    | Server-Side Pre-Rendering
    |--------------------------------------------------------------------------
    |
    | Enable or disable server-side pre-rendering of code snippets.
    | When enabled, code will be highlighted on the server using highlight.php.
    |
    */

    'pre_render' => env('SYNTAX_HIGHLIGHTING_PRE_RENDER', true),

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The default theme used for syntax highlighting.
    |
    */

    'default_theme' => env('SYNTAX_HIGHLIGHTING_THEME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Highlight.js Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for highlight.js
    |
    */

    'highlightjs' => [
        'version' => '11.9.0',
        'cdn' => [
            'script' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
            'styles_base' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/',
        ],
        'themes' => [
            'default',
            'github',
            'github-dark',
            'monokai',
            'vs2015',
            'atom-one-dark',
            'atom-one-light',
            'dracula',
            'nord',
            'tokyo-night-dark',
            'solarized-dark',
            'solarized-light',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prism.js Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Prism.js
    |
    */

    'prismjs' => [
        'version' => '1.29.0',
        'cdn' => [
            'script' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js',
            'styles_base' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/',
            'components' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/',
        ],
        'themes' => [
            'default' => 'prism',
            'dark' => 'prism-dark',
            'okaidia' => 'prism-okaidia',
            'twilight' => 'prism-twilight',
            'coy' => 'prism-coy',
            'solarizedlight' => 'prism-solarizedlight',
            'tomorrow' => 'prism-tomorrow',
            'funky' => 'prism-funky',
        ],
        'plugins' => [
            'line-numbers',
            'show-language',
            'copy-to-clipboard',
            'toolbar',
            'line-highlight',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Auto-Detection
    |--------------------------------------------------------------------------
    |
    | Enable automatic language detection for code snippets when
    | no language is explicitly specified.
    |
    */

    'auto_detect_language' => env('SYNTAX_HIGHLIGHTING_AUTO_DETECT', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Highlighted Code
    |--------------------------------------------------------------------------
    |
    | Cache the highlighted HTML output to improve performance.
    |
    */

    'cache_highlighted' => env('SYNTAX_HIGHLIGHTING_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Time in seconds to cache highlighted code. Default is 1 hour.
    |
    */

    'cache_ttl' => env('SYNTAX_HIGHLIGHTING_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Popular Languages
    |--------------------------------------------------------------------------
    |
    | Most commonly used programming languages for quick reference.
    |
    */

    'popular_languages' => [
        'javascript',
        'python',
        'java',
        'php',
        'cpp',
        'csharp',
        'go',
        'rust',
        'typescript',
        'ruby',
        'swift',
        'kotlin',
        'sql',
        'html',
        'css',
        'json',
        'yaml',
        'bash',
        'powershell',
        'r',
    ],

    /*
    |--------------------------------------------------------------------------
    | Snippet Types
    |--------------------------------------------------------------------------
    |
    | Available types for code snippets with their descriptions.
    |
    */

    'snippet_types' => [
        'example' => 'Example code to illustrate concepts',
        'solution' => 'Solution code for a problem',
        'test' => 'Test cases or unit tests',
        'template' => 'Code templates or boilerplate',
        'starter' => 'Starter code for users to begin with',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Snippet Order Increment
    |--------------------------------------------------------------------------
    |
    | When auto-assigning order values, increment by this amount.
    |
    */

    'order_increment' => 1,

    /*
    |--------------------------------------------------------------------------
    | Max Code Length
    |--------------------------------------------------------------------------
    |
    | Maximum length of code snippet in characters. Set to null for unlimited.
    |
    */

    'max_code_length' => env('SYNTAX_HIGHLIGHTING_MAX_LENGTH', null),

    /*
    |--------------------------------------------------------------------------
    | Enable Line Numbers
    |--------------------------------------------------------------------------
    |
    | Include line numbers in pre-rendered code output.
    |
    */

    'line_numbers' => env('SYNTAX_HIGHLIGHTING_LINE_NUMBERS', false),
];
