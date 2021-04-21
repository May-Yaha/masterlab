<?php


namespace main\app\api;

use main\app\event\CommonPlacedEvent;
use main\app\event\Events;
use main\app\model\project\ProjectLabelModel;
use main\app\model\project\ProjectModel;
use main\app\model\project\ProjectVersionModel;
use main\app\model\SettingModel;

/**
 * Class Versions
 * @package main\app\api
 */
class Versions extends BaseAuth
{
    public $isTriggerEvent = false;

    /**
     * @return array
     * @throws \Exception
     */
    public function v1()
    {
        if (in_array($this->requestMethod, self::$method_type)) {
            $handleFnc = $this->requestMethod . 'Handler';
            return $this->$handleFnc();
        }
        $this->isTriggerEvent = (bool)SettingModel::getInstance()->getSettingValue('api_trigger_event');
        return self::returnHandler('api方法错误');
    }

    /**
     * Restful GET , 获取项目版本列表 | 单个版本信息
     * 获取版本列表: {{API_URL}}/api/versions/v1/?project_id=1&access_token==xyz
     * 获取单个版本: {{API_URL}}/api/versions/v1/36?access_token==xyz
     * @return array
     * @throws \Exception
     */
    private function getHandler()
    {
        $uid = $this->masterUid;
        $projectId = 0;
        $versionId = 0;

        if (isset($_GET['project_id'])) {
            $projectId = intval($_GET['project_id']);
        }
        if (isset($_GET['_target'][3])) {
            $versionId = intval($_GET['_target'][3]);
        }
        $final = [];
        $list = [];
        $row = [];

        $projectModel = new ProjectModel();
        $projectList = $projectModel->getAll2();

        $model = new ProjectVersionModel();

        if ($versionId > 0) {
            $row = $model->getById($versionId);
        } else {
            // 全部标签
            if ($projectId > 0) {
                $list = $model->getByProject($projectId, true);
            } else {
                $list = $model->getAll();
            }
        }

        if (!empty($list)) {
            foreach ($list as &$version) {
                $version['project_name'] = $projectList[$version['project_id']]['name'];
            }
            $final = $list;
        }

        if (!empty($row)) {
            $row['project_name'] = $projectList[$row['project_id']]['name'];
            $final = $row;
        }

        return self::returnHandler('OK', $final);
    }


    /**
     * Restful POST 添加项目版本
     * {{API_URL}}/api/versions/v1/?project_id=1&access_token==xyz
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function postHandler()
    {
        $uid = $this->masterUid;
        $projectId = 0;

        if (isset($_GET['project_id'])) {
            $projectId = intval($_GET['project_id']);
        }

        if (!isset($_POST['version_name'])) {
            return self::returnHandler('需要版本名.', [], Constants::HTTP_BAD_REQUEST);
        }
        $versionName = trim($_POST['version_name']);

        if (!isset($_POST['description'])) {
            $_POST['description'] = '';
        }

        $info = [];
        $info['project_id'] = $projectId;
        $info['name'] = $versionName;
        $info['description'] = $_POST['description'];
        $info['sequence'] = 0;

        $projectVersionModel = new ProjectVersionModel();
        if ($projectVersionModel->checkNameExist($projectId, $versionName)) {
            return self::returnHandler('name is exist.', [], Constants::HTTP_BAD_REQUEST);
        }
        $ret = $projectVersionModel->insert($info);

        if ($ret[0]) {
            if($this->isTriggerEvent){
                $info['id'] = $ret[1];
                $event = new CommonPlacedEvent($this, $info);
                $this->dispatcher->dispatch($event,  Events::onVersionCreate);
            }
            return self::returnHandler('操作成功', ['id' => $ret[1]]);
        } else {
            return self::returnHandler('添加版本失败.', [], Constants::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Restful DELETE ,删除某个项目模块
     * {{API_URL}}/api/versions/v1/36?access_token==xyz
     * @return array
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     * @throws \Exception
     */
    private function deleteHandler()
    {
        $uid = $this->masterUid;
        $versionId = 0;
        $projectId = 0;

        if (isset($_GET['_target'][3])) {
            $versionId = intval($_GET['_target'][3]);
        }
        if ($versionId == 0) {
            return self::returnHandler('需要有版本ID', [], Constants::HTTP_BAD_REQUEST);
        }

        if (isset($_GET['project_id'])) {
            $projectId = intval($_GET['project_id']);
        }
        if ($projectId == 0) {
            return self::returnHandler('需要有项目ID', [], Constants::HTTP_BAD_REQUEST);
        }

        $projectVersionModel = new ProjectVersionModel();
        $version = $projectVersionModel->getById($versionId);
        $projectVersionModel->deleteByVersinoId($projectId, $versionId);
        if($this->isTriggerEvent){
            $event = new CommonPlacedEvent($this, $version);
            $this->dispatcher->dispatch($event,  Events::onVersionDelete);
        }
        return self::returnHandler('操作成功');
    }

    /**
     * Restful PATCH ,更新项目版本
     * {{API_URL}}/api/versions/v1/36?access_token==xyz
     * @return array
     * @throws \Exception
     */
    private function patchHandler()
    {
        $uid = $this->masterUid;
        $versionId = 0;
        $projectId = 0;

        if (isset($_GET['_target'][3])) {
            $versionId = intval($_GET['_target'][3]);
        }
        if ($versionId == 0) {
            return self::returnHandler('需要有版本ID', [], Constants::HTTP_BAD_REQUEST);
        }

        if (isset($_GET['project_id'])) {
            $projectId = intval($_GET['project_id']);
        }
        if ($projectId == 0) {
            return self::returnHandler('需要有项目ID', [], Constants::HTTP_BAD_REQUEST);
        }

        $patch = self::_PATCH();

        $row = [];
        if (isset($patch['version_name']) && !empty($patch['version_name'])) {
            $row['name'] = $patch['version_name'];
        } else {
            return self::returnHandler('需要有版本名', [], Constants::HTTP_BAD_REQUEST);
        }

        if (isset($patch['description']) && !empty($patch['description'])) {
            $row['description'] = $patch['description'];
        }

        if (isset($patch['released']) && !empty($patch['released']) && in_array($patch['released'], ['0','1'])) {
            $row['released'] = $patch['released'];
        }

        if (isset($patch['start_date']) && !empty($patch['start_date']) && is_datetime_format($patch['start_date'])) {
            $row['start_date'] = strtotime($patch['start_date']);
        }

        if (isset($patch['release_date'])
            && !empty($patch['release_date'])
            && is_datetime_format($patch['release_date'])
        ) {
            $row['release_date'] = strtotime($patch['release_date']);
        }

        $projectModuleModel = new ProjectVersionModel();
        $version = $projectModuleModel->getById($versionId);
        $ret = $projectModuleModel->updateById($versionId, $row);
        if($this->isTriggerEvent){
            $curVersion = $projectModuleModel->getById($versionId);
            $event = new CommonPlacedEvent($this, ['pre_data' => $version, 'cur_data' => $curVersion]);
            $this->dispatcher->dispatch($event,  Events::onVersionUpdate);
        }
        return self::returnHandler('修改成功', array_merge($row, ['id' => $versionId]));
    }
}
