<?php namespace Model\Logger;

use Model\Core\AbstractModelProvider;

class ModelProvider extends AbstractModelProvider
{
	public static function realign(): void
	{
		Logger::cleanup();
	}
}
