<?php namespace Model\Logger\Providers;

use Model\Config\Config;
use Model\Db\AbstractDbProvider;

class DbProvider extends AbstractDbProvider
{
	/**
	 * @return array|\string[][]
	 */
	public static function getMigrationsPaths(): array
	{
		$config = Config::get('logger');

		return $config['storage'] === 'db' ? [
			[
				'path' => 'vendor/model/logger/migrations',
			],
		] : [];
	}
}
