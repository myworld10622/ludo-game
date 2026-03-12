<?php

use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Redeem extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $header = $this->input->request_headers('token');

        if (!isset($header['Token'])) {
            $data['message'] = 'Invalid Request';
            $data['code'] = HTTP_UNAUTHORIZED;
            $this->response($data, HTTP_OK);
            exit();
        }

        if ($header['Token'] != getToken()) {
            $data['message'] = 'Invalid Authorization';
            $data['code'] = HTTP_METHOD_NOT_ALLOWED;
            $this->response($data, HTTP_OK);
            exit();
        }

        $this->load->model([
            'WithdrawalLog_model',
            'Users_model',
            'Setting_model'
        ]);
    }

    public function list_post()
    {
        $Redeem = $this->WithdrawalLog_model->AllRedeemList();
        if ($Redeem) {
            $data = [
                'List' => $Redeem,
                'message' => 'Success',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'No Redeem Available',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function Withdraw_post()
    {
        $user_id = $this->input->post('user_id');
        $redeem_id = $this->input->post('redeem_id');
        $type = $this->input->post('type');
        // $mobile = $this->input->post('mobile');
        if (empty($user_id) || empty($redeem_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $UserData = $this->Users_model->UserProfile($user_id);
        
        if (empty($UserData)) {
            $data = [
                'message' => 'Invalid User ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $RedeemData = $this->WithdrawalLog_model->getRedeem($redeem_id);
        if (empty($RedeemData)) {
            $data = [
                'message' => 'Invalid Redeem ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // $checkRecharge = $this->WithdrawalLog_model->checkRecharge($user_id);
        // if (empty($checkRecharge)) {
        //     $data = [
        //         'message' => 'You are not eligible to withdraw, you will have to recharge',
        //         'code' => HTTP_NOT_FOUND,
        //     ];
        //     $this->response($data, HTTP_OK);
        // }

        $Setting =$this->Setting_model->Setting(); 
        if ($RedeemData->coin<$Setting->min_withdrawal) {
            $data = [
                'message' => 'You can not withdraw less then '.$Setting->min_withdrawal.'$',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($UserData[0]->winning_wallet < $RedeemData->coin) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $user_bank = $this->Users_model->UserBankDetails($user_id);

        if(empty($user_bank)){
            $data = [
                'message' => 'Please Fill Account Details First From Profile',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $WithDraw = $this->WithdrawalLog_model->WithDraw($UserData[0]->id, $RedeemData->id, $RedeemData->coin, $user_bank, $type);
        if ($WithDraw) {
            log_statement($UserData[0]->id,WITHDRAW,-$RedeemData->coin,$WithDraw);
            $data = [
                'message' => 'Thank You Successfully Withdrawn',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Something Went Wrong',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function withdrawRequestForAgent_post()
    {
        $this->load->model('Agent_model');
        $user_id = $this->input->post('user_id');
        $coins = $this->input->post('coins');
        $amount = $this->input->post('amount');
        $agent_id = $this->input->post('agent_id');
        $mobile = $this->input->post('mobile');
        $type = $this->input->post('type');
        if (empty($user_id) || empty($agent_id) || empty($coins)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $UserData = $this->Users_model->UserProfile($user_id);
        if (empty($UserData)) {
            $data = [
                'message' => 'Invalid User ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $AgentData=$this->Agent_model->UserAgentProfile($agent_id);
        if (empty($AgentData)) {
            $data['message'] = 'Agent Not Found.';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $Setting =$this->Setting_model->Setting(); 
        if ($coins<$Setting->min_withdrawal) {
            $data = [
                'message' => 'You can not withdraw less then '.$Setting->min_withdrawal.'$',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($UserData[0]->winning_wallet < $coins) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $price=round(($coins/100)*$AgentData[0]->agent_withdraw_rate);

        $user_bank = $this->Users_model->UserBankDetails($user_id);
        
        $WithDraw = $this->WithdrawalLog_model->withdrawRequestForAgent($UserData[0]->id, 0, $coins, $user_bank, $price,$agent_id, $type);
        if ($WithDraw) {
            log_statement($UserData[0]->id,WITHDRAW,-$coins,$WithDraw);
            $data = [
                'message' => 'Thank You Successfully Withdrawn',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Something Went Wrong',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function Withdraw_custom_crypto_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        $crypto_address = $this->input->post('crypto_address');
        $mobile = $this->input->post('mobile');

        if (empty($user_id) || empty($amount)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $UserData = $this->Users_model->UserProfile($user_id);
        if (empty($UserData)) {
            $data = [
                'message' => 'Invalid User ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $bank_details = $this->Users_model->UserBankDetails($user_id);
        if (empty($bank_details)) {
            $data = [
                'message' => 'Please update your bank details Or crypto details.',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        // if ($amount<100) {
        //     $data = [
        //         'message' => 'Minimum amount should be greater or equalt 100',
        //         'code' => HTTP_NOT_FOUND,
        //     ];
        //     $this->response($data, HTTP_OK);
        // }
        if ($amount>100000) {
            $data = [
                'message' => 'Maximum limit 100000',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        // // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($UserData[0]->winning_wallet < $amount) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // $withdraw_count = $this->Users_model->get_withdraw_count($user_id);
        // if($withdraw_count > 3){
        //     $data = [
        //         'message' => 'Withdraw Limit Exceeded',
        //         'code' => HTTP_NOT_FOUND,
        //     ];
        //     $this->response($data, HTTP_OK);
        // }

        $WithDraw = $this->WithdrawalLog_model->WithDrawCrypto($UserData[0]->id, '', $amount, $mobile,$crypto_address);
        if ($WithDraw) {
            log_statement($UserData[0]->id,WITHDRAW,-$amount,$WithDraw);
            $data = [
                'message' => 'Thank You Successfully Withdrawn',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Something Went Wrong',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function Withdraw_custom_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        $type = $this->input->post('type');
        $mobile = "";

        if (empty($user_id) || empty($amount)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $UserData = $this->Users_model->UserProfile($user_id);
        if (empty($UserData)) {
            $data = [
                'message' => 'Invalid User ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // $checkRecharge = $this->WithdrawalLog_model->checkRecharge($user_id);
        // if (empty($checkRecharge)) {
        //     $data = [
        //         'message' => 'You are not eligible to withdraw, you will have to recharge',
        //         'code' => HTTP_NOT_FOUND,
        //     ];
        //     $this->response($data, HTTP_OK);
        // }

        $Setting =$this->Setting_model->Setting();
        if ($amount<$Setting->min_withdrawal) {
            $data = [
                'message' => 'You can not withdraw less then '.$Setting->min_withdrawal,
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }


        // // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($UserData[0]->winning_wallet < $amount) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $user_bank = $this->Users_model->UserBankDetails($user_id);

        if(empty($user_bank)){
            $data = [
                'message' => 'Please Fill Account Details First From Profile',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $WithDraw = $this->WithdrawalLog_model->WithDraw($UserData[0]->id, '', $amount, $user_bank, $type);
        if ($WithDraw) {
            log_statement($UserData[0]->id,WITHDRAW,-$amount,$WithDraw);
            $data = [
                'message' => 'Thank You Successfully Withdrawn',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Something Went Wrong',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function WithDrawal_log_post()
    {
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $UserData = $this->Users_model->UserProfile($user_id);
        if (empty($UserData)) {
            $data = [
                'message' => 'Invalid User ID',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $Log = $this->WithdrawalLog_model->WithDrawal_log($user_id);
        if ($Log) {
            $data = [
                'List' => $Log,
                'message' => 'Success',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'No WithDrawal Log Available',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }
}