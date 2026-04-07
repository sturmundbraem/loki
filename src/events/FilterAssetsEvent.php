<?php

namespace sturmundbraem\filepool\events;

use yii\base\Event;
use craft\elements\db\AssetQuery;

class FilterAssetsEvent extends Event
{
    public AssetQuery $query;
    public string $fieldHandle;
    public $fieldValue;
    public bool $isHidden;
}
