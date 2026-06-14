<?php

namespace stubr\models;

use craft\base\Model;

class Settings extends Model
{
    // Defines all fields which are hidden from the field filter table
    public array $hiddenFields = [];

    public array $fieldAssignments = [];
    
    public array $fieldOrder = [];

    public string $openaiApiKey = '$OPENAI_API_KEY';
    public string $claudeApiKey = '$CLAUDE_API_KEY';
    public string $deeplApiKey = '$DEEPL_API_KEY';

    public array $prompts = [
        [
            'uid' => '3bb64867-266b-439d-a36c-a2d54da75fa4',
            'text' => "DE",
            'label' => 'Translate to DE',
            'provider' => 'deepl',
            'createDraft' => '',
            'allPlainText' => '1',
            'allCKEditor' => '1',
        ],
        [
            'uid' => 'd9f85ad2-9677-4958-9583-71c973ad0d79',
            'text' => 'Translate to {{ siteLang }}',
            'label' => 'Translate to current language',
            'provider' => 'openai',
            'createDraft' => '',
            'allPlainText' => '1',
            'allCKEditor' => '1',
        ],
        [
            'uid' => 'e4bc07b5-4442-4859-afe7-884b65662e0c',
            'text' => 'Correct syntax, spelling, and grammar',
            'label' => 'Correct',
            'provider' => 'openai',
            'createDraft' => '',
            'allPlainText' => '1',
            'allCKEditor' => '1',
        ],
        [
            'uid' => '807c5365-8771-4dec-b92b-53f080ebab05',
            'text' => 'Shorten text to 3/4 length',
            'label' => 'Shorten',
            'provider' => 'claude',
            'createDraft' => '',
            'allPlainText' => '1',
            'allCKEditor' => '1',
        ],
    ];
    public string $basePrompt = <<<TXT
    You are a CMS content generator. Output ONLY the requested text.
    Do not greet, do not introduce, do not explain.
    Do not offer follow-ups or alternatives.
    Do not wrap your output in quotes or markdown unless the field requires HTML.
    Return the finished text and nothing else.
    TXT;

    // Defines all fields which will not filter the assets for the file pool
    // Instead the filtering can be done on the project side using events
    public array $skipDefaultHandlingFields = [];
}
