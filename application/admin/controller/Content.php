<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Category as CategoryModel;
use Exception;
use fast\Tree;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 内容管理
 *
 * @icon fa fa-circle-o
 */
class Content extends Backend
{

    /**
     * Content模型对象
     * @var \app\admin\model\Content
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Content;

        $status_radios = $this->model->getStatusRadios();
        $status_list = $this->model->getStatusList();
        $this->view->assign('statusRadios', $status_radios);
        $this->assignconfig('statusList', $status_list);

        $category_model = new CategoryModel;
        $tree = Tree::instance();
        $tree->init(collection($category_model->order('weigh desc,id desc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['type' => 'all', 'name' => __('None')]];
        foreach ($this->categorylist as $k => $v) {
            $categorydata[$v['id']] = $v;
        }
        $typeList = CategoryModel::getTypeList();
        $this->view->assign("categoryList", $categorydata);
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 单页分类操作内容中转
     */
    public function singlepage()
    {
        $get_category_id = $this->request->get('cid', '');
        $category = Db::name('category')
            ->where('id', $get_category_id)
            ->find();
        if(empty($category)) {
            $this->error(__('category not exists'));
        }

        $content = $this->model
            ->where('category_id', $get_category_id)
            ->find();
        $params = ['cid' => $get_category_id];
        if(empty($content)) {
            $this->redirect('content/add', $params);
        } else {
            $params['ids'] = $content['id'];
            $this->redirect('content/edit', $params);
        }
    }

    public function add()
    {
        $get_category_id = $this->request->get('cid', '');
        $this->view->assign('getCid', $get_category_id);
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }

        // 标签处理
        $tags = explode(',', $params['tags']);
        $tags = array_unique(array_filter($tags));
        // 自动补全数据
        $params['admin_id'] = $this->auth->id;
        $params['admin'] = $this->auth->username;
        $category = Db::name('category')
            ->where('id', $params['category_id'])
            ->find();
        if(empty($category)) {
            $this->error(__('category not exists'));
        }
        if($category['type'] === 'page') {
            $is_existed = $this->model
                ->where('category_id', $params['category_id'])
                ->count();
            if($is_existed > 0) {
                $this->error('page has content');
            }
        }
        $params['category'] = @$category['name'];
        if(empty($params['outline'])) {
            $params['outline'] = mb_substr(strip_tags($params['content']), 140);
        }

        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);

            if($result) {
                if(!empty($tags)) {
                    $tags_data = [];
                    foreach ($tags as $_t) {
                        array_push($tags_data, [
                            'name' => $_t,
                            'content_id' => $result
                        ]);
                    }
                    Db::name('tags')
                        ->insertAll($tags_data);
                }
            }
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        $get_category_id = $this->request->get('cid', '');
        $this->view->assign('getCid', $get_category_id);
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        // 标签处理
        $tags = explode(',', $params['tags']);
        $tags = array_unique(array_filter($tags));
        // 自动补全数据
        $category = Db::name('category')
            ->where('id', $params['category_id'])
            ->find();
        if(empty($category)) {
            $this->error(__('category not exists'));
        }
        if($category['type'] === 'page') {
            $is_existed = $this->model
                ->where('category_id', $params['category_id'])
                ->where('id', '<>', $ids)
                ->count();
            if($is_existed > 0) {
                $this->error('page has content');
            }
        }
        $params['category'] = @$category['name'];
        if(empty($params['outline'])) {
            $params['outline'] = mb_substr(strip_tags($params['content']), 140);
        }

        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if($result) {
                Db::name('tags')
                    ->where('content_id', $ids)
                    ->delete();
                if(!empty($tags)) {
                    $tags_data = [];
                    foreach ($tags as $_t) {
                        array_push($tags_data, [
                            'name' => $_t,
                            'content_id' => $ids
                        ]);
                    }
                    Db::name('tags')
                        ->insertAll($tags_data);
                }
            }
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 审核
     */
    public function review($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $extra = json_decode($row['extra'], true);
            $review_list = $this->model->getStatusList();
            $this->view->assign('row', $row);
            $this->view->assign('extra', ($extra?: []));
            $this->view->assign('reviewList', $review_list);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
