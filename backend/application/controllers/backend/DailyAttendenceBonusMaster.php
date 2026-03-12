<?php

class DailyAttendenceBonusMaster extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['DailyAttendenceBonusMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Daily Attendence Bonus Master Management',
            'Daily_Attendence_bonus_master' => $this->DailyAttendenceBonusMaster_model->AllDailyAttendenceBonus()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            // $data['SideBarbutton'] = ['backend/DailyAttendenceBonusMaster/add', 'Add Daily Attendence Bonus'];
        }
        template('daily_Attendence_bonus/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Daily Attendence Bonus'
        ];

        template('daily_Attendence_bonus/add', $data);
    }

    public function insert()
    {
        // $bonus = $this->input->post('bonus');
        $data = [
            'active_users' => $this->input->post('active_users'),
            'daily_Attendence_bonus' => $this->input->post('daily_Attendence_bonus')
        ];

        $subadmin = $this->DailyAttendenceBonusMaster_model->add_bonus($data);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Daily Attendence Bonus Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DailyAttendenceBonusMaster');
    }

    public function edit($id)
    {
        $bonus_details = $this->DailyAttendenceBonusMaster_model->bonus_details($id);
        $data = [
            'title' => 'Edit Daily Attendence Bonus',
            'bonus_details' => $bonus_details
        ];

        // echo "<pre>";
        // print_r(($subDomains));die;
        template('daily_Attendence_bonus/edit', $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $data = [
            'accumulated_amount' => $this->input->post('accumulated_amount'),
            'attendenece_bonus' => $this->input->post('attendenece_bonus')
        ];
        // print_r($data);
        // exit;

        $subadmin = $this->DailyAttendenceBonusMaster_model->update_bonus($id, $data);

        if ($subadmin) {
            $this->session->set_flashdata('msg', array('message' => 'Daily Attendence Bonus Master Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/DailyAttendenceBonusMaster');
    }
}
