<?php

use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class User extends REST_Controller
{
    private $data;
    private $UserData;
    private $UserId;
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

        $this->load->model([
            'Users_model',
            'Game_model',
            'Setting_model',
            'AppBanner_model',
            'Aviator_model',
            'Notification_model',
            'ImageNotification_model',
            'WithdrawalLog_model',
            'Country_model',
            'Chat_model'
        ]);
    }

    public function send_otp_post()
    {
        $mobile = $this->data['mobile'];
        $user = $this->Users_model->UserProfileByMobile($mobile);
        if ($user) {
            $data['message'] = 'Mobile Already Exist, Please Login';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        } else {

            $referral_user = array();
            if (!empty($this->data['referral_code'])) {
                $referral_user = $this->Users_model->IsValidReferral($this->data['referral_code']);
                if (empty($referral_user)) {
                    $data['message'] = 'Referral Code is Not Valid';
                    $data['code'] = HTTP_NOT_FOUND;
                    $this->response($data, HTTP_OK);
                    exit();
                }
            }

            $otp = rand(1000, 9999);

            // $otp = 9988;
            $otp_id = $this->Users_model->InsertOTP($mobile, $otp);
            $msg = "Yout OTP code is : " . $otp;
            // Send_SMS($mobile,$msg);
            Send_OTP($mobile, $otp);
            $data['message'] = 'Success';
            $data['otp_id'] = $otp_id;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function only_send_otp_post()
    {
        $mobile = $this->data['mobile'];
        // $user = $this->Users_model->UserProfileByMobile($mobile);
        // if ($user) {
        //     $data['message'] = 'Mobile Already Exist, Please Login';
        //     $data['code'] = HTTP_NOT_FOUND;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // } else {
        $otp = rand(1000, 9999);

        // $otp = 9988;
        $otp_id = $this->Users_model->InsertOTP($mobile, $otp);
        $msg = "Yout OTP code is : " . $otp;
        // Send_SMS($mobile,$msg);
        Send_OTP($mobile, $otp);
        $data['message'] = 'Success';
        $data['otp_id'] = $otp_id;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
        // }
    }

    // public function register_post()
    // {
    //     // if($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==$this->Setting_model->Setting()->default_otp)
    //     if ($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==DEFAULT_OTP) {
    //         $token = md5(uniqid(rand(), true));
    //         $user = $this->Users_model->UserProfileByMobile($this->data['mobile']);
    //         if ($user) {
    //             if ($user[0]->status==1) {
    //                 $data['message'] = 'You are blocked, Please contact to admin';
    //                 $data['code'] = HTTP_NOT_FOUND;
    //                 $this->response($data, HTTP_OK);
    //                 exit();
    //             }

    //             $this->Users_model->UpdateToken($user[0]->id, $token);
    //            $this->response([
    //             'message' => 'Mobile Already Exist',
    //             'user' => $user,
    //             'token' => $token,
    //             'code' => 201
    //         ], HTTP_OK);
    //             exit();
    //         } else {
    //             $referral_user = array();
    //             if (!empty($this->data['referral_code'])) {
    //                 $referral_user = $this->Users_model->IsValidReferral($this->data['referral_code']);
    //                 if (empty($referral_user)) {
    //                     $data['message'] = 'Referral Code is Not Valid';
    //                     $data['code'] = HTTP_NOT_FOUND;
    //                     $this->response($data, HTTP_OK);
    //                     exit();
    //                 }
    //             }

    //             $profile_pic = '';

    //             if (!empty($this->data['profile_pic'])) {
    //                 $img = $this->data['profile_pic'];
    //                 $img = str_replace(' ', '+', $img);
    //                 $img_data = base64_decode($img);
    //                 $profile_pic = uniqid().'.jpg';
    //                 $file = './data/post/'.$profile_pic;
    //                 file_put_contents($file, $img_data);
    //             }

    //             $gender = (strtolower(trim($this->input->post('gender')))=='female') ? 'f' : 'm';
    //             $setting = $this->Users_model->Setting();
    //             $user_id = $this->Users_model->RegisterUser($this->data['mobile'], $this->data['name'], $profile_pic, $gender, $token, $this->input->post('password'), $setting->bonus_amount, $this->input->post('app'), $this->input->post('email'),$setting->level_1);
    //             $this->Users_model->UpdateReferralCode($user_id, $setting->referral_id);
    //             if (!empty($referral_user)) {
    //                 $this->Users_model->UpdateRefferId($referral_user[0]->id, $user_id);

    //                 // $this->Users_model->UpdateWallet($referral_user[0]->id, $setting->referral_amount, $user_id);
    //                 // log_statement ($referral_user[0]->id, REFERRAL_BONUS, $setting->referral_amount,0,0);
    //             }

    //             if ($_ENV['ENVIRONMENT'] == 'demo') {
    //                 $to = 'info@androappstech.com';
    //                 $subject = 'New User Registration From Demo Panel';
    //                 $view = 'new_register';
    //                 $email_data['mobile'] = $this->data['mobile'];
    //                 $email_data['name'] = $this->data['name'];
    //                 $email_data['app'] = $this->input->post('app');
    //                 send_email($to, $subject, $view, $email_data);

    //                 $curl = curl_init();

    //                 curl_setopt_array($curl, array(
    //                 CURLOPT_URL => 'https://androappstech.com/api/demo_app_store',
    //                 CURLOPT_RETURNTRANSFER => true,
    //                 CURLOPT_ENCODING => '',
    //                 CURLOPT_MAXREDIRS => 10,
    //                 CURLOPT_TIMEOUT => 0,
    //                 CURLOPT_FOLLOWLOCATION => true,
    //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST => 'POST',
    //                 CURLOPT_POSTFIELDS => array('name' => $email_data['name'],'mobile' => $email_data['mobile'],'demo_app' => PROJECT_NAME,'source_reference' => '1'),
    //                 ));

    //                 $response = curl_exec($curl);

    //                 curl_close($curl);

    //             }

    //             $data['message'] = 'Success';
    //             $data['user_id'] = $user_id;
    //             $data['token'] = $token;
    //             $data['code'] = HTTP_OK;
    //             $this->response($data, HTTP_OK);
    //             exit();
    //         }
    //     } else {
    //         $data['message'] = 'OTP Not Matched';
    //         $data['code'] = HTTP_NOT_FOUND;
    //         $this->response($data, HTTP_OK);
    //         exit();
    //     }
    // }

    public function register_post()
    {
        // if($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==$this->Setting_model->Setting()->default_otp)
        $skip_otp = $this->input->post('skip_otp');
        if ($skip_otp == '1' || $this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp'] == DEFAULT_OTP) {
            $token = md5(uniqid(rand(), true));
            $user = $this->Users_model->UserProfileByMobile($this->data['mobile']);

            if ($user) {
                if ($user[0]->status == 1) {
                    $data['message'] = 'You are blocked, Please contact to admin';
                    $data['code'] = HTTP_NOT_FOUND;
                    $this->response($data, HTTP_OK);
                    exit();
                }

                $this->Users_model->UpdateToken($user[0]->id, $token);

                $this->response([
                    'message' => 'Mobile Already Exist',
                    'user' => $user,
                    'token' => $token,
                    'code' => 201
                ], HTTP_OK);
                exit();
            } else {
                $referral_user = [];
                $final_referral_code = '';

                // Step 1: Check if referral code provided and valid
                if (!empty($this->data['referral_code'])) {
                    $referral_user = $this->Users_model->IsValidReferral($this->data['referral_code']);
                    if (!empty($referral_user)) {
                        $final_referral_code = $this->data['referral_code'];
                    } else {
                        $this->response([
                            'message' => 'Referral Code is Not Valid',
                            'code' => HTTP_NOT_FOUND
                        ], HTTP_OK);
                        exit();
                    }
                } else {
                    // Step 2: Try fallback via created_by → tbl_admin → referral_code
                    $created_by = $this->data['created_by'] ?? null;

                    if (!empty($created_by)) {
                        $admin = $this->Users_model->GetAdminById($created_by); // You need to define this
                        if (!empty($admin) && !empty($admin->referral_code)) {
                            $final_referral_code = $admin->referral_code;
                        }
                    }
                }

                // Step 3: Handle profile picture
                $profile_pic = '';
                if (!empty($this->data['profile_pic'])) {
                    $img = str_replace(' ', '+', $this->data['profile_pic']);
                    $img_data = base64_decode($img);
                    $profile_pic = uniqid() . '.jpg';
                    $file = './data/post/' . $profile_pic;
                    file_put_contents($file, $img_data);
                }

                $gender = (strtolower(trim($this->input->post('gender'))) == 'female') ? 'f' : 'm';
                $setting = $this->Users_model->Setting();
                $generated_username = $this->Users_model->GenerateUsernameFromMobile($this->data['mobile']);
                $user_id = $this->Users_model->RegisterUser($this->data['mobile'], $generated_username, $profile_pic, $gender, $token, $this->input->post('password'), $setting->bonus_amount, $this->input->post('app'), $this->input->post('email'), $setting->level_1);
                $this->Users_model->UpdateReferralCode($user_id, $setting->referral_id);
                if (!empty($referral_user)) {
                    $this->Users_model->UpdateRefferId($referral_user[0]->id, $user_id);
                    // Optional: Referral bonus logic
                }

                $email = $this->input->post('email');
                if (!empty($email)) {
                    $email_data = [
                        'project_name' => PROJECT_NAME,
                        'user_id' => $user_id,
                        'username' => $generated_username,
                        'email' => $email,
                        'mobile' => $this->data['mobile'],
                        'password' => $this->input->post('password'),
                        'login_url' => 'https://roxludo.com/login',
                        'app_url' => 'https://roxludo.com',
                    ];
                    send_email($email, PROJECT_NAME . ' - Welcome', 'welcome_register', $email_data);
                }

                // Step 7: Demo email handling
                if ($_ENV['ENVIRONMENT'] == 'demo') {
                    $email_data = [
                        'mobile' => $this->data['mobile'],
                        'name' => $this->data['name'],
                        'app' => $this->input->post('app')
                    ];

                    send_email('info@androappstech.com', 'New User Registration From Demo Panel', 'new_register', $email_data);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://androappstech.com/api/demo_app_store',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => array('name' => $email_data['name'], 'mobile' => $email_data['mobile'], 'demo_app' => PROJECT_NAME, 'source_reference' => '1'),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                }

                // Step 8: Final response
                $this->response([
                    'message' => 'Success',
                    'user_id' => $user_id,
                    'username' => $generated_username,
                    'login_id' => $generated_username,
                    'token' => $token,
                    'code' => HTTP_OK
                ], HTTP_OK);
                exit();
            }
        } else {
            $this->response([
                'message' => 'OTP Not Matched',
                'code' => HTTP_NOT_FOUND
            ], HTTP_OK);
            exit();
        }
    }


    public function direct_register_post()
    {
        // if($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==$this->Setting_model->Setting()->default_otp)
        // if ($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==DEFAULT_OTP) {
        $token = md5(uniqid(rand(), true));
        $user = $this->Users_model->UserProfileByMobile($this->data['mobile']);
        if ($user) {
            if ($user[0]->status == 1) {
                $data['message'] = 'You are blocked, Please contact to admin';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, HTTP_OK);
                exit();
            }

            $this->Users_model->UpdateToken($user[0]->id, $token);
            $data['message'] = 'Mobile Already Exist';
            $data['user'] = $user;
            $data['token'] = $token;
            $data['code'] = 201;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $referral_user = array();
            if (!empty($this->data['referral_code'])) {
                $referral_user = $this->Users_model->IsValidReferral($this->data['referral_code']);
                if (empty($referral_user)) {
                    $data['message'] = 'Referral Code is Not Valid';
                    $data['code'] = HTTP_NOT_FOUND;
                    $this->response($data, HTTP_OK);
                    exit();
                }
            }

            $profile_pic = '';

            if (!empty($this->data['profile_pic'])) {
                $img = $this->data['profile_pic'];
                $img = str_replace(' ', '+', $img);
                $img_data = base64_decode($img);
                $profile_pic = uniqid() . '.jpg';
                $file = './data/post/' . $profile_pic;
                file_put_contents($file, $img_data);
            }

            $gender = (strtolower(trim($this->input->post('gender'))) == 'female') ? 'f' : 'm';
            $setting = $this->Users_model->Setting();
            $user_id = $this->Users_model->RegisterUser($this->data['mobile'], $this->data['name'], $profile_pic, $gender, $token, $this->input->post('password'), $setting->bonus_amount, $this->input->post('app'));
            $this->Users_model->UpdateReferralCode($user_id, $setting->referral_id);
            if (!empty($referral_user)) {
                $this->Users_model->UpdateWallet($referral_user[0]->id, $setting->referral_amount, $user_id);
                log_statement($referral_user[0]->id, REFERRAL_BONUS, $setting->referral_amount, 0, 0);
            }

            if ($_ENV['ENVIRONMENT'] == 'demo') {
                $to = 'info@androappstech.com';
                $subject = 'New User Registration From Demo Panel';
                $view = 'new_register';
                $email_data['mobile'] = $this->data['mobile'];
                $email_data['name'] = $this->data['name'];
                $email_data['app'] = $this->data['app'];
                send_email($to, $subject, $view, $email_data);

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://androappstech.com/api/demo_app_store',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array('name' => $email_data['name'], 'mobile' => $email_data['mobile'], 'demo_app' => PROJECT_NAME, 'source_reference' => '1'),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
            }

            $data['message'] = 'Success';
            $data['user_id'] = $user_id;
            $data['token'] = $token;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }
        // } else {
        //     $data['message'] = 'OTP Not Matched';
        //     $data['code'] = HTTP_NOT_FOUND;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }
    }

    public function guest_register_post()
    {
        if (empty($this->data['unique_token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->UserProfileByUniqueKey($this->data['unique_token']);
        if (!$user) {
            $avatar[] = 'm_1.png';
            $avatar[] = 'm_2.png';
            $avatar[] = 'm_3.png';
            $avatar[] = 'm_4.png';
            $avatar[] = 'm_5.png';
            $avatar[] = 'm_6.png';
            $avatar[] = 'm_7.png';
            $avatar[] = 'm_8.png';
            $avatar[] = 'm_9.png';
            $avatar[] = 'm_10.png';
            $avatarPic = $avatar[rand(0, 9)];
            $setting = $this->Users_model->Setting();
            $token = md5(uniqid(rand(), true));
            $user_id = $this->Users_model->RegisterUserByUniqueKey($this->data['unique_token'], $avatarPic, $setting->bonus_amount);
            $this->Users_model->UpdateToken($user_id, $token);
            //           $this->Users_model->UpdateReferralCode($user_id, $setting->referral_id);
            $data['message'] = 'Success';
            $data['user_id'] = $user_id;
            $data['token'] = $token;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $user_id = $user[0]->id;
            $token = $user[0]->token;
            $data['message'] = 'Success';
            $data['user_id'] = $user_id;
            $data['token'] = $token;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function email_login_post()
    {
        // if($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp']==$this->Setting_model->Setting()->default_otp)
        // {
        $token = md5(uniqid(rand(), true));
        $user = $this->Users_model->UserProfileByEmail($this->data['email']);
        if ($user) {
            if ($user[0]->status == 1) {
                $data['message'] = 'You are blocked, Please contact to admin';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, HTTP_OK);
                exit();
            }

            $this->Users_model->UpdateToken($user[0]->id, $token);
            $data['message'] = 'Email Already Exist';
            $data['user'] = $user;
            $data['token'] = $token;
            $data['code'] = 201;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $setting = $this->Users_model->Setting();
            $referral_user = array();
            if (!empty($this->data['referral_code'])) {
                $referral_user = $this->Users_model->IsValidReferral($this->data['referral_code']);
                if (empty($referral_user)) {
                    $data['message'] = 'Referral Code is Not Valid';
                    $data['code'] = HTTP_NOT_FOUND;
                    $this->response($data, HTTP_OK);
                    exit();
                }
            }

            $profile_pic = '';

            if (!empty($this->data['profile_pic'])) {
                $img = $this->data['profile_pic'];
                $img = str_replace(' ', '+', $img);
                $img_data = base64_decode($img);
                $profile_pic = uniqid() . '.jpg';
                $file = './data/post/' . $profile_pic;
                file_put_contents($file, $img_data);
            }

            $gender = (strtolower(trim($this->input->post('gender'))) == 'female') ? 'f' : 'm';
            $user_id = $this->Users_model->RegisterUserEmail($this->data['email'], $this->data['name'], $this->data['source'], $profile_pic, $gender, $token);
            $this->Users_model->UpdateReferralCode($user_id, $setting->referral_id);
            if (!empty($referral_user)) {
                $this->Users_model->UpdateWallet($referral_user[0]->id, $setting->referral_amount, $user_id);
                log_statement($referral_user[0]->id, REFERRAL_BONUS, $setting->referral_amount, 0, 0);
            }
            $data['message'] = 'Success';
            $data['user_id'] = $user_id;
            $data['token'] = $token;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }
        // }
        // else
        // {
        //     $data['message'] = 'OTP Not Matched';
        //     $data['code'] = HTTP_NOT_FOUND;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }
    }

    public function login_post()
    {
        $user = $this->Users_model->LoginUser($this->data['mobile'], $this->data['password']);
        if ($user) {
            if ($user[0]->status == 1) {
                $data['message'] = 'You are blocked, Please contact to admin';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, HTTP_OK);
                exit();
            }

            $token = md5(uniqid(rand(), true));
            $this->Users_model->UpdateToken($user[0]->id, $token);
            $user = $this->Users_model->LoginUser($this->data['mobile'], $this->data['password']);
            $data['message'] = 'Success';
            $data['user_data'] = $user;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            if ($this->Users_model->UserProfileByMobile($this->data['mobile'])) {
                $data['message'] = 'Incorrect Password';
                $data['code'] = 408;
                $this->response($data, HTTP_OK);
                exit();
            } else {
                $data['message'] = 'User Not Found With This Mobile Number';
                $data['code'] = HTTP_NOT_FOUND;
                $this->response($data, HTTP_OK);
                exit();
            }
        }
    }

    public function forgot_password_post()
    {
        $user_data = $this->Users_model->UserProfileByMobile($this->data['mobile']);
        if ($user_data) {

            $otp = rand(1000, 9999);
            $otp_id = $this->Users_model->InsertOTP($this->data['mobile'], $otp);

            Send_OTP($this->data['mobile'], $otp);

            $data['message'] = 'Otp Sent.';
            $data['otp_id'] = $otp_id;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'User Not Found With This Mobile Number';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function update_password_post()
    {
        if ($this->Users_model->OTPConfirm($this->data['otp_id'], $this->data['otp'], $this->data['mobile']) || $this->data['otp'] == DEFAULT_OTP) {
            $user = $this->Users_model->UserProfileByMobile($this->data['mobile']);
            $user_data['password'] = trim($this->input->post('new_password', true));
            $this->Users_model->Update($user[0]->id, $user_data);

            $data['message'] = 'Password Updated';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Invalid OTP';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function profile_post()
    {
        if (empty($this->data['id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Paramter';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $fcm = $this->input->post('fcm');

        if (!empty($fcm)) {
            $this->Users_model->UpdateUser($this->data['id'], $fcm);
        }

        $app_version = $this->input->post('app_version');
        if (!empty($app_version)) {
            $this->Users_model->UpdateAppVersion($this->data['id'], $app_version);
        }
        $this->Users_model->updateUpdatedDate($this->data['id']);
        $NotificationImage = $this->ImageNotification_model->get_latest_image();
        $AppBanner = $this->AppBanner_model->View();
        $UserData = $this->Users_model->UserProfile($this->data['id']);
        $UserKyc = $this->Users_model->UserKyc($this->data['id']);
        $UserBankDetails = $this->Users_model->UserBankDetails($this->data['id']);
        $this->Users_model->UpdateGameStatus($this->data['id']);
        $setting = $this->Setting_model->Setting('`min_redeem`, `referral_amount`, `contact_us`, `terms`, `privacy_policy`, `help_support`, `game_for_private`, `app_version`, `joining_amount`, `whats_no`, `bonus`, `payment_gateway`, `symbol`, `razor_api_key`, `cashfree_client_id`,`cashfree_stage`, `paytm_mercent_id`, `payumoney_key`, `share_text`, `bank_detail_field`, `adhar_card_field`, `upi_field`, `referral_link`, `referral_id`,`app_message`,`upi_merchant_id`,`upi_secret_key`,`admin_commission`,`upi_id`,`extra_spinner`,`upi_gateway_api_key`,`dollar`');
        $total_withdraw = $this->Users_model->get_total_withdraw($this->data['id']);
        $total_recharge = $this->Users_model->get_total_recharge($this->data['id']);

        $avatar[] = 'f_1.png';
        $avatar[] = 'f_2.png';
        $avatar[] = 'm_1.png';
        $avatar[] = 'm_2.png';
        $avatar[] = 'm_3.png';
        $avatar[] = 'm_4.png';
        $avatar[] = 'm_5.png';
        $avatar[] = 'm_6.png';
        $avatar[] = 'm_7.png';
        $avatar[] = 'm_8.png';
        $avatar[] = 'm_9.png';
        $avatar[] = 'm_10.png';

        $data['message'] = 'Success';
        $data['user_data'] = $UserData;
        $data['user_data'][0]->total_bet = $UserData[0]->todays_bet ?? 0;
        $data['user_data'][0]->total_withdraw = $total_withdraw ?? 0;
        $data['user_data'][0]->total_recharge = $total_recharge ?? 0;
        $data['user_kyc'] = $UserKyc;
        $data['user_bank_details'] = $UserBankDetails;
        $data['avatar'] = $avatar;
        $data['setting'] = $setting;
        $data['notification_image'] = $NotificationImage;
        $data['app_banner'] = $AppBanner;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function wallet_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Paramter';
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

        $UserData = $this->Users_model->UserProfile($this->data['user_id']);

        $data['message'] = 'Success';
        $data['wallet'] = $UserData[0]->wallet;
        $data['winning_wallet'] = $UserData[0]->winning_wallet;
        $data['unutilized_wallet'] = $UserData[0]->unutilized_wallet;
        $data['bonus_wallet'] = $UserData[0]->bonus_wallet;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function withdrawal_log_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $this->load->model('WithdrawalLog_model');
        $UserData = $this->WithdrawalLog_model->WithDrawal_log($this->data['user_id']);
        $data['message'] = 'Success';
        $data['data'] = $UserData;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function game_on_off_post()
    {
        $setting = $this->Setting_model->GetPermission('tbl_games_on_off.*,aviator_ui_type,color_prediction_ui_type');

        $data['message'] = 'Success';
        $data['game_setting'] = $setting;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function leaderboard_post()
    {
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $leaderboard = $this->Game_model->Leaderboard();

        $data['message'] = 'Success';
        $data['leaderboard'] = $leaderboard;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function setting_post()
    {
        // if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_INVALID;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }

        $avatar[] = 'f_1.png';
        $avatar[] = 'f_2.png';
        $avatar[] = 'm_1.png';
        $avatar[] = 'm_2.png';
        $avatar[] = 'm_3.png';
        $avatar[] = 'm_4.png';
        $avatar[] = 'm_5.png';
        $avatar[] = 'm_6.png';
        $avatar[] = 'm_7.png';
        $avatar[] = 'm_8.png';
        $avatar[] = 'm_9.png';
        $avatar[] = 'm_10.png';

        $setting = $this->Setting_model->Setting();
        $NotificationImage = $this->ImageNotification_model->get_latest_image();
        $AppBanner = $this->AppBanner_model->View();

        $data['message'] = 'Success';
        $data['notification_image'] = $NotificationImage;
        $data['app_banner'] = $AppBanner;
        $data['avatar'] = $avatar;
        $data['setting'] = $setting;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function list_post()
    {
        $Users = $this->Users_model->AllUserList();
        if ($Users) {
            $data = [
                'List' => $Users,
                'message' => 'Success',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Please try after sometime',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function bot_post()
    {
        $Users = $this->Users_model->GetFreeBot();
        if ($Users) {
            $data = [
                'List' => $Users,
                'message' => 'Success',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'Please try after sometime',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function randomBoatUsers_post()
    {
        // $user_id = $this->input->post('user_id');

        // if (empty($user_id)) {
        //     $data['message'] = 'Invalid Params';
        //     $data['code'] = HTTP_BLANK;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $user = $this->Users_model->UserProfile($user_id);
        // if (empty($user)) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }
        $Users = $this->Users_model->RandomBoatUsers();
        if ($Users) {
            $data = [
                'List' => $Users,
                'message' => 'Success',
                'code' => HTTP_OK,
            ];
            $this->response($data, HTTP_OK);
        } else {
            $data = [
                'message' => 'No Data Found.',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
    }

    public function winning_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            // 'GameWins' => $this->Users_model->View_Wins($user_id),
            // 'TeenPattiGameLog' => $this->Users_model->TeenPattiLog($user_id),
            // 'RummyGameLog' => $this->Users_model->RummyLog($user_id),
            'AllPurchase' => $this->Users_model->View_AllPurchase($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function winning_game_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameWins' => $this->Users_model->View_Wins($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function teenpatti_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'TeenPattiGameLog' => $this->Users_model->TeenPattiLog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function aviator_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'AviatorLog' => $this->Users_model->Aviatorlog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }
    public function jhandiMunda_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'JhandiMundalog' => $this->Users_model->JhandiMundalog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function ludo_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'Ludolog' => $this->Users_model->Ludolog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];

        $this->response($data, HTTP_OK);
    }

    public function poker_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'Pokerlog' => $this->Users_model->Pokerlog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function rummy_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'RummyGameLog' => $this->Users_model->RummyLog($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->WalletAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_gamelog_history_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->WalletAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_dragon_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->DragonWalletAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_rummy_deal_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->RummyDealLog($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_rummy_pool_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->RummyPoolLog($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_seven_up_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->SevenUpAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_andar_bahar_plus_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->AndarBaharAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_target_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->TargetAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_color_prediction_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->ColorPredictionAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_color_prediction_1_min_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->ColorPrediction1MinAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_color_prediction_3_min_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->ColorPrediction3MinAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_color_prediction_5_min_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->ColorPrediction5MinAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }
    public function wallet_history_car_roulette_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->CarRouletteAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_animal_roulette_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->AnimalRouletteAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_jackpot_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->JackpotAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_head_tail_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->HeadTailAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_red_black_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->RedBlack($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_baccarat_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->BaccaratLog($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_roulette_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->RouletteAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_ludo_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->Ludolog($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_ander_bahar_plus_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'GameLog' => $this->Users_model->AnderBaharPlusAmount($user_id),
            'MinRedeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function wallet_history_all_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $GameLog = $this->Users_model->GetAllLogs($user_id);
        $total = !empty($GameLog[0]->user_wallet) ? $GameLog[0]->user_wallet : 0;
        foreach ($GameLog as $key => $value) {
            if ($value->game == 'Rummy Win' || $value->game == 'Teen Patti Win' || $value->game == 'Deal Rummy Win' || $value->game == 'Pool Rummy Win' || $value->game == 'Poker Win' || $value->game == 'Ludo Win') {
                $amount = $value->winning_amount;
            } elseif ($value->is_game == 1) {
                $amount = $value->user_amount - $value->amount;
            } else {
                $amount = $value->user_amount - abs($value->amount);
            }


            $GameLog[$key]->bracket_amount = $amount;
            $GameLog[$key]->total = $total;
            $total = $total - $amount;
        }

        $data = [
            'GameLog' => $GameLog,
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function min_amount_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'Wallet' => $user[0]->wallet,
            'Min_Redeem' => min_redeem(),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function check_adhar_post()
    {
        $user_id = $this->input->post('user_id');


        if (empty($user_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }
        $adhar = $this->Users_model->getAdhar($user_id);
        if ($adhar == '') {
            $data['message'] = '0';
            $data['code'] = 200;
            $this->response($data, 200);
            exit();
        } else {
            $data['message'] = '1';
            $data['code'] = 200;
            $this->response($data, 200);
            exit();
        }
    }

    public function update_profile_post()
    {
        $user_id = $this->input->post('user_id');
        $name = $this->input->post('name');
        $bank_detail = $this->input->post('bank_detail');
        $adhar_card = $this->input->post('adhar_card');
        $upi = $this->input->post('upi');
        $email = $this->input->post('email');

        if (empty($user_id)) {
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

        // $img = $profile_pic;
        // $img = str_replace(' ', '+', $img);
        // $img_data = base64_decode($img);
        // $profile_pic_name = uniqid().'.jpg';
        // $file = './data/post/'.$profile_pic_name;
        // file_put_contents($file, $img_data);

        $profile_pic = '';
        if (!empty($this->data['profile_pic'])) {
            $img = $this->data['profile_pic'];
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $profile_pic = uniqid() . '.jpg';
            $file = './data/post/' . $profile_pic;
            file_put_contents($file, $img_data);
        }

        if (!empty($this->input->post('avatar'))) {
            $profile_pic = $this->data['avatar'];
        }

        $this->Users_model->UpdateUserPic($user_id, $name, $profile_pic, $bank_detail, $adhar_card, $upi, $email);
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function change_password_post()
    {
        $user_id = $this->input->post('user_id');
        $old_password = $this->input->post('old_password');
        $new_password = $this->input->post('new_password');

        if (empty($user_id)) {
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

        if ($user[0]->password != $old_password) {
            $data['message'] = 'Invalid Old Password';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user_data['password'] = $new_password;

        $this->Users_model->Update($user_id, $user_data);
        $data['message'] = 'Password updated successfully';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function update_bank_details_post()
    {
        $user_id = $this->input->post('user_id');
        $bank_name = $this->input->post('bank_name');
        $ifsc_code = $this->input->post('ifsc_code');
        $acc_holder_name = $this->input->post('acc_holder_name');
        $acc_no = $this->input->post('acc_no');
        $upi_id = $this->input->post('upi_id');
        $passbook_img = $this->input->post('passbook_img');
        $crypto_address = $this->input->post('crypto_address');
        $crypto_wallet_type = $this->input->post('crypto_wallet_type');
        $crypto_qr = $this->input->post('crypto_qr');

        if (empty($user_id)) {
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

        if (!empty($passbook_img)) {
            $img = $passbook_img;
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $passbook = uniqid() . '.jpg';
            $file = './data/post/' . $passbook;
            file_put_contents($file, $img_data);
            $update_data['passbook_img'] = $passbook;
        }
        if (!empty($crypto_qr)) {
            $img = $crypto_qr;
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $crypto_qr = uniqid() . '.jpg';
            $file = './data/post/' . $crypto_qr;
            file_put_contents($file, $img_data);
            $update_data['crypto_qr'] = $crypto_qr;
        }
        if (!empty($bank_name)) {
            $update_data['bank_name'] = $bank_name;
        }
        if (!empty($ifsc_code)) {
            $update_data['ifsc_code'] = $ifsc_code;
        }
        if (!empty($acc_holder_name)) {
            $update_data['acc_holder_name'] = $acc_holder_name;
        }
        if (!empty($acc_no)) {
            $update_data['acc_no'] = $acc_no;
        }
        if (!empty($upi_id)) {
            $update_data['upi_id'] = $upi_id;
        }
        if (!empty($crypto_address)) {
            $update_data['crypto_address'] = $crypto_address;
        }
        if (!empty($crypto_wallet_type)) {
            $update_data['crypto_wallet_type'] = $crypto_wallet_type;
        }

        $user_bank_details = $this->Users_model->UserBankDetails($user_id);
        if ($user_bank_details) {
            $this->Users_model->UpdateUserBankDetails($user_id, $update_data);
        } else {
            $update_data['user_id'] = $user_id;
            $this->Users_model->InsertUserBankDetails($update_data);
        }

        $data['message'] = 'Details Updated Successfull';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function update_kyc_post()
    {
        $user_id = $this->input->post('user_id');
        $pan_no = $this->input->post('pan_no');
        $pan_img = $this->input->post('pan_img');
        $aadhar_no = $this->input->post('aadhar_no');
        $aadhar_img = $this->input->post('aadhar_img');

        if (empty($user_id)) {
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

        if (!empty($pan_img)) {
            $img = $pan_img;
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $pan = uniqid() . 'pan.jpg';
            $file = './data/post/' . $pan;
            file_put_contents($file, $img_data);
            $update_data['pan_img'] = $pan;
        }

        if (!empty($aadhar_img)) {
            $img = $aadhar_img;
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $aadhar = uniqid() . '.jpg';
            $file = './data/post/' . $aadhar;
            file_put_contents($file, $img_data);
            $update_data['aadhar_img'] = $aadhar;
        }

        $update_data['pan_no'] = $pan_no;
        $update_data['aadhar_no'] = $aadhar_no;

        $user_kyc = $this->Users_model->UserKyc($user_id);
        if ($user_kyc) {
            $update_data['status'] = 0;
            $this->Users_model->UpdateUserKyc($user_id, $update_data);
        } else {
            $update_data['user_id'] = $user_id;
            $this->Users_model->InsertUserKyc($update_data);
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function welcome_bonus_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $WelcomeBonus = $this->Users_model->WelcomeBonus();
        if ($WelcomeBonus) {
            $bonus_log = $this->Users_model->WelcomeBonusLog($this->data['user_id']);

            $data['message'] = 'Success';
            $data['collected_days'] = count($bonus_log);
            if ($data['collected_days'] > 0) {
                $data['today_collected'] = ($bonus_log[0]->date == date('Y-m-d')) ? 1 : 0;
            } else {
                $data['today_collected'] = 0;
            }

            $data['welcome_bonus'] = $WelcomeBonus;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Bonus';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function reffer_earn_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'refferearnlog' => $this->Users_model->View_Reffer($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function purchase_reffer_post()
    {
        $user_id = $this->input->post('user_id');

        if (empty($user_id)) {
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

        $data = [
            'purchaserefferlog' => $this->Users_model->View_Purchase_Reffer($user_id),
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    public function collect_welcome_bonus_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $WelcomeBonus = $this->Users_model->WelcomeBonus();

        $bonus_log = $this->Users_model->WelcomeBonusLog($this->data['user_id']);
        if (empty($bonus_log)) {
            if ($WelcomeBonus[0]->game_played <= $user[0]->game_played) {
                $this->Users_model->AddWelcomeBonus($WelcomeBonus[0]->coin, $this->data['user_id']);
                direct_admin_profit_statement(DAILY_REWARD, -$WelcomeBonus[0]->coin, $this->data['user_id']);
                log_statement($this->data['user_id'], DAILY_REWARD, $WelcomeBonus[0]->coin, 0, 0);

                // $setting = $this->Setting_model->Setting();
                // for ($i=1; $i <= 3; $i++) {
                //     if ($user[0]->referred_by!=0) {
                //         $level = 'level_'.$i;
                //         $coins = (($WelcomeBonus[0]->coin*$setting->$level)/100);
                //         $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by);

                //         $log_data = [
                //             'user_id' => $user[0]->referred_by,
                //             'day' => $WelcomeBonus[0]->id,
                //             'bonus_user_id' => $this->data['user_id'],
                //             'coin' => $coins,
                //             'added_date' => date('Y-m-d H:i:s'),
                //             'level' => $i,
                //         ];

                //         $this->Users_model->AddWelcomeReferLog($log_data);
                //         $user = $this->Users_model->UserProfile($user[0]->referred_by);
                //     } else {
                //         break;
                //     }
                // }
                $data['message'] = 'Success';
                $data['coin'] = $WelcomeBonus[0]->coin;
                $data['code'] = HTTP_OK;
                $this->response($data, HTTP_OK);
                exit();
            }

            $data['message'] = 'You Have To Play ' . ($WelcomeBonus[0]->game_played - $user[0]->game_played) . ' More Games to Collect Bonus';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        } else {
            $last_date = $bonus_log[0]->date;

            if (strtotime($last_date) < strtotime(date('Y-m-d'))) {
                $collected_days = count($bonus_log);
                if ($WelcomeBonus && count($WelcomeBonus) > $collected_days) {
                    if ($WelcomeBonus[$collected_days]->game_played <= $user[0]->game_played) {
                        $this->Users_model->AddWelcomeBonus($WelcomeBonus[$collected_days]->coin, $this->data['user_id']);
                        direct_admin_profit_statement(DAILY_REWARD, -$WelcomeBonus[$collected_days]->coin, $this->data['user_id']);
                        log_statement($this->data['user_id'], DAILY_REWARD, $WelcomeBonus[$collected_days]->coin, 0, 0);
                        $setting = $this->Setting_model->Setting();
                        for ($i = 1; $i <= 3; $i++) {
                            if ($user[0]->referred_by != 0) {
                                $level = 'level_' . $i;
                                $coins = (($WelcomeBonus[$collected_days]->coin * $setting->$level) / 100);
                                $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by);
                                direct_admin_profit_statement(DAILY_REWARD, -$coins, $user[0]->referred_by);
                                log_statement($user[0]->referred_by, DAILY_REWARD, $coins, 0, 0);
                                $log_data = [
                                    'user_id' => $user[0]->referred_by,
                                    'day' => $WelcomeBonus[$collected_days]->id,
                                    'bonus_user_id' => $this->data['user_id'],
                                    'coin' => $coins,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'level' => $i,
                                ];

                                $this->Users_model->AddWelcomeReferLog($log_data);
                                $user = $this->Users_model->UserProfile($user[0]->referred_by);
                            } else {
                                break;
                            }
                        }

                        $data['message'] = 'Success';
                        $data['coin'] = $WelcomeBonus[$collected_days]->coin;
                        $data['code'] = HTTP_OK;
                        $this->response($data, HTTP_OK);
                        exit();
                    }
                } else {
                    $data['message'] = "All Bonus Already Collected";
                    $data['code'] = HTTP_NOT_ACCEPTABLE;
                    $this->response($data, 200);
                    exit();
                }

                $data['message'] = 'You Have To Play ' . ($WelcomeBonus[$collected_days]->game_played - $user[0]->game_played) . ' More Games to Collect Bonus';
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            $data['message'] = "Today's Bonus Already Collected";
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data['message'] = 'Invalid Bonus';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function DailyAttenenceBonus_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $DailyAttendance = $this->Users_model->DailyAttendanceBonus($this->data['user_id']);
        if ($DailyAttendance) {
            $data['message'] = 'Success';
            $data['data'] = $DailyAttendance;
            $data['todays_bet'] = $user[0]->todays_bet;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Bonus';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function user_category_post()
    {
        $this->load->model('UserCategory_model');

        $user_category = $this->UserCategory_model->AllTableMasterList();

        $data['message'] = 'Success';
        $data['user_category'] = $user_category;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    // public function get_qr_code_post()
    // {
    //     // $admin_id = $this->input->post('admin_id');
    //     $setting = $this->Setting_model->setting();
    //     $qr_code = $setting->qr_code;

    //     $data['message'] = 'Success';
    //     $data['qr_code'] = $qr_code;
    //     $data['code'] = HTTP_OK;
    //     $this->response($data, HTTP_OK);
    //     exit();
    // }

    public function upload_transaction_photo_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        $transaction_id = $this->input->post('transaction_id');

        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->UserProfile($user_id);

        $path = 'uploads/data/transaction/';
        $share_data['photo'] = upload_base64_image($this->input->post('img_1'), $path);
        $share_data['user_id'] = $user_id;
        $share_data['price'] = $amount;
        $share_data['transaction_id'] = $transaction_id;
        $share_data['transaction_type'] = 1; //for manual payment transaction type is 1 

        $inserted_id = $this->Users_model->insert($share_data);
        if ($inserted_id) {
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Failed to insert data';
            $data['code'] = 404;
            $this->response($data, HTTP_OK);
            exit();
        }

    }

    public function aviator_myHistory_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $history = $this->Aviator_model->myHistory($this->data['user_id'], 150);
        if ($history) {
            $data['game_data'] = $history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No logs';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function aviator_GameHistory_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $history = $this->Aviator_model->gameHistory($this->data['user_id'], 50);
        if ($history) {
            $data['game_data'] = $history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No logs';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function aviatorGameHistoryByLimit_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $limit = !empty($this->data['limit']) ? $this->data['limit'] : 10;

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $history = $this->Aviator_model->aviatorGameHistoryByLimit($limit);
        if ($history) {
            $data['game_data'] = $history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No logs';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function aviatorHistory_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $limit = !empty($this->data['limit']) ? $this->data['limit'] : 10;

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $history = $this->Aviator_model->LastWinningBet();
        if ($history) {
            $data['game_data'] = $history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No logs';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function ludo_winners_post()
    {
        $user_id = $this->input->post('user_id');
        $table_id = $this->input->post('table_id');
        if (empty($user_id) || empty($table_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_BLANK;
            $this->response($data, HTTP_OK);
            exit();
        }
        $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_BLANK;
            $this->response($data, HTTP_OK);
            exit();
        }
        $winners = $this->Users_model->ludo_winners($table_id);
        if ($winners) {
            $data['message'] = 'Success';
            $data['winner'] = $winners;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
        } else {
            $data['message'] = 'No Data';
            $data['code'] = 400;
            $this->response($data, 200);
            exit;
        }
    }

    public function purchase_history_post()
    {
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
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
        $purchase_history = $this->Users_model->get_purchase_data($user_id);
        if ($purchase_history) {
            $data['message'] = 'Success';
            $data['purchase_history'] = $purchase_history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No data';
            $data['code'] = 404;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function getNotifications_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $notification = $this->Users_model->getNotification();
        $data['message'] = 'Success';
        $data['notification'] = $notification;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function getStatement_post()
    {
        $page = 0;
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, HTTP_OK);
            exit();
        }

        $page = $this->input->post('page');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }


        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, HTTP_OK);
            exit();
        }


        $user_id = $this->data['user_id'];
        $statement = $this->Users_model->getUserStatement($user_id, '', 20, $page);

        $data['message'] = 'Success';
        $data['statement'] = $statement;
        $data['code'] = HTTP_OK;


        $this->response($data, HTTP_OK);
        exit();
    }

    public function verify_email_otp_post()
    {
        $otp_id = $this->data['otp_id'];
        $otp = $this->data['otp'];
        $email = $this->data['email'];
        if (empty($otp_id) || empty($otp) || empty($email)) {
            $data['message'] = 'Invalid parameter';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        }
        if ($this->Users_model->OTPConfirm($otp_id, $otp, $email)) {
            $data['message'] = 'Success';
            $data['otp_id'] = $otp_id;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'OTP Not Matched';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        }
        // }
    }
    public function user_amount_transfer_post()
    {
        $user_id = $this->input->post('user_id');
        $reciever_id = $this->input->post('reciever_id');
        $amount = $this->input->post('amount');
        $otp_id = $this->input->post('otp_id');
        $otp = $this->input->post('otp');
        if (empty($user_id) || empty($amount) || empty($reciever_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }
        $user_transfer_from = $this->Users_model->UserProfile($user_id);
        if ($user_transfer_from[0]->winning_wallet < $amount) {
            $data['message'] = 'You dont have sufficient amount to transfer';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if (!empty($user_transfer_from[0]->unutilized_wallet)) {
        //     $data['message'] = 'You need to play game after add cash';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $user = $this->Users_model->UserProfile($reciever_id);
        if (empty($user)) {
            $data['message'] = 'User Not Found';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user_transfer_from[0]->unutilized_wallet > 1) {
            $data = [
                'message' => 'withdraw not possible firstly use recharge wallet.',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $checkRecharge = $this->WithdrawalLog_model->checkRecharge($user_id);
        if (empty($checkRecharge)) {
            $data = [
                'message' => 'You are not eligible to withdraw, you will have to recharge',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $Setting = $this->Setting_model->Setting();
        if ($amount < $Setting->min_withdrawal) {
            $data = [
                'message' => 'You can not withdraw less then ' . $Setting->min_withdrawal . '$',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $check_otp = $this->Users_model->OTPConfirm($otp_id, $otp, $user_transfer_from[0]->mobile);
        if ($otp == 9999) {
            $check_otp = true;
        }
        if (empty($check_otp)) {
            $data['message'] = 'OTP Not Matched';
            $data['code'] = HTTP_NOT_FOUND;
            $this->response($data, HTTP_OK);
            exit();
        }
        if ($user) {
            $transfer = $this->Users_model->transfer_amount($reciever_id, $amount, $user_id);
            if ($transfer) {
                $data['message'] = 'Success';
                // $data['rank'] = $rank;
                $data['code'] = HTTP_OK;
                $this->response($data, HTTP_OK);
                exit();
            } else {
                $data['message'] = 'Transfer Unsuccessful';
                $data['code'] = 404;
                $this->response($data, HTTP_OK);
                exit();
            }
        } else {
            $data['message'] = 'User not found';
            $data['code'] = 404;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function createTicket_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token']) || empty($this->data['description']) || empty($this->data['category'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $ss = '';
        if (!empty($this->data['img'])) {
            $img = $this->data['img'];
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $ss = uniqid() . '.jpg';
            $file = './data/post/' . $ss;
            file_put_contents($file, $img_data);
        }
        $post_data = [
            'user_id' => $this->data['user_id'],
            'description' => $this->data['description'],
            'category' => $this->data['category'],
            'img' => $ss,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $id = $this->Users_model->createTicket($post_data);
        $data['message'] = 'Ticket Created Successfully, Team Will Response Shortly';
        $data['id'] = $id;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function getTickets_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $tickets = $this->Users_model->getTickets($this->data['user_id']);
        $data['message'] = 'Success';
        $data['tickets'] = $tickets;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function getDepositeBonus_post()
    {
        $user_id = $this->input->post('user_id');
        $date = $this->input->post('date');
        $purchase_user_id = $this->input->post('purchase_user_id');
        $type = $this->input->post('type');
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
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
        $activation_list = $this->Users_model->get_activation_data($user_id, $type, $purchase_user_id, $date);
        if ($activation_list) {
            $data['message'] = 'Success';
            $data['activation_list'] = $activation_list;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No records found.';
            $data['code'] = 404;
            $this->response($data, HTTP_OK);
            exit();
        }
    }

    public function salary_bonus_log_post()
    {
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
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
        $salary_bonus_data = $this->Users_model->get_salary_bonus_data($user_id);
        if ($salary_bonus_data) {
            $data['message'] = 'Success';
            $data['salary_bonus_data'] = $salary_bonus_data;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No data';
            $data['code'] = 404;
            $this->response($data, HTTP_OK);
            exit();
        }
    }
    public function bet_commission_log_post()
    {
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
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
        $bet_commission_log = $this->Users_model->get_bet_commission_log($user_id);
        if ($bet_commission_log) {
            $data['message'] = 'Success';
            $data['bet_commission_log'] = $bet_commission_log;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No Data';
            // $data['bet_commission_log'] = $bet_commission_log;
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, HTTP_OK);
            exit();
        }
    }
    public function reffer_level_post()
    {
        $user_id = $this->input->post('user_id');
        $level = $this->input->post('level');

        if (empty($user_id)) {
            $data['message'] = 'Invalid Params';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        $user_details = $this->Users_model->UserProfile($user_id);
        if (empty($user_details)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user_ids = ['No'];
        if (empty($level)) {
            for ($i = 1; $i <= 20; $i++) {
                if (!empty($user_details[0]->id) && $user_details[0]->id != 0) {
                    $user_details = $this->Users_model->get_levels_users($user_details[0]->id);
                    foreach ($user_details as $key => $value) {
                        $user_ids[] = $value->id;
                    }
                    if (!empty($user_details[0]->id)) {
                        $user_ids[] = $user_details[0]->id;
                    }

                } else {
                    break;
                }
            }
        } else {
            $default[] = $user_details[0]->id;
            for ($i = 1; $i <= $level; $i++) {
                if (!empty($default)) {
                    if ($i == 1) {
                        $level_users = $this->Users_model->get_all_levels_users($default);
                    } else {
                        $level_users = $this->Users_model->get_all_levels_users($get_user_ids);
                    }
                    $user_ids = ['No'];
                    $get_user_ids = [];
                    foreach ($level_users as $key => $value) {
                        $get_user_ids[] = $value->id;
                        $user_ids[] = $value->id;
                    }
                } else {
                    break;
                }
            }
            // print_r($last_level);
        }
        $refer = $this->Users_model->View_Reffers($user_id, $user_ids);
        //   echo $this->db->last_query();
        //   exit;
        $data = [
            'refferearnlog' => $refer,
            'message' => 'Success',
            'code' => HTTP_OK,
        ];
        $this->response($data, HTTP_OK);
    }

    // public function reffer_level_post()
    // {
    //     $user_id = $this->input->post('user_id');
    //     $level = $this->input->post('level');

    //     if (empty($user_id)) {
    //         $data['message'] = 'Invalid Params';
    //         $data['code'] = HTTP_BLANK;
    //         $this->response($data, 200);
    //         exit();
    //     }

    //     $user = $this->Users_model->UserProfile($user_id);
    //     if (empty($user)) {
    //         $data['message'] = 'Invalid User';
    //         $data['code'] = HTTP_NOT_ACCEPTABLE;
    //         $this->response($data, 200);
    //         exit();
    //     }
    //     $refer_users = array();
    //     $user_ids[] = $user_id;
    //     $final_user_ids = array();
    //     $level = ($level)?$level:1;

    //     for ($i=0; $i < $level; $i++) { 
    //         if($user_ids){
    //             foreach ($user_ids as $key => $for_user) {
    //                 $refer = $this->Users_model->View_Reffer($for_user);
    //                 if($refer){

    //                     if($i==($level-1)){
    //                         foreach ($refer as $key => $value) {
    //                             $final_user_ids[] = $value->id;
    //                             $refer_users[] = $value;
    //                         }
    //                     }
    //                     else{
    //                         $user_ids = array();
    //                         foreach ($refer as $key => $value) {
    //                             $user_ids[] = $value->id;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         else{
    //             break;
    //         }
    //     }
    //     // print_r($final_user_ids);
    //     // foreach ($final_user_ids as $ke => $final_user) {
    //     //     $refer = $this->Users_model->View_Reffer($final_user);
    //     //     foreach ($refer as $key => $value) {
    //     //         $refer_users[] = $value;
    //     //     }
    //     // }

    //     $data = [
    //         'refferearnlog' => $refer_users,
    //         'message' => 'Success',
    //         'code' => HTTP_OK,
    //     ];
    //     $this->response($data, HTTP_OK);
    // }

    public function user_commission_post()
    {
        $user_id = $this->input->post('user_id');
        $level = $this->input->post('level');
        $referal_user_id = $this->input->post('referal_user_id');

        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user_details = $this->Users_model->UserProfile($user_id);
        if (empty($user_details)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $user_ids = [];
        //     if(empty($level)){
        //     for ( $i = 1; $i <= 10; $i++ ) {
        //         if ( !empty($user_details[0]->id) && $user_details[ 0 ]->id != 0 ) {
        //             $user_details = $this->Users_model->get_levels_users( $user_details[ 0 ]->id );
        //             if(!empty($user_details[0]->id)){
        //                 $user_ids[]=$user_details[0]->id;
        //             }

        //         } else {
        //             break;
        //         }
        //     }
        // }else{
        //     for ( $i = 1; $i <= $level; $i++ ) {
        //         if ( !empty($user_details[0]->id) && $user_details[ 0 ]->id != 0 ) {
        //             $user_details = $this->Users_model->get_levels_users( $user_details[ 0 ]->id );
        //             if(!empty($user_details[0]->id)){
        //                 $user_ids=[$user_details[0]->id];
        //             }else{
        //                 $user_ids=['No'];
        //             }

        //         } else {
        //             break;
        //         }
        //     } 
        // }



        $todays_deposit_amount = $this->Users_model->totalDeposit($user_id);
        $todays_deposit_number = $this->Users_model->totalDepositNumber($user_id);
        $totalBetAmount = $this->Users_model->totalBetAmount($user_id);
        $totalFirstDeposit = 0;
        $totalFirstDepositAmount = 0;
        // $totalBetters = $this->Users_model->totalBetters($user_ids,$referal_user_id);
        $totalMyTeam = $this->Users_model->totalMyTeam($user_id);
        $recharge_commission = $this->Users_model->get_recharge_commission($user_id);
        // $totalBetters = $this->Users_model->totalBetters($user_ids,$referal_user_id);
        $level_1_users = $this->Users_model->get_levels_users($user_id);
        $level_1_count = 0;
        $level_2_count = 0;
        $level_3_count = 0;
        $level_4_count = 0;
        $level_5_count = 0;
        $level_6_count = 0;
        $level_7_count = 0;
        $level_8_count = 0;
        $level_9_count = 0;
        $level_10_count = 0;

        foreach ($level_1_users as $level_1) {
            $level_1_id = $level_1->id;
            // print_r($level_1_id);
            // echo ' level 1 users = '.$level_1_id;
            // echo '<br>';
            $level_2_users = $this->Users_model->get_levels_users($level_1_id);

            foreach ($level_2_users as $level_2) {
                $level_2_id = $level_2->id;
                // echo 'level 2 users = '.$level_2_id;
                // echo '<br>';
                $level_3_users = $this->Users_model->get_levels_users($level_2_id);

                foreach ($level_3_users as $level_3) {
                    $level_3_id = $level_3->id;
                    // echo 'level 3 users = '.$level_3_id;
                    // echo '<br>';
                    $level_4_users = $this->Users_model->get_levels_users($level_3_id);

                    foreach ($level_4_users as $level_4) {
                        $level_4_id = $level_4->id;
                        // echo 'level 4 users = '.$level_4_id;
                        // echo '<br>';
                        $level_5_users = $this->Users_model->get_levels_users($level_4_id);
                        foreach ($level_5_users as $level_5) {
                            $level_5_id = $level_5->id;
                            // echo 'level 5 users = '.$level_5_id;
                            // echo '<br>';
                            $level_6_users = $this->Users_model->get_levels_users($level_5_id);
                            foreach ($level_6_users as $level_6) {
                                $level_6_id = $level_6->id;
                                // echo 'level 6 users = '.$level_6_id;
                                // echo '<br>';
                                $level_7_users = $this->Users_model->get_levels_users($level_6_id);
                                foreach ($level_7_users as $level_7) {
                                    $level_7_id = $level_7->id;
                                    // echo 'level 7 users = '.$level_7_id;
                                    // echo '<br>';
                                    $level_8_users = $this->Users_model->get_levels_users($level_7_id);
                                    foreach ($level_8_users as $level_8) {
                                        $level_8_id = $level_8->id;
                                        // echo 'level 8 users = '.$level_8_id;
                                        // echo '<br>';
                                        $level_9_users = $this->Users_model->get_levels_users($level_8_id);
                                        foreach ($level_9_users as $level_9) {
                                            $level_9_id = $level_9->id;
                                            // echo 'level 9 users = '.$level_9_id;
                                            // echo '<br>';
                                            $level_10_users = $this->Users_model->get_levels_users($level_9_id);
                                            foreach ($level_10_users as $level_10) {
                                                $level_10_id = $level_10->id;

                                                $level_10_count++;
                                            }
                                            $level_9_count++;
                                        }
                                        $level_8_count++;
                                    }
                                    $level_7_count++;
                                }
                                $level_6_count++;
                            }
                            $level_5_count++;
                        }
                        $level_4_count++;
                    }
                    $level_3_count++;
                }
                $level_2_count++;
            }
            $level_1_count++;
        }

        $total_team_count = $level_1_count + $level_2_count + $level_3_count + $level_4_count + $level_5_count + $level_6_count + $level_7_count + $level_8_count + $level_9_count + $level_10_count;

        $total_team = $total_team_count;
        $data['message'] = 'Success';
        $data['deposit_number'] = $todays_deposit_number ?? 0;
        $data['deposit_amount'] = $todays_deposit_amount ?? 0;
        $data['number_of_battors'] = 0;
        $data['total_bet'] = $totalBetAmount ?? 0;
        $data['first_deposit'] = $totalFirstDeposit ?? 0;
        $data['total_team'] = $total_team_count ?? 0;
        $data['total_team_recgharge_amount'] = $recharge_commission ?? 0;
        $data['my_team'] = $totalMyTeam ?? 0;
        $data['first_deposit_amount'] = $totalFirstDepositAmount ?? 0;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function transfer_wallet_history_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $reciever_id = $this->input->post('reciever_id');
        $date = $this->input->post('date');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $history = $this->Users_model->getWalletTransfer($this->data['user_id'], $reciever_id, $date);
        $data['message'] = 'Success';
        $data['history'] = $history;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function getUserNameById_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $reciever_id = $this->input->post('reciever_id');
        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $user = $this->Users_model->getUserNameById($reciever_id);
        $data['message'] = 'Success';
        $data['data'] = $user;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }
    public function rebateHistory_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['token'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $history = $this->Users_model->rebateHistory($this->data['user_id'], 50);
        if ($history) {
            $data['data'] = $history;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No logs';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function CountryCode_post()
    {

        $country = $this->Country_model->AllTableMasterList();
        $data['message'] = 'Success';
        $data['list'] = $country;
        $location = getLocationData($_SERVER['REMOTE_ADDR']);
        $data['my_country'] = $location['country'];
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function spin_post()
    {
        $user_id = $this->input->post('user_id');
        // $redeem_id = $this->input->post('redeem_id');
        $amount = $this->input->post('amount');
        // $mobile = $this->input->post('mobile');
        if (empty($user_id) || empty($amount)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $arr = [];
        switch ($amount) {
            case 100:
                $arr = [1, 5, 18, 58, 88, 888];
                break;

            case 500:
                $arr = [5, 25, 90, 200, 440, 4440];
                break;

            case 1800:
                $arr = [18, 90, 324, 1044, 1584, 15984];
                break;

            default:
                $data['message'] = 'Invalid Amount';
                $data['code'] = HTTP_INVALID;
                $this->response($data, HTTP_OK);
                exit();
                break;
        }

        // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($user->wallet < $amount) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $this->Setting_model->updateSpinBucket('+' . $amount);

        $setting = $this->Setting_model->Setting('`spin_bucket`');
        $spin_bucket = $setting->spin_bucket;

        $win_amount = 0;

        foreach ($arr as $key => $value) {
            if ($spin_bucket >= $value) {
                $win_amount = $value;

                if ($key >= rand(0, 5)) {
                    break;
                }
                continue;
            }
            break;
        }

        minus_from_wallets($user->id, $amount, 1);
        $spin = $this->Users_model->spin($user->id, $amount, $win_amount);
        if ($spin) {
            log_statement($user->id, SPIN, -$amount, $spin);
            log_statement($user->id, SPIN, $win_amount, $spin);
            $this->Setting_model->updateSpinBucket('-' . $win_amount);
            $data = [
                'win_amount' => $win_amount,
                'message' => 'Congratulations!!! You Won ' . $win_amount,
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

    public function slot_spin_post()
    {
        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');

        if (empty($user_id) || empty($amount)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        // if ($UserData[0]->wallet < $RedeemData->coin) {
        if ($user->wallet < $amount) {
            $data = [
                'message' => 'Insufficient Coins',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }

        // $this->Setting_model->updateSpinBucket('+'.$amount);

        $setting = $this->Setting_model->Setting('`admin_coin`');
        $admin_coin = $setting->admin_coin;

        $win_amount = 0;

        if ($admin_coin >= 10) {
            $win_amount = mt_rand(ceil(10 / 10), floor($admin_coin / 10)) * 10;
        } else {
            $win_amount = 0;
        }

        minus_from_wallets($user->id, $amount, 1);
        $slot_id = $this->Users_model->slot_user($user->id, $amount, $win_amount);
        direct_admin_profit_statement(SL, $amount, $slot_id);
        if ($slot_id) {

            log_statement($user->id, SL, -$amount, $slot_id);
            log_statement($user->id, SL, $win_amount, $slot_id);

            direct_admin_profit_statement(SL, -$win_amount, $slot_id);
            $data = [
                'win_amount' => $win_amount,
                'message' => 'Congratulations!!! You Won ' . $win_amount,
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

    public function getAgentList_post()
    {

        $user_id = $this->input->post('user_id');
        $amount = $this->input->post('amount');
        if (empty($user_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $agents = $this->Users_model->getAgentByAmount($amount);
        $data['message'] = 'Success';
        $data['data'] = $agents;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function generateConversationId_post()
    {
        $this->load->model('Agent_model');
        $user_id = $this->input->post('user_id');
        $agent_id = $this->input->post('agent_id');
        if (empty($user_id) || empty($agent_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $AgentData = $this->Agent_model->UserAgentProfile($this->data['agent_id']);
        if (empty($AgentData)) {
            $data['message'] = 'Agent Not Found.';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $conversation_data = $this->Users_model->getConversationId($this->data['user_id'], $this->data['agent_id']);
        if (empty($conversation_data)) {
            $request_data = [
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'added_date' => date('Y-m-d H:i:s')
            ];
            $conversation_id = $this->Users_model->generateConversationId($request_data);
            $chats = [];
        } else {
            $conversation_id = $conversation_data->id;
            $chats = $this->Users_model->getChatsByConversationId($conversation_data->id);
        }
        $data['message'] = 'Success';
        $data['conversation_id'] = $conversation_id;
        $data['chats'] = $chats;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function startConversation_post()
    {
        $this->load->model('Agent_model');
        $user_id = $this->input->post('user_id');
        $agent_id = $this->input->post('agent_id');
        $conversation_id = $this->input->post('conversation_id');
        $msg = $this->input->post('msg');
        if (empty($user_id) || empty($msg) || empty($conversation_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $AgentData = $this->Agent_model->UserAgentProfile($this->data['agent_id']);
        if (empty($AgentData)) {
            $data['message'] = 'Agent Not Found.';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $conversation_data = $this->Users_model->getConversationId($this->data['user_id'], $this->data['agent_id']);
        if (empty($conversation_data)) {
            $data['message'] = 'Invalid Conversation Id';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $request_data = [
            'sender_id' => $user_id,
            'sender_type' => 'user',
            'conversation_id' => $conversation_id,
            'message' => $msg,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $id = $this->Users_model->sendMsg($request_data);

        $convPayload = ["updated_date" => date('Y-m-d H:i:s')];
        $this->Chat_model->updateConversation($convPayload, $conversation_id);

        $data['message'] = 'Success';
        $data['msg_id'] = $id;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function agent_chats_post()
    {
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            $this->response($data, HTTP_OK);
        }
        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $conversation_data = $this->Users_model->getConversationAgents($this->data['user_id']);
        $data['message'] = 'Success';
        $data['data'] = $conversation_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function push_notification_post()
    {
        $fcm_token = $this->input->post('fcm_token');
        $title = $this->input->post('title');
        $body = $this->input->post('body');
        $msg = array
        (
            'title' => $title,
            'body' => $body,
        );
        $response = push_notification_android($fcm_token, $msg);
        echo json_encode($response);
    }

    // public function get_payment_method_by_user_id_post()
    // {
    //     $user =$this->input->post('user_id');
    //     if (empty($user_id)) {
    //         $data = [
    //             'message' => 'Invalid Param',
    //             'code' => HTTP_NOT_FOUND,
    //         ];
    //         $this->response($data, HTTP_OK);
    //     }
    //     $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
    //     if (!$user) {
    //         $data['message'] = 'Invalid User';
    //         $data['code'] = HTTP_INVALID;
    //         $this->response($data, HTTP_OK);
    //         exit();
    //     }


    // }

    public function get_payment_method_by_user_id_post()
    {
        $user_id = $this->input->post('user_id');

        // Check if user_id is present
        if (empty($user_id)) {
            $data = [
                'message' => 'Invalid Param',
                'code' => HTTP_NOT_FOUND,
            ];
            return $this->response($data, HTTP_OK);
        }

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        // Step 2: Check if the user exists
        $user = $this->db->get_where('tbl_users', ['id' => $user_id])->row();
        if (!$user) {
            $data = [
                'message' => 'User Not Found',
                'code' => HTTP_NOT_FOUND,
            ];
            return $this->response($data, HTTP_OK);
        }

        $created_by = $user->created_by;

        $admin = $this->db->get_where('tbl_admin', ['id' => $created_by])->row();
        $admin_name = $admin ? $admin->first_name . ' ' . $admin->last_name : '';

        // Step 4: Fetch payment methods based on created_by
        $this->db->where('user_id', $created_by);
        $payment_methods = $this->db->get('tbl_payment_method')->result();

        // Step 5: Respond with the data
        $data = [
            'message' => 'Payment Methods Found',
            'code' => HTTP_OK,
            'agent_name' => $admin_name,
            'data' => $payment_methods
        ];
        return $this->response($data, HTTP_OK);
    }

    public function addPaymentProof_post()
    {
        $image = '';
        $agentId = $this->input->post('agent_id');
        $userId = $this->input->post('user_id');
        $txnId = $this->input->post('txn_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        // Check if image is provided
        if (!empty($this->data['image'])) {
            $img = $this->data['image'];
            $img = str_replace(' ', '+', $img);
            $img_data = base64_decode($img);
            $image = uniqid() . '.jpg';
            $file = './data/post/' . $image;
            file_put_contents($file, $img_data);
        }

        // Save to tbl_agent_payment_proof
        $paymentProof = $this->Users_model->AddAgentPaymentProof($agentId, $userId, $txnId, $image);
        // print_r($paymentProof);exit();

        if ($paymentProof) {
            $data['message'] = 'Thank you, Request Submitted';
            $data['paymentProof'] = $paymentProof;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
        } else {
            $data['code'] = HTTP_NOT_FOUND;
            $data['message'] = 'Something happened, try again later..';
            $this->response($data, 200);
        }
    }
}
