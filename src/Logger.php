<?php namespace Model\Logger;

use Model\Config\Config;
use Model\Events\AbstractEvent;
use Model\Events\Events;
use Model\Logger\Events\Error;

class Logger
{
	private static bool $enabled;
	private static array $long_ttl_reasons = [];
	private static array $events = [];

	public static function enable(): void
	{
		if (!isset(self::$enabled)) { // First init
			$config = Config::get('logger');

			Events::subscribeTo('*', function (AbstractEvent $event) use ($config) {
				if (self::$enabled) {
					$eventName = $event->getEventName();
					if (in_array($eventName, $config['long_ttl_on'])) {
						if (!in_array($eventName, self::$long_ttl_reasons))
							self::$long_ttl_reasons[] = $eventName;
					}

					self::$events[] = [
						'type' => 'event',
						'event' => $eventName,
						'data' => $event->getData(),
						'time' => microtime(true),
					];
				}
			});

			set_error_handler(function (int $errno, string $errstr, ?string $errfile = null, ?int $errline = null) {
				if (error_reporting() > 0)
					Events::dispatch(new Error($errno, $errstr, $errfile, $errline));
			});
		}

		self::$enabled = true;
	}

	public static function disable(): void
	{
		self::$enabled = false;
	}

	public static function isEnabled(): bool
	{
		return (isset(self::$enabled) and self::$enabled);
	}

	public static function persist(): void
	{
		if (!self::isEnabled() or count(self::$events) === 0)
			return;

		$config = Config::get('logger');

		switch ($config['storage']) {
			case 'db':
				self::disable(); // Avoid logging final query

				$db = \Model\Db\Db::getConnection();

				try {
					if (!defined('MYSQL_MAX_ALLOWED_PACKET')) {
						$max_allowed_packet_query = $db->query('SHOW VARIABLES LIKE \'max_allowed_packet\'')->fetch();
						if ($max_allowed_packet_query)
							define('MYSQL_MAX_ALLOWED_PACKET', $max_allowed_packet_query['Value']);
						else
							define('MYSQL_MAX_ALLOWED_PACKET', 1000000);
					}

					$prepared_server = $db->parseValue(json_encode($_SERVER));
					$prepared_session = $db->parseValue(json_encode($_SESSION));
					$prepared_events = $db->parseValue(json_encode(self::$events));

					if (strlen($prepared_session) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_session = '\'TOO LARGE\'';
					if (strlen($prepared_events) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_events = '\'TOO LARGE\'';

					// TODO: move here from ModEl 3, after that cookie law will be handled here as well
					$user_hash = isset($_COOKIE['ZKID']) ? $db->parseValue($_COOKIE['ZKID']) : 'NULL';

					$post = $_POST;
					$payload = file_get_contents('php://input');
					if (empty($post) and !empty($payload)) {
						$post = json_decode($payload, true); // Attempts to decode
						if (!$post)
							$post = $payload;
					}

					$prepared_post = $db->parseValue(json_encode($post));
					if (strlen($prepared_post) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_post = '\'TOO LARGE\'';

					if (strlen($prepared_server) + strlen($prepared_session) + strlen($prepared_post) > MYSQL_MAX_ALLOWED_PACKET - 400)
						throw new \Exception('Packet too large');

					$url = $_SERVER['REQUEST_URI'];

					$loading_id = defined('MODEL_LOADING_ID') ? $db->parseValue(MODEL_LOADING_ID) : 'NULL';

					$ttl = (count(self::$long_ttl_reasons) > 0) ? $config['ttl']['long'] : $config['ttl']['short'];
					$expireAt = date_create();
					$expireAt->modify('+' . $ttl . ' seconds');

					$db->query('INSERT INTO `model_logs`(
						`date`,
						`user_hash`,
						`method`,
						`url`,
						`get`,
						`loading_id`,
						`expire_at`,
						`reasons`
					) VALUES(
						' . $db->parseValue(date('Y-m-d H:i:s')) . ',
						' . $user_hash . ',
						' . $db->parseValue($_SERVER['REQUEST_METHOD']) . ',
						' . $db->parseValue($url) . ',
						' . $db->parseValue(http_build_query($_GET)) . ',
						' . $loading_id . ',
						' . $db->parseValue($expireAt->format('Y-m-d H:i:s')) . ',
						' . $db->parseValue(implode(',', self::$long_ttl_reasons)) . '
					)');

					$id = $db->getDb()->lastInsertId();

					$db->query('UPDATE `model_logs` SET `server` = ' . $prepared_server . ' WHERE `id` = ' . $id);
					$db->query('UPDATE `model_logs` SET `session` = ' . $prepared_session . ' WHERE `id` = ' . $id);
					$db->query('UPDATE `model_logs` SET `events` = ' . $prepared_events . ' WHERE `id` = ' . $id);
					$db->query('UPDATE `model_logs` SET `post` = ' . $prepared_post . ' WHERE `id` = ' . $id);
				} catch (\Exception $e) {
				}
				break;
		}
	}
}
