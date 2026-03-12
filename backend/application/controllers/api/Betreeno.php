<?php

use phpDocumentor\Reflection\Types\Object_;
use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Betreeno extends REST_Controller
{
    private $data;
    private $players;

    public function __construct(){
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
        // print_r($this->data['user_id']);
        $this->load->model([
            'Betreeno_model',
            'Users_model',
            'Setting_model'
        ]);
    }

    public function get_table_master_post(){
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

        // $joining_amount = $this->Setting_model->Setting()->joining_amount;
        // if ($user[0]->wallet<$joining_amount) {
        //     $data['message'] = 'Required Minimum '.number_format($joining_amount).' Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        if ($user[0]->betreeno_table_id) {
            $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['code'] = 205;
            $this->response($data, 200);
            exit();
        }

        // $table_amount = 500;
        // $table_data = [
        //     'blind_1' => $table_amount,
        //     'maximum_blind' => 4,
        //     'chaal_limit' => $table_amount*128,
        //     'pot_limit' => $table_amount*1024,
        //     'added_date' => date('Y-m-d H:i:s'),
        //     'updated_date' => date('Y-m-d H:i:s')
        // ];

        // $tables = $this->Betreeno_model->getPublicActiveTable();
        // $seat_position = 1;

        // if($tables)
        // {
        //     foreach ($tables as $value) {
        //         if($value->members<5)
        //         {
        //             $TableId = $value->betreeno_table_id;
        //             $seat_position = $this->Betreeno_model->GetSeatOnTable($TableId);
        //         }
        //     }
        // }

        // if(empty($TableId))
        // {
        //     $TableId = $this->Betreeno_model->CreateTable($table_data);
        //     // $this->sendNotification($TableId);

        //     $bot = $this->Users_model->GetFreeBot();

        //     $table_bot_data = [
        //         'betreeno_table_id' => $TableId,
        //         'user_id' => $bot[0]->id,
        //         'seat_position' => 2,
        //         'added_date' => date('Y-m-d H:i:s'),
        //         'updated_date' => date('Y-m-d H:i:s')
        //     ];

        //     $this->Betreeno_model->AddTableUser($table_bot_data);
        // }

        // $table_user_data = [
        //     'betreeno_table_id' => $TableId,
        //     'user_id' => $user[0]->id,
        //     'seat_position' => $seat_position,
        //     'added_date' => date('Y-m-d H:i:s'),
        //     'updated_date' => date('Y-m-d H:i:s')
        // ];

        // $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->getTableMaster();

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['blind_1'])) {
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

        // $joining_amount = $this->Setting_model->Setting()->joining_amount;
        $isMaster = $this->Betreeno_model->getTableMaster($this->data['blind_1']);
        if (empty($isMaster)) {
            $data['message'] = 'Invalid Boot Value';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        if ($user[0]->wallet<$isMaster[0]->boot_value) {
            $data['message'] = 'Required Minimum '.number_format($isMaster[0]->boot_value).' Coins to Play';
            // if ($user[0]->wallet<30) {
        //     $data['message'] = 'Required Minimum 30 Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->betreeno_table_id) {
            $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $tables = $this->Betreeno_model->getCustomizeActiveTable($isMaster[0]->blind_1);
        $seat_position = 1;

        if ($tables) {
            foreach ($tables as $value) {
                if ($value->members<9) {
                    $TableId = $value->betreeno_table_id;
                    $seat_position = $this->Betreeno_model->GetSeatOnTable($TableId);
                }
            }
        }

        if (empty($TableId)) {
            
            $table_data = [
                'master_table_id' => $isMaster[0]->id,
                'boot_value' => $isMaster[0]->blind_1,
                'maximum_blind' => 4,
                'chaal_limit' => $isMaster[0]->chaal_limit,
                'pot_limit' => $isMaster[0]->pot_limit,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $TableId = $this->Betreeno_model->CreateTable($table_data);
            // $this->sendNotification($TableId);

            // $bot = $this->Users_model->GetFreeBot();

            // if ($bot) {
            //     $table_bot_data = [
            //         'betreeno_table_id' => $TableId,
            //         'user_id' => $bot[0]->id,
            //         'seat_position' => 2,
            //         'added_date' => date('Y-m-d H:i:s'),
            //         'updated_date' => date('Y-m-d H:i:s')
            //     ];

            //     $this->Betreeno_model->AddTableUser($table_bot_data);
            // }
        }

        // $this->Betreeno_model->MinusWallet($user[0]->id, $isMaster[0]->boot_value);
        $table_user_data = [
            'betreeno_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'game_wallet' => 0,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function switch_table_post(){
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

        if (!$user[0]->betreeno_table_id) {
            // $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);
            $data['message'] = 'You Are Not On Table';
            // $data['table_data'] = $table_data;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if ($game) {
            $table_user_details = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id,$user[0]->id);
            $this->Betreeno_model->PackGame($this->data['user_id'], $game->id,0,$user[0]->wallet);
            $game_users = $this->Betreeno_model->GameUser($game->id);

            if (count($game_users)==1) {
                $comission = $this->Setting_model->Setting()->admin_commission;
                $this->Betreeno_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission, $user[0]->betreeno_table_id);
            }
        }
        $table = $this->Betreeno_model->isTableAvail($user[0]->betreeno_table_id);

        $table_amount = $table->blind_1;
        $table_data = [
            'blind_1' => $table_amount,
            'maximum_blind' => 4,
            'chaal_limit' => $table_amount*128,
            'pot_limit' => $table_amount*1024,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $seat_position = 1;
        // $tables = $this->Betreeno_model->getPublicActiveTable();
        $tables = $this->Betreeno_model->getCustomizeActiveTable($table_amount);

        if ($tables) {
            foreach ($tables as $value) {
                if ($user[0]->betreeno_table_id!=$value->betreeno_table_id) {
                    if ($value->members<5) {
                        $TableId = $value->betreeno_table_id;
                        $seat_position = $this->Betreeno_model->GetSeatOnTable($TableId);
                    }
                }
            }
        }

        $table_user_data = [
            'betreeno_table_id' => $user[0]->betreeno_table_id,
            'user_id' => $user[0]->id
        ];

        $this->Betreeno_model->RemoveTableUser($table_user_data);

        if (empty($TableId)) {
            $TableId = $this->Betreeno_model->CreateTable($table_data);
            // $this->sendNotification($TableId);

            $bot = $this->Users_model->GetFreeBot();

            $table_bot_data = [
                'betreeno_table_id' => $TableId,
                'user_id' => $bot[0]->id,
                'seat_position' => 2,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $this->Betreeno_model->AddTableUser($table_bot_data);
        }

        $table_user_data = [
            'betreeno_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_private_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['blind_1'])) {
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

        // if ($user[0]->wallet<10000) {
        //     $data['message'] = 'Required Minimum 10,000 Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        if ($user[0]->betreeno_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = [
            'blind_1' => $this->data['blind_1'],
            'maximum_blind' => 4,
            'chaal_limit' => $this->data['blind_1']*128,
            'pot_limit' => $this->data['blind_1']*1024,
            'private' => 2,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $TableId = $this->Betreeno_model->CreateTable($table_data);

        $table_user_data = [
            'betreeno_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => 1,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['betreeno_table_id'] = $TableId;
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_customise_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['blind_1'])) {
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

        // if ($user[0]->wallet<10000) {
        //     $data['message'] = 'Required Minimum 10,000 Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        if ($user[0]->betreeno_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $tables = $this->Betreeno_model->getCustomizeActiveTable($this->data['blind_1']);

        $seat_position = 1;
        if ($tables) {
            foreach ($tables as $value) {
                if ($value->members<5) {
                    $TableId = $value->betreeno_table_id;
                    $seat_position = $this->Betreeno_model->GetSeatOnTable($TableId);
                }
            }
        }

        if (empty($TableId)) {
            $table_data = [
                'blind_1' => $this->data['blind_1'],
                'maximum_blind' => 4,
                'chaal_limit' => $this->data['blind_1']*128,
                'pot_limit' => $this->data['blind_1']*1024,
                'private' => 2,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $TableId = $this->Betreeno_model->CreateTable($table_data);
            $this->sendNotification($TableId);
        }

        $table_user_data = [
            'betreeno_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['betreeno_table_id'] = $TableId;
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function join_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['betreeno_table_id'])) {
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

        if ($user[0]->betreeno_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Betreeno_model->isTable($this->data['betreeno_table_id'])) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Betreeno_model->isTableAvail($this->data['betreeno_table_id']);
        if (!$table) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$table->blind_1) {
            $data['message'] = 'Required Minimum '.$table->blind_1.' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'betreeno_table_id' => $this->data['betreeno_table_id'],
            'user_id' => $user[0]->id,
            'seat_position' => $this->Betreeno_model->GetSeatOnTable($this->data['betreeno_table_id']),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Betreeno_model->AddTableUser($table_user_data);

        $table_data = $this->Betreeno_model->TableUser($this->data['betreeno_table_id']);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function start_game_post(){
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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = 501;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);

        if (count($table_data)<2) {
            $data['message'] = 'Unable to Create Game, Only One User On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['game_id'] = $game->id;
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Betreeno_model->isTableAvail($user[0]->betreeno_table_id);
        $amount = $table->boot_value;

        if (count($table_data)>=2) {
            foreach ($table_data as $key => $value) {

                if($value->wallet<=$amount){
                    $table_user_data = [
                        'betreeno_table_id' => $value->betreeno_table_id,
                        'user_id' => $value->user_id
                    ];
            
                    $this->Betreeno_model->RemoveTableUser($table_user_data);
                    continue;
                }

                if ($value->user_type==1) {
                    $table_user_data = [
                        'betreeno_table_id' => $value->betreeno_table_id,
                        'user_id' => $value->user_id
                    ];

                    $this->Betreeno_model->RemoveTableUser($table_user_data);
                    $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);
                }
            }
        }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = 501;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);

        if (count($table_data)<2) {
            $data['message'] = 'Unable to Create Game, Only One User On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);

        $Cards = $this->Betreeno_model->GetCards((count($table_data)*3));

        $round_table_data = array();

        if($table->start_seat_no!=0){
            $before_round_table_data = array();
            $after_round_table_data = array();
            foreach ($table_data as $key => $value) {

                if($table->start_seat_no>=$value->seat_position){
                    $before_round_table_data[] = $value;
                }else{
                    $after_round_table_data[] = $value;
                }
            }

            $round_table_data = array_merge($after_round_table_data,$before_round_table_data);
        }else{
            $round_table_data = $table_data;
        }

        $game_data = [
            'betreeno_table_id' => $user[0]->betreeno_table_id,
            'amount' => $amount*count($round_table_data),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $GameId = $this->Betreeno_model->Create($game_data);

        $card_count=0;
        foreach ($round_table_data as $key => $value) {


            switch ($key) {
                case '0':
                    $role = 1;
                    break;

                case '1':
                    $role = 2;
                    break;

                case '2':
                    $role = 3;
                    break;

                default:
                    $role = 0;
                    break;
            }

            $table_user_data = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'card1' => $Cards[$key*3]->cards,
                'card2' => $Cards[($key*3)+1]->cards,
                'card3' => $Cards[($key*3)+2]->cards,
                'total_amount' => $amount,
                'role' => $role,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $card_count+3;

            $this->Betreeno_model->GiveGameCards($table_user_data);

            minus_from_wallets($value->user_id, $amount, 1);

            $this->Betreeno_model->AddGameCount($value->user_id);

            $game_log = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'action' => 0,
                'round' => 0,
                'amount' => $amount,
                // 'left_amount' => $value->game_wallet-$amount,
                'added_date' => date('Y-m-d H:i:s')
            ];

            $this->Betreeno_model->AddGameLog($game_log);

            if($key==0){
                $this->Betreeno_model->UpdateSeatNumber($table->id,$value->seat_position);
            }
        }

        $data['message'] = 'Success';
        $data['game_id'] = $GameId;
        $data['table_amount'] = $amount*count($round_table_data);
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function see_card_post(){

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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $cards = $this->Betreeno_model->getMyCards($game->id, $this->data['user_id']);
        ;

        $data['message'] = 'Success';
        $data['cards'] = $cards;
        $data['CardValue'] = $this->Betreeno_model->CardValue($cards[0]->card1, $cards[0]->card2);
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function leave_table_post(){
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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $table_data = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);

        // foreach ($table_data as $value) {
        // if($value->mobile)
        $table_user_details = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id,$user[0]->id);
        $table_user_data = [
                'betreeno_table_id' => $user[0]->betreeno_table_id,
                'user_id' => $user[0]->id
            ];

        $this->Betreeno_model->RemoveTableUser($table_user_data);
        // }

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if ($game) {
            
            $this->Betreeno_model->PackGame($this->data['user_id'], $game->id,0,$user[0]->wallet);
            $game_users = $this->Betreeno_model->GameUser($game->id);

            if (count($game_users)==1) {
                $comission = $this->Setting_model->Setting()->admin_commission;
                $this->Betreeno_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission, $user[0]->betreeno_table_id);
            }
        }

        $table_users = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id);

        if (count($table_users)==1) {
            if ($table_users[0]->mobile=="") {
                $table_user_data = [
                    'betreeno_table_id' => $table_users[0]->betreeno_table_id,
                    'user_id' => $table_users[0]->user_id
                ];

                $this->Betreeno_model->RemoveTableUser($table_user_data);
            }
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function pack_game_post(){

        $timeout = $this->input->post('timeout');
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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_log = $this->Betreeno_model->GameLog($game->id, 1);

        $game_users = $this->Betreeno_model->GameAllUser($game->id);

        $chaal = 0;
        $element = 0;
        foreach ($game_users as $key => $value) {
            if ($value->user_id==$game_log[0]->user_id) {
                $element = $key;
                break;
            }
        }

        $index = 0;
        foreach ($game_users as $key => $value) {
            $index = ($key+$element)%count($game_users);
            if ($key>0) {
                if (!$game_users[$index]->packed && !$game_users[$index]->all_in) {
                    // $user_game_wallet = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id, $game_users[$index]->user_id)[0]->game_wallet;
                    // if($user_game_wallet>0){
                        $chaal = $game_users[$index]->user_id;
                        break;
                    // }
                }
            }
        }

        if ($chaal==$this->data['user_id']) {
            $table_user_details = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id,$user[0]->id);
            $this->Betreeno_model->PackGame($this->data['user_id'], $game->id, $timeout, $user[0]->wallet);
            $game_users = $this->Betreeno_model->GameUser($game->id);

            if (count($game_users)==1) {
                $comission = $this->Setting_model->Setting()->admin_commission;
                $this->Betreeno_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission, $user[0]->betreeno_table_id);
            }

            if ($timeout==1) {
                $table_user_data = [
                    'betreeno_table_id' => $user[0]->betreeno_table_id,
                    'user_id' => $user[0]->id
                ];

                $this->Betreeno_model->RemoveTableUser($table_user_data);
            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Pack';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function chaal_post(){

        $rule = $this->input->post('rule');
        $rule_value = $this->input->post('value');
        $chaal_type = $this->input->post('chaal_type');
        // $raise = $this->input->post('raise');
        $amount = $this->input->post('amount');
        // echo "$amount".$amount;
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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $betreeno_table_id = $user[0]->betreeno_table_id;

        $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_details = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id,$user[0]->id);

        // echo $user[0]->wallet;
        // echo '$amount '.$amount.' chalty';
        if($chaal_type=='3' && $user[0]->wallet<=$amount){
            $chaal_type = '5';
            $amount = $user[0]->wallet;
        }
        
        // if ($user[0]->wallet<=0 && $chaal_type!='5') {
        //     $data['message'] = 'Insufficient Coins For '.$amount.' Coins Chaal AAA';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $lastChal = $this->Betreeno_model->LastChaal($game->id);

        $game_log = $this->Betreeno_model->GameLog($game->id, 1);

        $game_users = $this->Betreeno_model->GameAllUser($game->id);

        $chaal = 0;
        $element = 0;
        foreach ($game_users as $key => $value) {
            if ($value->user_id==$game_log[0]->user_id) {
                $element = $key;
                break;
            }
        }

        $index = 0;
        foreach ($game_users as $key => $value) {
            $index = ($key+$element)%count($game_users);
            if ($key>0) {
                if (!$game_users[$index]->packed && !$game_users[$index]->all_in) {
                    // $user_game_wallet = $this->Betreeno_model->TableUser($user[0]->betreeno_table_id, $game_users[$index]->user_id)[0]->game_wallet;
                    // if($user_game_wallet>0){
                        $chaal = $game_users[$index]->user_id;
                        break;
                    // }
                }
            }
        }

        if ($chaal==$this->data['user_id']) {
            $table = $this->Betreeno_model->isTableAvail($user[0]->betreeno_table_id);

            $round = $lastChal->round;
            $game_users = $this->Betreeno_model->GameUser($game->id);

            $middle_card_count = count($this->Betreeno_model->getTableCards($game->id));
            $active_game_users = $this->Betreeno_model->GameUser($game->id);
            $only_active_game_users = $this->Betreeno_model->GameUserNotAllIn($game->id);

            $is_equal = true;
            $max_amount = 0;
            $increase_round=false;
            foreach ($active_game_users as $key => $value) {
                $user_game_amount = $this->Betreeno_model->GameTotalAmount($game->id, $value->user_id);
                // echo '<br>user_game_amount '.$value->user_id.'----'.$user_game_amount;
                // $user_game_amount = $value->total_amount;
                $max_amount = ($max_amount>$user_game_amount) ? $max_amount : $user_game_amount;
            }
            // echo '<br>Max Amount ----'.$max_amount;
            $user_amount = $this->Betreeno_model->GameTotalAmount($game->id, $this->data['user_id']);
            // echo '<br>user_amount -- '.$user_amount;
            $diff_amount = $max_amount - $user_amount;

            // echo "diff_amount".$diff_amount;


            // $max_amount = 0;
            // foreach ($active_game_users as $key => $value) {
            //     $amount = $this->Betreeno_model->GameTotalAmount($game->id, $value->user_id);
            //     $max_amount = ($max_amount>$amount) ? $max_amount : $amount;
            // }
            // $user_amount = $this->Betreeno_model->GameTotalAmount($game->id, $chaal);
            // // echo $max_amount;
            // $diff_amount = $max_amount - $user_amount;
            // echo $chaal_type;
            if ($chaal_type=='2') {
                // $lastChal_amount = $this->Betreeno_model->LastChaalAmount($game->id);
                // $bb_chaal_log = $this->Betreeno_model->GameLog($game->id, 2);
                // $amount = (count($bb_chaal_log)==1)?$diff_amount*2:$diff_amount;
                $amount = $diff_amount;
                // echo '<br>first chaal amount -- '.$amount;
                // $amount = ($diff_amount==0) ? $lastChal_amount : $diff_amount;
                // if($user[0]->wallet<=$amount){
                //     $amount = $user[0]->wallet;
                //     $chaal_type = '5';
                // }
            }else if($chaal_type=='5'){
                $amount = $user[0]->wallet;
            }
            // echo $amount;
            if ($user[0]->wallet<$amount) {
                $data['message'] = 'Insufficient Coins For '.$amount.' Coins Chaal BBB'.$chaal_type;
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            // echo '<br>Amounts - '.$user_amount.'----'.$diff_amount.'----'.$amount;
            $equal_amount = $diff_amount - $amount;
            // echo '$equal_amount = '.$equal_amount;
            if ($equal_amount!=0) {
                $is_equal = false;

                // // UserAmount is Lower Than 
                // if($diff_amount > $amount){
                //     // echo '<br>User Difference Amount is ---- '.$equal_amount; 
                // }
            }
            // echo '<br>New Equal Value ---- '.$equal_amount; //
            if ($is_equal) {
                foreach ($only_active_game_users as $key => $value) {
                    if ($value->user_id!=$this->data['user_id']) {
                        // $user_game_amount = $this->Betreeno_model->GameTotalAmount($game->id, $value->user_id);
                        $user_game_amount = $value->total_amount;
                        $diff_amount = $max_amount - $user_game_amount;
                        // echo 'diff_amount = '.$diff_amount;
                        if ($diff_amount!=0) {
                            $is_equal = false;
                            break;
                        }
                    }
                }
                // echo 'New Diff Value ----'.$diff_amount;
            }
            
            $round = 1;
            // switch ($middle_card_count) {
            //     case 0:
            //         $round = 1;
            //         break;

            //     case 3:
            //         $round = 2;
            //         break;

            //     case 4:
            //         $round = 3;
            //         break;

            //     case 5:
            //         $round = 4;
            //         break;

            //     case 6:
            //         $round = 5;
            //         break;

            //     default:
            //         $round = 1;
            //         break;
            // }
            // echo '<br>entry in chaal -- '.$amount;
            $this->Betreeno_model->Chaal($game->id, $amount, $this->data['user_id'], $round, $rule, $rule_value, $chaal_type, $user[0]->betreeno_table_id,$user[0]->wallet, $user_amount);

            if($chaal_type=='5') {
                $gameUserCard = $this->Betreeno_model->GameUserCard($game->id, $this->data['user_id']);
                $pot_data['game_id'] = $game->id;
                $pot_data['user_id'] = $this->data['user_id'];
                $pot_data['amount'] = $gameUserCard->total_amount;
                $this->Betreeno_model->AddPot($pot_data);
            }
            
            $round_count = count($this->Betreeno_model->GameLog($game->id, '', '', $round));
            if (count($only_active_game_users)<=($round_count) && $is_equal) {
                // echo 'increase in';
                // if($round_count)
                // $increase_round = (count($only_active_game_users)<=($round_count+1)) ? true : false;
                $increase_round = true;
            }

            $NotAllInUsers = $this->Betreeno_model->GameUserNotAllIn($game->id);
            // echo 'diff_amount = '.$diff_amount;
            // echo 'is_equal = '.(($is_equal)?'yes':'no');
            if(count($NotAllInUsers)==1){
                $user_amount = $this->Betreeno_model->GameTotalAmount($game->id, $NotAllInUsers[0]->user_id);
                $game_max_amount = 0;
                // if($NotAllInUsers[0]->user_id!=$this->data['user_id']){
                //     $game_max_amount += $amount;
                // }

                foreach ($only_active_game_users as $key => $value) {
                    if($NotAllInUsers[0]->user_id!=$value->user_id){
                        $user_game_amount = $this->Betreeno_model->GameTotalAmount($game->id, $value->user_id);
                        // $user_game_amount = $value->total_amount;
                        $game_max_amount = ($game_max_amount>$user_game_amount) ? $game_max_amount : $user_game_amount;
                    }
                }

                // echo '$NotAllInUsers = '.$NotAllInUsers[0]->user_id;
                // echo '$user_id = '.$this->data['user_id'];
                // echo '$user_amount = '.$user_amount;
                // echo '$game_max_amount = '.$game_max_amount;
                // echo '$amount = '.$amount;

                if($user_amount>=$game_max_amount){
                    $increase_round = true;
                }

            }else if(count($NotAllInUsers)==0){
                $increase_round = true;
            }
            // echo '<br>Is Equal - '.($is_equal?'1':'0');
            // echo '<br>increase round - '.($increase_round?'1':'0');
            // echo '<br>'.count($only_active_game_users).'---'.$round_count;

            if ($increase_round==true) {
                // echo 'Increase';
                // New Code For Winner
                $pot_users = $this->Betreeno_model->ViewPot($game->id);
                $comission = $this->Setting_model->Setting()->admin_commission;
                if($pot_users){
                    // If All In User Found
                    $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);
                    $game_whole_amount = $game->amount;
                    $user_count = count($active_game_users);
                    $out_users = [];
                    $last_all_in_amount = 0;
                    foreach ($pot_users as $ke => $valu) {
                        $active_game_users = $this->Betreeno_model->GameUser($game->id,$out_users);

                        $game_amount = (($valu->amount-$last_all_in_amount)*$user_count);
                        echo '$valu->amount - '.$valu->amount.' ---- ';

                        $winner = 0;

                        if(count($active_game_users)>1 && $game_amount>0){

                            $game_whole_amount = $game_whole_amount-$game_amount;

                            $this->players = [];
                            foreach ($active_game_users as $k => $val) {

                                // if(!in_array($val->user_id,$out_users)){
                                    // $user1 = array($active_game_users[$winner]->rule,$active_game_users[$winner]->value);
                                    // $user2 = array($active_game_users[$k+1]->rule,$active_game_users[$k+1]->value);
                                    // $winner_pos = $this->Betreeno_model->getPotWinnerPosition($user1, $user2);
                                    // $winner = ($winner_pos==0) ? $winner : $k+1;

                                    // if (($k+2)==count($active_game_users)) {
                                    //     $winner_id = $active_game_users[$winner]->user_id;
                                    //     break;
                                    // }
                                // }else{
                                //     $k+1;
                                // }
                                $player = array();
                                $hand = array();

                                $player['user_id'] = $val->user_id;

                                $hand[] = ['value' => substr($val->card1, 2)];
                                $hand[] = ['value' => substr($val->card2, 2)];
                                $hand[] = ['value' => substr($val->card3, 2)];

                                $player['hand'] = $hand;

                                $this->players[] = $player;
                            }

                            $winners = $this->getWinner();
                            $winner_id = $winners['players'][0];

                            echo 'count($active_game_users) - '.count($active_game_users).' ---- ';
                            echo 'count($pot_users) - '.count($pot_users).' ---- ';

                            if(count($pot_users)>1 && count($active_game_users)!=count($pot_users)){
                                $this->Betreeno_model->MakeWinner($game->id, $game_amount, $winner_id, $comission, $betreeno_table_id,'main_pot_'.($ke+1).'_winner','main_pot_'.($ke+1), winning_explanation: $winners['explanation']);
                            }else{
                                $this->Betreeno_model->MakeWinner($game->id, $game_amount, $winner_id, $comission, $betreeno_table_id, winning_explanation: $winners['explanation']);
                            }

                            $out_users[] = $valu->user_id;

                            $last_all_in_amount = $valu->amount;
                            $user_count--;

                            // echo '$user_count - '.$user_count;
                            // echo '$game_whole_amount - '.$game_whole_amount;
                            if($user_count==1){
                                if($game_whole_amount>0){

                                    $active_game_users = $this->Betreeno_model->GameUser($game->id,$out_users);
                                    // print_r($active_game_users);

                                    $this->Betreeno_model->MakeWinner($game->id, $game_whole_amount, $active_game_users[0]->user_id, 0, $betreeno_table_id,'return_id','return_amt');

                                    $user_count--;
                                }
                            }
                        }
                        // else{

                        //     echo '$game_whole_amount - '.$game_whole_amount.' ---- ';
                        //     echo '$game_amount - '.$game_amount.' ---- ';
                        //     echo '$active_game_users[0]->user_id - '.$active_game_users[0]->user_id.' ---- ';
                        //     echo '$user_count - '.$user_count.' ---- ';

                        //     if($user_count<=0){
                        //         $this->Betreeno_model->MakeWinner($game->id, $game_amount, $active_game_users[0]->user_id, 0, $betreeno_table_id,'return_id','return_amt');
                        //     }
                        // }
                    }

                    $active_game_users = $this->Betreeno_model->GameUser($game->id,$out_users);

                    if(count($active_game_users)>1){
                        $this->players = [];
                        foreach ($active_game_users as $k => $val) {
                            $player = array();
                            $hand = array();

                            $player['user_id'] = $val->user_id;

                            $hand[] = ['value' => substr($val->card1, 2)];
                            $hand[] = ['value' => substr($val->card2, 2)];
                            $hand[] = ['value' => substr($val->card3, 2)];

                            $player['hand'] = $hand;

                            $this->players[] = $player;
                        }

                        $winners = $this->getWinner();
                        $winner_id = $winners['players'][0];
                        $this->Betreeno_model->MakeWinner($game->id, $game_whole_amount, $winner_id, $comission, $betreeno_table_id, winning_explanation: $winners['explanation']);
                    }
                }else{
                    // If All In User Not Found
                    $this->players = [];
                    foreach ($active_game_users as $k => $val) {
                        $player = array();
                        $hand = array();

                        $player['user_id'] = $val->user_id;

                        $hand[] = ['value' => substr($val->card1, 2)];
                        $hand[] = ['value' => substr($val->card2, 2)];
                        $hand[] = ['value' => substr($val->card3, 2)];

                        $player['hand'] = $hand;

                        $this->players[] = $player;
                    }

                    $winners = $this->getWinner();
                    $winner_id = $winners['players'][0];
                    // print_r($winners);
                    $this->Betreeno_model->MakeWinner($game->id, $game->amount+$amount, $winner_id, $comission, $betreeno_table_id, winning_explanation: $winners['explanation']);
                }

                $data['message'] = 'Pot Show';
                $data['winner'] = $winner_id;
                $data['code'] = HTTP_OK;
                $this->response($data, HTTP_OK);
                exit();

            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Chaal';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function chat_post(){
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

        // if(!$user[0]->betreeno_table_id)
        // {
        //     $data['message'] = 'You Are Not On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $game = $this->Betreeno_model->getActiveGameOnTable($user[0]->betreeno_table_id);

        // if(!$game)
        // {
        //     $data['message'] = 'Game Not Started';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $game['id'] = 1000;
        $game = (object) $game;

        $chat = $this->input->post('chat');

        if (!empty($chat)) {
            $chat_data = [
                'user_id' => $this->data['user_id'],
                'chat' => $chat,
                'game_id' => $game->id
            ];

            $this->Betreeno_model->Chat($chat_data);
        }

        $chat_list = $this->Betreeno_model->ChatList($game->id);
        $data['message'] = 'Success';
        $data['list'] = $chat_list;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function tip_post(){
        if (empty($this->data['user_id'] && $this->data['tip'])) {
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

        if (!$user[0]->betreeno_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$this->data['tip']) {
            $data['message'] = 'Insufficiant Tip Coins';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($this->Users_model->TipAdmin($this->data['tip'], $this->data['user_id'], $user[0]->betreeno_table_id, $this->data['gift_id'], $this->data['to_user_id'])) {
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Tip';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function status_post(){

        $betreeno_table_id = $this->input->post('betreeno_table_id');
        $user_id = $this->input->post('user_id');
        $rule = $this->input->post('rule');
        $card_value = $this->input->post('value');

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $table = $this->Betreeno_model->isTableAvail($betreeno_table_id);

        if (!$table) {
            $data['message'] = 'Invalid Table';
            $data['code'] = HTTP_BLANK;
            $this->response($data, 200);
            exit();
        }

        if (!empty($betreeno_table_id)) {
            $table_data = $this->Betreeno_model->TableUser($betreeno_table_id);
            // $data['table_users'] = $table_data;

            $table_new_data = array();
            for ($i=0; $i < 9; $i++) {
                $table_new_data[$i]['id'] = 0;
                $table_new_data[$i]['betreeno_table_id'] = 0;
                $table_new_data[$i]['user_id'] = 0;
                $table_new_data[$i]['seat_position'] = $i+1;
                $table_new_data[$i]['role'] = 0;
                $table_new_data[$i]['game_wallet'] = 0;
                $table_new_data[$i]['added_date'] = 0;
                $table_new_data[$i]['updated_date'] = 0;
                $table_new_data[$i]['isDeleted'] = 0;
                $table_new_data[$i]['user_type'] = 0;
                $table_new_data[$i]['name'] = 0;
                $table_new_data[$i]['mobile'] = 0;
                $table_new_data[$i]['profile_pic'] = 0;
                $table_new_data[$i]['wallet'] = 0;
            }

            foreach ($table_data as $t => $u) {
                $table_new_data[$u->seat_position-1] = $u;
            }

            $data['table_users'] = $table_new_data;

            // $table_updated_users = $table_data;
            // // foreach ($table_data as $key => $value) {
            // //     $table_updated_users[] = $value;
            // // }

            // $found = false;
            // $plus_position = 0;
            // foreach($table_updated_users as $k=>$v)
            // {
            //     $found = $found || $v->user_id===$user_id;
            //     if(!$found)
            //     {
            //         $plus_position=$k+1;
            //         unset($table_updated_users[$k]);
            //         $table_updated_users[] = $v;
            //     }
            //     //else break can be added for performance issues
            // }
            // // echo $plus_position;
            // $i=0;
            // foreach ($table_updated_users as $ke => $va) {
            //     $table_final_users[$i] = $va;
            //     $table_final_users[$i]->seat_position = ($va->seat_position+$plus_position)%5;
            //     $i++;
            // }

            // $data['table_final_users'] = $table_final_users;
            $data['table_detail'] = $table;
            $data['active_game_id'] = 0;
            $data['game_status'] = 0;
            $data['table_amount'] = 0;
            $active_game = $this->Betreeno_model->getActiveGameOnTable($betreeno_table_id);
            if ($active_game) {
                $data['active_game_id'] = $active_game->id;
                $data['game_status'] = 1;
            }
        }

        $game_id = $this->input->post('game_id');
        if (empty($game_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Betreeno_model->View($game_id);
        if (empty($game)) {
            $data['message'] = 'Invalid Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $this->Betreeno_model->UpdateRule($user_id, $game_id, $rule, $card_value);

        $game_log = $this->Betreeno_model->GameLog($game_id, 1);

        $game_users = $this->Betreeno_model->GameAllUser($game_id);

        $chaal = 0;
        $element = 0;
        foreach ($game_users as $key => $value) {
            if ($value->user_id==$game_log[0]->user_id) {
                $element = $key;
                break;
            }
        }

        $index = 0;
        foreach ($game_users as $key => $value) {
            $index = ($key+$element)%count($game_users);

            if ($key>0) {
                if (!$game_users[$index]->packed && !$game_users[$index]->all_in) {
                    // $user_game_wallet = $this->Betreeno_model->TableUser($betreeno_table_id, $game_users[$index]->user_id)[0]->game_wallet;
                    // if($user_game_wallet>0){
                        $chaal = $game_users[$index]->user_id;
                        break;
                    // }
                }
            }
        }

        $last_games = $this->Betreeno_model->UserLastGames($user_id);
        $data['last_games'] = $last_games;

        $data['game_log'] = $game_log;
        $data['all_users'] = $table_data;
        // if ($game_log[0]->action==3) {
        if ($game->winner_id>0) {
            $data['game_users'] = $this->Betreeno_model->GameAllUser($game_id);
        } else {
            $data['game_users'] = $this->Betreeno_model->GameOnlyUser($game_id);
        }
        $data['chaal'] = $chaal;
        $data['middle_card'] = $this->Betreeno_model->getTableCards($game->id);

        $data['game_amount'] = $game->amount;
        $chaalCount = $this->Betreeno_model->ChaalCount($game->id, $chaal);
        // if ($chaalCount>3) {
        //     $this->Betreeno_model->getMyCards($game->id, $chaal);
        // }

        if (!empty($user_id)) {
            // $user_card_seen = $this->Betreeno_model->isCardSeen($game->id, $user_id);
            // $data['cards'] = array();
            // if ($user_card_seen==1) {
            $data['cards'] = $this->Betreeno_model->getMyCards($game->id, $user_id);
            // }

            // $remaining_middle_card = 5-count($data['middle_card']);

            $rabbit_cards = [];
            // for ($i=0; $i < $remaining_middle_card; $i++) { 
            //     $rabbit_cards[] = $this->Betreeno_model->GetRamdomGameCard($game->id);
            // }
            $data['rabbit_cards'] = $rabbit_cards;
        }

        if ($game) {
            $active_game_users = $data['game_users'];
            $lastChal = $this->Betreeno_model->LastChaal($game->id);
            // $amount = $lastChal->amount;

            $max_amount = 0;
            foreach ($active_game_users as $key => $value) {
                $amount = $this->Betreeno_model->GameTotalAmount($game->id, $value->user_id);
                $max_amount = ($max_amount>$amount) ? $max_amount : $amount;
            }
            $user_amount = $this->Betreeno_model->GameTotalAmount($game->id, $chaal);
            // echo $max_amount;
            $diff_amount = $max_amount - $user_amount;

            $lastChal_amount = $this->Betreeno_model->LastChaalAmount($game->id);
            $data['check'] = ($diff_amount==0) ? 1 : 0;
            $NotAllInUsers = $this->Betreeno_model->GameUserNotAllIn($game->id);
            $data['raise'] = (count($NotAllInUsers)==1) ? 1 : 0;
            // $data['table_amount'] = ($diff_amount==0) ? $lastChal_amount : $diff_amount;
            // $bb_chaal_log = $this->Betreeno_model->GameLog($game_id, 2);
            // $data['table_amount'] = (count($bb_chaal_log)==1)?$diff_amount*2:$diff_amount;
            $data['table_amount'] = $diff_amount;
            $data['round'] = $lastChal->round;
            // $data['slide_show'] = $this->Betreeno_model->GetSlideShow($game->id);
        }
        $data['game_gifts'] = $this->Users_model->GiftList($betreeno_table_id);
        $data['game'] = $game;
        $data['user_wallet'] = $user->wallet;
        $data['message'] = 'Success';
        if ($game->winner_id>0) {
            $chaal = 0;
            $data['chaal'] = $chaal;
            $data['message'] = 'Game Completed';
            $data['game_status'] = 2;
            $data['winner_user_id'] = $game->winner_id;
        }
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function history_post(){
        $game_id = $this->input->post('game_id');
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

        $game_log = $this->Betreeno_model->GameLog($game_id);
        $table_cards = $this->Betreeno_model->getTableCards($game_id);
        $summary = $this->Betreeno_model->getSummary($game_id);
        $user_cards = $this->Betreeno_model->getMyCards($game_id, $this->data['user_id']);
        if($game_log)
        {
            $betreeno_game = $this->Betreeno_model->View($game_id);
            $game_summary = array();
            foreach ($summary as $key => $value) {
                $winning_amount = 0;
                $game_summary[$key]['user_id'] = $value->user_id;
                $game_summary[$key]['name'] = $value->name;
                $game_summary[$key]['invest_amount'] = $value->invest_amount;
                $game_summary[$key]['is_winner'] = ($betreeno_game->winner_id==$value->user_id)?1:0;
                $game_summary[$key]['cards'] = $this->Betreeno_model->getMyCards($game_id,$value->user_id);
                $winning_amount += ($betreeno_game->winner_id==$value->user_id)?$betreeno_game->user_winning_amt:0;
                $winning_amount += ($betreeno_game->main_pot_1_winner==$value->user_id)?$betreeno_game->main_pot_1:0;
                $winning_amount += ($betreeno_game->main_pot_2_winner==$value->user_id)?$betreeno_game->main_pot_2:0;
                $winning_amount += ($betreeno_game->main_pot_3_winner==$value->user_id)?$betreeno_game->main_pot_3:0;
                $game_summary[$key]['winning_amount'] = $winning_amount;
            }

            $data['message'] = 'Success';
            $data['game_summary'] = $game_summary;
            $data['game_log'] = $game_log;
            $data['table_cards'] = $table_cards;
            $data['user_cards'] = $user_cards;
            $data['showdown_amount'] = $betreeno_game->amount;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Game';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function game_history_list_post(){
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

        $last_games = $this->Betreeno_model->UserLastGamesWithDetails($this->data['user_id']);
        
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $data['last_games'] = $last_games;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function statistics_post(){
        $user_id = $this->input->post('user_id');
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_INVALID;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }

        $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $total_games = $this->Betreeno_model->UserTotalGames($user_id);
        $total_games_win = $this->Betreeno_model->UserTotalGamesWin($user_id);
        $total_games_fold = $this->Betreeno_model->UserTotalGamesFold($user_id);
        $total_games_first_round = $this->Betreeno_model->UserTotalGamesInRound($user_id,1);
        $total_games_second_round = $this->Betreeno_model->UserTotalGamesInRound($user_id,2);
        $total_games_third_round = $this->Betreeno_model->UserTotalGamesInRound($user_id,3);
        $total_games_fourth_round = $this->Betreeno_model->UserTotalGamesInRound($user_id,4);
        $total_games_check = $this->Betreeno_model->UserTotalGamesInRound($user_id,'',1);
        $total_games_call = $this->Betreeno_model->UserTotalGamesInRound($user_id,'',2);
        $total_games_raise = $this->Betreeno_model->UserTotalGamesInRound($user_id,'',3);

        $data['message'] = 'Success';
        $data['name'] = $user[0]->name;
        $data['profile_pic'] = $user[0]->profile_pic;
        $data['total_games'] = $total_games;
        $data['total_games_win'] = $total_games_win;
        $data['total_games_loss'] = $total_games-$total_games_win;
        $data['total_games_fold'] = $total_games_fold;
        $data['total_games_first_round'] = $total_games_first_round;
        $data['total_games_second_round'] = $total_games_second_round;
        $data['total_games_third_round'] = $total_games_third_round;
        $data['total_games_fourth_round'] = $total_games_fourth_round;
        $data['total_games_check'] = $total_games_check;
        $data['total_games_call'] = $total_games_call;
        $data['total_games_raise'] = $total_games_raise;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function getWinner(){
        $winningPlayers = [];
        $bestHand = null;

        foreach ($this->players as $player) {
            $hand = $this->evaluateHand($player['hand']);

            if (!$bestHand || $this->compareHands($hand, $bestHand) > 0) {
                $bestHand = $hand;
                $winningPlayers = [$player];
            } elseif ($this->compareHands($hand, $bestHand) == 0) {
                $winningPlayers[] = $player;
            }
        }

        $winningExplanation = $this->generateWinningExplanation($bestHand);

        $winnerNames = array_map(function ($player) {
            return $player['user_id'];
        }, $winningPlayers);

        return [
            'players' => $winnerNames,
            'hand' => $bestHand,
            'explanation' => $winningExplanation
        ];
    }

    private function evaluateHand($hand){
        usort($hand, function ($a, $b) {
            return $this->getCardValue($b) - $this->getCardValue($a);
        });

        return $hand;
    }

    private function getCardValue($card){
        $values = [
            '2' => 13, // Treat 2 as a high card like King
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            '10' => 10,
            'J' => 11,
            'Q' => 12,
            'K' => 13,
            'A' => 14
        ];

        return $values[$card['value']];
    }

    private function isAllRoyal($hand){
        $royalCards = ['A', 'K', '2', 'Q', 'J', '10'];
        foreach ($hand as $card) {
            if (!in_array($card['value'], $royalCards)) {
                return false;
            }
        }
        return true;
    }

    private function calculatePoints($hand){
        $points = 0;
        foreach ($hand as $card) {
            if (is_numeric($card['value']) && $card['value'] != '2') { // Exclude '2' from points calculation
                $points += (int)$card['value'];
            }
        }

        $pointsStr = (string)$points;
        if (strlen($pointsStr) > 1) {
            $points = (int)substr($pointsStr, -1);
        }

        return $points;
    }

    private function compareHands($hand1, $hand2){
        $isRoyal1 = $this->isAllRoyal($hand1);
        $isRoyal2 = $this->isAllRoyal($hand2);

        if ($isRoyal1 && !$isRoyal2) {
            return 1;
        } elseif (!$isRoyal1 && $isRoyal2) {
            return -1;
        } elseif ($isRoyal1 && $isRoyal2) {
            return $this->compareRoyalHands($hand1, $hand2);
        }

        $points1 = $this->calculatePoints($hand1);
        $points2 = $this->calculatePoints($hand2);

        if ($points1 != $points2) {
            return $points1 - $points2;
        }

        return $this->compareSupporterCards($hand1, $hand2);
    }

    private function compareRoyalHands($hand1, $hand2){
        $rankOrder = ['A' => 14, 'K' => 13, '2' => 13, 'Q' => 12, 'J' => 11, '10' => 10];

        $values1 = array_map(function ($card) { return $card['value']; }, $hand1);
        $values2 = array_map(function ($card) { return $card['value']; }, $hand2);

        foreach ($rankOrder as $card => $rank) {
            $count1 = count(array_keys($values1, $card));
            $count2 = count(array_keys($values2, $card));

            if ($count1 != $count2) {
                return $count1 - $count2;
            }
        }

        return 0;
    }

    private function compareSupporterCards($hand1, $hand2){
        $rankOrder = ['A' => 14, 'K' => 13, '2' => 13, 'Q' => 12, 'J' => 11, '10' => 10];

        $values1 = array_map(function ($card) { return $card['value']; }, $hand1);
        $values2 = array_map(function ($card) { return $card['value']; }, $hand2);

        foreach ($rankOrder as $card => $rank) {
            $count1 = count(array_keys($values1, $card));
            $count2 = count(array_keys($values2, $card));

            if ($count1 != $count2) {
                return $count1 - $count2;
            }
        }

        return 0;
    }

    private function generateWinningExplanation($hand){
        $values = array_map(function ($card) {
            return $card['value'];
        }, $hand);

        $rankOrder = ['A' => 14, 'K' => 13, '2' => 13, 'Q' => 12, 'J' => 11, '10' => 10];

        if ($this->isAllRoyal($hand)) {
            foreach ($rankOrder as $card => $rank) {
                if (count(array_keys($values, $card)) === 3) {
                    return "The player won with three {$card}s.";
                }
                if (count(array_keys($values, $card)) === 2) {
                    $remainingCard = array_diff($values, [$card]);
                    $remainingCard = array_shift($remainingCard);
                    return "The player won with two {$card}s and a {$remainingCard}.";
                }
            }
        }

        $points = $this->calculatePoints($hand);
        $supporter = '';
        foreach ($hand as $card) {
            if (!is_numeric($card['value']) || $card['value'] == '2') {
                $supporter = $card['value'];
                break;
            }
        }

        return "The player won with {$points} points and supporter card {$supporter}.";
    }
}