<?php
/**
 * Project: imooc-1
 * User: xialeistudio
 * Date: 2016/9/16 0016
 * Time: 22:10
 */
require_once __DIR__ . '/MyHttpException.php';

class User
{
	/**
	 * @var PDO
	 */
	private $db = null;
	/**
	 * @var string MD5加密混淆
	 */
	private $salt = 'imooc';

	/**
	 * User constructor.
	 * @param PDO $db
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * 注册
	 * @param string $username
	 * @param string $password
	 * @return array
	 * @throws Exception
	 */
	public function register($username, $password)
	{
		$username = trim($username);
		if (empty($username))
		{
			throw new MyHttpException(422, '用户名不能为空');
		}
		$password = trim($password);
		if (empty($password))
		{
			throw new MyHttpException(422, '密码不能为空');
		}
		//检测是否存在该用户
		if ($this->isUsernameExists($username))
		{
			throw new MyHttpException(422, '用户名已存在');
		}
		$password = md5($password . $this->salt);
		$createdAt = time();
		if ($this->db === null)
		{
			throw new MyHttpException(500, '数据库连接失败');
		}
		$sql = 'INSERT INTO `user`(`username`,`password`,`createdAt`) VALUES(:username,:password,:createdAt)';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->bindParam(':password', $password);
		$stmt->bindParam(':createdAt', $createdAt);
		if (!$stmt->execute())
		{
			throw new MyHttpException(500, '注册失败');
		}
		$userId = $this->db->lastInsertId();
		return [
			'userId' => $userId,
			'username' => $username,
			'createdAt' => $createdAt
		];
	}

	/**
	 * 登录
	 * @param string $username
	 * @param string $password
	 * @return array
	 * @throws Exception
	 */
	public function login($username, $password)
	{
		$sql = 'SELECT * FROM `user` WHERE `username`=:username';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($user))
		{
			throw new MyHttpException(422, '用户名或密码错误');
		}
		if ($user['password'] != md5($password . $this->salt))
		{
			throw new MyHttpException(422, '用户名或密码错误');
		}
		//TOOD:使用授权表
		unset($user['password']);
		return $user;
	}

	/**
	 * 检测用户名是否存在
	 * @param $username
	 * @return bool
	 * @throws Exception
	 */
	private function isUsernameExists($username)
	{
		if ($this->db === null)
		{
			throw new MyHttpException(500, '数据库连接失败');
		}
		$sql = 'SELECT userId FROM `user` WHERE username = :username';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		return !empty($data);
	}

	/**
	 * 查看用户
	 * @param $userId
	 * @return mixed
	 * @throws MyHttpException
	 */
	public function view($userId)
	{
		$sql = 'SELECT * FROM `user` WHERE userId=:id';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $userId, PDO::PARAM_INT);
		$stmt->execute();
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($data))
		{
			throw new MyHttpException(404, '用户不存在');
		}
		return $data;
	}

	/**
	 * 编辑
	 * @param $userId
	 * @param $password
	 * @return mixed
	 * @throws MyHttpException
	 */
	public function update($userId, $password)
	{
		$user = $this->view($userId);
		if (empty($password))
		{
			throw new MyHttpException(422, '密码不能为空');
		}
		$sql = 'UPDATE `user` SET `password` = :password WHERE userId=:id';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $userId);
		$password = md5($password . $this->salt);
		$stmt->bindParam(':password', $password);
		if (!$stmt->execute())
		{
			throw new MyHttpException(500, '编辑失败');
		}
		return $user;
	}
}