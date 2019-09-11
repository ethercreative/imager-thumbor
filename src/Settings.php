<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use craft\base\Model;

/**
 * Class Settings
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class Settings extends Model
{

	// Properties
	// =========================================================================

	/** @var string */
	public $domain;

	/** @var string|null */
	public $securityKey;

	/** @var bool */
	public $local = false;

	/** @var bool */
	public $webp = false;

}