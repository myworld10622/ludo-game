<?php
class Kyc extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Kyc_model']);
    }

    public function index()
    {
        $data = [
            'title' => 'Kyc Management',
            'AllKyc' => $this->Kyc_model->AllKyc(0),
            'AllApproved' => $this->Kyc_model->AllKyc(1),
            'AllRejected' => $this->Kyc_model->AllKyc(2)
        ];
        $data['SideBarbutton'] ='';
        template('kyc/kyc', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Card',
            'AllCards' => $this->RobotCard_model->AllCards()
        ];

        template('robot_card/add', $data);
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Robot Card',
            'AllCards' => $this->RobotCard_model->AllCards(),
            'TableMaster' => $this->RobotCard_model->ViewTableMaster($id)
        ];

        template('robot_card/edit', $data);
    }

    public function ChangeStatus()
    {
        $id=$this->input->post('id');
        if ($this->Kyc_model->ChangeStatus($id)) {
           echo "true";
        } else {
           echo "false";
        }
    }

    public function insert()
    {
        // print_r($this->input->post('cards')[2]);
        // exit;
        if(count($this->input->post('cards'))==3){
            $data = [
                'card1' => $this->input->post('cards')[0],
                'card2' => $this->input->post('cards')[1],
                'card3' => $this->input->post('cards')[2],
                'added_date' => date('Y-m-d H:i:s')
            ];
            $TableMaster = $this->RobotCard_model->AddTableMaster($data);
            if ($TableMaster) {
                $this->session->set_flashdata('msg', array('message' => 'Robot Card Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
        }else{
            $this->session->set_flashdata('msg', array('message' => 'Invalid Card Selection', 'class' => 'error', 'position' => 'top-right'));
        }
       
        redirect('backend/RobotCards');
    }

    public function ReasonUpdate()
    {
        $data = [
            'reason' => $this->input->post('reason'),
            'status' => 2,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $TableMaster = $this->Kyc_model->UpdateTableMaster($data, $this->input->post('id'));
        if ($TableMaster) {
          echo "true";
        } else {           
            echo "false";
        }
 
    }

}