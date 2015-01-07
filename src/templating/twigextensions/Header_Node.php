<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Header_Node
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Header_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles a Header_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->write('\Craft\HeaderHelper::setHeader(')
			->subcompile($this->getNode('header'))
			->raw(");\n");
	}
}