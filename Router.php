<?php

	namespace mvc_framework\core\router;

	use mvc_framework\app\mvc\controllers\Errors;

	class Router {
		private static $content_types = ['api' => 'application/json', 'front' => 'text/html'], $routes = [];

		public static function route($url, $callback, $type = 'api') {
			if(isset(self::$content_types[$type])) {
				self::$routes[$url] = $callback;
			}
		}

		public static function route_controller($url, $class, $method, $type = 'api') {
			if(isset(self::$content_types[$type])) {
				self::$routes[$url] = [
					$class,
					$method
				];
			}
		}

		public static function has_exact_route($url) {
  			return isset(self::$routes[$url]);
		}

		public static function has_route_with_variables($url) {
			return self::calcul_url_with_vars($url) === false ? false : true;
		}

		private static function execute_exact_route($url, $templating, $http_argv) {
			$callback = self::$routes[$url];
			if(gettype($callback) === 'array') {
				if(self::contains($callback[0], '\mvc\controllers')) {
					require_once __DIR__.'/../../app/public/mvc/controllers/'.explode('\\', $callback[0])[count(explode('\\', $callback[0]))-1].'.php';
					$controller = new $callback[0]($templating, $http_argv);
					$method = $callback[1];
					return $controller->$method();
				}
			}
			return $callback($templating, $http_argv);
		}

		private static function execute_route_with_variables($url, $templating, $http_argv) {
			if(($route = self::calcul_url_with_vars($url)) !== false) {
				$local_vars = self::calcul_local_vars($route, $url);
				$callback = self::$routes[$route];
				if(gettype($callback) === 'array') {
					if(self::contains($callback[0], '\mvc\controllers')) {
						require_once __DIR__.'/../../app/public/mvc/controllers/'.explode('\\', $callback[0])[count(explode('\\', $callback[0]))-1].'.php';
						$controller = new $callback[0]($templating, $http_argv, $local_vars);
						$method = $callback[1];
						return $controller->$method();
					}
				}
				return $callback($templating, $http_argv, $local_vars);
			}
			return self::_404($templating, $http_argv, 'Route for current URL not found !');
		}

		public static function execute_route($url, $templating, $http_argv) {
			if(self::has_exact_route($url)) {
				return self::execute_exact_route($url, $templating, $http_argv);
			}
			elseif(self::has_route_with_variables($url)) {
				return self::execute_route_with_variables($url, $templating, $http_argv);
			}
			else {
				$uri_base = explode('?', $url)[0];
				$uri_base = explode('/', $uri_base);
				$ctrl = $uri_base[1];
				$method = ucfirst(strtolower($_SERVER['REQUEST_METHOD']));
				if(file_exists(realpath(__DIR__.'/../../app/public/mvc/controllers/'.$ctrl.'.php'))) {
					require_once realpath(__DIR__.'/../../app/public/mvc/controllers/'.$ctrl.'.php');
					$ctrl_class = '\mvc_framework\app\mvc\controllers\\'.$ctrl;
					if(in_array($method, get_class_methods($ctrl_class))) {
						return (new $ctrl_class($templating, $http_argv))->$method();
					}
					return self::_404($templating, $http_argv, 'Method '.$method.' not found in '.$ctrl.' controller !');
				}
				return self::_404($templating, $http_argv, 'Controller '.$ctrl.' not found !');
			}
		}

		private static function contains($haystack, $needle) {
			return strstr($haystack, $needle);
		}

		private static function calcul_url_with_vars($url) {
			$url_exists = false;

			$url_exploded = explode('/', $url);
			foreach (array_keys(self::$routes) as $route) {
				if(self::contains($route, '@')) {
					$route_exploded = explode('/', $route);
					if(count($url_exploded) === count($route_exploded)) {
						$nb_vars     = 0;
						$nb_vars_tmp = count($url_exploded);

						foreach ($route_exploded as $part) {
							if(!self::contains($part, '@')) {
								$nb_vars_tmp--;
							}
							else {
								$nb_vars++;
							}
						}

						if($nb_vars === $nb_vars_tmp) {
							$url_exists = $route;
							break;
						}
					}
				}
			}
			return $url_exists;
		}

		private static function calcul_local_vars($route, $url) {
			$route_exploded = explode('/', $route);
			$url_exploded = explode('/', $url);

			$local_vars = [];

			foreach ($route_exploded as $id => $route_part) {
				if($route_part !== $url_exploded[$id]) {
					$parsed = is_numeric($url_exploded[$id]) ? (int)$url_exploded[$id] : $url_exploded[$id];
					$local_vars[str_replace('@', '', $route_part)] = $parsed;
				}
			}
			return $local_vars;
		}

		private static function _404($templating, $http_argv, $message = '') {
			require_once __DIR__.'/../../app/public/mvc/controllers/Errors.php';
			$controller = new Errors($templating, $http_argv);
			return $controller->_404($message);
		}
	}
