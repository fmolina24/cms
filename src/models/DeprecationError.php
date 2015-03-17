<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * DeprecationError model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeprecationError extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Key
	 */
	public $key;

	/**
	 * @var string Fingerprint
	 */
	public $fingerprint;

	/**
	 * @var \DateTime Last occurrence
	 */
	public $lastOccurrence;

	/**
	 * @var string File
	 */
	public $file;

	/**
	 * @var integer Line
	 */
	public $line;

	/**
	 * @var string Class
	 */
	public $class;

	/**
	 * @var string Method
	 */
	public $method;

	/**
	 * @var string Template
	 */
	public $template;

	/**
	 * @var integer Template line
	 */
	public $templateLine;

	/**
	 * @var string Message
	 */
	public $message;

	/**
	 * @var array Traces
	 */
	public $traces;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['lastOccurrence'], 'craft\\app\\validators\\DateTime'],
			[['line'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['templateLine'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'key', 'fingerprint', 'lastOccurrence', 'file', 'line', 'class', 'method', 'template', 'templateLine', 'message', 'traces'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Returns a simple indication of the origin of the deprecation error.
	 *
	 * @return string
	 */
	public function getOrigin()
	{
		if ($this->template)
		{
			$file = $this->template;

			if (strncmp($file, 'string:', 7) === 0)
			{
				$file = substr($file, 7);
				$line = null;
			}
			else
			{
				$line = $this->templateLine;
			}
		}
		else
		{
			$file = $this->file;
			$line = $this->line;
		}

		return $file.($line ? " ({$line})" : '');
	}
}