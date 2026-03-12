<?php
class Gateway extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Gateway_model', 'Users_model', 'Coin_plan_model', 'Setting_model', 'DepositBonus_model']);
    }

    public function manual()
    {
        $data = [
            'title' => 'Manual Gatway',
            'ManualGatway' => $this->Gateway_model->ManualGatway(),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Gateway/addManual', 'Add Manual Gatways'];
        }
        template('gateway/manual', $data);
    }

    public function addManual()
    {
        $data = [
            'title' => 'Add Manual Gatways'
        ];
        template('gateway/add_manual', $data);
    }

    public function storeManual()
    {
        // Validate inputs
        $this->form_validation->set_rules('name', 'Gateway Name', 'required');
        $this->form_validation->set_rules('currency', 'Currency', 'required');
        $this->form_validation->set_rules('rate', 'Rate', 'required|numeric');
        $this->form_validation->set_rules('min_amount', 'Minimum Amount', 'required|numeric');
        $this->form_validation->set_rules('max_amount', 'Maximum Amount', 'required|numeric');
        $this->form_validation->set_rules('fixed_charge', 'Fixed Charge', 'required|numeric');
        $this->form_validation->set_rules('percent_charge', 'Percentage Charge', 'required|numeric');
        $this->form_validation->set_rules('instructions', 'Deposit Instructions', 'required');

        if ($this->form_validation->run() === FALSE) {
            // Reload form with errors
            $this->session->set_flashdata('errors', validation_errors());
            return redirect('backend/Gateway/addManual');
        }
        // Prepare data
        $data = [
            'name' => $this->input->post('name', true),
            'role' => $this->input->post('role', true),
            'currency' => $this->input->post('currency', true),
            'rate' => $this->input->post('rate', true),
            'min_amount' => $this->input->post('min_amount', true),
            'max_amount' => $this->input->post('max_amount', true),
            'fixed_charge' => $this->input->post('fixed_charge', true),
            'percent_charge' => $this->input->post('percent_charge', true),
            'instructions' => $this->input->post('instructions'), // contains HTML from CKEditor
            'status' => 1,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $role = $this->input->post('role');
        // Convert array of subadmins into comma-separated string
        $role = implode(',', $role);
        // Add subadmins to the data array
        $data['role'] = $role;

        // Insert into DB
        $insertId = $this->Gateway_model->store($data);
        if ($insertId) {
            $this->session->set_flashdata('msg', array('message' => 'Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Gateway/manual');
    }

    public function editManual($id)
    {
        $ManualGatway = $this->Gateway_model->getManualGatwayById($id);
        $ManualGatway->role = explode(',', $ManualGatway->role);
        $data = [
            'title' => 'Edit Manual Types',
            'ManualGatway' => $ManualGatway,
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway/edit_manual', $data);
    }

    public function updateManual($id)
    {
        // validation rules
        $this->form_validation->set_rules('name', 'Gateway Name', 'trim|required');
        $this->form_validation->set_rules('currency', 'Currency', 'trim|required|max_length[5]');
        $this->form_validation->set_rules('rate', 'Rate', 'required|numeric');
        $this->form_validation->set_rules('min_amount', 'Minimum Amount', 'required|numeric');
        $this->form_validation->set_rules('max_amount', 'Maximum Amount', 'required|numeric|greater_than_equal_to[' . $this->input->post('min_amount') . ']');
        $this->form_validation->set_rules('fixed_charge', 'Fixed Charge', 'required|numeric');
        $this->form_validation->set_rules('percent_charge', 'Percentage Charge', 'required|numeric');
        $this->form_validation->set_rules('instructions', 'Deposit Instructions', 'trim|required');

        if ($this->form_validation->run() === FALSE) {
            // validation failed — reload edit form
            $data['ManualGatway'] = $this->Gateway_model->getManualGatwayById($id);
            $data['title'] = 'Edit Manual Types';
            $this->load->view('backend/Gateway/edit', $data);
        } else {
            // gather sanitized input
            $data = [
                'name' => $this->input->post('name', TRUE),
                'currency' => $this->input->post('currency', TRUE),
                'rate' => $this->input->post('rate'),
                'min_amount' => $this->input->post('min_amount'),
                'max_amount' => $this->input->post('max_amount'),
                'fixed_charge' => $this->input->post('fixed_charge'),
                'percent_charge' => $this->input->post('percent_charge'),
                'instructions' => $this->input->post('instructions', TRUE),
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $role = $this->input->post('role');
            if (is_array($role)) {
            $role = implode(',', $role);
            $data['role'] = $role;
            }
            // perform update
            $update = $this->Gateway_model->updateManual($id, $data);
            // set a success message and redirect
            if ($update) {
                $this->session->set_flashdata('msg', array('message' => 'Gateway updated successfully.', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
            redirect('backend/Gateway/manual');
        }

    }
    public function toggleManualStatus($id)
    {
        // disallow in demo
        if (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'demo') {
            show_error('Action not allowed in demo mode.', 403);
        }

        $this->load->model('Gateway_model');
        $gateway = $this->Gateway_model->getManualGatwayById($id);
        if (!$gateway) {
            show_404();
        }

        // flip 0→1 or 1→0
        $new_status = ($gateway->status == 0) ? 1 : 0;
        $this->Gateway_model->update_status($id, $new_status);

        // flash message
        $msg = $new_status == 1
            ? 'Gateway enabled successfully.'
            : 'Gateway disabled successfully.';
        $this->session->set_flashdata('msg', array('message' => $msg, 'class' => 'success', 'position' => 'top-right'));

        // redirect back to manual list
        redirect('backend/Gateway/manual');
    }

    //////////////////////////////////////////////////////////////////////////
    //////////////////////////////// Agent ///////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    public function agentGateway()
    {
        $data = [
            'title' => 'All Gatway Numbers',
            'AgentManualGatway' => $this->Gateway_model->agentManualGatway($this->session->userdata('admin_id')),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Gateway/addAgentManual', 'Add New'];
        }
        template('gateway/agent_manual', $data);
    }
    public function addAgentManual()
    {
        $data = [
            'title' => 'Add Manual Gatways',
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway/add_agent_manual', $data);
    }

    public function storeAgentManual()
    {

        // Set validation rules
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            // Reload form with errors
            $this->session->set_flashdata('errors', validation_errors());
            return redirect('backend/Gateway/addAgentManual');
        }
        // Prepare data
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'agent_id' => $this->session->userdata('admin_id'),
            'created_date' => date('Y-m-d H:i:s'),
        ];
        // Insert into DB
        $insertId = $this->Gateway_model->storeAgentManual($data);
        if ($insertId) {
            $this->session->set_flashdata('msg', array('message' => 'Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Gateway/agentGateway');
    }

    public function editAgentManual($id)
    {

        $data = [
            'title' => 'Edit Manual Types',
            'AgentManualGatway' => $this->Gateway_model->getAgentManualGatwayById($id),
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),

        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway/edit_agent_manual', $data);
    }

    public function updateAgentManual($id)
    {
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('backend/Gateway/editAgentManual/' . $id);
        }

        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'agent_id' => $this->session->userdata('admin_id'),
            'updated_date' => date('Y-m-d H:i:s'),
        ];

        $update = $this->Gateway_model->updateAgentManual($id, $data);
        if ($update) {
            $this->session->set_flashdata('msg', array('message' => 'Manual Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Gateway/agentGateway');
    }

    ///////////////////// withdraw channel agent ///////////////////////
    public function agentGatewayWithdraw()
    {
        $data = [
            'title' => 'All Gatway Numbers',
            'AgentManualGatwayWithdraw' => $this->Gateway_model->agentManualGatwayWithdraw($this->session->userdata('admin_id')),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Gateway/addAgentManualWithdraw', 'Add New'];
        }
        template('gateway/agent_manual_withdraw', $data);
    }
    public function addAgentManualWithdraw()
    {
        $data = [
            'title' => 'Add Manual Gatways',
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway/add_agent_manual_withdraw', $data);
    }

    public function storeAgentManualWithdraw()
    {

        // Set validation rules
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            // Reload form with errors
            $this->session->set_flashdata('errors', validation_errors());
            return redirect('backend/Gateway/addAgentManualWithdraw');
        }
        // Prepare data
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'agent_id' => $this->session->userdata('admin_id'),
            'created_date' => date('Y-m-d H:i:s'),
        ];
        // Insert into DB
        $insertId = $this->Gateway_model->storeAgentManualWithdraw($data);
        if ($insertId) {
            $this->session->set_flashdata('msg', array('message' => 'Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Gateway/agentGatewayWithdraw');
    }

    public function editAgentManualWithdraw($id)
    {

        $data = [
            'title' => 'Edit Manual Types',
            'AgentManualGatwayWithdraw' => $this->Gateway_model->getAgentManualGatwayWithdrawById($id),
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),

        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway/edit_agent_manual_withdraw', $data);
    }

    public function updateAgentManualWithdraw($id)
    {
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('backend/Gateway/editAgentManualWithdraw/' . $id);
        }

        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'agent_id' => $this->session->userdata('admin_id'),
            'updated_date' => date('Y-m-d H:i:s'),
        ];

        $update = $this->Gateway_model->updateAgentManualWithdraw($id, $data);
        if ($update) {
            $this->session->set_flashdata('msg', array('message' => 'Manual Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Gateway/agentGatewayWithdraw');
    }


    //////////////////////////////////////////////////////////////////////////
    //////////////////////////////// Distributer /////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    
    public function distributorGateway()
    {
        $data = [
            'title' => 'All Gatway Numbers',
            'DistributorManualGatway' => $this->Gateway_model->distributorManualGatway($this->session->userdata('admin_id')),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Gateway/addDistributorManual', 'Add New'];
        }
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway_distributor/distributor_manual', $data);
    }
    public function addDistributorManual()
    {
        $data = [
            'title' => 'Add Manual Gatways',
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway_distributor/add_distributor_manual', $data);
    }

    public function storeDistributorManual()
    {

        // Set validation rules
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            // Reload form with errors
            $this->session->set_flashdata('errors', validation_errors());
            return redirect('backend/Gateway/addAgentManual');
        }
        // Prepare data
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'distributor_id	' => $this->session->userdata('admin_id'),
            'created_date' => date('Y-m-d H:i:s'),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        // Insert into DB
        $insertId = $this->Gateway_model->storeDistributorManual($data);
        if ($insertId) {
            $this->session->set_flashdata('msg', array('message' => 'Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Gateway/distributorGateway');
    }

    public function editDistributorManual($id)
    {

        $data = [
            'title' => 'Edit Manual Types',
            'AgentManualGatway' => $this->Gateway_model->getDistributorManualGatwayById($id),
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),

        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway_distributor/edit_distributor_manual', $data);
    }

    public function updateDistributorManual($id)
    {
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('backend/Gateway/editDistributorManual/' . $id);
        }

        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'distributor_id	' => $this->session->userdata('admin_id'),
            'updated_date' => date('Y-m-d H:i:s'),
        ];

        $update = $this->Gateway_model->updateDistributorManual($id, $data);
        if ($update) {
            $this->session->set_flashdata('msg', array('message' => 'Manual Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Gateway/distributorGateway');
    }

    ///////////////////// withdraw channel Distributor ///////////////////////
     public function distributorGatewayWithdraw()
    {
        $data = [
            'title' => 'All Gatway Numbers',
            'AgentManualGatwayWithdraw' => $this->Gateway_model->distributorManualGatwayWithdraw($this->session->userdata('admin_id')),
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/Gateway/addDistributorManualWithdraw', 'Add New'];
        }
        template('gateway_distributor/distributor_manual_withdraw', $data);
    }
    public function addDistributorManualWithdraw()
    {
        $data = [
            'title' => 'Add Manual Gatways',
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),
        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway_distributor/add_distributor_manual_withdraw', $data);
    }

    public function storeDistributorManualWithdraw()
    {

        // Set validation rules
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            // Reload form with errors
            $this->session->set_flashdata('errors', validation_errors());
            return redirect('backend/Gateway/addDistributorManualWithdraw');
        }
        // Prepare data
        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'created_date' => date('Y-m-d H:i:s'),
            'distributor_id	' => $this->session->userdata('admin_id'),
        ];
        // Insert into DB
        $insertId = $this->Gateway_model->storeDistributorManualWithdraw($data);
        if ($insertId) {
            $this->session->set_flashdata('msg', array('message' => 'Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Gateway/distributorGatewayWithdraw');
    }

    public function editDistributorManualWithdraw($id)
    {

        $data = [
            'title' => 'Edit Manual Types',
            'AgentManualGatwayWithdraw' => $this->Gateway_model->getDistributorManualGatwayWithdrawById($id),
            'ManualGateway' => $this->Gateway_model->getManualGatwayByRole($this->session->userdata('role')),

        ];
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('gateway_distributor/edit_distributor_manual_withdraw', $data);
    }

    public function updateDistributorManualWithdraw($id)
    {
        $this->form_validation->set_rules('gateway_id', 'Gateway Name', 'required|integer');
        $this->form_validation->set_rules('number', 'Number', 'required|numeric');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('backend/Gateway/editDistributorManualWithdraw/' . $id);
        }

        $data = [
            'gateway_id' => $this->input->post('gateway_id'),
            'number' => $this->input->post('number'),
            'distributor_id	' => $this->session->userdata('admin_id'),
            'updated_date' => date('Y-m-d H:i:s'),
        ];

        $update = $this->Gateway_model->updateDistributorManualWithdraw($id, $data);
        if ($update) {
            $this->session->set_flashdata('msg', array('message' => 'Manual Gateway Added successfully.', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Gateway/distributorGatewayWithdraw');
    }



    ################################# extra ##########################
    public function delete($id)
    {
        if ($this->TournamentTypes_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Tournamaent Types Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/TournamentTypes');
    }



}
