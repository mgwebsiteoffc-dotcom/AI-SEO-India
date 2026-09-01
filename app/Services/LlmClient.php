<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared LLM client — OpenAI or Gemini, whichever key is configured.
 * Returns raw text or null when no key / failure (callers fall back to templates).
 */
class LlmClient
{
    public function available(): bool
    {
        return (bool) (config('services.openai.key') || config('services.gemini.key'));
    }

    public function provider(): string
    {
        return config('services.openai.key') ? 'openai' : 'gemini';
    }

    public function chat(string $system, string $user, bool $json = false): ?string
    {
        if (! $this->available()) {
            return null;
        }
        try {
            if (config('services.openai.key')) {
                return $this->openai($system, $user, $json);
            }
            return $this->gemini($system, $user, $json);
        } catch (\Throwable $e) {
            Log::warning('LLM call failed: '.$e->getMessage());
            return null;
        }
    }

    private function openai(string $system, string $user, bool $json): ?string
    {
        $payload = [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1600,
        ];
        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        $res = Http::timeout(45)->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', $payload);
        if (! $res->successful()) {
            return null;
        }
        return $res->json('choices.0.message.content');
    }

    private function gemini(string $system, string $user, bool $json): ?string
    {
        $res = Http::timeout(45)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini.model', 'gemini-1.5-flash').':generateContent?key='.config('services.gemini.key'), [
                'contents' => [['parts' => [['text' => $system."\n\n".$user]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1600],
            ]);
        if (! $res->successful()) {
            return null;
        }
        return $res->json('candidates.0.content.parts.0.text');
    }
}
