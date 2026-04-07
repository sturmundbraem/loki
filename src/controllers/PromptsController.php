<?php

namespace sturmundbraem\automatisations\controllers;

use Craft;
use craft\web\Controller;
use sturmundbraem\automatisations\Plugin;

class PromptsController extends Controller{

    public function actionIndex(){

        $settings = Plugin::$plugin->getSettings();
        $prompts = $settings->prompts;
        $allFields = Craft::$app->getFields()->getAllFields();
        $fieldAssignments = $settings->fieldAssignments;


        return $this->renderTemplate('stubr-automatisations/index', [
            'prompts' => $prompts,
            'allFields' => $allFields,
            'fieldAssignments' => $fieldAssignments,

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

        return $this->redirect('stubr-automatisations/prompts');
    }
}