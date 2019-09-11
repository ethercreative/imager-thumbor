<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use aelvan\imager\exceptions\ImagerException;
use aelvan\imager\models\ConfigModel;
use aelvan\imager\services\ImagerService;
use aelvan\imager\transformers\TransformerInterface;
use craft\elements\Asset;

/**
 * Class Transformer
 *
 * @author  Ether Creative
 * @package ether\imagerthumbor
 */
class Transformer implements TransformerInterface
{

	/**
	 * @param Asset|string $image
	 * @param array        $transforms
	 *
	 * @return array|null
	 *
	 * @throws ImagerException
	 */
	public function transform ($image, $transforms)
	{
		$transformedImages = [];

		foreach ($transforms as $transform)
			$transformedImages[] = $this->_getTransformedImage($image, $transform);

		return $transformedImages;
	}

	// Private
	// =========================================================================

	private function _getTransformedImage ($image, $transform): ThumborTransformedImage
	{
		// FIXME: Can't use Imagers config file, have to use own :(
		/** @var ConfigModel $config */
		$config = ImagerService::getConfig();
		$thumborConfigArr = $config->getSetting('thumborConfig', $transform);
		$thumborConfig = new ThumborConfig($thumborConfigArr ?? []);

		\Craft::dd(compact('thumborConfig', 'image', 'transform'));
	}

}