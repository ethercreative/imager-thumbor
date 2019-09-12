<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use aelvan\imager\exceptions\ImagerException;
use aelvan\imager\externalstorage\ImagerStorageInterface;
use aelvan\imager\models\LocalSourceImageModel;
use aelvan\imager\models\LocalTargetImageModel;
use aelvan\imager\services\ImagerService;
use aelvan\imager\transformers\TransformerInterface;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use yii\base\ErrorException;

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
	 * @throws ImagerException
	 * @throws ErrorException
	 */
	public function transform ($image, $transforms)
	{
		if ($image->getExtension() === 'svg')
		{
			Craft::error('Thumbor does not support SVG images.');
			return null;
		}

		$sourceModel = new LocalSourceImageModel($image);

		$transformedImages = [];

		foreach ($transforms as $transform)
			$transformedImages[] = $this->_getTransformedImage($sourceModel, $image, $transform);

		return $transformedImages;
	}

	// Private
	// =========================================================================

	/**
	 * @param LocalSourceImageModel $sourceModel
	 * @param Asset                 $image
	 * @param array                 $transform
	 *
	 * @return ThumborTransformedImage|ThumborTransformedRemoteImage
	 * @throws ImagerException
	 * @throws ErrorException
	 */
	private function _getTransformedImage ($sourceModel, $image, $transform)
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
		$ratio = @$transform['ratio'];
		$width = @$transform['width'];
		$height = @$transform['height'];

		if ($width)
			$size[] = $width;
		else
			$size[] = $ratio && $height ? $height * $ratio : '';

		if ($height)
			$size[] = $height;
		else
			$size[] = $ratio && $width ? $width * $ratio : '';

		if (!empty(array_filter($size)))
			$parts[] = implode('x', $size);

		// Position

		if ($position = @$transform['position'])
		{
			list($x, $y) = explode(' ', $position);
			$x = (int) ($image->getWidth() * (floatval($x) / 100));
			$y = (int) ($image->getHeight() * (floatval($y) / 100));

			if ($x < 1) $x++;
			if ($y < 1) $y++;

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

		// Saving

		$config = ImagerService::getConfig();
		$client = Craft::createGuzzleClient();

		$restEndpoint = $this->_join([$settings->domain, 'image']);
		$uploadedPath = null;

		$targetModel = new LocalTargetImageModel($sourceModel, $transform);

		if ($settings->local)
		{
			if (
				!$config->getSetting('cacheEnabled', $transform) ||
			    !file_exists($targetModel->getFilePath()) ||
			    (
			    	($config->getSetting('cacheDuration', $transform) !== false) &&
				    (FileHelper::lastModifiedTime($targetModel->getFilePath()) + $config->getSetting('cacheDuration', $transform) < time())
			    )
			) {
				$sourceModel->getLocalCopy();
				$targetModel->isNew = true;

				$name = uniqid('tb_') . $sourceModel->filename;

				$posted = $client->post($restEndpoint, [
					'headers' => [
						'Slug' => $name,
					],
					'body' => fopen($sourceModel->getFilePath(), 'r'),
				]);

				$uploadedPath = str_replace('/image/', '', $posted->getHeader('Location')[0]);
				$parts[] = $uploadedPath;
			}
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
			if ($targetModel->isNew)
			{

				if (empty($config->storages))
				{
					FileHelper::writeToFile(
						$targetModel->getFilePath(),
						$client->get($url)->getBody()->getContents()
					);
				}
				else
				{
					foreach ($config->storages as $storage)
					{
						if (isset(ImagerService::$storage[$storage]))
						{
							$storageSettings = $config->storageConfig[$storage] ?? null;

							if ($storageSettings)
							{
								/** @var ImagerStorageInterface $storageClass */
								$storageClass = ImagerService::$storage[$storage];
								$storageClass::upload(
									$url,
									$targetModel->getFilePath(),
									true,
									$storageSettings
								);
							}
							else
							{
								$msg = 'Could not find settings for storage "' . $storage . '"';
								Craft::error($msg, __METHOD__);
								throw new ImagerException($msg);
							}
						}
						else
						{
							$msg = 'Could not find a registered storage with handle "' . $storage . '"';
							Craft::error($msg, __METHOD__);
							throw new ImagerException($msg);
						}
					}
				}

				$client->delete(
					$this->_join([
						$restEndpoint,
						$uploadedPath,
					])
				);
			}
		}
		else
		{
			$targetModel->url = $url;
		}

		if (!$settings->local)
		{
			return new ThumborTransformedRemoteImage([
				'url' => $targetModel->url,
			]);
		}

		return new ThumborTransformedImage(
			$targetModel,
			$sourceModel,
			$transform
		);
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