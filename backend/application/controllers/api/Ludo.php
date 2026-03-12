<?php

use phpDocumentor\Reflection\Types\Object_;
use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Ludo extends REST_Controller
{
    private $data;
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
            'Ludo_model',
            'Setting_model',
            'Users_model'
        ]);
    }

    public function get_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['boot_value'])) {
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

        if ($user[0]->ludo_table_id) {
            $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['no_of_players'] = $table_data[0]->no_of_players;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        if(!in_array($this->data['no_of_players'],array(2,3,4)))
        {
            $data['message'] = 'Invalid No. Of Players';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $isMaster = $this->Ludo_model->getTableMaster($this->data['boot_value'],$this->data['no_of_players']);
        if (empty($isMaster)) {
            $data['message'] = 'Invalid Boot Value';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $joining_amount = $this->Setting_model->Setting()->joining_amount;
        if ($user[0]->wallet<$isMaster[0]->boot_value) {
            $data['message'] = 'Required Minimum '.number_format($isMaster[0]->boot_value).' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_amount = $isMaster[0]->boot_value;

        $tables = $this->Ludo_model->getCustomizeActiveTable($table_amount,$this->data['no_of_players']);
        $seat_position = 1;

        if ($tables) {
            foreach ($tables as $value) {
                if ($value->members<$isMaster[0]->no_of_players) {
                    $TableId = $value->ludo_table_id;
                    $seat_position = $this->Ludo_model->GetSeatOnTable($TableId);
                    if(!$seat_position){
                        $seat_position = 1;
                        $TableId = '';
                        break;
                    }
                }
            }
        }

        if (empty($TableId)) {
            $table_data = [
                'boot_value' => $table_amount,
                'no_of_players' => $this->data['no_of_players'],
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $TableId = $this->Ludo_model->CreateTable($table_data);

            $admin_mobile = $this->Setting_model->Setting()->mobile;
            if (!empty($admin_mobile)) {
                $admin_user = $this->Users_model->UserByMobile($admin_mobile);
                if ($admin_user) {
                    if (!empty($admin_user->fcm)) {
                        $fcm_data['msg'] = PROJECT_NAME;
                        $fcm_data['title'] = "New User On Ludo Table Boot Value ".$table_amount;
                        $return = push_notification_android($admin_user->fcm, $fcm_data);
                        // print_r($return);
                    }
                }
            }
        //     $robot_ludo = $this->Setting_model->Setting()->robot_ludo;
        //     if ($robot_ludo==0) {
        //         $bot = $this->Users_model->GetFreeBot();

        //         if ($bot) {
        //             $table_bot_data = [
        //                 'table_id' => $TableId,
        //                 'user_id' => $bot[0]->id,
        //                 'seat_position' => 2,
        //                 'added_date' => date('Y-m-d H:i:s'),
        //                 'updated_date' => date('Y-m-d H:i:s')
        //             ];

        //             $this->Ludo_model->AddTableUser($table_bot_data);
        //         }
        //     }
        }

        $table_user_data = [
            'ludo_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data = $this->Ludo_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_private_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['boot_value'])) {
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

        if ($user[0]->table_id) {
            $data['message'] = 'You are Already On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = [
            'boot_value' => $this->data['boot_value'],
            'maximum_blind' => 4,
            'chaal_limit' => (int) $this->data['boot_value']*128,
            'pot_limit' => (int) $this->data['boot_value']*1024,
            'private' => 2,
            'code' => uniqid(),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $TableId = $this->Ludo_model->CreateTable($table_data);

        $table_user_data = [
            'ludo_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => 1,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data_user = $this->Ludo_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_id'] = $TableId;
        $data['table_data'] = $table_data_user;
        $data['table_code'] = $table_data['code'];
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
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

        if ($user[0]->ludo_table_id) {
            $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['no_of_players'] = $table_data[0]->no_of_players;
            $data['code'] = 205;
            $this->response($data, 200);
            exit();
        }

        if(!in_array($this->data['no_of_players'],array(2,3,4)))
        {
            $data['message'] = 'Invalid No. Of Players';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Ludo_model->getTableMaster('',$this->data['no_of_players']);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function join_table_post(){
        if (empty($this->data['user_id']) || empty($this->data['table_id'])) {
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

        if ($user[0]->ludo_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['table_data'][0]['table_id'] = $user[0]->ludo_table_id;
            $data['table_id'] = $user[0]->ludo_table_id;
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Ludo_model->isTable($this->data['table_id'])) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Ludo_model->isTableAvail($this->data['table_id']);
        if (!$table) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$table->boot_value) {
            $data['message'] = 'Required Minimum '.$table->boot_value.' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'ludo_table_id' => $this->data['table_id'],
            'user_id' => $user[0]->id,
            'seat_position' => $this->Ludo_model->GetSeatOnTable($this->data['table_id']),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data = $this->Ludo_model->TableUser($this->data['table_id']);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function join_table_with_code_post(){    
        if (empty($this->data['user_id']) || empty($this->data['code'])) {
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

        if ($user[0]->ludo_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if (!$this->Ludo_model->isTable($this->data['code'])) {
        //     $data['message'] = 'Invalid Table Id';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table = $this->Ludo_model->isTableAvailCode($this->data['code']);
        if (!$table) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$table->boot_value) {
            $data['message'] = 'Required Minimum '.$table->boot_value.' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'ludo_table_id' => $table->id,
            'user_id' => $user[0]->id,
            'seat_position' => $this->Ludo_model->GetSeatOnTable($table->id),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data = $this->Ludo_model->TableUser($table->id);

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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        if (count($table_data)<2) {

            // $robot_ludo = $this->Setting_model->Setting()->robot_ludo;
            // if ($robot_ludo==0) {
            //     $bot = $this->Users_model->GetFreeBot();

            //     if ($bot) {
            //         $seat_position = $this->Ludo_model->GetSeatOnTable($user[0]->ludo_table_id);
            //         if(!$seat_position){
            //             $seat_position = 1;
            //         }

            //         $table_bot_data = [
            //             'ludo_table_id' => $user[0]->ludo_table_id,
            //             'user_id' => $bot[0]->id,
            //             'seat_position' => $seat_position,
            //             'added_date' => date('Y-m-d H:i:s'),
            //             'updated_date' => date('Y-m-d H:i:s')
            //         ];

            //         $this->Ludo_model->AddTableUser($table_bot_data);
            //     }
            //     $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
            // }

            $data['message'] = 'Unable to Create Game, Only One User On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Ludo_model->isTableAvail($user[0]->ludo_table_id);
        $boot_value = $table->boot_value;

        if (count($table_data)>=2) {
            foreach ($table_data as $key => $value) {
                if ($value->wallet<$boot_value) {
                    $table_user_data = [
                        'table_id' => $value->ludo_table_id,
                        'user_id' => $value->user_id
                    ];

                    $this->Ludo_model->RemoveTableUser($table_user_data);
                    $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
                }
            }
        }

        if (count($table_data)>2) {
            foreach ($table_data as $key => $value) {
                if ($value->user_type==1) {
                    $table_user_data = [
                        'table_id' => $value->table_id,
                        'user_id' => $value->user_id
                    ];

                    $this->Ludo_model->RemoveTableUser($table_user_data);
                    $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
                }
            }
        }

        $amount = $table->boot_value;
        $Cards = $this->Ludo_model->GetStartCards(16);
        // $joker = $Cards[0]->cards;
        // $Cards = [];
        $game_data = [
            'ludo_table_id' => $user[0]->ludo_table_id,
            'amount' => count($table_data)*$amount,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $GameId = $this->Ludo_model->Create($game_data);
        $drop_card = "";

        $end = 0;
        foreach ($table_data as $key => $value) {
            $start = $end;
            $end = $end+4;
            for ($i=$start; $i < $end; $i++) {

        //         if(empty($drop_card) && substr(trim($joker, '_'), 2)!=substr(trim($Cards[$i]->cards, '_'), 2)){
        //             $drop_card = $Cards[$i]->cards;
        //             $i++;
        //             $end++;
        //         }
        //         // else{
                    $table_user_data = [
                        'game_id' => $GameId,
                        'user_id' => $value->user_id,
                        'card' => $Cards[$i]->cards,
                        'added_date' => date('Y-m-d H:i:s'),
                        'updated_date' => date('Y-m-d H:i:s'),
                        'isDeleted' => 0
                    ];
    
                    $this->Ludo_model->GiveGameCards($table_user_data);
        //         // }
            }

        //     $this->Ludo_model->AddGameCount($value->user_id);

            $this->Ludo_model->MinusWallet($value->user_id, $amount);
            log_statement ($value->user_id, LD, -$amount,$GameId,0);

            $game_log = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'action' => 0,
                'amount' => $amount,
                'added_date' => date('Y-m-d H:i:s')
            ];

            $this->Ludo_model->AddGameLog($game_log);
        }

        // $table_user_data = [
        //     'game_id' => $GameId,
        //     'user_id' => 0,
        //     'card' => $drop_card
        // ];

        // $this->Ludo_model->StartDropGameCards($table_user_data);

        $data['message'] = 'Success';
        $data['game_id'] = $GameId;
        $data['table_amount'] = $amount;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function leave_table_post(){
        $timeout = '';
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        // foreach ($table_data as $value) {
        // if($value->mobile)
        $table_user_data = [
                'table_id' => $user[0]->ludo_table_id,
                'user_id' => $user[0]->id
            ];

        $this->Ludo_model->RemoveTableUser($table_user_data);
        // }

        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if ($game) {
            $isUserGame = $this->Ludo_model->GameOnlyUser($game->id, $user[0]->id);
            if ($isUserGame) {
                $table = $this->Ludo_model->isTableAvail($user[0]->ludo_table_id);
                // $boot_value = $table->boot_value;
                // $ChaalCount = $this->Ludo_model->ChaalCount($game->id, $this->data['user_id']);

                // $percent = $ChaalCount>0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                // $amount = round(($percent / 100) * $boot_value, 2);
                $amount = $table->amount;

                $this->Ludo_model->PackGame($this->data['user_id'], $game->id, $timeout, '', $amount);
                // $this->Ludo_model->MinusWallet($this->data['user_id'], $amount);
                // log_statement ($this->data['user_id'], LD, -$amount,$game->id,0);
                $game_users = $this->Ludo_model->GameUser($game->id);

                // echo count($game_users);
                if (count($game_users)==1) {
                    $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);
                    $comission = $this->Setting_model->Setting()->admin_commission;
                    $this->Ludo_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
                }
            }
        }

        $table_users = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        if (count($table_users)==1) {
            if ($table_users[0]->user_type==1) {
                $table_user_data = [
                    'table_id' => $table_users[0]->table_id,
                    'user_id' => $table_users[0]->user_id
                ];

                $this->Ludo_model->RemoveTableUser($table_user_data);
            }
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function dice_post(){

        $user_id = $this->data['user_id'];
        // $card = $this->data['card'];
        $data['bot'] = 0;

        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $chaal = $this->get_chaal($game->id);

        if($chaal!=$user_id){
            $data['message'] = 'Invalid Chaal';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'game_id' => $game->id,
            'user_id' => $user_id
        ];

        $dice = rand(1,6);
        // $dice = 2;

        $this->Ludo_model->Dice($table_user_data, $dice);

        // $game_users = $this->Ludo_model->GameAllUser($game->id);
        // if (count($game_users)==2) {
        //     $bot_id = $this->Ludo_model->getGameBot($game->id);
        //     if ($bot_id) {
        //         $data['bot'] = 1;
        //     }
        // }

        $data['message'] = 'Success';
        $data['dice'] = $dice;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function chaal_post(){

        $user_id = $this->data['user_id'];
        $card = $this->input->post('card');
        $cut_card = $this->input->post('cut_card');

        // print_r($_POST);
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $is_card = $this->Ludo_model->getMyCards($game->id, $user_id, $card);

        if ($is_card) {

            $last_log = $this->Ludo_model->GameLog($game->id, 1);

            if($last_log[0]->user_id==$user_id && $last_log[0]->action==2){
                $comission = $this->Setting_model->Setting()->admin_commission;
                $this->Ludo_model->Chaal($game->id, $user_id, $card, $last_log[0]->step, $cut_card, $game->amount, $comission);

                $data['message'] = 'Success';
                $data['code'] = HTTP_OK;
                $this->response($data, HTTP_OK);
                exit();
            }
        }

        $data['message'] = 'Invalid Chaal';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function status_post(){

        $user_id = $this->input->post('user_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($user_id);
        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = 403;
            $this->response($data, 200);
            exit();
        }

        $table_id = $user[0]->ludo_table_id;

        if (!empty($table_id)) {
            $table_data = $this->Ludo_model->TableUser($table_id);
            // $data['table_users'] = $table_data;

            $table_new_data = array();
            for ($i=0; $i < 4; $i++) {
                $table_new_data[$i]['id'] = 0;
                $table_new_data[$i]['table_id'] = 0;
                $table_new_data[$i]['user_id'] = 0;
                $table_new_data[$i]['seat_position'] = $i+1;
                $table_new_data[$i]['added_date'] = 0;
                $table_new_data[$i]['updated_date'] = 0;
                $table_new_data[$i]['isDeleted'] = 0;
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
            $table = $this->Ludo_model->isTableAvail($table_id);
            $data['table_detail'] = $table;
            $data['active_game_id'] = 0;
            $data['game_status'] = 0;
            $data['table_amount'] = $table->boot_value;
            $active_game = $this->Ludo_model->getActiveGameOnTable($table_id);
            if ($active_game) {
                // $game_id = $active_game->id;
                $data['active_game_id'] = $active_game->id;
                $data['game_status'] = 1;
            }
        }

        $game_id = (empty($this->input->post('game_id')))?$data['active_game_id']:$this->input->post('game_id');
        if (empty($game_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->View($game_id);
        if (empty($game)) {
            $data['message'] = 'Invalid Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_log = $this->Ludo_model->GameLog($game_id, 1);

        $chaal = $this->get_chaal($game_id);

        $data['game_log'] = $game_log;
        $data['all_users'] = $table_data;
        // $data['declare'] = false;
        // $data['declare_user_id'] = 0;
        $data['dice'] = 0;
        if ($game_log[0]->action==2) {
            $data['dice'] = $game_log[0]->step;
        }
        // else
        // {
        $data['game_users'] = $this->Ludo_model->GameOnlyUser($game_id);
        // }
        $data['chaal'] = $chaal;
        $data['game_amount'] = $game->amount;
        $data['all_steps'] = $this->Ludo_model->GameCard($game_id);

        $data['message'] = 'Success';

        if($table->no_of_players==2){
            if ($game->winner_id>0) {
                $chaal = 0;
                $data['chaal'] = $chaal;
                $data['message'] = 'Game Completed';
                $data['game_status'] = 2;
                $data['winner_user_id'] = $game->winner_id;
            }
        }elseif($table->no_of_players==3){
            if ($game->winner_id_2>0) {
                $chaal = 0;
                $data['chaal'] = $chaal;
                $data['message'] = 'Game Completed';
                $data['game_status'] = 2;
                $data['winner_user_id'] = $game->winner_id;
                $data['winner_2_user_id'] = $game->winner_id_2;
            }
        }elseif($table->no_of_players==4){
            if ($game->winner_id_3>0) {
                $chaal = 0;
                $data['chaal'] = $chaal;
                $data['message'] = 'Game Completed';
                $data['game_status'] = 2;
                $data['winner_user_id'] = $game->winner_id;
                $data['winner_2_user_id'] = $game->winner_id_2;
                $data['winner_3_user_id'] = $game->winner_id_3;
            }
        }
        
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    function get_chaal($game_id){

        $chaal = 0;
        
        $last_chaal = $this->Ludo_model->LastChaal($game_id);
        

        if($last_chaal->step==6){
            $chaal = $last_chaal->user_id;
        }else{
            $game_users = $this->Ludo_model->GameAllUser($game_id);
            $game = $this->Ludo_model->View($game_id);
            $winner_arr = [$game->winner_id,$game->winner_id_2,$game->winner_id_3];
            $element = 0;
            foreach ($game_users as $key => $value) {
                if ($value->user_id==$last_chaal->user_id) {
                    $element = $key;
                    break;
                }
            }
    
            $index = 0;
            foreach ($game_users as $key => $value) {
                $index = ($key+$element)%count($game_users);
                if ($key>0) {
                    // $game_users[$index]->user_id;
                    if (!$game_users[$index]->packed && !in_array($game_users[$index]->user_id,$winner_arr)) {

                        $chaal = $game_users[$index]->user_id;
                        break;
                        
                    }
                }
            }
        }

        return $chaal;
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

        // if(!$user[0]->ludo_table_id)
        // {
        //     $data['message'] = 'You Are Not On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

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

            $this->Ludo_model->Chat($chat_data);
        }

        $chat_list = $this->Ludo_model->ChatList($game->id);
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

        if (!$user[0]->ludo_table_id) {
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

        if ($this->Users_model->TipAdmin($this->data['tip'], $this->data['user_id'], $user[0]->ludo_table_id)) {
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

    public function my_card_post(){
        $user_id = $this->input->post('user_id');
        $token = $this->input->post('token');

        if (!$this->Users_model->TokenConfirm($user_id, $token)) {
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!empty($user_id)) {
            $data['cards'] = $this->Ludo_model->getMyCards($game->id, $user_id);
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function user_game_history_post(){
        $user_id = $this->data['user_id'];

        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->getLastGameOnTable($user[0]->ludo_table_id);

        if (!$game) {
            $data['message'] = 'This is First Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data['game_users'] = $this->Ludo_model->GameOnlyUser($game->id);

        $game_users_cards = array();
        foreach ($data['game_users'] as $key => $value) {
            $declare_log = $this->Ludo_model->GameLog($game->id, 1, '', $value->user_id);
            $game_users_cards[$key]['user'] = $value;
            $game_users_cards[$key]['user']->win = ($game->winner_id==$value->user_id) ? $game->user_winning_amt : $declare_log[0]->amount;
            $game_users_cards[$key]['user']->result = $declare_log[0]->action;
            $game_users_cards[$key]['user']->score = $declare_log[0]->points;
            $game_users_cards[$key]['user']->cards = json_decode($this->Ludo_model->GameLogJson($game->id, $value->user_id));
        }

        $data['game_users_cards'] = $game_users_cards;
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();

    }

    public function getTableUsers_post(){
        $user_id = $this->data['user_id'];
        $room_code = $this->data['room_code'];
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $user = $this->Ludo_model->TableUserByRoomCode($room_code);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
     
        $data['data'] = $user;
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();

    }

    public function getOpponentProfile_post(){
        $user_id = $this->data['user_id'];
        $opponent_userid = $this->data['opponent_userid'];
        if (empty($user_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$this->Users_model->TokenConfirm($user_id, $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }
        $user = $this->Users_model->UserProfile($opponent_userid);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
     
        $data['data'] = $user;
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();

    }

    public function get_table_master_bachpan_post(){
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

        // if ($user[0]->ludo_table_id) {
        //     $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
        //     $data['message'] = 'You are Already On Table';
        //     $data['table_data'] = $table_data;
        //     $data['no_of_players'] = $table_data[0]->no_of_players;
        //     $data['code'] = 205;
        //     $this->response($data, 200);
        //     exit();
        // }

        // if(!in_array($this->data['no_of_players'],array(2,3,4)))
        // {
        //     $data['message'] = 'Invalid No. Of Players';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table_data = $this->Ludo_model->getTableMaster('',4);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_private_table_ludo_bachpan_post(){
        if (empty($this->data['user_id']) || empty($this->data['boot_value']) || empty($this->data['room_code'])) {
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

        // if ($user[0]->table_id) {
        //     $data['message'] = 'You are Already On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table_data = [
            'boot_value' => $this->data['boot_value'],
            'maximum_blind' => 4,
            'chaal_limit' => (int) $this->data['boot_value']*128,
            'pot_limit' => (int) $this->data['boot_value']*1024,
            'private' => 2,
            'room_code' => $this->data['room_code'],
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $TableId = $this->Ludo_model->CreateTable($table_data);

        $table_user_data = [
            'ludo_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => 1,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data_user = $this->Ludo_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_id'] = $TableId;
        $data['table_data'] = $table_data_user;
        $data['table_code'] = $table_data['room_code'];
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_table_ludo_bachpan_post(){

        $action = 'join';

        if (empty($this->data['user_id']) || empty($this->data['boot_value'])) {
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

        // if ($user[0]->ludo_table_id) {
        //     $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
        //     $data['message'] = 'You are Already On Table';
        //     $data['table_data'] = $table_data;
        //     $data['no_of_players'] = $table_data[0]->no_of_players;
        //     $data['code'] = HTTP_OK;
        //     $this->response($data, 200);
        //     exit();
        // }

        // if(!in_array($this->data['no_of_players'],array(2,3,4)))
        // {
        //     $data['message'] = 'Invalid No. Of Players';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $isMaster = $this->Ludo_model->getTableMaster($this->data['boot_value'],4);
        if (empty($isMaster)) {
            $data['message'] = 'Invalid Boot Value';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $joining_amount = $this->Setting_model->Setting()->joining_amount;
        if ($user[0]->wallet<$isMaster[0]->boot_value) {
            $data['message'] = 'Required Minimum '.number_format($isMaster[0]->boot_value).' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_amount = $isMaster[0]->boot_value;

        $tables = $this->Ludo_model->getCustomizeActiveTable($table_amount,4);
        $seat_position = 1;
        $room_code = '';

        if ($tables) {
            foreach ($tables as $value) {
                
                $table_games = $this->Ludo_model->getAllGameOnTable($value->ludo_table_id);
                if (count($table_games)==0) {
                    if ($value->members<4) {
                        $TableId = $value->ludo_table_id;
                        $seat_position = $this->Ludo_model->GetSeatOnTable($TableId);
                        $room_code = $value->room_code;
                        if(!$seat_position){
                            $seat_position = 1;
                            $TableId = '';
                            break;
                        }
                    }
                }
            }
        }

        if (empty($TableId)) {
            $room_code = uniqid();
            $table_data = [
                'boot_value' => $table_amount,
                'no_of_players' => 4,
                'room_code' => $room_code,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $TableId = $this->Ludo_model->CreateTable($table_data);

            $action = 'create';

            // $admin_mobile = $this->Setting_model->Setting()->mobile;
            // if (!empty($admin_mobile)) {
            //     $admin_user = $this->Users_model->UserByMobile($admin_mobile);
            //     if ($admin_user) {
            //         if (!empty($admin_user->fcm)) {
            //             $fcm_data['msg'] = PROJECT_NAME;
            //             $fcm_data['title'] = "New User On Ludo Table Boot Value ".$table_amount;
            //             $return = push_notification_android($admin_user->fcm, $fcm_data);
            //             // print_r($return);
            //         }
            //     }
            // }
        }

        $table_user_data = [
            'ludo_table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data = $this->Ludo_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['room_code'] = $room_code;
        $data['action'] = $action;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function join_table_with_code_bachpan_post(){

        if (empty($this->data['user_id']) || empty($this->data['room_code'])) {
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

        // if ($user[0]->ludo_table_id) {
        //     $data['message'] = 'You are Already On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // if (!$this->Ludo_model->isTable($this->data['code'])) {
        //     $data['message'] = 'Invalid Table Id';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table = $this->Ludo_model->isTableAvailCodeBachpan($this->data['room_code']);
        if (!$table) {
            $data['message'] = 'Invalid Table Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$table->boot_value) {
            $data['message'] = 'Required Minimum '.$table->boot_value.' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'ludo_table_id' => $table->id,
            'user_id' => $user[0]->id,
            'seat_position' => $this->Ludo_model->GetSeatOnTable($table->id),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Ludo_model->AddTableUser($table_user_data);

        $table_data = $this->Ludo_model->TableUser($table->id);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function start_game_bachpan_post(){
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

        // if (!$user[0]->ludo_table_id) {
        //     $data['message'] = 'You are Not On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        // if (count($table_data)<2) {
        //     $data['message'] = 'Unable to Create Game, Only One User On Table';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }


        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Ludo_model->isTableAvail($user[0]->ludo_table_id);
        $boot_value = $table->boot_value;

        if (count($table_data)>=2) {
            foreach ($table_data as $key => $value) {
                if ($value->wallet<$boot_value) {
                    $table_user_data = [
                        'table_id' => $value->ludo_table_id,
                        'user_id' => $value->user_id
                    ];

                    $this->Ludo_model->RemoveTableUser($table_user_data);
                    $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
                }
            }
        }

        if (count($table_data)>2) {
            foreach ($table_data as $key => $value) {
                if ($value->user_type==1) {
                    $table_user_data = [
                        'table_id' => $value->table_id,
                        'user_id' => $value->user_id
                    ];

                    $this->Ludo_model->RemoveTableUser($table_user_data);
                    $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);
                }
            }
        }

        $amount = $table->boot_value;
        $Cards = $this->Ludo_model->GetStartCards(16);
        // $joker = $Cards[0]->cards;
        // $Cards = [];
        $game_data = [
            'ludo_table_id' => $user[0]->ludo_table_id,
            'amount' => count($table_data)*$amount,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $GameId = $this->Ludo_model->Create($game_data);
        $drop_card = "";

        $end = 0;
        foreach ($table_data as $key => $value) {
            $start = $end;
            $end = $end+4;
            for ($i=$start; $i < $end; $i++) {

        //         if(empty($drop_card) && substr(trim($joker, '_'), 2)!=substr(trim($Cards[$i]->cards, '_'), 2)){
        //             $drop_card = $Cards[$i]->cards;
        //             $i++;
        //             $end++;
        //         }
        //         // else{
                    $table_user_data = [
                        'game_id' => $GameId,
                        'user_id' => $value->user_id,
                        'card' => $Cards[$i]->cards,
                        'added_date' => date('Y-m-d H:i:s'),
                        'updated_date' => date('Y-m-d H:i:s'),
                        'isDeleted' => 0
                    ];
    
                    $this->Ludo_model->GiveGameCards($table_user_data);
        //         // }
            }

        //     $this->Ludo_model->AddGameCount($value->user_id);

            $this->Ludo_model->MinusWallet($value->user_id, $amount);
            log_statement ($value->user_id, LD, -$amount,$GameId,0);

            $game_log = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'action' => 0,
                'amount' => $amount,
                'added_date' => date('Y-m-d H:i:s')
            ];

            $this->Ludo_model->AddGameLog($game_log);
        }

        $data['message'] = 'Success';
        $data['game_id'] = $GameId;
        $data['table_amount'] = $amount;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function make_winner_bachpan_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['room_code'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        // $post_data_expected = json_encode($this->data);
        $log_data = [
            'response' => json_encode($this->data)
        ];
         $this->db->insert('response_log', $log_data);

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $winner_user_id = $this->data['user_id'];
        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Ludo_model->getActiveUsersByRoomCode($this->data['room_code']);
        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($game->winner_id==0) {
            $comission = $this->Setting_model->Setting()->admin_commission;
            $this->Ludo_model->MakeWinner($game->id, $game->amount, $winner_user_id, $comission);
            $games = $this->Ludo_model->View($game->id);
            $data['message'] = 'Success';
            $data['winner'] = $winner_user_id;
            $data['games'] = $games;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }
        // }

        $data['message'] = 'Invalid Show1_'.$game->winner_id;

        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function leave_table_bachpan_post(){
        $timeout = '';
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

        if (!$user[0]->ludo_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $table_data = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        // foreach ($table_data as $value) {
        // if($value->mobile)
        $table_user_data = [
                'table_id' => $user[0]->ludo_table_id,
                'user_id' => $user[0]->id
            ];

        $this->Ludo_model->RemoveTableUser($table_user_data);
        // }

        $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);

        if ($game) {
            $isUserGame = $this->Ludo_model->GameOnlyUser($game->id, $user[0]->id);
            if ($isUserGame) {
                $table = $this->Ludo_model->isTableAvail($user[0]->ludo_table_id);
                // $boot_value = $table->boot_value;
                // $ChaalCount = $this->Ludo_model->ChaalCount($game->id, $this->data['user_id']);

                // $percent = $ChaalCount>0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                // $amount = round(($percent / 100) * $boot_value, 2);
                $amount = $table->amount;

                $this->Ludo_model->PackGame($this->data['user_id'], $game->id, $timeout, '', $amount);
                // $this->Ludo_model->MinusWallet($this->data['user_id'], $amount);
                // log_statement ($this->data['user_id'], LD, -$amount,$game->id,0);
                $game_users = $this->Ludo_model->GameUser($game->id);

                // echo count($game_users);
                if (count($game_users)==1) {
                    $game = $this->Ludo_model->getActiveGameOnTable($user[0]->ludo_table_id);
                    $comission = $this->Setting_model->Setting()->admin_commission;
                    // $this->Ludo_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
                }
            }
        }

        $table_users = $this->Ludo_model->TableUser($user[0]->ludo_table_id);

        if (count($table_users)==1) {
            if ($table_users[0]->user_type==1) {
                $table_user_data = [
                    'table_id' => $table_users[0]->table_id,
                    'user_id' => $table_users[0]->user_id
                ];

                $this->Ludo_model->RemoveTableUser($table_user_data);
            }
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function Statement_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
       
        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
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
        $statement = $this->Ludo_model->LudoStatement($this->data['user_id']);

            $data['message'] = 'Success';
            $data['data'] = $statement;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
       
    }
}
