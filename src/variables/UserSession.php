<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\User as UserModel;

/**
 * User session functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserSession
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether the user is logged in.
	 *
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return !craft()->getUser()->getIsGuest();
	}

	/**
	 * Returns the currently logged in user.
	 *
	 * @return UserModel|null
	 */
	public function getUser()
	{
		return craft()->getUser()->getIdentity();
	}

	/**
	 * Returns the number of seconds the user will be logged in for.
	 *
	 * @return int
	 */
	public function getAuthTimeout()
	{
		if (craft()->isInstalled())
		{
			return craft()->getUser()->getAuthTimeout();
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns the remembered username from cookie.
	 *
	 * @return string
	 */
	public function getRememberedUsername()
	{
		return craft()->getUser()->getRememberedUsername();
	}

	/**
	 * Returns the URL the user was trying to access before getting sent to the login page.
	 *
	 * @param string $defaultUrl The default URL that should be returned if no return URL was stored.
	 *
	 * @return string The return URL, or $defaultUrl.
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		return craft()->getUser()->getReturnUrl($defaultUrl);
	}

	/**
	 * Returns all flash data for the user.
	 *
	 * @param bool $delete
	 *
	 * @return array
	 */
	public function getFlashes($delete = true)
	{
		return craft()->getSession()->getAllFlashes($delete);
	}

	/**
	 * Returns a flash message by a given key.
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $delete
	 *
	 * @return mixed
	 */
	public function getFlash($key, $defaultValue = null, $delete = true)
	{
		return craft()->getUser()->getFlash($key, $defaultValue, $delete);
	}

	/**
	 * Returns whether a flash message exists by a given key.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function hasFlash($key)
	{
		return craft()->getUser()->hasFlash($key);
	}
}