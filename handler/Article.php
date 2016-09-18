<?php
/**
 * Project: imooc-1
 * User: xialeistudio
 * Date: 2016/9/16 0016
 * Time: 22:10
 */
require_once __DIR__ . '/MyHttpException.php';

class Article
{
	/**
	 * @var PDO
	 */
	private $db = null;

	/**
	 * Article constructor.
	 * @param PDO $db
	 */
	public function __construct(PDO $db)
	{
		$this->db = $db;
	}

	/**
	 * 发表文章
	 * @param string $title
	 * @param string $content
	 * @param integer $userId
	 * @return array
	 * @throws Exception
	 */
	public function create($title, $content, $userId)
	{
		if (empty($title))
		{
			throw new MyHttpException(422, '标题不能为空');
		}
		if (empty($content))
		{
			throw new MyHttpException(422, '内容不能为空');
		}
		if (empty($userId))
		{
			throw new MyHttpException(422, '用户ID不能为空');
		}
		$sql = 'INSERT INTO `article` (`title`,`createdAt`,`content`,`userId`) VALUES (:title,:createdAt,:content,:userId)';
		$createdAt = time();
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':content', $content);
		$stmt->bindParam(':userId', $userId);
		$stmt->bindParam(':createdAt', $createdAt);
		if (!$stmt->execute())
		{
			throw new MyHttpException(500, '发表失败');
		}
		return [
			'articleId' => $this->db->lastInsertId(),
			'title' => $title,
			'content' => $content,
			'createdAt' => $createdAt,
			'userId' => $userId
		];
	}

	/**
	 * 查看文章
	 * @param integer $articleId
	 * @return mixed
	 * @throws Exception
	 */
	public function view($articleId)
	{
		$sql = 'SELECT * FROM `article` WHERE `articleId`=:id';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $articleId, PDO::PARAM_INT);
		$stmt->execute();
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($data))
		{
			throw new MyHttpException(404, '文章不存在');
		}
		return $data;
	}

	/**
	 * 编辑文章
	 * @param integer $articleId
	 * @param string $title
	 * @param string $content
	 * @param integer $userId
	 * @return array
	 * @throws Exception
	 */
	public function update($articleId, $title, $content, $userId)
	{
		$article = $this->view($articleId);
		if ($article['userId'] != $userId)
		{
			throw new MyHttpException(403, '你没有权限修改该文章');
		}
		$sql = 'UPDATE `article` SET `title`=:title,`content`=:content WHERE articleId=:id';
		$stmt = $this->db->prepare($sql);
		$t = empty($title) ? $article['title'] : $title;
		$stmt->bindParam(':title', $t);
		$c =  empty($content) ? $article['content'] : $content;
		$stmt->bindParam(':content',$c);
		$stmt->bindParam(':id', $articleId);
		if (!$stmt->execute())
		{
			throw new MyHttpException(500, '编辑失败');
		}
		return [
			'articleId' => $articleId,
			'title' => $t,
			'content' => $c,
			'createdAt' => $article['createdAt'],
			'userId' => $userId
		];
	}

	/**
	 * 文章列表
	 * @param string $userId
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function all($userId, $page = 1, $limit = 10)
	{
		$sql = 'SELECT * FROM `article` WHERE `userId`=:userId ORDER BY `articleId` DESC LIMIT :offset,:limit';
		$offset = ($page - 1) * $limit;
		if ($offset < 0)
		{
			$offset = 0;
		}
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
		$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * 删除文章
	 * @param string $articleId
	 * @param integer $userId
	 * @throws Exception
	 */
	public function delete($articleId, $userId)
	{
		$article = $this->view($articleId);
		if ($article['userId'] != $userId)
		{
			throw new MyHttpException(404, '文章不存在');
		}
		$sql = 'DELETE FROM `article` WHERE `articleId`=:articleId AND `userId`=:userId';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':articleId', $articleId);
		$stmt->bindParam(':userId', $userId);
		if (!$stmt->execute())
		{
			throw new MyHttpException(500, '删除失败');
		}
	}
}