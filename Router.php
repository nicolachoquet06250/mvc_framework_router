<?php

	namespace mvc_framework\core\router;

	use mvc_framework\app\mvc\controllers\Errors;

	class Router {
		private static $routes = [];
		private static $content_types = ['api' => 'application/json', 'front' => 'text/html'];

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
				$callback = self::$routes[$route];
				return $callback($templating, $http_argv);
			}
			return self::_404($templating, $http_argv);
		}

		public static function execute_route($url, $templating, $http_argv) {
			if(self::has_exact_route($url)) {
				return self::execute_exact_route($url, $templating, $http_argv);
			}
			elseif(self::has_route_with_variables($url)) {
				return self::execute_route_with_variables($url, $templating, $http_argv);
			}
			else {
				return self::_404($templating, $http_argv);
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

		private static function _404($templating, $http_argv) {
			require_once __DIR__.'/../../app/public/mvc/controllers/Errors.php';
			$controller = new Errors($templating, $http_argv);
			return $controller->_404();
		}
	}
