<?php
class PokerMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['PokerMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Poker Table Master Management',
            'AllTableMaster' => $this->PokerMaster_model->AllTableMasterList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/PokerMaster/add', 'Add Poker Table Master'];
        }
        template('poker_master/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Poker Table Master'
        ];

        template('poker_master/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Poker Table Master',
            'PokerMaster' => $this->PokerMaster_model->ViewTableMaster($id)
        ];

        template('poker_master/edit', $data);
    }

    public function delete($id)
    {
        if ($this->PokerMaster_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Poker Table Master Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/PokerMaster');
    }

    public function insert()
    {
        $data = [
            'boot_value' => $this->input->post('boot_value'),
            'maximum_blind' => 4,
            'city' => $this->input->post('city'),
            'blind_1' => $this->input->post('blind_1'),
            'blind_2' => $this->input->post('blind_2'),
            'added_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($_FILES['image']['name'])) {
            $data['image'] = upload_image($_FILES['image'], './data/post/');
        }
        if (!empty($_FILES['image_bg']['name'])) {
            $data['image_bg'] = upload_image($_FILES['image_bg'], './data/post/');
        }
        $PokerMaster = $this->PokerMaster_model->AddTableMaster($data);
        if ($PokerMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Poker Table Master Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/PokerMaster');
    }

    public function update()
    {
        $data = [
            'boot_value' => $this->input->post('boot_value'),
            'maximum_blind' => 4,
            'city' => $this->input->post('city'),
            'blind_1' => $this->input->post('blind_1'),
            'blind_2' => $this->input->post('blind_2'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = upload_image($_FILES['image'], './data/post/');
        }
        if (!empty($_FILES['image_bg']['name'])) {
            $data['image_bg'] = upload_image($_FILES['image_bg'], './data/post/');
        }
        $PokerMaster = $this->PokerMaster_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($PokerMaster) {
            $this->session->set_flashdata('msg', array('message' => 'Poker Table Master Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/PokerMaster');
    }
}
