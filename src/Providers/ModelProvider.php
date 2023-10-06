<?php namespace Model\Logger\Providers;

use Model\Core\AbstractModelProvider;
use Model\Logger\Logger;

class ModelProvider extends AbstractModelProvider
{
	public static function realign(): void
	{
		Logger::cleanup();
	}
}
