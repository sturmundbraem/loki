<?php

namespace sturmundbraem\automatisations\services\providers;

use GuzzleHttp\Client;
use sturmundbraem\automatisations\services\providers\LlmProviderInterface;

// Handles communication with the Claude API
// Implements the interface so it has the same method signature as all other providers
class ClaudeProvider implements LlmProviderInterface
{
    public function generateText(string $prompt, string $context, string $fieldHandle): string
    {
        // Build the full prompt that combines: page context + task + target field
        $fullPrompt = "Here is the content of the page:\n" . $context . "\nTask: " . $prompt . "\nWrite the content for the field: " . $fieldHandle;

        // --- Real API call (currently skipped because of the return above) ---

        // Guzzle is an HTTP client library (like Python's requests)
        $client = new Client();

        // Read the API key from the .env file — NEVER hardcode API keys!
        $apiKey = getenv('CLAUDE_API_KEY');
        if (!$apiKey) {
            throw new \Exception('CLAUDE_API_KEY not set in .env');
        }

        // Send a POST request to Claude's chat completions endpoint
        try {
            $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $apiKey,   // Authentication
                'Content-Type' => 'application/json',       // Tell Claude we're sending JSON
                'anthropic-version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $fullPrompt]
                ]
            ]
        ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            throw new \Exception('Claude API error: ' . $errorBody);
        }
        // Parse the JSON response from OpenAI
        $body = json_decode($response->getBody(), true);

        // Dig into the response structure to get the actual generated text
        // Claude returns: { content: [ { text: "the text" } ] }
        return $body['content'][0]['text'];

    }
}