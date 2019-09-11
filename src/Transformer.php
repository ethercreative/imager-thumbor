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
		$filters = [];

		// Format

		if ($format = @$transform['format'])
		{
			if ($format === 'jpg')
				$format = 'jpeg';

			$filters['format'] = $format;
		}

		// Trim

		if ($trim = @$transform['trim'])
		{
			if ($trim === true) $parts[] = 'trim';
			else $parts[] = 'trim:' . $trim;
		}

		// Mode

		if ($mode = @$transform['mode'])
		{
			switch ($mode)
			{
				default:
				case 'crop':
					// Do nothing, this is Thumbor's default
					break;
				case 'fit':
					$parts[] = 'fit-in';
					break;
				case 'stretch':
					$filters['stretch'] = null;
					break;
			}
		}

		// Size

		$size = [];

		if ($width = @$transform['width'])
			$size[] = $width;
		else
			$size[] = '';

		if ($height = @$transform['height'])
			$size[] = $height;
		else
			$size[] = '';

		if (!empty(array_filter($size)))
			$parts[] = implode('x', $size);

		// Position

		if ($position = @$transform['position'])
		{
			list($x, $y) = explode(' ', $position);
			$x = (int) ($image->getWidth() * (floatval($x) / 100));
			$y = (int) ($image->getHeight() * (floatval($y) / 100));

			$filters['focal'] = $x . 'x' . $y . ':' . ($x - 1) . 'x' . ($y - 1);
		}

		// Smart focal point detection

		if (@$transform['smart'])
			$parts[] = 'smart';

		// Upscale

		if (!@$transform['upscale'])
			$filters['no_upscale'] = '';

		// Effects

		if ($effects = @$transform['effects'])
		{
			foreach ($effects as $name => $args)
			{
				$value = $this->_parseFilterArgs($name, $args);

				if ($value === null)
					continue;

				$filters[$this->_parseFilterName($name)] = $value;
			}
		}

		// Filters

		if (!empty($filters))
		{
			$f = ['filters'];

			foreach ($filters as $name => $args)
				$f[] = $name . '(' . $args . ')';

			$parts[] = implode(':', $f);
		}

		if ($settings->local)
		{
			// TODO: Check to see if we have a cached version available
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

		$url = $this->_join(array_merge($url, $parts));

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
		$url = $this->_join($parts);
		$hash = hash_hmac('sha1', $url, $key);
		$hash = base64_encode($hash);

		$hash = str_replace('+', '-', $hash);
		$hash = str_replace('/', '_', $hash);

		return $hash;
	}

	private function _join ($parts = [])
	{
		$parts = array_map(function ($part) {
			return rtrim($part, '/');
		}, $parts);

		return implode('/', $parts);
	}

	private function _parseFilterName ($name)
	{
		$name = preg_replace('/(?<=\\w)(?=[A-Z])/','_$1', $name);
		$name = strtolower($name);

		return $name;
	}

	private function _parseFilterArgs ($name, $args)
	{
		if (in_array($name, ['equalize', 'grayscale']))
		{
			if ($args) return '';
			return null;
		}

		return $this->_parseFilterArgsValue($args);
	}

	private function _parseFilterArgsValue ($args)
	{
		if (is_bool($args))
			return $args ? 'True' : 'False';

		if (is_array($args))
			return implode(',', array_map([$this, '_parseFilterArgsValue'], $args));

		return $args;
	}

}