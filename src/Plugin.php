<?php
namespace stubr;

// Craft and Yii framework classes
use Craft;
use yii\base\Event;
use craft\base\Model;
use craft\base\Field;
use craft\events\DefineFieldHtmlEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\services\Plugins;

// Plugin's own classes
use stubr\models\Settings;
use stubr\FieldHelperAsset;


class Plugin extends \craft\base\Plugin
{
    // A reference to this plugin instance, accessible from anywhere via Plugin::$plugin
    public static $plugin;

    // Database schema version — increment when you change database structure
    public string $schemaVersion = "1.0.1";

    // Show a settings page for this plugin in the CP
    public bool $hasCpSettings = true;

    // Show a section in the CP for this plugin
    public bool $hasCpSection = true;

    // Tell Craft which model holds our plugin settings
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    // Render the settings page HTML in the CP
    // This is shown when admin goes to Settings > Plugins > your plugin
    protected function settingsHtml(): string
    {
        
        // Render the Twig template and pass it the settings + fields data
        return \Craft::$app->getView()->renderTemplate(
            'craft-cp-ai/settings.twig',
            [
                'settings' => $this->getSettings(),
            ]
        );

    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'AI Prompts';
        $item['url'] = 'craft-cp-ai/prompts';
        return $item;
    }

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            \craft\web\UrlManager::class,
            \craft\web\UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (\craft\events\RegisterUrlRulesEvent $event) {
                $event->rules['craft-cp-ai/prompts'] = 'craft-cp-ai/prompts/index';
            }
        );


        // Defer setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            // ...
        });

        // Listen for when Craft renders any field's input HTML
        // This fires once for EACH field on the entry edit page
        Event::on(
            Field::class,                       // Listen on all field objects
            Field::EVENT_DEFINE_INPUT_HTML,      // The event that fires when field HTML is built
            function (DefineFieldHtmlEvent $event) {

            // Only add the wand button to PlainText and CKEditor fields
            if ($event->sender instanceof \craft\fields\PlainText || $event->sender instanceof \craft\ckeditor\Field) {

                // Load our JS and CSS files (fieldHelper.js, styles.css)
                Craft::$app->getView()->registerAssetBundle(FieldHelperAsset::class);

                // Load the prompts config and inject it as a JS variable
                // This makes the prompts available in the browser as window.aiPrompts
                $prompts = self::$plugin->getSettings()->prompts;
                Craft::$app->getView()->registerJs('var aiPrompts = ' . json_encode($prompts) . ';', \yii\web\View::POS_HEAD);
                $fieldAssignments = self::$plugin->getSettings()->fieldAssignments;
                Craft::$app->getView()->registerJs('var aiFieldAssignments = ' . json_encode($fieldAssignments) . ';', \yii\web\View::POS_HEAD);

                // Get the field's handle (e.g. "subtitle") and type (e.g. "PlainText")
                $fieldHandle = $event->sender->handle;
                $fieldType  = (new \ReflectionClass($event->sender))->getShortName();

                // Append a wand button to the field's HTML
                // data-field: tells JS which field this button belongs to
                // data-type: tells JS the field type (for picking the right prompts)
                // style="display:none": hidden initially, the inline script moves it to the right place
                $event->html .= '
                <button type="button" class="ai-wand-btn btn small icon" data-icon="wand-magic-sparkles" data-field="' . $fieldHandle . '" data-type="' . $fieldType . '" style="display:none"></button>

                <script>
                    (function() {
                        // Find THIS field\'s wand button and move it into the heading area
                        // (next to the field label) instead of below the input
                        const btn = document.querySelector(\'button.ai-wand-btn[data-field="' . $fieldHandle . '"]\');
                        const field = btn.closest(".field");
                        const heading = field.querySelector(".heading .flex-grow");
                        if (heading) {
                            heading.before(btn);
                            btn.style.display = "";
                        }
                    })();
                </script>';
                }
            }
        );
    }
}