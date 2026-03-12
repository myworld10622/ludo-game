<?php
class Deposit extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Deposit_model','Gateway_model']);
    }

    //////////////////////////////////////////////////////////////////////
    /////////////////////// Distributor  to admin ////////////////////////
    //////////////////////////////////////////////////////////////////////

    public function index()
    {
        $data = [
            'title' => 'Deposits',
            'PendingManualPurchase' => $this->Deposit_model->depositHistory(0),
            'ApprovedManualPurchase' => $this->Deposit_model->depositHistory(1),
            'RejectedManualPurchase' => $this->Deposit_model->depositHistory(2),
        ];

        template('deposit/index', $data);
    }
    public function add()
    {
        $data = [
            'title' => 'Add Deposit Requests',
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),
        ];

        template('deposit/request', $data);
    }

    public function store()
    {
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'amount' => $this->input->post('amount'),
            'sender_number' => $this->input->post('sender_number'),
            'txn_id' => $this->input->post('txn_id'),
            'status' => 0, // pending
            'distributor_id' => $this->session->userdata('admin_id'),
        ];
        $recharge = $this->Deposit_model->store($data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Request Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Deposit/add');
    }

    public function ChangeStatus()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $Change = $this->Deposit_model->DepositChangeStatus($id, $status);
        if ($Change) {
            $data = ['msg' => 'Status Change Successfully', 'class' => 'success'];
        } else {
            $data = ['msg' => 'Something went to wrong', 'class' => 'error'];
        }
        echo json_encode($data);
    }

    //////////////////////////////////////////////////////////////////////
    /////////////////////// agent to distributor /////////////////////////
    //////////////////////////////////////////////////////////////////////

    public function indexDistributor()
    {
        $distributor_id  = $this->session->userdata('admin_id');
        $data = [
            'title' => 'Deposits',
            'PendingManualPurchase' => $this->Deposit_model->depositHistoryDistributor($distributor_id, 0),
            'ApprovedManualPurchase' => $this->Deposit_model->depositHistoryDistributor($distributor_id, 0),
            'RejectedManualPurchase' => $this->Deposit_model->depositHistoryDistributor($distributor_id, 0),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('deposit/index_to_distributor', $data);
    }

    public function addToDistributor()
    {
        $data = [
            'title' => 'Add Deposit Requests',
            'ManualGateway' => $this->Gateway_model->getGatwayByRoleForAgentRequest($this->session->userdata('role'),$this->session->userdata('addedby')),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('deposit/request_to distributor', $data);
    }

    public function storeToDistributor()
    {
        $agent_id = $this->session->userdata('admin_id');
        $agent =  $this->Deposit_model->getAgentDetailsById($agent_id);
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'gateway_number' => $this->input->post('gateway_number'),
            'amount' => $this->input->post('amount'),
            'sender_number' => $this->input->post('sender_number'),
            'txn_id' => $this->input->post('txn_id'),
            'status' => 0, // pending
            'agent_id' => $agent_id,
            'distributor_id' => $agent->addedby,
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        $recharge = $this->Deposit_model->storeToDistributer($data);
        if ($recharge) {
            $this->session->set_flashdata('msg', array('message' => 'Deposit Request Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Deposit/add');
    }


    public function ChangeStatusDistributor()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $Change = $this->Deposit_model->DepositChangeStatusDistributor($id, $status);
        if ($Change) {
            $data = ['msg' => 'Status Change Successfully', 'class' => 'success'];
        } else {
            $data = ['msg' => 'Something went to wrong', 'class' => 'error'];
        }
        echo json_encode($data);
    }
}
