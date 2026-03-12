<?php

use Restserver\Libraries\REST_Controller;
use Razorpay\Api\Api;
use paytm\paytmchecksum\PaytmChecksum;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Plan extends REST_Controller
{
    private $data;

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

        $this->data = $this->input->post();

        $this->load->model('Users_model');
        $this->load->model('Coin_plan_model');
        $this->load->model('Setting_model');
        $this->load->model('Gift_model');
    }

    public function index_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $PlanDetails = $this->Coin_plan_model->List();
        if ($PlanDetails) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Success';
            $data['PlanDetails']=$PlanDetails;
            $this->response($data, 200);
        } else {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Somthing Happend, try again later..';
            $this->response($data, 200);
        }
    }

    public function gift_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $Gift = $this->Gift_model->List();
        if ($Gift) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Success';
            $data['Gift']=$Gift;
            $this->response($data, 200);
        } else {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Somthing Happend, try again later..';
            $this->response($data, 200);
        }
    }

    public function addcash_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $ss_image='';
        $price = $this->input->post('price');
        $type = $this->input->post('type');
        // if($price<100){
        //     $data['code'] = HTTP_NOT_FOUND;
        //     $data['message'] = 'You can not deposit less than 100';
        //     $this->response($data, 200);
        // }
        if (!empty($this->data['ss_image'])) {
            $img = $this->data['ss_image'];
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $ss_image = uniqid().'.jpg';
            $file = './data/post/'.$ss_image;
            file_put_contents($file, $img_data);
        }

        $Utr = $this->Gift_model->Addcash($ss_image);
        if ($Utr) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Thank you Request Submitted';
            $data['Utr']=$Utr;
            $this->response($data, 200);
        } 
        else 
        {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Somthing Happend, try again later..';
            $this->response($data, 200);
        }
    }

    public function puchaseFromAgent_post()
    {
        $this->load->model('Agent_model');
        if (empty($this->data['token']) || empty($this->data['agent_id']) || empty($this->data['user_id']) || empty($this->data['coins'])) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $AgentData=$this->Agent_model->UserAgentProfile($this->data['agent_id']);
        if (empty($AgentData)) {
            $data['message'] = 'Agent Not Found.';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        if($this->data['coins']<100){
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'You can not deposit less than 100 coins';
            $this->response($data, 200);
        }
        $ss_image='';
        if (!empty($this->data['ss_image'])) {
            $img = $this->data['ss_image'];
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $ss_image = uniqid().'.jpg';
            $file = './data/post/'.$ss_image;
            file_put_contents($file, $img_data);
        }
        $request_data = [
            'agent_id' =>$this->data['agent_id'],
            'price' => round(($this->data['coins']/100)*$AgentData[0]->agent_deposite_rate),
            'user_id' => $this->input->post('user_id'),
            'transaction_type' => 1,
            'utr' => $this->input->post('utr'),
            'photo' => $ss_image,
            'coin' =>$this->data['coins'],
            'added_date' =>date('Y-m-d H:i:s'),
            
        ];
        $Utr = $this->Gift_model->AddRqeuestForAgent($request_data);
        if ($Utr) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Success';
            $data['Utr']=$Utr;
            $this->response($data, 200);
        } 
        else 
        {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Something went wrong, try again later..';
            $this->response($data, 200);
        }
    }


    public function paytm_token_api_Post()
    {
        $user_id = $this->input->post('user_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (empty($this->Users_model->UserProfile($user_id))) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);
        $Order_ID_paytm = $Order_ID;
        $paytm_body['orderId'] = $Order_ID_paytm;
        $paytm_body['websiteName'] = str_replace(' ', '', PROJECT_NAME);
        $paytm_body['amount'] = number_format($Amount, 2, '.', '');
        $paytm_body['currency'] = 'INR';
        $paytm_body['custId'] = $user_id;
        $paytm_body['callbackUrl'] = 'https://securegw.paytm.in/theia/paytmCallback?ORDER_ID='.$Order_ID;
        $paytm_body['requestType'] = 'Payment';

        $paytm_token = $this->paytm_token($paytm_body);

        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $paytm_token);


        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID_paytm;
            $data['Total_Amount'] = $Amount;
            $data['paytm_token'] = $paytm_token;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function paytm_token($data)
    {
        $setting = $this->Setting_model->Setting();
        $paytmParams = array();

        $paytm_url = ($setting->cashfree_stage=='PROD') ? PAYTM_LIVE_URL : PAYTM_TEST_URL;

        $paytmParams["body"] = array(
            "requestType" => $data['requestType'],
            "mid"         => $setting->paytm_mercent_id,
            "websiteName"   => "DEFAULT",
            "orderId"       => $data['orderId'],
            "callbackUrl"   => $paytm_url.'/theia/paytmCallback?ORDER_ID='.$data['orderId'],
            "txnAmount"     => array(
                "value"     => $data['amount'],
                "currency"  => $data['currency'],
            ),
            "userInfo"      => array(
                "custId"    => $data['custId'],
            ),
        );

        /*
        * Generate checksum by parameters we have in body
        * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
        */
        // print_r(json_encode($paytmParams));
        // echo $setting->paytm_mercent_key;
        $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $setting->paytm_mercent_key);

        $paytmParams["head"] = array(
            "signature" => $checksum
        );

        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        // $paytm_url = ($setting->cashfree_stage=='PROD')?PAYTM_LIVE_URL:PAYTM_TEST_URL;
        // $paytm_url = PAYTM_TEST_URL;
        $url = $paytm_url."/theia/api/v1/initiateTransaction?mid=$setting->paytm_mercent_id&orderId=".$data['orderId'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $response = curl_exec($ch);
        $reponse_arr = json_decode($response);
        // print_r($reponse_arr->body->resultInfo);
        return (isset($reponse_arr->body->txnToken)) ? $reponse_arr->body->txnToken : $reponse_arr->body->resultInfo->resultMsg;
    }

    public function paytm_pay_now_api_post()
    {
        $user_id = $this->input->post('user_id');
        $order_id = $this->input->post('order_id');

        if (empty($user_id) || empty($order_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
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
            $data['message'] = 'Invalid Ticket ID';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $setting = $this->Setting_model->Setting();
        /* initialize an array */
        $paytmParams = array();
        /* body parameters */
        $paytmParams["body"] = array(
            "mid" => $setting->paytm_mercent_id,
            "orderId" => $order_id,
        );
        /**
        * Generate checksum by parameters we have in body
        * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
        */
        $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $setting->paytm_mercent_key);

        /* head parameters */
        $paytmParams["head"] = array(
            /* put generated checksum value here */
            "signature"	=> $checksum
        );

        /* prepare JSON string for request */
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        /* for Staging */
        $paytm_url = ($setting->cashfree_stage=='PROD') ? PAYTM_LIVE_URL : PAYTM_TEST_URL;
        $url = $paytm_url."/v3/order/status";

        /* for Production */
        // $url = "https://securegw.paytm.in/v3/order/status";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $response_arr = json_decode($response);

        if ($response_arr->body->resultInfo->resultStatus!='TXN_SUCCESS') {
            // Reject this call
            $data['message'] = $response_arr->body->resultInfo->resultMsg;
            $data['code'] = HTTP_UNAUTHORIZED;
            $this->response($data, 200);
            exit();
        }

        $Amount = $CheckTicket[0]->price;

        if ($CheckTicket[0]->payment==0 && $response_arr->body->txnAmount == $Amount) {
            $this->Coin_plan_model->UpdateOrderPayment($CheckTicket[0]->razor_payment_id);
            $this->Users_model->UpdateWalletOrder($CheckTicket[0]->coin, $CheckTicket[0]->user_id, bonus: 0);


            for ($i=1; $i <= 3; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($CheckTicket[0]->coin*$setting->$level)/100);
                    $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

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

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Invalid Payment';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, 200);
            exit();
        }
    }

    public function cashfree_token_api_Post()
    {
        $user_id = $this->input->post('user_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (empty($this->Users_model->UserProfile($user_id))) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);
        $cashfree_token = $this->cashkaro_token($Order_ID, $Amount);
        // print_r($cashfree_token->status);
        if ($cashfree_token->status=='OK') {
            $cftoken = $cashfree_token->cftoken;
        } else {
            $cftoken = $cashfree_token->message;
        }


        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $cftoken);


        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $Amount;
            $data['cftoken'] = $cftoken;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function cashkaro_token($order_id, $amount)
    {
        $setting = $this->Setting_model->Setting();
        $url = ($setting->cashfree_stage=='PROD') ? CLIENT_LIVE_URL : CLIENT_TEST_URL;
        // print_r($setting);
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url.'/api/v2/cftoken/order',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
        "orderId": '.$order_id.',
        "orderAmount":'.$amount.',
        "orderCurrency": "INR"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'x-client-id: '.$setting->cashfree_client_id,
            'x-client-secret: '.$setting->cashfree_client_secret
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function cashfree_pay_now_api_Post()
    {
        $user_id = $this->input->post('user_id');
        $order_id = $this->input->post('order_id');

        if (empty($user_id) || empty($order_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
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
            $data['message'] = 'Invalid Ticket ID';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $setting = $this->Setting_model->Setting();

        $orderAmount = $_POST["orderAmount"];
        $referenceId = $_POST["referenceId"];
        $txStatus = $_POST["txStatus"];
        $paymentMode = $_POST["paymentMode"];
        $txMsg = $_POST["txMsg"];
        $txTime = $_POST["txTime"];
        $signature = $_POST["signature"];
        $cashfree_data = $order_id.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
        $hash_hmac = hash_hmac('sha256', $cashfree_data, $setting->cashfree_client_secret, true) ;
        $computedSignature = base64_encode($hash_hmac);
        if ($signature != $computedSignature) {
            // Reject this call
            $data['message'] = 'Invalid Payment Id';
            $data['code'] = HTTP_UNAUTHORIZED;
            $this->response($data, 200);
            exit();
        }

        $Amount = $CheckTicket[0]->price;

        if ($CheckTicket[0]->payment==0 && $orderAmount == $Amount) {
            $this->Coin_plan_model->UpdateOrderPayment($CheckTicket[0]->razor_payment_id);
            $this->Users_model->UpdateWalletOrder($CheckTicket[0]->coin, $CheckTicket[0]->user_id, bonus: 0);


            for ($i=1; $i <= 3; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($CheckTicket[0]->coin*$setting->$level)/100);
                    $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

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

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Invalid Payment';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, 200);
            exit();
        }
    }

    public function payumoney_token_api_Post()
    {
        $user_id = $this->input->post('user_id');

        // if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_INVALID;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $user_data = $this->Users_model->UserProfile($user_id);
        if (empty($user_data)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);
        $txn_id = uniqid().$Order_ID;
        $paytm_body['orderId'] = $txn_id;
        $paytm_body['plan_id'] = $plan_id;
        $paytm_body['name'] = $user_data[0]->name;
        $paytm_body['email'] = ($user_data[0]->email) ? $user_data[0]->email : 'support@androappstech.com';
        $paytm_body['mobile'] = $user_data[0]->mobile;
        $paytm_body['amount'] = number_format($Amount, 1);

        // $payumoney_token = $this->payumoney_salt($paytm_body);
        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $txn_id);

        if ($Update_Order_Master) {
            $data['order_id'] = $txn_id;
            $data['Total_Amount'] = $Amount;
            // $data['payumoney_token'] = $payumoney_token['hash'];
            // $data['payumoney_string'] = $payumoney_token['string'];
            $data['payumoney_body'] = $paytm_body;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function payumoney_salt_post()
    {
        $setting = $this->Setting_model->Setting();
        $paytmParams = array();

        $hash_data = $this->input->post('hash_data');
        // $product_info = $data['plan_id'];
        // $customer_name = $data['name'];
        // $customer_email = $data['email'];
        // $customer_mobile = $data['mobile'];
        // $customer_address = $data['email'];

        // //payumoney details


        // $MERCHANT_KEY = $setting->payumoney_key; //change  merchant with yours
        $SALT = $setting->payumoney_salt;  //change salt with yours

        // $txnid = $data['orderId'];
        // // $txnid = uniqid().md5($data['orderId']);
        // //optional udf values
        // $udf1 = '';
        // $udf2 = '';
        // $udf3 = '';
        // $udf4 = '';
        // $udf5 = '';

        // $return['string'] = $hashstring = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $SALT;
        // $return['string'] = $hashstring = $MERCHANT_KEY . '|payment_related_details_for_mobile_sdk|'.$customer_email.'|' . $SALT;
        // $return['hash'] = strtolower(hash('sha512', $hashstring));
        // return $return;

        if ($hash_data) {
            $data['payumoney_hash'] = strtolower(hash('sha512', ($hash_data . $SALT)));
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'hash data empty';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function Place_Order_Post()
    {
        $user_id = $this->input->post('user_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (empty($this->Users_model->UserProfile($user_id))) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);


        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $RazorPay_order->id);


        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $Amount;
            $data['RazorPay_ID'] = $RazorPay_order->id;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function Place_Order_upi_Post()
    {
        $user_id = $this->input->post('user_id');
        $extra = $this->input->post('extra');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (empty($this->Users_model->UserProfile($user_id))) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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

        $Order_ID = $this->Coin_plan_model->GetCoin($user_id, $plan_id, $plan->coin, $Amount, $extra);

        if (empty($Order_ID)) {
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);


        // $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $RazorPay_order->id);


        if ($Order_ID) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $Amount;
            // $data['RazorPay_ID'] = $RazorPay_order->id;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function RazorPay_order($Order_ID, $Amount)
    {
        $setting = $this->Setting_model->Setting();
        $api = new Api($setting->razor_api_key, $setting->razor_secret_key);
        $order = $api->order->create(
            array(
                'receipt' => $Order_ID,
                'amount' => ($Amount * 100),
                'payment_capture' => 0,
                'currency' => 'INR'
            )
        );
        return $order;
    }

    public function Pay_Now_post()
    {
        $user_id = $this->input->post('user_id');
        $order_id = $this->input->post('order_id');
        $Payment_ID = $this->input->post('payment_id');

        if (empty($user_id) || empty($order_id)  || empty($Payment_ID)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
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
            $data['message'] = 'Invalid Ticket ID';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $setting = $this->Setting_model->Setting();
        $api = new Api($setting->razor_api_key, $setting->razor_secret_key);
        try {
            $payment = $api->payment->fetch($Payment_ID);
        } catch (\Exception $e) {
            // print_r($e);
            $data['message'] = 'Invalid Payment Id';
            $data['code'] = HTTP_UNAUTHORIZED;
            $this->response($data, 200);
            exit();
        }

        if ($payment) {
            $R_Order_ID = $payment->order_id;

            if ($CheckTicket[0]->razor_payment_id != $R_Order_ID) {
                $data['message'] = 'Invalid Order Data';
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            $Amount = $CheckTicket[0]->price;
            if ($payment->status = 'authorized' && $payment->amount >= $Amount) {
                $payment->capture(array('amount' => ($Amount * 100), 'currency' => 'INR'));
                $this->Coin_plan_model->UpdateOrderPayment($CheckTicket[0]->razor_payment_id, $payment);
                $this->Users_model->UpdateWalletOrder($CheckTicket[0]->coin, $CheckTicket[0]->user_id, bonus: 0);


                for ($i=1; $i <= 3; $i++) {
                    if ($user[0]->referred_by!=0) {
                        $level = 'level_'.$i;
                        $coins = (($CheckTicket[0]->coin*$setting->$level)/100);
                        $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

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

                $data['message'] = 'Success';
                $data['code'] = HTTP_OK;
                $this->response($data, 200);
                exit();
            } else {
                $data['message'] = 'Invalid Payment';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, 200);
                exit();
            }
        }
    }

    public function Place_Order_Neokred_Post()
    {
        $user_id = $this->input->post('user_id');
        $extra = 0;

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');

        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (empty($this->Users_model->UserProfile($user_id))) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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

        $Order_ID = $this->Coin_plan_model->GetCoin($user_id, $plan_id, $plan->coin, $Amount, $extra);

        if (empty($Order_ID)) {
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        $Neokred_order = $this->Neokred_order($Amount);
        // print_r($Neokred_order);
        $Neokred_order_id = $Neokred_order->data->orderId;
        $Neokred_qr = $this->Neokred_qr($Neokred_order_id);
        $Neokred_transaction_id = $Neokred_qr->data->transactionId;
        $Neokred_upi_string = $Neokred_qr->data->upiIntentString;

        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $Neokred_transaction_id);

        if ($Order_ID) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $Amount;
            $data['Neokred_transaction_id'] = $Neokred_transaction_id;
            $data['Neokred_upi_string'] = $Neokred_upi_string;
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function Neokred_order($Amount)
    {
        $setting = $this->Setting_model->Setting();
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://collectbot.neokred.tech/payin/kpy/api/v1/external/upi/qr/create-order',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
        "amount": "'.$Amount.'.00"
        }',
        CURLOPT_HTTPHEADER => array(
            'client_secret: '.$setting->neokred_client_secret,
            'program_id: '.$setting->neokred_project_id,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function Neokred_qr($transaction_id)
    {
        $setting = $this->Setting_model->Setting();
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://collectbot.neokred.tech/payin/kpy/api/v1/external/upi/qr/generate',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "orderId": "'.$transaction_id.'"
          }',
          CURLOPT_HTTPHEADER => array(
            'client_secret: '.$setting->neokred_client_secret,
            'program_id: '.$setting->neokred_project_id,
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function Neokred_status($transaction_id)
    {
        $setting = $this->Setting_model->Setting();
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://collectbot.neokred.tech/payin/kpy/api/v1/external/upi/qr/status',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "transactionId": "'.$transaction_id.'"
            }',
          CURLOPT_HTTPHEADER => array(
            'client_secret: '.$setting->neokred_client_secret,
            'program_id: '.$setting->neokred_project_id,
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function check_status_post()
    {
        $setting = $this->Setting_model->Setting();

        $user_id = $this->input->post('user_id');
        $order_id = $this->input->post('order_id');

        if (empty($user_id) || empty($order_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
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

        $setting = $this->Setting_model->Setting();
        $Neokred_status = $this->Neokred_status($CheckTicket[0]->razor_payment_id);
        // $api = new Api($setting->razor_api_key, $setting->razor_secret_key);
        // try {
        //     $payment = $api->payment->fetch($Payment_ID);
        // } catch (\Exception $e) {
        //     // print_r($e);
        //     $data['message'] = 'Invalid Payment Id';
        //     $data['code'] = HTTP_UNAUTHORIZED;
        //     $this->response($data, 200);
        //     exit();
        // }

        if ($Neokred_status->data->txnStatus=='SUCCESS') {
            // $R_Order_ID = $payment->order_id;

            // if ($CheckTicket[0]->razor_payment_id != $R_Order_ID) {
            //     $data['message'] = 'Invalid Order Data';
            //     $data['code'] = HTTP_NOT_ACCEPTABLE;
            //     $this->response($data, 200);
            //     exit();
            // }

            $Amount = $CheckTicket[0]->price;
            if ($Neokred_status->data->amount >= $Amount) {
                if ($CheckTicket[0]->payment==0) {
                    // $payment->capture(array('amount' => ($Amount * 100), 'currency' => 'INR'));
                    $this->Coin_plan_model->UpdateOrderPayment($CheckTicket[0]->razor_payment_id);
                    $this->Users_model->UpdateWalletOrder($CheckTicket[0]->coin, $CheckTicket[0]->user_id, bonus: 0);


                    for ($i=1; $i <= 3; $i++) {
                        if ($user[0]->referred_by!=0) {
                            $level = 'level_'.$i;
                            $coins = (($CheckTicket[0]->coin*$setting->$level)/100);
                            $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

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

                $data['message'] = 'Success';
                $data['code'] = HTTP_OK;
                $this->response($data, 200);
                exit();
            } else {
                $data['message'] = 'Amount Not Matched';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, 200);
                exit();
            }
        } else {
            $data['message'] = 'Status';
            $data['status'] = $Neokred_status->data->txnStatus;
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, 200);
            exit();
        }
    }

    public function get_qr_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $qr_image = $this->Setting_model->Setting();
        
        if ($qr_image) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Success';
            $data['qr_image']=base_url(QR_IMAGE.$qr_image->qr_image);
            $data['upi_id'] = $qr_image->upi_id;
            $this->response($data, 200);
        } 
        else 
        {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Somthing Happend, try again later..';
            $this->response($data, 200);
        }
    }

    public function get_usdt_qr_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $qr_image = $this->Setting_model->Setting();
        
        if ($qr_image) {
            $data['code'] = HTTP_OK;
            $data['message'] = 'Success';
            $data['qr_image']=base_url(QR_IMAGE.$qr_image->usdt_qr_image);
            $data['usdt_address'] = $qr_image->usdt_address;
            $this->response($data, 200);
        } 
        else 
        {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Somthing Happend, try again later..';
            $this->response($data, 200);
        }
    }

    public function Place_Order_NowPayment_post()
    {
        $user_id = $this->input->post('user_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $amount = $this->input->post('amount');
        $coin = $this->input->post('coin');
        $currency = !empty($this->input->post('currency'))?$this->input->post('currency'):'usdt';

        if (empty($user_id) || empty($amount) || empty($coin)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }
       $user= $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $plan = $this->Coin_plan_model->View($plan_id);
        // if (empty($plan)) {
        //     $data['message'] = 'Invalid Plan';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $Amount = $amount;             //Product Amount While the Time OF Order

        $Order_ID = $this->Coin_plan_model->GetCoin($user_id, 0, $coin, $Amount,0,2);

        if (empty($Order_ID)) {
            $data['message'] = 'Error while Creating Ticket';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);

        // $clientRefId = "PWR".rand(100000,999999).time();
        $amount = $Amount;
       $order_id = $Order_ID;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.nowpayments.io/v1/payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "price_amount":'.$amount.',
            "price_currency": "usd",
            "pay_currency":"usdtbsc",
            "ipn_callback_url": "'.base_url('api/Callback/nowpaymentpayout').'",
            "order_id":'.$order_id.',
            "order_description": "Apple Macbook Pro 2019 x 1"
          }',
            CURLOPT_HTTPHEADER => array(
              'x-api-key:'.$_ENV["PAYMENTAPI_KEY"],
              'Content-Type: application/json'
            ),
          ));
          $response = curl_exec($curl);
        curl_close($curl);
        $response_arr = json_decode($response,true);
        if(empty($response_arr['status']) && !empty($response_arr['statusCode']) && $response_arr['statusCode']==400){
            $data['message'] = $response_arr['message'];
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        if($response_arr['pay_address']==''){
            $data['message'] = 'Invalid';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
      

        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $response_arr['payment_id']);

        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $Amount;
            $data['payment_id'] = $response_arr['payment_id'];
            $data['pay_address'] = $response_arr['pay_address'];
            $data['pay_amount'] = round(1+($response_arr['pay_amount']),2);
            $data['coin_type']='USDT BSC';
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }
    public function getCurrencyAvailable_post()
    {
        $user_id = $this->input->post('user_id');
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $plan_id = $this->input->post('plan_id');
        if (empty($user_id) || empty($plan_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }
       $user= $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
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

    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => 'https://api.nowpayments.io/v1/currencies?fixed_rate=true',
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => array(
    //           'x-api-key:'.$_ENV["PAYMENTAPI_KEY"],
    //           'Content-Type: application/json'
    //         ),
    //       ));
    //    $response = curl_exec($curl);
    //      curl_close($curl);
    //     $response_arr = json_decode($response,true);
        if($plan){
            $data['amount'] = $Amount;
            $data['user_name'] = $user[0]->name;
            $data['code'] = 200;
            $data['message'] = 'success';
            $data['currencies'] = ['USDT BEP20'];
            $this->response($data, 200);
        }else{
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
       
    }

  

    public function Place_Order_Upi_Gateway_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        $plan_id = $this->input->post('plan_id');
  
        if (empty($user_id) ) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $setting = $this->Setting_model->Setting();

       $user= $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
  
        $plan = $this->Coin_plan_model->View($plan_id);
        // if (empty($plan)) {
        //     $data['message'] = 'Invalid Plan';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $coins = empty($plan)?$amount:$plan->coin;
            //Product Amount While the Time OF Order
          //   $Order_ID = $this->Coin_plan_model->GetCoin($user_id,0,0, $amount);
          $Order_ID = $this->Coin_plan_model->GetCoin($user_id, $plan_id, $coins, $amount,0,0);
  
  
      //   $Order_ID = $this->Users_model->GetCoin($user_id,0,0,$amount);
        if (empty($Order_ID)) {
            $data['message'] = 'Error while generating id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);
  
        $curl = curl_init();
  
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.ekqr.in/api/create_order',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "key":"'.$setting->upi_gateway_api_key.'",
          "client_txn_id":"'.$Order_ID.'",
          "amount":"'.$amount.'",
          "p_info": "'.PROJECT_NAME.'",
          "customer_name": "'.$user[0]->name.'",
          "customer_email": "support@gmail.com",
          "customer_mobile": "'.$user[0]->mobile.'",
          "redirect_url": "'.base_url().'",
          "udf1": "user defined field 1",
          "udf2": "user defined field 2",
          "udf3": "user defined field 3"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
       
         $response = curl_exec($curl);
        curl_close($curl);
        $response_arr = json_decode($response,true);
        if(!$response_arr['status']){
            $data['message'] = $response_arr['msg'];
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
  
        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $response_arr['data']['order_id']);
        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $amount;
            $data['txnId'] = $response_arr['data']['order_id'];
            $data['clientRefId'] = '';
            $data['intentData'] = $response_arr['data']['payment_url'];
            // $data['upi_id'] = $response_arr['data']['VPA'];
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function Place_Order_Gamora_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        $plan_id = $this->input->post('plan_id');
  
        if (empty($user_id) ) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $setting = $this->Setting_model->Setting();

       $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
  
        $plan = $this->Coin_plan_model->View($plan_id);
        // if (empty($plan)) {
        //     $data['message'] = 'Invalid Plan';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $coins = empty($plan)?$amount:$plan->coin;
            //Product Amount While the Time OF Order
          //   $Order_ID = $this->Coin_plan_model->GetCoin($user_id,0,0, $amount);
        $Order_ID = $this->Coin_plan_model->GetCoin($user_id, $plan_id, $coins, $amount,0,3);//transaction_type == 3 for getting only payformee transactions 
  
  
      //   $Order_ID = $this->Users_model->GetCoin($user_id,0,0,$amount);
        if (empty($Order_ID)) {
            $data['message'] = 'Error while generating id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // create ORder in razor pay
        // $RazorPay_order = $this->RazorPay_order($Order_ID, $Amount);
  
        $api_url = 'https://Payformee.com/api/create-order';

// Form-encoded payload data
        $post_data = [
            'customer_mobile' => $user[0]->mobile,
            'user_token' => 'bedfad31f39edc90bc5e60c7c2550749',
            'amount' => $amount,
            // 'order_id' => 'RWANDER'.$Order_ID,
            'order_id' => $Order_ID,
            'redirect_url' => 'https://rummywander.com',
            'remark1' => '',
            'remark2' => '',
            'route' => '2' // route 2 is for VIP users, route 1 is for normal users
        ];

        // Initialize cURL session
        $ch = curl_init($api_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // to format POST data
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        // Execute the cURL session and capture the response
        $response = curl_exec($ch);
        // echo 'hi';
        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }

        // Close the cURL session
        curl_close($ch);
        $response_arr = json_decode($response,true);
        if(!$response_arr['status']){
            $data['message'] = $response_arr['message'];
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
  
        $Update_Order_Master = $this->Coin_plan_model->UpdateOrder($user_id, $Order_ID, $response_arr['result']['orderId']);
        if ($Update_Order_Master) {
            $data['order_id'] = $Order_ID;
            $data['Total_Amount'] = $amount;
            $data['txnId'] = $response_arr['result']['orderId'];
            $data['clientRefId'] = '';
            $data['intentData'] = $response_arr['result']['payment_url'];
            // $data['upi_id'] = $response_arr['data']['VPA'];
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = 'Technical Error';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

}
