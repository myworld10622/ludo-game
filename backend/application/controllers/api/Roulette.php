<?php

use phpDocumentor\Reflection\Types\Object_;
use Restserver\Libraries\REST_Controller;

include APPPATH . '/libraries/REST_Controller.php';
include APPPATH . '/libraries/Format.php';
class Roulette extends REST_Controller
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
            'Roulette_model',
            'Setting_model',
            'Users_model'
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

        $room_data = $this->Roulette_model->getRoom();
        if ($room_data) {
            $rooms = array();

            foreach ($room_data as $key => $value) {
                $rooms[$key]['id'] = $value->id;
                $rooms[$key]['min_coin'] = $value->min_coin;
                $rooms[$key]['max_coin'] = $value->max_coin;
                $rooms[$key]['added_date'] = $value->added_date;
                $rooms[$key]['updated_date'] = $value->updated_date;
                $rooms[$key]['isDeleted'] = $value->isDeleted;
                $rooms[$key]['online'] = $this->Roulette_model->getRoomOnline($value->id);
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
        $total_bet_heart = $this->input->post('total_bet_heart') ?? 0;
        $total_bet_spade = $this->input->post('total_bet_spade') ?? 0;
        $total_bet_diamond = $this->input->post('total_bet_diamond') ?? 0;
        $total_bet_club = $this->input->post('total_bet_club') ?? 0;
        $total_bet_face = $this->input->post('total_bet_face') ?? 0;
        $total_bet_flag = $this->input->post('total_bet_flag') ?? 0;

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

        $room = $this->Roulette_model->getRoom($this->data['room_id'], $this->data['user_id']);
        if (empty($room)) {
            $data['message'] = 'Invalid Room';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bot_user = $this->Users_model->AllBotUserList();
        $data['bot_user'] = $bot_user;
        $game_data = $this->Roulette_model->getActiveGameOnTable($this->data['room_id']);
        if ($game_data) {
            $game_cards = array();
            if ($game_data[0]->status) {
                $game_cards = $this->Roulette_model->GetGameCards($game_data[0]->id);
            }

            $new_game_data[0]['id'] = $game_data[0]->id;
            $new_game_data[0]['room_id'] = $game_data[0]->room_id;
            // $new_game_data[0]['main_card'] = $game_data[0]->main_card;
            $new_game_data[0]['winning'] = $game_data[0]->winning;
            // $new_game_data[0]['winning_rule'] = $game_data[0]->winning_rule;
            $new_game_data[0]['status'] = $game_data[0]->status;
            $new_game_data[0]['added_date'] = $game_data[0]->added_date;
            $added_datetime_sec = strtotime($game_data[0]->added_date);
            $new_game_data[0]['time_remaining'] = ($added_datetime_sec+DRAGON_TIME_FOR_BET) - time();
            $new_game_data[0]['end_datetime'] = $game_data[0]->end_datetime;
            $new_game_data[0]['updated_date'] = $game_data[0]->updated_date;

            $data['message'] = 'Success';
            $data['game_data'] = $new_game_data;
            $data['game_cards'] = $game_cards;
            $data['online'] = $this->Roulette_model->getRoomOnline($this->data['room_id']);
            $data['online_users'] = $this->Roulette_model->getRoomOnlineUser($this->data['room_id']);

            $HeartAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, HEART);
            $SpadeAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, SPADE);
            $DiamondAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, DIAMOND);
            $ClubAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, CLUB);
            $FaceAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, FACE);
            $FlagAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, FLAG);

            $data['heart_amount'] = rand($total_bet_heart, $total_bet_heart+10000)+$HeartAmount;
            $data['spade_amount'] = rand($total_bet_spade, $total_bet_spade+10000)+$SpadeAmount;
            $data['diamond_amount'] = rand($total_bet_diamond, $total_bet_diamond+10000)+$DiamondAmount;
            $data['club_amount'] = rand($total_bet_club, $total_bet_club+10000)+$ClubAmount;
            $data['face_amount'] = rand($total_bet_face, $total_bet_face+10000)+$FaceAmount;
            $data['flag_amount'] = rand($total_bet_flag, $total_bet_flag+10000)+$FlagAmount;

            $data['last_bet'] = $this->Roulette_model->ViewBet('', $game_data[0]->id, '', '', 1);

            // $data['jackpot_amount'] = $this->Setting_model->Setting()->jackpot_coin;

            $data['last_winning'] = $this->Roulette_model->LastWinningBet($this->data['room_id'], 15);

            // $winners = $this->Roulette_model->getJackpotWinners(1);
            // if ($winners) {
            //     foreach ($winners as $key => $value) {
            //         $value->user_data = $this->Roulette_model->getJackpotBigWinners($value->id);
            //     }
            // }
            // $data['big_winner'] = $winners;

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
        $total_bet_heart = $this->input->post('total_bet_heart') ?? 0;
        $total_bet_spade = $this->input->post('total_bet_spade') ?? 0;
        $total_bet_diamond = $this->input->post('total_bet_diamond') ?? 0;
        $total_bet_club = $this->input->post('total_bet_club') ?? 0;
        $total_bet_face = $this->input->post('total_bet_face') ?? 0;
        $total_bet_flag = $this->input->post('total_bet_flag') ?? 0;

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

        // $room = $this->Roulette_model->getRoom($this->data['room_id'], $this->data['user_id']);
        // if (empty($room)) {
        //     $data['message'] = 'Invalid Room';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $bot_user = $this->Users_model->AllBotUserList();
        $data['bot_user'] = $bot_user;
        $game_data = $this->Roulette_model->getActiveGameOnTable($this->data['room_id']);
        if ($game_data) {
            $game_cards = array();
            if ($game_data[0]->status) {
                $game_cards = $this->Roulette_model->GetGameCards($game_data[0]->id);
            }

            $new_game_data[0]['id'] = $game_data[0]->id;
            $new_game_data[0]['room_id'] = $game_data[0]->room_id;
            // $new_game_data[0]['main_card'] = $game_data[0]->main_card;
            $new_game_data[0]['winning'] = $game_data[0]->winning;
            // $new_game_data[0]['winning_rule'] = $game_data[0]->winning_rule;
            $new_game_data[0]['status'] = $game_data[0]->status;
            $new_game_data[0]['added_date'] = $game_data[0]->added_date;
            $added_datetime_sec = strtotime($game_data[0]->added_date);
            $new_game_data[0]['time_remaining'] = ($added_datetime_sec+DRAGON_TIME_FOR_BET) - time();
            $new_game_data[0]['end_datetime'] = $game_data[0]->end_datetime;
            $new_game_data[0]['updated_date'] = $game_data[0]->updated_date;

            $data['message'] = 'Success';
            $data['game_data'] = $new_game_data;
            $data['game_cards'] = $game_cards;
            $data['online'] = $this->Roulette_model->getRoomOnline($this->data['room_id']);
            // $data['online_users'] = $this->Roulette_model->getRoomOnlineUser($this->data['room_id']);
            $data['online_users'] = array();

            $HeartAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, HEART);
            $SpadeAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, SPADE);
            $DiamondAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, DIAMOND);
            $ClubAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, CLUB);
            $FaceAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, FACE);
            $FlagAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id, FLAG);

            $data['heart_amount'] = rand($total_bet_heart, $total_bet_heart+10000)+$HeartAmount;
            $data['spade_amount'] = rand($total_bet_spade, $total_bet_spade+10000)+$SpadeAmount;
            $data['diamond_amount'] = rand($total_bet_diamond, $total_bet_diamond+10000)+$DiamondAmount;
            $data['club_amount'] = rand($total_bet_club, $total_bet_club+10000)+$ClubAmount;
            $data['face_amount'] = rand($total_bet_face, $total_bet_face+10000)+$FaceAmount;
            $data['flag_amount'] = rand($total_bet_flag, $total_bet_flag+10000)+$FlagAmount;

            $data['last_bet'] = $this->Roulette_model->ViewBet('', $game_data[0]->id, '', '', 1);

            // $data['jackpot_amount'] = $this->Setting_model->Setting()->jackpot_coin;

            $data['last_winning'] = $this->Roulette_model->LastWinningBet($this->data['room_id'], 50);
            $data['last_winning_500'] = $this->Roulette_model->LastWinningBetNumber($this->data['room_id'], 500);
            // $winners = $this->Roulette_model->getJackpotWinners(1);
            // if ($winners) {
            //     foreach ($winners as $key => $value) {
            //         $value->user_data = $this->Roulette_model->getJackpotBigWinners($value->id);
            //     }
            // }
            // $data['big_winner'] = $winners;

            // $data['profile'] = $user;

            $zero = 0;
            $even = 0;
            $odd = 0;
            $red = 0;
            $black = 0;
            $r1_18 = 0;
            $r19_36 = 0;
            $r1_12 = 0;
            $r2_12 = 0;
            $r3_12 = 0;
            $c1_12 = 0;
            $c2_12 = 0;
            $c3_12 = 0;

            foreach ($data['last_winning_500'] as $key => $value) {

                switch ($value->winning) {
                    case -1:
                    case 0:
                        $zero++;
                        break;
                    case 1:
                        $odd++;
                        $red++;
                        $r1_18++;
                        $r3_12++;
                        $c1_12++;
                        break;
                    case 2:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r2_12++;
                        $c1_12++;
                        break;
                    case 3:
                        $odd++;
                        $red++;
                        $r1_18++;
                        $r1_12++;
                        $c1_12++;
                        break;
                    case 4:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r3_12++;
                        $c1_12++;
                        break;
                    case 5:
                        $odd++;
                        $red++;
                        $r1_18++;
                        $r2_12++;
                        $c1_12++;
                        break;
                    case 6:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r1_12++;
                        $c1_12++;
                        break;
                    case 7:
                        $odd++;
                        $red++;
                        $r1_18++;
                        $r3_12++;
                        $c1_12++;
                        break;
                    case 8:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r2_12++;
                        $c1_12++;
                        break;
                    case 9:
                        $odd++;
                        $red++;
                        $r1_18++;
                        $r1_12++;
                        $c1_12++;
                        break;
                    case 10:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r3_12++;
                        $c1_12++;
                        break;
                    case 11:
                        $odd++;
                        $black++;
                        $r1_18++;
                        $r2_12++;
                        $c1_12++;
                        break;
                    case 12:
                        $even++;
                        $red++;
                        $r1_18++;
                        $r1_12++;
                        $c1_12++;
                        break;
                    case 13:
                        $odd++;
                        $black++;
                        $r1_18++;
                        $r3_12++;
                        $c2_12++;
                        break;
                    case 14:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r2_12++;
                        $c2_12++;
                        break;
                    case 15:
                        $odd++;
                        $black++;
                        $r1_18++;
                        $r1_12++;
                        $c2_12++;
                        break;
                    case 16:
                        $even++;
                        $black++;
                        $r1_18++;
                        $r3_12++;
                        $c2_12++;
                        break;
                    case 17:
                        $odd++;
                        $black++;
                        $r1_18++;
                        $r2_12++;
                        $c2_12++;
                        break;
                    case 18:
                        $even++;
                        $red++;
                        $r1_18++;
                        $r1_12++;
                        $c2_12++;
                        break;
                    case 19:
                        $odd++;
                        $red++;
                        $r19_36++;
                        $r3_12++;
                        $c2_12++;
                        break;
                    case 20:
                        $even++;
                        $black++;
                        $r19_36++;
                        $r2_12++;
                        $c2_12++;
                        break;
                    case 21:
                        $odd++;
                        $red++;
                        $r19_36++;
                        $r1_12++;
                        $c2_12++;
                        break;
                    case 22:
                        $even++;
                        $black++;
                        $r19_36++;
                        $r3_12++;
                        $c2_12++;
                        break;
                    case 23:
                        $odd++;
                        $red++;
                        $r19_36++;
                        $r2_12++;
                        $c2_12++;
                        break;
                    case 24:
                        $even++;
                        $black++;
                        $r19_36++;
                        $r1_12++;
                        $c2_12++;
                        break;
                    case 25:
                        $odd++;
                        $red++;
                        $r19_36++;
                        $r3_12++;
                        $c3_12++;
                        break;
                    case 26:
                        $even++;
                        $black++;
                        $r19_36++;
                        $r2_12++;
                        $c3_12++;
                        break;
                    case 27:
                        $odd++;
                        $red++;
                        $r19_36++;
                        $r1_12++;
                        $c3_12++;
                        break;
                    case 28:
                        $even++;
                        $black++;
                        $r19_36++;
                        $r3_12++;
                        $c3_12++;
                        break;
                    case 29:
                        $odd++;
                        $black++;
                        $r19_36++;
                        $r2_12++;
                        $c3_12++;
                        break;
                    case 30:
                        $even++;
                        $red++;
                        $r19_36++;
                        $r1_12++;
                        $c3_12++;
                        break;
                    case 31:
                        $odd++;
                        $black++;
                        $r19_36++;
                        $r3_12++;
                        $c3_12++;
                        break;
                    case 32:
                        $even++;
                        $red++;
                        $r19_36++;
                        $r2_12++;
                        $c3_12++;
                        break;
                    case 33:
                        $odd++;
                        $black++;
                        $r19_36++;
                        $r1_12++;
                        $c3_12++;
                        break;
                    case 34:
                        $even++;
                        $red++;
                        $r19_36++;
                        $r3_12++;
                        $c3_12++;
                        break;
                    case 35:
                        $odd++;
                        $black++;
                        $r19_36++;
                        $r2_12++;
                        $c3_12++;
                        break;
                    case 36:
                        $even++;
                        $red++;
                        $r19_36++;
                        $r1_12++;
                        $c3_12++;
                        break;
                    default:
                        break;
                }
            }

            $data['zero'] = round($zero/count($data['last_winning_500'])*100,2);
            $data['even'] = round($even/count($data['last_winning_500'])*100,2);
            $data['odd'] = round($odd/count($data['last_winning_500'])*100,2);
            $data['red'] = round($red/count($data['last_winning_500'])*100,2);
            $data['black'] = round($black/count($data['last_winning_500'])*100,2);
            $data['r1_18'] = round($r1_18/count($data['last_winning_500'])*100,2);
            $data['r19_36'] = round($r19_36/count($data['last_winning_500'])*100,2);
            $data['r1_12'] = round($r1_12/count($data['last_winning_500'])*100,2);
            $data['r2_12'] = round($r2_12/count($data['last_winning_500'])*100,2);
            $data['r3_12'] = round($r3_12/count($data['last_winning_500'])*100,2);
            $data['c1_12'] = round($c1_12/count($data['last_winning_500'])*100,2);
            $data['c2_12'] = round($c2_12/count($data['last_winning_500'])*100,2);
            $data['c3_12'] = round($c3_12/count($data['last_winning_500'])*100,2);


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

        $room = $this->Roulette_model->getRoom($this->data['room_id'], $this->data['user_id']);
        if (empty($room)) {
            $data['message'] = 'Invalid Room';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $leave_room = $this->Roulette_model->leave_room($this->data['user_id']);
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
        if (empty($this->data['user_id']) || empty($this->data['game_id']) || ($this->data['bet']=="") || empty($this->data['amount'])) {
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

        // if ($user[0]->wallet<100) {
        //     $data['message'] = 'Required Minimum 100 Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $setting = $this->Setting_model->setting();
        // print_r($setting);exit;
        if ($user[0]->wallet < $setting->roullette_min_bet) {
            $data['message'] = 'Required Minimum ' . $setting->roullette_min_bet . ' Coins to Play';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        if ($user[0]->wallet<$this->data['amount']) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Roulette_model->View($this->data['game_id']);
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

        // $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id'], $this->data['bet']);

        // if ($bet) {
        //     $data['message'] = 'One Bet Already Placed';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $bet_data = [
            'roulette_id' => $this->data['game_id'],
            'user_id' => $this->data['user_id'],
            'bet' => $this->data['bet'],
            'amount' => $this->data['amount'],
            'added_date' => date('Y-m-d H:i:s')

        ];

        $bet_id = $this->Roulette_model->PlaceBet($bet_data);

        if ($bet_id) {
            $this->Roulette_model->MinusWallet($this->data['user_id'], $this->data['amount']);
            log_statement ( $this->data['user_id'], RT,-$this->data['amount'],$bet_id) ;
            $data['message'] = 'Success';
            $data['bet_id'] = $bet_id;
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

    public function place_bet_multiple_post()
    {
        if (empty($this->data['user_id']) || empty($this->data['game_id']) || ($this->data['bet']=="") || empty($this->data['amount'])) {
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

        // if ($user[0]->wallet<100) {
        //     $data['message'] = 'Required Minimum 100 Coins to Play';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        if ($user[0]->wallet<$this->data['amount']) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $game = $this->Roulette_model->View($this->data['game_id']);
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

        // $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id'], $this->data['bet']);

        // if ($bet) {
        //     $data['message'] = 'One Bet Already Placed';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $bets = explode(",",$this->data['bet']);

        foreach ($bets as $key => $value) {
            $bet_data = [
                'roulette_id' => $this->data['game_id'],
                'user_id' => $this->data['user_id'],
                'bet' => $value,
                'amount' => round($this->data['amount']/count($bets),2),
                'added_date' => date('Y-m-d H:i:s')
            ];

            $bet_id = $this->Roulette_model->PlaceBet($bet_data);
        }

        if ($bet_id) {
            $this->Roulette_model->MinusWallet($this->data['user_id'], $this->data['amount']);
            log_statement ( $this->data['user_id'], RT,-$this->data['amount'],$bet_id) ;
            $data['message'] = 'Success';
            $data['bet_id'] = $bet_id;
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

        $game = $this->Roulette_model->View($this->data['game_id']);
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

        // $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id']);
        // if ($bet) {
        //     $data['message'] = 'One Bet Already Placed';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $last_bet = $this->Roulette_model->ViewBet($this->data['user_id'],$this->data['game_id']-1);
        $total_amount = 0;
        foreach ($last_bet as $key => $value) {
            $total_amount += $value->amount;
        }
        if ($user[0]->wallet<$total_amount) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        foreach ($last_bet as $key => $value) {
            $bet_data = [
                'roulette_id' => $this->data['game_id'],
                'user_id' => $this->data['user_id'],
                'bet' => $value->bet,
                'amount' => $value->amount,
                'added_date' => date('Y-m-d H:i:s')
    
            ];
    
            $bet_id = $this->Roulette_model->PlaceBet($bet_data);
        }

        // if($bet_id)
        // {
        $this->Roulette_model->MinusWallet($this->data['user_id'], $total_amount);
        $data['message'] = 'Success';
        // $data['bet_id'] = $bet_id;
        $data['bet'] = $last_bet;
        $data['amount'] = $total_amount;
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

        $game = $this->Roulette_model->View($this->data['game_id']);
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

        // $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id']);
        // if ($bet) {
        //     $data['message'] = 'One Bet Already Placed';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        $last_bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id']);

        $total_amount = 0;
        foreach ($last_bet as $key => $value) {
            $total_amount += $value->amount;
        }
        if ($user[0]->wallet<$total_amount) {
            $data['message'] = 'Insufficient Wallet Amount';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        foreach ($last_bet as $key => $value) {
            $bet_data = [
                'roulette_id' => $this->data['game_id'],
                'user_id' => $this->data['user_id'],
                'bet' => $value->bet,
                'amount' => $value->amount,
                'added_date' => date('Y-m-d H:i:s')
    
            ];
    
            $bet_id = $this->Roulette_model->PlaceBet($bet_data);
        }


        // $amount = $last_bet[0]->amount*2;
        // if ($user[0]->wallet<$amount) {
        //     $data['message'] = 'Insufficient Wallet Amount';
        //     $data['code'] = HTTP_NOT_ACCEPTABLE;
        //     $this->response($data, 200);
        //     exit();
        // }

        // $bet_data = [
        //     'roulette_id' => $this->data['game_id'],
        //     'user_id' => $this->data['user_id'],
        //     'bet' => $last_bet[0]->bet,
        //     'amount' => $amount,
        //     'added_date' => date('Y-m-d H:i:s')
        // ];

        // $bet_id = $this->Roulette_model->PlaceBet($bet_data);

        if ($bet_id) {
            $this->Roulette_model->MinusWallet($this->data['user_id'], $total_amount);
            $data['message'] = 'Success';
            $data['bet'] = $last_bet;
            $data['amount'] = $total_amount;
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

        $game = $this->Roulette_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id'], '', '');
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

        if ($this->Roulette_model->DeleteBet($this->data['user_id'], $this->data['game_id'])) {

            foreach ($bet as $key => $value) {
                $this->Roulette_model->AddWallet($this->data['user_id'], $value->amount);
            
                log_statement($this->data['user_id'], RT, $value->amount, $value->id, 0);
            }

            $data['message'] = 'Bet Cancel Successfully';
            $user_wallet = $this->Users_model->UserProfile($this->data['user_id']);
            $data['wallet'] = $user_wallet[0]->wallet;
            $data['cancel_bet'] =$bet[0];
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

    public function jackpot_winners_post()
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

        $winners = $this->Roulette_model->getJackpotWinners();
        if ($winners) {
            foreach ($winners as $key => $value) {
                $value->user_data = $this->Roulette_model->getJackpotBigWinners($value->id);
            }
            $data['winners'] = $winners;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No Jackpot Till Now';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }
    }

    public function last_winners_post()
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

        $winners = $this->Roulette_model->LastWinningBet($this->data['room_id'], 50);
        if ($winners) {
            $data['winners'] = $winners;
            $data['code'] = HTTP_OK;
            $this->response($data, HTTP_OK);
            exit();
        } else {
            $data['message'] = 'No Jackpot Till Now';
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

        $game = $this->Roulette_model->View($this->data['game_id']);
        if (!$game) {
            $data['message'] = 'Invalid Game Id';
            $data['code'] = HTTP_NOT_ACCEPTABLE;
            $this->response($data, 200);
            exit();
        }

        $win_amount = 0;
        $bet_amount = 0;
        $bet = $this->Roulette_model->ViewBet($this->data['user_id'], $this->data['game_id']);
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