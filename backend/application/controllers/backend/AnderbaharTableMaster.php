<?php
class AnderbaharTableMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AnderBaherTableMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Ander Bahar Table Master Management',
            'AllAnderBaherTableMaster' => $this->AnderBaherTableMaster_model->AllTableMasterList()
        ];
        $data['SideBarbutton'] = ['backend/AnderbaharTableMaster/add', 'Add Ander Bahar Table Master'];
        template('anderbaher_table_master/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Ander Bahar Table Master'
        ];

        template('anderbaher_table_master/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Ander Bahar Table Master',
            'AnderbaharTableMaster' => $this->AnderBaherTableMaster_model->ViewTableMaster($id)
        ];

        template('anderbaher_table_master/edit', $data);
    }

    public function delete($id)
    {
        if ($this->AnderBaherTableMaster_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Ander Bahar Table Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AnderbaharTableMaster');
    }

    public function insert()
    {
        $data = [
            'min_coin' => $this->input->post('min_coin'),
            'max_coin' => $this->input->post('max_coin'),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $AnderbaharTableMaster = $this->AnderBaherTableMaster_model->AddTableMaster($data);
        if ($AnderbaharTableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Ander Bahar Table Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AnderbaharTableMaster');
    }

    public function update()
    {
        $data = [
            'min_coin' => $this->input->post('min_coin'),
            'max_coin' => $this->input->post('max_coin'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $AnderbaharTableMaster = $this->AnderBaherTableMaster_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($AnderbaharTableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Ander Bahar Table Master Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/AnderbaharTableMaster');
    }

}