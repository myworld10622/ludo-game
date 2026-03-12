<?php

class Setting extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting_model');
    }

    public function appConfiguration()
    {
        $data = [
            'title' => 'Setting',
            'Setting' => $this->Setting_model->Setting(),
            // 'Permission' => $this->Setting_model->GetPermission()
        ];
        template('setting/app_configuration', $data);
    }
    public function systemConfiguration()
    {
        $data = [
            'title' => 'Setting',
            'Setting' => $this->Setting_model->Setting(),
            // 'Permission' => $this->Setting_model->GetPermission()
        ];
        template('setting/system_configuration', $data);
    }

    public function gamePermissions()
    {
        $data = [
            'title' => 'Permissions',
            'Permission' => $this->Setting_model->GetPermission()
        ];
        template('setting/game_permissions', $data);
    }

    public function update()
    {
        $form_type = $this->input->post('form_type');

        $referral_amount = $this->input->post('referral_amount');
        $mobile = $this->input->post('mobile');
        $level_1 = $this->input->post('level_1');
        $level_2 = $this->input->post('level_2');
        $level_3 = $this->input->post('level_3');
        $level_4 = $this->input->post('level_4');
        $level_5 = $this->input->post('level_5');
        $level_6 = $this->input->post('level_6');
        $level_7 = $this->input->post('level_7');
        $level_8 = $this->input->post('level_8');
        $level_9 = $this->input->post('level_9');
        $level_10 = $this->input->post('level_10');
        $referral_id = $this->input->post('referral_id');
        $referral_link = $this->input->post('referral_link');
        $contact_us = $this->input->post('contact_us');
        $about_us = $this->input->post('about_us');
        $refund_policy = $this->input->post('refund_policy');
        $terms = $this->input->post('terms');
        $privacy_policy = $this->input->post('privacy_policy');
        $help_support = $this->input->post('help_support');
        $default_otp = $this->input->post('default_otp');
        $game_for_private = $this->input->post('game_for_private');
        $app_version = $this->input->post('app_version');
        $update_url = $this->input->post('update_url');
        $joining_amount = $this->input->post('joining_amount');
        $min_withdrawal = $this->input->post('min_withdrawal');
        $admin_commission = $this->input->post('admin_commission');
        $whats_no = $this->input->post('whats_no');
        $upi_merchant_id = $this->input->post('upi_merchant_id');
        $upi_secret_key = $this->input->post('upi_secret_key');
        $bonus = $this->input->post('bonus');
        $bonus_amount = $this->input->post('bonus_amount');
        $payment_gateway = $this->input->post('payment_gateway');
        $symbol = $this->input->post('symbol');
        $razor_api_key = $this->input->post('razor_api_key');
        $payumoney_key = $this->input->post('payumoney_key');
        $payumoney_salt = $this->input->post('payumoney_salt');
        $razor_secret_key = $this->input->post('razor_secret_key');
        $cashfree_client_id = $this->input->post('cashfree_client_id');
        $cashfree_client_secret = $this->input->post('cashfree_client_secret');
        $cashfree_stage = $this->input->post('cashfree_stage');
        $paytm_mercent_id = $this->input->post('paytm_mercent_id');
        $paytm_mercent_key = $this->input->post('paytm_mercent_key');
        $share_text = $this->input->post('share_text');
        $bank_detail_field = $this->input->post('bank_detail_field');
        $adhar_card_field = $this->input->post('adhar_card_field');
        $upi_field = $this->input->post('upi_field');
        $app_message = $this->input->post('app_message');
        $upi_id = $this->input->post('upi_id');
        $usdt_address = $this->input->post('usdt_address');
        $neokred_client_secret = $this->input->post('neokred_client_secret');
        $neokred_project_id = $this->input->post('neokred_project_id');
        $upi_gateway_key = $this->input->post('upi_gateway_key');
        $daily_rebate_income = $this->input->post('daily_rebate_income');
        $project_name = $this->input->post('project_name');
        $company_name = $this->input->post('company_name');
        $dollar = $this->input->post('dollar');
        $distribute_precent = $this->input->post('distribute_precent');
        $daily_bonus_status = $this->input->post('daily_bonus_status');
        $app_popop_status = $this->input->post('app_popop_status');
        $fcm_server_key = $this->input->post('fcm_server_key');


        if (!empty($_FILES['app_url']['name'])) {
            $app_url = upload_apk($_FILES['app_url'], APP_URL);
        } else {
            $app_url = '';
        }
        if (!empty($_FILES['logo']['name'])) {
            $logo = upload_image($_FILES['logo'], LOGO);
        } else {
            $logo = '';
        }
        if (!empty($_FILES['qr_image']['name'])) {
            $qr_image = upload_image($_FILES['qr_image'], QR_IMAGE);
        } else {
            $qr_image = '';
        }
        if (!empty($_FILES['usdt_qr_image']['name'])) {
            $usdt_qr_image = upload_image($_FILES['usdt_qr_image'], QR_IMAGE);
        } else {
            $usdt_qr_image = '';
        }
        $data = ['updated_date' => date('Y-m-d H:i:s')];

        if (!empty($mobile)) {
            $data['mobile'] = $mobile;
        }
        if ($referral_amount != '') {
            $data['referral_amount'] = $referral_amount;
        }
        if ($level_1 != '') {
            $data['level_1'] = $level_1;
        }
        if ($level_2 != '') {
            $data['level_2'] = $level_2;
        }
        if ($level_3 != '') {
            $data['level_3'] = $level_3;
        }
        if ($level_4 != '') {
            $data['level_4'] = $level_4;
        }
        if ($level_5 != '') {
            $data['level_5'] = $level_5;
        }
        if ($level_6 != '') {
            $data['level_6'] = $level_6;
        }
        if ($level_7 != '') {
            $data['level_7'] = $level_7;
        }
        if ($level_8 != '') {
            $data['level_8'] = $level_8;
        }
        if ($level_9 != '') {
            $data['level_9'] = $level_9;
        }
        if ($level_10 != '') {
            $data['level_10'] = $level_10;
        }
        $data['referral_id'] = $referral_id;
        $data['referral_link'] = $referral_link;
        $data['upi_id'] = $upi_id;
        $data['usdt_address'] = $usdt_address;
        if (!empty($contact_us)) {
            $data['contact_us'] = $contact_us;
        }
        if (!empty($about_us)) {
            $data['about_us'] = $about_us;
        }
        if (!empty($refund_policy)) {
            $data['refund_policy'] = $refund_policy;
        }
        if (!empty($terms)) {
            $data['terms'] = $terms;
        }
        if (!empty($privacy_policy)) {
            $data['privacy_policy'] = $privacy_policy;
        }
        if (!empty($help_support)) {
            $data['help_support'] = $help_support;
        }
        if (!empty($default_otp)) {
            $data['default_otp'] = $default_otp;
        }
        if (!empty($upi_merchant_id)) {
            $data['upi_merchant_id'] = $upi_merchant_id;
        }
        if (!empty($upi_secret_key)) {
            $data['upi_secret_key'] = $upi_secret_key;
        }
        if (!empty($game_for_private)) {
            $data['game_for_private'] = $game_for_private;
        }
        if (!empty($app_version)) {
            $data['app_version'] = $app_version;
        }

        if (!empty($update_url)) {
            $data['update_url'] = $update_url;
        }

        if (!empty($app_url)) {
            $data['app_url'] = $app_url;
        }

        if (!empty($logo)) {
            $data['logo'] = $logo;
        }
        if (!empty($qr_image)) {
            $data['qr_image'] = $qr_image;
        }
        if (!empty($usdt_qr_image)) {
            $data['usdt_qr_image'] = $usdt_qr_image;
        }
        if (!empty($joining_amount)) {
            $data['joining_amount'] = $joining_amount;
        }
        if (!empty($min_withdrawal)) {
            $data['min_withdrawal'] = $min_withdrawal;
        }
        if (!empty($admin_commission)) {
            $data['admin_commission'] = $admin_commission;
        }
        if (!empty($whats_no)) {
            $data['whats_no'] = $whats_no;
        }
        // if (!empty($bonus)) {
        $data['bonus'] = $bonus;

        $data['bonus_amount'] = $bonus_amount;
        // }
        // if (!empty($payment_gateway)) {
        $data['payment_gateway'] = $payment_gateway;
        // }
        // if (!empty($symbol)) {
        $data['symbol'] = $symbol;
        // }
        if (!empty($payumoney_key)) {
            $data['payumoney_key'] = $payumoney_key;
        }
        if (!empty($payumoney_salt)) {
            $data['payumoney_salt'] = $payumoney_salt;
        }
        if (!empty($razor_api_key)) {
            $data['razor_api_key'] = $razor_api_key;
        }
        if (!empty($razor_secret_key)) {
            $data['razor_secret_key'] = $razor_secret_key;
        }
        if (!empty($cashfree_client_id)) {
            $data['cashfree_client_id'] = $cashfree_client_id;
        }
        if (!empty($cashfree_client_secret)) {
            $data['cashfree_client_secret'] = $cashfree_client_secret;
        }
        if (!empty($cashfree_stage)) {
            $data['cashfree_stage'] = $cashfree_stage;
        }
        if (!empty($paytm_mercent_id)) {
            $data['paytm_mercent_id'] = $paytm_mercent_id;
        }
        if (!empty($paytm_mercent_key)) {
            $data['paytm_mercent_key'] = $paytm_mercent_key;
        }
        if (!empty($share_text)) {
            $data['share_text'] = $share_text;
        }
        if (!empty($bank_detail_field)) {
            $data['bank_detail_field'] = $bank_detail_field;
        }
        if (!empty($adhar_card_field)) {
            $data['adhar_card_field'] = $adhar_card_field;
        }
        if (!empty($upi_field)) {
            $data['upi_field'] = $upi_field;
        }
        if (!empty($app_message)) {
            $data['app_message'] = $app_message;
        }
        if (!empty($neokred_client_secret)) {
            $data['neokred_client_secret'] = $neokred_client_secret;
        }
        if (!empty($neokred_project_id)) {
            $data['neokred_project_id'] = $neokred_project_id;
        }
        if (!empty($upi_gateway_key)) {
            $data['upi_gateway_api_key'] = $upi_gateway_key;
        }
        if (!empty($daily_rebate_income)) {
            $data['daily_rebate_income'] = $daily_rebate_income;
        }
        if (!empty($project_name)) {
            $data['copyright_project_name'] = $project_name;
        }
        if (!empty($company_name)) {
            $data['copyright_company_name'] = $company_name;
        }
        if (!empty($dollar)) {
            $data['dollar'] = $dollar;
        }
        if (!empty($distribute_precent)) {
            $data['distribute_precent'] = $distribute_precent;
        }

        if (!empty($daily_bonus_status)) {
            $data['daily_bonus_status'] = $daily_bonus_status;
        }

        if (!empty($app_popop_status)) {
            $data['app_popop_status'] = $app_popop_status;
        }

        if (!empty($fcm_server_key)) {
            $data['fcm_server_key'] = $fcm_server_key;
        }

        $UpdateProduct = $this->Setting_model->update($data);
        if ($UpdateProduct) {
            $this->session->set_flashdata('msg', array('message' => 'Setting Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        // Redirect accordingly
        if ($form_type == 'App') {
            redirect('backend/setting/appConfiguration');
        } else {
            redirect('backend/setting/systemConfiguration');
        }
    }

    public function AdminCoin_log()
    {
        $data = [
            'title' => 'Admin Coin Log',
            'AllTipLog' => $this->Setting_model->AllTipLog(),
            'GetAllLogs' => $this->Setting_model->AllAdminCommisionLog(),
            'GetAllAdminCoin' => $this->Setting_model->getAllAdminCoin(),
        ];
        // echo '<pre>';
        // print_r($data);
        template('setting/AdminCoin_log', $data);
    }

    public function ChangeJackpotStatus()
    {
        $status = $this->input->post('status');

        $Change = $this->Setting_model->update_jackpot_status($status);
        if ($Change) {
            $this->session->set_flashdata('message', array('message' => 'Status Change Successfully', 'class' => 'success'));
        } else {
            $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
        }
        echo 'true';
    }

    public function ChangeRummyBotStatus()
    {
        $status = $this->input->post('status');

        $Change = $this->Setting_model->update_rummy_bot_status($status);
        if ($Change) {
            $this->session->set_flashdata('message', array('message' => 'Status Change Successfully', 'class' => 'success'));
        } else {
            $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
        }
        echo 'true';
    }

    public function ChangeTeenpattiBotStatus()
    {
        $status = $this->input->post('status');

        $Change = $this->Setting_model->update_teenpatti_bot_status($status);
        if ($Change) {
            $this->session->set_flashdata('message', array('message' => 'Status Change Successfully', 'class' => 'success'));
        } else {
            $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
        }
        echo 'true';
    }

    public function ChangeGameStatus()
    {
        $type = $this->input->post('type');
        $Change = false;
        $column = $this->input->post('name');
        switch ($column) {
            case 'teen_patti':
                if (TEENPATTI == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'dragon_tiger':
                if (DRAGON_TIGER == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'andar_bahar':
                if (ANDER_BAHAR == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'point_rummy':
                if (POINT_RUMMY == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'private_rummy':
                if (POINT_RUMMY == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'pool_rummy':
                if (RUMMY_POOL == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'deal_rummy':
                if (RUMMY_DEAL == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'private_table':
                if (TEENPATTI == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'custom_boot':
                if (TEENPATTI == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'seven_up_down':
                if (SEVEN_UP_DOWN == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'car_roulette':
                if (CAR_ROULETTE == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'jackpot_teen_patti':
                if (TEENPATTI == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'animal_roulette':
                if (ANIMAL_ROULETTE == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'color_prediction':
                if (COLOR_PREDICTION == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'color_prediction_vertical':
                if (COLOR_PREDICTION_VERTICAL == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'poker':
                if (POKER == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'head_tails':
                if (HEAD_TAILS == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'red_vs_black':
                if (RED_VS_BLACK == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'ludo_online':
                if (LUDO == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'ludo_local':
                if (LUDO_LOCAL == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'ludo_computer':
                if (LUDO_COMPUTER == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'bacarate':
                if (BACCARAT == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'jhandi_munda':
                if (JHANDI_MUNDA == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            case 'roulette':
                if (ROULETTE == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'aviator':
                if (AVIATOR == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'aviator_vertical':
                if (AVIATOR_VERTICAL == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;

            case 'lottery':
                if (LOTTERY == true) {
                    $Change = $this->Setting_model->UpdateGamesStatus($column, $type);
                }
                break;
            default:
                $Change = false;
                break;
        }
        if ($Change) {
            echo 'true';
        } else {
            echo 'false';
        }
    }

    public function Transfer()
    {
        $setting = $this->Setting_model->Setting();
        if (!empty($setting->aviator_bucket)) {
            $bucket_coin = $setting->aviator_bucket;
            $result = $this->Setting_model->updateAdminCoin($bucket_coin);
            $this->Setting_model->DeductAviatorBucket($bucket_coin);
            direct_admin_profit_statement('Aviator Bucket Transfer ', $bucket_coin, $setting->id);
            if ($result) {
                $this->session->set_flashdata('message', array('message' => 'Transfer Successfully', 'class' => 'success'));
            } else {
                $this->session->set_flashdata('message', array('message' => 'Something went to wrong', 'class' => 'success'));
            }
        } else {
            $this->session->set_flashdata('message', array('message' => 'Bucket is already empty', 'class' => 'success'));
        }

        echo 'true';
    }
    public function add_admin_commission()
    {
        $admin_commission = $this->input->post('admin_commission', true);
        $source = 'admin';

        if (!is_numeric($admin_commission) || $admin_commission <= 0 || empty($source)) {
            $this->session->set_flashdata('error', 'Invalid input data');
            echo json_encode(['status' => false]);
            return;
        }

        $result = direct_admin_profit_statement($source, $admin_commission);
        $this->session->set_flashdata('success', 'Admin commission added successfully!');
        echo json_encode(['status' => true, 'query' => $result]);
    }

    public function deduct_admin_commission()
    {
        $admin_commission = $this->input->post('admin_commission', true);
        $source = 'admin';

        if (!is_numeric($admin_commission) || $admin_commission <= 0 || empty($source)) {
            $this->session->set_flashdata('error', 'Invalid input data');
            echo json_encode(['status' => false]);
            return;
        }

        // Fetch the current admin wallet balance
        // $setting = $this->Setting_model->Setting();
        // $current_wallet = $setting->admin_coin;

        // if ($current_wallet < $admin_commission) {
        //     $this->session->set_flashdata('error', 'Insufficient balance in admin wallet');
        //     echo json_encode(['status' => false]);
        //     return;
        // }

        $admin_commission = -$admin_commission; // Negative value for deduction
        $result = direct_admin_profit_statement($source, $admin_commission);
        $this->session->set_flashdata('error', 'Admin commission deducted successfully!');
        echo json_encode(['status' => true, 'query' => $result]);
    }

}
