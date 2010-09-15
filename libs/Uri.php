<?php
class Uri extends App {

	public $uriArray;
	public $controller;
	public $contorllerFile;
	public $protocol;
	
	//new:
	public $domain;
	public $request;
	public $count = 0;
	public $contorller;

	function __construct() {
		/*
		@todo:
			X Make this library work with the new config set up.
			- Simplify out the class
				- Clean up the functions and remove the ones that im not useing
				- Clean up the classes varibles
				X Add in a callRoute() function that essenitally calls up the correct controller
				X Maybe rewrite the loadUrl function to make it more modular
			- Make it clearer to as what is happening in this code
			X Don't make it so coupled with $_SERVER['QUERY']
		*/
		//[0] => helldsdfs34&what=4
		$this->request = $_SERVER['QUERY_STRING'];
		//[HTTP_HOST] => localhost
		$this->domain = $_SERVER['HTTP_HOST'];
		//http or https?
		$this->protocol = strtolower(strstr($_SERVER['SERVER_PROTOCOL'], '/', true));;

		$folder = strstr($_SERVER['REQUEST_URI'] .'?', '?', true);
		
		if($this->lib('Config')->get('site', 'prettyUrls')) {
		
			if($this->request) {
				define('URL', $this->protocol . '://' . $this->domain . substr($folder, 0, -strlen($this->request)) );
			} else {
				define('URL', $this->protocol . '://' . $this->domain . $folder );
			}
		
			define('SITE_URL', URL);
		} else {
			define('URL', $this->protocol . '://' . $this->domain . $folder );
			define('SITE_URL', URL . '?');
		}
		D::log(SITE_URL . $this->request, 'URI Library Loaded');
		D::log(SITE_URL, 'SITE_URL');
		D::log($this->request, 'Request');
		
		$this->contorllerFile = $this->libs->Config->get('site', 'mainController');
	}
	
	function callRoute($request=null) {
		if(isset($request)) {
			//$request = $this->getRequest();
			$this->request = $request;
		}
		//D::log($this->loadController(), 'controller funcj');
		echo f_call($this->loadController());
	}
		
//	var $contorllerFile;
	
	function loadController($controller=null) {
		if(isset($controller)) {
			$this->contorllerFile = $controller;
		}
		
		$class = SweetFramework::className($this->contorllerFile);
		D::log($class, 'Controller Loaded');
		
		if(!SweetFramework::loadFileType('controller', $this->contorllerFile)) {
			D::error('No Controller Found');
		}
		if(!empty($class::$urlPattern)) {
			$page = $this->loadUrl($class::$urlPattern, $this->count);
		} else {
			$page = $this->loadUrl(array(), $this->count);
		}
		
		D::log($this->count, 'COUNT');
		D::log($page, 'page');
		
		
		if(is_array(f_last($page))) {
			if(is_array( f_first(f_last($page)) )) {
				$this->request = f_first($page);
				D::log($this->request, 'Request Reduced');
				return $this->loadController(f_first(f_first(f_last($page))), $this->count+=1);
			}
			$page[$this->count] = f_first(f_last($page));
		}
		
		
		//
		//;
		$fpage = f_first($page);
		$this->controller = new $class();
		if(empty($fpage)) {
			return f_callable(array($this->controller, D::log('index', 'Controller Function')));
		} else {
			if(method_exists($class, $fpage)) {
				return f_callable(array($this->controller, D::log($fpage, 'Controller Function')));
			}
		}
		
/*
		if(D::log($fpage = f_first($page), 'Controller Function') && method_exists($class, $fpage) {
			
		}
*/
		//
/*
		if(empty($page[$this->count])) {
			return f_callable(array($this->controller, 'index'));
		} else {
			if(method_exists($class, D::log($page[$this->count], 'Controller Function')) ) {
				return f_callable(array(
					$this->controller,
					$page[$this->count]
				));
			}
		}
*/
		//D::show($class, 'controller');
		if(method_exists($class, '__DudeWheresMyCar')) {
			return f_callable(array(
				$this->controller,
				'__DudeWheresMyCar'
			));
		}
		//@todo find a way to check for __DudeWheresMyCar functions higher up the controller tree.
		
		return function() {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>404 error</h1>'; //todo check for some sort of custom 404…
			return false;
		};
	}
	
	
	
	function getRequest() {
		return $this->request;
	}
	
	function loadUrl($regexs=array()) {
		$this->uriArray = null;
		if(!empty($regexs)) {
			$this->uriArray = $this->regexArray($regexs);
			$pop = true;
		}
		if(empty($this->uriArray)) {
			$this->uriArray = explode('/', $this->request);
		}
		return $this->uriArray;
	}
	
	function regexArray($regexs) {
		$matches = array();
		foreach($regexs as $regex => $func) {
			preg_match_all($regex, $this->request, $matches);
			if(f_first($matches)) {
				D::log($regex, 'regex');
				return f_push(
					array($func),
					array_map('f_first', f_rest($matches))
				);
			}
		}
		return false;
	}
		
	function getPart($index) {
		return isset($this->uriArray[$index]) ? $this->uriArray[$index] : null;
	}
	
	function get($index) {
		return rawurldecode($this->rawGet($index));
	}
	
	function rawGet($index) {
		return isset($this->uriArray[$index]) ? $this->uriArray[$index] : null;
	}
	
	function getArray() {
		return $this->uriArray;
	}
	
	function redirect($uri = '', $http_response_code = 302) {
		if(substr($uri, 0, 7) != 'http://') {
			//@todo fix this so it works with https
/*
			$this->callRoute($uri);
			exit;
*/
			if($uri == '/') {
				$uri = SITE_URL;	
			} else {
				$uri = SITE_URL . $uri;
			}
		}
		//@todo make this be set off with the debug switch. and if debugging is on it should show a link to the page it would have forwarded to.
 		header('Location: ' . $uri, TRUE, $http_response_code);

		/* @todo you should call an app end event here.*/
		SweetFramework::end();
	}	
}
?>
