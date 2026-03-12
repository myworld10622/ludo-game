<?php

class Users_model extends MY_Model
{
    public function AllBotUserList()
    {
        $this->db->from('tbl_bot_users');
        $this->db->where('tbl_bot_users.isDeleted', false);
        $this->db->order_by('rand()');
        $this->db->limit(6);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function AllUserList()
    {
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'asc');
        // $this->db->limit(10);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function WelcomeBonus($id = '')
    {
        if (!empty($id)) {
            $this->db->where('id', $id);
        }
        $Query = $this->db->get('tbl_welcome_reward');
        return $Query->result();
    }


    public function Bonus($id = '')
    {
        if (!empty($id)) {
            $this->db->where('tbl_welcome_log.id', $id);
        }

        $this->db->select('tbl_welcome_log.*, tbl_users.name'); // Include tbl_users.username
        $this->db->from('tbl_welcome_log');
        $this->db->join('tbl_users', 'tbl_welcome_log.user_id = tbl_users.id', 'left');

        $Query = $this->db->get();
        return $Query->result();
    }


    public function WelcomeRefferalBonus($id = '')
    {
        if (!empty($id)) {
            $this->db->where('tbl_welcome_ref.id', $id);
        }
        $this->db->select('tbl_welcome_ref.*, tbl_users.name'); // Include tbl_users.username
        $this->db->from('tbl_welcome_ref');
        $this->db->join('tbl_users', 'tbl_welcome_ref.user_id = tbl_users.id', 'left');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function RefferalBonus($id = '')
    {
        if (!empty($id)) {
            $this->db->where('tbl_referral_bonus_log.id', $id);
        }
        $this->db->select('tbl_referral_bonus_log.*, tbl_users.name'); // Include tbl_users.username
        $this->db->from('tbl_referral_bonus_log');
        $this->db->join('tbl_users', 'tbl_referral_bonus_log.user_id = tbl_users.id', 'left');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function PurchaseBonus($id = '')
    {
        if (!empty($id)) {
            $this->db->where('tbl_purcharse_ref.id', $id);
        }
        $this->db->select('tbl_purcharse_ref.*, tbl_users.name'); // Include tbl_users.username
        $this->db->from('tbl_purcharse_ref');
        $this->db->join('tbl_users', 'tbl_purcharse_ref.user_id = tbl_users.id', 'left');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function WelcomeBonusLog($user_id)
    {
        $this->db->select('*,DATE(added_date) as date');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get('tbl_welcome_log');
        return $Query->result();
    }


    public function AddWelcomeBonus($amount, $user_id)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('bonus_wallet', 'bonus_wallet+' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        $data = [
            'user_id' => $user_id,
            'coin' => $amount,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_welcome_log', $data);
        return $this->db->insert_id();
    }

    public function UpdateWelcomeBonus($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_welcome_reward', $data);
        return $this->db->affected_rows();
    }

    public function FreeUserList()
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->where('tbl_users.table_id', false);
        $this->db->order_by('tbl_users.id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function AllRedeemList()
    {
        $this->db->select('tbl_redeem.*,tbl_users.name');
        $this->db->from('tbl_redeem');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_redeem.user_id');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_redeem.id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function RedeemList($user_id)
    {
        $this->db->select('id,amount,payment_method,status,reason,added_date');
        $this->db->from('tbl_redeem');
        $this->db->where('isDeleted', false);
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function WinningList($user_id)
    {
        $this->db->from('tbl_game_rewards');
        $this->db->where('isDeleted', false);
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function TodayUserList()
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->where('date(tbl_users.created_date)', date("Y-m-d"));
        $Query = $this->db->get();
        return $Query->result();
    }

    public function InsertOTP($MobileNo, $OTP)
    {
        $this->db->where('mobile', $MobileNo);
        $Query = $this->db->get('tbl_otp');
        $OTPRecord = $Query->row();
        if ($OTPRecord) {
            //update otp
            $data = [
                'otp' => $OTP,
                'added_date' => date('Y-m-d H:i:s')
            ];
            $this->db->where('id', $OTPRecord->id);
            if ($this->db->update('tbl_otp', $data)) {
                return $OTPRecord->id;
            } else {
                return false;
            }
        } else {
            //insert otp
            $data = [
                'otp' => $OTP,
                'mobile' => $MobileNo
            ];
            if ($this->db->insert('tbl_otp', $data)) {
                return $this->db->insert_id();
            } else {
                return false;
            }
        }
    }

    public function OTPConfirm($Id, $OTP, $MobileNo)
    {
        $this->db->where('id', $Id);
        $this->db->where('otp', $OTP);
        $this->db->where('mobile', $MobileNo);
        $Query = $this->db->get('tbl_otp');
        return $Query->row();
    }

    public function TokenConfirm($user_id, $token)
    {
        $this->db->where('id', $user_id);
        $this->db->where('token', $token);
        $this->db->where('status', 0);
        $this->db->where('isDeleted', 0);
        $Query = $this->db->get('tbl_users');
        // echo $this->db->last_query();die();
        return $Query->row();
    }

    public function UserByMobile($MobileNo)
    {
        $this->db->where('isDeleted', false);
        $this->db->where('mobile', $MobileNo);
        $Query = $this->db->get('tbl_users');
        return $Query->row();
    }

    public function UpdateUser($UserId, $fcm)
    {
        $data = [
            'fcm' => $fcm
        ];
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function Delete($UserId)
    {
        $data = [
            'isDeleted' => 1
        ];
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function UpdateAppVersion($UserId, $app_version)
    {
        $data = [
            'app_version' => $app_version
        ];
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function updateUpdatedDate($UserId)
    {
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $UserId);
        return $this->db->affected_rows();
    }
    public function UpdateToken($UserId, $token)
    {
        $data = [
            'token' => $token
        ];
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function UpdateUserWallet($data, $UserId)
    {
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function AddBot($data)
    {
        $this->db->insert('tbl_users', $data);
        $user_id = $this->db->insert_id();
        $this->WalletLog($data['wallet'], 1, $user_id);
        return $user_id;
    }

    public function getAdhar($user)
    {
        $this->db->select('*');
        $this->db->where('id', $user);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get('tbl_users');
        return $Query->row()->adhar_card;
    }

    public function UpdateUserPic($UserId, $name, $profile_pic = '', $bank_detail = '', $adhar_card = '', $upi = '', $email = '')
    {
        $data = [
            'name' => $name,
            'bank_detail' => $bank_detail,
            'adhar_card' => $adhar_card,
            'upi' => $upi,
            'email' => $email,
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($profile_pic)) {
            $data['profile_pic'] = $profile_pic;
        }
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function Update($UserId, $data)
    {
        $this->db->where('id', $UserId);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function UpdateUserBankDetails($UserId, $data)
    {
        $this->db->where('user_id', $UserId);
        $this->db->update('tbl_users_bank_details', $data);
        return $this->db->affected_rows();
    }

    public function InsertUserBankDetails($data)
    {
        $this->db->insert('tbl_users_bank_details', $data);
        return $this->db->insert_id();
    }

    public function UpdateUserKyc($UserId, $data)
    {
        $this->db->where('user_id', $UserId);
        $this->db->update('tbl_users_kyc', $data);
        return $this->db->affected_rows();
    }

    public function InsertUserKyc($data)
    {
        $this->db->insert('tbl_users_kyc', $data);
        return $this->db->insert_id();
    }

    public function ChangeStatus($id, $status)
    {
        $data = [
            'status' => $status
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_users', $data);

        return $this->db->affected_rows();
    }

    public function RegisterUser($MobileNo, $Name, $profile_pic, $gender = 'm', $token = '', $password = '', $bonus_amount = '', $app = 'self', $email = '', $referral_precent = '')
    {
        if (empty($profile_pic)) {
            $profile_pic = ($gender == 'f') ? 'f_' . rand(1, 3) . '.png' : 'm_' . rand(1, 10) . '.png';
        }
        if ($bonus_amount == '') {
            $bonus_amount = 25000;
        }

        $data = [
            'mobile' => $MobileNo,
            'name' => $Name,
            'gender' => $gender,
            'profile_pic' => $profile_pic,
            'token' => $token,
            'password' => $password,
            'wallet' => $bonus_amount,
            'bonus_wallet' => $bonus_amount,
            'referral_precent' => $referral_precent,
            'app' => ($app) ? $app : 'self',
            'email' => ($email) ? $email : '',
            'added_date' => date('Y-m-d H:i:s')
        ];
        // print_r($data);
        $this->db->insert('tbl_users', $data);
        $UserId = $this->db->insert_id();
        direct_admin_profit_statement(REGISTER_BONUS, -$bonus_amount, $UserId);
        log_statement($UserId, REGISTER_BONUS, $bonus_amount, 0, 0);
        $this->WalletLog($bonus_amount, 0, $UserId);

        return $UserId;
    }

    public function RegisterUserByUniqueKey($uniqueyKey, $avatar, $bonus_amount = '')
    {
        if ($bonus_amount == '') {
            $bonus_amount = 25000;
        }

        $data = [
            'unique_token' => $uniqueyKey,
            'wallet' => $bonus_amount,
            'profile_pic' => $avatar,
            'name' => 'Guest',
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_users', $data);
        $UserId = $this->db->insert_id();
        log_statement($UserId, REGISTER_BONUS, $bonus_amount, 0, 0);
        $this->WalletLog($bonus_amount, 0, $UserId);

        return $UserId;
    }

    public function RegisterUserEmail($Email, $Name, $source, $profile_pic, $gender = 'm', $token = '')
    {
        if (empty($profile_pic)) {
            $profile_pic = ($gender == 'f') ? 'f_' . rand(1, 3) . '.png' : 'm_' . rand(1, 10) . '.png';
        }

        $data = [
            'email' => $Email,
            'name' => $Name,
            'source' => $source,
            'gender' => $gender,
            'profile_pic' => $profile_pic,
            'token' => $token,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_users', $data);
        $UserId = $this->db->insert_id();

        return $UserId;
    }

    public function AddRedeem($data)
    {
        $this->db->insert('tbl_redeem', $data);
        $ReedemId = $this->db->insert_id();

        $this->db->set('wallet', 'wallet-' . $data['amount'], false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $data['user_id']);
        $this->db->update('tbl_users');

        $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
        $this->db->where('id', $user_id);
        $this->db->where('winning_wallet>', 0);
        $this->db->update('tbl_users');

        return $ReedemId;
    }

    public function UpdateWallet($referer_id, $amount, $user_id)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('bonus_wallet', 'bonus_wallet+' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $referer_id);
        $this->db->update('tbl_users');

        $data = [
            'user_id' => $referer_id,
            'referred_user_id' => $user_id,
            'coin' => $amount,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_referral_bonus_log', $data);

        return true;
    }

    public function UpdateWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        if ($bonus == 1) {
            $this->db->set('bonus_wallet', 'bonus_wallet+' . $amount, false);
        } else {
            $this->db->set('unutilized_wallet', 'unutilized_wallet+' . $amount, false);
            $this->db->set('todays_recharge', 'todays_recharge+' . $amount, false);
        }

        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        return true;
    }

    public function UpdateWalletSpin($user_id, $coin)
    {
        $this->db->set('wallet', 'wallet+' . $coin, false);
        $this->db->set('bonus_wallet', 'bonus_wallet+' . $coin, false);
        $this->db->set('spin_remaining', 'spin_remaining-1', false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        return true;
    }

    public function UpdateSpin($user_id, $spin_count, $user_category_id)
    {
        $this->db->set('spin_remaining', 'spin_remaining+' . $spin_count, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->set('user_category_id', $user_category_id);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
        return true;
    }

    public function UpdateStar($user_id, $star)
    {
        $this->db->from('tbl_users');
        $this->db->where('id', $user_id);

        $Query = $this->db->get();
        $user = $Query->row();

        $star = ($star < 0 && $user->golden_wheel_star <= 0) ? 0 : $star;

        $this->db->set('golden_wheel_star', 'golden_wheel_star' . $star, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
        return $this->db->affected_rows();
    }

    public function ExtraWalletLog($user_id, $amount, $type)
    {
        $data = [
            'user_id' => $user_id,
            'coin' => $amount,
            'type' => $type,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_extra_wallet_log', $data);
        return $this->db->insert_id();
    }

    public function WalletLog($amount, $bonus, $user_id)
    {
        $data = [
            'user_id' => $user_id,
            'bonus' => $bonus,
            'coin' => $amount,
            'added_by' => ($this->session->userdata('admin_id')) ? $this->session->userdata('admin_id') : '0'
            //'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_wallet_log', $data);
        return $this->db->insert_id();
    }

    public function View_WalletLog($user_id)
    {
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get('tbl_wallet_log');
        return $Query->result();
    }

    public function TipAdmin($amount, $user_id, $table_id, $gift_id, $to_user_id)
    {
        $this->db->set('wallet', 'wallet-' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
        $this->db->where('id', $user_id);
        $this->db->where('winning_wallet>', 0);
        $this->db->update('tbl_users');

        $this->db->set('admin_coin', 'admin_coin+' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->update('tbl_setting');

        $data = [
            'user_id' => $user_id,
            'to_user_id' => $to_user_id,
            'gift_id' => $gift_id,
            'table_id' => $table_id,
            'coin' => $amount,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_tip_log', $data);
        return $this->db->insert_id();
    }

    public function GiftList($table_id)
    {
        $curr = date('Y-m-d H:i:s');
        $last_min = date('Y-m-d H:i:s', strtotime('-30 seconds'));

        $this->db->select('tbl_tip_log.*,tbl_gift.image');
        $this->db->where('gift_id!=', 0);
        $this->db->where('table_id', $table_id);
        $this->db->where('tbl_tip_log.added_date >=', $last_min);
        $this->db->where('tbl_tip_log.added_date <=', $curr);
        $Query = $this->db->join('tbl_gift', 'tbl_gift.id=tbl_tip_log.gift_id');
        $Query = $this->db->get('tbl_tip_log');
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function UpdateReferralCode($user_id, $referralId)
    {
        if (!$referralId) {
            $referralId = 'TEENPATTI';
        }
        $this->db->set('referral_code', $referralId . str_pad($user_id, 5, "0", STR_PAD_LEFT));
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
    }

    public function LoginUser($MobileNo, $Password)
    {
        $this->db->where('mobile', $MobileNo);
        $this->db->where('password', $Password);
        $this->db->where('isDeleted', false);
        $user = $this->db->get('tbl_users');

        return $user->result();
    }

    public function UserProfile($id)
    {
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        $this->db->where('tbl_users.id', $id);
        $this->db->where('tbl_users.isDeleted', false);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserProfileByUniqueKey($uniqueKey)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_users.unique_token', $uniqueKey);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserKyc($id)
    {
        $this->db->from('tbl_users_kyc');
        $this->db->where('user_id', $id);
        $this->db->where('isDeleted', false);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserBankDetails($id)
    {
        $this->db->from('tbl_users_bank_details');
        $this->db->where('user_id', $id);
        $this->db->where('isDeleted', false);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function AddPurchaseReferLog($data)
    {
        $this->db->insert('tbl_purcharse_ref', $data);
        return $this->db->insert_id();
    }

    public function AddWelcomeReferLog($data)
    {
        $this->db->insert('tbl_welcome_ref', $data);
        return $this->db->insert_id();
    }

    public function GetFreeBot($amount = 10000)
    {
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('status', false);
        $this->db->where('table_id', 0);
        $this->db->where('rummy_table_id', 0);
        $this->db->where('poker_table_id', 0);
        $this->db->where('rummy_pool_table_id', 0);
        $this->db->where('rummy_deal_table_id', 0);
        $this->db->where('ludo_table_id', 0);
        $this->db->where('wallet>=', $amount);
        $this->db->where('user_type', 1);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function RandomBoatUsers()
    {
        $this->db->from('tbl_bot_users');
        $this->db->where('isDeleted', false);
        $this->db->order_by('rand()');
        $this->db->limit(50);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GetFreeRummyBot($amount = 1000)
    {
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('status', false);
        $this->db->where('rummy_table_id', 0);
        $this->db->where('wallet>=', $amount);
        $this->db->where('user_type', 1);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Setting()
    {
        $this->db->from('tbl_setting');
        $this->db->where('isDeleted', false);

        $Query = $this->db->get();
        return $Query->row();
    }

    public function getAdmin($id = '')
    {
        $this->db->from('tbl_admin');
        $this->db->where('isDeleted', false);
        if ($id) {
            $this->db->where('id', $id);
        }
        $Query = $this->db->get();
        return $Query->row();
    }

    public function UpdateSetting($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_setting', $data);
        return $this->db->affected_rows();
    }

    public function UpdateAdmin($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_admin', $data);
    }

    public function UpdateGameStatus($user_id)
    {
        // $this->db->set('table_id', '0'); //value that used to update column
        $this->db->set('poker_table_id', '0'); //value that used to update column
        $this->db->set('head_tail_room_id', '0'); //value that used to update column
        // $this->db->set('rummy_table_id', '0'); //value that used to update column
        $this->db->set('ander_bahar_room_id', '0'); //value that used to update column
        $this->db->set('dragon_tiger_room_id', '0'); //value that used to update column
        $this->db->set('jackpot_room_id', '0'); //value that used to update column
        $this->db->set('seven_up_room_id', '0'); //value that used to update column
        // $this->db->set('rummy_pool_table_id', '0'); //value that used to update column
        $this->db->set('color_prediction_room_id', '0'); //value that used to update column
        $this->db->set('car_roulette_room_id', '0'); //value that used to update column
        $this->db->set('animal_roulette_room_id', '0'); //value that used to update column
        $this->db->set('ludo_table_id', '0'); //value that used to update column
        $this->db->set('red_black_id', '0'); //value that used to update column
        $this->db->set('baccarat_id', '0'); //value that used to update column
        $this->db->set('jhandi_munda_id', '0'); //value that used to update column
        $this->db->set('rummy_tournament_table_id', '0'); //value that used to update column
        $this->db->set('target_room_id', '0'); //value that used to update column
        $this->db->set('roulette_id', '0'); //value that used to update column
        $this->db->set('ander_bahar_plus_room_id', '0'); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name
        return $this->db->affected_rows();
    }

    public function UserWallet($user_id)
    {
        $this->db->select('tbl_users.wallet');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_users.id', $user_id);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function UserProfileByMobile($MobileNo)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_users.mobile', $MobileNo);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserProfileByEmail($Email)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_users.email', $Email);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function IsValidReferral($referral_code)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_users.referral_code', $referral_code);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function View_Wins($user_id)
    {
        $Query = $this->db->where('isDeleted', false)
            ->where('winner_id', $user_id)
            ->order_by('tbl_game.id', 'desc')
            ->get('tbl_game');
        return $Query->result();
    }

    public function View_Purchase($user_id)
    {
        $Query = $this->db->where('isDeleted', false)
            ->where('user_id', $user_id)
            // ->where('payment', 1)
            ->get('tbl_purchase');
        return $Query->result();
    }

    public function getNumberOfPurchase($user_id)
    {
        return $this->db->where(['user_id' => $user_id, 'payment' => 1])->from("tbl_purchase")->count_all_results();
    }

    // public function View_AllPurchase($user_id)
    // {
    //     $Query = $this->db->query("SELECT * FROM (
    //         SELECT `coin`,`price`,`updated_date`, 'ONLINE PURCHASE' as type,`user_id` FROM `tbl_purchase` WHERE `payment`=1
    //         UNION
    //         SELECT `coin`,0 as price,`added_date`, IF(`bonus`=1,'BONUS','ADMIN PURCHASE') as type,`user_id` FROM `tbl_wallet_log`
    //         ) as a WHERE user_id='" . $user_id . "'ORDER BY updated_date DESC");
    //     return $Query->result();
    // }

    public function View_AllPurchase($user_id)
    {
        $Query = $this->db->query("
            SELECT * FROM (
                SELECT 
                    `coin`,
                    `price`,
                    `updated_date`,
                    'ONLINE PURCHASE' as type,
                    `user_id`,
                    CASE 
                        WHEN `transaction_type` = 1 THEN 'Manual'
                        WHEN `transaction_type` = 2 THEN 'Crypto'
                        ELSE 'INR'
                    END as transaction_type
                FROM `tbl_purchase`
                WHERE `payment` = 1
                
                UNION
                
                SELECT 
                    `coin`,
                    0 as price,
                    `added_date`,
                    IF(`bonus` = 1, 'BONUS', 'ADMIN PURCHASE') as type,
                    `user_id`,
                    'Admin' as transaction_type -- Set transaction_type as 'Admin' for tbl_wallet_log
                FROM `tbl_wallet_log`
            ) as a 
            WHERE `user_id` = '" . $user_id . "'
            ORDER BY `updated_date` DESC
        ");

        return $Query->result();
    }

    // public function View_Reffer($user_id)
    // {
    //     $Query = $this->db->where('isDeleted', false)
    //         ->where('referred_by', $user_id)
    //         ->get('tbl_users');
    //     return $Query->result();
    // }
    public function View_Reffer_Earn($user_id = '')
    {
        $this->db->select('tbl_referral_bonus_log.*,tbl_users.name,tbl_users.added_date,(SELECT COUNT(id)
         FROM `tbl_users` WHERE `referred_by`=tbl_referral_bonus_log.referred_user_id) as refer_count', false)
            ->from('tbl_users')
            ->join('tbl_referral_bonus_log', 'tbl_users.id=tbl_referral_bonus_log.referred_user_id', 'LEFT')
            ->where('tbl_users.isDeleted', false);

        if (!empty($user_id)) {
            $this->db->where('tbl_users.referred_by', $user_id);
        }

        $Query = $this->db->get();
        return $Query->result();
    }

    public function View_Reffer($user_id = '')
    {
        $this->db->select('tbl_referral_bonus_log.*,tbl_users.id as referred_user_id,tbl_users.id as id,tbl_users.added_date as added_date,IFNULL(tbl_referral_bonus_log.coin,0) as coin,tbl_users.referred_by as user_id,tbl_users.name,(SELECT COUNT(id)
         FROM `tbl_users` WHERE `referred_by`=tbl_referral_bonus_log.referred_user_id) as refer_count', false)
            ->from('tbl_users')
            ->join('tbl_referral_bonus_log', 'tbl_users.id=tbl_referral_bonus_log.referred_user_id', 'left')
            ->where('tbl_users.isDeleted', false);

        if (!empty($user_id)) {
            $this->db->where('tbl_users.referred_by', $user_id);
        }

        $Query = $this->db->get();
        return $Query->result();
    }

    //     public function View_Reffer($user_id = '')
//     {
//         $this->db->select('tbl_users.id,(select ROUND(sum(bonus),2) from tbl_bet_income_log where to_user_id="'.$user_id.'" AND DATE(added_date)="'.date('Y-m-d').'") as total_bonus,tbl_users.todays_bet as total_bet,tbl_users.todays_recharge as total_recharge,tbl_users.name,(SELECT COUNT(id)
//  FROM `tbl_users` WHERE `referred_by`=tbl_users.id) as refer_count', false)
//             ->from('tbl_users')
//             ->where('tbl_users.isDeleted', false);
//         if (!empty($user_id)) {
//             $this->db->where('tbl_users.referred_by', $user_id);
//         }

    //         $Query = $this->db->get();
//         return $Query->result();
//     }

    // public function View_Reffer($user_id = '')
    // {
    //     $this->db->select('tbl_referral_bonus_log.*,tbl_users.id as referred_user_id,tbl_users.id as id,tbl_users.added_date as added_date,IFNULL(tbl_referral_bonus_log.coin,0) as coin,tbl_users.referred_by as user_id,tbl_users.name,(SELECT COUNT(id)
    //      FROM `tbl_users` WHERE `referred_by`=tbl_referral_bonus_log.referred_user_id) as refer_count', false)
    //         ->from('tbl_users')
    //         ->join('tbl_referral_bonus_log', 'tbl_users.id=tbl_referral_bonus_log.referred_user_id','left')
    //         ->where('tbl_users.isDeleted', false);

    //     if (!empty($user_id)) {
    //         $this->db->where('tbl_users.referred_by', $user_id);
    //     }

    //     $Query = $this->db->get();
    //     return $Query->result();
    // }

    // public function View_Reffers($user_id,$user_ids = '')
    // {
    //     $this->db->select('tbl_referral_bonus_log.*,tbl_users.id as referred_user_id,tbl_users.id as id,tbl_users.added_date as added_date,IFNULL(tbl_referral_bonus_log.coin,0) as coin,tbl_users.referred_by as user_id,tbl_users.name,(SELECT COUNT(id)
    //      FROM `tbl_users` WHERE `referred_by`=tbl_referral_bonus_log.referred_user_id) as refer_count', false)
    //         ->from('tbl_users')
    //         ->join('tbl_referral_bonus_log', 'tbl_users.id=tbl_referral_bonus_log.referred_user_id','left')
    //         ->where('tbl_users.isDeleted', false);

    //     if (!empty($user_ids)) {
    //         $this->db->where_in('tbl_referral_bonus_log.referred_by', $user_ids);
    //     }
    //     // $this->db->where('tbl_referral_bonus_log.user_id',$user_id);
    //     $Query = $this->db->get();
    //     // echo $this->db->last_query();
    //     // exit;
    //     return $Query->result();
    // }

    public function View_Reffers($user_id, $user_ids = '')
    {
        $this->db->select('tbl_referral_bonus_log.*,tbl_users.name,(SELECT COUNT(id)
 FROM `tbl_users` WHERE `referred_by`=tbl_referral_bonus_log.referred_user_id) as refer_count', false)
            ->from('tbl_referral_bonus_log')
            ->join('tbl_users', 'tbl_users.id=tbl_referral_bonus_log.referred_user_id')
            ->where('tbl_users.isDeleted', false);

        if (!empty($user_ids)) {
            $this->db->where_in('tbl_referral_bonus_log.referred_user_id', $user_ids);
        }
        // $this->db->where('tbl_referral_bonus_log.user_id',$user_id);
        $this->db->order_by('tbl_referral_bonus_log.id', 'DESC'); // Order by id in descending order
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function get_levels_users($user_id)
    {
        $this->db->select('tbl_users.id');
        $this->db->from('tbl_users');
        $this->db->where('referred_by', $user_id);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get();
        return $query->result();
    }

    public function get_all_levels_users($user_ids)
    {
        $this->db->select('tbl_users.id');
        $this->db->from('tbl_users');
        $this->db->where_in('referred_by', $user_ids);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get();
        return $query->result();
    }

    public function Purchase_History()
    {
        $this->db->select('tbl_purchase.*,tbl_users.name');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_purchase.user_id');
        $this->db->where('tbl_purchase.payment', true);
        $this->db->where_in('tbl_purchase.transaction_type', [0, 3]);
        $this->db->where('tbl_purchase.isDeleted', false);
        $this->db->where('tbl_users.isDeleted', false);
        if ($this->session->role == 2) {
            $this->db->where('tbl_purchase.agent_id', $this->session->admin_id);
        }
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ManualPurchase_History($status = '')
    {
        $this->db->select('tbl_purchase.*,tbl_users.name');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_purchase.user_id');
        if ($this->session->role == 2) {
            $this->db->where('tbl_purchase.agent_id', $this->session->admin_id);
        }
        $this->db->where('tbl_purchase.transaction_type', 1);
        $this->db->where('tbl_purchase.status', $status);
        $this->db->where('tbl_purchase.isDeleted', false);
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function Purchase_History_Offline()
    {
        $Query = $this->db->select('tbl_wallet_log.*,tbl_users.name')
            ->from('tbl_wallet_log')
            ->join('tbl_users', 'tbl_users.id=tbl_wallet_log.user_id')
            ->where('tbl_users.user_type', 0)
            ->where('tbl_users.isDeleted', false)
            ->get();
        return $Query->result();
    }
    public function Purchase_History_Robot()
    {
        $Query = $this->db->select('tbl_wallet_log.*,tbl_users.name')
            ->from('tbl_wallet_log')
            ->join('tbl_users', 'tbl_users.id=tbl_wallet_log.user_id')
            ->where('tbl_users.user_type', 1)
            ->where('tbl_users.isDeleted', false)
            ->get();
        return $Query->result();
    }

    public function View_Purchase_Reffer($user_id = '')
    {
        $this->db->select('tbl_purcharse_ref.id,tbl_purcharse_ref.purchase_user_id as user_id,SUM(tbl_purcharse_ref.purchase_amount) as purchase_amount,SUM(tbl_purcharse_ref.coin) as coin,tbl_users.name', false)
            ->from('tbl_purcharse_ref')
            ->join('tbl_users', 'tbl_users.id=tbl_purcharse_ref.purchase_user_id')
            ->where('tbl_users.isDeleted', false)
            ->group_by('tbl_purcharse_ref.purchase_user_id')
            ->order_by('tbl_purcharse_ref.id', 'DESC');

        if (!empty($user_id)) {
            $this->db->where('tbl_purcharse_ref.user_id', $user_id);
        }

        $Query = $this->db->get();
        return $Query->result();
    }

    public function get_activation_data($user_id, $type = '', $purchase_user_id = '', $date = '')
    {
        $this->db->select('tbl_purcharse_ref.*');
        $this->db->from('tbl_purcharse_ref');
        $this->db->where('user_id', $user_id);
        if (!empty($type)) {
            $this->db->where('type', $type);
        }
        if (!empty($purchase_user_id)) {
            $this->db->where('purchase_user_id', $purchase_user_id);
        }
        if (!empty($date)) {
            $this->db->where('DATE(added_date)', date('Y-m-d', strtotime($date)));
        }
        $this->db->order_by('tbl_purcharse_ref.id', 'DESC'); // Order by id in descending order
        $query = $this->db->get();
        return $query->result();
    }

    public function View_Welcome_Reffer($user_id)
    {
        $Query = $this->db->select('tbl_welcome_ref.*,tbl_users.name')
            ->from('tbl_welcome_ref')
            ->join('tbl_users', 'tbl_users.id=tbl_welcome_ref.bonus_user_id')
            ->where('tbl_users.isDeleted', false)
            ->where('tbl_welcome_ref.user_id', $user_id)
            ->get();
        return $Query->result();
    }

    public function ActiveUser()
    {
        $Query = $this->db->select('tbl_users.*')
            ->from('tbl_users')
            ->where('tbl_users.isDeleted', false)
            ->where('DATE(tbl_users.updated_date)', date('Y-m-d'))
            ->order_by('tbl_users.id', 'desc')
            ->get();
        return $Query->result();
    }

    public function WalletAmount($user_id)
    {
        $this->db->select('tbl_ander_baher_bet.*,tbl_ander_baher.room_id');
        $this->db->from('tbl_ander_baher_bet');
        $this->db->join('tbl_ander_baher', 'tbl_ander_baher.id=tbl_ander_baher_bet.ander_baher_id');
        $this->db->where('tbl_ander_baher_bet.user_id', $user_id);
        $this->db->order_by('tbl_ander_baher_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function AnderBaharPlusAmount($user_id)
    {
        $this->db->select('tbl_ander_baher_plus_bet.*,tbl_ander_baher_plus.room_id');
        $this->db->from('tbl_ander_baher_plus_bet');
        $this->db->join('tbl_ander_baher_plus', 'tbl_ander_baher_plus.id=tbl_ander_baher_plus_bet.ander_baher_plus_id');
        $this->db->where('tbl_ander_baher_plus_bet.user_id', $user_id);
        $this->db->order_by('tbl_ander_baher_plus_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function DragonWalletAmount($user_id)
    {
        $this->db->select('tbl_dragon_tiger_bet.*,tbl_dragon_tiger.room_id');
        $this->db->from('tbl_dragon_tiger_bet');
        $this->db->join('tbl_dragon_tiger', 'tbl_dragon_tiger.id=tbl_dragon_tiger_bet.dragon_tiger_id');
        $this->db->where('tbl_dragon_tiger_bet.user_id', $user_id);
        $this->db->order_by('tbl_dragon_tiger_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function TeenPattiLog($user_id)
    {
        $Query = $this->db->query('
        SELECT 
    gl.game_id,
    SUM(gl.amount) AS invest,
    COALESCE(g.user_winning_amt, 0) AS winning_amount,
    COALESCE(g.admin_winning_amt, 0) AS admin_commission,
    MIN(gl.added_date) AS added_date,
    MAX(t.private) AS table_type
    FROM tbl_game_log AS gl
    JOIN tbl_game AS g ON gl.game_id = g.id
    JOIN tbl_table AS t ON g.table_id = t.id
    WHERE gl.user_id = ' . $user_id . '
    AND g.winner_id = ' . $user_id . '
    GROUP BY gl.game_id
    ORDER BY gl.game_id DESC');

        // $this->db->get();
        return $Query->result();
    }
    public function JhandiMundalog($user_id)
    {
        //     $Query = $this->db->query('
        //     SELECT tbl_jhandi_munda_bet.`jhandi_munda_id`,
        //         SUM(tbl_jhandi_munda_bet.`amount`) AS invest,
        //         IFNULL((SELECT winning_amount FROM `tbl_jhandi_munda` WHERE user_id=' . $user_id . ' AND id=`jhandi_munda_id`), 0) AS winning_amount,
        //         tbl_jhandi_munda_bet.added_date,
        //         tbl_table.private AS table_type
        //     FROM `tbl_jhandi_munda_bet`
        //     JOIN tbl_jhandi_munda ON tbl_jhandi_munda_bet.jhandi_munda_id=tbl_jhandi_munda.id
        //     JOIN tbl_table ON tbl_table.id=tbl_jhandi_munda.room_id
        //     WHERE tbl_jhandi_munda_bet.`user_id`=' . $user_id . '
        //     GROUP BY tbl_jhandi_munda_bet.`jhandi_munda_id`
        //     ORDER BY tbl_jhandi_munda_bet.`jhandi_munda_id` DESC
        // ');
        // echo $this->db->last_query();

        // $this->db->get();
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get('tbl_jhandi_munda_bet');
        return $Query->result();
    }

    public function Aviatorlog($user_id)
    {
        $this->db->select('tbl_aviator_bet.*,tbl_aviator.room_id');
        $this->db->from('tbl_aviator_bet');
        $this->db->join('tbl_aviator', 'tbl_aviator.id=tbl_aviator_bet.dragon_tiger_id');
        $this->db->where('tbl_aviator_bet.user_id', $user_id);
        $Query = $this->db->get();

        return $Query->result();
    }


    public function RummyLog($user_id)
    {
        //     $Query = $this->db->query('
        //     SELECT tbl_rummy_log.`game_id`,
        //         SUM(tbl_rummy_log.`amount`) AS invest,
        //         IFNULL((SELECT user_winning_amt FROM `tbl_rummy` WHERE winner_id='.$user_id.' AND id=`game_id`), 0) AS winning_amount,
        //         tbl_rummy_log.added_date,
        //         tbl_table.private AS table_type
        //     FROM `tbl_rummy_log`
        //     JOIN tbl_rummy ON tbl_rummy_log.game_id=tbl_rummy.id
        //     JOIN tbl_table ON tbl_table.id=tbl_rummy.table_id
        //     WHERE tbl_rummy_log.`user_id`='.$user_id.'
        //     GROUP BY tbl_rummy_log.`game_id`
        //     ORDER BY tbl_rummy_log.`game_id` DESC
        // ');

        $Query = $this->db->query('SELECT * FROM
        (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, amount , `user_winning_amt` as user_amount, `admin_winning_amt` as comission_amount,`added_date` FROM `tbl_rummy` WHERE  `winner_id`=' . $user_id . ') poker
        UNION
        SELECT `game_id`,`user_id`,`action`, 0 as user_winning_amt,`amount`, 0 as admin_winning_amt,`added_date` FROM `tbl_rummy_log` WHERE `amount`!=0 AND `user_id`=' . $user_id . '
        ORDER BY added_date DESC');

        return $Query->result();
    }

    public function Ludolog($user_id)
    {
        $Query = $this->db->query('SELECT * FROM
        (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, amount , `user_winning_amt` as user_amount, `admin_winning_amt` as comission_amount,`added_date` FROM `tbl_ludo` WHERE  `winner_id`=' . $user_id . ') ludo
        ORDER BY added_date DESC');
        // $this->db->get();
        return $Query->result();
    }

    public function Pokerlog($user_id)
    {
        $Query = $this->db->query('SELECT * FROM
        (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, amount , `user_winning_amt` as user_amount, `admin_winning_amt` as comission_amount,`updated_date` as added_date FROM `tbl_poker` WHERE  `winner_id`=' . $user_id . ') poker
        UNION
        (SELECT `game_id`,`user_id`,`action`,`amount`, 0 as user_winning_amt, 0 as admin_winning_amt,`added_date` FROM `tbl_poker_log` WHERE `amount`!=0 AND `user_id`=' . $user_id . ')
        ORDER BY added_date DESC');
        // $this->db->get();
        return $Query->result();
    }

    public function RummyDealLog($user_id)
    {
        // $Query = $this->db->query('SELECT * FROM
        // (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, winning_amount, user_amount, commission_amount,`updated_date` as added_date FROM `tbl_rummy_deal_table` WHERE  `winner_id`='.$user_id.') rummy
        // UNION
        // (SELECT `game_id`,`user_id`,`action`, 0 as user_winning_amt,-`amount`, 0 as admin_winning_amt,`added_date` FROM `tbl_rummy_deal_log` WHERE `amount`!=0 AND `user_id`='.$user_id.')
        // ORDER BY added_date DESC');
        $Query = $this->db->query('SELECT * FROM
        (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, winning_amount, user_amount, commission_amount,`updated_date` as added_date FROM `tbl_rummy_deal_table` WHERE  `winner_id`=' . $user_id . ') rummy
        UNION
        (SELECT tbl_rummy_deal.table_id as `game_id`,tbl_rummy_deal_log.`user_id`,tbl_rummy_deal_log.`action`, 0 as user_winning_amt,-tbl_rummy_deal_log.`amount`, 0 as admin_winning_amt,tbl_rummy_deal_log.`added_date` FROM `tbl_rummy_deal_log` JOIN tbl_rummy_deal ON tbl_rummy_deal_log.game_id=tbl_rummy_deal.id WHERE tbl_rummy_deal_log.`amount`!=0 AND tbl_rummy_deal_log.`user_id`=' . $user_id . ')
        ORDER BY added_date DESC;');
        // $this->db->get();
        return $Query->result();
        // $Query = $this->db->query('SELECT * FROM
        // (SELECT `game_id`,`user_id`,`action`,`amount`,`added_date` FROM `tbl_rummy_deal_log` WHERE `amount`!=0 AND `user_id`='.$user_id.'
        // UNION
        // SELECT `id`,`winner_id`,10,`user_winning_amt`,`added_date` FROM `tbl_rummy_deal` WHERE  `winner_id`='.$user_id.') rummy
        // ORDER BY added_date DESC');
        // // $this->db->get();
        // return $Query->result();
    }

    public function RummyPoolLog($user_id)
    {
        // $Query = $this->db->query('SELECT * FROM
        // (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, winning_amount , `user_amount`, `commission_amount`,`updated_date` as added_date FROM `tbl_rummy_pool_table` WHERE  `winner_id`='.$user_id.') rummy
        // UNION
        // (SELECT `game_id`,`user_id`,`action`,`amount` as winning_amount, 0 as user_amount, 0 as commission_amount,`added_date` FROM `tbl_rummy_pool_log` WHERE `amount`!=0 AND `user_id`='.$user_id.')
        // ORDER BY added_date DESC;');
        $Query = $this->db->query('SELECT * FROM
        (SELECT `id` as game_id,`winner_id` as user_id,"10" as action, winning_amount, user_amount, commission_amount,`updated_date` as added_date FROM `tbl_rummy_pool_table` WHERE  `winner_id`=' . $user_id . ') rummy
        UNION
        (SELECT tbl_rummy_pool.table_id as `game_id`,tbl_rummy_pool_log.`user_id`,tbl_rummy_pool_log.`action`, 0 as user_winning_amt,tbl_rummy_pool_log.`amount`, 0 as admin_winning_amt,tbl_rummy_pool_log.`added_date` FROM `tbl_rummy_pool_log` JOIN tbl_rummy_pool ON tbl_rummy_pool_log.game_id=tbl_rummy_pool.id WHERE tbl_rummy_pool_log.`amount`!=0 AND tbl_rummy_pool_log.`user_id`=' . $user_id . ')
        ORDER BY added_date DESC;');
        // $this->db->get();
        return $Query->result();
        // $Query = $this->db->query('SELECT * FROM
        // (SELECT `game_id`,`user_id`,`action`,`amount`,`added_date` FROM `tbl_rummy_pool_log` WHERE `amount`!=0 AND `user_id`='.$user_id.'
        // UNION
        // SELECT `id`,`winner_id`,10,`user_winning_amt`,`added_date` FROM `tbl_rummy_pool` WHERE  `winner_id`='.$user_id.') rummy
        // ORDER BY added_date DESC');
        // // $this->db->get();
        // return $Query->result();
    }

    public function SevenUpAmount($user_id)
    {
        $this->db->select('tbl_seven_up_bet.*,tbl_seven_up.room_id');
        $this->db->from('tbl_seven_up_bet');
        $this->db->join('tbl_seven_up', 'tbl_seven_up.id=tbl_seven_up_bet.seven_up_id');
        $this->db->where('tbl_seven_up_bet.user_id', $user_id);
        $this->db->order_by('tbl_seven_up_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function AndarBaharAmount($user_id)
    {
        $this->db->select('tbl_ander_baher_plus_bet.*,tbl_ander_baher_plus.room_id');
        $this->db->from('tbl_ander_baher_plus_bet');
        $this->db->join('tbl_ander_baher_plus', 'tbl_ander_baher_plus.id=tbl_ander_baher_plus_bet.ander_baher_plus_id');
        $this->db->where('tbl_ander_baher_plus_bet.user_id', $user_id);
        $this->db->order_by('tbl_ander_baher_plus_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function TargetAmount($user_id)
    {
        $this->db->select('tbl_target_bet.*,tbl_target.room_id');
        $this->db->from('tbl_target_bet');
        $this->db->join('tbl_target', 'tbl_target.id=tbl_target_bet.target_id');
        $this->db->where('tbl_target_bet.user_id', $user_id);
        $this->db->order_by('tbl_target_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ColorPredictionAmount($user_id)
    {
        $this->db->select('tbl_color_prediction_bet.*,tbl_color_prediction.room_id');
        $this->db->from('tbl_color_prediction_bet');
        $this->db->join('tbl_color_prediction', 'tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id');
        $this->db->where('tbl_color_prediction_bet.user_id', $user_id);
        $this->db->order_by('tbl_color_prediction_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ColorPrediction1MinAmount($user_id)
    {
        $this->db->select('tbl_color_prediction_1_min_bet.*,tbl_color_prediction_1_min.room_id');
        $this->db->from('tbl_color_prediction_1_min_bet');
        $this->db->join('tbl_color_prediction_1_min', 'tbl_color_prediction_1_min.id=tbl_color_prediction_1_min_bet.color_prediction_id');
        $this->db->where('tbl_color_prediction_1_min_bet.user_id', $user_id);
        $this->db->order_by('tbl_color_prediction_1_min_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ColorPrediction3MinAmount($user_id)
    {
        $this->db->select('tbl_color_prediction_3_min_bet.*,tbl_color_prediction_3_min.room_id');
        $this->db->from('tbl_color_prediction_3_min_bet');
        $this->db->join('tbl_color_prediction_3_min', 'tbl_color_prediction_3_min.id=tbl_color_prediction_3_min_bet.color_prediction_id');
        $this->db->where('tbl_color_prediction_3_min_bet.user_id', $user_id);
        $this->db->order_by('tbl_color_prediction_3_min_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function ColorPrediction5MinAmount($user_id)
    {
        $this->db->select('tbl_color_prediction_5_min_bet.*,tbl_color_prediction_5_min.room_id');
        $this->db->from('tbl_color_prediction_5_min_bet');
        $this->db->join('tbl_color_prediction_5_min', 'tbl_color_prediction_5_min.id=tbl_color_prediction_5_min_bet.color_prediction_id');
        $this->db->where('tbl_color_prediction_5_min_bet.user_id', $user_id);
        $this->db->order_by('tbl_color_prediction_5_min_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function CarRouletteAmount($user_id)
    {
        $this->db->select('tbl_car_roulette_bet.*,tbl_car_roulette.room_id');
        $this->db->from('tbl_car_roulette_bet');
        $this->db->join('tbl_car_roulette', 'tbl_car_roulette.id=tbl_car_roulette_bet.car_roulette_id');
        $this->db->where('tbl_car_roulette_bet.user_id', $user_id);
        $this->db->order_by('tbl_car_roulette_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function AnimalRouletteAmount($user_id)
    {
        $this->db->select('tbl_animal_roulette_bet.*,tbl_animal_roulette.room_id');
        $this->db->from('tbl_animal_roulette_bet');
        $this->db->join('tbl_animal_roulette', 'tbl_animal_roulette.id=tbl_animal_roulette_bet.animal_roulette_id');
        $this->db->where('tbl_animal_roulette_bet.user_id', $user_id);
        $this->db->order_by('tbl_animal_roulette_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function RouletteAmount($user_id)
    {
        $this->db->select('tbl_roulette_bet.*,tbl_roulette.room_id');
        $this->db->from('tbl_roulette_bet');
        $this->db->join('tbl_roulette', 'tbl_roulette.id=tbl_roulette_bet.roulette_id');
        $this->db->where('tbl_roulette_bet.user_id', $user_id);
        $this->db->order_by('tbl_roulette_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function JackpotAmount($user_id)
    {
        $this->db->select('tbl_jackpot_bet.*,tbl_jackpot.room_id');
        $this->db->from('tbl_jackpot_bet');
        $this->db->join('tbl_jackpot', 'tbl_jackpot.id=tbl_jackpot_bet.jackpot_id');
        $this->db->where('tbl_jackpot_bet.user_id', $user_id);
        $this->db->order_by('tbl_jackpot_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function Roulette($user_id)
    {
        $this->db->select('tbl_roulette_bet.*,tbl_roulette.room_id');
        $this->db->from('tbl_roulette_bet');
        $this->db->join('tbl_roulette', 'tbl_roulette.id=tbl_roulette_bet.roulette_id');
        $this->db->where('tbl_roulette_bet.user_id', $user_id);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function Poker($user_id)
    {
        // $this->db->select('*');
        // $this->db->from('tbl_poker');
        // $this->db->where('winner_id', $user_id);
        // $Query = $this->db->get();
        // return $Query->result();
        // $this->db->select('tbl_poker_log.*,tbl_poker.user_winning_amt,tbl_poker.admin_winning_amt, tbl_poker.poker_table_id');
        // $this->db->from('tbl_poker_log');
        // $this->db->join('tbl_poker', 'tbl_poker.id = tbl_poker_log.game_id');
        // $this->db->where('tbl_poker_log.user_id', $user_id);
        $Query = $this->db->query('
            SELECT tbl_poker_log.`game_id`,
                SUM(tbl_poker_log.`amount`) AS invest,
                IFNULL((SELECT user_winning_amt FROM `tbl_poker` WHERE winner_id=' . $user_id . ' AND id=`game_id`), 0) AS winning_amount,
                tbl_poker_log.added_date,
                tbl_poker_table.private AS table_type
            FROM `tbl_poker_log`
            JOIN tbl_poker ON tbl_poker_log.game_id=tbl_poker.id
            JOIN tbl_poker_table ON tbl_poker_table.id=tbl_poker.poker_table_id
            WHERE tbl_poker_log.`user_id`=' . $user_id . '
            GROUP BY tbl_poker_log.`game_id`
            ORDER BY tbl_poker_log.`game_id` DESC
        ');
        return $Query->result();
    }

    public function Aviator($user_id)
    {
        $this->db->select('tbl_aviator_bet.*,tbl_aviator.room_id');
        $this->db->from('tbl_aviator_bet');
        $this->db->join('tbl_aviator', 'tbl_aviator.id=tbl_aviator_bet.aviator_id');
        $this->db->where('tbl_aviator_bet.user_id', $user_id);
        $this->db->order_by('added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function HeadTailAmount($user_id)
    {
        $this->db->select('tbl_head_tail_bet.*,tbl_head_tail.room_id');
        $this->db->from('tbl_head_tail_bet');
        $this->db->join('tbl_head_tail', 'tbl_head_tail.id=tbl_head_tail_bet.head_tail_id');
        $this->db->where('tbl_head_tail_bet.user_id', $user_id);
        $this->db->order_by('tbl_head_tail_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function RedBlack($user_id)
    {
        $this->db->select('tbl_red_black_bet.*,tbl_red_black.room_id');
        $this->db->from('tbl_red_black_bet');
        $this->db->join('tbl_red_black', 'tbl_red_black.id=tbl_red_black_bet.red_black_id');
        $this->db->where('tbl_red_black_bet.user_id', $user_id);
        $this->db->order_by('tbl_red_black_bet.added_date', 'DESC');
        $Query = $this->db->get();

        return $Query->result();
    }

    public function BaccaratLog($user_id)
    {
        $this->db->select('tbl_baccarat_bet.*,tbl_baccarat.room_id');
        $this->db->from('tbl_baccarat_bet');
        $this->db->join('tbl_baccarat', 'tbl_baccarat.id=tbl_baccarat_bet.baccarat_id');
        $this->db->where('tbl_baccarat_bet.user_id', $user_id);
        $this->db->order_by('tbl_baccarat_bet.added_date', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function JhandiMunda($user_id)
    {
        $this->db->select('tbl_jhandi_munda_bet.*,tbl_jhandi_munda.room_id');
        $this->db->from('tbl_jhandi_munda_bet');
        $this->db->join('tbl_jhandi_munda', 'tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id');
        $this->db->where('tbl_jhandi_munda_bet.user_id', $user_id);
        $this->db->order_by('tbl_jhandi_munda_bet.added_date', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function getHistory($user_id)
    {
        $this->db->select('tbl_ludo.*,tbl_users.name');
        $this->db->from('tbl_ludo');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_ludo.winner_id');
        $this->db->where('tbl_ludo.winner_id', $user_id);
        $Query = $this->db->get();
        return $Query->result();

    }

    public function UpdateOfflineUsers()
    {
        $this->db->query('UPDATE `tbl_users` SET `ander_bahar_room_id`=0,`dragon_tiger_room_id`=0,`jackpot_room_id`=0,`seven_up_room_id`=0,`color_prediction_room_id`=0,`car_roulette_room_id`=0,`animal_roulette_room_id`=0 WHERE TIME_TO_SEC(TIMEDIFF(NOW(), updated_date))>30');
        return $this->db->affected_rows();
    }

    public function getOnlineUsers($room_id, $game_column)
    {
        $this->db->where($game_column . '>', 0);
        $this->db->where('tbl_users.isDeleted', false);
        $Query = $this->db->get('tbl_users');
        return $Query->num_rows();
    }

    public function GetUsers($postData = null, $id = '', $role = '')
    {
        // print_r($postData);
        // die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        // $min = !empty($postData['min'])?$postData['min']:date('Y-m-d');
        // $max = !empty($postData['max'])?$postData['max']:date('Y-m-d');
        $active = $postData['active']; // New parameter for active filter
        $min = $postData['min'];
        $max = $postData['max'];
        $start = $postData['start'];
        $rowperpage = ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') ? 10 : $postData['length'];
        // Limiting to 10 records if environment is "demo"
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'desc');
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
            $this->db->limit(10);


        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_users.added_date) >=', $min);
            $this->db->where('DATE(tbl_users.added_date) <=', $max);
        }
        // echo $this->db->last_query();
        // $totalRecords = $this->db->get()->num_rows();
        $totalRecords = $this->db->count_all_results();

        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
            $this->db->limit(10);
        }
        $this->db->order_by('tbl_users.id', 'desc');
        // echo $this->db->last_query();
        // $this->db->where($defaultWhere);
        if ($active == '1') {
            $this->db->where('DATE(tbl_users.updated_date)', date('Y-m-d'));
        }
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            // $this->db->like('tbl_admin.first_name', $searchValue, 'after');
            $this->db->like('tbl_users.id', $searchValue, 'after');
            $this->db->like('tbl_users.profile_pic', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();
        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_users.added_date) >=', $min);
            $this->db->where('DATE(tbl_users.added_date) <=', $max);
        }

        // $totalRecordwithFilter = $this->db->get()->num_rows();
        $totalRecordwithFilter = $this->db->count_all_results();

        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->join('tbl_admin', 'tbl_users.created_by=tbl_admin.id', 'LEFT');

        $this->db->where('tbl_users.isDeleted', false);
        // $this->db->where('tbl_users.created_by', $id);
        if ($active == '1') {
            $this->db->where('DATE(tbl_users.updated_date)', date('Y-m-d'));
        }
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.id', $searchValue, 'after');
            $this->db->or_like('tbl_users.profile_pic', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.bind_device', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();

        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_users.added_date) >=', $min);
            $this->db->where('DATE(tbl_users.added_date) <=', $max);
        }
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
            $status = '<select class="form-control" onchange="ChangeStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->status == 0) ? 'selected' : '') . '>Active</option>
            <option value="1" ' . (($record->status == 1) ? 'selected' : '') . '>Block</option>
        </select>';
            $bet_lock_status = '<select class="form-control" onchange="changeBetLockStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->bet_lock_status == 0) ? 'selected' : '') . '>UnLock</option>
            <option value="1" ' . (($record->bet_lock_status == 1) ? 'selected' : '') . '>lock</option>
        </select>';
            $action = '<a href="' . base_url('backend/user/view/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="View Logs"><span
            class="fa fa-eye"></span></a>
            | <a href="' . base_url('backend/user/LadgerReports/' . $record->id) . '" class="btn btn-info"
            data-toggle="tooltip" data-placement="top" title="View Ladger Report"><span class="ti-wallet"></span></a>
    | <a href="' . base_url('backend/user/edit/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="Add Wallet"><span
            class="fa fa-credit-card" ></span></a>

    | <a href="' . base_url('backend/user/edit_wallet/' . $record->id) . '" class="btn btn-danger"
        data-toggle="tooltip" data-placement="top" title="Deduct Wallet"><span
            class="fa fa-credit-card" ></span></a>

            | <a href="' . base_url('backend/user/edit_user/' . $record->id) . '" class="btn btn-info"
        data-toggle="tooltip" data-placement="top" title="Edit"><span
            class="fa fa-edit" ></span></a>';

            $data[] = array(
                "id" => $i,
                "name" => $record->name,
                "mobile" => ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') ? "9892552468" : $record->mobile,
                "ID" => $record->id,
                "profile_pic" => '<img src="' . base_url('data/post/' . $record->profile_pic) . '" height="75px" width="75px" >',
                "bank_detail" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->bank_detail : "",
                "adhar_card" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->adhar_card : "",
                "upi" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->upi : "",
                "email" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->email : "",
                "user_type" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? ($record->user_type == 1 ? 'BOT' : 'REAL') : "",
                "user_category" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->user_category : "",
                "wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->wallet : "",
                "winning_wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->winning_wallet : "",
                "unutilized_wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->unutilized_wallet : "",
                "bonus_wallet" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $record->bonus_wallet : "",
                "on_table" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? (($record->table_id > 0 || $record->rummy_table_id > 0 || $record->ander_bahar_room_id > 0 || $record->dragon_tiger_room_id > 0 || $record->seven_up_room_id > 0 || $record->jhandi_munda_id > 0) ? 'Yes' : 'No') : "",
                "status" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $status : "",
                "added_date" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? date("d-m-Y h:i:s A", strtotime($record->added_date)) : "",
                "action" => ($_ENV['ENVIRONMENT'] != 'demo' || $_ENV['ENVIRONMENT'] != 'fame') ? $action : "",
            );

            $i++;
        }

        ## Response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
        );

        return $response;
        // ################## delete button backup ###################
        //     | <a href="' . base_url('backend/user/delete/' . $record->id) . '" class="btn btn-danger"
        // data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm(\'Are You Sure Want To Delete ' . $record->name . '?\')"><span
        //     class="fa fa-trash" ></span></a>
    }


    public function GetLadgerReports($id, $postData = null)
    {
        $response = array();
        # Total number of records without filtering
        $totalRecordwithoutFilter = $this->TotalRecordsWithoutFilter($id, $postData);
        # Total number of records with filtering
        $totalRecordwithFilter = $this->TotalRecordsWithFilter($id, $postData);
        $records = $this->GetAllLogs($id, $postData);
        $data = array();
        $start = $postData['start'];
        $draw = $postData['draw'];
        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        $total = $records[0]->user_wallet;
        foreach ($records as $record) {
            $amount = $record->winning_amount - $record->amount;
            $total = $total + $amount;
            $data[] = array(
                "id" => $i,
                "game" => $record->game,
                "amount" => $amount,
                "wallet" => $total,
                "added_date" => date("d-m-Y h:i:s A", strtotime($record->added_date)),
            );
            $i++;
        }

        ## Response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithoutFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
        );

        return $response;
    }

    public function deductWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->where('id', $user_id);
        $Query = $this->db->get('tbl_users');
        $userData = $Query->row();

        $this->db->set('wallet', 'wallet - ' . $amount, false);

        //$this->db->set('winning_wallet', 'winning_wallet+' . $amount, false);
        if ($bonus == 1) {
            $this->db->set('bonus_wallet', 'bonus_wallet-' . $amount, false);
        } else {
            if ($amount > $userData->winning_wallet) {
                $this->db->set('bonus_wallet', 'bonus_wallet-' . $amount, false);
            } else {
                $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
            }
        }

        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
        return true;
    }

    public function TotalRecordsWithFilter($id, $postData)
    {
        ## Read value
        $draw = $postData['draw'];
        $start = $postData['start'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value
        $sql = 'SELECT main_table.*,tbl_users.wallet as user_wallet FROM (SELECT 
   "Andar Bahar" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_ander_baher_bet where user_id="' . $id . '"
    UNION
   SELECT 
   "Dragon & Tiger" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_dragon_tiger_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Baccarat" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_baccarat_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Seven Up Down" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_seven_up_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Car Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_car_roulette_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Color Predection" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_color_prediction_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Animal Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_animal_roulette_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Head Tail" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_head_tail_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Red Vs Black" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_red_black_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Dragon & Tiger" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_jhandi_munda_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_roulette_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Poker" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_poker where winner_id="' . $id . '"
   UNION
   SELECT 
   "Teen Patti" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_game where winner_id="' . $id . '"
   UNION
   SELECT 
   "JackPot" as game,user_id,winning_amount,added_date,amount,user_amount
   FROM tbl_jackpot_bet where user_id="' . $id . '"
   UNION
   SELECT 
   "Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_rummy where winner_id="' . $id . '"
   UNION
   SELECT 
   "Deal Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_rummy_deal where winner_id="' . $id . '"
   UNION
   SELECT 
   "Pool Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_rummy_pool where winner_id="' . $id . '"
   UNION
   SELECT 
   "Ludo" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
   FROM tbl_ludo where winner_id="' . $id . '"
   UNION
   SELECT 
   "Wallet Log" as game,user_id,"0" as winning_amount,added_date,coin as amount,"" as user_amount
   FROM tbl_wallet_log where user_id="' . $id . '"
   UNION
   SELECT 
   "Purchase" as game,user_id,"0" as winning_amount,added_date,coin as amount,"" as user_amount
   FROM tbl_purchase where user_id="' . $id . '"
   ) as main_table join tbl_users on tbl_users.id=main_table.user_id Where tbl_users.isDeleted=0';
        if ($searchValue) {
            $sql .= ' and game like "%' . $searchValue . '%"';
        }
        $query = $this->db->query($sql);
        // $this->db->where($defaultWhere);

        return $totalRecordwithFilter = $query->num_rows();
    }
    public function TotalRecordsWithoutFilter($id, $postData)
    {
        $query = $this->db->query('SELECT main_table.*,tbl_users.wallet as user_wallet FROM (SELECT 
        "Andar Bahar" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_ander_baher_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Dragon & Tiger" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_dragon_tiger_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Baccarat" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_baccarat_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Seven Up Down" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_seven_up_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Car Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_car_roulette_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Color Predection" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_color_prediction_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Animal Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_animal_roulette_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Head Tail" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_head_tail_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Red Vs Black" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_red_black_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Dragon & Tiger" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_jhandi_munda_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Roulette" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_roulette_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Poker" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_poker where winner_id="' . $id . '"
        UNION
        SELECT 
        "Teen Patti" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_game where winner_id="' . $id . '"
        UNION
        SELECT 
        "JackPot" as game,user_id,winning_amount,added_date,amount,user_amount
        FROM tbl_jackpot_bet where user_id="' . $id . '"
        UNION
        SELECT 
        "Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_rummy where winner_id="' . $id . '"
        UNION
        SELECT 
        "Deal Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_rummy_deal where winner_id="' . $id . '"
        UNION
        SELECT 
        "Pool Rummy" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_rummy_pool where winner_id="' . $id . '"
        UNION
        SELECT 
        "Ludo" as game,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,"" as user_amount
        FROM tbl_ludo where winner_id="' . $id . '"
        UNION
        SELECT 
        "Wallet Log" as game,user_id,coin as winning_amount,added_date,0 as amount,"" as user_amount
        FROM tbl_wallet_log where user_id="' . $id . '"
        UNION
        SELECT 
        "Purchase" as game,user_id,coin as winning_amount,added_date,"0" as amount,"" as user_amount
        FROM tbl_purchase where user_id="' . $id . '"
        ) as main_table join tbl_users on tbl_users.id=main_table.user_id');

        return $query->num_rows();
    }

    public function GetAllLogs($id, $postData = [])
    {
        ## Read value
        //    $draw = $postData['draw'];
        //    $start = $postData['start'];
        //    $rowperpage = $postData['length']; // Rows display per page
        //    $columnIndex = $postData['order'][0]['column']; // Column index
        //    $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        //    $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        //    $searchValue = $postData['search']['value']; // Search value
        $sql = 'SELECT main_table.*,tbl_users.wallet as user_wallet FROM (
    SELECT "Rummy" as game, `game_id` as reff_id, id as bet_id, `user_id`, `amount` as winning_amount, added_date, amount, 0 as user_amount, 0 as is_game FROM `tbl_rummy_log` WHERE user_id="' . $id . '" AND `amount`!=0
    UNION
    SELECT "Ludo" as game, `ludo_table_id` as reff_id, id as bet_id, `user_id`, `game_amount` as winning_amount, added_date, game_amount, 0 as user_amount, 0 as is_game FROM `tbl_ludo_table_user` WHERE user_id="' . $id . '" AND `game_amount`!=0
    UNION
    SELECT "Teen Patti" as game, `game_id` as reff_id, id as bet_id, `user_id`, `amount` as winning_amount, added_date, amount, 0 as user_amount, 0 as is_game FROM `tbl_game_log` WHERE user_id="' . $id . '" AND `amount`!=0
    UNION
    SELECT "Poker" as game, `game_id` as reff_id, id as bet_id, `user_id`, `amount` as winning_amount, added_date, amount, 0 as user_amount, 0 as is_game FROM `tbl_poker_log` WHERE user_id="' . $id . '" AND `amount`!=0
    UNION
    SELECT "Deal Rummy Entry" as game, tbl_rummy_deal.table_id as reff_id, tbl_rummy_deal.table_id as bet_id, `user_id`, tbl_rummy_deal_log.`amount` as winning_amount, tbl_rummy_deal_log.added_date, tbl_rummy_deal_log.amount, 0 as user_amount, 0 as is_game FROM `tbl_rummy_deal_log` JOIN tbl_rummy_deal ON tbl_rummy_deal.id=tbl_rummy_deal_log.game_id WHERE user_id="' . $id . '" AND tbl_rummy_deal_log.`amount`!=0
    UNION
    SELECT "Pool Rummy Entry" as game, tbl_rummy_pool.table_id as reff_id, tbl_rummy_pool.table_id as bet_id, `user_id`, tbl_rummy_pool_log.`amount` as winning_amount, tbl_rummy_pool_log.added_date, tbl_rummy_pool_log.amount, 0 as user_amount, 0 as is_game FROM `tbl_rummy_pool_log` JOIN tbl_rummy_pool ON tbl_rummy_pool.id=tbl_rummy_pool_log.game_id WHERE user_id="' . $id . '" AND tbl_rummy_pool_log.`amount`!=0
    UNION
    SELECT 
    "Andar Bahar" as game,ander_baher_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_ander_baher_bet where user_id="' . $id . '"
    UNION
    SELECT
    "Aviator" as game,dragon_tiger_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_aviator_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Andar Bahar Plus" as game,ander_baher_plus_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_ander_baher_plus_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Dragon & Tiger" as game,dragon_tiger_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_dragon_tiger_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Baccarat" as game,baccarat_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_baccarat_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Seven Up Down" as game,seven_up_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_seven_up_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Target" as game,target_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_target_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Car Roulette" as game,car_roulette_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_car_roulette_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Color Predection" as game,color_prediction_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_color_prediction_1_min_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Animal Roulette" as game,animal_roulette_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_animal_roulette_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Head Tail" as game,head_tail_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_head_tail_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Red Vs Black" as game,red_black_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_red_black_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Dragon & Tiger" as game,dragon_tiger_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_dragon_tiger_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Roulette" as game,id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_roulette_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Jhandi Munda" as game,jhandi_munda_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_jhandi_munda_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Poker Win" as game,id as reff_id,id as bet_id,winner_id as user_id,user_winning_amt as winning_amount,added_date,amount,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_poker where winner_id="' . $id . '"
    UNION
    SELECT 
    "Teen Patti Win" as game,id as reff_id,id as bet_id,winner_id as user_id,user_winning_amt as winning_amount,updated_date as added_date,amount,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_game where winner_id="' . $id . '"
    UNION
    SELECT 
    "Ludo Win" as game,ludo_table_id as reff_id,id as bet_id,winner_id as user_id,user_winning_amt as winning_amount,updated_date as added_date,amount,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_ludo where winner_id="' . $id . '"
    UNION
    SELECT 
    "JackPot" as game,jackpot_id as reff_id,id as bet_id,user_id,winning_amount,added_date,amount,user_amount, 1 as is_game
    FROM tbl_jackpot_bet where user_id="' . $id . '"
    UNION
    SELECT 
    "Rummy Win" as game,id as reff_id,id as bet_id,winner_id as user_id,user_winning_amt as winning_amount,updated_date as added_date,amount,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_rummy where winner_id="' . $id . '"
    UNION
    SELECT 
    "Deal Rummy Win" as game,id as reff_id,id as bet_id,winner_id as user_id,user_amount as winning_amount,updated_date as added_date,winning_amount as amount,user_amount, 1 as is_game
    FROM tbl_rummy_deal_table where winner_id="' . $id . '"
    UNION
    SELECT 
    "Pool Rummy Win" as game,id as reff_id,id as bet_id,winner_id as user_id,user_amount as winning_amount,updated_date as added_date,winning_amount as amount,user_amount, 1 as is_game
    FROM tbl_rummy_pool_table where winner_id="' . $id . '"
    UNION
    SELECT 
    tbl_admin.first_name as game,tbl_wallet_log.id as reff_id,tbl_wallet_log.id as bet_id,user_id,"" as winning_amount,tbl_wallet_log.added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_wallet_log join tbl_admin on tbl_wallet_log.added_by=tbl_admin.id where user_id="' . $id . '"
    UNION
    SELECT 

    "Purchase" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount,added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_purchase where payment=1 and user_id="' . $id . '"
    UNION
    SELECT 
    "Welcome Bonus" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount,added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_welcome_log where user_id="' . $id . '"
    UNION
    SELECT 
    "Withdrawl" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount,created_date as added_date,coin as amount,"0" as user_amount, 0 as is_game
    FROM tbl_withdrawal_log where (status=0 OR status=1) and user_id="' . $id . '"
    UNION
    SELECT 
    "Tip" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount, added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_tip_log where user_id="' . $id . '"
    UNION
    SELECT 
    "Refferal Bonus" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount, added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_referral_bonus_log where user_id="' . $id . '"
    UNION
    SELECT 
    "Extra Bonus" as game,id as reff_id,id as bet_id,user_id,"0" as winning_amount, added_date,"0" as amount,coin as user_amount, 0 as is_game
    FROM tbl_extra_wallet_log where user_id="' . $id . '"
    ) as main_table join tbl_users on tbl_users.id=main_table.user_id where tbl_users.isDeleted=0 ';

        //    if ($searchValue) {
        //     $sql .= ' and game like "%' . $searchValue . '%"';
        // }
        $sql .= ' order by added_date desc limit 100';
        // $sql.=' order by '.$columnName.' '.$columnSortOrder;
        // $sql.=' limit '.$start.','.$rowperpage.'';
        $query = $this->db->query($sql);
        // $this->db->order_by($columnName, $columnSortOrder);
        return $records = $query->result();
    }


    public function ActiveUserlist($postData = null)
    {

        // print_r($_GET);die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        $start = $postData['start'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'asc');
        $totalRecords = $this->db->get()->num_rows();

        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by('tbl_users.id', 'asc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();
        $this->db->select('tbl_users.*,tbl_user_category.name as user_category');
        $this->db->from('tbl_users');
        $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.mobile', $searchValue, 'after');
            $this->db->or_like('tbl_users.bank_detail', $searchValue, 'after');
            $this->db->or_like('tbl_users.adhar_card', $searchValue, 'after');
            $this->db->or_like('tbl_users.upi', $searchValue, 'after');
            $this->db->or_like('tbl_users.email', $searchValue, 'after');
            $this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            $this->db->or_like('tbl_users.wallet', $searchValue, 'after');
            $this->db->or_like('tbl_users.added_date', $searchValue, 'after');
            $this->db->group_end();
        }
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
            $status = '<select class="form-control" onchange="ChangeStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->status == 0) ? 'selected' : '') . '>Active</option>
            <option value="1" ' . (($record->status == 1) ? 'selected' : '') . '>Block</option>
        </select>';
            $action = '<a href="' . base_url('backend/user/view/' . $record->id) . '" class="btn btn-info"
            data-toggle="tooltip" data-placement="top" title="View Wins"><span
                class="fa fa-eye"></span></a>
                | <a href="' . base_url('backend/user/LadgerReports/' . $record->id) . '" class="btn btn-info"
                data-toggle="tooltip" data-placement="top" title="View Ladger Report"><span class="ti-wallet"></span></a>
        | <a href="' . base_url('backend/user/edit/' . $record->id) . '" class="btn btn-info"
            data-toggle="tooltip" data-placement="top" title="Edit"><span
                class="fa fa-credit-card" ></span></a>
        | <a href="' . base_url('backend/user/edit_user/' . $record->id) . '" class="btn btn-info"
            data-toggle="tooltip" data-placement="top" title="Edit"><span
                class="fa fa-edit" ></span></a>
        | <a href="' . base_url('backend/user/delete/' . $record->id) . '" class="btn btn-danger"
            data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm(\'Are You Sure Want To Delete ' . $record->name . '?\')"><span
                class="fa fa-trash" ></span></a>';
            $data[] = array(
                "id" => $i,
                "name" => $record->name,
                "bank_detail" => $record->bank_detail,
                "adhar_card" => $record->adhar_card,
                "upi" => $record->upi,
                "mobile" => ($record->mobile == '') ? $record->email : $record->mobile,
                "user_type" => $record->user_type == 1 ? 'BOT' : 'REAL',
                "user_category" => $record->user_category,
                "wallet" => $record->wallet,
                "winning_wallet" => $record->winning_wallet,
                "on_table" => ($record->table_id > 0) ? 'Yes' : 'No',
                "status" => $status,
                "added_date" => date("d-m-Y", strtotime($record->added_date)),
                "action" => $action,
            );
            $i++;
        }

        ## Response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
        );

        return $response;
    }

    public function Insert($data)
    {
        $this->db->insert('tbl_purchase', $data);
        return $this->db->insert_id();
    }

    public function PurchaseChangeStatus($id, $status)
    {
        if ($this->session->role == 2) {
            $this->db->where('tbl_purchase.agent_id', $this->session->admin_id);
        }
        $this->db->where('id', $id);
        $this->db->set('status', $status);
        $this->db->update('tbl_purchase');

        if ($status == 1) {
            $query = $this->db->where('isDeleted', FALSE)
                ->where('id', $id)
                ->get('tbl_purchase')
                ->row();

            $this->db->set('payment', 1);
            if ($this->session->role == 2) {
                $this->db->where('tbl_purchase.agent_id', $this->session->admin_id);
            }
            $this->db->where('id', $id);
            $this->db->update('tbl_purchase');

            $this->db->set('wallet', "wallet + $query->coin", FALSE)
                ->set('unutilized_wallet', "unutilized_wallet + $query->coin", FALSE)
                ->set('todays_recharge', "todays_recharge + $query->coin", FALSE)
                ->set('updated_date', date('Y-m-d H:i:s'))
                ->where('id', $query->user_id)
                ->update('tbl_users');
        }
        return $this->db->last_query();
    }

    public function ludo_winners($table_id)
    {
        $this->db->select('tbl_ludo_table.room_code,tbl_ludo_table.boot_value as amount,tbl_users.name as winner,tbl_ludo.winner_id,(select name from tbl_users join tbl_ludo_table_user on tbl_users.id=tbl_ludo_table_user.user_id where tbl_ludo_table_user.user_id!=tbl_ludo.winner_id and tbl_ludo_table_user.ludo_table_id=tbl_ludo_table.id limit 1 ) as lost');
        $this->db->from('tbl_ludo');
        $this->db->join('tbl_ludo_table', 'tbl_ludo.ludo_table_id=tbl_ludo_table.id');
        $this->db->join('tbl_users', 'tbl_ludo.winner_id=tbl_users.id');
        // $this->db->group_by('tbl_ludo_table_user.ludo_table_id');
        $this->db->where('tbl_ludo_table.invite_code', $table_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function get_purchase_data($user_id)
    {
        $this->db->select('tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        // $this->db->where('payment', 1);
        $query = $this->db->get();
        return $query->result();
    }

    public function getNotification()
    {
        $this->db->where('isDeleted', 0);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get('tbl_notification');
        return $Query->result();
    }

    public function GetUserByOrderTxnId($txn_id)
    {
        $this->db->select('tbl_users.name,tbl_users.mobile,tbl_users.profile_pic,tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_purchase.user_id=tbl_users.id');
        $this->db->where('tbl_purchase.razor_payment_id', $txn_id);
        $Query = $this->db->get();

        return $Query->result();
    }

    public function getUserStatement($user_id, $user_type = 0, $limit = 0, $offset = 0)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('user_type', $user_type);
        $this->db->order_by('id', 'desc');
        if ($limit > 0) {
            $this->db->limit($limit, (int) $offset * (int) $limit);
        }
        $query = $this->db->get('tbl_statement');
        return $query->result();
    }

    function AllLadgerReport($postData = null)
    {
        // print_r($_GET);die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        $start = $postData['start'];
        $min = $postData['min'];
        $max = $postData['max'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_statement.*');
        $this->db->from('tbl_statement');
        $this->db->order_by('tbl_statement.id', 'asc');
        $totalRecords = $this->db->get()->num_rows();

        $this->db->select('tbl_statement.*');
        $this->db->from('tbl_statement');
        $this->db->order_by('tbl_statement.id', 'asc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_statement.added_date', $searchValue, 'after');
            $this->db->like('tbl_statement.amount', $searchValue, 'after');
            $this->db->or_like('tbl_statement.user_id', $searchValue, 'after');
            $this->db->or_like('tbl_statement.source_id', $searchValue, 'after');
            $this->db->or_like('tbl_statement.amount', $searchValue, 'after');
            $this->db->or_like('tbl_statement.current_wallet', $searchValue, 'after');
            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();
        $this->db->select('tbl_statement.*');
        $this->db->from('tbl_statement');
        $this->db->order_by('id', 'DESC');

        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_statement.added_date', $searchValue, 'after');
            $this->db->like('tbl_statement.amount', $searchValue, 'after');
            $this->db->or_like('tbl_statement.user_id', $searchValue, 'after');
            $this->db->or_like('tbl_statement.source_id', $searchValue, 'after');
            $this->db->or_like('tbl_statement.amount', $searchValue, 'after');
            $this->db->or_like('tbl_statement.current_wallet', $searchValue, 'after');

            $this->db->group_end();
        }

        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_statement.added_date) >=', $min);
            $this->db->where('DATE(tbl_statement.added_date) <=', $max);
        }
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();

        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {

            $data[] = array(
                "id" => $i,
                "user_id" => $record->user_id,
                "source_id" => $record->source_id,
                "source" => $record->source,
                "wallet" => ($record->amount < 0) ? $record->current_wallet . '(<span style="color:red">' . $record->amount . '</span>)' : $record->current_wallet . '(<span style="color:green">+' . $record->amount . '</span>)',
                "added_date" => date("d-m-y h:i:s A", strtotime($record->added_date)),
                //"action"=>$action,
            );
            $i++;
        }
        //echo '<pre>';print_r($data);die;
        ## Response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
        );

        return $response;
    }

    public function DeleteUser($mobile)
    {
        $data = [
            'isDeleted' => 1
        ];
        $this->db->where('mobile', $mobile);
        $this->db->update('tbl_users', $data);
        return $this->db->affected_rows();
    }

    public function transfer_amount($reciever_id, $amount, $user_id)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('unutilized_wallet', 'unutilized_wallet+' . $amount, false);
        $this->db->where('id', $reciever_id);
        $this->db->update('tbl_users');

        $this->db->set('wallet', 'wallet-' . $amount, false);
        $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        $this->db->insert('tbl_transfer_wallet', ['sender_id' => $user_id, 'reciever_id' => $reciever_id, 'amount' => $amount, 'added_date' => date('Y-m-d H:i:s')]);
        return $this->db->insert_id();
    }

    public function get_total_withdraw($user_id)
    {
        $this->db->select('SUM(coin) as total_withdrawn');
        $this->db->from('tbl_withdrawal_log');
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 1);
        $query = $this->db->get();
        return $query->row()->total_withdrawn;
    }

    public function get_total_recharge($user_id)
    {
        $this->db->select('SUM(coin) as total_recharge');
        $this->db->from('tbl_purchase');
        $this->db->where('user_id', $user_id);
        $this->db->where('payment', 1);
        $query = $this->db->get();
        return $query->row()->total_recharge;
    }

    public function createTicket($data)
    {
        $this->db->insert('tbl_tickets', $data);
        return $this->db->insert_id();
    }
    public function getTickets($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get('tbl_tickets');
        return $Query->result();
    }
    public function get_salary_bonus_data($user_id)
    {
        $this->db->select('tbl_daily_salary_bonus.*');
        $this->db->from('tbl_daily_salary_bonus');
        $this->db->where('to_user_id', $user_id);
        $this->db->order_by('tbl_daily_salary_bonus.id', 'DESC'); // Order by id in descending order
        $query = $this->db->get();
        return $query->result();
    }
    public function get_bet_commission_log($user_id)
    {
        $this->db->select('tbl_bet_income_log.*,tbl_users.name');
        $this->db->from('tbl_bet_income_log');
        $this->db->join('tbl_users', 'tbl_bet_income_log.bet_user_id=tbl_users.id');
        $this->db->where('tbl_bet_income_log.to_user_id', $user_id);
        $this->db->order_by('tbl_bet_income_log.id', 'DESC'); // Order by id in descending order
        $query = $this->db->get();
        return $query->result();
    }

    public function totalDeposit($user_ids, $filter_id = '')
    {
        // Get yesterday's date
        $today_date = date('Y-m-d');
        // Select the sum of amounts where user_id is $user_id, level is 1, and the date is yesterday's date
        $this->db->select_sum('coin');
        $this->db->from('tbl_purchase');
        if (!empty($user_ids)) {
            $this->db->where_in('user_id', $user_ids);
        }

        if (!empty($filter_id)) {
            $this->db->where('user_id', $filter_id);
        }
        $this->db->where('payment', 1);
        $this->db->where('DATE(added_date)', $today_date);
        $query = $this->db->get();
        $result = $query->row();
        return $result->coin ? round($result->coin, 2) : 0;
    }
    public function totalDepositNumber($user_ids, $filter_id = '')
    {
        // Get yesterday's date
        $today_date = date('Y-m-d');
        // Select the sum of amounts where user_id is $user_id, level is 1, and the date is yesterday's date
        $this->db->from('tbl_purchase');
        if (!empty($user_ids)) {
            $this->db->where_in('user_id', $user_ids);
        }
        if (!empty($filter_id)) {
            $this->db->where('user_id', $filter_id);
        }
        $this->db->where('payment', 1);
        $this->db->where('DATE(added_date)', $today_date);
        $query = $this->db->get();
        return $query->num_rows();
    }
    public function totalBetAmount($user_ids, $filter_id = '')
    {
        // Get yesterday's date
        $today_date = date('Y-m-d');
        // Select the sum of amounts where user_id is $user_id, level is 1, and the date is yesterday's date
        $this->db->select_sum('todays_bet');
        $this->db->from('tbl_users');
        // if(!empty($user_ids)){
        //     $this->db->where_in('id', $user_ids);
        // }
        // if(!empty($filter_id)){
        $this->db->where('id', $user_ids);
        // }
        $query = $this->db->get();
        $result = $query->row();
        return $result->todays_bet ? round($result->todays_bet, 2) : 0;
    }
    public function totalFirstDepositAmount($user_ids, $filter_id = '')
    {

        if (!empty($user_ids) && !empty(implode(",", $user_ids))) {
            $ids = implode(",", $user_ids);

            if ($ids == 'No') {
                $ids = '0';
            }
        } else {
            $ids = '0';
        }
        if (!empty($filter_id)) {
            $and = 'AND  `user_id`="' . $filter_id . '"';
        } else {
            $and = "";
        }
        $this->db->select_sum('sub.coin', 'total_coin');
        $this->db->from('(SELECT `coin` FROM `tbl_purchase` WHERE `user_id` IN (' . $ids . ') ' . $and . ' AND `payment` = 1 AND DATE(added_date)="' . date('Y-m-d') . '" GROUP BY `user_id`) AS sub');
        $query = $this->db->get();
        $result = $query->row();
        return $total_coin = $result->total_coin;

    }

    public function UpdateRefferId($referer_id, $user_id)
    {
        $this->db->set('referred_by', $referer_id);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
        return true;
    }

    public function totalFirstDeposit($user_ids, $filter_id = '')
    {
        // Get yesterday's date
        $today_date = date('Y-m-d');
        // Select the sum of amounts where user_id is $user_id, level is 1, and the date is yesterday's date
        $this->db->select_sum('coin');
        $this->db->from('tbl_purchase');
        if (!empty($user_ids)) {
            $this->db->where_in('user_id', $user_ids);
        }
        if (!empty($filter_id)) {
            $this->db->where('user_id', $filter_id);
        }
        $this->db->where('payment', 1);
        $this->db->where('DATE(added_date)', $today_date);
        $this->db->group_by('user_id');
        $this->db->order_by('coin', 'asc');
        $query = $this->db->get();
        return $result = $query->num_rows();
        // echo $this->db->last_query();
        return !empty($result->coin) ? round($result->coin, 2) : 0;
    }

    public function totalMyTeam($user_id)
    {
        $this->db->from('tbl_users');
        $this->db->where('referred_by', $user_id);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function getWalletTransfer($user_id, $reciever_id = '', $date = '')
    {
        if (!empty($reciever_id)) {
            $this->db->where('reciever_id', $reciever_id);
        }
        if (!empty($date)) {
            $this->db->where('DATE(added_date)', date('Y-m-d', strtotime($date)));
        }
        $this->db->group_start();
        $this->db->where('sender_id', $user_id);
        $this->db->or_where('reciever_id', $user_id);
        $this->db->group_end();
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get('tbl_transfer_wallet');
        return $Query->result();
    }

    public function getUserNameById($user_id)
    {
        $this->db->from('tbl_users');
        $this->db->where('id', $user_id);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get();
        return $query->result();
    }

    public function DailyAttendanceBonus($user_id)
    {
        $this->db->select('tbl_daily_attendence_bonus_master.*,(select tbl_attendance_bonus_log.coin from tbl_attendance_bonus_log where user_id="' . $user_id . '" and tbl_daily_attendence_bonus_master.id=tbl_attendance_bonus_log.day order by id desc limit 1 ) as collected,(select tbl_attendance_bonus_log.bet_amount from tbl_attendance_bonus_log where user_id="' . $user_id . '" and tbl_daily_attendence_bonus_master.id=tbl_attendance_bonus_log.day order by id desc limit 1 ) as todays_bet');
        $this->db->from('tbl_daily_attendence_bonus_master');
        $this->db->order_by('tbl_daily_attendence_bonus_master.id', 'asc');
        // $this->db->limit(10);
        $Query = $this->db->get();
        return $Query->result();
    }
    public function rebateHistory($user_id, $limit = 0)
    {
        $this->db->from('tbl_rebate_income');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'desc');
        if (!empty($limit)) {
            $this->db->limit($limit);
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function spin($user_id, $amount, $win_amount)
    {
        $data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'win_amount' => $win_amount
        ];
        $this->db->insert('tbl_spin', $data);
        return $this->db->insert_id();
    }

    public function slot_user($user_id, $amount, $win_amount)
    {
        $data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'win_amount' => $win_amount
        ];
        $this->db->insert('tbl_slot_user', $data);
        return $this->db->insert_id();
    }

    public function getAgentByAmount($amount)
    {
        $this->db->select('tbl_admin.first_name,tbl_admin.last_name,tbl_admin.id,tbl_admin.agent_deposite_rate,tbl_admin.agent_withdraw_rate,tbl_admin.agent_acc_details');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted', false);
        $this->db->where('tbl_admin.role', 2);

        if (!empty($amount)) {
            $this->db->where('tbl_admin.wallet>=', $amount);
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function getConversationId($user_id, $agent_id)
    {
        $this->db->select('*');
        $this->db->from('tbl_conversations');
        $this->db->where('tbl_conversations.isDeleted', false);
        $this->db->where('tbl_conversations.user_id', $user_id);
        $this->db->where('tbl_conversations.agent_id', $agent_id);
        $query = $this->db->get();
        return $query->row();
    }


    public function getChatsByConversationId($conversation_id)
    {
        $this->db->select('*');
        $this->db->from('tbl_messages');
        $this->db->where('tbl_messages.conversation_id', $conversation_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function generateConversationId($data)
    {
        $this->db->insert('tbl_conversations', $data);
        return $this->db->insert_id();
    }

    public function sendMsg($data)
    {
        $this->db->insert('tbl_messages', $data);
        return $this->db->insert_id();
    }

    public function get_recharge_commission($user_id)
    {
        $referred_user_id = $user_id;
        for ($i = 0; $i < 10; $i++) {
            $user_query = $this->db->get_where('tbl_users', array('referred_by' => $referred_user_id));
            $user_result = $user_query->row();
            if (!$user_result || !$user_result->referred_by) {
                break;
            }
            $referred_user_id = $user_result->referred_by;
        }
        $this->db->select_sum('coin');
        $this->db->from('tbl_purcharse_ref');
        $this->db->where('user_id', $referred_user_id);
        $query = $this->db->get();
        $result = $query->row();
        return $result->coin;
    }

    public function getConversationAgents($user_id)
    {
        $this->db->select('tbl_admin.first_name,last_name,tbl_conversations.id as conversation_id,agent_id');
        $this->db->from('tbl_conversations');
        $this->db->join('tbl_admin', 'tbl_conversations.agent_id=tbl_admin.id');
        $this->db->where('tbl_conversations.isDeleted', false);
        $this->db->where('tbl_conversations.user_id', $user_id);
        $this->db->distinct();
        $query = $this->db->get();
        return $query->result();
    }
    public function AllBankDetails($id)
    {

        $this->db->select('tbl_users_bank_details.*,tbl_users.name as user_name');
        $this->db->from('tbl_users_bank_details');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_users_bank_details.user_id');
        $this->db->where('tbl_users_bank_details.isDeleted', false);
        $this->db->where('tbl_users_bank_details.user_id', $id);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit();
        return $Query->result();
    }
    public function TotalBotBalance()
    {
        $this->db->select('SUM(wallet) as totalBalance');
        $this->db->from('tbl_users');
        $this->db->where('user_type', 1); //bot users
        $query = $this->db->get();
        return $query->row()->totalBalance ?? 0;
    }
    public function TodayNewUsers()
    {
        $this->db->select('COUNT(*) AS todayNewUsers');
        $this->db->from('tbl_users');
        $this->db->where('DATE(added_date)', date('Y-m-d'));
        $this->db->where('user_type', 0); // real users

        $query = $this->db->get();
        return (int) $query->row()->todayNewUsers;
    }

    public function GetAdminById($admin_id)
    {
        return $this->db->where('id', $admin_id)->get('tbl_admin')->row();
    }


    public function AddAgentPaymentProof($agent_id, $user_id, $txn_id, $image)
    {
        $data = [
            'agent_id' => $agent_id,
            'user_id' => $user_id,
            'txn_id' => $txn_id,
            'image' => $image,
            'added_date' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('tbl_agent_payment_proof', $data);
    }
}