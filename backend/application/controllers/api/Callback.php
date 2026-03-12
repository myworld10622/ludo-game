<?php

use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Callback extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Users_model');
        $this->load->model('Coin_plan_model');
        $this->load->model('Setting_model');
        $this->load->model('DepositBonus_model');
        $this->load->helper('string');
    }

    public function index_post()
    {
        // $post_data_expected = file_get_contents("php://input");
        $post_data_expected = json_encode($_REQUEST);
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);

        $post = json_decode($post_data_expected);

        //param1 is mandatory
        if (empty($post->param1)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Coin_plan_model->GetUserByOrderId($post->param1);

        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check user id with your local order
        if ($post->user_id!=$order_details[0]->user_id) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check amount with your local order
        if ($post->amount!=$order_details[0]->price) {
            $data['message'] = 'Invalid Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $setting = $this->Setting_model->Setting();
        //cross check if your local payment already done
        if ($post->status==1 && $order_details[0]->payment==0) {
            //update local payment status
            $this->Coin_plan_model->UpdateOrderPaymentStatus($post->param1);
            $this->Users_model->UpdateWalletOrder($order_details[0]->coin, $post->user_id);
            $this->Users_model->UpdateSpin($post->user_id, ceil($post->amount/100),0);

            $user = $this->Users_model->UserProfile($order_details[0]->user_id);
            log_statement ($order_details[0]->user_id, DEPOSIT, $order_details[0]->coin,
            $order_details[0]->id,0);
            $purchase_count = $this->Users_model->getNumberOfPurchase($order_details[0]->user_id);
            if (($purchase_count==1) && !empty($user[0]->referred_by)) {
                $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount, $order_details[0]->user_id);
                if($setting->referral_amount>0){
                    direct_admin_profit_statement(REFERRAL_BONUS,-$setting->referral_amount,$user[0]->referred_by);
                    log_statement ($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount,0,0);
                }
               
            }
            if(INCOME_DEPOSIT_BONUS){
            switch ($purchase_count) {
                case 1:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '1st Deposit Bonus',1);
                    break;
                case 2:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '2nd Deposit Bonus',2);
                    break;
                case 3:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '3rd Deposit Bonus',3);
                    break;
                case 4:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '4th Deposit Bonus',4);
                    break;
                case 5:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '5th Deposit Bonus',5);
                    break;
                default:
                    break;
            }
        }
            
            for ($i=1; $i <= 10; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($order_details[0]->coin*$setting->$level)/100);
                    $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                    $log_data = [
                        'user_id' => $user[0]->referred_by,
                        'purchase_id' => $order_details[0]->id,
                        'purchase_user_id' => $order_details[0]->user_id,
                        'coin' => $coins,
                        'purchase_amount' => $order_details[0]->coins,
                        'level' => $i,
                    ];

                    $this->Users_model->AddPurchaseReferLog($log_data);
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($order_details[0]->extra>0) {
                $this->Users_model->UpdateWalletOrder(($order_details[0]->coin*($order_details[0]->extra/100)), $post->user_id, bonus: 1);
            }
        }
    }

    public function payforme_post()
    {
        // $post_data_expected = file_get_contents("php://input");
        $post_data_expected = json_encode($_REQUEST);
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);

        $post = json_decode($post_data_expected);
        // print_r($post);

        //param1 is mandatory
        if ($post->status!='SUCCESS') {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Coin_plan_model->GetUserByOrderId($post->order_id);

        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check user id with your local order
        // if ($post->user_id!=$order_details[0]->user_id) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }
        //cross check amount with your local order
        if ($post->amount!=$order_details[0]->price) {
            $data['message'] = 'Invalid Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $setting = $this->Setting_model->Setting();
        // print_r($order_details);
        //cross check if your local payment already done
        if ($post->status=='SUCCESS' && $order_details[0]->payment==0) {
            //update local payment status
            $this->Coin_plan_model->UpdateOrderPaymentStatus($post->order_id);
            $this->Users_model->UpdateWalletOrder($order_details[0]->coin, $order_details[0]->user_id);
            // $this->Users_model->UpdateSpin($order_details[0]->user_id, ceil($post->amount/100),0);

            $user = $this->Users_model->UserProfile($order_details[0]->user_id);
            log_statement ($order_details[0]->user_id, DEPOSIT, $order_details[0]->coin,
            $order_details[0]->id,0);
            $purchase_count = $this->Users_model->getNumberOfPurchase($order_details[0]->user_id);
            if (($purchase_count==1) && !empty($user[0]->referred_by)) {
                $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount, $order_details[0]->user_id);
                if($setting->referral_amount>0){
                    direct_admin_profit_statement(REFERRAL_BONUS,-$setting->referral_amount,$user[0]->referred_by);
                    log_statement ($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount,0,0);
                }
               
            }
            if(INCOME_DEPOSIT_BONUS){
            switch ($purchase_count) {
                case 1:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '1st Deposit Bonus',1);
                    break;
                case 2:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '2nd Deposit Bonus',2);
                    break;
                case 3:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '3rd Deposit Bonus',3);
                    break;
                case 4:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '4th Deposit Bonus',4);
                    break;
                case 5:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '5th Deposit Bonus',5);
                    break;
                default:
                    break;
            }
        }
            
            for ($i=1; $i <= 10; $i++) {
                if ($user[0]->referred_by!=0) {
                    if($i==1){
                        $percent_user = $this->Users_model->UserProfile($user[0]->referred_by);
                        $coins = (($order_details[0]->coin * $percent_user[0]->referral_precent) / 100);
                    }
                    else{
                        $level = 'level_' . $i;
                        $coins = (($order_details[0]->coin * $setting->$level) / 100);
                    }
                    $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                    $log_data = [
                        'user_id' => $user[0]->referred_by,
                        'purchase_id' => $order_details[0]->id,
                        'purchase_user_id' => $order_details[0]->user_id,
                        'coin' => $coins,
                        'purchase_amount' => $order_details[0]->coin,
                        'level' => $i,
                    ];

                    $this->Users_model->AddPurchaseReferLog($log_data);

                    log_statement($user[0]->referred_by, REFERRAL_BONUS, $coins,$order_details[0]->user_id, 0);
                    
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($order_details[0]->extra>0) {
                $this->Users_model->UpdateWalletOrder(($order_details[0]->coin*($order_details[0]->extra/100)), $order_details[0]->user_id, bonus: 1);
            }
            $data1['message'] = 'Success';
            $data1['code'] = HTTP_OK;
            $this->response($data1, 200);
            exit();
        }else{
            $data1['message'] = 'Already Paid';
            $data1['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data1, 200);
            exit();
        }

        
    }

    public function nowpaymentpayout_post()
    {
        $post_data_expected = file_get_contents("php://input");
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);
       
        $post = json_decode($post_data_expected);
        $order_id= $post->order_id;

        //param1 is mandatory
        if (empty($order_id)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Coin_plan_model->GetUserByOrderId($order_id);

        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check user id with your local order
        // if ($post->user_id!=$order_details[0]->user_id) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }
        //cross check amount with your local order
        if ($post->price_amount!=$order_details[0]->price) {
            $data['message'] = 'Invalid Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
   
        $setting = $this->Setting_model->Setting();
        //cross check if your local payment already done
        if ($post->payment_status=='finished' && $order_details[0]->payment==0) {
            //update local payment status
            $total_amount=$this->Coin_plan_model->GetTotalAmountByUser($order_details[0]->user_id);
            $category=$this->Coin_plan_model->GetUserCategoryByAmount($total_amount+$order_details[0]->coin);

            $category_id = (empty($category)) ? 0 : $category->id;
            $category_amount = (empty($category)) ? 0 : $order_details[0]->coin*($category->percentage/100);

            $this->Coin_plan_model->UpdateOrderPaymentStatus($order_details[0]->id);
            $this->Users_model->UpdateWalletOrder($order_details[0]->coin+$category_amount, $order_details[0]->user_id);
            log_statement ($order_details[0]->user_id, DEPOSIT, $order_details[0]->coin+$category_amount,
            $order_details[0]->id,0);

            $this->Users_model->UpdateSpin($order_details[0]->user_id, ceil($order_details[0]->price/100), $category_id);

            $user = $this->Users_model->UserProfile($order_details[0]->user_id);


            $purchase_count = $this->Users_model->getNumberOfPurchase($order_details[0]->user_id);
            if (($purchase_count==1) && !empty($user[0]->referred_by)) {
                $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount,$order_details[0]->user_id);
                if($setting->referral_amount>0){
                    direct_admin_profit_statement(REFERRAL_BONUS,-$setting->referral_amount,$user[0]->referred_by);
                    log_statement ($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount,0,0);
                }
               
            }
            if(INCOME_DEPOSIT_BONUS){
            switch ($purchase_count) {
                case 1:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '1st Deposit Bonus',1);
                    break;
                case 2:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '2nd Deposit Bonus',2);
                    break;
                case 3:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '3rd Deposit Bonus',3);
                    break;
                case 4:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '4th Deposit Bonus',4);
                    break;
                case 5:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '5th Deposit Bonus',5);
                    break;
                default:
                    break;
            }
        }

           
            for ($i=1; $i <= 10; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($order_details[0]->coin*$setting->$level)/100);
                    if(!empty($coins)){
                        $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                        $log_data = [
                            'user_id' => $user[0]->referred_by,
                            'purchase_id' => $order_details[0]->id,
                            'purchase_user_id' => $order_details[0]->user_id,
                            'coin' => $coins,
                            'purchase_amount' => $order_details[0]->coins,
                            'level' => $i,
                        ];
    
                        $this->Users_model->AddPurchaseReferLog($log_data);
                    }
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($order_details[0]->extra>0) {
                $extra_amount = $order_details[0]->coin*($order_details[0]->extra/100);
                $this->Users_model->UpdateWalletOrder($extra_amount, $order_details[0]->user_id, bonus: 1);
                $this->Users_model->ExtraWalletLog($order_details[0]->user_id, $extra_amount, 0);
            }

            if ($category_amount>0) {
                $this->Users_model->ExtraWalletLog($order_details[0]->user_id, $category_amount, 1);
            }

        }
    }


    public function UpiGateway_post()
    {
        $post_data_expected = file_get_contents("php://input");
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);

        parse_str($post_data_expected, $output_array);

        $id=$output_array['id'];
        $status=$output_array['status'];
        // $id = 80567325;
        //param1 is mandatory
        if (empty($id)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Users_model->GetUserByOrderTxnId($id);
        // print_r($order_details);exit;
        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check user id with your local order
        if (empty($order_details[0]->user_id)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check amount with your local order
        if (empty($order_details[0]->price)) {
            $data['message'] = 'Invalid Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $setting = $this->Setting_model->Setting();
        //cross check if your local payment already done
        if ($status=='success' && $order_details[0]->payment==0) {
            //update local payment status
            $total_amount=$this->Coin_plan_model->GetTotalAmountByUser($order_details[0]->user_id);
            $category=$this->Coin_plan_model->GetUserCategoryByAmount($total_amount+$order_details[0]->coin);

            $category_id = (empty($category)) ? 0 : $category->id;
            $category_amount = (empty($category)) ? 0 : $order_details[0]->coin*($category->percentage/100);

            $this->Coin_plan_model->UpdateOrderPaymentStatus($order_details[0]->id);
            $this->Users_model->UpdateWalletOrder($order_details[0]->coin+$category_amount, $order_details[0]->user_id);
            log_statement ($order_details[0]->user_id, DEPOSIT, $order_details[0]->coin+$category_amount,
            $order_details[0]->id,0);

            $this->Users_model->UpdateSpin($order_details[0]->user_id, ceil($order_details[0]->price/100), $category_id);

            $user = $this->Users_model->UserProfile($order_details[0]->user_id);


            $purchase_count = $this->Users_model->getNumberOfPurchase($order_details[0]->user_id);
            if (($purchase_count==1) && !empty($user[0]->referred_by)) {
                $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount,$order_details[0]->user_id);
                if($setting->referral_amount>0){
                    direct_admin_profit_statement(REFERRAL_BONUS,-$setting->referral_amount,$user[0]->referred_by);
                    log_statement ($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount,0,0);
                }
               
            }
            if(INCOME_DEPOSIT_BONUS){
            switch ($purchase_count) {
                case 1:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '1st Deposit Bonus',1);
                    break;
                case 2:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '2nd Deposit Bonus',2);
                    break;
                case 3:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '3rd Deposit Bonus',3);
                    break;
                case 4:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '4th Deposit Bonus',4);
                    break;
                case 5:
                    depositBonus($order_details[0]->coin, $order_details[0]->id, $order_details[0]->user_id, $user[0]->referred_by, '5th Deposit Bonus',5);
                    break;
                default:
                    break;
            }
        }

           
            for ($i=1; $i <= 10; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($order_details[0]->coin*$setting->$level)/100);
                    if(!empty($coins)){
                        $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                        $log_data = [
                            'user_id' => $user[0]->referred_by,
                            'purchase_id' => $order_details[0]->id,
                            'purchase_user_id' => $order_details[0]->user_id,
                            'coin' => $coins,
                            'purchase_amount' => $order_details[0]->coins,
                            'level' => $i,
                        ];
    
                        $this->Users_model->AddPurchaseReferLog($log_data);
                    }
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($order_details[0]->extra>0) {
                $extra_amount = $order_details[0]->coin*($order_details[0]->extra/100);
                $this->Users_model->UpdateWalletOrder($extra_amount, $order_details[0]->user_id, bonus: 1);
                $this->Users_model->ExtraWalletLog($order_details[0]->user_id, $extra_amount, 0);
            }

            if ($category_amount>0) {
                $this->Users_model->ExtraWalletLog($order_details[0]->user_id, $category_amount, 1);
            }

        }
    }
    public function checkUpiGateWayPaymentStatus_post()
    {
        $post_data_expected = json_encode($_POST);
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);

        $post = json_decode($post_data_expected);

        //param1 is mandatory
        if (empty($post->param1)) {
            $data['message'] = 'Invalid params';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Coin_plan_model->GetUserByOrderId($post->param1);
       
        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
     
        //cross check if your local payment already done
        if ($order_details[0]->payment == 1) {
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }
    }

    public function verify_post()
    {
        $post_data_expected = json_encode($_POST);
        $data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $data);

        $post = json_decode($post_data_expected);

        //param1 is mandatory
        if (empty($post->param1)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //checks param1 in local
        $order_details = $this->Coin_plan_model->GetUserByOrderId($post->param1);

        if (empty($order_details)) {
            $data['message'] = 'Invalid Order Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check user id with your local order
        if ($post->user_id!=$order_details[0]->user_id) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check amount with your local order
        if ($post->amount!=$order_details[0]->price) {
            $data['message'] = 'Invalid Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        //cross check if your local payment already done
        if ($post->status==1 && $order_details[0]->payment==0) {
            //update local payment status
            $total_amount=$this->Coin_plan_model->GetTotalAmountByUser($post->user_id);
            $category=$this->Coin_plan_model->GetUserCategoryByAmount($total_amount+$order_details[0]->coin);

            $category_id = (empty($category)) ? 0 : $category->id;
            $category_amount = (empty($category)) ? 0 : $order_details[0]->coin*($category->percentage/100);

            $this->Coin_plan_model->UpdateOrderPaymentStatus($post->param1);
            $this->Users_model->UpdateWalletOrder($order_details[0]->coin+$category_amount, $post->user_id);

            $this->Users_model->UpdateSpin($post->user_id, ceil($post->amount/100), $category_id);

            $user = $this->Users_model->UserProfile($post->user_id);
            $setting = $this->Setting_model->Setting();
            for ($i=1; $i <= 3; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($order_details[0]->coin*$setting->$level)/100);
                    $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                    $log_data = [
                        'user_id' => $user[0]->referred_by,
                        'purchase_id' => $order_details[0]->id,
                        'purchase_user_id' => $post->user_id,
                        'coin' => $coins,
                        'level' => $i,
                    ];

                    $this->Users_model->AddPurchaseReferLog($log_data);
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($order_details[0]->extra>0) {
                $extra_amount = $order_details[0]->coin*($order_details[0]->extra/100);
                $this->Users_model->UpdateWalletOrder($extra_amount, $post->user_id, bonus: 1);
                $this->Users_model->ExtraWalletLog($post->user_id, $extra_amount, 0);
            }

            if ($category_amount>0) {
                $this->Users_model->ExtraWalletLog($post->user_id, $category_amount, 1);
            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }
    }

    public function spin_post()
    {
        $post_data_expected = json_encode($_POST);
        $log_data = [
            'response' => $post_data_expected
        ];
        $this->db->insert('response_log', $log_data);

        $post = json_decode($post_data_expected);

        //param1 is mandatory
        $user = $this->Users_model->UserProfile($post->user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        //cross check if your local payment already done
        if ($user[0]->spin_remaining>0) {
            //update local payment status
            $coin = $post->amount;
            if ($coin>3) {
                $data['message'] = 'Invalid Spin';
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            direct_admin_profit_statement(SPIN,-$coin,$post->user_id);
           
            $this->Users_model->UpdateWalletSpin($post->user_id, $coin);
            $this->Users_model->ExtraWalletLog($post->user_id, $coin, 0);
            log_statement ($post->user_id, SPIN, $coin,0,0);
            $data['message'] = 'Success';
            $data['coin'] = $coin;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'No Spin Found';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function jungle_rummy_room_join_post()
    {
        $responseData['room_id'] = 1;
        $responseData['time_remaining'] = 15;
        $data['responseData'] = $responseData;
        $data['responseMessage'] = 'Room Join';
        $data['responseCode'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function jungle_rummy_room_list_post()
    {
        $roomData['maxPlayers'] = 1;
        $roomData['entryFee'] = 1;
        $roomData['activePlayers'] = 1;
        $roomData['usersInTable'] = 1;

        $cashGameInfo['gameMode'] = 1;
        $cashGameInfo['minFee'] = 1;
        $cashGameInfo['maxFee'] = 10;
        $cashGameInfo['activePlayers'] = 1;
        $cashGameInfo['roomData'][] = $roomData;

        $responseData['cashGameInfo'][] = $cashGameInfo;
        $responseData['practiceGameInfo'][] = $cashGameInfo;
        $data['responseData'] = $responseData;
        $data['responseMessage'] = 'Room List';
        $data['responseCode'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function jungle_rummy_card_post()
    {
        $this->load->model('Rummy_model');
        $cards = $this->Rummy_model->GetStartCards(13);
        $data['cards'] = $cards;
        $data['message'] = 'Card List';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }
}
