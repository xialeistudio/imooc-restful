<?php
/**
 * Project: imooc-1
 * User: xialeistudio
 * Date: 2016/9/16 0016
 * Time: 22:11
 */
require __DIR__ . '/../handler/User.php';
require __DIR__ . '/../handler/Article.php';

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
     * Handler constructor.
     */
    public function __construct()
    {
        $pdo = require __DIR__ . '/../db.php';
        $this->user = new User($pdo);
        $this->article = new Article($pdo);
    }

    /**
     * 注册
     * @param string $username
     * @param string $password
     * @return array|string
     */
    public function register($username, $password)
    {

        try {
            $this->user->register($username, $password);
            return json_encode(['code' => 0, 'message' => '注册成功']);
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 发表文章
     * @param string $title
     * @param string $content
     * @return string
     */
    public function createArticle($title, $content)
    {
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (empty($username) || empty($password)) {
                throw new Exception('缺少登录参数');
            }

            $user = $this->user->login($username, $password);
            return json_encode($this->article->create($title, $content, $user['userId']));
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 编辑文章
     * @param string $articleId
     * @param $title
     * @param $content
     * @return string
     */
    public function updateArticle($articleId, $title, $content)
    {
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (empty($username) || empty($password)) {
                throw new Exception('缺少登录参数');
            }

            $user = $this->user->login($username, $password);
            return json_encode($this->article->update($articleId, $title, $content, $user['userId']));
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 文章列表
     * @param int $page
     * @param int $limit
     * @return string
     */
    public function listArticle($page = 1, $limit = 10)
    {
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (empty($username) || empty($password)) {
                throw new Exception('缺少登录参数');
            }

            $user = $this->user->login($username, $password);
            return json_encode($this->article->all($user['userId'], $page, $limit));
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function deleteArticle($articleId)
    {
        try {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (empty($username) || empty($password)) {
                throw new Exception('缺少登录参数');
            }

            $user = $this->user->login($username, $password);
            $this->article->delete($articleId, $user['userId']);
            return json_encode(['code' => 0, 'message' => '删除成功']);
        } catch (Exception $e) {
            return json_encode(['code' => 1, 'message' => $e->getMessage()]);
        }
    }
}

try {
    $server = new SoapServer(null, [
        'uri' => 'post'
    ]);
    $server->setClass(Bridge::class);
    $server->handle();
} catch (SoapFault $e) {
    echo $e->getMessage();
}