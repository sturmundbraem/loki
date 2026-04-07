<?php

namespace sturmundbraem\automatisations\models;

use craft\base\Model;

class Settings extends Model
{
    // Defines all fields which are hidden from the field filter table
    public array $hiddenFields = [];

    public array $fieldAssignments = [];


    public array $prompts = [];
    // Defines all fields which will not filter the assets for the file pool
    // Instead the filtering can be done on the project side using events
    public array $skipDefaultHandlingFields = [];
}