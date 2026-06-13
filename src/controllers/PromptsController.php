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
        $prompts = $settings->prompts;
        $fieldAssignments = $settings->fieldAssignments;

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
        $savedOrder = $settings->fieldOrder;
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

        $request = Craft::$app->getRequest();
        $prompts = $request->getBodyParam('prompts', []);
        $fieldAssignments = $request->getBodyParam('fieldAssignments', []);
        $fieldOrderJson = $request->getBodyParam('fieldOrder', '');
        $fieldOrder = $fieldOrderJson ? (json_decode($fieldOrderJson, true) ?: []) : [];
        $buckets = $request->getBodyParam('bucketAssignments', []);

        $plainTextKeys = $buckets['allPlainText'] ?? [];
        $ckEditorKeys = $buckets['allCKEditor']  ?? [];

        foreach ($prompts as $key => $prompt) {
            $uid = $prompt['uid'] ?? '';
            $prompts[$key]['allPlainText'] = $uid && in_array($uid, $plainTextKeys, true) ? '1' : '';
            $prompts[$key]['allCKEditor']  = $uid && in_array($uid, $ckEditorKeys,  true) ? '1' : '';
        }

        $settings = Plugin::$plugin->getSettings();
        $settings->prompts = $prompts;

        Craft::$app->getPlugins()->savePluginSettings(Plugin::$plugin, [
            'prompts' => $prompts,
            'fieldAssignments' => $fieldAssignments,
            'fieldOrder' => $fieldOrder,
        ]);

        return $this->redirect('craft-loki/prompts');
    }
}
