<?php

/**
 * Project: imooc-restful
 * User: xialei
 * Date: 2016/9/18 0018
 * Time: 10:11
 */
class MyHttpException extends Exception
{
	private $statusCode;

	/**
	 * HttpException constructor.
	 * @param int $statusCode
	 * @param string $message
	 * @param int $code
	 * @param $exception
	 */
	public function __construct($statusCode, $message = '', $code = 0, $exception = null)
	{
		parent::__construct($message, $code, $exception);
		$this->statusCode = $statusCode;
	}

	/**
	 * @return mixed
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}


}