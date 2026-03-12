<?php
class DepositBonus extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['DepositBonus_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Deposit Bonus',
            'All' => $this->DepositBonus_model->All()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/DepositBonus/add', 'Add'];
        }
        template('deposit_bonus/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Deposit Bonus'
        ];

        template('deposit_bonus/add', $data);
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
        $recharge = $this->DepositBonus_model->Store($data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Bonus Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositBonus');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Deposit Bonus',
            'deposit' => $this->DepositBonus_model->ViewFisrtRecharge($id)
        ];
        template('deposit_bonus/edit', $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $data = [
            'min' => $this->input->post('min'),
            'max' => $this->input->post('max'),
            'self_bonus' => $this->input->post('self_bonus'),
            'upline_bonus' => $this->input->post('upline_bonus'),
            'deposit_count' => $this->input->post('deposit_count'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $recharge = $this->DepositBonus_model->Update($id, $data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Bonus Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositBonus');
    }


    public function delete($id)
    {
        if ($this->DepositBonus_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Deposite bonus Deleted Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DepositBonus');
    }
}
