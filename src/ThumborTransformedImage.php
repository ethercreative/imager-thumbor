<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use aelvan\imager\models\TransformedImageInterface;
use craft\base\Model;

/**
 * Class ThumborTransformedImage
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class ThumborTransformedImage extends Model implements TransformedImageInterface
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