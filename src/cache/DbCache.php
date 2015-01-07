<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

use craft\app\db\DbConnection;
use craft\app\enums\ConfigFile;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * DbCache stores cache data in a DB table named [[cacheTableName]]. If the table does not exist, it will be
 * automatically created.
 *
 * DbCache relies on [PDO](http://www.php.net/manual/en/ref.pdo.php) to access database. By default, it will use
 * the database connection information stored in your craft/config/db.php file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DbCache extends \CDbCache
{
	// Public Methods
	// =========================================================================

	/**
	 * @return DbConnection
	 */
	public function getDbConnection()
	{
		return craft()->db;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @param DbConnection   $db
	 * @param string         $tableName
	 */
	protected function createCacheTable($db, $tableName)
	{
		if (!craft()->db->tableExists(craft()->config->get('cacheTableName', ConfigFile::DbCache), true))
		{
			parent::createCacheTable($db, $tableName);
		}
	}
}