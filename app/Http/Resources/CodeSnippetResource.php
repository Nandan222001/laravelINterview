<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CodeSnippetResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'title' => $this->title,
            'description' => $this->description,
            'code' => $this->code,
            'language' => $this->language,
            'type' => $this->type,
            'order' => $this->order,
            'is_executable' => $this->is_executable,
            'expected_output' => $this->expected_output,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if (isset($this->highlighted_code)) {
            $data['highlighted_code'] = $this->highlighted_code;
            $data['detected_language'] = $this->detected_language;
            $data['relevance'] = $this->relevance;
            $data['second_best_language'] = $this->second_best_language;
            $data['rendering_instructions'] = [
                'type' => 'pre_rendered',
                'stylesheet' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
                'theme_options' => [
                    'default',
                    'github',
                    'monokai',
                    'vs2015',
                    'atom-one-dark',
                    'atom-one-light',
                ],
                'usage' => 'Wrap highlighted_code in <pre><code class="hljs"> tags or use data-language attribute for client-side rendering',
            ];
        } else {
            $data['rendering_instructions'] = [
                'type' => 'client_side',
                'library' => 'highlight.js',
                'cdn' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
                'stylesheet' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
                'usage' => [
                    'Include highlight.js script and stylesheet',
                    'Wrap code in <pre><code class="language-{language}"> tags',
                    'Call hljs.highlightAll() or hljs.highlightElement(element)',
                ],
                'example' => [
                    'html' => '<pre><code class="language-'.$this->language.'">'.htmlspecialchars($this->code).'</code></pre>',
                    'javascript' => 'document.addEventListener("DOMContentLoaded", (event) => { hljs.highlightAll(); });',
                ],
            ];
        }

        if ($this->relationLoaded('question')) {
            $data['question'] = new QuestionResource($this->whenLoaded('question'));
        }

        if ($this->relationLoaded('tags')) {
            $data['tags'] = TagResource::collection($this->whenLoaded('tags'));
        }

        return $data;
    }
}
