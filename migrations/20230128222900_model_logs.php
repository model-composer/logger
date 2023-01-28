<?php

use Phinx\Migration\AbstractMigration;

class ModelLogs extends AbstractMigration
{
	public function change()
	{
		$this->table('model_logs')
			->addColumn('server', 'blob', ['null' => false])
			->addColumn('session', 'blob', ['null' => false])
			->addColumn('events', 'longblob', ['null' => false])
			->addColumn('date', 'datetime', ['null' => false])
			->addColumn('user_hash', 'string')
			->addColumn('method', 'string')
			->addColumn('url', 'string')
			->addColumn('get', 'string')
			->addColumn('post', 'string')
			->addColumn('loading_id', 'string', ['null' => false])
			->addColumn('expire_at', 'datetime')
			->addColumn('reasons', 'string')
			->addIndex('expire_at')
			->addIndex('date')
			->create();
	}
}
