<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

/**
 * usage: php make.php <command>
 * command: build (default), clean
 **/

class app_env {
	public $_items;
	public function set($name, $value) {
		$this->_items[$name] = $value;
	}
	public function get($name) {
		if (array_key_exists($name, $this->_items)) {
			return $this->_items[$name];
		}
		return NULL;
	}
	public function exists($name) {
		return array_key_exists($this->_items);
	}
}

class app_servers {
	public $items;
	public function add($name) {
		$item = new \app_server;
		$item->name = $name;
		$this->items[$name] = $item;
		return $item;
	}
	public function exists($name) {
		return array_key_exists($name, $this->items);
	}
	public function item($name) {
		return $this->items[$name];
	}
}

class app_server {
	public $name;
	public $env;
	public function __construct() {
		$this->env = new \app_env;
	}
}

class app_tasks {
	public $items;
	public function add($name, $callback) {
		$item = new \app_task;
		$item->name = $name;
		$item->callback = $callback;
		$this->items[$name] = $item;
		return $item;
	}
	public function exists($name) {
		return isset($this->items[$name]);
	}
	public function item($name) {
		return $this->items[$name];
	}
}

class app_task {
	public $name;
	public $callback;
	public $server;
	public $env;
	public function __construct() {
		$this->env = new \app_env;
	}
	public function start() {
		$this->server = app::server();
		$_env = $this->env;
		$callback = $this->callback;
		$callback($this);
		$this->env = $_env;
	}
	public function copy($local, $remote) {

	}
}

class app_logger {
	public function error($message) {
		echo date('Y-m-d H:i:s')." ERROR $message\n";
		debug_print_backtrace();
		exit(1);
	}
	public function warning($message) {
		echo date('Y-m-d H:i:s')." WARNING $message\n";
	}
	public function debug($message) {
		echo date('Y-m-d H:i:s')." DEBUG $message\n";
	}
	public function info($message) {
		echo date('Y-m-d H:i:s')." INFO $message\n";
	}
}

class app {

	public static $env;
	public static $logger;
	public static $servers;
	public static $server; // current server
	public static $tasks;
	public static $task; // current task

	public static function env() {
		if (is_null(self::$env)) {
			self::$env = new \app_env;
		}
		return self::$env;
	}

	public static function servers() {
		if (is_null(self::$servers)) {
			self::$servers = new \app_servers;
		}
		return self::$servers;
	}

	public static function server() {
		return self::$servers->item('localhost');
	}

	public static function tasks($name=NULL) {
		if (is_null(self::$tasks)) {
			self::$tasks = new \app_tasks;
		}
		if (is_null($name) == FALSE) {
			if (self::$tasks->exists($name)) {
				$task = self::$tasks->item($name);
				return clone($task);
			} else {
				self::logger()->error('task "'.$name.'" is not exists.');
			}
		}
		return self::$tasks;
	}

	public static function start() {
		app::logger()->debug('starting ..');
		app::tasks('build')->start();
		app::logger()->debug('completed.');
	}

	public static function logger() {
		if (is_null(self::$logger)) {
			self::$logger = new \app_logger;
		}
		return self::$logger;
	}
}

app::env()->set('REPOSITORY', $_SERVER['HOME'].'/repos/test.git');

$server = app::servers()->add('localhost');
$server->protocol = 'file';
$server->environment = 'development';
$server->env->set('DEPLOY_PATH', $_SERVER['HOME'].'/tmp/test');

app::tasks()->add('build', function($task) {
	app::logger()->debug('start task "'.$task->name.'" on server "'.app::server()->name.'".');
	app::tasks('clean')->start();
// 	$task->env->set('TEMP_DIR', $task->server->text('mktemp -d'));
// 	$task->server->exec('rm -rf $TEMP_DIR');
});

app::start();

