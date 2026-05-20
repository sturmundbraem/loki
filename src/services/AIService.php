<?php

namespace stubr\services;

use stubr\services\providers\OpenAiProvider;
use stubr\services\providers\LlmProviderInterface;

// The AI service acts as a bridge between the controller and the LLM providers
// It reads the .env config to decide which provider to use, then forwards the request
class AIService
{
    private const SYSTEM_RULES = <<<TXT
    You are a CMS content generator. Output ONLY the requested text.
    Do not greet, do not introduce, do not explain.
    Do not offer follow-ups or alternatives.
    Do not wrap your output in quotes or markdown unless the field requires HTML.
    Return the finished text and nothing else.
    TXT;
    // Takes a prompt, page context, and field handle
    // Returns the AI-generated text as a string
    public function generateContent(string $prompt, $context, $fieldHandle, string $provider, string $basePrompt): string
    {
        $factory = LlmProviderFactory::getInstance();
        $llmProvider = $factory->getProvider($provider);
        $systemMessage = self::SYSTEM_RULES;
        if ($basePrompt !== '') {
            $systemMessage .= "\n\n" . $basePrompt;
        }
        return $llmProvider->generateText($prompt, $context, $fieldHandle, $systemMessage);


    }

}