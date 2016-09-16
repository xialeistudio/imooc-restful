<?php

/**
 * Project: imooc-1
 * User: xialeistudio
 * Date: 2016/9/16 0016
 * Time: 22:10
 */
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
        if (empty($username)) {
            throw new Exception('用户名不能为空');
        }
        $password = trim($password);
        if (empty($password)) {
            throw new Exception('密码不能为空');
        }
        //检测是否存在该用户
        if ($this->isUsernameExists($username)) {
            throw new Exception('用户名已存在');
        }
        $password = md5($password . $this->salt);
        $createdAt = time();
        if ($this->db === null) {
            throw new Exception('数据库连接失败');
        }
        $sql = 'INSERT INTO `user`(`username`,`password`,`createdAt`) VALUES(:username,:password,:createdAt)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':createdAt', $createdAt);
        if (!$stmt->execute()) {
            throw new Exception('注册失败');
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
     * @return bool
     * @throws Exception
     */
    public function login($username, $password)
    {
        $sql = 'SELECT * FROM `user` WHERE `username`=:username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($user)) {
            throw new Exception('用户名不存在');
        }
        if ($user['password'] != md5($password . $this->salt)) {
            throw new Exception('密码错误');
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
        if ($this->db === null) {
            throw new Exception('数据库连接失败');
        }
        $sql = 'SELECT userId FROM `user` WHERE username = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($data);
    }
}