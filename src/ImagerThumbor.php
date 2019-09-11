<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use aelvan\imager\services\ImagerService;
use craft\base\Plugin;

/**
 * Class ImagerThumbor
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class ImagerThumbor extends Plugin
{

	public function init ()
	{
		parent::init();

		ImagerService::$transformers['thumbor'] = Transformer::class;
	}

}