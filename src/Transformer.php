<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

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

	/**
	 * @param Asset $image
	 * @param array $transform
	 *
	 * @return ThumborTransformedImage
	 */
	private function _getTransformedImage ($image, $transform): ThumborTransformedImage
	{
		/** @var Settings $settings */
		$settings = ImagerThumbor::getInstance()->getSettings();

		$parts = [];

		// TODO: build url (remember webp setting, if it's even possible)

		if ($settings->local)
		{
			// TODO: upload file to thumbor server (as unique)

			$parts[] = ''; // TODO: Use returned file location as file path
		}
		else
		{
			$parts[] = $image->getUrl();
		}

		$url = [$settings->domain];

		if ($settings->securityKey)
			$url[] = $this->_generateKey($parts, $settings->securityKey);
		else
			$url[] = 'unsafe';

		$url = $this->_join($url);

		if ($settings->local)
		{
			// TODO: Get the URL & save
			// TODO: Delete image on thumbor server

			$url = ''; // TODO: whatever the local url is
		}

		return new ThumborTransformedImage([
			'url' => $url,
		]);
	}

	// Helpers
	// =========================================================================

	private function _generateKey ($parts, $key)
	{
		// TODO: this

		return '';
	}

	private function _join ($parts = [])
	{
		$parts = array_map(function ($part) {
			return rtrim($part, '/');
		}, $parts);

		return implode('/', $parts);
	}

}