<?php

namespace stubr\models;

use craft\base\Model;

class Settings extends Model
{
    // Defines all fields which are hidden from the field filter table
    public array $hiddenFields = [];

    public array $fieldAssignments = [];


    public array $prompts = [];
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