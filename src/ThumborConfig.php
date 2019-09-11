<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use Yii;

/**
 * Class ThumborConfig
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class ThumborConfig
{

	// Properties
	// =========================================================================

	/** @var string */
	public $domain;

	/** @var bool */
	public $webp = false;

	// Constructor
	// =========================================================================

	public function __construct ($config = [])
	{
		if (!empty($config))
			Yii::configure($this, $config);
	}

}