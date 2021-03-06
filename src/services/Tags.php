<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Tag;
use craft\errors\TagGroupNotFoundException;
use craft\events\TagGroupEvent;
use craft\models\TagGroup;
use craft\records\TagGroup as TagGroupRecord;
use yii\base\Component;

/**
 * Class Tags service.
 *
 * An instance of the Tags service is globally accessible in Craft via [[Application::tags `Craft::$app->getTags()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Tags extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event TagGroupEvent The event that is triggered before a tag group is saved.
     */
    const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is saved.
     */
    const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered before a tag group is deleted.
     */
    const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_allTagGroupIds;

    /**
     * @var
     */
    private $_tagGroupsById;

    /**
     * @var bool
     */
    private $_fetchedAllTagGroups = false;

    // Public Methods
    // =========================================================================

    // Tag groups
    // -------------------------------------------------------------------------

    /**
     * Returns all of the group IDs.
     *
     * @return array
     */
    public function getAllTagGroupIds(): array
    {
        if ($this->_allTagGroupIds !== null) {
            return $this->_allTagGroupIds;
        }

        if ($this->_fetchedAllTagGroups) {
            return $this->_allTagGroupIds = array_keys($this->_tagGroupsById);
        }

        return $this->_allTagGroupIds = (new Query())
            ->select(['id'])
            ->from(['{{%taggroups}}'])
            ->column();
    }

    /**
     * Returns all tag groups.
     *
     * @return TagGroup[]
     */
    public function getAllTagGroups(): array
    {
        if (!$this->_fetchedAllTagGroups) {
            $this->_tagGroupsById = TagGroupRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->indexBy('id')
                ->all();

            foreach ($this->_tagGroupsById as $key => $value) {
                $this->_tagGroupsById[$key] = new TagGroup($value->toArray([
                    'id',
                    'name',
                    'handle',
                    'fieldLayoutId',
                ]));
            }

            $this->_fetchedAllTagGroups = true;
        }

        return array_values($this->_tagGroupsById);
    }

    /**
     * Gets the total number of tag groups.
     *
     * @return int
     */
    public function getTotalTagGroups(): int
    {
        return count($this->getAllTagGroupIds());
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     *
     * @return TagGroup|null
     */
    public function getTagGroupById(int $groupId)
    {
        if ($this->_tagGroupsById !== null && array_key_exists($groupId, $this->_tagGroupsById)) {
            return $this->_tagGroupsById;
        }

        if ($this->_fetchedAllTagGroups) {
            return null;
        }

        if (($groupRecord = TagGroupRecord::findOne($groupId)) === null) {
            return $this->_tagGroupsById[$groupId] = null;
        }

        return $this->_tagGroupsById[$groupId] = new TagGroup($groupRecord->toArray([
            'id',
            'name',
            'handle',
            'fieldLayoutId',
        ]));
    }

    /**
     * Gets a group by its handle.
     *
     * @param string $groupHandle
     *
     * @return TagGroup|null
     */
    public function getTagGroupByHandle(string $groupHandle)
    {
        $groupRecord = TagGroupRecord::findOne([
            'handle' => $groupHandle
        ]);

        if ($groupRecord) {
            return new TagGroup($groupRecord->toArray([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
            ]));
        }

        return null;
    }

    /**
     * Saves a tag group.
     *
     * @param TagGroup $tagGroup      The tag group to be saved
     * @param bool     $runValidation Whether the tag group should be validated
     *
     * @return bool Whether the tag group was saved successfully
     * @throws TagGroupNotFoundException if $tagGroup->id is invalid
     * @throws \Exception if reasons
     */
    public function saveTagGroup(TagGroup $tagGroup, bool $runValidation = true): bool
    {
        if ($runValidation && !$tagGroup->validate()) {
            Craft::info('Tag group not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewTagGroup = !$tagGroup->id;

        // Fire a 'beforeSaveGroup' event
        $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new TagGroupEvent([
            'tagGroup' => $tagGroup,
            'isNew' => $isNewTagGroup
        ]));

        if (!$isNewTagGroup) {
            $tagGroupRecord = TagGroupRecord::findOne($tagGroup->id);

            if (!$tagGroupRecord) {
                throw new TagGroupNotFoundException("No tag group exists with the ID '{$tagGroup->id}'");
            }

            $oldTagGroup = new TagGroup($tagGroupRecord->toArray([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
            ]));
        } else {
            $tagGroupRecord = new TagGroupRecord();
        }

        $tagGroupRecord->name = $tagGroup->name;
        $tagGroupRecord->handle = $tagGroup->handle;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Is there a new field layout?
            $fieldLayout = $tagGroup->getFieldLayout();
            if (!$fieldLayout->id) {
                // Delete the old one
                /** @noinspection PhpUndefinedVariableInspection */
                if (!$isNewTagGroup && $oldTagGroup->fieldLayoutId) {
                    Craft::$app->getFields()->deleteLayoutById($oldTagGroup->fieldLayoutId);
                }

                // Save the new one
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Update the tag group record/model with the new layout ID
                $tagGroup->fieldLayoutId = $fieldLayout->id;
                $tagGroupRecord->fieldLayoutId = $fieldLayout->id;
            }

            // Save it!
            $tagGroupRecord->save(false);

            // Now that we have a tag group ID, save it on the model
            if (!$tagGroup->id) {
                $tagGroup->id = $tagGroupRecord->id;
            }

            // Might as well update our cache of the tag group while we have it.
            $this->_tagGroupsById[$tagGroup->id] = $tagGroup;

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGroup' event
        $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new TagGroupEvent([
            'tagGroup' => $tagGroup,
            'isNew' => $isNewTagGroup,
        ]));

        return true;
    }

    /**
     * Deletes a tag group by its ID.
     *
     * @param int $tagGroupId
     *
     * @return bool Whether the tag group was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteTagGroupById(int $tagGroupId): bool
    {
        if (!$tagGroupId) {
            return false;
        }

        $tagGroup = $this->getTagGroupById($tagGroupId);

        if (!$tagGroup) {
            return false;
        }

        // Fire a 'beforeDeleteGroup' event
        $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new TagGroupEvent([
            'tagGroup' => $tagGroup
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%taggroups}}'])
                ->where(['id' => $tagGroupId])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the tags
            $tags = Tag::find()
                ->status(null)
                ->enabledForSite(false)
                ->groupId($tagGroupId)
                ->all();

            foreach ($tags as $tag) {
                Craft::$app->getElements()->deleteElement($tag);
            }

            Craft::$app->getDb()->createCommand()
                ->delete('{{%taggroups}}', ['id' => $tagGroupId])
                ->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGroup' event
        $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new TagGroupEvent([
            'tagGroup' => $tagGroup
        ]));

        return true;
    }

    // Tags
    // -------------------------------------------------------------------------

    /**
     * Returns a tag by its ID.
     *
     * @param int      $tagId
     * @param int|null $siteId
     *
     * @return Tag|null
     */
    public function getTagById(int $tagId, int $siteId = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($tagId, Tag::class, $siteId);
    }
}
