<?php

namespace App\Services;

use Highlight\Highlighter;

class SyntaxHighlightingService
{
    private Highlighter $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter;
    }

    public function highlight(string $code, ?string $language = null): array
    {
        try {
            if ($language && $language !== 'auto' && $language !== 'plaintext') {
                $result = $this->highlighter->highlight($language, $code);
            } else {
                $result = $this->highlighter->highlightAuto($code);
            }

            return [
                'success' => true,
                'highlighted' => $result->value,
                'language' => $result->language,
                'relevance' => $result->relevance ?? 0,
                'second_best' => $result->secondBest->language ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'highlighted' => htmlspecialchars($code),
                'language' => $language ?? 'plaintext',
                'relevance' => 0,
                'second_best' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function detectLanguage(string $code): string
    {
        try {
            $result = $this->highlighter->highlightAuto($code);

            return $result->language;
        } catch (\Exception $e) {
            return 'plaintext';
        }
    }

    public function getSupportedLanguages(): array
    {
        return $this->highlighter->listLanguages();
    }

    public function getLanguageAliases(string $language): array
    {
        try {
            $languageDefinition = $this->highlighter->getLanguage($language);

            return $languageDefinition->aliases ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getThemeOptions(): array
    {
        return [
            'default' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
            'github' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css',
            'github-dark' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css',
            'monokai' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/monokai.min.css',
            'vs2015' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/vs2015.min.css',
            'atom-one-dark' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css',
            'atom-one-light' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-light.min.css',
            'dracula' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/dracula.min.css',
            'nord' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/nord.min.css',
            'tokyo-night-dark' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/tokyo-night-dark.min.css',
        ];
    }

    public function getClientSideInstructions(?string $language = null): array
    {
        return [
            'library' => 'highlight.js',
            'version' => '11.9.0',
            'cdn' => [
                'script' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
                'stylesheet' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
            ],
            'installation' => [
                'html' => [
                    '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">',
                    '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>',
                ],
                'init' => '<script>hljs.highlightAll();</script>',
            ],
            'usage' => [
                'html' => '<pre><code class="language-'.($language ?? 'javascript').'">Your code here</code></pre>',
                'javascript' => [
                    'auto' => 'hljs.highlightAll();',
                    'manual' => 'hljs.highlightElement(codeElement);',
                ],
            ],
            'themes' => $this->getThemeOptions(),
        ];
    }

    public function getPrismJsInstructions(?string $language = null): array
    {
        return [
            'library' => 'prism.js',
            'version' => '1.29.0',
            'cdn' => [
                'script' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js',
                'stylesheet' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css',
            ],
            'installation' => [
                'html' => [
                    '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">',
                    '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>',
                ],
                'language_specific' => '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-'.($language ?? 'javascript').'.min.js"></script>',
            ],
            'usage' => [
                'html' => '<pre><code class="language-'.($language ?? 'javascript').'">Your code here</code></pre>',
                'note' => 'Prism.js automatically highlights code on page load',
            ],
            'themes' => [
                'default' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css',
                'dark' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-dark.min.css',
                'okaidia' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css',
                'twilight' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-twilight.min.css',
                'coy' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-coy.min.css',
                'solarizedlight' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-solarizedlight.min.css',
                'tomorrow' => 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css',
            ],
        ];
    }

    public function getRenderingMetadata(bool $preRendered = true, ?string $language = null): array
    {
        if ($preRendered) {
            return [
                'type' => 'pre_rendered',
                'library' => 'highlight.js (server-side)',
                'stylesheet' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
                'usage' => 'Wrap highlighted_code in <pre><code class="hljs"> tags',
                'themes' => $this->getThemeOptions(),
                'note' => 'No JavaScript required - HTML is pre-rendered on the server',
            ];
        }

        return [
            'type' => 'client_side',
            'options' => [
                'highlight.js' => $this->getClientSideInstructions($language),
                'prism.js' => $this->getPrismJsInstructions($language),
            ],
            'recommendation' => 'Use highlight.js for better language detection and broader language support',
        ];
    }
}
