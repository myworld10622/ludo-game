<?php
class LudoTableMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['LudoTableMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Ludo Table Master Management',
            'AllLudoTableMaster' => $this->LudoTableMaster_model->AllTableMasterList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/LudoTableMaster/add', 'Add Point Table Master'];
        }
        template('ludo_table_master/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Point Table Master'
        ];

        template('ludo_table_master/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Point Table Master',
            'LudoTableMaster' => $this->LudoTableMaster_model->ViewTableMaster($id)
        ];

        template('ludo_table_master/edit', $data);
    }

    public function delete($id)
    {
        if ($this->LudoTableMaster_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Point Table Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/LudoTableMaster');
    }

    public function insert()
    {
        $data = [
            'boot_value' => $this->input->post('boot_value'),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $LudoTableMaster = $this->LudoTableMaster_model->AddTableMaster($data);
        if ($LudoTableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Point Table Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/LudoTableMaster');
    }

    public function update()
    {
        $data = [
            'boot_value' => $this->input->post('boot_value'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $LudoTableMaster = $this->LudoTableMaster_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($LudoTableMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Point Table Master Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/LudoTableMaster');
    }
}
