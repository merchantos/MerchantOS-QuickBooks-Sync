<?php
/**
 * helpers_Airbrake
 *
 * @category error
 * @package  helpers_Airbrake
 * @author   Rich Cavanaugh <no@email>
 * @author   Till Klampaeckel <till@php.net>
 * @license  
 * @version  GIT: $Id$
 * @link     http://github.com/till/php-hoptoad-notifier
 */


/**
 * helpers_Airbrake
 *
 * @category error
 * @package  helpers
 * @author   Rich Cavanaugh <no@email>
 * @author   Till Klampaeckel <till@php.net>
 * @author   Justin Laing <justin@merchantos.com>
 * @license  
 * @version  Release: @package_version@
 * @link     http://github.com/rich/php-hoptoad-notifier
 */
class helpers_Airbrake
{
	const NOTIFIER_NAME = 'php-airbrake-notifier';
	const NOTIFIER_VERSION = '0.2.0';
	const NOTIFIER_URL = 'http://github.com/rich/php-hoptoad-notifier';
	const NOTIFIER_API_VERSION = '2.0';
	const NOTIFIER_API_KEY = 'a75436fd6c37166c1a20168c3155dcde';

	protected $error_class;
	protected $message;
	protected $file;
	protected $line;
	protected $trace;

	/**
	 * Report E_STRICT
	 *
	 * @var bool $reportESTRICT
	 * @todo Implement set!
	 */
	protected $reportESTRICT;

	/**
	 * Timeout for cUrl.
	 * @var int $timeout
	 */
	protected $timeout;

	/**
	 * @var string
	 **/
	public $environment;
	
	/**
	 * Holds the singleton client
	 * @var helpers_Airbrake $_client
	 */
	static protected $_client;

	/**
	 * Return singleton Airbrake client for the specified environment
	 *
	 * @return helpers_Airbrake The client
	 * @author Justin Laing
	 */
	public static function singleton()
	{
		if (isset(self::$_client))
		{
			return self::$_client;
		}
		
		self::$_client = new helpers_Airbrake(MERCHANTOS_ENVIRONMENT);
		return self::$_client;
	}

	/**
	 * Hook's this notifier to PHP error and exception handlers
	 * @return void
	 * @author Rich Cavanaugh
	 **/
	public function installNotifierHandlers()
	{
		set_error_handler(array($this, "errorHandler"));
		set_exception_handler(array($this, "exceptionHandler"));		
	}

	/**
	 * Initialize the Hoptad client
	 *
	 * @param string $environment
	 * @param string $reportESTRICT
	 * @param int $timeout
	 * @return void
	 * @author Rich Cavanaugh
	 */	
	function __construct($environment='production', $reportESTRICT=false, $timeout=4)
	{
		$this->environment = $environment;
		$this->reportESTRICT = $reportESTRICT;
		$this->timeout = $timeout;
		$this->setup();
	}

	/**
	 * A method meant specifically for subclasses to override so they don't need
	 * to handle the constructor
	 * @return void
	 * @author Rich Cavanaugh
	 **/
	public function setup()
	{
		// we don't do anything here in the base class
	}

	/**
	 * Handle a php error
	 *
	 * @param string $code 
	 * @param string $message 
	 * @param string $file 
	 * @param string $line 
	 * @return void
	 * @author Rich Cavanaugh
	 */
	public function errorHandler($code, $message, $file, $line)
	{
		if ($code == E_STRICT && $this->reportESTRICT === false) return;

		$this->notify($code, $message, $file, $line, debug_backtrace());
	}

	/**
	 * Handle a raised exception
	 *
	 * @param Exception $exception 
	 * @return void
	 * @author Rich Cavanaugh
	 */
	public function exceptionHandler($exception)
	{
		$this->notify(get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace());
	}
	
	/**
	* Handle fatal errors
	*
	* @param array $error Error array returned from error_get_last() call within a shutdown or buffer function
	* @return void
	* @author Justin Laing
	*/
	public function fatalErrorHandler($error) 
	{
		$this->notify($error['type'], $error['message'], $error['file'], $error['line'], debug_backtrace());
	} 

	/**
	 * Set the values to be used for the next notice sent to Airbrake
	 * @return void
	 * @author Rich Cavanaugh
	 **/
	public function setParamsForNotify($error_class, $message, $file, $line, $trace, $component=NULL)
	{
		$this->error_class = $error_class;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->trace = $trace;
		$this->component = $component;
	}

	/**
	 * Pass the error and environment data on to Airbrake
	 *
	 * @param mixed  $error_class
	 * @param string $message
	 * @param string $file
	 * @param string $line
	 * @param array  $trace
	 * @param string $environment
	 *
	 * @author Rich Cavanaugh
	 * @todo   Handle response (e.g. errors)
	 */
	function notify($error_class, $message, $file, $line, $trace, $component=NULL)
	{
		$this->setParamsForNotify($error_class, $message, $file, $line, $trace, $component);

		$url = "http://airbrakeapp.com/notifier_api/v2/notices";
		$headers = array(
			'Accept'				=> 'text/xml, application/xml',
			'Content-Type'	=> 'text/xml'
		);
		$body = $this->buildXmlNotice();

		try {
			$status = $this->curlRequest($url, $headers, $body);
			if ($status != 200) $this->handleErrorResponse($status);
		} catch (RuntimeException $e) {
			// TODO do something reasonable with the runtime exception.
			// we can't really throw our runtime exception since we're likely in
			// an exception handler. Punt on this for now and come back to it.
			openlog('php', LOG_CONS | LOG_NDELAY | LOG_PID, LOG_USER | LOG_PERROR);
			syslog(LOG_ERR, 'Failed to log error within Airbrake.');
			closelog();
		}
	}

	/**
	 * Build up the XML to post according to the documentation at:
	 * http://help.airbrake.io/kb/api-2/notifier-api-version-22
	 * @return string
	 * @author Rich Cavanaugh
	 **/
	function buildXmlNotice()
	{
		$doc = new SimpleXMLElement('<notice />');
		$doc->addAttribute('version', self::NOTIFIER_API_VERSION);
		$doc->addChild('api-key', self::NOTIFIER_API_KEY);

		$notifier = $doc->addChild('notifier');
		$notifier->addChild('name', self::NOTIFIER_NAME);
		$notifier->addChild('version', self::NOTIFIER_VERSION);
		$notifier->addChild('url', self::NOTIFIER_URL);

		$error = $doc->addChild('error');
		$error->addChild('class', $this->error_class);
		$error->addChild('message', htmlentities($this->message));
		$this->addXmlBacktrace($error);

		$request = $doc->addChild('request');
		$request->addChild('url', htmlentities($this->request_uri()));
		$request->addChild('component', $this->component());
		$request->addChild('action', $this->action());

		if (isset($_REQUEST)) $this->addXmlVars($request, 'params', $this->params());
		if (isset($_SESSION)) $this->addXmlVars($request, 'session', $this->session());
		if (isset($_SERVER)) $this->addXmlVars($request, 'cgi-data', $this->cgi_data());

		$env = $doc->addChild('server-environment');
		$env->addChild('project-root', $this->project_root());
		$env->addChild('environment-name', $this->environment());

		return $doc->asXML();
	}

	/**
	 * Add a Airbrake var block to the XML
	 * @return void
	 * @author Rich Cavanaugh
	 **/
	function addXmlVars($parent, $key, $source)
	{
		if (empty($source)) return;

		$node = $parent->addChild($key);
		foreach ($source as $key => $val) {
			if (is_array($val))
			{
				$val = print_r($val,true);
			}
			$val = htmlentities($val);
			$var_node = $node->addChild('var', $val);
			$var_node->addAttribute('key', $key);
		}
	}

	/**
	 * Add a Airbrake backtrace to the XML
	 * @return void
	 * @author Rich Cavanaugh
	 **/
	function addXmlBacktrace($parent)
	{
		$backtrace = $parent->addChild('backtrace');
		$line_node = $backtrace->addChild('line');
		$line_node->addAttribute('file', $this->file);
		$line_node->addAttribute('number', $this->line);

		foreach ($this->trace as $entry) {
			if (isset($entry['class']) && $entry['class'] == 'helpers_Airbrake') continue;

			$line_node = $backtrace->addChild('line');
			if (isset($entry['file']))
			{
				$line_node->addAttribute('file', $entry['file']);
			}
			if (isset($entry['line']))
			{
				$line_node->addAttribute('number', $entry['line']);
			}
			if (isset($entry['function']))
			{
				$line_node->addAttribute('method', $entry['function']);
			}
		}
	}

	/**
	 * params
	 * @return array
	 * @author Scott Woods
	 **/
	function params() {
		return $_REQUEST;
	}

	/**
	 * session
	 * @return array
	 * @author Scott Woods
	 **/
	function session() {
		return $_SESSION;
	}

	/**
	 * cgi_data
	 * @return array
	 * @author Scott Woods
	 **/
	function cgi_data() {
		if (isset($_ENV) && !empty($_ENV)) {
			return array_merge($_SERVER, $_ENV);
		}
		return $_SERVER;
	}

	/**
	 * component
	 * @return mixed
	 * @author Scott Woods
	 **/
	function component() {
		return $this->component;
	}

	/**
	 * action
	 * @return mixed
	 * @author Scott Woods
	 **/
	function action() {
		return '';
	}

	/**
	 * environment
	 * @return string
	 * @author Rich Cavanaugh
	 **/
	function environment() {
		return $this->environment;
	}
	
	/**
	 * project_root
	 * @return string
	 * @author Scott Woods
	 **/
	function project_root() {
		if (isset($_SERVER['DOCUMENT_ROOT'])) {
			return $_SERVER['DOCUMENT_ROOT'];
		} else {
			return dirname(__FILE__);
		}
	}


	/**
	 * get the request uri
	 * @return string
	 * @author Scott Woods
	 **/
	function request_uri() {
		if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
			$protocol = 'https';
		} else {
			$protocol = 'http';
		}
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$query_string = isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '';
		return "{$protocol}://{$host}{$path}{$query_string}";
	}

	/**
	 * @param mixed $code The HTTP status code from Airbrake.
	 *
	 * @return void
	 * @throws RuntimeException Error message from airbrake, translated to a RuntimeException.
	 */
	protected function handleErrorResponse($code)
	{
		switch ($code) {
		case '403':
			$msg = 'The requested project does not support SSL - resubmit in an http request.';
			break;
		case '422':
			$msg = 'The submitted notice was invalid - check the notice xml against the schema.';
			break;
		case '500':
			$msg = 'Unexpected errors - submit a bug report at http://help.airbrakeapp.com.';
			break;
		default:
			$msg = 'Unknown error code from Airbrake\'s API: ' . $code;
			break;
		}

		throw new RuntimeException($msg, $code);
	}
	
	/**
	 * Send the request to Airbrake using Curl
	 * @return integer
	 * @author Rich Cavanaugh
	 **/
	public function curlRequest($url, $headers, $body)
	{
		$header_strings = array();
		foreach ($headers as $key => $val) {
			$header_strings[] = "{$key}: {$val}";
		}

		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL,            $url);
		curl_setopt($curlHandle, CURLOPT_POST,           1);
		curl_setopt($curlHandle, CURLOPT_HEADER,         0);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT,        $this->timeout);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS,     $body);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER,     $header_strings);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($curlHandle);
		$status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);
		return $status;
	}
}
