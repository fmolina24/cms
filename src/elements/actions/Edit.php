<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

/**
 * Edit represents an Edit element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Edit extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The trigger label
     */
    public $label;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'Edit');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-editable');
        },
        activate: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element:first');

            if (Craft.elementIndex.viewMode === 'table') {
                new Craft.ElementEditor(\$element, {
                    params: {
                        includeTableAttributesForSource: Craft.elementIndex.sourceKey
                    },
                    onSaveElement: $.proxy(function(response) {
                        if (response.tableAttributes) {
                            Craft.elementIndex.view._updateTableAttributes(\$element, response.tableAttributes);
                        }
                    }, this)
                });
            } else {
                new Craft.ElementEditor(\$element);
            }
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
