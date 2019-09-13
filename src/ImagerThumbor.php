<?php
/**
 * Imager Thumbor Transformer
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\imagerthumbor;

use aelvan\imager\services\ImagerService;
use Craft;
use craft\base\Plugin;
use craft\web\View;
use yii\base\Event;

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
		ImagerService::$transformKeyTranslate['trim'] = 'T';

		Event::on(
			View::class,
			View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
			[$this, 'onAfterRenderPageTemplate']
		);
	}

	// Settings
	// =========================================================================

	protected function createSettingsModel ()
	{
		return new Settings();
	}

	/**
	 * @return Settings
	 */
	public function getSettings ()
	{
		return parent::getSettings();
	}

	// Events
	// =========================================================================

	public function onAfterRenderPageTemplate ()
	{
		if (empty(Transformer::$uploadedImages))
			return;

		$client = Craft::createGuzzleClient();
		$restEndpoint = self::join([$this->getSettings()->domain, 'image']);

		foreach (Transformer::$uploadedImages as $path)
			$client->delete(self::join([$restEndpoint, $path]));
	}

	// Helpers
	// =========================================================================

	public static function join ($parts)
	{
		$parts = array_map(function ($part) {
			return rtrim($part, '/');
		}, $parts);

		return implode('/', $parts);
	}

}