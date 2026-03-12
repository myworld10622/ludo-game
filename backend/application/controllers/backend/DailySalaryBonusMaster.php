<?php

class DailySalaryBonusMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['DailySalaryBonusMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Daily Salary Bonus Master Management',
            'Daily_salary_bonus_master' => $this->DailySalaryBonusMaster_model->AllDailySalaryBonus()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/DailySalaryBonusMaster/add', 'Add Daily Salary Bonus'];
        }
        template('daily_salary_bonus/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Daily Salary Bonus'
        ];

        template('daily_salary_bonus/add', $data);
    }

    public function insert()
    {
        // $bonus = $this->input->post('bonus');
        $data = [
            'active_users' => $this->input->post('active_users'),
            'daily_salary_bonus' => $this->input->post('daily_salary_bonus')
        ];

        $subadmin = $this->DailySalaryBonusMaster_model->add_bonus($data);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Daily Salary Bonus Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DailySalaryBonusMaster');
    }

    public function edit($id)
    {
        $bonus_details = $this->DailySalaryBonusMaster_model->bonus_details($id);
        $data = [
            'title' => 'Edit Daily Salary Bonus',
            'bonus_details' => $bonus_details
        ];

        // echo "<pre>";
        // print_r(($subDomains));die;
        template('daily_salary_bonus/edit', $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $data = [
            'active_users' => $this->input->post('active_users'),
            'daily_salary_bonus' => $this->input->post('daily_salary_bonus')
        ];
        // print_r($data);
        // exit;

        $subadmin = $this->DailySalaryBonusMaster_model->update_bonus($id, $data);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Daily Salary Bonus Master Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DailySalaryBonusMaster');
    }
}
