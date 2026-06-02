<?php

namespace stubr\controllers;

use Craft;
use craft\web\Controller;
use stubr\Plugin;

class PromptsController extends Controller{

    public function actionIndex(){

        $settings = Plugin::$plugin->getSettings();
        $prompts = $settings->prompts;
        $allFields = Craft::$app->getFields()->getAllFields();
        $fieldAssignments = $settings->fieldAssignments;

        return $this->renderTemplate('craft-cp-ai/index', [
            'prompts' => $prompts,
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

        $settings = Plugin::$plugin->getSettings();
        $settings->prompts = $prompts;

        Craft::$app->getPlugins()->savePluginSettings(Plugin::$plugin, [
            'prompts' => $prompts,
            'fieldAssignments' => $fieldAssignments,
        ]);

        return $this->redirect('craft-cp-ai/prompts');
    }
}