<?php
class DepositPercentage extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['DepositPercentage_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Deposit Percentage',
            'All' => $this->DepositPercentage_model->all()
        ];
        // if ($_ENV['ENVIRONMENT'] != 'demo') {
        //     $data['SideBarbutton'] = ['backend/DepositPercentage/add', 'Add'];
        // }
        template('deposit_percentage/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Deposit Percentage'
        ];

        template('deposit_percentage/add', $data);
    }

    public function store()
    {
        $data = [
            'min' => $this->input->post('min'),
            'max' => $this->input->post('max'),
            'self_bonus' => $this->input->post('self_bonus'),
            'upline_bonus' => $this->input->post('upline_bonus'),
            'deposit_count' => $this->input->post('deposit_count'),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $recharge = $this->DepositPercentage_model->store($data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Percentage Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositPercentage');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Deposit Bonus',
            'deposit' => $this->DepositPercentage_model->getDepositById($id)
        ];
        template('deposit_percentage/edit', $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $data = [
            'user_type' => $this->input->post('user_type'),
            'percentage' => $this->input->post('percentage'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $recharge = $this->DepositPercentage_model->update($id, $data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Percentage Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositPercentage');
    }


    public function delete($id)
    {
        if ($this->DepositPercentage_model->delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Deposite Percentage Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositPercentage');
    }
}
