<?php
class WithdrawalLog extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('WithdrawalLog_model');
    }

    public function index()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $tab_Active = $this->input->get('tab_active');

        $data = [
            'title' => 'Withdrawal History',
            'Pending' => $this->WithdrawalLog_model->WithDrawal_list(0, $start_date, $end_date),
            'Approved' => $this->WithdrawalLog_model->WithDrawal_list(1, $start_date, $end_date),
            'Rejected' => $this->WithdrawalLog_model->WithDrawal_list(2, $start_date, $end_date),
            'tab_active' => $tab_Active
        ];

        template('redeem/withdrawal_log', $data);
    }


    public function ChangeStatus()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $response = "";

        if($status==1){

            // $withdraw = $this->WithdrawalLog_model->WithDrawal_log_by_id($id);
            // // API URL
            // $api_url = 'https://Payformee.com/api/bank/create-order';

            // // User input
            // $user_token = 'ba7ded0202cd40d42dab6bfff66bd68b'; // Replace with the user's API Key
            // $amount = $withdraw->coin; // Replace with the payout amount
            // $accnumber = $withdraw->acc_no; // Replace with acc number
            // $ifsc = $withdraw->ifsc_code; //ifsc code
            // $secret_key = 'xQWxHUOaHsCKMmR991hL3Krd6flHXpny'; // Your secret key for checksum generation 

            // // Create an array with POST data
            // $post_data = [
            //     'user_token' => $user_token,
            //     'amount' => $amount,
            //     'acc_no' => $accnumber,
            //     'ifsc' =>$ifsc
            // ];

            // // Generate xverify
            // $xverify = $this->generatexverify($post_data, $secret_key);

            // // Initialize cURL session
            // $ch = curl_init($api_url);

            // // Prepare the headers including the X-Verify custom header
            // $headers = [
            //     'Content-Type: application/x-www-form-urlencoded', // Set the content type
            //     'X-VERIFY: ' . $xverify, // Send the xverify in the headers
            // ];

            // // Set cURL options
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_POST, true);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // Use http_build_query to format POST data
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Ensure headers are correctly set

            // // Execute the cURL session and capture the response
            // $response = curl_exec($ch);

            // // Check for cURL errors
            // if (curl_errno($ch)) {
            //     echo 'cURL Error: ' . curl_error($ch);
            // } else {
            //     $res = json_decode($response);

            //     if(!$res->status){
            //         $this->WithdrawalLog_model->ChangeStatus($id, 0, $response);
            //         $data = ['msg' => $res->message, 'class' => 'error'];
            //         echo json_encode($data);
            //         return;
            //     }
            // }
        }

        $Change = $this->WithdrawalLog_model->ChangeStatus($id, $status, $response);
        if ($Change) {
            $data = ['msg' => 'Status Change Successfully', 'class' => 'success'];
        } else {
            $data = ['msg' => 'Something went to wrong', 'class' => 'error'];
        }
        echo json_encode($data);
    }

    // Function to generate xverify
    function generatexverify($data, $secret_key) {
    // Sort the data by keys to ensure consistent order
        ksort($data);
        $dataString = implode('|', array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, array_keys($data), $data));
        return hash_hmac('sha256', $dataString, $secret_key);
    }

    public function ReedemNow()
    {
        $data = [
            'title' => 'Withdraw Chips Management',
            'AllRedeem' => $this->WithdrawalLog_model->AllRedeemList()
        ];
        if ($_ENV['ENVIRONMENT'] != 'demo') {
            $data['SideBarbutton'] = ['backend/WithdrawalLog/add', 'Add Chips'];
        }
        template('redeem/index', $data);
    }
    public function add()
    {
        $data = [
            'title' => 'Add Redeem',
        ];
        template('redeem/add', $data);
    }

    public function insert()
    {
        $RedeemData = [
            'title' => $this->input->post('title'),
            'coin' => $this->input->post('coin'),
            'amount' => $this->input->post('amount')
        ];
        $img = '';
        if (!empty($_FILES["img"]['name'])) {
            $config['upload_path'] = './data/Redeem/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|JPEG';
            $config['max_size'] = '10000';
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('img')) {

                $error = array('error' => $this->upload->display_errors());

                $this->session->set_flashdata('msg', array('message' => $error['error'], 'class' => 'error', 'position' => 'top-right'));
                redirect('backend/WithdrawalLog/Add');
            } else {

                $file = $this->upload->data();
                $img = $file['file_name'];
                $RedeemData['img'] = $img;
            }
        }

        $InsertRedeem = $this->WithdrawalLog_model->Insert($RedeemData);
        if ($InsertRedeem) {
            $this->session->set_flashdata('msg', array('message' => 'Redeem Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/WithdrawalLog/ReedemNow');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Redeem',
            'Redeem' => $this->WithdrawalLog_model->getRedeem($id),
        ];
        $this->form_validation->set_rules('img', 'Image', 'required');
        if ($this->form_validation->run() == false)
            template('redeem/edit', $data);
    }

    public function update()
    {
        $Redeem_id = $this->input->post('Redeem_id');
        // print_r($Redeem_id); exit;
        $RedeemData = [
            'title' => $this->input->post('title'),
            'coin' => $this->input->post('coin'),
            'amount' => $this->input->post('amount'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $img = '';
        if (!empty($_FILES["img"]['name'])) {
            $config['upload_path'] = './data/Redeem/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|JPEG';
            $config['max_size'] = '10000';
            //            $config['max_width'] = '2000';
            //            $config['max_height'] = '2000';
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('img')) {

                $error = array('error' => $this->upload->display_errors());

                $this->session->set_flashdata('msg', array('message' => $error['error'], 'class' => 'error', 'position' => 'top-right'));
                redirect('backend/WithdrawalLog/');
            } else {

                $file = $this->upload->data();
                $img = $file['file_name'];
                $RedeemData['img'] = $img;
            }
        }

        $UpdateRedeem = $this->WithdrawalLog_model->update($Redeem_id, $RedeemData);
        if ($UpdateRedeem) {
            $this->session->set_flashdata('msg', array('message' => 'Redeem Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/WithdrawalLog/ReedemNow');
    }

    public function delete($id)
    {
        if ($this->WithdrawalLog_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Redeem Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/WithdrawalLog/ReedemNow');
    }
}