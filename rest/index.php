<?php
/**
 * Project: imooc-restful
 * User: xialei
 * Date: 2016/9/18 0018
 * Time: 9:35
 */
require_once __DIR__ . '/../handler/User.php';
require_once __DIR__ . '/../handler/Article.php';
require_once __DIR__ . '/../handler/MyHttpException.php';

class Bridge
{
	/**
	 * @var User
	 */
	private $user;
	/**
	 * @var Article
	 */
	private $article;
	/**
	 * @var string 请求方法
	 */
	private $method;
	/**
	 * @var string 请求实体
	 */
	private $entity;

	/**
	 * @var integer ID
	 */
	private $id;

	/**
	 * @var array 允许请求的实体类
	 */
	private $allowEntities = ['users', 'articles'];

	private $allowMethods = ['GET', 'POST', 'PUT', 'OPTIONS', 'HEAD', 'DELETE'];

	/**
	 * Bridge constructor.
	 */
	public function __construct()
	{
		$pdo = require_once __DIR__ . '/../db.php';
		$this->user = new User($pdo);
		$this->article = new Article($pdo);
		$this->setupRequestMethod();
		$this->setupEntity();
	}

	/**
	 * 初始化实体
	 */
	private function setupEntity()
	{
		$pathinfo = $_SERVER['PATH_INFO'];
		if (empty($pathinfo))
		{
			static::sendHttpStatus(400);
		}
		$params = explode('/', $pathinfo);
		$this->entity = $params[1];
		if (!in_array($this->entity, $this->allowEntities))
		{
			//实体不存在
			static::sendHttpStatus(404);
		}
		if (!empty($params[2]))
		{
			$this->id = $params[2];
		}
	}

	/**
	 * 发送HTTP响应状态码
	 * @param $code
	 * @param string $error
	 * @param array|null $data
	 * @internal param null $message
	 */
	static function sendHttpStatus($code, $error = '', $data = null)
	{
		static $_status = array(
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',
			// Success 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',  // 1.1
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			// 306 is deprecated but reserved
			307 => 'Temporary Redirect',
			// Client Error 4xx
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			509 => 'Bandwidth Limit Exceeded'
		);
		if (isset($_status[$code]))
		{
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
			header('Content-Type:application/json;charset=utf-8');
			if ($code == 200) //2xx状态码
			{
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
			}
			else if ($code == 204)
			{
				//无响应体
			}
			else
			{
				if (empty($error))
				{
					$error = $_status[$code];
				}
				echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
			}
		}
		exit(0);
	}

	/**
	 * 初始化请求方法
	 */
	private function setupRequestMethod()
	{
		$this->method = $_SERVER['REQUEST_METHOD'];
		if (!in_array($this->method, $this->allowMethods))
		{
			//请求方法不被允许
			static::sendHttpStatus(405);
		}
	}

	/**
	 * 处理请求
	 */
	public function handle()
	{
		try
		{
			if ($this->entity == 'users')
			{
				$this->handleUser();
			}
			if ($this->entity == 'articles')
			{
				$this->handleArticle();
			}
		}
		catch (MyHttpException $e)
		{
			static::sendHttpStatus($e->getStatusCode(), $e->getMessage());
		}
	}

	/**
	 * 获取请求体
	 * @return mixed|null
	 */
	private function getBodyParams()
	{
		$raw = file_get_contents('php://input');
		if (empty($raw))
		{
			return [];
		}
		return json_decode($raw, true);
	}

	private function getBodyParam($name, $defaultValue = null)
	{
		$data = $this->getBodyParams();
		return isset($data[$name]) ? $data[$name] : $defaultValue;
	}

	/**
	 * 要求认证
	 * @return bool|array
	 * @throws MyHttpException
	 */
	private function requestAuth()
	{
		if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW']))
		{
			try
			{
				$user = $this->user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
				return $user;
			}
			catch (MyHttpException $e)
			{
				if ($e->getStatusCode() != 422)
				{
					throw $e;
				}
			}
		}
		header("WWW-Authenticate:Basic realm='Private'");
		header('HTTP/1.0 401 Unauthorized');
		print "You are unauthorized to enter this area.";
		exit(0);
	}

	/**
	 * 处理访问用户的请求
	 */
	private function handleUser()
	{
		if ($this->method == 'GET')
		{
			if (empty($this->id))
			{
				//客户端需要获取所有用户的信息，访问有效，但是无权限
				throw new MyHttpException(403);
			}
			static::sendHttpStatus(200, null, $this->user->view($this->id));
		}
		else if ($this->method == 'POST')
		{
			$data = $this->getBodyParams();
			if (empty($data['username']))
			{
				throw new MyHttpException(422, '用户名不能为空');
			}
			if (empty($data['password']))
			{
				throw new MyHttpException(422, '密码不能为空');
			}
			static::sendHttpStatus(200, null, $this->user->register($data['username'], $data['password']));
		}
		else if ($this->method == 'PUT')
		{
			if (empty($this->id))
			{
				throw new MyHttpException(400);
			}
			$params = $this->getBodyParams();
			if (empty($params) || empty($params['password']))
			{
				throw new MyHttpException(422, '密码不能为空');
			}
			//检测认证参数
			$user = $this->requestAuth();
			$result = $this->user->update($user['userId'], $params['password']);
			static::sendHttpStatus(200, null, $result);
		}
		else
		{
			throw new MyHttpException(405);
		}
	}

	private function getQueryParam($name, $defaultValue = null)
	{
		return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
	}

	/**
	 * 处理访问文章的请求
	 */
	private function handleArticle()
	{
		if ($this->method == 'GET')
		{
			$user = $this->requestAuth();
			if (empty($this->id))
			{
				$list = $this->article->all($user['userId'], $this->getQueryParam('page', 1), $this->getQueryParam('size', 10));
				self::sendHttpStatus(200, null, $list);
			}
			self::sendHttpStatus(200, null, $this->article->view($this->id));
		}
		else if ($this->method == 'POST')
		{
			$user = $this->requestAuth();
			$data = $this->getBodyParams();
			if (empty($data['title']))
			{
				throw new MyHttpException(422, '标题不能为空');
			}
			if (empty($data['content']))
			{
				throw new MyHttpException(422, '内容不能为空');
			}
			$article = $this->article->create($data['title'], $data['content'], $user['userId']);
			static::sendHttpStatus(200, null, $article);
		}
		else if ($this->method == 'PUT')
		{
			if (empty($this->id))
			{
				throw new MyHttpException(400);
			}
			$user = $this->requestAuth();
			$data = $this->getBodyParams();
			$title = isset($data['title']) ? $data['title'] : null;
			$content = isset($data['content']) ? $data['content'] : null;
			$article = $this->article->update($this->id, $title, $content, $user['userId']);
			static::sendHttpStatus(200, null, $article);
		}
		else if ($this->method == 'DELETE')
		{
			if (empty($this->id))
			{
				throw new MyHttpException(400);
			}
			$user = $this->requestAuth();
			$this->article->delete($this->id, $user['userId']);
			static::sendHttpStatus(204, null, null);
		}
	}
}


$bridge = new Bridge();
$bridge->handle();