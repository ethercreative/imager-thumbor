<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use yii\base\Model;

/**
 * Class ThumborTransformedRemoteImage
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class ThumborTransformedRemoteImage extends Model
{

	// Properties
	// =========================================================================

	/** @var string */
	public $url;

	// Methods
	// =========================================================================

	public function __toString ()
	{
		return $this->url;
	}

}