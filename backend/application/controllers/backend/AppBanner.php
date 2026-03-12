<?php
class AppBanner extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('AppBanner_model');
    }

    public function index()
    {
        $data = [
            'title' => 'App Banner',
            'Allbanner' => $this->AppBanner_model->view()
        ];
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
        } else {
            $data['SideBarbutton'] = ['backend/AppBanner/add', 'Add App Banner'];
        }

        template('app_banner/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add App Banner'
        ];
        // echo '<pre>';print_r($data);die;
        template('app_banner/add', $data);
    }

    public function insert()
    {
        $inserted_id = 0;

        if (!empty($_FILES['banner']['name'])) {
            $data = [];
            $data['banner'] = upload_image($_FILES['banner'], BANNER_URL);
            $inserted_id = $this->AppBanner_model->insert($data);
        }

        if ($inserted_id) {
            $this->session->set_flashdata('msg', array('message' => 'App Banner Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AppBanner');
    }

    public function delete($id)
    {
        if ($this->AppBanner_model->update(array('isDeleted' => true), $id)) {
            $this->session->set_flashdata('msg', array('message' => 'App Banner Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AppBanner');
    }
}
