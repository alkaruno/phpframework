<?php

namespace Xplosio\PhpFramework;

/**
 * @deprecated
 */
class AdminListController extends Controller
{
    private $rowsOnPage = 25;
    private $tableName;
    private $listUrl;
    private $listView;
    private $editView;
    private $images = array();

    /**
     * @var Validator
     */
    private $validator;

    public function handle()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $action = isset($_GET['action']) ? $_GET['action'] : null;

        if ($this->request->getMethod() == 'POST') {

            if ($id != null && isset($_POST['_delete'])) {
                Entity::delete($this->tableName, $id);
                return 'redirect:' . $this->listUrl;
            }

            if ($id != null) {
                $_POST['id'] = $id;
                $this->request->set('id', $id);
            }

            if ($this->validator->validate()) {
                $data = $this->validator->getData();
                $this->postProcess($data);
                $id = Entity::save($this->tableName, $data);
                $this->processImages($id);
                return 'redirect:' . $this->listUrl;
            } else {
                return array($this->editView, array(
                    'errors' => $this->validator->getErrors()
                ));
            }

        } else {

            if ($id != null) {
                $data = Entity::getRow($this->tableName, $id);
                $this->preProcess($data);
                $_POST = $data;
                return array($this->editView, array('id' => $id));
            } else if ($action == 'create') {
                return $this->editView;
            } else {
                $page = isset($_GET['page']) ? $_GET['page'] : 1;
                return array($this->listView, array(
                    'pagination' => new SqlPage('SELECT * FROM `' . $this->tableName . '`', array(), $page, $this->rowsOnPage)
                ));
            }

        }
    }

    public function addImage($field, $path, $params)
    {
        $this->images[$field] = array($path, $params);
    }

    protected function preProcess(&$data)
    {
        ;
    }

    protected function postProcess(&$data)
    {
        ;
    }

    /* private */

    private function processImages($id)
    {
        foreach ($this->images as $field => $data) {

            if (!isset($_FILES[$field]) || $_FILES[$field]['size'] == 0) {
                continue;
            }

            list($path, $arr) = $data;

            foreach ($arr as $params) {
                Image::create($_FILES[$field]['tmp_name'])
                    ->resize(isset($params['width']) ? $params['width'] : null, isset($params['height']) ? $params['height'] : null)
                    ->quality(isset($params['quality']) ? $params['quality'] : 90)
                    ->save($path . '/' . $id . (isset($params['suffix']) ? $params['suffix'] : '') . '.jpg');
            }
        }
    }

    /* setters */

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function setListUrl($listUrl)
    {
        $this->listUrl = $listUrl;
    }

    public function setListView($listView)
    {
        $this->listView = $listView;
    }

    public function setEditView($editView)
    {
        $this->editView = $editView;
    }

    public function setRowsOnPage($rowsOnPage)
    {
        $this->rowsOnPage = $rowsOnPage;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;
    }
}
