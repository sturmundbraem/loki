<?php

namespace sturmundbraem\automatisations\services\providers;

use sturmundbraem\automatisations\services\providers\LlmProviderInterface;
use GuzzleHttp\Client;

class DeepLProvider implements LlmProviderInterface
{
    public function generateText($prompt, $context, $fieldHandle):string {
        $client = new Client();

        $apiKey = getenv('DEEPL_API_KEY');
        if (!$apiKey) {
            throw new \Exception('DEEPL API is not set in .env');
        }

        $parts = explode(' ', trim($prompt));
        $targetLang = strtoupper(end($parts));

        preg_match('/' . $fieldHandle . ': (.+)/', $context, $matches);
        $textToTranslate = $matches[1] ?? '';

        try {
            $response = $client->post('https://api-free.deepl.com/v2/translate', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $apiKey,
                ],
                'form_params' => [
                    'text' => $textToTranslate,
                    'target_lang' => $targetLang,
                ]
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            throw new \Exception('DeepL API error: ' . $errorBody);
        }

        $body = json_decode($response->getBody(), true);
        return $body['translations'][0]['text'];        

    }
}