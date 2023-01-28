<?php namespace Model\Logger;

use Model\Config\Config;
use Model\Core\AbstractModelProvider;

class ModelProvider extends AbstractModelProvider
{
	public static function realign(): void
	{
		$config = Config::get('logger');

		switch ($config['storage']) {
			case 'db':
				\Model\Db\Db::getConnection()->delete('model_logs', ['expire_at' > ['<=', date('Y-m-d H:i:s')]]);
				break;
		}
	}
}
