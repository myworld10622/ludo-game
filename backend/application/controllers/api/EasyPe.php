<?php

use Restserver\Libraries\REST_Controller;
use Razorpay\Api\Api;
use paytm\paytmchecksum\PaytmChecksum;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class EasyPe extends REST_Controller
{
    private $data;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Users_model');
        $this->load->model('Coin_plan_model');
        $this->load->model('Setting_model');
    }

    public function pay_get()
    {
        $user_id = $this->input->get('user_id');

        $plan_id = $this->input->get('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->TokenConfirm($this->input->get('user_id'), $this->input->get('token'));
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan = $this->Coin_plan_model->View($plan_id);
        if (empty($plan)) {
            $data['message'] = 'Invalid Plan';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $Amount = $plan->price;             //Product Amount While the Time OF Order

        $Order_ID = $this->Coin_plan_model->GetCoin($user_id, $plan_id, $plan->coin, $Amount);

        if (empty($Order_ID)) {
            $data['message'] = 'Error while Creating Order';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $Order_ID);

        $amount = $Amount*100;
        $pay_id = EASY_PE_PAY_ID;
        $order_id = EASY_PE_PREFIX.$Order_ID;
        $tnxtype = PROJECT_NAME;
        $customer_id = $user_id;
        $customer_name = $user->name;
        $customer_phone = $user->mobile;
        $customer_email = $user->email;
        $product_desc = 'Coin Purchase';
        $return_url = base_url('/api/EasyPe/response');

        $hash_string = "AMOUNT=".$amount."~CURRENCY_CODE=356~CUST_EMAIL=".$customer_email."~CUST_ID=".$customer_id."~CUST_NAME=".$customer_name."~CUST_PHONE=".$customer_phone."~ORDER_ID=".$order_id."~PAY_ID=".$pay_id."~RETURN_URL=".$return_url."~TXNTYPE=".$tnxtype.EASY_PE_SALT;

        $hash = strtoupper(hash('sha256', $hash_string));

        echo '<!DOCTYPE html>
        <html>
        <head>
        <style>
        .container { 
          height: 200px;
          position: relative;
          border: 3px solid green; 
        }
        
        .center {
          margin: 0;
          position: absolute;
          top: 50%;
          left: 50%;
          -ms-transform: translate(-50%, -50%);
          transform: translate(-50%, -50%);
        }
        </style>
        </head>
        <body>
        <div class="container">
          <div class="center">
          <form action="https://pg.eazype.co/pgui/jsp/paymentrequest" method="POST">
            <input type="hidden" name="PAY_ID" value="'.$pay_id.'"/>
            <input type="hidden" name="ORDER_ID" value="'.$order_id.'"/>
            <input type="hidden" name="AMOUNT" value="'.$amount.'"/>
            <input type="hidden" name="TXNTYPE" value="'.$tnxtype.'"/>
            <input type="hidden" name="CUST_ID" value="'.$customer_id.'"/>
            <input type="hidden" name="CUST_NAME" value="'.$customer_name.'"/>
            <input type="hidden" name="CUST_PHONE" value="'.$customer_phone.'"/>
            <input type="hidden" name="CUST_EMAIL" value="'.$customer_email.'"/>
            <input type="hidden" name="CURRENCY_CODE" value="356"/>
            <input type="hidden" name="RETURN_URL" value="'.$return_url.'"/>
            <input type="hidden" name="HASH" value="'.$hash.'"/>
            <button type="submit">Click to Pay Rs.'.$Amount.' And Get '.$plan->coin.' Coins</button>
        </form>
          </div>
        </div>
        
        </body>
        </html>';
    }

    public function response_post()
    {
        $setting = $this->Setting_model->Setting();

        $user_id = $this->input->post('CUST_ID');
        $order_id = str_replace(EASY_PE_PREFIX, '', $this->input->post('ORDER_ID'));

        if (empty($user_id) || empty($order_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $CheckTicket = $this->Coin_plan_model->GetUserByOrderId($order_id);
        if (empty($CheckTicket)) {
            $data['message'] = 'Invalid Order ID';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($this->input->post('STATUS')=='Captured') {

            $Amount = $CheckTicket[0]->price;
            if (($this->input->post('AMOUNT')/100) >= $Amount) {
                if ($CheckTicket[0]->payment==0) {
                    // $payment->capture(array('amount' => ($Amount * 100), 'currency' => 'INR'));
                    $this->Coin_plan_model->UpdateOrderPayment($CheckTicket[0]->razor_payment_id);
                    $this->Users_model->UpdateWalletOrder($CheckTicket[0]->coin, $CheckTicket[0]->user_id);


                    for ($i=1; $i <= 3; $i++) {
                        if ($user[0]->referred_by!=0) {
                            $level = 'level_'.$i;
                            $coins = (($CheckTicket[0]->coin*$setting->$level)/100);
                            $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by);

                            $log_data = [
                                'user_id' => $user[0]->referred_by,
                                'purchase_id' => $order_id,
                                'purchase_user_id' => $user_id,
                                'coin' => $coins,
                                'level' => $i,
                            ];

                            $this->Users_model->AddPurchaseReferLog($log_data);
                            $user = $this->Users_model->UserProfile($user[0]->referred_by);
                        } else {
                            break;
                        }
                    }
                }

                echo '<h1 style="color:green">Payment Successfull</h1>';
                // $data['message'] = 'Success';
                // $data['code'] = HTTP_OK;
                // $this->response($data, 200);
                // exit();
            } else {
                echo '<h1 style="color:red">Amount Not Matched</h1>';
                // $data['message'] = 'Amount Not Matched';
                // $data['code'] = HTTP_NOT_FOUND;
                // $this->response($data, 200);
                // exit();
            }
        } else {
            echo '<h1 style="color:red">'.$this->input->post('RESPONSE_MESSAGE').'</h1>';
            // $data['message'] = $this->input->post('RESPONSE_MESSAGE');
            // $data['status'] = $this->input->post('PG_TXN_MESSAGE');
            // $data['code'] = HTTP_NOT_FOUND;
            // $this->response($data, 200);
            // exit();
        }
    }

}
