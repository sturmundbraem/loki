<?php
namespace sturmundbraem\automatisations;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

// Asset bundles tell Craft which JS and CSS files to load on the page
// This one loads fieldHelper.js (click handlers) and styles.css (dropdown styling)
class FieldHelperAsset extends AssetBundle
{
    public function init()
    {
        // Where the JS/CSS files are located (relative to this plugin's src folder)
        $this->sourcePath = '@sturmundbraem/automatisations/resources';

        // Load Craft's CP assets first (jQuery, Craft.sendActionRequest, etc.)
        // Our JS depends on these being available
        $this->depends = [
            CpAsset::class,
        ];

        // JS files to load
        $this->js = [
            'fieldHelper.js',
        ];

        // CSS files to load
        $this->css = [
            'styles.css',
        ];

        parent::init();
    }
}