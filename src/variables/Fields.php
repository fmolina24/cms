<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\fieldtypes\BaseFieldType;
use craft\app\models\BaseElementModel;
use craft\app\models\Field              as FieldModel;
use craft\app\models\FieldGroup         as FieldGroupModel;
use craft\app\models\FieldLayout        as FieldLayoutModel;

/**
 * Class Fields variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Fields
{
	// Public Methods
	// =========================================================================

	// Groups
	// -------------------------------------------------------------------------

	/**
	 * Returns all field groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		return craft()->fields->getAllGroups($indexBy);
	}

	/**
	 * Returns a field group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return FieldGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return craft()->fields->getGroupById($groupId);
	}

	// Fields
	// -------------------------------------------------------------------------

	/**
	 * Returns a field by its ID.
	 *
	 * @param int $fieldId
	 *
	 * @return FieldModel|null
	 */
	public function getFieldById($fieldId)
	{
		return craft()->fields->getFieldById($fieldId);
	}

	/**
	 * Returns a field by its handle.
	 *
	 * @param string $handle
	 *
	 * @return FieldModel|null
	 */
	public function getFieldByHandle($handle)
	{
		return craft()->fields->getFieldByHandle($handle);
	}

	/**
	 * Returns all fields.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllFields($indexBy = null)
	{
		return craft()->fields->getAllFields($indexBy);
	}

	/**
	 * Returns all the fields in a given group.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getFieldsByGroupId($groupId, $indexBy = null)
	{
		return craft()->fields->getFieldsByGroupId($groupId, $indexBy);
	}

	// Layouts
	// -------------------------------------------------------------------------

	/**
	 * Returns a field layout by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutById($layoutId)
	{
		return craft()->fields->getLayoutById($layoutId);
	}

	/**
	 * Returns a field layout by its type.
	 *
	 * @param int $type
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutByType($type)
	{
		return craft()->fields->getLayoutByType($type);
	}

	// Fieldtypes
	// -------------------------------------------------------------------------

	/**
	 * Returns all installed fieldtypes.
	 *
	 * @return array
	 */
	public function getAllFieldTypes()
	{
		$fieldTypes = craft()->fields->getAllFieldTypes();
		return FieldType::populateVariables($fieldTypes);
	}

	/**
	 * Gets a fieldtype.
	 *
	 * @param string $class
	 *
	 * @return FieldType|null
	 */
	public function getFieldType($class)
	{
		$fieldType = craft()->fields->getFieldType($class);

		if ($fieldType)
		{
			return new FieldType($fieldType);
		}
	}

	/**
	 * Populates a fieldtype.
	 *
	 * @param FieldModel            $field
	 * @param BaseElementModel|null $element
	 *
	 * @return BaseFieldType|null
	 */
	public function populateFieldType(FieldModel $field, $element = null)
	{
		$fieldType = $field->getFieldType();

		if ($fieldType)
		{
			$fieldType->element = $element;
			return new FieldType($fieldType);
		}
	}
}