<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\Section as SectionModel;

/**
 * Class Sections variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Sections
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all sections.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllSections($indexBy = null)
	{
		return craft()->sections->getAllSections($indexBy);
	}

	/**
	 * Returns all editable sections.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getEditableSections($indexBy = null)
	{
		return craft()->sections->getEditableSections($indexBy);
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @return int
	 */
	public function getTotalSections()
	{
		return craft()->sections->getTotalSections();
	}

	/**
	 * Gets the total number of sections that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSections()
	{
		return craft()->sections->getTotalEditableSections();
	}

	/**
	 * Returns a section by its ID.
	 *
	 * @param int $sectionId
	 *
	 * @return SectionModel|null
	 */
	public function getSectionById($sectionId)
	{
		return craft()->sections->getSectionById($sectionId);
	}

	/**
	 * Returns a section by its handle.
	 *
	 * @param string $handle
	 *
	 * @return SectionModel|null
	 */
	public function getSectionByHandle($handle)
	{
		return craft()->sections->getSectionByHandle($handle);
	}
}