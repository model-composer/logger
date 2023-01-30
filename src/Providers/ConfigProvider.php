<?php namespace Model\Logger\Providers;

use Model\Config\AbstractConfigProvider;

class ConfigProvider extends AbstractConfigProvider
{
	public static function migrations(): array
	{
		return [
			[
				'version' => '0.1.0',
				'migration' => function (array $config, string $env) {
					return [
						'storage' => 'db',
						'long_ttl_on' => [
							'\\Model\\Core\\Events\\Error',
							'\\Model\\Db\\Events\\DeleteQuery',
							'\\Model\\Db\\Events\\UpdateQuery',
							'\\Model\\Db\\Events\\InsertQuery',
							'\\Model\\ORM\\Events\\Save',
							'\\Model\\ORM\\Events\\Delete',
						],
						'ttl' => [
							'short' => 1800,
							'long' => 1209600,
						],
					];
				},
			],
		];
	}
}
