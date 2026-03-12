<?php

class Bet_income_master extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Bet_income_master_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Bet Income Bonus Master Management',
            'Bet_income_master' => $this->Bet_income_master_model->AllBetIncomeBonus()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Bet_income_master/add', 'Add Bet Income Bonus'];
        }
        template('bet_income/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Bet Income Bonus'
        ];

        template('bet_income/add', $data);
    }

    public function insert()
    {
        $bonus = $this->input->post('bonus');

        $subadmin = $this->Bet_income_master_model->add_bonus($bonus);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Bet Income Bonus Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Bet_income_master');
    }

    public function edit($id)
    {
        $bonus_details = $this->Bet_income_master_model->bonus_details($id);
        $data = [
            'title' => 'Edit Bet Income master',
            'bonus_details' => $bonus_details
        ];

        // echo "<pre>";
        // print_r(($subDomains));die;
        template('bet_income/edit', $data);
    }

    public function update()
    {
        $bonus = $this->input->post('bonus');
        $id = $this->input->post('id');

        $subadmin = $this->Bet_income_master_model->update_bonus($id, $bonus);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Bet Income Bonus Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Bet_income_master');
    }
}
