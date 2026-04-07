<?php

namespace sturmundbraem\automatisations\services;

use sturmundbraem\automatisations\services\providers\OpenAiProvider;
use sturmundbraem\automatisations\services\providers\LlmProviderInterface;
use sturmundbraem\automatisations\services\providers\ClaudeProvider;
use sturmundbraem\automatisations\services\providers\DeepLProvider;


class LlmProviderFactory
{
    private static ?self $instance = null;

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getProvider(string $provider) {
        if ($provider === 'openai') {
            // Create the OpenAI provider and call its generateText method
            $openAiProvider = new OpenAiProvider();
            return $openAiProvider;
        }
        if ($provider === 'claude') {
            return new ClaudeProvider();
        }
        if ($provider === 'deepl') {
            return new DeepLProvider();
        }

        else {
            // If the provider is not recognized, throw an error
            throw new \Exception("Unsupported AI provider: " . $provider);
        }
    }
}

