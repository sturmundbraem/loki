<?php

namespace stubr\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use stubr\services\AIService;
use stubr\Plugin;

class ContentController extends \craft\web\Controller
{
    // This method handles POST requests from fieldHelper.js
    // URL: craft-cp-ai/content/generate
    public function actionGenerate()
    {
        // Read the POST data sent by the JS fetch request
        // Each getBodyParam() reads one value by its key name
        $entryId = (int) Craft::$app->getRequest()->getBodyParam('entryId');       // Cast to integer for database lookup
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');                // Which site/language
        $fieldHandle = Craft::$app->getRequest()->getBodyParam('fieldHandle');       // Which field to fill (e.g. "subtitle")
        $prompt = Craft::$app->getRequest()->getBodyParam('prompt');                 // The AI prompt to use
        $provider = Craft::$app->getRequest()->getBodyParam('provider');            // Read provider from POST data
        $settings = Plugin::$plugin->getSettings();

        $validPrompts = array_column($settings->prompts, 'text');
        if (!in_array($prompt, $validPrompts)) {
            return $this->asJson(['error' => 'Invalid prompt'], 400);
        }

        $allowedProviders = ['openai', 'claude', 'deepl'];
        if (!in_array($provider, $allowedProviders)) {
            return $this->asJson(['error' => 'Invalid provider'], 400);
        }

        // Look up the entry from the database using Craft's query builder
        // ->siteId() ensures we get the correct language version
        // ->status(null) finds the entry even if it's disabled or a draft
        // ->one() executes the query and returns a single entry object
        $entry = Entry::find()
            ->siteId($siteId)
            ->id($entryId)
            ->status(null)
            ->one();
        
        if (!$entry) {
            return $this->asJson(['error' => 'Entry not found'], 404);
        }

        // $this->requirePermission('edit-entries:' . $entry->section->uid);

        if (!$entry->getFieldLayout()->getFieldByHandle($fieldHandle)) {
            return $this->asJson(['error' => 'Invalid field'], 400);
        }

        $site = Craft::$app->getSites()->getSiteById($siteId);
        $prompt = Craft::$app->getView()->renderString($prompt, [
            'siteLang' => $site->language,
            'fieldHandle' => $fieldHandle,
            'entryTitle' => $entry->title,
        ]);

        // Build a context string with ALL field values from the entry
        // This gives the AI the full picture of the page content
        $fieldValues = $entry->getFieldValues();
        $context = 'Title: ' . $entry->title . "\n";
        foreach ($fieldValues as $handle => $value) {
            $context .= $handle . ': ' . $value . "\n";
        }

        // Call the AI service to generate text
        // The service picks the right provider (OpenAI, Claude, etc.) based on .env
        $aiService = new AIService();
        
        try {
            $generatedContent = $aiService->generateContent($prompt, $context, $fieldHandle, $provider);
        } catch (\Exception $e) {
            return $this->asJson(['error' => $e->getMessage()]);
        }

        // Create a draft copy of the entry (doesn't affect the live version)
        $draft = Craft::$app->getDrafts()->createDraft($entry);

        // Set the AI-generated text on the target field and save the draft
        $draft->setFieldValue($fieldHandle, $generatedContent);
        Craft::$app->getElements()->saveElement($draft);

        // Return JSON response to the JS .then() callback
        return $this->asJson([
            'draftId' => $draft->draftId,
            'title' => $draft->title,
            'generatedContent' => $generatedContent,
            'fieldHandle' => $fieldHandle,
            'draftUrl' => $draft->getCpEditUrl()
        ]);

    }
}