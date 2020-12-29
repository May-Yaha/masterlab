<?php

namespace main\app\model\project;

use main\app\model\BaseDictionaryModel;

/**
 *  项目标识模型
 */
class ProjectFlagModel extends BaseDictionaryModel
{
    public $prefix = 'project_';

    public $table = 'flag';

    const   DATA_KEY = 'project_flag/';

    public $fields = '*';

    /**
     * 用于实现单例模式
     * @var self
     */
    protected static $instance;

    /**
     * 创建一个自身的单例对象
     * @param bool $persistent
     * @return self
     * @throws \Exception
     * @throws \PDOException
     */
    public static function getInstance($persistent = false)
    {
        $index = intval($persistent);
        if (!isset(self::$instance[$index]) || !is_object(self::$instance[$index])) {
            self::$instance[$index] = new self($persistent);
        }
        return self::$instance[$index];
    }

    /**
     * @param $projectId
     * @param $flag
     * @param $value
     * @return array
     * @throws \Exception
     */
    public function add($projectId, $flag, $value)
    {
        $info = [];
        $info['project_id'] = $projectId;
        $info['flag'] = $flag;
        $info['value'] = $value;
        $info['update_time'] = time();
        $ret = $this->replace($info);
        return $ret;
    }

    /**
     * @param $id
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getById($id)
    {
        return $this->getRowById($id);
    }

    /**
     * @param $projectId
     * @param $flag
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getByFlag($projectId, $flag)
    {
        $conditions = [];
        $conditions['project_id'] = $projectId;
        $conditions['flag'] = $flag;
        $row = $this->getRow("*", $conditions);
        return $row;
    }

    /**
     * @param $projectId
     * @param $flag
     * @return mixed|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getValueByFlag($projectId, $flag)
    {
        $value = null;
        $row = $this->getByFlag($projectId, $flag);
        if (isset($row['value'])) {
            $value = $row['value'];
        }
        return $value;
    }
}
