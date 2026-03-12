<?php

use phpDocumentor\Reflection\Types\Object_;
use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Belote extends REST_Controller
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

        // echo getToken();
        if ($header['Token'] != getToken()) {
            $data['message'] = 'Invalid Authorization';
            $data['code'] = HTTP_METHOD_NOT_ALLOWED;
            $this->response($data, HTTP_OK);
            exit();
        }

        $this->data = $this->input->post();
        // print_r($this->data['user_id']);
        $this->load->model([
            'Belote_model',
            'Setting_model',
            'Users_model'
        ]);
    }

    public function sendNotification($TableId)
    {
        $userdata = $this->Users_model->FreeUserList();

        foreach ($userdata as $value) {
            if (!empty($value->fcm)) {
                $data['msg'] = "New User Joined Table";
                $data['title'] = "Teen Patti";
                $data['table_id'] = $TableId;
                push_notification_android($value->fcm, $data);
            }
        }
    }

    public function get_table_master_post()
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

        if ($user[0]->rummy_pool_table_id) {
            $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['no_of_players'] = $table_data[0]->no_of_players;
            $data['code'] = 205;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Belote_model->getTableMaster('',$this->data['no_of_players']);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_table_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['boot_value'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if(!in_array($this->data['no_of_players'],array(2,5)))
        // {
        //     $data['message'] = 'Invalid No. Of Players';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

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


        if ($user[0]->rummy_pool_table_id) {
            $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['no_of_players'] = $table_data[0]->no_of_players;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $isMaster = $this->Belote_model->getTableMaster($this->data['boot_value'],$this->data['no_of_players']);
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

        $tables = $this->Belote_model->getCustomizeActiveTable($table_amount,$this->data['no_of_players']);
        $seat_position = 1;

        if ($tables) {
            foreach ($tables as $value) {
                if ($value->members<$isMaster[0]->no_of_players) {
                    // foreach ($tables as $key => $value) {
                    $table_games = $this->Belote_model->getAllGameOnTable($value->rummy_pool_table_id);
                    if (count($table_games)==0) {
                        $TableId = $value->rummy_pool_table_id;
                        $seat_position = $this->Belote_model->GetSeatOnTable($TableId);
                    }
                    // }
                }
            }
        }

        if (empty($TableId)) {
            $table_data = [
                'boot_value' => $isMaster[0]->boot_value,
                'pool_point' => $isMaster[0]->pool_point,
                'no_of_players' => $this->data['no_of_players'],
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $TableId = $this->Belote_model->CreateTable($table_data);

            // $bot = $this->Users_model->GetFreeRummyBot();

            // if ($bot) {
            //     $table_bot_data = [
            //         'table_id' => $TableId,
            //         'user_id' => $bot[0]->id,
            //         'seat_position' => 2,
            //         'added_date' => date('Y-m-d H:i:s'),
            //         'updated_date' => date('Y-m-d H:i:s')
            //     ];

            //     $this->Belote_model->AddTableUser($table_bot_data);
            // }
        }

        $table_user_data = [
            'table_id' => $TableId,
            'user_id' => $user[0]->id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Belote_model->AddTableUser($table_user_data);

        $table_data = $this->Belote_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function create_table_post()
    {
        if (empty($this->data['user_id']) || $this->data['boot_value']=='' || empty($this->data['name']) || empty($this->data['max_player']) || $this->data['viewer_status']=='' || $this->data['private']=='') {
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

        if (!in_array($this->data['max_player'], array(3,4,5,6,7))) {
            $data['message'] = 'Invalid No. Of Players';
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

        // $joining_amount = $this->Setting_model->Setting()->joining_amount;
        $joining_amount = $this->data['boot_value'];
        if ($user[0]->wallet<$joining_amount) {
            $data['message'] = 'Required Minimum '.number_format($joining_amount).' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->rummy_pool_table_id) {
            $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $table_amount = $this->data['boot_value'];
        $name = $this->data['name'];
        $user_id = $this->data['user_id'];
        $max_player = $this->data['max_player'];
        $viewer_status = $this->data['viewer_status'];
        $private = $this->data['private'];

        $table_data = [
            'boot_value' => $table_amount,
            'name' => $name,
            'founder_id' => $user_id,
            'max_player' => $max_player,
            'invitation_code' => uniqid(),
            'viewer_status' => $viewer_status,
            'private' => $private,
            'password' => ($private==1) ? rand(1000, 9999) : '',
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $seat_position = 1;
        $TableId = $this->Belote_model->CreateTable($table_data);

        $table_user_data = [
            'table_id' => $TableId,
            'user_id' => $user_id,
            'seat_position' => $seat_position,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Belote_model->AddTableUser($table_user_data);

        if ($this->data['boot_value']==0) {
            // $bot = $this->Users_model->GetFreeBot();
            // for ($i=0; $i < 3; $i++) {
            //     $seat_position = $seat_position+1;
            //     if ($bot[$i]) {
            //         $table_user_data = [
            //             'table_id' => $TableId,
            //             'user_id' => $bot[$i]->id,
            //             'seat_position' => $seat_position,
            //             'added_date' => date('Y-m-d H:i:s'),
            //             'updated_date' => date('Y-m-d H:i:s')
            //         ];

            //         $this->Belote_model->AddTableUser($table_user_data);
            //     }
            // }
        }

        $table_data = $this->Belote_model->TableUser($TableId);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function get_public_table_post()
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

        if ($user[0]->rummy_pool_table_id) {
            $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $data['message'] = 'You are Already On Table';
            $data['table_data'] = $table_data;
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $tables = $this->Belote_model->getPublicActiveTable();

        foreach ($tables as $key => $value) {
            $table_games = $this->Belote_model->getAllGameOnTable($value->id);
            $value->ongoing = (count($table_games)!=0) ? 1 : 0;
        }

        $data['message'] = 'Success';
        $data['table_data'] = $tables;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function join_table_post()
    {
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

        if ($user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Already On Table';
            $data['table_id'] = $user[0]->rummy_pool_table_id;
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        $table = $this->Belote_model->isTableAvail($this->data['table_id']);
        if (!$table) {
            $data['message'] = 'Invalid Invitation Code';
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

        // $table_user = $this->Belote_model->TableUser($table->id);

        // if (count($table_user)>=$table->max_player) {
        //     $data['message'] = 'Max Player Already Reached';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $table_games = $this->Belote_model->getAllGameOnTable($table->id);
        if (count($table_games)!=0) {
            $data['message'] = 'Game Already Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_user_data = [
            'table_id' => $table->id,
            'user_id' => $user[0]->id,
            'seat_position' => $this->Belote_model->GetSeatOnTable($table->id),
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $this->Belote_model->AddTableUser($table_user_data);

        $table_data = $this->Belote_model->TableUser($table->id);

        $data['message'] = 'Success';
        $data['table_data'] = $table_data;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function start_game_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Belote_model->TableUserCardPosition($user[0]->rummy_pool_table_id);

        if (count($table_data)<2) {
            $data['message'] = 'Minimum 2 Players Required to Start the Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if(count($table_data)>2)
        // {
        //     foreach ($table_data as $key => $value) {
        //         if($value->mobile=="")
        //         {
        //             $table_user_data = [
        //                 'table_id' => $value->rummy_pool_table_id,
        //                 'user_id' => $value->user_id
        //             ];

        //             $this->Belote_model->RemoveTableUser($table_user_data);
        //             $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
        //         }
        //     }
        // }

        $new_table_data = $table_data;
        $last_game = $this->Belote_model->getLastGameOnTable($user[0]->rummy_pool_table_id);
        if ($last_game) {
            $last_game_winner = $last_game->winner_id;
            $position = 0;
            foreach ($table_data as $k => $val) {
                if ($val->user_id==$last_game_winner) {
                    $position = $k;
                }
            }
            $new_table_data = $this->rearrange_array($table_data, $position);
        }

        $table = $this->Belote_model->isTableAvail($user[0]->rummy_pool_table_id);
        $table_games = $this->Belote_model->getAllGameOnTable($user[0]->rummy_pool_table_id);
        $amount = (count($table_games)==0) ? $table->boot_value : 0;

        $Cards = $this->Belote_model->GetCards((count($new_table_data)*RUMMY_CARDS)+2);
        $joker = $Cards[0]->cards;
        $game_data = [
            'table_id' => $user[0]->rummy_pool_table_id,
            'amount' => count($new_table_data)*$amount,
            'joker' => $joker,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $GameId = $this->Belote_model->Create($game_data);

        // $Cards = $this->Belote_model->GetCards(count($table_data)*13);
        $drop_card = "";
        $end = 1;
        foreach ($new_table_data as $key => $value) {
            $start = $end;
            $end = $end+RUMMY_CARDS;
            for ($i=$start; $i < $end; $i++) {

                if(empty($drop_card) && substr(trim($joker, '_'), 2)!=substr(trim($Cards[$i]->cards, '_'), 2)){
                    $drop_card = $Cards[$i]->cards;
                    $i++;
                    $end++;
                }

                $table_user_data = [
                    'game_id' => $GameId,
                    'user_id' => $value->user_id,
                    'card' => $Cards[$i]->cards,
                    'added_date' => date('Y-m-d H:i:s'),
                    'updated_date' => date('Y-m-d H:i:s'),
                    'isDeleted' => 0
                ];

                $this->Belote_model->GiveGameCards($table_user_data);
            }

            if ($amount!=0) {
                $this->Belote_model->MinusWallet($value->user_id, $amount);
            }

            $this->Belote_model->AddGameCount($value->user_id);

            $game_log = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'action' => 0,
                'amount' => ($amount==0)?0:(-$amount),
                'added_date' => date('Y-m-d H:i:s')
            ];

            $this->Belote_model->AddGameLog($game_log);
        }

        $table_user_data = [
            'game_id' => $GameId,
            'user_id' => 0,
            'card' => $drop_card
        ];

        $this->Belote_model->StartDropGameCards($table_user_data);

        $data['message'] = 'Success';
        $data['game_id'] = $GameId;
        $data['table_amount'] = $amount;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function rejoin_game_amount_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_id = $user[0]->rummy_pool_table_id;

        $table = $this->Belote_model->isTableAvail($table_id);

        $game = $this->Belote_model->getLastGameOnTable($table_id);
        $rejoin_log = $this->Belote_model->rejoin_log($table_id, $this->data['user_id']);

        $amount = (!empty($rejoin_log)) ? ($rejoin_log->amount*2) : $table->boot_value;
        $rejoin_amount = $amount;

        $data['message'] = 'Success';
        $data['rejoin_amount'] = $rejoin_amount;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function rejoin_game_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
        $table_id = $user[0]->rummy_pool_table_id;
        // $table_id = $this->data['table_id'];

        $table = $this->Belote_model->isTableAvail($table_id);

        $game = $this->Belote_model->getLastGameOnTable($table_id);
        $rejoin_log = $this->Belote_model->rejoin_log($table_id, $this->data['user_id']);

        $amount = (!empty($rejoin_log)) ? ($rejoin_log->amount*2) : $table->boot_value;
        $rejoin_amount = $amount;

        $this->Belote_model->MinusWallet($this->data['user_id'], $rejoin_amount);

        $rejoin_data = [
            'user_id' => $this->data['user_id'],
            'table_id' => $table_id,
            'game_id' => $game->id,
            'amount' => $rejoin_amount,
        ];
        $this->Belote_model->Rejoin($rejoin_data);

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function leave_table_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);

        // foreach ($table_data as $value) {
        // if($value->mobile)
        $table_user_data = [
                'table_id' => $user[0]->rummy_pool_table_id,
                'user_id' => $user[0]->id
            ];

        $this->Belote_model->RemoveTableUser($table_user_data);
        // }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if ($game) {
            $this->Belote_model->PackGame($this->data['user_id'], $user[0]->rummy_pool_table_id, $game->id);
            $game_users = $this->Belote_model->GameUser($game->id);

            if (count($game_users)==1) {
                $comission = $this->Setting_model->Setting()->admin_commission;

                $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);

                $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                $this->Belote_model->MakeWinner($game->id, 0, $game_users[0]->user_id, $admin_winning_amt);
                $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $user[0]->rummy_pool_table_id, $game_users[0]->user_id);
                $this->Belote_model->AddToWallet($user_winning_amt, $game_users[0]->user_id);
            }
        }

        $table_users = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);

        if (count($table_users)==3) {
            foreach ($table_users as $ke => $valu) {
                if ($valu->user_type==1) {
                    $table_user_data = [
                        'table_id' => $valu->rummy_pool_table_id,
                        'user_id' => $valu->user_id
                    ];

                    $this->Belote_model->RemoveTableUser($table_user_data);
                }
            }
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function pack_game_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Belote_model->isTableAvail($user[0]->rummy_pool_table_id);

        // $game_log = $this->Belote_model->GameLog($game->id,1);

        // $game_users = $this->Belote_model->GameAllUser($game->id);

        // $chaal = 0;
        // $element = 0;
        // foreach ($game_users as $key => $value) {
        //     if($value->user_id==$game_log[0]->user_id)
        //     {
        //         $element = $key;
        //         break;
        //     }
        // }

        // $index = 0;
        // foreach ($game_users as $key => $value) {

        //     $index = ($key+$element)%count($game_users);
        //     if($key>0)
        //     {
        //         if(!$game_users[$index]->packed)
        //         {
        //             $chaal = $game_users[$index]->user_id;
        //             break;
        //         }
        //     }
        // }

        // echo $chaal;
        // if($chaal==$this->data['user_id'])
        // {
        $ChaalCount = $this->Belote_model->ChaalCount($game->id, $this->data['user_id']);

        $percent = $ChaalCount>0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
        $this->Belote_model->PackGame($this->data['user_id'], $user[0]->rummy_pool_table_id, $game->id, $timeout, $this->input->post('json'), '', $percent);
        $game_users = $this->Belote_model->GameUser($game->id);

        if (count($game_users)==1) {
            $amount = 0;
            // $this->Belote_model->MinusWallet($this->data['user_id'], $amount);
            $this->Belote_model->MakeWinner($game->id, $amount, $game_users[0]->user_id);
            $winner_data = ['points'=>0, 'table_id'=>$user[0]->rummy_pool_table_id,'user_id'=>$game_users[0]->user_id,'game_id'=>$game->id,'json'=>''];
            // print_r($winner_data);
            $this->Belote_model->Declare($winner_data);

            $All_table_users = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            if (count($All_table_users)>=2) {
                $exceed_count = 1;
                $user_ids = array();
                foreach ($All_table_users as $key => $value) {
                    // if ($value->total_points>MAX_POINT) {
                    if ($value->total_points>$table->pool_point) {
                        $exceed_count++;
                        $user_ids[] = $value->user_id;
                    } else {
                        $winner_user_id = $value->user_id;
                    }
                }

                if (count($All_table_users)==$exceed_count) {
                    // Remove From Table Code
                    foreach ($user_ids as $val) {
                        $table_user_data = [
                            'table_id' => $user[0]->rummy_pool_table_id,
                            'user_id' =>$val
                        ];

                        $this->Belote_model->RemoveTableUser($table_user_data);
                    }
                    // // Make Winner Code
                    $comission = $this->Setting_model->Setting()->admin_commission;
                    $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);
                    $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                    $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);
                    // $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user[0]->rummy_pool_table_id);
                    // $this->Belote_model->AddToWallet($TotalAmount, $winner_user_id);

                    $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $user[0]->rummy_pool_table_id, $winner_user_id);
                    $this->Belote_model->AddToWallet($user_winning_amt, $winner_user_id);
                }
            }

            // $comission = $this->Setting_model->Setting()->admin_commission;

            // $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);

            // $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
            // $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

            // $this->Belote_model->MakeWinner($game->id, 0, $game_users[0]->user_id, $admin_winning_amt);
            // $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $user[0]->rummy_pool_table_id, $game_users[0]->user_id);
            // $this->Belote_model->AddToWallet($user_winning_amt, $game_users[0]->user_id);
        }

        // if ($timeout==1) {
        //     $table_user_data = [
        //             'table_id' => $user[0]->rummy_pool_table_id,
        //             'user_id' => $user[0]->id
        //         ];

        //     $this->Belote_model->RemoveTableUser($table_user_data);
        // }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
        // }

        // $data['message'] = 'Invalid Pack';
        // $data['code'] = HTTP_NOT_ACCEPTABLE;
        // $this->response($data, 200);
        // exit();
    }

    public function get_card_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_log = $this->Belote_model->GameLog($game->id, 1);

        $game_users = $this->Belote_model->GameAllUser($game->id);

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
                if (!$game_users[$index]->packed) {
                    $chaal = $game_users[$index]->user_id;
                    break;
                }
            }
        }

        // echo $chaal;
        if ($chaal==$user_id) {
            $cards = $this->Belote_model->getMyCards($game->id, $user_id);

            if (count($cards)>RUMMY_CARDS) {
                $data['message'] = 'Please Drop Card And Then Pick One';
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            $random_card = $this->Belote_model->GetRamdomGameCard($game->id);
            if (empty($random_card)) {
                $this->Belote_model->deleteDropCard($game->id);
                $random_card = $this->Belote_model->GetRamdomGameCard($game->id);
            }

            if ($random_card) {
                $table_user_data = [
                    'game_id' => $game->id,
                    'user_id' => $user_id,
                    'card' => $random_card[0]->cards,
                    'added_date' => date('Y-m-d H:i:s'),
                    'updated_date' => date('Y-m-d H:i:s'),
                    'isDeleted' => 0
                ];

                $this->Belote_model->GiveGameCards($table_user_data);

                $data['message'] = 'Success';
                $data['code'] = HTTP_OK;
                $data['card'] = $random_card;
                $this->response($data, HTTP_OK);
                exit();
            }

            $data['message'] = 'No Cards Left';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data['message'] = 'Wait For Your Chaal';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function drop_card_post()
    {
        $user_id = $this->data['user_id'];
        $card = $this->data['card'];

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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $cards = $this->Belote_model->getMyCards($game->id, $user_id);

        if (count($cards)<=RUMMY_CARDS) {
            $data['message'] = 'Please Get Or Pick Card First And Then Drop One';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $isCardAvailable = $this->Belote_model->getMyCards($game->id, $user_id, $card);
        // print_r($isCardAvailable);
        if ($isCardAvailable) {
            // $joker_num = substr(trim($game->joker,'_'), 2);
            // $card_num = substr(trim($card,'_'), 2);

            if ($card=='JKR1' || $card=='JKR2') {
                $data['message'] = 'You Can\'t Drop Joker Card';
                $data['code'] = HTTP_NOT_ACCEPTABLE;
                $this->response($data, 200);
                exit();
            }

            $table_user_data = [
                'game_id' => $game->id,
                'user_id' => $user_id,
                'card' => $card
            ];

            $this->Belote_model->DropGameCards($table_user_data, $this->input->post('json'));

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Card';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function get_drop_card_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $cards = $this->Belote_model->getMyCards($game->id, $user_id);
        // echo count($cards);
        if (count($cards)>RUMMY_CARDS) {
            $data['message'] = 'Please Drop Card And Then Pick One';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $drop_card = $this->Belote_model->GetAndDeleteGameDropCard($game->id);

        if ($drop_card) {
            $table_user_data = [
                'game_id' => $game->id,
                'user_id' => $user_id,
                'card' => $drop_card[0]->card,
                'is_drop_card' => 1,
                'added_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s'),
                'isDeleted' => 0
            ];

            $this->Belote_model->GiveGameCards($table_user_data);

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $data['card'] = $drop_card;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Chaal';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function declare_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $cards = $this->Belote_model->getMyCards($game->id, $this->data['user_id']);
        // echo count($cards);
        if (count($cards)>RUMMY_CARDS) {
            $data['message'] = 'Please Drop Card And Then Declare';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $amount = 0;

        $game_log = $this->Belote_model->GameLog($game->id, 1);

        if ($game_log[0]->action==3) {
            $data['message'] = 'Already Declare';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_users = $this->Belote_model->GameAllUser($game->id);

        $remain_game_users = $this->Belote_model->GameUser($game->id);
        // print_r($remain_game_users);

        // if(count($remain_game_users)!=2)
        // {
        //     $data['message'] = 'Show can be done between 2 users only';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $chaal = 0;
        // $element = 0;
        // foreach ($game_users as $key => $value) {
        //     if($value->user_id==$game_log[0]->user_id)
        //     {
        //         $element = $key;
        //         break;
        //     }
        // }

        // $index = 0;
        // foreach ($game_users as $key => $value) {

        //     $index = ($key+$element)%count($game_users);
        //     if($key>0)
        //     {
        //         if(!$game_users[$index]->packed)
        //         {
        //             $chaal = $game_users[$index]->user_id;
        //             break;
        //         }
        //     }
        // }

        // $game_users = $this->Belote_model->GameUser($game->id);

        // if($chaal==$this->data['user_id'])
        // {
        // $arr = json_decode($this->data['json']);
        // $count = 0;
        // $wrong = 0;
        // foreach ($arr as $key => $value) {
        //     $count = $count+count($value->cards);
        //     if($value->card_group==0)
        //     {
        //         $wrong = 1;
        //         break;
        //     }
        // }

        // if($remain_game_users[0]->user_id!=$this->data['user_id'])
        // {
        //     $other_user_id = $remain_game_users[1]->user_id;
        // }
        // else
        // {
        //     $other_user_id = $remain_game_users[0]->user_id;
        // }
        // $user_id = ($wrong)?$this->data['user_id']:$other_user_id;
        // $lose_user_id = (!$wrong)?$this->data['user_id']:$other_user_id;

        $arr = json_decode($this->data['json']);
        $count = 0;

        foreach ($arr as $key => $value) {
            if ($value->card_group==0) {
                $count = $count+$this->card_points($value->cards, $game->joker);
            }
        }

        if ($count>0) {
            $data['message'] = 'Wrong Declare';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data = [
                'user_id' => $this->data['user_id'],
                'game_id' => $game->id,
                'table_id' => $user[0]->rummy_pool_table_id,
                'points' => $count,
                'json' => $this->data['json']
            ];
        $this->Belote_model->Declare($data);

        // $this->Belote_model->Show($game->id,$amount,$this->data['user_id']);

        // $amount = 800;
        // $this->Belote_model->MinusWallet($lose_user_id,$amount);
        // $this->Belote_model->MakeWinner($game->id,$amount,$user_id);
        $data['message'] = 'Success';
        // $data['winner'] = $user_id;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
        // }

        $data['message'] = 'Wait For Your Chaal';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function declare_back_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Belote_model->isTableAvail($user[0]->rummy_pool_table_id);
        $amount = 0;

        $game_log = $this->Belote_model->GameLog($game->id, 1);

        $remain_game_users = $this->Belote_model->GameUser($game->id);
        if ($game_log[0]->action!=3) {
            $data['message'] = 'Invalid Declare Back';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $arr = json_decode($this->data['json']);

        $count = 0;
        $wrong = 0;

        foreach ($arr as $key => $value) {
            if ($value->card_group==0) {
                $count = $count+$this->card_points($value->cards, $game->joker);
                $wrong = 1;
            }
        }

        $count = ($count>80) ? 80 : $count;

        $data_log = [
            'user_id' => $this->data['user_id'],
            'game_id' => $game->id,
            'table_id' => $user[0]->rummy_pool_table_id,
            'points' => $count,
            'json' => $this->data['json']
        ];
        $this->Belote_model->Declare($data_log);

        $declare_log = $this->Belote_model->GameLog($game->id, '', 3);
        $declare_count = count($declare_log);
        if (count($remain_game_users)<=$declare_count) {
            $amount = 0;
            $winner_id = $declare_log[$declare_count-1]->user_id;
            // $this->Belote_model->MakeWinner($game->id, $amount, $winner_id);
            $comission = $this->Setting_model->Setting()->admin_commission;

            $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);

            $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
            $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

            $this->Belote_model->MakeWinner($game->id, 0, $winner_id, 0);

            $All_table_users = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            if (count($All_table_users)>=2) {
                $exceed_count = 1;
                $user_ids = array();
                foreach ($All_table_users as $key => $value) {
                    // if ($value->total_points>MAX_POINT) {
                    if ($value->total_points>$table->pool_point) {
                        $exceed_count++;
                        $user_ids[] = $value->user_id;
                    } else {
                        $winner_user_id = $value->user_id;
                    }
                }

                if (count($All_table_users)==$exceed_count) {
                    // Remove From Table Code
                    foreach ($user_ids as $val) {
                        $table_user_data = [
                            'table_id' => $user[0]->rummy_pool_table_id,
                            'user_id' =>$val
                        ];

                        $this->Belote_model->RemoveTableUser($table_user_data);
                    }
                    // // Make Winner Code
                    // $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);
                    // $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user[0]->rummy_pool_table_id);
                    // $this->Belote_model->AddToWallet($TotalAmount, $winner_user_id);

                    $this->Belote_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $user[0]->rummy_pool_table_id, $winner_user_id);
                    $this->Belote_model->AddToWallet($user_winning_amt, $winner_user_id);
                }
            }
        }


        $data['message'] = 'Success';
        $data['winner'] = 0;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();

        // $data['message'] = 'Invalid Show';
        // $data['code'] = HTTP_NOT_ACCEPTABLE;
        // $this->response($data, 200);
        // exit();
    }

    public function card_points($cards, $joker='')
    {
        $sum = 0;
        foreach ($cards as $key => $card) {
            // Joker Point is Zero
            if ($card=='JKR1' || $card=='JKR2') {
                continue;
            }

            $card_value = substr(str_replace('_', '', $card), 2);
            $joker_value = substr(str_replace('_', '', $joker), 2);

            // Middle Joker Point is Zero
            if ($card_value==$joker_value) {
                continue;
            }

            $card_int = (int) $card_value;
            $sum += ($card_int==0) ? 10 : $card_int;
        }
        return $sum;
    }

    public function chat_post()
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        // if(!$game)
        // {
        //     $data['message'] = 'Game Not Started';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $game['id'] = 1000;
        // $game = (Object) $game;

        $chat = $this->input->post('chat');

        if (!empty($chat)) {
            $chat_data = [
                'user_id' => $this->data['user_id'],
                'chat' => $chat,
                // 'game_id' => $game->id
                'rummy_pool_table_id' => $user[0]->rummy_pool_table_id
            ];
            $chat_id = $this->Belote_model->Chat($chat_data);
            $table_users = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);

            foreach ($table_users as $key => $value) {
                if (!empty($value->fcm)) {
                    $chat_data['title'] = $user[0]->name;
                    $chat_data['image'] = $user[0]->profile_pic;
                    $chat_data['message'] = $chat;
                    $chat_data['chat_id'] = $chat_id;
                    $chat_data['sender_id'] = $this->data['user_id'];
                    push_notification_android($value->fcm, $chat_data);
                }
            }
        }

        $chat_list = $this->Belote_model->ChatList($user[0]->rummy_pool_table_id);
        $data['message'] = 'Success';
        $data['list'] = $chat_list;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function tip_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
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

        if ($this->Users_model->TipAdmin($this->data['tip'], $this->data['user_id'], $user[0]->rummy_pool_table_id)) {
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

    public function card_value_post()
    {
        $user_id = $this->data['user_id'];
        $token = $this->data['token'];
        $card_1 = $this->input->post('card_1');
        $card_2 = $this->input->post('card_2');
        $card_3 = $this->input->post('card_3');
        $card_4 = $this->input->post('card_4');
        $card_5 = $this->input->post('card_5');
        $card_6 = $this->input->post('card_6');

        if (empty($user_id) || empty($card_1) || empty($card_2) || empty($card_3)) {
            $data['message'] = 'Minimum 3 cards Needed For Grouping';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $active_game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);
        $joker = "";
        if ($active_game) {
            // $game_id = $active_game->id;
            $joker = $active_game->joker;
            // $data['active_game_id'] = $active_game->id;
            // $data['game_status'] = 1;
        }

        $card_value = $this->Belote_model->CardValue('', $card_1, $card_2, $card_3, $card_4, $card_5, $card_6);
        if ($card_value) {
            // echo $joker;
            // print_r($card_value);
            if ($card_value[0]==0) {
                $card_value = $this->Belote_model->CardValue($joker, $card_1, $card_2, $card_3, $card_4, $card_5, $card_6);
            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $data['card_value'] = $card_value;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Card Value';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function status_post()
    {
        $user_id = $this->input->post('user_id');
        $table_id = $this->input->post('table_id');

        if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        $user = $this->Users_model->UserProfile($user_id);
        $table_id = (!empty($user[0]->rummy_pool_table_id)) ? $user[0]->rummy_pool_table_id : $table_id;
        if (empty($table_id)) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = 403;
            $this->response($data, 200);
            exit();
        }

        if (!empty($table_id)) {
            // $data['start_game'] = $this->Belote_model->GetStartGame($table_id);
            $data['chat_list'] = $this->Belote_model->ChatList($table_id, 1);

            $table_data = $this->Belote_model->TableUser($table_id);
            // $data['table_users'] = $table_data;

            $table_new_data = array();
            for ($i=0; $i < 6; $i++) {
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
                $table_new_data[$i]['total_points'] = 0;
                $table_new_data[$i]['wallet'] = 0;
                $table_new_data[$i]['invested'] = 0;
            }

            foreach ($table_data as $t => $u) {
                $seat = $u->seat_position-1;
                $amount = $this->Belote_model->Invested($table_id, $u->seat_position)->amount;
                $table_new_data[$seat] = $u;
                $table_new_data[$seat]->invested = is_null($amount) ? 0 : $amount;
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
            $table = $this->Belote_model->isTableAvail($table_id);
            $data['table_detail'] = $table;
            $data['active_game_id'] = 0;
            $data['game_status'] = 0;
            $data['table_amount'] = $table->boot_value;
            $active_game = $this->Belote_model->getActiveGameOnTable($table_id);
            if ($active_game) {
                // $game_id = $active_game->id;
                $data['active_game_id'] = $active_game->id;
                $data['game_status'] = 1;
            }

            $points = $this->Belote_model->GetTablePoints($table_id);
            $points_arr = array();

            $score = array();
            $win_points = array();
            foreach ($points as $key => $value) {
                if ($key%3!=0) {
                    $points_arr[] = $value;
                    $win_points[$value->user_id] = $value->points;
                }
                $score[$value->user_id] = $value->points;
            }
            // $data['points'] = $points_arr;

            $data['points'] = $this->Belote_model->GetTablePoints($table_id);
        }

        $game_id = $this->input->post('game_id');
        if (empty($game_id)) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->View($game_id);
        if (empty($game)) {
            $data['message'] = 'Invalid Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_log = $this->Belote_model->GameLog($game_id, 1);

        $game_users = $this->Belote_model->GameAllUser($game_id);

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
                if (!$game_users[$index]->packed) {
                    $chaal = $game_users[$index]->user_id;
                    break;
                }
            }
        }

        $data['game_log'] = $game_log;
        $data['all_users'] = $table_data;
        $data['declare'] = false;
        $data['declare_user_id'] = 0;
        if ($game_log[0]->action==3) {
            $data['declare'] = true;
            $data['declare_user_id'] = $game_log[0]->user_id;
            // $data['game_users'] = $this->Belote_model->GameAllUser($game_id);
        }
        // else
        // {
        $data['game_users'] = $this->Belote_model->GameOnlyUser($game_id);
        $data['card_count'] = $this->Belote_model->GetGameTableCardCount($game_id);
        // }
        $data['chaal'] = $chaal;
        $data['game_amount'] = $game->amount;
        $data['last_card'] = $this->Belote_model->LastGameCard($game_id);
        $data['discarded_card'] = $this->Belote_model->DiscardedGameCard($game_id);
        $data['total_table_amount'] = $this->Belote_model->TotalAmountOnTable($table_id);
        $data['share_wallet'] = $this->Belote_model->GetShareWallet($table_id);

        // $chaalCount = $this->Belote_model->ChaalCount($game->id,$chaal);
        // if($chaalCount>3)
        // {
        //     $this->Belote_model->getMyCards($game->id,$chaal);
        // }

        if (!empty($user_id)) {
            // $data['cards'] = $this->Belote_model->getMyCards($game->id,$user_id);
            $table_games = $this->Belote_model->getAllGameOnTable($table_id);
            $data['round'] = count($table_games);
            if ($active_game) {
                $data['drop_card'] = $this->Belote_model->GetGameDropCard($active_game->id);
            } else {
                $data['drop_card'] = array();
            }

            $data['joker'] = $game->joker;
        }

        $data['message'] = 'Success';
        if ($game->winner_id>0) {
            $chaal = 0;
            $data['chaal'] = $chaal;
            $data['message'] = 'Game Completed';
            $game_users_cards = array();
            foreach ($data['game_users'] as $key => $value) {
                $game_users_cards[$key]['user'] = $value;
                // $game_users_cards[$key]['user']->win = ($game->winner_id==$value->user_id) ? 50 : 0;
                $game_users_cards[$key]['user']->win = (isset($score[$value->user_id])) ? $score[$value->user_id] : 0;
                // $game_users_cards[$key]['user']->score = ($game->winner_id==$value->user_id)?50:-50;
                $game_users_cards[$key]['user']->score = (isset($score[$value->user_id])) ? $score[$value->user_id] : 0;
                $game_users_cards[$key]['user']->total = $this->Belote_model->getTotalPoints($table_id, $value->user_id);
                $game_users_cards[$key]['user']->cards = json_decode($this->Belote_model->GameLogJson($game->id, $value->user_id));
            }
            $data['game_users_cards'] = $game_users_cards;
            $data['game_start_time'] = (strtotime($game->updated_date)+15)-time();
            $data['game_status'] = 2;
            $data['winner_user_id'] = $game->winner_id;
        }
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function my_card_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!empty($user_id)) {
            $data['cards'] = $this->Belote_model->getMyCards($game->id, $user_id);
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function share_wallet_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        if (!$user->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $TableUser = $this->Belote_model->TableUser($user->rummy_pool_table_id);
        if (count($TableUser)>1) {
            foreach ($TableUser as $k => $val) {
                if ($val->user_id!=$user->id) {
                    $this->Belote_model->ShareWallet($user->rummy_pool_table_id, $user->id, $val->user_id);
                }
            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Share Wallet';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function do_share_wallet_post()
    {
        $user_id = $this->data['user_id'];
        $share_wallet_id = $this->data['share_wallet_id'];
        $type = $this->data['type']; //1=accept,2=reject
        if (empty($user_id) || empty($share_wallet_id) || empty($type)) {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $share = $this->Belote_model->GetShareWalletById($share_wallet_id);

        $this->Belote_model->UpdateShareWallet($share_wallet_id, $type);

        if ($type==1) {
            $reject = false;
            $TableUser = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $user_count = count($TableUser)-1;
            $ShareWallet = $this->Belote_model->GetShareWalletLimit($user[0]->rummy_pool_table_id, $user_count);
            // print_r($ShareWallet);
            foreach ($ShareWallet as $key => $value) {
                if ($value['status']!=1) {
                    $reject = true;
                    break;
                }
            }

            if (!$reject) {
                // $TotalAmount = $this->Belote_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);
                $comission = $this->Setting_model->Setting()->admin_commission;

                $TotalAmount = $this->RummyDeal_model->TotalAmountOnTable($user[0]->rummy_deal_table_id);

                $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                $this->Belote_model->updateTotalWinningAmtTable($user_winning_amt, $user_winning_amt, $admin_winning_amt, $user[0]->rummy_pool_table_id, 0);
                $EachAmount = round($user_winning_amt/count($TableUser), 2);
                foreach ($TableUser as $ke => $val) {
                    $this->Belote_model->AddToWallet($EachAmount, $val->user_id); // Add Amount to User Amount

                    $table_user_data = [
                        'table_id' => $user[0]->rummy_pool_table_id,
                        'user_id' =>$val->user_id
                    ];

                    $this->Belote_model->RemoveTableUser($table_user_data);
                }
            }
            // $user1 = $this->Belote_model->GameUserCard($game->id,$slide->user_id);
            // $user2 = $this->Belote_model->GameUserCard($game->id,$slide->prev_id);
            // $remain_game_users[] = $user1;
            // $remain_game_users[] = $user2;

            // $user1 = $this->Belote_model->CardValue($remain_game_users[0]->card1,$remain_game_users[0]->card2,$remain_game_users[0]->card3);
            // $user2 = $this->Belote_model->CardValue($remain_game_users[1]->card1,$remain_game_users[1]->card2,$remain_game_users[1]->card3);

            // $winner = $this->Belote_model->getWinnerPosition($user1,$user2);

            // if($winner==2)
            // {
            //     $looser_id = $remain_game_users[0]->user_id;
            // }
            // else
            // {
            //     $looser = ($winner==1)?0:1;
            //     $looser_id = $remain_game_users[$looser]->user_id;
            // }

            // $this->Belote_model->PackGame($looser_id,$game->id);
        }

        // $lastChal = $this->Belote_model->LastChaal($game->id);

        // $seen = $lastChal->seen;
        // $amount = $lastChal->amount;

        // $card_seen = $this->Belote_model->isCardSeen($game->id,$share->user_id);

        // if($seen==0 && $card_seen==1)
        // {
        //     $amount = $amount*2;
        // }

        // if($seen==1 && $card_seen==0)
        // {
        //     $amount = $amount/2;
        // }

        // $this->Belote_model->Chaal($game->id,$amount,$share->user_id);

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function ask_start_game_post()
    {
        if (empty($this->data['user_id'])) {
            $data['message'] = 'Invalid Parameter';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user = $this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token']);
        if (!$user) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_INVALID;
            $this->response($data, HTTP_OK);
            exit();
        }

        if (!$user->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($this->Belote_model->GetStartGame($user->rummy_pool_table_id, '0')) {
            $data['message'] = 'Start Game Already Requested';
            $data['code'] = HTTP_OK;
            $this->response($data, 200);
            exit();
        }

        $TableUser = $this->Belote_model->TableUser($user->rummy_pool_table_id);
        if (count($TableUser)>1) {
            foreach ($TableUser as $k => $val) {
                if ($val->user_id!=$user->id) {
                    $this->Belote_model->StartGame($user->rummy_pool_table_id, $user->id, $val->user_id);
                }
            }

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Minimum 2 Players required to start the game';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function do_start_game_post()
    {
        $user_id = $this->data['user_id'];
        $start_game_id = $this->data['start_game_id'];
        $type = $this->data['type']; //1=accept,2=reject
        if (empty($user_id) || empty($start_game_id) || empty($type)) {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $share = $this->Belote_model->GetStartGameById($start_game_id);

        $this->Belote_model->UpdateStartGame($start_game_id, $type);

        if ($type==1) {
            $reject = false;
            $TableUser = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);
            $user_count = count($TableUser)-1;
            $StartGame = $this->Belote_model->GetStartGameLimit($user[0]->rummy_pool_table_id, $user_count);
            // print_r($ShareWallet);
            foreach ($StartGame as $key => $value) {
                if ($value['status']!=1) {
                    $reject = true;
                    break;
                }
            }

            if (!$reject) {
                $this->start_game_cards($user_id);
                // $this->start_game($user_id);
                $data['message'] = 'Start Game';
                $data['code'] = HTTP_OK;
                $this->response($data, HTTP_OK);
                exit();
            }
        }
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function start_game_cards($user_id)
    {
        $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);

        if (count($table_data)<2) {
            $data['message'] = 'Minimum 3 Players Required to Start the Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $table = $this->Belote_model->isTableAvail($user[0]->rummy_pool_table_id);
        // $table_games = $this->Belote_model->getAllGameOnTable($user[0]->rummy_pool_table_id);
        // $amount = (count($table_games)==0)?$table->boot_value:0;

        $Cards = $this->Belote_model->GetCards(count($table_data));
        // $game_data = [
        //     'table_id' => $user[0]->rummy_pool_table_id,
        //     'amount' => count($table_data)*$amount,
        //     'joker' => $Cards[0]->cards,
        //     'added_date' => date('Y-m-d H:i:s'),
        //     'updated_date' => date('Y-m-d H:i:s')
        // ];

        // $GameId = $this->Belote_model->Create($game_data);

        // $Cards = $this->Belote_model->GetCards(count($table_data)*13);

        foreach ($table_data as $key => $value) {
            $card_value = substr(str_replace('_', '', $Cards[$key]->cards), 2);
            $points = (int) $card_value;
            if ($points==0) {
                $find = array("J","Q","K","A");
                $replace = array(11,12,13,1);
                $points = str_replace($find, $replace, $card_value);
            }
            $table_user_data = [
                'card_position' => $points,
                'card' => $Cards[$key]->cards,
                'updated_date' => date('Y-m-d H:i:s'),
            ];

            $this->Belote_model->UpdateTableUserCard($value->id, $table_user_data);
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function start_game($user_id)
    {
        $user = $this->Users_model->UserProfile($user_id);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table_data = $this->Belote_model->TableUser($user[0]->rummy_pool_table_id);

        if (count($table_data)<2) {
            $data['message'] = 'Minimum 3 Players Required to Start the Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }


        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if ($game) {
            $data['message'] = 'Active Game is Going On';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $table = $this->Belote_model->isTableAvail($user[0]->rummy_pool_table_id);
        $table_games = $this->Belote_model->getAllGameOnTable($user[0]->rummy_pool_table_id);
        $amount = (count($table_games)==0) ? $table->boot_value : 0;

        $Cards = $this->Belote_model->GetCards((count($table_data)*RUMMY_CARDS)+2);
        $game_data = [
            'table_id' => $user[0]->rummy_pool_table_id,
            'amount' => count($table_data)*$amount,
            'joker' => $Cards[0]->cards,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        $GameId = $this->Belote_model->Create($game_data);

        // $Cards = $this->Belote_model->GetCards(count($table_data)*13);

        $table_user_data = [
            'game_id' => $GameId,
            'user_id' => 0,
            'card' => $Cards[1]->cards
        ];

        $this->Belote_model->StartDropGameCards($table_user_data);

        $end = 2;
        foreach ($table_data as $key => $value) {
            $start = $end;
            $end = $end+RUMMY_CARDS;
            for ($i=$start; $i < $end; $i++) {
                $table_user_data = [
                    'game_id' => $GameId,
                    'user_id' => $value->user_id,
                    'card' => $Cards[$i]->cards,
                    'added_date' => date('Y-m-d H:i:s'),
                    'updated_date' => date('Y-m-d H:i:s'),
                    'isDeleted' => 0
                ];

                $this->Belote_model->GiveGameCards($table_user_data);
            }

            if ($amount!=0) {
                $this->Belote_model->MinusWallet($value->user_id, $amount);
            }

            $this->Belote_model->AddGameCount($value->user_id);

            $game_log = [
                'game_id' => $GameId,
                'user_id' => $value->user_id,
                'action' => 0,
                'amount' => ($amount==0)?0:(-$amount),
                'added_date' => date('Y-m-d H:i:s')
            ];

            $this->Belote_model->AddGameLog($game_log);
        }

        $data['message'] = 'Success';
        $data['game_id'] = $GameId;
        $data['table_amount'] = $amount;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function rearrange_array($array, $key)
    {
        while ($key > 0) {
            $temp = array_shift($array);
            $array[] = $temp;
            $key--;
        }
        return $array;
    }

    public function user_card_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game_users = $this->Belote_model->GameAllUser($game->id);

        foreach ($game_users as $key => $value) {
            $cards = $this->Belote_model->getMyCards($game->id, $value->user_id);
            $game_users[$key]->cards = $cards;
            $game_users[$key]->joker = $game->joker;
        }

        $data['message'] = 'Success';
        $data['game_users'] = $game_users;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
    }

    public function swap_card_post()
    {
        $user_id = $this->data['user_id'];
        $my_card = $this->data['my_card'];
        $new_card = $this->data['new_card'];

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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $cards = $this->Belote_model->getMyCards($game->id, $user_id);

        // if (count($cards)<=RUMMY_CARDS) {
        //     $data['message'] = 'Please Get Or Pick Card First And Then Drop One';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $isCardAvailable = $this->Belote_model->getMyCards($game->id, $user_id, $my_card);

        if ($isCardAvailable) {
            $this->Belote_model->SwapCards($user_id, $game->id, $my_card, $new_card);

            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        }

        $data['message'] = 'Invalid Card';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function get_available_cards_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getActiveGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'Game Not Started';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $data['cards_list'] = $this->Belote_model->GetGameCard($game->id);
        $data['joker'] = $game->joker;
        $this->response($data, HTTP_OK);
        exit();

        $data['message'] = 'Invalid Card';
        $data['code'] = HTTP_NOT_ACCEPTABLE;
        $this->response($data, 200);
        exit();
    }

    public function user_game_history_post()
    {
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

        if (!$user[0]->rummy_pool_table_id) {
            $data['message'] = 'You Are Not On Table';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Belote_model->getLastGameOnTable($user[0]->rummy_pool_table_id);

        if (!$game) {
            $data['message'] = 'This is First Game';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $data['game_users'] = $this->Belote_model->GameOnlyUser($game->id);

        $game_users_cards = array();
        foreach ($data['game_users'] as $key => $value) {
            $declare_log = $this->Belote_model->GameLog($game->id, 1, '', $value->user_id);
            $game_users_cards[$key]['user'] = $value;
            $game_users_cards[$key]['user']->win = ($game->winner_id==$value->user_id) ? $game->user_winning_amt : $declare_log[0]->amount;
            $game_users_cards[$key]['user']->result = $declare_log[0]->action;
            $game_users_cards[$key]['user']->score = $declare_log[0]->points;
            $game_users_cards[$key]['user']->cards = json_decode($this->Belote_model->GameLogJson($game->id, $value->user_id));
        }

        $data['game_users_cards'] = $game_users_cards;
        $data['message'] = 'Success';
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();

    }
}