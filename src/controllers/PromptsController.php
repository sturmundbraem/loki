<?php

namespace stubr\controllers;

use Craft;
use craft\web\Controller;
use stubr\Plugin;
use craft\helpers\StringHelper;

class PromptsController extends Controller{

    public function actionIndex()
    {
        $settings = Plugin::$plugin->getSettings();
        $promptSettings = Plugin::$plugin->getPromptSettings();
        $promptData = $promptSettings->getAll();
        $prompts = $promptData['prompts'];
        $fieldAssignments = $promptData['fieldAssignments'];

        // 1) Ensure every prompt has a UID (in-memory backfill)
        $indexToUid = [];
        foreach ($prompts as $key => $prompt) {
            if (empty($prompt['uid'])) {
                $prompts[$key]['uid'] = StringHelper::UUID();
            }
            $indexToUid[(string)$key] = $prompts[$key]['uid'];
        }

        // 2) Migrate fieldAssignments from row-index → UID for any old-format entries
        foreach ($fieldAssignments as $fieldHandle => $promptRefs) {
            foreach ($promptRefs as $i => $ref) {
                if (isset($indexToUid[(string)$ref])) {
                    $fieldAssignments[$fieldHandle][$i] = $indexToUid[(string)$ref];
                }
            }
        }

        // 3) UID-keyed map for the JS (used by the assignments panel)
        $promptsByUid = [];
        foreach ($prompts as $prompt) {
            $promptsByUid[$prompt['uid']] = $prompt;
        }

        $allFields = Craft::$app->getFields()->getAllFields();
        $savedOrder = $promptData['fieldOrder'];
        if (!empty($savedOrder)) {
            $positions = array_flip($savedOrder);
            usort($allFields, function ($a, $b) use ($positions) {
                $posA = $positions[$a->handle] ?? PHP_INT_MAX;
                $posB = $positions[$b->handle] ?? PHP_INT_MAX;
                return $posA <=> $posB;
            });
        }

        return $this->renderTemplate('craft-loki/index', [
            'prompts' => $prompts,
            'promptsByUid' => $promptsByUid,
            'allFields' => $allFields,
            'fieldAssignments' => $fieldAssignments,
            'settings' => $settings,
        ]);
    }


    public function actionSave(){
        $this->requirePostRequest();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();
        $promptSettings = Plugin::$plugin->getPromptSettings();
        $promptData = $promptSettings->getAll();
        $prompts = $request->getBodyParam('prompts', []);
        $fieldAssignments = $promptData['fieldAssignments'];
        $postedFieldAssignments = $request->getBodyParam('fieldAssignments', []);
        $touchedFields = $request->getBodyParam('fieldAssignmentsTouched', []);
        $fieldOrderJson = $request->getBodyParam('fieldOrder', '');
        $fieldOrder = $fieldOrderJson ? (json_decode($fieldOrderJson, true) ?: []) : [];
        $buckets = $request->getBodyParam('bucketAssignments', []);
        $touchedBuckets = $request->getBodyParam('bucketAssignmentsTouched', []);
        $prompts = is_array($prompts) ? $prompts : [];
        $fieldAssignments = is_array($fieldAssignments) ? $fieldAssignments : [];
        $postedFieldAssignments = is_array($postedFieldAssignments) ? $postedFieldAssignments : [];
        $buckets = is_array($buckets) ? $buckets : [];

        $asArray = function ($value): array {
            if (!is_array($value)) {
                $value = $value !== null && $value !== '' ? [$value] : [];
            }

            return array_values(array_filter($value, fn($item) => $item !== null && $item !== ''));
        };

        foreach ($asArray($touchedFields) as $fieldHandle) {
            $fieldAssignments[$fieldHandle] = $asArray($postedFieldAssignments[$fieldHandle] ?? []);
        }

        $touchedBuckets = $asArray($touchedBuckets);
        $plainTextTouched = in_array('allPlainText', $touchedBuckets, true);
        $ckEditorTouched = in_array('allCKEditor', $touchedBuckets, true);
        $plainTextKeys = $asArray($buckets['allPlainText'] ?? []);
        $ckEditorKeys = $asArray($buckets['allCKEditor'] ?? []);
        $existingPromptsByUid = [];
        $existingPrompts = is_array($promptData['prompts']) ? $promptData['prompts'] : [];
        foreach ($existingPrompts as $existingPrompt) {
            if (!empty($existingPrompt['uid'])) {
                $existingPromptsByUid[$existingPrompt['uid']] = $existingPrompt;
            }
        }

        foreach ($prompts as $key => $prompt) {
            $uid = $prompt['uid'] ?? '';
            $existingPrompt = $uid ? ($existingPromptsByUid[$uid] ?? []) : [];
            $prompts[$key]['allPlainText'] = $plainTextTouched
                ? ($uid && in_array($uid, $plainTextKeys, true) ? '1' : '')
                : ($existingPrompt['allPlainText'] ?? '');
            $prompts[$key]['allCKEditor'] = $ckEditorTouched
                ? ($uid && in_array($uid, $ckEditorKeys, true) ? '1' : '')
                : ($existingPrompt['allCKEditor'] ?? '');
        }

        $promptSettings->saveAll([
            'prompts' => $prompts,
            'fieldAssignments' => $fieldAssignments,
            'fieldOrder' => $fieldOrder,
        ]);

        return $this->redirect('craft-loki/prompts');
    }
}
