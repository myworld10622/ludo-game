<?php

class RummyTournaMent extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['RummyTournaMentMaster_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Manage Tournament',
            'AllTournaments' => $this->RummyTournaMentMaster_model->AllTournaMent()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/RummyTournaMent/add', 'Add Tournament'];
        }
        template('tournament/index', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Tournament'
        ];

        template('tournament/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Tournament',
            'data' => $this->RummyTournaMentMaster_model->ViewTableMaster($id)
        ];

        template('tournament/edit', $data);
    }

    public function delete($id)
    {
        if ($this->RummyTournaMentMaster_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Tournament Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/RummyTournaMent');
    }

    public function insert()
    {
        $data = [
            'name' => $this->input->post('name'),
            'no_of_participant' => $this->input->post('no_of_participant'),
            'registration_fee' => $this->input->post('registration_fee'),
            'first_price' => $this->input->post('first_price'),
            'second_price' => $this->input->post('second_price'),
            'third_price' => $this->input->post('third_price'),
            'start_time' => date('Y-m-d H:i:s', strtotime($this->input->post('start_time'))),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $check = $this->RummyTournaMentMaster_model->CheckDuplicate($this->input->post('name'));
        if (empty($check)) {
            $category = $this->RummyTournaMentMaster_model->AddTableMaster($data);
            if ($category) {
                $this->session->set_flashdata('msg', array('message' => 'Tournament Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Category Already Exists', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/RummyTournaMent');
    }

    public function update()
    {
        $data = [
            'name' => $this->input->post('name'),
            'no_of_participant' => $this->input->post('no_of_participant'),
            'registration_fee' => $this->input->post('registration_fee'),
            'first_price' => $this->input->post('first_price'),
            'second_price' => $this->input->post('second_price'),
            'third_price' => $this->input->post('third_price'),
            'start_time' => date('Y-m-d H:i:s', strtotime($this->input->post('start_time'))),
            'added_date' => date('Y-m-d H:i:s')
        ];
        $Category = $this->RummyTournaMentMaster_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($Category) {
            $this->session->set_flashdata('msg', array('message' => 'Tournament Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/RummyTournaMent');
    }
}
