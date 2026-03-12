<?php

use phpDocumentor\Reflection\Types\Object_;
use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class DragonTigerLocalLogic extends REST_Controller
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

        $this->data = $this->input->post();
        // print_r($this->data['user_id']);
        $this->load->model([
            'DragonTigerLocalLogic_model',
            'Users_model',
            'Setting_model'

        ]);
    }

    public function room_post()
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

        $room_data = $this->DragonTigerLocalLogic_model->getRoom();
        if ($room_data) {
            $rooms = array();

            foreach ($room_data as $key => $value) {
                $rooms[$key]['id'] = $value->id;
                $rooms[$key]['min_coin'] = $value->min_coin;
                $rooms[$key]['max_coin'] = $value->max_coin;
                $rooms[$key]['added_date'] = $value->added_date;
                $rooms[$key]['updated_date'] = $value->updated_date;
                $rooms[$key]['isDeleted'] = $value->isDeleted;
                $rooms[$key]['online'] = $this->DragonTigerLocalLogic_model->getRoomOnline($value->id);
            }

            $data['message'] = 'Success';
            $data['room_data'] = $rooms;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Room Starting Soon';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function get_active_game_post()
    {
        $total_bet_dragon = $this->input->post('total_bet_dragon');
        $total_bet_tiger = $this->input->post('total_bet_tiger');
        $total_bet_tie = $this->input->post('total_bet_tie');
        $increment = $this->input->post('increment');
        if (empty($this->data['user_id']) || empty($this->data['token']) || empty($this->data['room_id'])) {
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

        $room = $this->DragonTigerLocalLogic_model->getRoom($this->data['room_id'], $this->data['user_id']);
        if (empty($room)) {
            $data['message'] = 'Invalid Room';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bot_user = $this->Users_model->AllBotUserList();
        $data['bot_user'] = $bot_user;
        $game_data = $this->DragonTigerLocalLogic_model->getActiveGameOnTable($this->data['room_id']);
        if ($game_data) {
            $game_cards = array();
            if ($game_data[0]->status) {
                $game_cards = $this->DragonTigerLocalLogic_model->GetGameCards($game_data[0]->id);
            }

            $new_game_data[0]['id'] = $game_data[0]->id;
            $new_game_data[0]['room_id'] = $game_data[0]->room_id;
            $new_game_data[0]['main_card'] = $game_data[0]->main_card;
            $new_game_data[0]['winning'] = $game_data[0]->winning;
            $new_game_data[0]['status'] = $game_data[0]->status;
            $new_game_data[0]['added_date'] = $game_data[0]->added_date;
            $added_datetime_sec = strtotime($game_data[0]->added_date);
            $new_game_data[0]['time_remaining'] = ($added_datetime_sec+DRAGON_TIME_FOR_BET) - time();
            $new_game_data[0]['end_datetime'] = $game_data[0]->end_datetime;
            $new_game_data[0]['updated_date'] = $game_data[0]->updated_date;

            $data['message'] = 'Success';
            $data['game_data'] = $new_game_data;
            $data['game_cards'] = $game_cards;
            // $data['online'] = $this->DragonTigerLocalLogic_model->getRoomOnline($this->data['room_id']);
            $data['online_users'] = $this->DragonTigerLocalLogic_model->getRoomOnlineUser($this->data['room_id']);
            $data['online'] = rand(300, 350)+count($data['online_users']);
            $data['last_bet'] = $this->DragonTigerLocalLogic_model->ViewBet('', $game_data[0]->id, '', '', 1);
            $data['my_dragon_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 0, $this->data['user_id']);
            $data['my_tiger_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 1, $this->data['user_id']);
            $data['my_tie_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 2, $this->data['user_id']);
            $dragon_bet = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 0);
            $tiger_bet = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 1);
            $tie_bet = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 2);

            if ($increment==1) {
                $data['dragon_bet'] = rand($total_bet_dragon, $total_bet_dragon+10000)+$dragon_bet;
                $data['tiger_bet'] = rand($total_bet_tiger, $total_bet_tiger+10000)+$tiger_bet;
                $data['tie_bet'] = rand($total_bet_tie, $total_bet_tie+2000)+$tie_bet;
            } else {
                $data['dragon_bet'] = $total_bet_dragon+$dragon_bet;
                $data['tiger_bet'] = $total_bet_tiger+$tiger_bet;
                $data['tie_bet'] = $total_bet_tie+$tie_bet;
            }

            $data['last_winning'] = $this->DragonTigerLocalLogic_model->LastWinningBet($this->data['room_id']);
            $data['profile'] = $user;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Game Starting Soon';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function get_active_game_socket_post()
    {
        $total_bet_dragon = 0;
        $total_bet_tiger = 0;
        $total_bet_tie = 0;
        $increment = 0;
        // if (empty($this->data['user_id']) || empty($this->data['token']) || empty($this->data['room_id'])) {
        //     $data['message'] = 'Invalid Parameter';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // if (!$this->Users_model->TokenConfirm($this->data['user_id'], $this->data['token'])) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_INVALID;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }

        // $user = $this->Users_model->UserProfile($this->data['user_id']);
        // if (empty($user)) {
        //     $data['message'] = 'Invalid User';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $room = $this->DragonTigerLocalLogic_model->getRoom($this->data['room_id'], $this->data['user_id']);
        // if (empty($room)) {
        //     $data['message'] = 'Invalid Room';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $bot_user = $this->Users_model->AllBotUserList();
        $data['bot_user'] = $bot_user;
        $game_data = $this->DragonTigerLocalLogic_model->getActiveGameOnTable($this->data['room_id']);
        if ($game_data) {
            $game_cards = array();
            if ($game_data[0]->status) {
                $game_cards = $this->DragonTigerLocalLogic_model->GetGameCards($game_data[0]->id);
            }

            $new_game_data[0]['id'] = $game_data[0]->id;
            $new_game_data[0]['room_id'] = $game_data[0]->room_id;
            $new_game_data[0]['main_card'] = $game_data[0]->main_card;
            $new_game_data[0]['winning'] = $game_data[0]->winning;
            $new_game_data[0]['status'] = $game_data[0]->status;
            $new_game_data[0]['added_date'] = $game_data[0]->added_date;
            $added_datetime_sec = strtotime($game_data[0]->added_date);
            $new_game_data[0]['time_remaining'] = ($added_datetime_sec+DRAGON_TIME_FOR_BET) - time();
            $new_game_data[0]['end_datetime'] = $game_data[0]->end_datetime;
            $new_game_data[0]['updated_date'] = $game_data[0]->updated_date;

            $data['message'] = 'Success';
            $data['game_data'] = $new_game_data;
            $data['game_cards'] = $game_cards;
            // $data['online'] = $this->DragonTigerLocalLogic_model->getRoomOnline($this->data['room_id']);
            $data['online_users'] = $this->DragonTigerLocalLogic_model->getRoomOnlineUser($this->data['room_id']);
            $data['online'] = rand(300, 350)+count($data['online_users']);
            $data['last_bet'] = $this->DragonTigerLocalLogic_model->ViewBet('', $game_data[0]->id, '', '', 1);
            $data['my_dragon_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 0, '');
            $data['my_tiger_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 1, '');
            $data['my_tie_bet'] = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_data[0]->id, 2, '');
    
            // if ($increment==1) {
                $data['dragon_bet'] = rand($game_data[0]->random_amount, $game_data[0]->random_amount+4000)+$data['my_dragon_bet'];
                $data['tiger_bet'] = rand($game_data[0]->random_amount, $game_data[0]->random_amount+4000)+$data['my_tiger_bet'];
                $data['tie_bet'] = rand($game_data[0]->random_amount, $game_data[0]->random_amount+4000)+$data['my_tie_bet'];
                $this->DragonTigerLocalLogic_model->updateRandomAmount($game_data[0]->id,$data['dragon_bet']);
            // } else {
            //     $data['dragon_bet'] = $total_bet_dragon+$dragon_bet;
            //     $data['tiger_bet'] = $total_bet_tiger+$tiger_bet;
            //     $data['tie_bet'] = $total_bet_tie+$tie_bet;
            // }

            $data['last_winning'] = $this->DragonTigerLocalLogic_model->LastWinningBet($this->data['room_id']);
            // $data['profile'] = $user;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Game Starting Soon';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function leave_room_post()
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

        $room = $this->DragonTigerLocalLogic_model->getRoom($this->data['room_id'], $this->data['user_id']);
        if (empty($room)) {
            $data['message'] = 'Invalid Room';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $leave_room = $this->DragonTigerLocalLogic_model->leave_room($this->data['user_id']);
        if ($leave_room) {
            $data['message'] = 'Success';
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Something wents wrong';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function place_bet_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['bet']) || empty($this->data['amount'])) {
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

        // if (!in_array($this->data['bet'], array(DRAGON,TIGER,TIE))) {
        //     $data['message'] = 'Invalid Bet';
        //     $data['code'] = HTTP_INVALID;
        //     $this->response($data, HTTP_OK);
        //     exit();
        // }

        $user = $this->Users_model->UserProfile($this->data['user_id']);
        if (empty($user)) {
            $data['message'] = 'Invalid User';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // if ($user[0]->wallet<20) {
        //     $data['message'] = 'Required Minimum 20 Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $setting = $this->Setting_model->setting();
        // print_r($setting);exit;
        // if ($user[0]->wallet < $setting->dragon_tiger_min_bet) {
        //     $data['message'] = 'Required Minimum ' . $setting->dragon_tiger_bet . ' Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $all_bets = json_decode($this->data['bet']);
        $amounts = json_decode($this->data['amount']);

        $total_bet_amount = 0;
        foreach ($amounts as $key => $value) {
            $total_bet_amount += $value;
        }

        if ($user[0]->wallet<$total_bet_amount) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $user_id = $this->data['user_id'];
        // if(empty($this->data['game_id'])){
            $game_id = $this->DragonTigerLocalLogic_model->Create($user_id);
            $game = $this->DragonTigerLocalLogic_model->View($game_id,$user_id);
        // }
        // else{
        //     $game_id = $this->data['game_id'];
        //     $game = $this->DragonTigerLocalLogic_model->View($game_id,$user_id);
        //     if (!$game) {
        //         $data['message'] = 'Invalid Game Id';
        //         $data['code'] = HTTP_NOT_ACCEPTABLE;
        //         $this->response($data, 200);
        //         exit();
        //     }
        // }

        if ($game->status) {
            $data['message'] = 'Can\'t Place Bet, Game Has Been Ended';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id'], $this->data['game_id'], $this->data['bet']);

        // if ($bet) {
        //     $data['message'] = 'One Bet Already Placed';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        foreach ($amounts as $key => $value) {
            $this->DragonTigerLocalLogic_model->MinusWallet($user_id, $value);
            

            $bet_data = [
                'dragon_tiger_id' => $game_id,
                'user_id' => $user_id,
                'bet' => $all_bets[$key],
                'amount' => $value,
                'added_date' => date('Y-m-d H:i:s')
            ];

            $bet_id = $this->DragonTigerLocalLogic_model->PlaceBet($bet_data);
            log_statement ( $user_id, DT,-$value,$bet_id) ;
        }

        if ($bet_id) {
            
            $data['message'] = 'Success';
            $data['bet_id'] = $bet_id;
            
            // if($this->data['isLastBet']=='yes'){
                //Winner Code
                $TotalWinningAmount = 0;
                $TotalBetAmount = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_id);

                $DragonBetAmount = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_id, DRAGON)*2;
                $TigerBetAmount = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_id, TIGER)*2;
                $TieBetAmount = $this->DragonTigerLocalLogic_model->TotalBetAmount($game_id, TIE)*11;

                if ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                    $winning = TIE;
                } else {
                    $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                }

                do {
                    $limit = 2;
                    $cards = $this->DragonTigerLocalLogic_model->GetCards($limit);
                    $card1_point = $this->card_points($cards[0]->cards);
                    $card2_point = $this->card_points($cards[1]->cards);

                    $card_big = '';
                    $card_small = '';

                    if ($card1_point>$card2_point) {
                        $card_big = $cards[0]->cards;
                        $card_small = $cards[1]->cards;
                    } else {
                        $card_big = $cards[1]->cards;
                        $card_small = $cards[0]->cards;
                    }
                } while ($card1_point==$card2_point);

                $card_dragon = ($winning==DRAGON) ? $card_big : $card_small;
                $card_tiger = ($winning==TIGER) ? $card_big : $card_small;

                $bets = $this->DragonTigerLocalLogic_model->ViewBet("", $game_id, $winning);
                if ($bets) {
                    // print_r($bets);
                    $comission = $this->Setting_model->Setting()->admin_commission;
                    foreach ($bets as $key => $value) {
                        if ($winning==TIE) {
                            $amount = $value->amount*TIE_MULTIPLY;
                            $TotalWinningAmount += $amount;
                            $this->DragonTigerLocalLogic_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_id);
                        } else {
                            $amount = $value->amount*DRAGON_MULTIPLY;
                            $TotalWinningAmount += $amount;
                            $this->DragonTigerLocalLogic_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_id);
                        }
                    }
                    // echo "Winning Amount Given".PHP_EOL;
                } else {
                    // echo "No Winning Bet Found".PHP_EOL;
                }
                $update_data['status'] = 1;
                $update_data['winning'] = $winning;
                $update_data['dragon_card'] = $card_dragon;
                $update_data['tiger_card'] = $card_tiger;
                $update_data['total_amount'] = $TotalBetAmount;
                $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                // $update_data['updated_date'] = date('Y-m-d H:i:s');
                // $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                // $update_data['random'] = $random;
                if(!empty($update_data['admin_profit'])){
                    direct_admin_profit_statement(DT,$update_data['admin_profit'],$game_id);
                }
                $this->DragonTigerLocalLogic_model->Update($update_data, $game_id);
                //Winner Code
                $data['card_dragon'] = $card_dragon;
                $data['card_tiger'] = $card_tiger;
                $data['winning'] = $winning;
            // }

            $user_wallet = $this->Users_model->UserProfile($user_id);
            $data['wallet'] = $user_wallet[0]->wallet;
            $data['game_id'] = $game_id;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Something Wents Wrong';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function card_points($card)
    {
        $card_value = substr($card, 2);

        $point = str_replace(
            array("J", "Q", "K", "A"),
            array(11, 12, 13, 1),
            $card_value
        );
        return $point;
    }

    public function repeat_bet_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['game_id'])) {
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

        $game = $this->DragonTigerLocalLogic_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($game->status) {
            $data['message'] = 'Can\'t Place Bet, Game Has Been Ended';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id'], $this->data['game_id']);
        if ($bet) {
            $data['message'] = 'One Bet Already Placed';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $last_bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id']);
        if ($user[0]->wallet<$last_bet[0]->amount) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        // $bet_data = [
        //     'dragon_tiger_id' => $this->data['game_id'],
        //     'user_id' => $this->data['user_id'],
        //     'bet' => $last_bet[0]->bet,
        //     'amount' => $last_bet[0]->amount,
        //     'added_date' => date('Y-m-d H:i:s')

        // ];

        // $bet_id = $this->DragonTigerLocalLogic_model->PlaceBet($bet_data);

        // if($bet_id)
        // {
        // $this->DragonTigerLocalLogic_model->MinusWallet($this->data['user_id'], $last_bet[0]->amount);
        $data['message'] = 'Success';
        // $data['bet_id'] = $bet_id;
        $data['bet'] = $last_bet[0]->bet;
        $data['amount'] = $last_bet[0]->amount;
        $user_wallet = $this->Users_model->UserProfile($this->data['user_id']);
        $data['wallet'] = $user_wallet[0]->wallet;
        $data['code'] = HTTP_OK;
        $this->response($data, HTTP_OK);
        exit();
        // }
        // else
        // {
        //     $data['message'] = 'Something Wents Wrong';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }
    }

    public function double_bet_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['game_id'])) {
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

        $game = $this->DragonTigerLocalLogic_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($game->status) {
            $data['message'] = 'Can\'t Place Bet, Game Has Been Ended';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id'], $this->data['game_id']);
        if ($bet) {
            $data['message'] = 'One Bet Already Placed';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $last_bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id']);
        $amount = $last_bet[0]->amount*2;
        if ($user[0]->wallet<$amount) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bet_data = [
            'dragon_tiger_id' => $this->data['game_id'],
            'user_id' => $this->data['user_id'],
            'bet' => $last_bet[0]->bet,
            'amount' => $amount,
            'added_date' => date('Y-m-d H:i:s')
        ];

        $bet_id = $this->DragonTigerLocalLogic_model->PlaceBet($bet_data);

        if ($bet_id) {
            $this->DragonTigerLocalLogic_model->MinusWallet($this->data['user_id'], $amount);
            $data['message'] = 'Success';
            $data['bet_id'] = $bet_id;
            $data['bet'] = $last_bet[0]->bet;
            $data['amount'] = $amount;
            $user_wallet = $this->Users_model->UserProfile($this->data['user_id']);
            $data['wallet'] = $user_wallet[0]->wallet;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Something Wents Wrong';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function cancel_bet_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['game_id'])) {
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

        $game = $this->DragonTigerLocalLogic_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id'], $this->data['game_id'], '', '');
        if (!$bet) {
            $data['message'] = 'Invalid Bet';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($game->status) {
            $data['message'] = 'Can\'t Cancel Bet, Game Has Been Ended';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($this->DragonTigerLocalLogic_model->DeleteBet($bet[0]->id, $this->data['user_id'], $this->data['game_id'])) {
            $this->DragonTigerLocalLogic_model->AddWallet($this->data['user_id'], $bet[0]->amount);
            $data['message'] = 'Bet Cancel Successfully';
            $user_wallet = $this->Users_model->UserProfile($this->data['user_id']);
            $data['wallet'] = $user_wallet[0]->wallet;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'Something Wents Wrong';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function get_result_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['game_id'])) {
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

        $game = $this->DragonTigerLocalLogic_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $win_amount = 0;
        $bet_amount = 0;
        $bet = $this->DragonTigerLocalLogic_model->ViewBet($this->data['user_id'], $this->data['game_id']);
        if (!$bet) {
            $data['win_amount'] = $win_amount;
            $data['bet_amount'] = $bet_amount;
            $data['diff_amount'] = $win_amount-$bet_amount;
            $data['message'] = 'No Bet';
            $data['code'] = 101;
            $this->response($data, 200);
            exit();
        }

        foreach ($bet as $key => $value) {
            $win_amount += $value->user_amount;
            $bet_amount += $value->amount;
        }

        $data['win_amount'] = $win_amount;
        $data['bet_amount'] = $bet_amount;
        $data['diff_amount'] = $win_amount-$bet_amount;

        if($data['diff_amount']>0){
            $data['message'] = "You Win";
            $data['code'] = 102;
            $this->response($data, 200);
            exit();
        }else{
            $data['message'] = "You Loss";
            $data['code'] = 103;
            $this->response($data, 200);
            exit();
        }
    }
}