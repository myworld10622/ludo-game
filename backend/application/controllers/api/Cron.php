<?php

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Users_model',
            'Game_model',
            'Setting_model',
            'AnderBahar_model',
            'AnderBaharPlus_model',
            'AnimalRoulette_model',
            'DragonTiger_model',
            'Jackpot_model',
            'CarRoulette_model',
            'ColorPrediction_model',
            'ColorPrediction1Min_model',
            'ColorPrediction3Min_model',
            'ColorPrediction5Min_model',
            'SevenUp_model',
            'RummyPool_model',
            'RummyDeal_model',
            'Rummy_model',
            'Poker_model',
            'HeadTail_model',
            'RedBlack_model',
            'Baccarat_model',
            'JhandiMunda_model',
            'Roulette_model',
            'Target_model',
            'GoldenWheel_model',
            'RummyCacheta_model',
            'Lottery_model',
            'Betreeno_model',
            'Ludo_model'
        ]);
    }

    public function teenpatti()
    {
        $tables = $this->Game_model->getActiveTable();
        // print_r($tables);

        foreach ($tables as $val) {
            $chaal = 0;
            $game = $this->Game_model->getActiveGameOnTable($val->table_id);
            // print_r($game);
            if ($game) {
                $game_log = $this->Game_model->GameLog($game->id, 1);
                if ($game_log) {
                    $time = time()-strtotime($game_log[0]->added_date);
                    // print_r($game_log);
                    if ($time>35) {
                        $game_users = $this->Game_model->GameAllUser($game->id);


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
                    }
                    // echo $chaal;
                    if ($chaal!=0) {
                        $this->Game_model->PackGame($chaal, $game->id, 1);
                        $game_users = $this->Game_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            $this->Game_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);

                            $user = $this->Users_model->UserProfile($game_users[0]->user_id);
                            if ($user[0]->user_type==1) {
                                $table_user_data = [
                                    'table_id' => $val->table_id,
                                    'user_id' => $user[0]->id
                                ];

                                $this->Game_model->RemoveTableUser($table_user_data);
                            }
                        }

                        $table_user_data = [
                            'table_id' => $val->table_id,
                            'user_id' =>$chaal
                        ];

                        $this->Game_model->RemoveTableUser($table_user_data);
                    }
                }
            }

            echo '<br>Success';
        }
    }

    public function teenpatti_socket($table_id)
    {
        // $tables = $this->Game_model->getActiveTable();
        // // print_r($tables);

        // foreach ($tables as $val) {
            $chaal = 0;
            $game = $this->Game_model->getActiveGameOnTable($table_id);
            // print_r($game);
            if ($game) {
                $game_log = $this->Game_model->GameLog($game->id, 1);
                if ($game_log) {
                    $time = time()-strtotime($game_log[0]->added_date);
                    // print_r($game_log);
                    // if ($time>35) {
                        $game_users = $this->Game_model->GameAllUser($game->id);


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
                    // }
                    // echo $chaal;
                    if ($chaal!=0) {
                        $this->Game_model->PackGame($chaal, $game->id, 1);
                        $game_users = $this->Game_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            $this->Game_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);

                            $user = $this->Users_model->UserProfile($game_users[0]->user_id);
                            if ($user[0]->user_type==1) {
                                $table_user_data = [
                                    'table_id' => $table_id,
                                    'user_id' => $user[0]->id
                                ];

                                $this->Game_model->RemoveTableUser($table_user_data);
                            }
                        }

                        $table_user_data = [
                            'table_id' => $table_id,
                            'user_id' =>$chaal
                        ];

                        $this->Game_model->RemoveTableUser($table_user_data);
                    }
                }
            }

            echo ($game)?'Running':'Stop';
        // }
    }

    public function rummy()
    {
        // log_message('error', 'hello test');
        $tables = $this->Rummy_model->getActiveTable();
        // print_r($tables);

        foreach ($tables as $val) {
            $game = $this->Rummy_model->getActiveGameOnTable($val->rummy_table_id);
            if ($game) {
                $chaal = 0;
                $user_type = 0;
                $declare_count = 0;

                $game_log = $this->Rummy_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->Rummy_model->GameAllUser($game->id);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                $given_time = ($user_type==0) ? 50 : 10;
                // log_message('error', 'robot timing '.$time.' - '.$given_time);
                if ($time>$given_time) {
                    if ($user_type==1) {
                        // log_message('error', 'robot play 123');
                        $bot_chaal = $this->Rummy_model->ChaalCount($game->id, $chaal);
                        // log_message('error', 'robot play - '.$bot_chaal);
                        if ($bot_chaal>2) {
                            $combination_json[] = '[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            
                            $combination_json[] = '[{"card_group":"5","cards":["BL4","BL3_","BL2_"]},{"card_group":"4","cards":["BL10","BL9","BL8_","BLJ"]},{"card_group":"5","cards":["RP6_","RP5","RP4"]},{"card_group":"4","cards":["BLK_","BLQ_","BLJ_"]}]';
                            $combination_json[] = '[{"card_group":"5","cards":["RSA_","RSK_","RSQ_"]},{"card_group":"4","cards":["BPK_","BPQ_","BPJ_"]},{"card_group":"4","cards":["BL8_","BL6","BL7"]},{"card_group":"5","cards":["BP9_","BP8","BP7_","BP6_"]}]';
                            $combination_json[] = '[{"card_group":"4","cards":["RSJ","RS9_","RS8_","RS10"]},{"card_group":"4","cards":["RP7","RP5","RP6_"]},{"card_group":"4","cards":["BLA","BL3_","BL2_"]},{"card_group":"5","cards":["BPA_","BPK","BPQ"]}]';
                            
                            $bot_combination_json = $combination_json[array_rand($combination_json)];
                            // $combination = json_decode($combination_json);
                            $data_declare = [
                                'user_id' => $chaal,
                                'game_id' => $game->id,
                                'points' => 0,
                                'actual_points' => 0,
                                'json' => $bot_combination_json
                            ];
                            $this->Rummy_model->Declare($data_declare);
                            continue;
                        }
                    }

                    if ($game_log[0]->action==3) {
                        $game_active_users = $this->Rummy_model->GameUser($game->id);

                        foreach ($game_active_users as $key => $value) {
                            if ($user_type==1) {
                                $combination_json[] = '[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                                $bot_combination_json = $combination_json[array_rand($combination_json)];
                                $json_arr = array();

                                $json_arr[0]['json'] = $bot_combination_json;
                                $json_arr = json_decode(json_encode($json_arr), false);
                            } else {
                                $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $chaal);
                            }
                            // $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $value->user_id);

                            if ($json_arr) {
                                $already_declare = $this->Rummy_model->GameLog($game->id, 1, 3, $chaal);

                                if (!$already_declare) {
                                    $json = $json_arr[0]->json;
                                    $arr = json_decode($json);
                                    $points = 0;
                                    // $wrong = 0;

                                    $table = $this->Rummy_model->isTableAvail($val->rummy_table_id);
                                    $actual_points = $points*round($table->boot_value/80, 2);

                                    $data_log = [
                                        'user_id' => $chaal,
                                        'game_id' => $game->id,
                                        'table_id' => $val->rummy_table_id,
                                        'points' => $points,
                                        'actual_points' => $actual_points,
                                        'json' => $json
                                    ];
                                    $this->Rummy_model->Declare($data_log);
                                }

                                $declare_log = $this->Rummy_model->GameLog($game->id, '', 3);
                                $declare_count = count($declare_log);
                                // $remain_game_users = $this->Rummy_model->GameUser($game->id);
                                if (count($game_active_users)<=$declare_count) {
                                    // $amount = 0;
                                    $game = $this->Rummy_model->getActiveGameOnTable($val->rummy_table_id);
                                    if ($game) {
                                        $comission = $this->Setting_model->Setting()->admin_commission;
                                        $this->Rummy_model->MakeWinner($game->id, $game->amount, $declare_log[$declare_count-1]->user_id, $comission);
                                    }
                                }
                            }
                        }

                        continue;
                    }

                    $timeout_log = $this->Rummy_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    if (count($timeout_log)<2) {
                        $cards = $this->Rummy_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->Rummy_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->Rummy_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->Rummy_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if ($json) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $time_out = ($user_type==0) ? 1 : 0;
                            $this->Rummy_model->DropGameCards($table_user_data, $json, $time_out);
                        }
                    } else {
                        $table = $this->Rummy_model->isTableAvail($val->rummy_table_id);
                        $boot_value = $table->boot_value;
                        $ChaalCount = $this->Rummy_model->ChaalCount($game->id, $chaal);

                        $percent = ($ChaalCount>0) ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                        $amount = round(($percent / 100) * $boot_value, 2);

                        $this->Rummy_model->PackGame($chaal, $game->id, 1, '', $amount, $percent);
                        $this->Rummy_model->MinusWallet($chaal, $amount);
                        $game_users = $this->Rummy_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $game = $this->Rummy_model->getActiveGameOnTable($val->rummy_table_id);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            $this->Rummy_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
                            // $this->Rummy_model->MakeWinner($game->id,$amount,$game_users[0]->user_id);
                        }

                        $table_user_data = [
                                'table_id' => $val->rummy_table_id,
                                'user_id' =>$chaal
                        ];

                        $this->Rummy_model->RemoveTableUser($table_user_data);
                    }
                }
            }

            echo '<br>Success';
        }
    }

    public function ludo_socket($table_id)
    {
        $game = $this->Ludo_model->getActiveGameOnTable($table_id);
        if ($game) {
            $chaal = 0;
            $user_type = 0;
            $declare_count = 0;

            $last_chaal = $this->Ludo_model->LastChaal($game->id);

            if($last_chaal->step==6){
                $chaal = $last_chaal->user_id;
            }else{
                $game_users = $this->Ludo_model->GameAllUser($game->id);

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
                        if (!$game_users[$index]->packed) {
                            $chaal = $game_users[$index]->user_id;
                            break;
                        }
                    }
                }
            }
            
            $table = $this->Ludo_model->isTableAvail($table_id);
            
            $this->Ludo_model->PackGame($chaal, $game->id, 1);

            $game_users = $this->Ludo_model->GameUser($game->id);

            if (count($game_users)==1) {
                $game = $this->Ludo_model->getActiveGameOnTable($table_id);
                $comission = $this->Setting_model->Setting()->admin_commission;
                $this->Ludo_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
            }

            $table_user_data = [
                    'table_id' => $table_id,
                    'user_id' =>$chaal
            ];

            $this->Ludo_model->RemoveTableUser($table_user_data);
        }

        echo ($game)?'Running':'Stop';
    }

    public function rummy_socket($table_id)
    {
        // // log_message('error', 'hello test');
        // $tables = $this->Rummy_model->getActiveTable();
        // // print_r($tables);

        // foreach ($tables as $val) {
            $game = $this->Rummy_model->getActiveGameOnTable($table_id);
            if ($game) {
                $chaal = 0;
                $user_type = 0;
                $declare_count = 0;

                $game_log = $this->Rummy_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->Rummy_model->GameAllUser($game->id);

                // print_r(json_encode($game_users));
                $element = 0;
                foreach ($game_users as $key => $value) {
                    if ($value->user_id==$game_log[0]->user_id) {
                        $element = $key;
                        break;
                    }
                }

                $index = 0;
                $pack_users = 1;
                foreach ($game_users as $key => $value) {
                    $index = ($key+$element)%count($game_users);
                    if ($key>0) {
                        if (!$game_users[$index]->packed) {
                            $chaal = $game_users[$index]->user_id;
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }else{
                            $pack_users++;
                        }
                    }
                }

                // echo $pack_users;
                if($chaal==0 && count($game_users)==$pack_users){
                    // echo 'removed packed users';
                    foreach ($game_users as $key => $value) {
                        $table_user_data = [
                            'table_id' => $table_id,
                            'user_id' => $value->user_id
                        ];

                        $this->Rummy_model->RemoveTableUser($table_user_data);
                        // echo '--- removed - '.$value->user_id;
                    }
                }
                // $given_time = ($user_type==0) ? 50 : 10;
                // // log_message('error', 'robot timing '.$time.' - '.$given_time);
                // if ($time>$given_time) {
                    if ($user_type==1) {
                        // log_message('error', 'robot play 123');
                        $bot_chaal = $this->Rummy_model->ChaalCount($game->id, $chaal);
                        // log_message('error', 'robot play - '.$bot_chaal);
                        if ($bot_chaal>rand(8,10)) {
                            $combination_json[] = '[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            
                            $combination_json[] = '[{"card_group":"5","cards":["BL4","BL3_","BL2_"]},{"card_group":"4","cards":["BL10","BL9","BL8_","BLJ"]},{"card_group":"5","cards":["RP6_","RP5","RP4"]},{"card_group":"4","cards":["BLK_","BLQ_","BLJ_"]}]';
                            $combination_json[] = '[{"card_group":"5","cards":["RSA_","RSK_","RSQ_"]},{"card_group":"4","cards":["BPK_","BPQ_","BPJ_"]},{"card_group":"4","cards":["BL8_","BL6","BL7"]},{"card_group":"5","cards":["BP9_","BP8","BP7_","BP6_"]}]';
                            $combination_json[] = '[{"card_group":"4","cards":["RSJ","RS9_","RS8_","RS10"]},{"card_group":"4","cards":["RP7","RP5","RP6_"]},{"card_group":"4","cards":["BLA","BL3_","BL2_"]},{"card_group":"5","cards":["BPA_","BPK","BPQ"]}]';
                            
                            $bot_combination_json = $combination_json[array_rand($combination_json)];
                            // $combination = json_decode($combination_json);
                            $data_declare = [
                                'user_id' => $chaal,
                                'game_id' => $game->id,
                                'points' => 0,
                                'actual_points' => 0,
                                'json' => $bot_combination_json
                            ];
                            $this->Rummy_model->Declare($data_declare);
                            return;
                        }
                    }

                    if ($game_log[0]->action==3) {
                        $game_active_users = $this->Rummy_model->GameUser($game->id);

                        foreach ($game_active_users as $key => $value) {
                            if ($user_type==1) {
                                $combination_json[] = '[{"card_group":"5","cards":["BLK","BLA","BLQ"]},{"card_group":"0","cards":["BPJ_","BP3","BP9"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"4","cards":["JKR1","RP8_","RP7"]}]';
                                $combination_json[] = '[{"card_group":"0","cards":["RS3_","BL8_","BPJ"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"0","cards":["BP8_","BP5_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"0","cards":["RS3_","BPK","RPQ_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"4","cards":["BP3_","BP4","BP5_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"0","cards":["BL5","BL4_","RP8"]}]';
                                $bot_combination_json = $combination_json[array_rand($combination_json)];
                                $json_arr = array();

                                $json_arr[0]['json'] = $bot_combination_json;
                                $json_arr = json_decode(json_encode($json_arr), false);
                            } else {
                                // $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $chaal);
                                $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $value->user_id);
                            }

                            // if ($json_arr) {
                                $already_declare = $this->Rummy_model->GameLog($game->id, 1, 3, $value->user_id);

                                if (!$already_declare) {
                                    $json = $json_arr[0]->json;
                                    // $json = '[]';
                                    $arr = json_decode($json);
                                    $points = 0;
                                    foreach ($arr as $ke => $val) {
                                        if ($val->card_group==0) {
                                            $points = $points+$this->rummy_card_points($val->cards, $game->joker);
                                        }
                                    }
                                    $points = ($points==0)?2:$points;
                                    $points = ($points>80) ? 80 : $points;
                                    // $wrong = 0;

                                    $table = $this->Rummy_model->isTableAvail($table_id);
                                    $actual_points = $points*round($table->boot_value/80, 2);

                                    $data_log = [
                                        'user_id' => $value->user_id,
                                        'game_id' => $game->id,
                                        'table_id' => $table_id,
                                        'points' => $points,
                                        'actual_points' => $actual_points,
                                        'json' => $json
                                    ];
                                    $this->Rummy_model->Declare($data_log);
                                }

                                $declare_log = $this->Rummy_model->GameLog($game->id, '', 3);
                                $declare_count = count($declare_log);
                                // $remain_game_users = $this->Rummy_model->GameUser($game->id);
                                log_message('debug', 'declare count - '.count($game_active_users).' - '.$declare_count);
                                if (count($game_active_users)<=$declare_count) {
                                    // $amount = 0;
                                    $game = $this->Rummy_model->getActiveGameOnTable($table_id);
                                    if ($game) {
                                        $comission = $this->Setting_model->Setting()->admin_commission;
                                        $this->Rummy_model->MakeWinner($game->id, $game->amount, $declare_log[$declare_count-1]->user_id, $comission);
                                    }
                                }
                            // }
                        }

                        return;
                    }

                        // echo $chaal;
                    $timeout_log = $this->Rummy_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    if (count($timeout_log)<2) {
                        $cards = $this->Rummy_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->Rummy_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->Rummy_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->Rummy_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if ($json) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $time_out = ($user_type==0) ? 1 : 0;
                            $this->Rummy_model->DropGameCards($table_user_data, $json, $time_out);
                        }
                    } else {
                        $table = $this->Rummy_model->isTableAvail($table_id);
                        $boot_value = $table->boot_value;
                        $ChaalCount = $this->Rummy_model->ChaalCount($game->id, $chaal);

                        $percent = ($ChaalCount>0) ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                        $amount = round(($percent / 100) * $boot_value, 2);

                        $this->Rummy_model->PackGame($chaal, $game->id, 1, '', $amount, $percent);
                        $this->Rummy_model->MinusWallet($chaal, $amount);
                        $game_users = $this->Rummy_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $game = $this->Rummy_model->getActiveGameOnTable($table_id);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            $this->Rummy_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
                            // $this->Rummy_model->MakeWinner($game->id,$amount,$game_users[0]->user_id);
                        }

                        $table_user_data = [
                                'table_id' => $table_id,
                                'user_id' =>$chaal
                        ];

                        $this->Rummy_model->RemoveTableUser($table_user_data);
                    }
                // }
            }

            echo ($game)?'Running':'Stop';
        // }
    }

    public function rummy_cacheta_socket($table_id)
    {
        // // log_message('error', 'hello test');
        // $tables = $this->Rummy_model->getActiveTable();
        // // print_r($tables);

        // foreach ($tables as $val) {
            $game = $this->RummyCacheta_model->getActiveGameOnTable($table_id);
            if ($game) {
                $chaal = 0;
                $user_type = 0;
                $declare_count = 0;

                $game_log = $this->RummyCacheta_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->RummyCacheta_model->GameAllUser($game->id);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                // $given_time = ($user_type==0) ? 50 : 10;
                // // log_message('error', 'robot timing '.$time.' - '.$given_time);
                // if ($time>$given_time) {
                    if ($user_type==1) {
                        // log_message('error', 'robot play 123');
                        $bot_chaal = $this->RummyCacheta_model->ChaalCount($game->id, $chaal);
                        // log_message('error', 'robot play - '.$bot_chaal);
                        if ($bot_chaal>2) {
                            $combination_json[] = '[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                            
                            $combination_json[] = '[{"card_group":"5","cards":["BL4","BL3_","BL2_"]},{"card_group":"4","cards":["BL10","BL9","BL8_","BLJ"]},{"card_group":"5","cards":["RP6_","RP5","RP4"]},{"card_group":"4","cards":["BLK_","BLQ_","BLJ_"]}]';
                            $combination_json[] = '[{"card_group":"5","cards":["RSA_","RSK_","RSQ_"]},{"card_group":"4","cards":["BPK_","BPQ_","BPJ_"]},{"card_group":"4","cards":["BL8_","BL6","BL7"]},{"card_group":"5","cards":["BP9_","BP8","BP7_","BP6_"]}]';
                            $combination_json[] = '[{"card_group":"4","cards":["RSJ","RS9_","RS8_","RS10"]},{"card_group":"4","cards":["RP7","RP5","RP6_"]},{"card_group":"4","cards":["BLA","BL3_","BL2_"]},{"card_group":"5","cards":["BPA_","BPK","BPQ"]}]';
                            
                            $bot_combination_json = $combination_json[array_rand($combination_json)];
                            // $combination = json_decode($combination_json);
                            $data_declare = [
                                'user_id' => $chaal,
                                'game_id' => $game->id,
                                'points' => 0,
                                'actual_points' => 0,
                                'json' => $bot_combination_json
                            ];
                            $this->RummyCacheta_model->Declare($data_declare);
                            return;
                        }
                    }

                    if ($game_log[0]->action==3) {
                        $game_active_users = $this->RummyCacheta_model->GameUser($game->id);

                        foreach ($game_active_users as $key => $value) {
                            if ($user_type==1) {
                                $combination_json[] = '[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                                $combination_json[] = '[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]';
                                $bot_combination_json = $combination_json[array_rand($combination_json)];
                                $json_arr = array();

                                $json_arr[0]['json'] = $bot_combination_json;
                                $json_arr = json_decode(json_encode($json_arr), false);
                            } else {
                                // $json_arr = $this->Rummy_model->GameLog($game->id, 1, 2, $chaal);
                                $json_arr = $this->RummyCacheta_model->GameLog($game->id, 1, 2, $value->user_id);
                            }

                            // if ($json_arr) {
                                $already_declare = $this->RummyCacheta_model->GameLog($game->id, 1, 3, $value->user_id);

                                if (!$already_declare) {
                                    // $json = $json_arr[0]->json;
                                    $json = '[]';
                                    // $arr = json_decode($json);
                                    $points = 80;
                                    // $wrong = 0;

                                    $table = $this->RummyCacheta_model->isTableAvail($table_id);
                                    $actual_points = $points*round($table->boot_value/80, 2);

                                    $data_log = [
                                        'user_id' => $value->user_id,
                                        'game_id' => $game->id,
                                        'table_id' => $table_id,
                                        'points' => $points,
                                        'actual_points' => $actual_points,
                                        'json' => $json
                                    ];
                                    $this->RummyCacheta_model->Declare($data_log);
                                }

                                $declare_log = $this->RummyCacheta_model->GameLog($game->id, '', 3);
                                $declare_count = count($declare_log);
                                // $remain_game_users = $this->Rummy_model->GameUser($game->id);
                                if (count($game_active_users)<=$declare_count) {
                                    // $amount = 0;
                                    $game = $this->RummyCacheta_model->getActiveGameOnTable($table_id);
                                    if ($game) {
                                        $comission = $this->Setting_model->Setting()->admin_commission;
                                        $this->RummyCacheta_model->MakeWinner($game->id, $game->amount, $declare_log[$declare_count-1]->user_id, $comission);
                                    }
                                }
                            // }
                        }

                        return;
                    }

                    $timeout_log = $this->RummyCacheta_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    if (count($timeout_log)<2) {
                        $cards = $this->RummyCacheta_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=RUMMY_CACHETA_CARDS) {
                            $random_card = $this->RummyCacheta_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->RummyCacheta_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->RummyCacheta_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->RummyCacheta_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if ($json) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $time_out = ($user_type==0) ? 1 : 0;
                            $this->RummyCacheta_model->DropGameCards($table_user_data, $json, $time_out);
                        }
                    } else {
                        $table = $this->RummyCacheta_model->isTableAvail($table_id);
                        $boot_value = $table->boot_value;
                        $ChaalCount = $this->RummyCacheta_model->ChaalCount($game->id, $chaal);

                        $percent = ($ChaalCount>0) ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                        $amount = round(($percent / 100) * $boot_value, 2);

                        $this->RummyCacheta_model->PackGame($chaal, $game->id, 1, '', $amount, $percent);
                        $this->RummyCacheta_model->MinusWallet($chaal, $amount);
                        $game_users = $this->RummyCacheta_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $game = $this->RummyCacheta_model->getActiveGameOnTable($table_id);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            $this->RummyCacheta_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission);
                            // $this->Rummy_model->MakeWinner($game->id,$amount,$game_users[0]->user_id);
                        }

                        $table_user_data = [
                                'table_id' => $table_id,
                                'user_id' =>$chaal
                        ];

                        $this->RummyCacheta_model->RemoveTableUser($table_user_data);
                    }
                // }
            }

            echo '<br>Success';
        // }
    }

    public function rummy_pool()
    {
        $tables = $this->RummyPool_model->getActiveTable();

        foreach ($tables as $val) {
            $game = $this->RummyPool_model->getActiveGameOnTable($val->rummy_pool_table_id);
            $table = $this->RummyPool_model->isTableAvail($val->rummy_pool_table_id);
            if ($game) {
                $chaal = 0;
                $isChaal = false;
                $game_log = $this->RummyPool_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->RummyPool_model->GameAllUser($game->id);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                $given_time = ($user_type==0) ? 32 : 1;

                if ($time>$given_time) {
                    $timeout_log = $this->RummyPool_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    if (count($timeout_log)<2) {
                        $cards = $this->RummyPool_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->RummyPool_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->RummyPool_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->RummyPool_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->RummyPool_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if (!empty($json)) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $this->RummyPool_model->DropGameCards($table_user_data, $json, 1);
                        }
                    } else {
                        $percent = CHAAL_PERCENT;
                        $this->RummyPool_model->PackGame($chaal, $val->rummy_pool_table_id, $game->id, 1, '', '', $percent);
                        $game_users = $this->RummyPool_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $amount = 0;
                            // $this->RummyPool_model->MinusWallet($this->data['user_id'], $amount);
                            $this->RummyPool_model->MakeWinner($game->id, $amount, $game_users[0]->user_id);
                            $winner_data = ['points'=>0, 'table_id'=>$val->rummy_pool_table_id,'user_id'=>$game_users[0]->user_id,'game_id'=>$game->id,'json'=>''];
                            // print_r($winner_data);
                            $this->RummyPool_model->Declare($winner_data);

                            $All_table_users = $this->RummyPool_model->TableUser($val->rummy_pool_table_id);
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
                                    foreach ($user_ids as $va) {
                                        $table_user_data = [
                                            'table_id' => $val->rummy_pool_table_id,
                                            'user_id' =>$va
                                        ];

                                        $this->RummyPool_model->RemoveTableUser($table_user_data);
                                    }
                                    // // Make Winner Code
                                    $comission = $this->Setting_model->Setting()->admin_commission;
                                    $TotalAmount = $this->RummyPool_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);
                                    $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                                    $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                                    $this->RummyPool_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $val->rummy_pool_table_id, $winner_user_id);
                                    $this->RummyPool_model->AddToWallet($user_winning_amt, $winner_user_id);
                                }
                            }
                        }
                    }
                }
            }

            // echo '<br>Success';
        }
    }

    public function rummy_pool_socket($table_id)
    {
        // $tables = $this->RummyPool_model->getActiveTable();

        // foreach ($tables as $val) {
            $game = $this->RummyPool_model->getActiveGameOnTable($table_id);
            $table = $this->RummyPool_model->isTableAvail($table_id);
            if ($game) {
                $chaal = 0;
                $isChaal = false;
                $game_log = $this->RummyPool_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->RummyPool_model->GameAllUser($game->id);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                $given_time = ($user_type==0) ? 32 : 1;

                // if ($time>$given_time) {
                    $timeout_log = $this->RummyPool_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    if (count($timeout_log)<2) {
                        $cards = $this->RummyPool_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->RummyPool_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->RummyPool_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->RummyPool_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->RummyPool_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if (!empty($json)) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $this->RummyPool_model->DropGameCards($table_user_data, $json, 1);
                        }
                    } else {
                        $percent = CHAAL_PERCENT;
                        $this->RummyPool_model->PackGame($chaal, $table_id, $game->id, 1, '', '', $percent);
                        $game_users = $this->RummyPool_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $amount = 0;
                            // $this->RummyPool_model->MinusWallet($this->data['user_id'], $amount);
                            $this->RummyPool_model->MakeWinner($game->id, $amount, $game_users[0]->user_id);
                            $winner_data = ['points'=>0, 'table_id'=>$table_id,'user_id'=>$game_users[0]->user_id,'game_id'=>$game->id,'json'=>''];
                            // print_r($winner_data);
                            $this->RummyPool_model->Declare($winner_data);

                            $All_table_users = $this->RummyPool_model->TableUser($table_id);
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
                                    foreach ($user_ids as $va) {
                                        $table_user_data = [
                                            'table_id' => $table_id,
                                            'user_id' =>$va
                                        ];

                                        $this->RummyPool_model->RemoveTableUser($table_user_data);
                                    }
                                    // // Make Winner Code
                                    $comission = $this->Setting_model->Setting()->admin_commission;
                                    $TotalAmount = $this->RummyPool_model->TotalAmountOnTable($user[0]->rummy_pool_table_id);
                                    $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                                    $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                                    $this->RummyPool_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $val->rummy_pool_table_id, $winner_user_id);
                                    $this->RummyPool_model->AddToWallet($user_winning_amt, $winner_user_id);
                                }
                            }
                        }
                    }
                // }
            }

            // echo '<br>Success';
            echo ($game)?'Running':'Stop';
        // }
    }

    public function rummy_deal()
    {
        $tables = $this->RummyDeal_model->getActiveTable();

        foreach ($tables as $val) {
            $game = $this->RummyDeal_model->getActiveGameOnTable($val->rummy_deal_table_id);
            if ($game) {
                $chaal = 0;
                $isChaal = false;
                $game_log = $this->RummyDeal_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->RummyDeal_model->GameAllUser($game->id);
                // print_r($game_users);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                $given_time = ($user_type==0) ? 32 : 1;

                if ($time>$given_time) {
                    // echo $chaal;
                    $timeout_log = $this->RummyDeal_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    // exit;
                    if (count($timeout_log)<2) {
                        $cards = $this->RummyDeal_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->RummyDeal_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->RummyDeal_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->RummyDeal_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->RummyDeal_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if (!empty($json)) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $this->RummyDeal_model->DropGameCards($table_user_data, $json, 1);
                        }
                    } else {
                        // echo 'hello';
                        // exit;
                        $table_user_data = [
                            'table_id' => $val->rummy_deal_table_id,
                            'user_id' => $chaal
                        ];

                        $this->RummyDeal_model->RemoveTableUser($table_user_data);
                        $this->RummyDeal_model->PackGame($chaal, $game->id, 1);
                        $game_users = $this->RummyDeal_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $comission = $this->Setting_model->Setting()->admin_commission;

                            $TotalAmount = $this->RummyDeal_model->TotalAmountOnTable($val->rummy_deal_table_id);

                            $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                            $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                            $this->RummyDeal_model->MakeWinner($game->id, 0, $game_users[0]->user_id, $admin_winning_amt);
                            $this->RummyDeal_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $val->rummy_deal_table_id, $game_users[0]->user_id);
                            $this->RummyDeal_model->AddToWallet($user_winning_amt, $game_users[0]->user_id);
                        }
                    }
                }
            }

            echo '<br>Success';
        }
    }

    public function rummy_deal_socket($table_id)
    {
        // $tables = $this->RummyDeal_model->getActiveTable();

        // foreach ($tables as $val) {
            $game = $this->RummyDeal_model->getActiveGameOnTable($table_id);
            if ($game) {
                $chaal = 0;
                $isChaal = false;
                $game_log = $this->RummyDeal_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);

                $game_users = $this->RummyDeal_model->GameAllUser($game->id);
                // print_r($game_users);

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
                            $user_type = $game_users[$index]->user_type;
                            break;
                        }
                    }
                }
                $given_time = ($user_type==0) ? 32 : 1;

                // if ($time>$given_time) {
                    // echo $chaal;
                    $timeout_log = $this->RummyDeal_model->GameLog($game->id, '', 2, $chaal, 1);
                    // echo count($timeout_log);
                    // exit;
                    if (count($timeout_log)<2) {
                        $cards = $this->RummyDeal_model->getMyCards($game->id, $chaal);

                        if (count($cards)<=13) {
                            $random_card = $this->RummyDeal_model->GetRamdomGameCard($game->id);

                            if ($random_card) {
                                $table_user_data = [
                                    'game_id' => $game->id,
                                    'user_id' => $chaal,
                                    'card' => $random_card[0]->cards,
                                    'added_date' => date('Y-m-d H:i:s'),
                                    'updated_date' => date('Y-m-d H:i:s'),
                                    'isDeleted' => 0
                                ];

                                $this->RummyDeal_model->GiveGameCards($table_user_data);
                            }
                        }
                        $user_card = $this->RummyDeal_model->GameUserCard($game->id, $chaal);
                        if (!empty($user_card)) {
                            $json_arr = $this->RummyDeal_model->GameLog($game->id, 1, 2, $chaal);
                            $json = (empty($json_arr)) ? '' : $json_arr[0]->json;

                            // Joker Card Code
                            // $joker_num = substr(trim($game->joker,'_'), 2);
                            // $card_num = substr(trim($user_card->card,'_'), 2);
                            $card = "";

                            // if($joker_num==$card_num)
                            if ($user_card->card=='JKR1' || $user_card->card=='JKR2') {
                                if (!empty($json)) {
                                    $arr = json_decode($json);

                                    $final_arr = array();

                                    $card_json = array();
                                    foreach ($arr as $key => $value) {
                                        if (empty($card) && $value->card_group==0) {
                                            $card = $value->cards[0];
                                            //var_dump($value->cards);
                                            $card_json['card_group'] = "0";
                                            $card_json['cards'][0] = $user_card->card;
                                            $final_arr[] = $card_json;
                                            continue;
                                        }

                                        $final_arr[] = $value;
                                    }
                                    $json =  json_encode($final_arr);
                                }
                            }

                            $card = (!empty($card)) ? $card : $user_card->card;

                            $table_user_data = [
                                'game_id' => $game->id,
                                'user_id' => $chaal,
                                'card' => $card
                            ];

                            $this->RummyDeal_model->DropGameCards($table_user_data, $json, 1);
                        }
                    } else {
                        // echo 'hello';
                        // exit;
                        $table_user_data = [
                            'table_id' => $table_id,
                            'user_id' => $chaal
                        ];

                        $this->RummyDeal_model->RemoveTableUser($table_user_data);
                        $this->RummyDeal_model->PackGame($chaal, $game->id, 1);
                        $game_users = $this->RummyDeal_model->GameUser($game->id);

                        if (count($game_users)==1) {
                            $comission = $this->Setting_model->Setting()->admin_commission;

                            $TotalAmount = $this->RummyDeal_model->TotalAmountOnTable($table_id);

                            $admin_winning_amt = round($TotalAmount * round($comission/100, 2));
                            $user_winning_amt = round($TotalAmount - $admin_winning_amt, 2);

                            $this->RummyDeal_model->MakeWinner($game->id, 0, $game_users[0]->user_id, $admin_winning_amt);
                            $this->RummyDeal_model->updateTotalWinningAmtTable($TotalAmount, $user_winning_amt, $admin_winning_amt, $table_id, $game_users[0]->user_id);
                            $this->RummyDeal_model->AddToWallet($user_winning_amt, $game_users[0]->user_id);
                        }
                    }
                // }
            }

            // echo '<br>Success';
            echo ($game)?'Running':'Stop';
        // }
    }

    public function ander_bahar()
    {
        echo 'Date '.date('Y-m-d H:i:s').PHP_EOL;
        $room_data = $this->AnderBahar_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBahar_model->getActiveGameOnTable($room->id);
                // print_r($game_data);
                if (!$game_data) {
                    $card = $this->AnderBahar_model->GetCards($limit)[0]->cards;
                    $this->AnderBahar_model->Create($room->id, $card);

                    echo 'First Ander Baher Game Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $min = 1;
                        $max = 30;

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id);

                        $AnderBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id, ANDER);
                        $BaharBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id, BAHAR);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->ander_bahar_random;
                        if ($random==1) {
                            $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                        } else {
                            if ($AnderBetAmount>0 || $BaharBetAmount>0) {
                                $winning = ($AnderBetAmount>$BaharBetAmount) ? BAHAR : ANDER; //0=ander,1=bahar
                            } else {
                                $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                            }
                        }

                        $exit = false;
                        do {
                            $number = rand($min, $max);
                            if ($winning==BAHAR) {
                                $exit = ($number % 2 != 0);
                            } else {
                                $exit = ($number % 2 == 0);
                            }
                        } while (!$exit);

                        $card_num = substr($game_data[0]->main_card, 2);
                        $middle_cards = $this->AnderBahar_model->GetCards($number, $card_num);
                        $alt_card = $this->AnderBahar_model->GetCards($limit, $game_data[0]->main_card, $card_num)[0]->cards;

                        foreach ($middle_cards as $key => $value) {
                            $this->AnderBahar_model->CreateMap($game_data[0]->id, $value->cards);
                        }
                        $this->AnderBahar_model->CreateMap($game_data[0]->id, $alt_card);

                        // Give winning Amount to user
                        $multiply = ($winning==ANDER) ? 1.85 : 1.95; //ander=1.85,bahar=1.95
                        $bets = $this->AnderBahar_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($bets);
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBahar_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        // $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+ '.(count($middle_cards)+5).'seconds'));
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.(round(count($middle_cards)/5)+2).' seconds'));
                        $update_data['random'] = $random;
                        $this->AnderBahar_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'ander_bahar_room_id');
                        if ($count>0) {
                            $card = $this->AnderBahar_model->GetCards($limit)[0]->cards;
                            $this->AnderBahar_model->Create($room->id, $card);

                            echo 'Ander Baher Game Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
        $this->Users_model->UpdateOfflineUsers();
    }

    public function ander_bahar_create_socket()
    {
        $room_data = $this->AnderBahar_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBahar_model->getActiveGameOnTable($room->id);
                // print_r($game_data);
                if (!$game_data || $game_data[0]->status==1) {
                    $card = $this->AnderBahar_model->GetCards($limit)[0]->cards;
                    $this->AnderBahar_model->Create($room->id, $card);

                    echo 'Ander Baher Game Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
        $this->Users_model->UpdateOfflineUsers();
    }

    public function ander_bahar_winner_socket()
    {
        $room_data = $this->AnderBahar_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBahar_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $min = 6;
                        $max = 30;

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id);

                        $AnderBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id, ANDER);
                        $BaharBetAmount = $this->AnderBahar_model->TotalBetAmount($game_data[0]->id, BAHAR);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->ander_bahar_random;
                        if ($random==1) {
                            $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                        }else if($random==2){

                            $admin_coin = $setting->admin_coin;
                            if ($AnderBetAmount==0 && $BaharBetAmount==0) {
                                $winning = RAND(0, 1);
                            } else {
                                $option_arr = [0,1];
                                shuffle($option_arr);
                                $winning = "";
                                foreach ($option_arr as $k => $value) {

                                    switch ($value) {
                                        case ANDER:
                                            if($AnderBetAmount>0 && $admin_coin>=$AnderBetAmount){
                                                $winning = DRAGON;
                                            }
                                            break;
                                        case BAHAR:
                                            if($BaharBetAmount>0 && $admin_coin>=$BaharBetAmount){
                                                $winning = TIGER;
                                            }
                                            break;
                                    }

                                    if($winning!=""){
                                        break;
                                    }
                                }

                                if($winning==""){
                                    if ($AnderBetAmount>0 || $BaharBetAmount>0) {
                                        $winning = ($AnderBetAmount>$BaharBetAmount) ? BAHAR : ANDER; //0=ander,1=bahar
                                    } else {
                                        $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                                    }
                                }
                            }

                        } else {
                            if ($AnderBetAmount>0 || $BaharBetAmount>0) {
                                $winning = ($AnderBetAmount>$BaharBetAmount) ? BAHAR : ANDER; //0=ander,1=bahar
                            } else {
                                $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                            }
                        }

                        $exit = false;
                        do {
                            $number = rand($min, $max);
                            if ($winning==BAHAR) {
                                $exit = ($number % 2 != 0);
                            } else {
                                $exit = ($number % 2 == 0);
                            }
                        } while (!$exit);
                        // $number = 8;
                        $card_num = substr($game_data[0]->main_card, 2);
                        $middle_cards = $this->AnderBahar_model->GetCards($number, $card_num);
                        $alt_card = $this->AnderBahar_model->GetCards($limit, $game_data[0]->main_card, $card_num)[0]->cards;

                        foreach ($middle_cards as $key => $value) {
                            $this->AnderBahar_model->CreateMap($game_data[0]->id, $value->cards);
                        }
                        $this->AnderBahar_model->CreateMap($game_data[0]->id, $alt_card);

                        // Give winning Amount to user
                        $multiply = ($winning==ANDER) ? 1.85 : 1.95; //ander=1.85,bahar=1.95
                        $bets = $this->AnderBahar_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($bets);
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBahar_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No Winning Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        // $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+ '.(count($middle_cards)+5).'seconds'));
                        $seconds = round(count($middle_cards)/3)+2;
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.($seconds).' seconds'));
                        $update_data['random'] = $random;

                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(AB,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->AnderBahar_model->Update($update_data, $game_data[0]->id);
                        echo $seconds;
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function dragon_tiger()
    {
        $room_data = $this->DragonTiger_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->DragonTiger_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = "";
                    $this->DragonTiger_model->Create($room->id, $card);

                    echo 'First Dragon Tiger Game Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id);

                        $DragonBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, DRAGON)*2;
                        $TigerBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, TIGER)*2;
                        $TieBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, TIE)*11;

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->dragon_tiger_random;
                        if ($random==1) {
                            $winning = RAND(0, 2);
                        } else {
                            if ($DragonBetAmount==0 && $TigerBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } elseif ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                                $winning = TIE;
                            } else {
                                $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                            }
                        }

                        if ($winning==TIE) {
                            $number = rand(2, 10);
                            $card_dragon = 'BP'.$number;
                            $card_tiger = 'RP'.$number;

                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_dragon);
                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_tiger);
                        } else {
                            do {
                                $limit = 2;
                                $cards = $this->DragonTiger_model->GetCards($limit);
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

                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_dragon);
                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_tiger);
                        }

                        // Give winning Amount to user
                        $bets = $this->DragonTiger_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                if ($winning==TIE) {
                                    $amount = $value->amount*TIE_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->DragonTiger_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*DRAGON_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->DragonTiger_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->DragonTiger_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'dragon_tiger_room_id');
                        if ($count>0) {
                            $this->DragonTiger_model->Create($room->id);

                            echo 'Dragon Tiger Game Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function dragon_tiger_create_socket()
    {
        $room_data = $this->DragonTiger_model->getRoom();
        // echo 'First Dragon Tiger Game Created Successfully'.PHP_EOL;
        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->DragonTiger_model->getActiveGameOnTable($room->id);
                // print_r($game_data);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = "";
                    $this->DragonTiger_model->Create($room->id, $card);

                    echo 'Dragon Tiger Game Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function dragon_tiger_winner_socket()
    {
        $room_data = $this->DragonTiger_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->DragonTiger_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id);

                        $DragonBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, DRAGON)*DRAGON_MULTIPLY;
                        $TigerBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, TIGER)*DRAGON_MULTIPLY;
                        $TieBetAmount = $this->DragonTiger_model->TotalBetAmount($game_data[0]->id, TIE)*TIE_MULTIPLY;

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->dragon_tiger_random;
                        if ($random==1) {
                            $winning = RAND(0, 2);
                        }else if($random==2){

                            $admin_coin = $setting->admin_coin;
                            if ($DragonBetAmount==0 && $TigerBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } else {
                                $option_arr = [0,1,2];
                                shuffle($option_arr);
                                $winning = "";
                                foreach ($option_arr as $k => $value) {

                                    switch ($value) {
                                        case DRAGON:
                                            if($DragonBetAmount>0 && $admin_coin>=$DragonBetAmount){
                                                $winning = DRAGON;
                                            }
                                            break;
                                        case TIGER:
                                            if($TigerBetAmount>0 && $admin_coin>=$TigerBetAmount){
                                                $winning = TIGER;
                                            }
                                            break;
                                        case TIE:
                                            if($TieBetAmount>0 && $admin_coin>=$TieBetAmount){
                                                $winning = TIE;
                                            }
                                            break;
                                    }

                                    if($winning!=""){
                                        break;
                                    }
                                }

                                if($winning==""){
                                    if ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                                        $winning = TIE;
                                    } else {
                                        $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                                    }
                                }
                            }

                        } else {
                            if ($DragonBetAmount==0 && $TigerBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } elseif ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                                $winning = TIE;
                            } else {
                                $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                            }
                        }

                        if ($winning==TIE) {
                            $number = rand(2, 10);
                            $card_dragon = 'BP'.$number;
                            $card_tiger = 'RP'.$number;

                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_dragon);
                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_tiger);
                        } else {
                            do {
                                $limit = 2;
                                $cards = $this->DragonTiger_model->GetCards($limit);
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

                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_dragon);
                            $this->DragonTiger_model->CreateMap($game_data[0]->id, $card_tiger);
                        }

                        // Give winning Amount to user
                        $bets = $this->DragonTiger_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                if ($winning==TIE) {
                                    $amount = $value->amount*TIE_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->DragonTiger_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*DRAGON_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->DragonTiger_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(DT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->DragonTiger_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
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

    public function rummy_card_points($cards, $joker='')
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

    public function jackpot()
    {
        $room_data = $this->Jackpot_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Jackpot_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->Jackpot_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalBetAmount = 0;
                        $TotalWinningAmount = 0;
                        $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        if ($min!='SET') {
                            $TotalWinningAmount = 0;
                            $TotalBetAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id);
                            $HighCardAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, HIGH_CARD);
                            $PairAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, PAIR);
                            $ColorAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, COLOR);
                            $SequenceAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, SEQUENCE);
                            $PureSequenceAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, PURE_SEQUENCE);

                            //1=High Card, 2=Pair, 3=Color, 4=Sequence, 5=Pure Sequence, 6=Set
                            $setting = $this->Setting_model->Setting();
                            $random = $setting->jackpot_random;
                            if ($random==1) {
                                $arr = ['HIGH_CARD','PAIR','COLOR','SEQUENCE','PURE_SEQUENCE'];
                                $min = $arr[array_rand($arr)];
                            } else {
                                $arr['HIGH_CARD'] = $HighCardAmount*HIGH_CARD_MULTIPLY;
                                $arr['PAIR'] = $PairAmount*PAIR_MULTIPLY;
                                $arr['COLOR'] = $ColorAmount*COLOR_MULTIPLY;
                                $arr['SEQUENCE'] = $SequenceAmount*SEQUENCE_MULTIPLY;
                                $arr['PURE_SEQUENCE'] = $PureSequenceAmount*PURE_SEQUENCE_MULTIPLY;
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[0];

                                $rand_array = [];
                                if ($arr[$min_arr[0]]==$arr['HIGH_CARD']) {
                                    $rand_array[] = 'HIGH_CARD';
                                }
                                if ($arr[$min_arr[0]]==$arr['PAIR']) {
                                    $rand_array[] = 'PAIR';
                                }
                                if ($arr[$min_arr[0]]==$arr['COLOR']) {
                                    $rand_array[] = 'COLOR';
                                }
                                if ($arr[$min_arr[0]]==$arr['SEQUENCE']) {
                                    $rand_array[] = 'SEQUENCE';
                                }
                                if ($arr[$min_arr[0]]==$arr['PURE_SEQUENCE']) {
                                    $rand_array[] = 'PURE_SEQUENCE';
                                }

                                if (!empty($rand_array)) {
                                    $min = $rand_array[array_rand($rand_array)];
                                }
                            }
                        }

                        $multiply = 0;
                        $color_arr = array('BP','BL','RS','RP');
                        $number_arr = array('A','2','3','4','5','6','7','8','9','10','J','Q','K');

                        switch ($min) {
                            case 'HIGH_CARD':
                                $high = rand(1, 10);
                                switch ($high) {
                                    case 1:
                                        $card1 = 'BPA';
                                        $card2 = 'RS8';
                                        $card3 = 'BL3';
                                        break;

                                    case 2:
                                        $card1 = 'BPK';
                                        $card2 = 'RS7';
                                        $card3 = 'BL4';
                                        break;

                                    case 3:
                                        $card1 = 'BP9';
                                        $card2 = 'RS7';
                                        $card3 = 'BL2';
                                        break;

                                    case 4:
                                        $card1 = 'BPK';
                                        $card2 = 'RSA';
                                        $card3 = 'BLJ';
                                        break;

                                    case 5:
                                        $card1 = 'BP9';
                                        $card2 = 'RS5';
                                        $card3 = 'BL6';
                                        break;

                                    case 6:
                                        $card1 = 'BP3';
                                        $card2 = 'RS2';
                                        $card3 = 'BL8';
                                        break;

                                    case 7:
                                        $card1 = 'BP4';
                                        $card2 = 'RS5';
                                        $card3 = 'BL9';
                                        break;

                                    case 8:
                                        $card1 = 'BP3';
                                        $card2 = 'RS5';
                                        $card3 = 'BL6';
                                        break;

                                    case 9:
                                        $card1 = 'BPQ';
                                        $card2 = 'RSK';
                                        $card3 = 'BL8';
                                        break;

                                    case 10:
                                        $card1 = 'BP4';
                                        $card2 = 'RS6';
                                        $card3 = 'BL9';
                                        break;

                                    default:
                                        $card1 = 'BPA';
                                        $card2 = 'RS8';
                                        $card3 = 'BL3';
                                        break;
                                }

                                $winning = HIGH_CARD;
                                $multiply = HIGH_CARD_MULTIPLY;
                                break;

                            case 'PAIR':
                                $number_index = array_rand($number_arr, 2);
                                $number1 = $number_arr[$number_index[0]];
                                $number2 = $number_arr[$number_index[1]];

                                $card1 = 'BP'.$number1;
                                $card2 = 'RP'.$number1;
                                $card3 = 'BL'.$number2;
                                $winning = PAIR;
                                $multiply = PAIR_MULTIPLY;
                                break;

                            case 'COLOR':
                                $color_index = array_rand($color_arr);
                                $color = $color_arr[$color_index];

                                $card1 = $color.'A';
                                $card2 = $color.'5';
                                $card3 = $color.'7';
                                $winning = COLOR;
                                $multiply = COLOR_MULTIPLY;
                                break;

                            case 'SEQUENCE':
                                $number = rand(2, 7);

                                $card1 = 'RP'.$number;
                                $card2 = 'BL'.($number+1);
                                $card3 = 'BP'.($number+2);
                                $winning = SEQUENCE;
                                $multiply = SEQUENCE_MULTIPLY;
                                break;

                            case 'PURE_SEQUENCE':
                                $color_index = array_rand($color_arr);
                                $color = $color_arr[$color_index];

                                $number = rand(2, 7);
                                $card1 = $color.$number;
                                $card2 = $color.($number+1);
                                $card3 = $color.($number+2);
                                $winning = PURE_SEQUENCE;
                                $multiply = PURE_SEQUENCE_MULTIPLY;
                                break;

                            case 'SET':
                                $number_index = array_rand($number_arr);
                                $number = $number_arr[$number_index];
                                $card1 = 'BP'.$number;
                                $card2 = 'RP'.$number;
                                $card3 = 'BL'.$number;
                                $winning = SET;
                                $SetAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, SET);
                                $jackpot_coin = $this->Setting_model->Setting()->jackpot_coin;
                                $give_coins = round(0.2*$jackpot_coin);
                                $minus_jackpot_coin = '-'.$jackpot_coin;
                                $this->Setting_model->update_jackpot_amount($minus_jackpot_coin);
                                break;

                            default:
                                $card1 = 'BPA';
                                $card2 = 'RP7';
                                $card3 = 'BL4';
                                $winning = HIGH_CARD;
                                $multiply = HIGH_CARD_MULTIPLY;
                                break;
                        }

                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card1);
                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card2);
                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card3);

                        // Give winning Amount to user
                        $bets = $this->Jackpot_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                if ($winning==SET) {
                                    $winning_percent = round(($value->amount/$SetAmount)*100);
                                    $winning_amount = round(($winning_percent/100)*$give_coins);
                                    $TotalWinningAmount += $winning_amount;
                                    $this->Jackpot_model->MakeWinner($value->user_id, $value->id, $winning_amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*$multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Jackpot_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->Jackpot_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'jackpot_room_id');
                        if ($count>0) {
                            $this->Jackpot_model->Create($room->id);

                            echo 'Jackpot Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function jackpot_create_socket()
    {
        $room_data = $this->Jackpot_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Jackpot_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->Jackpot_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function jackpot_winner_socket()
    {
        $room_data = $this->Jackpot_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Jackpot_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalBetAmount = 0;
                        $TotalWinningAmount = 0;
                        $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        if ($min!='SET') {
                            $TotalWinningAmount = 0;
                            $TotalBetAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id);
                            $HighCardAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, HIGH_CARD);
                            $PairAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, PAIR);
                            $ColorAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, COLOR);
                            $SequenceAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, SEQUENCE);
                            $PureSequenceAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, PURE_SEQUENCE);

                            //1=High Card, 2=Pair, 3=Color, 4=Sequence, 5=Pure Sequence, 6=Set
                            $setting = $this->Setting_model->Setting();
                            $random = $setting->jackpot_random;
                            if ($random==1) {
                                $arr = ['HIGH_CARD','PAIR','COLOR','SEQUENCE','PURE_SEQUENCE'];
                                $min = $arr[array_rand($arr)];
                            } else if ($random==2) {
                                $admin_coin = $setting->admin_coin;
    
                                $option_arr = ['HIGH_CARD','PAIR','COLOR','SEQUENCE','PURE_SEQUENCE'];
                                $arr['HIGH_CARD'] = $HighCardAmount*HIGH_CARD_MULTIPLY;
                                $arr['PAIR'] = $PairAmount*PAIR_MULTIPLY;
                                $arr['COLOR'] = $ColorAmount*COLOR_MULTIPLY;
                                $arr['SEQUENCE'] = $SequenceAmount*SEQUENCE_MULTIPLY;
                                $arr['PURE_SEQUENCE'] = $PureSequenceAmount*PURE_SEQUENCE_MULTIPLY;
                                
                                shuffle($option_arr);
                                $min = "";
                                foreach ($option_arr as $k => $value) {
                                    if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                        $min = $value;
                                        break;
                                    }
                                }
    
                                if($min==""){
                                    $min_arr = array_keys($arr, min($arr));
                                    $min = $min_arr[array_rand($min_arr)];
                                }
    
                            } else {
                                $arr['HIGH_CARD'] = $HighCardAmount*HIGH_CARD_MULTIPLY;
                                $arr['PAIR'] = $PairAmount*PAIR_MULTIPLY;
                                $arr['COLOR'] = $ColorAmount*COLOR_MULTIPLY;
                                $arr['SEQUENCE'] = $SequenceAmount*SEQUENCE_MULTIPLY;
                                $arr['PURE_SEQUENCE'] = $PureSequenceAmount*PURE_SEQUENCE_MULTIPLY;
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];

                                $rand_array = [];
                                if ($arr[$min_arr[0]]==$arr['HIGH_CARD']) {
                                    $rand_array[] = 'HIGH_CARD';
                                }
                                if ($arr[$min_arr[0]]==$arr['PAIR']) {
                                    $rand_array[] = 'PAIR';
                                }
                                if ($arr[$min_arr[0]]==$arr['COLOR']) {
                                    $rand_array[] = 'COLOR';
                                }
                                if ($arr[$min_arr[0]]==$arr['SEQUENCE']) {
                                    $rand_array[] = 'SEQUENCE';
                                }
                                if ($arr[$min_arr[0]]==$arr['PURE_SEQUENCE']) {
                                    $rand_array[] = 'PURE_SEQUENCE';
                                }

                                if (!empty($rand_array)) {
                                    $min = $rand_array[array_rand($rand_array)];
                                }
                            }
                        }

                        $multiply = 0;
                        $color_arr = array('BP','BL','RS','RP');
                        $number_arr = array('A','2','3','4','5','6','7','8','9','10','J','Q','K');

                        switch ($min) {
                            case 'HIGH_CARD':
                                $high = rand(1, 10);
                                switch ($high) {
                                    case 1:
                                        $card1 = 'BPA';
                                        $card2 = 'RS8';
                                        $card3 = 'BL3';
                                        break;

                                    case 2:
                                        $card1 = 'BPK';
                                        $card2 = 'RS7';
                                        $card3 = 'BL4';
                                        break;

                                    case 3:
                                        $card1 = 'BP9';
                                        $card2 = 'RS7';
                                        $card3 = 'BL2';
                                        break;

                                    case 4:
                                        $card1 = 'BPK';
                                        $card2 = 'RSA';
                                        $card3 = 'BLJ';
                                        break;

                                    case 5:
                                        $card1 = 'BP9';
                                        $card2 = 'RS5';
                                        $card3 = 'BL6';
                                        break;

                                    case 6:
                                        $card1 = 'BP3';
                                        $card2 = 'RS2';
                                        $card3 = 'BL8';
                                        break;

                                    case 7:
                                        $card1 = 'BP4';
                                        $card2 = 'RS5';
                                        $card3 = 'BL9';
                                        break;

                                    case 8:
                                        $card1 = 'BP3';
                                        $card2 = 'RS5';
                                        $card3 = 'BL6';
                                        break;

                                    case 9:
                                        $card1 = 'BPQ';
                                        $card2 = 'RSK';
                                        $card3 = 'BL8';
                                        break;

                                    case 10:
                                        $card1 = 'BP4';
                                        $card2 = 'RS6';
                                        $card3 = 'BL9';
                                        break;

                                    default:
                                        $card1 = 'BPA';
                                        $card2 = 'RS8';
                                        $card3 = 'BL3';
                                        break;
                                }

                                $winning = HIGH_CARD;
                                $multiply = HIGH_CARD_MULTIPLY;
                                break;

                            case 'PAIR':
                                $number_index = array_rand($number_arr, 2);
                                $number1 = $number_arr[$number_index[0]];
                                $number2 = $number_arr[$number_index[1]];

                                $card1 = 'BP'.$number1;
                                $card2 = 'RP'.$number1;
                                $card3 = 'BL'.$number2;
                                $winning = PAIR;
                                $multiply = PAIR_MULTIPLY;
                                break;

                            case 'COLOR':
                                $color_index = array_rand($color_arr);
                                $color = $color_arr[$color_index];

                                $card1 = $color.'A';
                                $card2 = $color.'5';
                                $card3 = $color.'7';
                                $winning = COLOR;
                                $multiply = COLOR_MULTIPLY;
                                break;

                            case 'SEQUENCE':
                                $number = rand(2, 7);

                                $card1 = 'RP'.$number;
                                $card2 = 'BL'.($number+1);
                                $card3 = 'BP'.($number+2);
                                $winning = SEQUENCE;
                                $multiply = SEQUENCE_MULTIPLY;
                                break;

                            case 'PURE_SEQUENCE':
                                $color_index = array_rand($color_arr);
                                $color = $color_arr[$color_index];

                                $number = rand(2, 7);
                                $card1 = $color.$number;
                                $card2 = $color.($number+1);
                                $card3 = $color.($number+2);
                                $winning = PURE_SEQUENCE;
                                $multiply = PURE_SEQUENCE_MULTIPLY;
                                break;

                            case 'SET':
                                $number_index = array_rand($number_arr);
                                $number = $number_arr[$number_index];
                                $card1 = 'BP'.$number;
                                $card2 = 'RP'.$number;
                                $card3 = 'BL'.$number;
                                $winning = SET;
                                $SetAmount = $this->Jackpot_model->TotalBetAmount($game_data[0]->id, SET);
                                $jackpot_coin = $this->Setting_model->Setting()->jackpot_coin;
                                $give_coins = round(0.2*$jackpot_coin);
                                $minus_jackpot_coin = '-'.$jackpot_coin;
                                $this->Setting_model->update_jackpot_amount($minus_jackpot_coin);
                                break;

                            default:
                                $card1 = 'BPA';
                                $card2 = 'RP7';
                                $card3 = 'BL4';
                                $winning = HIGH_CARD;
                                $multiply = HIGH_CARD_MULTIPLY;
                                break;
                        }

                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card1);
                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card2);
                        $this->Jackpot_model->CreateMap($game_data[0]->id, $card3);

                        // Give winning Amount to user
                        $bets = $this->Jackpot_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                if ($winning==SET) {
                                    $winning_percent = round(($value->amount/$SetAmount)*100);
                                    $winning_amount = round(($winning_percent/100)*$give_coins);
                                    $TotalWinningAmount += $winning_amount;
                                    $this->Jackpot_model->MakeWinner($value->user_id, $value->id, $winning_amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*$multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Jackpot_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(JT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->Jackpot_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function red_black()
    {
        $room_data = $this->RedBlack_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->RedBlack_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->RedBlack_model->Create($room->id, $card);

                    echo 'First Red Black Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $setting = $this->Setting_model->Setting();
                        $random = $setting->red_black_random;
                        if ($random==1) {
                            $cards = $this->RedBlack_model->GetCards(6);

                            $card1 = $cards[0]->cards;
                            $card2 = $cards[1]->cards;
                            $card3 = $cards[2]->cards;
                            $card4 = $cards[3]->cards;
                            $card5 = $cards[4]->cards;
                            $card6 = $cards[5]->cards;
                        } else {
                            $RedAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_RED);
                            $BlackAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_BLACK);

                            $PairAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_PAIR);
                            $ColorAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_COLOR);
                            $SequenceAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_SEQUENCE);
                            $PureSequenceAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_PURE_SEQUENCE);
                            $SetAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_SET);

                            //1=High Card, 2=Pair, 3=Color, 4=Sequence, 5=Pure Sequence, 6=Set
                            // $total = $HighCardAmount+$PairAmount+$ColorAmount+$SequenceAmount+$PureSequenceAmount+$SetAmount;
                            $RedMultiplyAmount = $RedAmount*RB_RED_MULTIPLE;
                            $BlackMultiplyAmount = $BlackAmount*RB_BLACK_MULTIPLE;

                            $PairMultiplyAmount = $PairAmount*RB_PAIR_MULTIPLE;
                            $ColorMultiplyAmount = $ColorAmount*RB_COLOR_MULTIPLE;
                            $SequenceMultiplyAmount = $SequenceAmount*RB_SEQUENCE_MULTIPLE;
                            $PureSequenceMultiplyAmount = $PureSequenceAmount*RB_PURE_SEQUENCE_MULTIPLE;
                            $SetMultiplyAmount = $SetAmount*RB_SET_MULTIPLE;

                            $arr['R_PAIR'] = $RedMultiplyAmount+$PairMultiplyAmount;
                            $arr['R_COLOR'] = $RedMultiplyAmount+$ColorMultiplyAmount;
                            $arr['R_SEQUENCE'] = $RedMultiplyAmount+$SequenceMultiplyAmount;
                            $arr['R_PURE_SEQUENCE'] = $RedMultiplyAmount+$PureSequenceMultiplyAmount;
                            $arr['R_SET'] = $RedMultiplyAmount+$SetMultiplyAmount;

                            $arr['B_PAIR'] = $BlackMultiplyAmount+$PairMultiplyAmount;
                            $arr['B_COLOR'] = $BlackMultiplyAmount+$ColorMultiplyAmount;
                            $arr['B_SEQUENCE'] = $BlackMultiplyAmount+$SequenceMultiplyAmount;
                            $arr['B_PURE_SEQUENCE'] = $BlackMultiplyAmount+$PureSequenceMultiplyAmount;
                            $arr['B_SET'] = $BlackMultiplyAmount+$SetMultiplyAmount;

                            $arr = shuffle_assoc($arr);

                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];

                            $high = rand(1, 10);
                            $high_cards = array();
                            $big_cards = array();
                            $cards = array();
                            switch ($high) {
                                case 1:
                                    $high_cards[] = 'BPA';
                                    $high_cards[] = 'RS8';
                                    $high_cards[] = 'BL3';
                                    break;

                                case 2:
                                    $high_cards[] = 'BPK';
                                    $high_cards[] = 'RS7';
                                    $high_cards[] = 'BL4';
                                    break;

                                case 3:
                                    $high_cards[] = 'BP9';
                                    $high_cards[] = 'RS7';
                                    $high_cards[] = 'BL2';
                                    break;

                                case 4:
                                    $high_cards[] = 'BPK';
                                    $high_cards[] = 'RSA';
                                    $high_cards[] = 'BLJ';
                                    break;

                                case 5:
                                    $high_cards[] = 'BP9';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL6';
                                    break;

                                case 6:
                                    $high_cards[] = 'BP3';
                                    $high_cards[] = 'RS2';
                                    $high_cards[] = 'BL8';
                                    break;

                                case 7:
                                    $high_cards[] = 'BP4';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL9';
                                    break;

                                case 8:
                                    $high_cards[] = 'BP3';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL6';
                                    break;

                                case 9:
                                    $high_cards[] = 'BPQ';
                                    $high_cards[] = 'RSK';
                                    $high_cards[] = 'BL8';
                                    break;

                                case 10:
                                    $high_cards[] = 'BP4';
                                    $high_cards[] = 'RS6';
                                    $high_cards[] = 'BL9';
                                    break;

                                default:
                                    $high_cards[] = 'BPA';
                                    $high_cards[] = 'RS8';
                                    $high_cards[] = 'BL3';
                                    break;
                            }

                            $multiply = 0;
                            $color_arr = array('BP','BL','RS','RP');
                            $number_arr = array('A','2','3','4','5','6','7','8','9','10','J','Q','K');

                            switch ($min) {
                                case 'R_PAIR':
                                    $number_index = array_rand($number_arr, 2);
                                    $number1 = $number_arr[$number_index[0]];
                                    $number2 = $number_arr[$number_index[1]];

                                    $big_cards[] = 'BP'.$number1;
                                    $big_cards[] = 'RP'.$number1;
                                    $big_cards[] = 'BL'.$number2;
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_COLOR':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $big_cards[] = $color.'A';
                                    $big_cards[] = $color.'5';
                                    $big_cards[] = $color.'7';
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_SEQUENCE':
                                    $number = rand(2, 7);

                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.($number+1);
                                    $big_cards[] = 'BP'.($number+2);
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_PURE_SEQUENCE':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $number = rand(2, 7);
                                    $big_cards[] = $color.$number;
                                    $big_cards[] = $color.($number+1);
                                    $big_cards[] = $color.($number+2);
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_SET':
                                    $number_index = array_rand($number_arr);
                                    $number = $number_arr[$number_index];
                                    $big_cards[] = 'BP'.$number;
                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.$number;
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'B_PAIR':
                                    $number_index = array_rand($number_arr, 2);
                                    $number1 = $number_arr[$number_index[0]];
                                    $number2 = $number_arr[$number_index[1]];

                                    $big_cards[] = 'BP'.$number1;
                                    $big_cards[] = 'RP'.$number1;
                                    $big_cards[] = 'BL'.$number2;
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_COLOR':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $big_cards[] = $color.'A';
                                    $big_cards[] = $color.'5';
                                    $big_cards[] = $color.'7';
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_SEQUENCE':
                                    $number = rand(2, 7);

                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.($number+1);
                                    $big_cards[] = 'BP'.($number+2);
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_PURE_SEQUENCE':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $number = rand(2, 7);
                                    $big_cards[] = $color.$number;
                                    $big_cards[] = $color.($number+1);
                                    $big_cards[] = $color.($number+2);
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_SET':
                                    $number_index = array_rand($number_arr);
                                    $number = $number_arr[$number_index];
                                    $big_cards[] = 'BP'.$number;
                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.$number;
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                default:
                                    $big_cards[] = 'BPA';
                                    $big_cards[] = 'RP7';
                                    $big_cards[] = 'BL4';
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;
                            }
                            $card1 = $cards[0];
                            $card2 = $cards[1];
                            $card3 = $cards[2];
                            $card4 = $cards[3];
                            $card5 = $cards[4];
                            $card6 = $cards[5];
                        }

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id);

                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card1);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card2);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card3);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card4);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card5);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card6);

                        $redPoint = $this->RedBlack_model->CardValue($card1, $card2, $card3);
                        $blackPoint = $this->RedBlack_model->CardValue($card4, $card5, $card6);
                        $winningPosition = $this->RedBlack_model->getWinnerPosition($redPoint, $blackPoint);
                        $winning = ($winningPosition==0) ? RB_RED : RB_BLACK;

                        $multiply = ($winning==RB_RED) ? RB_RED_MULTIPLE : RB_BLACK_MULTIPLE;
                        // Give winning Amount to user
                        $bets = $this->RedBlack_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->RedBlack_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $winning_rule = ($winning==RB_RED) ? $redPoint[0] : $blackPoint[0];

                        if ($winning_rule>0) {
                            switch ($winning_rule) {
                                case (RB_PAIR-1):
                                    $multiply_rule = RB_PAIR_MULTIPLE;
                                    break;

                                case (RB_COLOR-1):
                                    $multiply_rule = RB_COLOR_MULTIPLE;
                                    break;

                                case (RB_SEQUENCE-1):
                                    $multiply_rule = RB_SEQUENCE_MULTIPLE;
                                    break;

                                case (RB_PURE_SEQUENCE-1):
                                    $multiply_rule = RB_PURE_SEQUENCE_MULTIPLE;
                                    break;

                                case (RB_SET-1):
                                    $multiply_rule = RB_SET_MULTIPLE;
                                    break;

                                default:
                                    $multiply_rule = 0;
                                    break;
                            }
                            $bets = $this->RedBlack_model->ViewBet("", $game_data[0]->id, $winning_rule+1);
                            if ($bets && $multiply_rule>0) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$multiply_rule;
                                    $TotalWinningAmount += $amount;
                                    $this->RedBlack_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['winning_rule'] = $winning_rule;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->RedBlack_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'red_black_id');
                        if ($count>0) {
                            $this->RedBlack_model->Create($room->id);

                            echo 'Red Black Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function red_black_create_socket()
    {
        $room_data = $this->RedBlack_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->RedBlack_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->RedBlack_model->Create($room->id, $card);

                    echo 'First Red Black Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function red_black_winner_socket()
    {
        $room_data = $this->RedBlack_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->RedBlack_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $setting = $this->Setting_model->Setting();
                        $random = $setting->red_black_random;
                        if ($random==1) {
                            $cards = $this->RedBlack_model->GetCards(6);

                            $card1 = $cards[0]->cards;
                            $card2 = $cards[1]->cards;
                            $card3 = $cards[2]->cards;
                            $card4 = $cards[3]->cards;
                            $card5 = $cards[4]->cards;
                            $card6 = $cards[5]->cards;
                        } else {

                            $RedAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_RED);
                            $BlackAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_BLACK);

                            $PairAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_PAIR);
                            $ColorAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_COLOR);
                            $SequenceAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_SEQUENCE);
                            $PureSequenceAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_PURE_SEQUENCE);
                            $SetAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id, RB_SET);

                            //1=High Card, 2=Pair, 3=Color, 4=Sequence, 5=Pure Sequence, 6=Set
                            // $total = $HighCardAmount+$PairAmount+$ColorAmount+$SequenceAmount+$PureSequenceAmount+$SetAmount;
                            $RedMultiplyAmount = $RedAmount*RB_RED_MULTIPLE;
                            $BlackMultiplyAmount = $BlackAmount*RB_BLACK_MULTIPLE;

                            $PairMultiplyAmount = $PairAmount*RB_PAIR_MULTIPLE;
                            $ColorMultiplyAmount = $ColorAmount*RB_COLOR_MULTIPLE;
                            $SequenceMultiplyAmount = $SequenceAmount*RB_SEQUENCE_MULTIPLE;
                            $PureSequenceMultiplyAmount = $PureSequenceAmount*RB_PURE_SEQUENCE_MULTIPLE;
                            $SetMultiplyAmount = $SetAmount*RB_SET_MULTIPLE;

                            $arr['R_PAIR'] = $RedMultiplyAmount+$PairMultiplyAmount;
                            $arr['R_COLOR'] = $RedMultiplyAmount+$ColorMultiplyAmount;
                            $arr['R_SEQUENCE'] = $RedMultiplyAmount+$SequenceMultiplyAmount;
                            $arr['R_PURE_SEQUENCE'] = $RedMultiplyAmount+$PureSequenceMultiplyAmount;
                            $arr['R_SET'] = $RedMultiplyAmount+$SetMultiplyAmount;

                            $arr['B_PAIR'] = $BlackMultiplyAmount+$PairMultiplyAmount;
                            $arr['B_COLOR'] = $BlackMultiplyAmount+$ColorMultiplyAmount;
                            $arr['B_SEQUENCE'] = $BlackMultiplyAmount+$SequenceMultiplyAmount;
                            $arr['B_PURE_SEQUENCE'] = $BlackMultiplyAmount+$PureSequenceMultiplyAmount;
                            $arr['B_SET'] = $BlackMultiplyAmount+$SetMultiplyAmount;

                            $arr = shuffle_assoc($arr);

                            // if($random==2){

                            //     $admin_coin = $setting->admin_coin;
    
                            // else {

                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[0];
                            // }

                            $high = rand(1, 10);
                            $high_cards = array();
                            $big_cards = array();
                            $cards = array();
                            switch ($high) {
                                case 1:
                                    $high_cards[] = 'BPA';
                                    $high_cards[] = 'RS8';
                                    $high_cards[] = 'BL3';
                                    break;

                                case 2:
                                    $high_cards[] = 'BPK';
                                    $high_cards[] = 'RS7';
                                    $high_cards[] = 'BL4';
                                    break;

                                case 3:
                                    $high_cards[] = 'BP9';
                                    $high_cards[] = 'RS7';
                                    $high_cards[] = 'BL2';
                                    break;

                                case 4:
                                    $high_cards[] = 'BPK';
                                    $high_cards[] = 'RSA';
                                    $high_cards[] = 'BLJ';
                                    break;

                                case 5:
                                    $high_cards[] = 'BP9';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL6';
                                    break;

                                case 6:
                                    $high_cards[] = 'BP3';
                                    $high_cards[] = 'RS2';
                                    $high_cards[] = 'BL8';
                                    break;

                                case 7:
                                    $high_cards[] = 'BP4';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL9';
                                    break;

                                case 8:
                                    $high_cards[] = 'BP3';
                                    $high_cards[] = 'RS5';
                                    $high_cards[] = 'BL6';
                                    break;

                                case 9:
                                    $high_cards[] = 'BPQ';
                                    $high_cards[] = 'RSK';
                                    $high_cards[] = 'BL8';
                                    break;

                                case 10:
                                    $high_cards[] = 'BP4';
                                    $high_cards[] = 'RS6';
                                    $high_cards[] = 'BL9';
                                    break;

                                default:
                                    $high_cards[] = 'BPA';
                                    $high_cards[] = 'RS8';
                                    $high_cards[] = 'BL3';
                                    break;
                            }

                            $multiply = 0;
                            $color_arr = array('BP','BL','RS','RP');
                            $number_arr = array('A','2','3','4','5','6','7','8','9','10','J','Q','K');

                            switch ($min) {
                                case 'R_PAIR':
                                    $number_index = array_rand($number_arr, 2);
                                    $number1 = $number_arr[$number_index[0]];
                                    $number2 = $number_arr[$number_index[1]];

                                    $big_cards[] = 'BP'.$number1;
                                    $big_cards[] = 'RP'.$number1;
                                    $big_cards[] = 'BL'.$number2;
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_COLOR':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $big_cards[] = $color.'A';
                                    $big_cards[] = $color.'5';
                                    $big_cards[] = $color.'7';
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_SEQUENCE':
                                    $number = rand(2, 7);

                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.($number+1);
                                    $big_cards[] = 'BP'.($number+2);
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_PURE_SEQUENCE':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $number = rand(2, 7);
                                    $big_cards[] = $color.$number;
                                    $big_cards[] = $color.($number+1);
                                    $big_cards[] = $color.($number+2);
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'R_SET':
                                    $number_index = array_rand($number_arr);
                                    $number = $number_arr[$number_index];
                                    $big_cards[] = 'BP'.$number;
                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.$number;
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;

                                case 'B_PAIR':
                                    $number_index = array_rand($number_arr, 2);
                                    $number1 = $number_arr[$number_index[0]];
                                    $number2 = $number_arr[$number_index[1]];

                                    $big_cards[] = 'BP'.$number1;
                                    $big_cards[] = 'RP'.$number1;
                                    $big_cards[] = 'BL'.$number2;
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_COLOR':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $big_cards[] = $color.'A';
                                    $big_cards[] = $color.'5';
                                    $big_cards[] = $color.'7';
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_SEQUENCE':
                                    $number = rand(2, 7);

                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.($number+1);
                                    $big_cards[] = 'BP'.($number+2);
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_PURE_SEQUENCE':
                                    $color_index = array_rand($color_arr);
                                    $color = $color_arr[$color_index];

                                    $number = rand(2, 7);
                                    $big_cards[] = $color.$number;
                                    $big_cards[] = $color.($number+1);
                                    $big_cards[] = $color.($number+2);
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                case 'B_SET':
                                    $number_index = array_rand($number_arr);
                                    $number = $number_arr[$number_index];
                                    $big_cards[] = 'BP'.$number;
                                    $big_cards[] = 'RP'.$number;
                                    $big_cards[] = 'BL'.$number;
                                    $cards = array_merge($high_cards, $big_cards);
                                    break;

                                default:
                                    $big_cards[] = 'BPA';
                                    $big_cards[] = 'RP7';
                                    $big_cards[] = 'BL4';
                                    $cards = array_merge($big_cards, $high_cards);
                                    break;
                            }
                            $card1 = $cards[0];
                            $card2 = $cards[1];
                            $card3 = $cards[2];
                            $card4 = $cards[3];
                            $card5 = $cards[4];
                            $card6 = $cards[5];
                        }

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->RedBlack_model->TotalBetAmount($game_data[0]->id);

                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card1);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card2);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card3);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card4);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card5);
                        $this->RedBlack_model->CreateMap($game_data[0]->id, $card6);

                        $redPoint = $this->RedBlack_model->CardValue($card1, $card2, $card3);
                        $blackPoint = $this->RedBlack_model->CardValue($card4, $card5, $card6);
                        $winningPosition = $this->RedBlack_model->getWinnerPosition($redPoint, $blackPoint);
                        $winning = ($winningPosition==0) ? RB_RED : RB_BLACK;

                        $multiply = ($winning==RB_RED) ? RB_RED_MULTIPLE : RB_BLACK_MULTIPLE;
                        // Give winning Amount to user
                        $bets = $this->RedBlack_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->RedBlack_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $winning_rule = ($winning==RB_RED) ? $redPoint[0] : $blackPoint[0];

                        if ($winning_rule>0) {
                            switch ($winning_rule) {
                                case (RB_PAIR-1):
                                    $multiply_rule = RB_PAIR_MULTIPLE;
                                    break;

                                case (RB_COLOR-1):
                                    $multiply_rule = RB_COLOR_MULTIPLE;
                                    break;

                                case (RB_SEQUENCE-1):
                                    $multiply_rule = RB_SEQUENCE_MULTIPLE;
                                    break;

                                case (RB_PURE_SEQUENCE-1):
                                    $multiply_rule = RB_PURE_SEQUENCE_MULTIPLE;
                                    break;

                                case (RB_SET-1):
                                    $multiply_rule = RB_SET_MULTIPLE;
                                    break;

                                default:
                                    $multiply_rule = 0;
                                    break;
                            }
                            $bets = $this->RedBlack_model->ViewBet("", $game_data[0]->id, $winning_rule+1);
                            if ($bets && $multiply_rule>0) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$multiply_rule;
                                    $TotalWinningAmount += $amount;
                                    $this->RedBlack_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['winning_rule'] = $winning_rule;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(RB,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->RedBlack_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function seven_up()
    {
        $room_data = $this->SevenUp_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->SevenUp_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->SevenUp_model->Create($room->id, $card);

                    echo 'First Seven Up Game Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id);

                        $UpBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, UP);
                        $DownBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, DOWN);
                        $TieBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, TIE);
                        // $winning = ($UpBetAmount>$DownBetAmount) ? DOWN : UP; //0=Down,1=Up
                        $setting = $this->Setting_model->Setting();
                        $random = $setting->up_down_random;
                        if ($random==1) {
                            $winning = RAND(0, 2);
                        } else {
                            if ($DownBetAmount==0 && $UpBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } elseif ($DownBetAmount>$TieBetAmount && $UpBetAmount>$TieBetAmount) {
                                $winning = TIE;
                            } else {
                                $winning = ($UpBetAmount>$DownBetAmount) ? DOWN : UP; //0=Dragon,1=Tiger
                            }
                        }

                        $winning_number = ($winning==DOWN) ? rand(2, 6) : (($winning==UP) ? rand(8, 12) : 7);

                        $this->SevenUp_model->CreateMap($game_data[0]->id, $winning_number);

                        // Give winning Amount to user
                        $bets = $this->SevenUp_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                // $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $value->amount*2, $comission, $game_data[0]->id);
                                if ($winning==TIE) {
                                    $amount = $value->amount*UP_DOWN_TIE_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*UP_DOWN_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->SevenUp_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'seven_up_room_id');
                        if ($count>0) {
                            $this->SevenUp_model->Create($room->id);

                            echo 'Seven Up Game Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function seven_up_down_create_socket()
    {
        $room_data = $this->SevenUp_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->SevenUp_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->SevenUp_model->Create($room->id, $card);

                    echo 'First Seven Up Game Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function seven_up_down_winner_socket()
    {
        $room_data = $this->SevenUp_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->SevenUp_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id);

                        $UpBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, UP);
                        $DownBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, DOWN);
                        $TieBetAmount = $this->SevenUp_model->TotalBetAmount($game_data[0]->id, TIE);
                        // $winning = ($UpBetAmount>$DownBetAmount) ? DOWN : UP; //0=Down,1=Up
                        $setting = $this->Setting_model->Setting();
                        $random = $setting->up_down_random;
                        if ($random==1) {
                            $winning = RAND(0, 2);
                        }else if ($random==2) {
                            
                            $admin_coin = $setting->admin_coin;
                            if ($UpBetAmount==0 && $DownBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } else {
                                $option_arr = [0,1,2];
                                shuffle($option_arr);
                                $winning = "";
                                foreach ($option_arr as $k => $value) {

                                    switch ($value) {
                                        case UP:
                                            if($UpBetAmount>0 && $admin_coin>=$UpBetAmount){
                                                $winning = UP;
                                            }
                                            break;
                                        case DOWN:
                                            if($DownBetAmount>0 && $admin_coin>=$DownBetAmount){
                                                $winning = DOWN;
                                            }
                                            break;
                                        case TIE:
                                            if($TieBetAmount>0 && $admin_coin>=$TieBetAmount){
                                                $winning = TIE;
                                            }
                                            break;
                                    }

                                    if($winning!=""){
                                        break;
                                    }
                                }

                                if($winning==""){
                                    if ($DownBetAmount>$TieBetAmount && $UpBetAmount>$TieBetAmount) {
                                        $winning = TIE;
                                    } else {
                                        $winning = ($UpBetAmount>$DownBetAmount) ? DOWN : UP; //0=Dragon,1=Tiger
                                    }
                                }
                            }

                        } else {
                            if ($DownBetAmount==0 && $UpBetAmount==0 && $TieBetAmount==0) {
                                $winning = RAND(0, 2);
                            } elseif ($DownBetAmount>$TieBetAmount && $UpBetAmount>$TieBetAmount) {
                                $winning = TIE;
                            } else {
                                $winning = ($UpBetAmount>$DownBetAmount) ? DOWN : UP; //0=Dragon,1=Tiger
                            }
                        }

                        $winning_number = ($winning==DOWN) ? rand(2, 6) : (($winning==UP) ? rand(8, 12) : 7);

                        $this->SevenUp_model->CreateMap($game_data[0]->id, $winning_number);

                        // Give winning Amount to user
                        $bets = $this->SevenUp_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                // $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $value->amount*2, $comission, $game_data[0]->id);
                                if ($winning==TIE) {
                                    $amount = $value->amount*UP_DOWN_TIE_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                } else {
                                    $amount = $value->amount*UP_DOWN_MULTIPLY;
                                    $TotalWinningAmount += $amount;
                                    $this->SevenUp_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(SU,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->SevenUp_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function car_roulette()
    {
        $room_data = $this->CarRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->CarRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->CarRoulette_model->Create($room->id, $card);

                    echo 'First Car Roulette Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        // $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        // if ($min!='SET') {

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id);

                        $ToyotaAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, TOYOTA);
                        $MahindraAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, MAHINDRA);
                        $AudiAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, AUDI);
                        $BmwAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, BMW);
                        $MercedesAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, MERCEDES);
                        $PorscheAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, PORSCHE);
                        $LamborghiniAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, LAMBORGHINI);
                        $FerrariAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, FERRARI);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->car_roulette_random;
                        if ($random==1) {
                            $arr = ['TOYOTA','MAHINDRA','AUDI','BMW','MERCEDES','PORSCHE','LAMBORGHINI','FERRARI'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr['TOYOTA'] = $ToyotaAmount*TOYOTA_MULTIPLY;
                            $arr['MAHINDRA'] = $MahindraAmount*MAHINDRA_MULTIPLY;
                            $arr['AUDI'] = $AudiAmount*AUDI_MULTIPLY;
                            $arr['BMW'] = $BmwAmount*BMW_MULTIPLY;
                            $arr['MERCEDES'] = $MercedesAmount*MERCEDES_MULTIPLY;
                            $arr['PORSCHE'] = $PorscheAmount*PORSCHE_MULTIPLY;
                            $arr['LAMBORGHINI'] = $LamborghiniAmount*LAMBORGHINI_MULTIPLY;
                            $arr['FERRARI'] = $FerrariAmount*FERRARI_MULTIPLY;
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                            // }
                        }
                        $multiply = 0;

                        switch ($min) {
                            case 'TOYOTA':
                                $winning = TOYOTA;
                                $multiply = TOYOTA_MULTIPLY;
                                break;
                            case 'MAHINDRA':
                                $winning = MAHINDRA;
                                $multiply = MAHINDRA_MULTIPLY;
                                break;
                            case 'AUDI':
                                $winning = AUDI;
                                $multiply = AUDI_MULTIPLY;
                                break;
                            case 'BMW':
                                $winning = BMW;
                                $multiply = BMW_MULTIPLY;
                                break;
                            case 'MERCEDES':
                                $winning = MERCEDES;
                                $multiply = MERCEDES_MULTIPLY;
                                break;
                            case 'PORSCHE':
                                $winning = PORSCHE;
                                $multiply = PORSCHE_MULTIPLY;
                                break;
                            case 'LAMBORGHINI':
                                $winning = LAMBORGHINI;
                                $multiply = LAMBORGHINI_MULTIPLY;
                                break;
                            case 'FERRARI':
                                $winning = FERRARI;
                                $multiply = FERRARI_MULTIPLY;
                                break;

                            default:
                                $winning = TOYOTA;
                                $multiply = TOYOTA_MULTIPLY;
                                break;
                        }

                        $this->CarRoulette_model->CreateMap($game_data[0]->id, $winning);

                        // Give winning Amount to user
                        $bets = $this->CarRoulette_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->CarRoulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->CarRoulette_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'car_roulette_room_id');
                        if ($count>0) {
                            $this->CarRoulette_model->Create($room->id);

                            echo 'Car Roulette Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function car_roulette_create_socket()
    {
        $room_data = $this->CarRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->CarRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->CarRoulette_model->Create($room->id, $card);

                    echo 'Car Roulette Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function car_roulette_winner_socket()
    {
        $room_data = $this->CarRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->CarRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        // $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        // if ($min!='SET') {

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id);

                        $ToyotaAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, TOYOTA);
                        $MahindraAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, MAHINDRA);
                        $AudiAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, AUDI);
                        $BmwAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, BMW);
                        $MercedesAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, MERCEDES);
                        $PorscheAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, PORSCHE);
                        $LamborghiniAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, LAMBORGHINI);
                        $FerrariAmount = $this->CarRoulette_model->TotalBetAmount($game_data[0]->id, FERRARI);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->car_roulette_random;
                        if ($random==1) {
                            $arr = ['TOYOTA','MAHINDRA','AUDI','BMW','MERCEDES','PORSCHE','LAMBORGHINI','FERRARI'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random==2) {
                            $admin_coin = $setting->admin_coin;

                            $option_arr = ['TOYOTA','MAHINDRA','AUDI','BMW','MERCEDES','PORSCHE','LAMBORGHINI','FERRARI'];
                            $arr['TOYOTA'] = $ToyotaAmount*TOYOTA_MULTIPLY;
                            $arr['MAHINDRA'] = $MahindraAmount*MAHINDRA_MULTIPLY;
                            $arr['AUDI'] = $AudiAmount*AUDI_MULTIPLY;
                            $arr['BMW'] = $BmwAmount*BMW_MULTIPLY;
                            $arr['MERCEDES'] = $MercedesAmount*MERCEDES_MULTIPLY;
                            $arr['PORSCHE'] = $PorscheAmount*PORSCHE_MULTIPLY;
                            $arr['LAMBORGHINI'] = $LamborghiniAmount*LAMBORGHINI_MULTIPLY;
                            $arr['FERRARI'] = $FerrariAmount*FERRARI_MULTIPLY;
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }

                        } else {
                            $arr['TOYOTA'] = $ToyotaAmount*TOYOTA_MULTIPLY;
                            $arr['MAHINDRA'] = $MahindraAmount*MAHINDRA_MULTIPLY;
                            $arr['AUDI'] = $AudiAmount*AUDI_MULTIPLY;
                            $arr['BMW'] = $BmwAmount*BMW_MULTIPLY;
                            $arr['MERCEDES'] = $MercedesAmount*MERCEDES_MULTIPLY;
                            $arr['PORSCHE'] = $PorscheAmount*PORSCHE_MULTIPLY;
                            $arr['LAMBORGHINI'] = $LamborghiniAmount*LAMBORGHINI_MULTIPLY;
                            $arr['FERRARI'] = $FerrariAmount*FERRARI_MULTIPLY;
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                            // }
                        }
                        $multiply = 0;

                        switch ($min) {
                            case 'TOYOTA':
                                $winning = TOYOTA;
                                $multiply = TOYOTA_MULTIPLY;
                                break;
                            case 'MAHINDRA':
                                $winning = MAHINDRA;
                                $multiply = MAHINDRA_MULTIPLY;
                                break;
                            case 'AUDI':
                                $winning = AUDI;
                                $multiply = AUDI_MULTIPLY;
                                break;
                            case 'BMW':
                                $winning = BMW;
                                $multiply = BMW_MULTIPLY;
                                break;
                            case 'MERCEDES':
                                $winning = MERCEDES;
                                $multiply = MERCEDES_MULTIPLY;
                                break;
                            case 'PORSCHE':
                                $winning = PORSCHE;
                                $multiply = PORSCHE_MULTIPLY;
                                break;
                            case 'LAMBORGHINI':
                                $winning = LAMBORGHINI;
                                $multiply = LAMBORGHINI_MULTIPLY;
                                break;
                            case 'FERRARI':
                                $winning = FERRARI;
                                $multiply = FERRARI_MULTIPLY;
                                break;

                            default:
                                $winning = TOYOTA;
                                $multiply = TOYOTA_MULTIPLY;
                                break;
                        }

                        $this->CarRoulette_model->CreateMap($game_data[0]->id, $winning);

                        // Give winning Amount to user
                        $bets = $this->CarRoulette_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->CarRoulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(CR,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->CarRoulette_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function animal_roulette()
    {
        $room_data = $this->AnimalRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnimalRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->AnimalRoulette_model->Create($room->id, $card);

                    echo 'First Animal Roulette Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        // $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        // if ($min!='SET') {

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id);

                        $TigerAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, TIGER);
                        $SnakeAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, SNAKE);
                        $SharkAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, SHARK);
                        $FoxAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, FOX);
                        $CheetahAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, CHEETAH);
                        $BearAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, BEAR);
                        $WhaleAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, WHALE);
                        $LionAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, LION);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->animal_roulette_random;
                        if ($random==1) {
                            $arr = ['TIGER','SNAKE','SHARK','FOX','CHEETAH','BEAR','WHALE','LION'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr['TIGER'] = $TigerAmount*TIGER_MULTIPLY;
                            $arr['SNAKE'] = $SnakeAmount*SNAKE_MULTIPLY;
                            $arr['SHARK'] = $SharkAmount*SHARK_MULTIPLY;
                            $arr['FOX'] = $FoxAmount*FOX_MULTIPLY;
                            $arr['CHEETAH'] = $CheetahAmount*CHEETAH_MULTIPLY;
                            $arr['BEAR'] = $BearAmount*BEAR_MULTIPLY;
                            $arr['WHALE'] = $WhaleAmount*WHALE_MULTIPLY;
                            $arr['LION'] = $LionAmount*LION_MULTIPLY;
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        }
                        // }

                        $multiply = 0;

                        switch ($min) {
                            case 'TIGER':
                                $winning = TIGER;
                                $multiply = TIGER_MULTIPLY;
                                break;
                            case 'SNAKE':
                                $winning = SNAKE;
                                $multiply = SNAKE_MULTIPLY;
                                break;
                            case 'SHARK':
                                $winning = SHARK;
                                $multiply = SHARK_MULTIPLY;
                                break;
                            case 'FOX':
                                $winning = FOX;
                                $multiply = FOX_MULTIPLY;
                                break;
                            case 'CHEETAH':
                                $winning = CHEETAH;
                                $multiply = CHEETAH_MULTIPLY;
                                break;
                            case 'BEAR':
                                $winning = BEAR;
                                $multiply = BEAR_MULTIPLY;
                                break;
                            case 'WHALE':
                                $winning = WHALE;
                                $multiply = WHALE_MULTIPLY;
                                break;
                            case 'LION':
                                $winning = LION;
                                $multiply = LION_MULTIPLY;
                                break;

                            default:
                                $winning = TIGER;
                                $multiply = TIGER_MULTIPLY;
                                break;
                        }

                        $this->AnimalRoulette_model->CreateMap($game_data[0]->id, $winning);

                        // Give winning Amount to user
                        $bets = $this->AnimalRoulette_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnimalRoulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->AnimalRoulette_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'animal_roulette_room_id');
                        if ($count>0) {
                            $this->AnimalRoulette_model->Create($room->id);

                            echo 'Animal Roulette Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function animal_roulette_create_socket()
    {
        $room_data = $this->AnimalRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnimalRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->AnimalRoulette_model->Create($room->id, $card);

                    echo 'First Animal Roulette Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function animal_roulette_winner_socket()
    {
        $room_data = $this->AnimalRoulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnimalRoulette_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        // $min = ($this->Setting_model->Setting()->jackpot_status==1) ? 'SET' : '';
                        // if ($min!='SET') {

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id);

                        $TigerAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, TIGER);
                        $SnakeAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, SNAKE);
                        $SharkAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, SHARK);
                        $FoxAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, FOX);
                        $CheetahAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, CHEETAH);
                        $BearAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, BEAR);
                        $WhaleAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, WHALE);
                        $LionAmount = $this->AnimalRoulette_model->TotalBetAmount($game_data[0]->id, LION);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->animal_roulette_random;
                        if ($random==1) {
                            $arr = ['TIGER','SNAKE','SHARK','FOX','CHEETAH','BEAR','WHALE','LION'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random==2) {
                            $admin_coin = $setting->admin_coin;

                            $option_arr = ['TIGER','SNAKE','SHARK','FOX','CHEETAH','BEAR','WHALE','LION'];
                            $arr['TIGER'] = $TigerAmount*TIGER_MULTIPLY;
                            $arr['SNAKE'] = $SnakeAmount*SNAKE_MULTIPLY;
                            $arr['SHARK'] = $SharkAmount*SHARK_MULTIPLY;
                            $arr['FOX'] = $FoxAmount*FOX_MULTIPLY;
                            $arr['CHEETAH'] = $CheetahAmount*CHEETAH_MULTIPLY;
                            $arr['BEAR'] = $BearAmount*BEAR_MULTIPLY;
                            $arr['WHALE'] = $WhaleAmount*WHALE_MULTIPLY;
                            $arr['LION'] = $LionAmount*LION_MULTIPLY;
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }
                            /*foreach ($option_arr as $k => $value) {
                                switch ($value) {
                                    case "TIGER":
                                        if($TigerAmount>0 && $admin_coin>=$TigerAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "SNAKE":
                                        if($SnakeAmount>0 && $admin_coin>=$SnakeAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "SHARK":
                                        if($SharkAmount>0 && $admin_coin>=$SharkAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "FOX":
                                        if($FoxAmount>0 && $admin_coin>=$FoxAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "CHEETAH":
                                        if($CheetahAmount>0 && $admin_coin>=$CheetahAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "BEAR":
                                        if($BearAmount>0 && $admin_coin>=$BearAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "WHALE":
                                        if($WhaleAmount>0 && $admin_coin>=$WhaleAmount){
                                            $winning = $value;
                                        }
                                        break;
                                    case "LION":
                                        if($LionAmount>0 && $admin_coin>=$LionAmount){
                                            $winning = $value;
                                        }
                                        break;
                                }

                                if($winning!=""){
                                    break;
                                }
                            }*/

                        } else {
                            $arr['TIGER'] = $TigerAmount*TIGER_MULTIPLY;
                            $arr['SNAKE'] = $SnakeAmount*SNAKE_MULTIPLY;
                            $arr['SHARK'] = $SharkAmount*SHARK_MULTIPLY;
                            $arr['FOX'] = $FoxAmount*FOX_MULTIPLY;
                            $arr['CHEETAH'] = $CheetahAmount*CHEETAH_MULTIPLY;
                            $arr['BEAR'] = $BearAmount*BEAR_MULTIPLY;
                            $arr['WHALE'] = $WhaleAmount*WHALE_MULTIPLY;
                            $arr['LION'] = $LionAmount*LION_MULTIPLY;
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        }
                        // }

                        $multiply = 0;

                        switch ($min) {
                            case 'TIGER':
                                $winning = TIGER;
                                $multiply = TIGER_MULTIPLY;
                                break;
                            case 'SNAKE':
                                $winning = SNAKE;
                                $multiply = SNAKE_MULTIPLY;
                                break;
                            case 'SHARK':
                                $winning = SHARK;
                                $multiply = SHARK_MULTIPLY;
                                break;
                            case 'FOX':
                                $winning = FOX;
                                $multiply = FOX_MULTIPLY;
                                break;
                            case 'CHEETAH':
                                $winning = CHEETAH;
                                $multiply = CHEETAH_MULTIPLY;
                                break;
                            case 'BEAR':
                                $winning = BEAR;
                                $multiply = BEAR_MULTIPLY;
                                break;
                            case 'WHALE':
                                $winning = WHALE;
                                $multiply = WHALE_MULTIPLY;
                                break;
                            case 'LION':
                                $winning = LION;
                                $multiply = LION_MULTIPLY;
                                break;

                            default:
                                $winning = TIGER;
                                $multiply = TIGER_MULTIPLY;
                                break;
                        }

                        $this->AnimalRoulette_model->CreateMap($game_data[0]->id, $winning);

                        // Give winning Amount to user
                        $bets = $this->AnimalRoulette_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnimalRoulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(AR,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->AnimalRoulette_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_winner_socket()
    {
        $room_data = $this->ColorPrediction_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->ColorPrediction_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully' . PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status == 0) {
                    if ((strtotime($game_data[0]->added_date) + DRAGON_TIME_FOR_BET) <= time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_random;
                        $admin_coin = $setting->admin_coin;
                        if ($random == RANDOM) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random == 20) {
                            $option_arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }
                        $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction_model->ViewBet(
                            "",
                            $game_data[0]->id,
                            $number
                        );
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount * $number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction_model->MakeWinner(
                                    $value->user_id,
                                    $value->id,
                                    $amount,
                                    $comission,
                                    $game_data[0]->id
                                );
                              
                            }
                            echo "Winning Amount Given" . PHP_EOL;
                        } else {
                            echo "No Winning Bet Found" . PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color != '') {
                            $color_bets = $this->ColorPrediction_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount * $color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1 != '') {
                            $color_1_bets = $this->ColorPrediction_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount * $color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }
                        // Give winning Amount to Small Big user
                        if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction_model->MakeWinner(
                                        $value->user_id,
                                        $value->id,
                                        $amount,
                                        $comission,
                                        $game_data[0]->id
                                    );
                                   
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+' . DRAGON_TIME_FOR_START_NEW_GAME . ' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(CP,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->ColorPrediction_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start" . PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime) <= time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'color_prediction_room_id');
                        if ($count > 0) {
                            $this->ColorPrediction_model->Create($room->id);

                            echo 'Color Prediction Created Successfully' . PHP_EOL;
                        } else {
                            echo 'No Online User Found' . PHP_EOL;
                        }
                    } else {
                        echo "No Game to End" . PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available' . PHP_EOL;
        }
    }

    public function color_prediction_create_socket()
    {
        $room_data = $this->ColorPrediction_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->ColorPrediction_model->Create($room->id, $card);

                    echo 'First Color Prediction Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_1_min()
    {
        $room_data = $this->ColorPrediction1Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction1Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->ColorPrediction1Min_model->Create($room->id, $card);

                    echo 'Color Prediction Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+COLOR_1MIN_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_1_min_random;
                        if ($random == RANDOM) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }

                        $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;


                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction1Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color!='') {
                            $color_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount*$color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1!='') {
                            $color_1_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount*$color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Small Big user
                        if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.COLOR_1MIN_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->ColorPrediction1Min_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'color_prediction_1_min_room_id');
                        if ($count>0) {
                            $this->ColorPrediction1Min_model->Create($room->id);

                            echo 'Color Prediction Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_1_min_create_socket()
    {
        $room_data = $this->ColorPrediction1Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction1Min_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->ColorPrediction1Min_model->Create($room->id, $card);

                    echo 'Color Prediction Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_1_min_winner_socket()
    {
        $room_data = $this->ColorPrediction1Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction1Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+COLOR_1MIN_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction1Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_1_min_random;
                        $admin_coin = $setting->admin_coin;
                        if ($random==RANDOM) {
                            $arr = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random==LEAST) {
                           $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random == 20) {
                            $option_arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }

                       $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }


                        // echo $number.'hi';
                        $this->ColorPrediction1Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color!='') {
                            $color_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount*$color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1!='') {
                            $color_1_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount*$color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction1Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction1Min_model->MakeWinner(
                                        $value->user_id,
                                        $value->id,
                                        $amount,
                                        $comission,
                                        $game_data[0]->id
                                    );
                                     
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.COLOR_1MIN_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(CP1,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->ColorPrediction1Min_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_3_min()
    {
        $room_data = $this->ColorPrediction3Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction3Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->ColorPrediction3Min_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully' . PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status == 0) {
                    if ((strtotime($game_data[0]->added_date) + COLOR_3MIN_TIME_FOR_BET) <= time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_3_min_random;
                        if ($random == RANDOM) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }
                        $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction3Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount * $number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given" . PHP_EOL;
                        } else {
                            echo "No Winning Bet Found" . PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color != '') {
                            $color_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount * $color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1 != '') {
                            $color_1_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount * $color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }
                        // Give winning Amount to Small Big user
                        if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+' . COLOR_3MIN_TIME_FOR_START_NEW_GAME . ' seconds'));
                        $update_data['random'] = $random;
                        $this->ColorPrediction3Min_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start" . PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime) <= time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'color_prediction_3_min_room_id');
                        if ($count > 0) {
                            $this->ColorPrediction3Min_model->Create($room->id);

                            echo 'Color Prediction Created Successfully' . PHP_EOL;
                        } else {
                            echo 'No Online User Found' . PHP_EOL;
                        }
                    } else {
                        echo "No Game to End" . PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available' . PHP_EOL;
        }
    }

    public function color_prediction_3_min_create_socket()
    {
        $room_data = $this->ColorPrediction3Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction3Min_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->ColorPrediction3Min_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_3_min_winner_socket()
    {
        $room_data = $this->ColorPrediction3Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction3Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+COLOR_3MIN_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction3Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_3_min_random;
                        $admin_coin = $setting->admin_coin;
                        if ($random==RANDOM) {
                            $arr = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random==LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random == 20) {
                            $option_arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }

                         $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction3Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                               
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color!='') {
                            $color_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount*$color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1!='') {
                            $color_1_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount*$color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                         if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction3Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction3Min_model->MakeWinner(
                                        $value->user_id,
                                        $value->id,
                                        $amount,
                                        $comission,
                                        $game_data[0]->id
                                    );
                                   
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.COLOR_3MIN_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(CP3,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->ColorPrediction3Min_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_5_min()
    {
        $room_data = $this->ColorPrediction5Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction5Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->ColorPrediction5Min_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully' . PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status == 0) {
                    if ((strtotime($game_data[0]->added_date) + COLOR_3MIN_TIME_FOR_BET) <= time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_3_min_random;
                        if ($random == RANDOM) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }
                        $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction5Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount * $number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given" . PHP_EOL;
                        } else {
                            echo "No Winning Bet Found" . PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color != '') {
                            $color_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount * $color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1 != '') {
                            $color_1_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount * $color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }
                        // Give winning Amount to Small Big user
                        if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+' . COLOR_3MIN_TIME_FOR_START_NEW_GAME . ' seconds'));
                        $update_data['random'] = $random;
                        $this->ColorPrediction5Min_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start" . PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime) <= time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'color_prediction_3_min_room_id');
                        if ($count > 0) {
                            $this->ColorPrediction5Min_model->Create($room->id);

                            echo 'Color Prediction Created Successfully' . PHP_EOL;
                        } else {
                            echo 'No Online User Found' . PHP_EOL;
                        }
                    } else {
                        echo "No Game to End" . PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available' . PHP_EOL;
        }
    }

    public function color_prediction_5_min_create_socket()
    {
        $room_data = $this->ColorPrediction5Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction5Min_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->ColorPrediction5Min_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function color_prediction_5_min_winner_socket()
    {
        $room_data = $this->ColorPrediction5Min_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->ColorPrediction5Min_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+COLOR_3MIN_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, 9);

                        $GreenAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, GREEN);
                        $VioletAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, VIOLET);
                        $RedAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, RED);

                        $SmallAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, SMALL);
                        $BigAmount = $this->ColorPrediction5Min_model->TotalBetAmount($game_data[0]->id, BIG);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->color_prediction_5_min_random;
                        $admin_coin = $setting->admin_coin;
                        if ($random==RANDOM) {
                            $arr = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random==LEAST) {
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FOUR'] = ($FourAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        } else if ($random == GREEN) {
                            $arr = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == VIOLET) {
                            $arr = ['ZERO', 'FIVE'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == RED) {
                            $arr = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == SMALL) {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            $min = $arr[array_rand($arr)];
                        } else if ($random == BIG) {
                            $arr = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[array_rand($arr)];
                        } else if($random == 20) {
                            $option_arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $arr['ZERO'] = ($ZeroAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['ONE'] = ($OneAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['TWO'] = ($TwoAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['THREE'] = ($ThreeAmount * NUMBER_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE);
                            $arr['FIVE'] = ($FiveAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_HALF_MULTIPLE) + ($VioletAmount * VIOLET_MULTIPLE) + ($SmallAmount * SMALL_BIG_MULTIPLE);
                            $arr['SIX'] = ($SixAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['SEVEN'] = ($SevenAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['EIGHT'] = ($EightAmount * NUMBER_MULTIPLE) + ($RedAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            $arr['NINE'] = ($NineAmount * NUMBER_MULTIPLE) + ($GreenAmount * GREEN_RED_MULTIPLE) + ($BigAmount * SMALL_BIG_MULTIPLE);
                            
                            shuffle($option_arr);
                            $min = "";
                            foreach ($option_arr as $k => $value) {
                                if($arr[$value] > 0 && $admin_coin > $arr[$value]) {
                                    $min = $value;
                                }
                                if($min!=""){
                                    break;
                                }
                            }

                            if($min==""){
                                $min_arr = array_keys($arr, min($arr));
                                $min = $min_arr[array_rand($min_arr)];
                            }
                        } else {
                            $arr = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            $min = $arr[$random];
                        }

                         $color = '';
                        $color_multiply = '';
                        $color_1 = '';
                        $color_1_multiply = '';
                        $number = '';
                        $number_multiply = '';
                        $small_big = '';
                        $small_big_multiply = SMALL_BIG_MULTIPLE;

                        switch ($min) {
                            case 'ZERO':
                                $color = RED;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'ONE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'TWO':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'THREE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FOUR':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = SMALL;
                                break;
                            case 'FIVE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_HALF_MULTIPLE;
                                $color_1 = VIOLET;
                                $color_1_multiply = VIOLET_MULTIPLE;
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SIX':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'SEVEN':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'EIGHT':
                                $color = RED;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;
                            case 'NINE':
                                $color = GREEN;
                                $color_multiply = GREEN_RED_MULTIPLE;
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                $small_big = BIG;
                                break;

                            default:
                                $color = '';
                                $color_multiply = '';
                                $color_1 = '';
                                $color_1_multiply = '';
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        // echo $number.'hi';
                        $this->ColorPrediction5Min_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                               
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color!='') {
                            $color_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount*$color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Color 1 user
                        if ($color_1!='') {
                            $color_1_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $color_1);
                            if ($color_1_bets) {
                                foreach ($color_1_bets as $key => $value) {
                                    $amount = $value->amount*$color_1_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                    
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                         if ($small_big != '') {
                            $small_big_bets = $this->ColorPrediction5Min_model->ViewBet("", $game_data[0]->id, $small_big);
                            if ($small_big_bets) {
                                foreach ($small_big_bets as $key => $value) {
                                    $amount = $value->amount * $small_big_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->ColorPrediction5Min_model->MakeWinner(
                                        $value->user_id,
                                        $value->id,
                                        $amount,
                                        $comission,
                                        $game_data[0]->id
                                    );
                                   
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.COLOR_5MIN_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(CP5,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->ColorPrediction5Min_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function head_tail()
    {
        $room_data = $this->HeadTail_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->HeadTail_model->getActiveGameOnTable($room->id);
                $card = '';
                if (!$game_data) {
                    $this->HeadTail_model->Create($room->id, $card);

                    echo 'First Head Tail Game Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id);

                        $DragonBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, HEAD)*2;
                        $TigerBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, TAIL)*2;

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->head_tail_random;
                        if ($random==1) {
                            $winning = RAND(HEAD, TAIL); //0=head,1=tail
                        } else {
                            if ($DragonBetAmount>0 || $TigerBetAmount>0) {
                                $winning = ($DragonBetAmount>$TigerBetAmount) ? TAIL : HEAD; //0=ander,1=bahar
                            } else {
                                $winning = RAND(HEAD, TAIL); //0=head,1=tail
                            }
                        }

                        // $TieBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, TIE)*11;

                        // if ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                        //     $winning = TIE;
                        // } else {
                        // $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                        // }

                        // if ($winning==TIE) {
                        //     $number = rand(2, 10);
                        //     $card_dragon = 'BP'.$number;
                        //     $card_tiger = 'RP'.$number;

                        //     $this->HeadTail_model->CreateMap($game_data[0]->id, $card_dragon);
                        //     $this->HeadTail_model->CreateMap($game_data[0]->id, $card_tiger);
                        // } else {
                        $limit = 2;
                        $cards = $this->HeadTail_model->GetCards($limit);
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

                        $card_dragon = ($winning==HEAD) ? $card_big : $card_small;
                        $card_tiger = ($winning==TAIL) ? $card_big : $card_small;

                        $this->HeadTail_model->CreateMap($game_data[0]->id, $card_dragon);
                        $this->HeadTail_model->CreateMap($game_data[0]->id, $card_tiger);
                        // }

                        // Give winning Amount to user
                        $bets = $this->HeadTail_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;

                            foreach ($bets as $key => $value) {
                                // if ($winning==TIE) {
                                //     $this->HeadTail_model->MakeWinner($value->user_id, $value->id, $value->amount*11, $comission, $game_data[0]->id);
                                // } else {
                                $amount = $value->amount*2;
                                $TotalWinningAmount += $amount;
                                $this->HeadTail_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                // }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->HeadTail_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'head_tail_room_id');
                        if ($count>0) {
                            $this->HeadTail_model->Create($room->id);

                            echo 'Head Tail Game Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function head_tail_create_socket()
    {
        $room_data = $this->HeadTail_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->HeadTail_model->getActiveGameOnTable($room->id);
                $card = '';
                if (!$game_data || $game_data[0]->status==1) {
                    $this->HeadTail_model->Create($room->id, $card);

                    echo 'First Head Tail Game Created Successfully'.PHP_EOL;
                    continue;
                }

            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function head_tail_winner_socket()
    {
        $room_data = $this->HeadTail_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->HeadTail_model->getActiveGameOnTable($room->id);
                $card = '';
                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id);

                        $DragonBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, HEAD)*2;
                        $TigerBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, TAIL)*2;

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->head_tail_random;
                        if ($random==1) {
                            $winning = RAND(HEAD, TAIL); //0=head,1=tail
                        }else if($random==2){

                            $admin_coin = $setting->admin_coin;
                            if ($DragonBetAmount==0 && $TigerBetAmount==0) {
                                $winning = RAND(0, 1);
                            } else {
                                $option_arr = [0,1];
                                shuffle($option_arr);
                                $winning = "";
                                foreach ($option_arr as $k => $value) {

                                    switch ($value) {
                                        case HEAD:
                                            if($DragonBetAmount>0 && $admin_coin>=$DragonBetAmount){
                                                $winning = HEAD;
                                            }
                                            break;
                                        case TAIL:
                                            if($TigerBetAmount>0 && $admin_coin>=$TigerBetAmount){
                                                $winning = TAIL;
                                            }
                                            break;
                                    }

                                    if($winning!=""){
                                        break;
                                    }
                                }

                                if($winning==""){
                                    if ($DragonBetAmount>0 || $TigerBetAmount>0) {
                                        $winning = ($DragonBetAmount>$TigerBetAmount) ? TAIL : HEAD; //0=ander,1=bahar
                                    } else {
                                        $winning = RAND(HEAD, TAIL); //0=head,1=tail
                                    }
                                }
                            }

                        } else {
                            if ($DragonBetAmount>0 || $TigerBetAmount>0) {
                                $winning = ($DragonBetAmount>$TigerBetAmount) ? TAIL : HEAD; //0=ander,1=bahar
                            } else {
                                $winning = RAND(HEAD, TAIL); //0=head,1=tail
                            }
                        }

                        // $TieBetAmount = $this->HeadTail_model->TotalBetAmount($game_data[0]->id, TIE)*11;

                        // if ($DragonBetAmount>$TieBetAmount && $TigerBetAmount>$TieBetAmount) {
                        //     $winning = TIE;
                        // } else {
                        // $winning = ($DragonBetAmount>$TigerBetAmount) ? TIGER : DRAGON; //0=Dragon,1=Tiger
                        // }

                        // if ($winning==TIE) {
                        //     $number = rand(2, 10);
                        //     $card_dragon = 'BP'.$number;
                        //     $card_tiger = 'RP'.$number;

                        //     $this->HeadTail_model->CreateMap($game_data[0]->id, $card_dragon);
                        //     $this->HeadTail_model->CreateMap($game_data[0]->id, $card_tiger);
                        // } else {
                        $limit = 2;
                        $cards = $this->HeadTail_model->GetCards($limit);
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

                        $card_dragon = ($winning==HEAD) ? $card_big : $card_small;
                        $card_tiger = ($winning==TAIL) ? $card_big : $card_small;

                        $this->HeadTail_model->CreateMap($game_data[0]->id, $card_dragon);
                        $this->HeadTail_model->CreateMap($game_data[0]->id, $card_tiger);
                        // }

                        // Give winning Amount to user
                        $bets = $this->HeadTail_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;

                            foreach ($bets as $key => $value) {
                                // if ($winning==TIE) {
                                //     $this->HeadTail_model->MakeWinner($value->user_id, $value->id, $value->amount*11, $comission, $game_data[0]->id);
                                // } else {
                                $amount = $value->amount*2;
                                $TotalWinningAmount += $amount;
                                $this->HeadTail_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                // }
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(HT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->HeadTail_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function poker_socket($table_id)
    {
        // $tables = $this->Poker_model->getActiveTable();
        // // print_r($tables);

        // foreach ($tables as $val) {
            $chaal = 0;
            $game = $this->Poker_model->getActiveGameOnTable($table_id);
            // print_r($game);
            if ($game) {
                $game_log = $this->Poker_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);
                // print_r($game_log);
                // if ($time>35) {
                    $game_users = $this->Poker_model->GameAllUser($game->id);


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
                                // $user_game_wallet = $this->Poker_model->TableUser($val->poker_table_id, $game_users[$index]->user_id)[0]->game_wallet;
                                // if($user_game_wallet>0){
                                    $chaal = $game_users[$index]->user_id;
                                    break;
                                // }
                            }
                        }
                    }
                // }
                // echo $chaal;
                if ($chaal!=0) {
                    $table_user_details = $this->Poker_model->TableUser($table_id,$chaal);
                    $this->Poker_model->PackGame($chaal, $game->id,1,$table_user_details[0]->game_wallet);
                    // $this->Poker_model->PackGame($chaal, $game->id, 1);
                    $game_users = $this->Poker_model->GameUser($game->id);

                    if (count($game_users)==1) {
                        $comission = $this->Setting_model->Setting()->admin_commission;
                        $this->Poker_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission, $table_id);

                        $user = $this->Users_model->UserProfile($game_users[0]->user_id);
                        if ($user[0]->user_type==1) {
                            $table_user_data = [
                                'poker_table_id' => $table_id,
                                'user_id' => $user[0]->id
                            ];

                            $this->Poker_model->RemoveTableUser($table_user_data);
                        }
                    }

                    $table_user_data = [
                        'poker_table_id' => $table_id,
                        'user_id' =>$chaal
                    ];

                    $this->Poker_model->RemoveTableUser($table_user_data);
                }
            }

            echo ($game)?'Running':'Stop';
        //     echo '<br>Success';
        // }
    }

    public function betreeno_socket($table_id)
    {
        // $tables = $this->Poker_model->getActiveTable();
        // // print_r($tables);

        // foreach ($tables as $val) {
            $chaal = 0;
            $game = $this->Betreeno_model->getActiveGameOnTable($table_id);
            // print_r($game);
            if ($game) {
                $game_log = $this->Betreeno_model->GameLog($game->id, 1);
                $time = time()-strtotime($game_log[0]->added_date);
                // print_r($game_log);
                // if ($time>35) {
                    $game_users = $this->Betreeno_model->GameAllUser($game->id);


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
                                // $user_game_wallet = $this->Poker_model->TableUser($val->poker_table_id, $game_users[$index]->user_id)[0]->game_wallet;
                                // if($user_game_wallet>0){
                                    $chaal = $game_users[$index]->user_id;
                                    break;
                                // }
                            }
                        }
                    }
                // }
                // echo $chaal;
                if ($chaal!=0) {
                    $table_user_details = $this->Betreeno_model->TableUser($table_id,$chaal);
                    $this->Betreeno_model->PackGame($chaal, $game->id,1,$table_user_details[0]->game_wallet);
                    // $this->Betreeno_model->PackGame($chaal, $game->id, 1);
                    $game_users = $this->Betreeno_model->GameUser($game->id);

                    if (count($game_users)==1) {
                        $comission = $this->Setting_model->Setting()->admin_commission;
                        $this->Betreeno_model->MakeWinner($game->id, $game->amount, $game_users[0]->user_id, $comission, $table_id);

                        $user = $this->Users_model->UserProfile($game_users[0]->user_id);
                        if ($user[0]->user_type==1) {
                            $table_user_data = [
                                'betreeno_table_id' => $table_id,
                                'user_id' => $user[0]->id
                            ];

                            $this->Betreeno_model->RemoveTableUser($table_user_data);
                        }
                    }

                    $table_user_data = [
                        'betreeno_table_id' => $table_id,
                        'user_id' =>$chaal
                    ];

                    $this->Betreeno_model->RemoveTableUser($table_user_data);
                }
            }

            echo ($game)?'Running':'Stop';
        //     echo '<br>Success';
        // }
    }

    public function baccarat()
    {
        $room_data = $this->Baccarat_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Baccarat_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->Baccarat_model->Create($room->id, $card);

                    echo 'First Baccarat Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id);
                        $PlayerAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, PLAYER);
                        $BankerAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, BANKER);
                        $TieAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, TIE);
                        $PlayerPairAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, PLAYER_PAIR);
                        $BankerPairAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, BANKER_PAIR);

                        $cards = $this->Baccarat_model->GetCards(6);
                        $card1 = $cards[0]->cards;
                        $card2 = $cards[1]->cards;
                        $card3 = $cards[2]->cards;
                        $card4 = $cards[3]->cards;
                        $card5 = $cards[4]->cards;
                        $card6 = $cards[5]->cards;

                        $playerPoint = $this->Baccarat_model->CardValue($card1, $card2);
                        $bankerPoint = $this->Baccarat_model->CardValue($card3, $card4);
                        $winning = $this->Baccarat_model->getWinner($playerPoint, $bankerPoint);
                        $multiply = $this->Baccarat_model->getMultiply($winning);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->bacarate_random;
                        if ($random==1) {
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card1);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card2);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card3);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card4);
                        } else {
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card1);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card2);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card3);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card4);
                        }

                        // Give winning Amount to user
                        $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $playerPair = $this->Baccarat_model->isPair($card1, $card2);
                        $playerPairMultiply = PLAYER_PAIR_MULTIPLE;
                        $bankerPair = $this->Baccarat_model->isPair($card3, $card4);
                        $bankerPairMultiply = BANKER_PAIR_MULTIPLE;

                        if ($playerPair) {
                            $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, PLAYER_PAIR);
                            if ($bets) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$playerPairMultiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        if ($bankerPair) {
                            $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, BANKER_PAIR);
                            if ($bets) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$bankerPairMultiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['player_pair'] = $playerPair;
                        $update_data['banker_pair'] = $bankerPair;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->Baccarat_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'baccarat_id');
                        if ($count>0) {
                            $this->Baccarat_model->Create($room->id);

                            echo 'Baccarat Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function baccarat_create_socket()
    {
        $room_data = $this->Baccarat_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Baccarat_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->Baccarat_model->Create($room->id, $card);

                    echo 'First Baccarat Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function baccarat_winner_socket()
    {
        $room_data = $this->Baccarat_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Baccarat_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id);
                        $PlayerAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, PLAYER);
                        $BankerAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, BANKER);
                        $TieAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, TIE);
                        $PlayerPairAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, PLAYER_PAIR);
                        $BankerPairAmount = $this->Baccarat_model->TotalBetAmount($game_data[0]->id, BANKER_PAIR);

                        $cards = $this->Baccarat_model->GetCards(6);
                        $card1 = $cards[0]->cards;
                        $card2 = $cards[1]->cards;
                        $card3 = $cards[2]->cards;
                        $card4 = $cards[3]->cards;
                        $card5 = $cards[4]->cards;
                        $card6 = $cards[5]->cards;

                        $playerPoint = $this->Baccarat_model->CardValue($card1, $card2);
                        $bankerPoint = $this->Baccarat_model->CardValue($card3, $card4);
                        $winning = $this->Baccarat_model->getWinner($playerPoint, $bankerPoint);
                        $multiply = $this->Baccarat_model->getMultiply($winning);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->bacarate_random;
                        if ($random==1) {
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card1);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card2);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card3);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card4);
                        } else {
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card1);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card2);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card3);
                            $this->Baccarat_model->CreateMap($game_data[0]->id, $card4);
                        }

                        // Give winning Amount to user
                        $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            // print_r($bets);
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }
                        $playerPair = $this->Baccarat_model->isPair($card1, $card2);
                        $playerPairMultiply = PLAYER_PAIR_MULTIPLE;
                        $bankerPair = $this->Baccarat_model->isPair($card3, $card4);
                        $bankerPairMultiply = BANKER_PAIR_MULTIPLE;

                        if ($playerPair) {
                            $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, PLAYER_PAIR);
                            if ($bets) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$playerPairMultiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        if ($bankerPair) {
                            $bets = $this->Baccarat_model->ViewBet("", $game_data[0]->id, BANKER_PAIR);
                            if ($bets) {
                                // print_r($bets);
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$bankerPairMultiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Baccarat_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['player_pair'] = $playerPair;
                        $update_data['banker_pair'] = $bankerPair;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(BT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->Baccarat_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function jhandi_munda()
    {
        $room_data = $this->JhandiMunda_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->JhandiMunda_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->JhandiMunda_model->Create($room->id, $card);

                    echo 'First Baccarat Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->jhandi_munda_random;
                        if ($random==1) {
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                        } else {
                            $arr['ONE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 1);
                            $arr['TWO'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 2);
                            $arr['THREE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 3);
                            $arr['FOUR'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 4);
                            $arr['FIVE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 5);
                            $arr['SIX'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 6);

                            $arr = shuffle_assoc($arr);
                            asort($arr);

                            $dice_count = 6;
                            $remaining_balance = $TotalBetAmount;

                            foreach ($arr as $key => $value) {
                                if ($dice_count>0) {
                                    $k = word_to_digit($key);
                                    if ($remaining_balance>($value*TWO_DICE)) {
                                        $two_dice = ($dice_count>=2) ? 2 : $dice_count;
                                        $three_dice = ($dice_count>=3) ? 3 : $dice_count;
                                        $dice = ($value*TWO_DICE==0) ? rand(1, $three_dice) : $two_dice;
                                        $remaining_balance = $remaining_balance - ($value*TWO_DICE);
                                    } else {
                                        $dice = 1;
                                    }

                                    for ($i=0; $i < $dice; $i++) {
                                        $this->JhandiMunda_model->CreateMap($game_data[0]->id, $k);
                                    }
                                    $dice_count = $dice_count - $dice;
                                } else {
                                    break;
                                }
                            }
                        }

                        for ($i=1; $i <= 6; $i++) {
                            $count = $this->JhandiMunda_model->MapCount($game_data[0]->id, $i);

                            if ($count>0) {
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                switch ($count) {
                                    case 1:
                                        $multiply = ONE_DICE;
                                        break;

                                    case 2:
                                        $multiply = TWO_DICE;
                                        break;

                                    case 3:
                                        $multiply = THREE_DICE;
                                        break;

                                    case 4:
                                        $multiply = FOUR_DICE;
                                        break;

                                    case 5:
                                        $multiply = FIVE_DICE;
                                        break;

                                    case 6:
                                        $multiply = SIX_DICE;
                                        break;

                                    default:
                                        break;
                                }

                                if ($multiply>0) {
                                    $bets = $this->JhandiMunda_model->ViewBet("", $game_data[0]->id, $i);
                                    if ($bets) {
                                        // print_r($bets);

                                        foreach ($bets as $key => $value) {
                                            $amount = $value->amount*$multiply;
                                            $TotalWinningAmount += $amount;
                                            $this->JhandiMunda_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                        }
                                        echo "Winning Amount Given".PHP_EOL;
                                    } else {
                                        echo "No Winning Bet Found".PHP_EOL;
                                    }
                                }
                            }
                        }

                        $update_data['status'] = 1;
                        // $update_data['winning'] = $winning;
                        // $update_data['player_pair'] = $playerPair;
                        // $update_data['banker_pair'] = $bankerPair;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->JhandiMunda_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'jhandi_munda_id');
                        if ($count>0) {
                            $this->JhandiMunda_model->Create($room->id);

                            echo 'Baccarat Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function jhandi_munda_create_socket()
    {
        $room_data = $this->JhandiMunda_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->JhandiMunda_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->JhandiMunda_model->Create($room->id, $card);

                    echo 'First Baccarat Created Successfully'.PHP_EOL;
                    continue;
                }

            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function jhandi_munda_winner_socket()
    {
        $room_data = $this->JhandiMunda_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->JhandiMunda_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->jhandi_munda_random;
                        if ($random==1) {
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                            $this->JhandiMunda_model->CreateMap($game_data[0]->id, rand(1, 6));
                        } else {
                            $arr['ONE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 1);
                            $arr['TWO'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 2);
                            $arr['THREE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 3);
                            $arr['FOUR'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 4);
                            $arr['FIVE'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 5);
                            $arr['SIX'] = $this->JhandiMunda_model->TotalBetAmount($game_data[0]->id, 6);

                            $arr = shuffle_assoc($arr);
                            asort($arr);

                            $dice_count = 6;
                            $remaining_balance = $TotalBetAmount;

                            foreach ($arr as $key => $value) {
                                if ($dice_count>0) {
                                    $k = word_to_digit($key);
                                    if ($remaining_balance>($value*TWO_DICE)) {
                                        $two_dice = ($dice_count>=2) ? 2 : $dice_count;
                                        $three_dice = ($dice_count>=3) ? 3 : $dice_count;
                                        $dice = ($value*TWO_DICE==0) ? rand(1, $three_dice) : $two_dice;
                                        $remaining_balance = $remaining_balance - ($value*TWO_DICE);
                                    } else {
                                        $dice = 1;
                                    }

                                    for ($i=0; $i < $dice; $i++) {
                                        $this->JhandiMunda_model->CreateMap($game_data[0]->id, $k);
                                    }
                                    $dice_count = $dice_count - $dice;
                                } else {
                                    break;
                                }
                            }
                        }

                        for ($i=1; $i <= 6; $i++) {
                            $count = $this->JhandiMunda_model->MapCount($game_data[0]->id, $i);

                            if ($count>0) {
                                $comission = $this->Setting_model->Setting()->admin_commission;
                                switch ($count) {
                                    case 1:
                                        $multiply = ONE_DICE;
                                        break;

                                    case 2:
                                        $multiply = TWO_DICE;
                                        break;

                                    case 3:
                                        $multiply = THREE_DICE;
                                        break;

                                    case 4:
                                        $multiply = FOUR_DICE;
                                        break;

                                    case 5:
                                        $multiply = FIVE_DICE;
                                        break;

                                    case 6:
                                        $multiply = SIX_DICE;
                                        break;

                                    default:
                                        break;
                                }

                                if ($multiply>0) {
                                    $bets = $this->JhandiMunda_model->ViewBet("", $game_data[0]->id, $i);
                                    if ($bets) {
                                        // print_r($bets);

                                        foreach ($bets as $key => $value) {
                                            $amount = $value->amount*$multiply;
                                            $TotalWinningAmount += $amount;
                                            $this->JhandiMunda_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                        }
                                        echo "Winning Amount Given".PHP_EOL;
                                    } else {
                                        echo "No Winning Bet Found".PHP_EOL;
                                    }
                                }
                            }
                        }

                        $update_data['status'] = 1;
                        // $update_data['winning'] = $winning;
                        // $update_data['player_pair'] = $playerPair;
                        // $update_data['banker_pair'] = $bankerPair;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(JM,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->JhandiMunda_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function roulette_create_socket()
    {
        $room_data = $this->Roulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Roulette_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->Roulette_model->Create($room->id, $card);

                    echo 'First Roulette Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function roulette_winner_socket()
    {
        $room_data = $this->Roulette_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Roulette_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Roulette_model->TotalBetAmount($game_data[0]->id);
                        $setting = $this->Setting_model->Setting();
                        $random = $setting->roulette_random;
                        if ($random==1) {
                            // $arr = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,R_TWELFTH_1ST,R_TWELFTH_2ND,R_TWELFTH_3RD,R_EIGHTEENTH_1ST,R_EIGHTEENTH_2ND,R_ODD,R_EVEN,R_RED,R_BLACK,R_ROW_1,R_ROW_2,R_ROW_3,R_1_2,R_2_3,R_4_5,R_5_6,R_7_8,R_8_9,R_10_11,R_11_12,R_13_14,R_14_15,R_16_17,R_17_18,R_19_20,R_20_21,R_22_23,R_23_24,R_25_26,R_26_27,R_28_29,R_29_30,R_31_32,R_32_33,R_34_35,R_35_36,R_0_1,R_0_2,R_0_3,R_1_4,R_2_5,R_3_6,R_4_7,R_5_8,R_6_9,R_7_10,R_8_11,R_9_12,R_10_13,R_11_14,R_12_15,R_13_16,R_14_17,R_15_18,R_16_19,R_17_20,R_18_21,R_19_22,R_20_23,R_21_24,R_22_25,R_23_26,R_24_27,R_25_28,R_26_29,R_27_30,R_28_31,R_29_32,R_30_33,R_31_34,R_32_35,R_33_36,R_0_1_2,R_0_2_3,R_1_2_4_5,R_2_3_5_6,R_4_5_7_8,R_5_6_8_9,R_7_8_10_11,R_8_9_11_12,R_10_11_13_14,R_11_12_14_15,R_13_14_16_17,R_14_15_17_18,R_16_17_19_20,R_17_18_20_21,R_19_20_22_23,R_20_21_23_24,R_22_23_25_26,R_23_24_26_27,R_25_26_28_29,R_26_27_29_30,R_28_29_31_32,R_29_30_32_33,R_31_32_34_35,R_32_33_35_36];
                            $arr = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36];
                            $number = $arr[array_rand($arr)];
                        } else {
                            $arr[0] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 0);
                            $arr[1] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 1);
                            $arr[2] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 2);
                            $arr[3] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 3);
                            $arr[4] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 4);
                            $arr[5] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 5);
                            $arr[6] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 6);
                            $arr[7] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 7);
                            $arr[8] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 8);
                            $arr[9] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 9);
                            $arr[10] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 10);
                            $arr[11] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 11);
                            $arr[12] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 12);
                            $arr[13] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 13);
                            $arr[14] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 14);
                            $arr[15] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 15);
                            $arr[16] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 16);
                            $arr[17] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 17);
                            $arr[18] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 18);
                            $arr[19] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 19);
                            $arr[20] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 20);
                            $arr[21] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 21);
                            $arr[22] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 22);
                            $arr[23] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 23);
                            $arr[24] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 24);
                            $arr[25] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 25);
                            $arr[26] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 26);
                            $arr[27] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 27);
                            $arr[28] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 28);
                            $arr[29] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 29);
                            $arr[30] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 30);
                            $arr[31] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 31);
                            $arr[32] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 32);
                            $arr[33] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 33);
                            $arr[34] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 34);
                            $arr[35] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 35);
                            $arr[36] = $this->Roulette_model->TotalBetAmount($game_data[0]->id, 36);
                            $min_arr = array_keys($arr, min($arr));
                            $number = $min_arr[array_rand($min_arr)];
                        }
                        
                        // $number = rand(0, 36);
                        $number_multiply = R_NUMBER_MULTIPLE;
                        $color = '';
                        $color_multiply = R_COLOR_MULTIPLE;
                        $odd_even = '';
                        $odd_even_multiply = R_ODD_EVEN_MULTIPLE;
                        $twelfth_column = '';
                        $twelfth_column_multiply = R_TWELFTH_MULTIPLE;
                        $eighteenth_column = '';
                        $eighteenth_column_multiply = R_EIGHTEENTH_MULTIPLE;
                        $row = '';
                        $row_multiply = R_ROW_MULTIPLE;
                        $split_2 = array();
                        $split_4 = array();
                        $split_2_multiply = R_TWO_SPLIT_MULTIPLE;
                        $split_4_multiply = R_FOUR_SPLIT_MULTIPLE;

                        switch ($number) {
                            case 0:
                                $row = R_ROW_2;
                                $split_2 = array(R_0_1, R_0_2, R_0_3);
                                $split_4 = array(R_0_1_2, R_0_2_3);
                                break;
                            case 1:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_0_1, R_1_2, R_1_4);
                                $split_4 = array(R_0_1_2, R_1_2_4_5);
                                break;
                            case 2:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_0_2, R_1_2, R_2_3, R_2_5);
                                $split_4 = array(R_0_1_2, R_1_2_4_5, R_0_2_3, R_2_3_5_6);
                                break;
                            case 3:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_0_3, R_2_3, R_3_6);
                                $split_4 = array(R_0_2_3, R_2_3_5_6);
                                break;
                            case 4:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_1_4, R_4_5, R_4_7);
                                $split_4 = array(R_1_2_4_5, R_4_5_7_8);
                                break;
                            case 5:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_2_5, R_4_5, R_5_6, R_5_8);
                                $split_4 = array(R_1_2_4_5, R_4_5_7_8, R_2_3_5_6, R_5_6_8_9);
                                break;
                            case 6:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_3_6, R_6_9, R_5_6);
                                $split_4 = array(R_2_3_5_6, R_5_6_8_9);
                                break;
                            case 7:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_4_7, R_7_8, R_7_10);
                                $split_4 = array(R_4_5_7_8, R_7_8_10_11);
                                break;
                            case 8:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_5_8, R_7_8, R_8_9, R_8_11);
                                $split_4 = array(R_4_5_7_8, R_7_8_10_11, R_5_6_8_9, R_8_9_11_12);
                                break;
                            case 9:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_6_9, R_8_9, R_9_12);
                                $split_4 = array(R_5_6_8_9, R_8_9_11_12);
                                break;
                            case 10:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_7_10, R_10_11, R_10_13);
                                $split_4 = array(R_7_8_10_11, R_10_11_13_14);
                                break;
                            case 11:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_8_11, R_10_11, R_11_12, R_11_14);
                                $split_4 = array(R_7_8_10_11, R_10_11_13_14, R_11_12_14_15, R_8_9_11_12);
                                break;
                            case 12:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_1ST;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_9_12, R_11_12, R_12_15);
                                $split_4 = array(R_8_9_11_12, R_11_12_14_15);
                                break;
                            case 13:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_10_13, R_13_14, R_13_16);
                                $split_4 = array(R_10_11_13_14, R_13_14_16_17);
                                break;
                            case 14:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_11_14, R_13_14, R_14_15, R_14_17);
                                $split_4 = array(R_10_11_13_14, R_13_14_16_17, R_11_12_14_15, R_14_15_17_18);
                                break;
                            case 15:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_12_15, R_14_15, R_15_18);
                                $split_4 = array(R_11_12_14_15, R_14_15_17_18);
                                break;
                            case 16:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_3;
                                $split_2 = array(R_13_16, R_16_17, R_16_19);
                                $split_4 = array(R_13_14_16_17, R_16_17_19_20);
                                break;
                            case 17:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_2;
                                $split_2 = array(R_14_17, R_16_17, R_17_18, R_17_20);
                                $split_4 = array(R_13_14_16_17, R_16_17_19_20, R_14_15_17_18, R_17_18_20_21);
                                break;
                            case 18:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_1ST;
                                $row = R_ROW_1;
                                $split_2 = array(R_15_18, R_17_18, R_18_21);
                                $split_4 = array(R_14_15_17_18, R_17_18_20_21);
                                break;
                            case 19:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_16_19, R_19_20, R_19_22);
                                $split_4 = array(R_16_17_19_20, R_19_20_22_23);
                                break;
                            case 20:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_17_20, R_19_20, R_20_21, R_20_23);
                                $split_4 = array(R_16_17_19_20, R_19_20_22_23, R_17_18_20_21, R_20_21_23_24);
                                break;
                            case 21:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_18_21, R_20_21, R_21_24);
                                $split_4 = array(R_17_18_20_21, R_20_21_23_24);
                                break;
                            case 22:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_19_22, R_22_23, R_22_25);
                                $split_4 = array(R_19_20_22_23, R_22_23_25_26);
                                break;
                            case 23:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_20_23, R_22_23, R_23_24, R_23_26);
                                $split_4 = array(R_19_20_22_23, R_22_23_25_26, R_20_21_23_24, R_23_24_26_27);
                                break;
                            case 24:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_2ND;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_21_24, R_23_24, R_24_27);
                                $split_4 = array(R_20_21_23_24, R_23_24_26_27);
                                break;
                            case 25:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_22_25, R_25_26, R_25_28);
                                $split_4 = array(R_22_23_25_26, R_25_26_28_29);
                                break;
                            case 26:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_23_26, R_25_26, R_26_27, R_26_29);
                                $split_4 = array(R_22_23_25_26, R_25_26_28_29, R_23_24_26_27, R_26_27_29_30);
                                break;
                            case 27:
                                $color = R_RED;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_24_27, R_26_27, R_27_30);
                                $split_4 = array(R_23_24_26_27, R_26_27_29_30);
                                break;
                            case 28:
                                $color = R_BLACK;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_25_28, R_28_29, R_28_31);
                                $split_4 = array(R_25_26_28_29, R_28_29_31_32);
                                break;
                            case 29:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_26_29, R_28_29, R_29_30, R_29_32);
                                $split_4 = array(R_25_26_28_29, R_26_27_29_30, R_28_29_31_32, R_29_30_32_33);
                                break;
                            case 30:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_27_30, R_29_30, R_30_33);
                                $split_4 = array(R_26_27_29_30, R_29_30_32_33);
                                break;
                            case 31:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_28_31, R_31_32, R_31_34);
                                $split_4 = array(R_28_29_31_32, R_31_32_34_35);
                                break;
                            case 32:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_29_32, R_31_32, R_32_33, R_32_35);
                                $split_4 = array(R_28_29_31_32, R_31_32_34_35, R_29_30_32_33, R_32_33_35_36);
                                break;
                            case 33:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_30_33, R_32_33, R_33_36);
                                $split_4 = array(R_29_30_32_33, R_32_33_35_36);
                                break;
                            case 34:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_3;
                                $split_2 = array(R_31_34, R_34_35);
                                $split_4 = array(R_31_32_34_35);
                                break;
                            case 35:
                                $color = R_BLACK;
                                $odd_even = R_ODD;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_2;
                                $split_2 = array(R_32_35, R_34_35, R_35_36);
                                $split_4 = array(R_31_32_34_35, R_32_33_35_36);
                                break;
                            case 36:
                                $color = R_RED;
                                $odd_even = R_EVEN;
                                $twelfth_column = R_TWELFTH_3RD;
                                $eighteenth_column = R_EIGHTEENTH_2ND;
                                $row = R_ROW_1;
                                $split_2 = array(R_33_36, R_35_36);
                                $split_4 = array(R_32_33_35_36);
                                break;
                            default:
                                $color = '';
                                $odd_even = '';
                                $twelfth_column = '';
                                $eighteenth_column = '';
                                $row = '';
                                $split_2 = array();
                                $split_4 = array();
                                break;
                        }

                        $this->Roulette_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Give winning Amount to Color user
                        if ($color!='') {
                            $color_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $color);
                            if ($color_bets) {
                                foreach ($color_bets as $key => $value) {
                                    $amount = $value->amount*$color_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to OddEven user
                        if ($odd_even!='') {
                            $odd_even_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $odd_even);
                            if ($odd_even_bets) {
                                foreach ($odd_even_bets as $key => $value) {
                                    $amount = $value->amount*$odd_even_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Twelfth user
                        if ($twelfth_column!='') {
                            $twelfth_column_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $twelfth_column);
                            if ($twelfth_column_bets) {
                                foreach ($twelfth_column_bets as $key => $value) {
                                    $amount = $value->amount*$twelfth_column_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Eighteenth user
                        if ($eighteenth_column!='') {
                            $eighteenth_column_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $eighteenth_column);
                            if ($eighteenth_column_bets) {
                                foreach ($eighteenth_column_bets as $key => $value) {
                                    $amount = $value->amount*$eighteenth_column_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Row user
                        if ($row!='') {
                            $row_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $row);
                            if ($row_bets) {
                                foreach ($row_bets as $key => $value) {
                                    $amount = $value->amount*$row_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Split 2 user
                        if ($split_2!='') {
                            $split_2_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $split_2);
                            if ($split_2_bets) {
                                foreach ($split_2_bets as $key => $value) {
                                    $amount = $value->amount*$split_2_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Split 4 user
                        if ($split_4!='') {
                            $split_4_bets = $this->Roulette_model->ViewBet("", $game_data[0]->id, $split_4);
                            if ($split_4_bets) {
                                foreach ($split_4_bets as $key => $value) {
                                    $amount = $value->amount*$split_4_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Roulette_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['random'] = $random;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(RT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->Roulette_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function lottery_create_socket()
    {
        $game_id = 0;

        $res_arr = array();
        $res_arr['code'] = 100;
        $res_arr['msg'] = 'No Room';
        $res_arr['game_id'] = $game_id;
        
        $room_data = $this->Lottery_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Lottery_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $game_id = $this->Lottery_model->Create($room->id, $card);

                    $res_arr['code'] = 200;
                    $res_arr['msg'] = 'First Lottery Created Successfully';
                    $res_arr['game_id'] = $game_id;
                    continue;
                }

                if ($game_data[0]->status==1) {
                    $card = '';
                    $game_id = $this->Lottery_model->Create($room->id, $card);

                    $res_arr['code'] = 200;
                    $res_arr['msg'] = 'Lottery Created Successfully';
                    $res_arr['game_id'] = $game_id;
                }else{
                    $res_arr['code'] = 200;
                    $res_arr['msg'] = 'Old Game Running';
                    $res_arr['game_id'] = $game_data[0]->id;
                }
            }
        }

        echo json_encode($res_arr);
    }

    public function lottery_winner_socket()
    {
        $room_data = $this->Lottery_model->getRoom();

        if ($room_data) {
            
            foreach ($room_data as $key => $room) {
                $game_data = $this->Lottery_model->getActiveGameOnTable($room->id);
                
                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Lottery_model->TotalBetAmount($game_data[0]->id);
                        
                        $comission = $this->Setting_model->Setting()->admin_commission;

                        foreach (range('A','T') as $key => $value) {
                            $number = str_pad(rand(01,99),2, 0, STR_PAD_LEFT);
                            echo $winner = $value.'-'.$number;
                            $this->Lottery_model->CreateMap($game_data[0]->id, $winner);

                            // Give winning Amount to Number user
                            $bets = $this->Lottery_model->ViewBet("", $game_data[0]->id, $winner);
                            if ($bets) {
                                $number_multiply = ($number>9)?99:9;
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$number_multiply;
                                    $TotalWinningAmount += $amount;
                                    $this->Lottery_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = '';
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.DRAGON_TIME_FOR_START_NEW_GAME.' seconds'));
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(LT,$update_data['admin_profit'],$game_data[0]->id);
                        }
                        $this->Lottery_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function target()
    {
        $room_data = $this->Target_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Target_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->Target_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+TARGET_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Target_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 9);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->target_random;
                        if ($random==1) {
                            $arr = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr['ZERO'] = $ZeroAmount*TARGET_MULTIPLE;
                            $arr['ONE'] = $OneAmount*TARGET_MULTIPLE;
                            $arr['TWO'] = $TwoAmount*TARGET_MULTIPLE;
                            $arr['THREE'] = $ThreeAmount*TARGET_MULTIPLE;
                            $arr['FOUR'] = $FourAmount*TARGET_MULTIPLE;
                            $arr['FIVE'] = $FiveAmount*TARGET_MULTIPLE;
                            $arr['SIX'] = $SixAmount*TARGET_MULTIPLE;
                            $arr['SEVEN'] = $SevenAmount*TARGET_MULTIPLE;
                            $arr['EIGHT'] = $EightAmount*TARGET_MULTIPLE;
                            $arr['NINE'] = $NineAmount*TARGET_MULTIPLE;

                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        }

                        switch ($min) {
                            case 'ZERO':
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'ONE':
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'TWO':
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'THREE':
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'FOUR':
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'FIVE':
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'SIX':
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'SEVEN':
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'EIGHT':
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'NINE':
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;

                            default:
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        $this->Target_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->Target_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply*$game_data[0]->into;
                                $TotalWinningAmount += $amount;
                                $this->Target_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.TARGET_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->Target_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'target_room_id');
                        if ($count>0) {
                            $this->Target_model->Create($room->id);

                            echo 'Target Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function target_create_socket()
    {
        $room_data = $this->Target_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Target_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->Target_model->Create($room->id, $card);

                    echo 'First Jackpot Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function target_winner_socket()
    {
        $room_data = $this->Target_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->Target_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+TARGET_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->Target_model->TotalBetAmount($game_data[0]->id);

                        $ZeroAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 0);
                        $OneAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 8);
                        $NineAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 9);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->target_random;
                        if ($random==1) {
                            $arr = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE'];
                            $min = $arr[array_rand($arr)];
                        } else {
                            $arr['ZERO'] = $ZeroAmount*TARGET_MULTIPLE;
                            $arr['ONE'] = $OneAmount*TARGET_MULTIPLE;
                            $arr['TWO'] = $TwoAmount*TARGET_MULTIPLE;
                            $arr['THREE'] = $ThreeAmount*TARGET_MULTIPLE;
                            $arr['FOUR'] = $FourAmount*TARGET_MULTIPLE;
                            $arr['FIVE'] = $FiveAmount*TARGET_MULTIPLE;
                            $arr['SIX'] = $SixAmount*TARGET_MULTIPLE;
                            $arr['SEVEN'] = $SevenAmount*TARGET_MULTIPLE;
                            $arr['EIGHT'] = $EightAmount*TARGET_MULTIPLE;
                            $arr['NINE'] = $NineAmount*TARGET_MULTIPLE;

                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[array_rand($min_arr)];
                        }

                        switch ($min) {
                            case 'ZERO':
                                $number = 0;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'ONE':
                                $number = 1;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'TWO':
                                $number = 2;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'THREE':
                                $number = 3;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'FOUR':
                                $number = 4;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'FIVE':
                                $number = 5;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'SIX':
                                $number = 6;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'SEVEN':
                                $number = 7;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'EIGHT':
                                $number = 8;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;
                            case 'NINE':
                                $number = 9;
                                $number_multiply = NUMBER_MULTIPLE;
                                break;

                            default:
                                $number = '';
                                $number_multiply = '';
                                break;
                        }

                        $this->Target_model->CreateMap($game_data[0]->id, $number);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        // Give winning Amount to Number user
                        $bets = $this->Target_model->ViewBet("", $game_data[0]->id, $number);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$number_multiply*$game_data[0]->into;
                                $TotalWinningAmount += $amount;
                                $this->Target_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.TARGET_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;

                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement('Target',$update_data['admin_profit'],$game_data[0]->id);
                        }

                        $this->Target_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function golden_wheel()
    {
        $room_data = $this->GoldenWheel_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->GoldenWheel_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    $card = '';
                    $this->GoldenWheel_model->Create($room->id, $card);

                    echo 'First Golden Wheel Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+TARGET_TIME_FOR_BET)<=time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id);

                        $OneAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 8);

                        $OddAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, GOLDEN_ODD);
                        $EvenAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, GOLDEN_EVEN);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->golden_wheel_random;
                        if ($random==1) {
                            $arr = ['ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } else {

                            $arr_into = [10,15,20,25,30,50,100,200,1000];
                            $into = $arr_into[array_rand($arr_into)];

                            $arr['ONE'] = $OneAmount;
                            $arr['TWO'] = $TwoAmount;
                            $arr['THREE'] = $ThreeAmount;
                            $arr['FOUR'] = $FourAmount;
                            $arr['FIVE'] = $FiveAmount;
                            $arr['SIX'] = $SixAmount;
                            $arr['SEVEN'] = $SevenAmount;
                            $arr['EIGHT'] = $EightAmount;

                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        }

                        $number_multiply = 0;
                        $odd_even_winner = 0;
                        $odd_even_multiply = GOLDEN_ODD_EVEN_MULTIPLY;

                        $winning = rand(true,false);

                        switch ($min) {
                            case 'ONE':
                                $winning_outer = 1;
                                $winning_inner_arr = ($winning)?[1,2,5,8]:[3,4,6,7];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'TWO':
                                $winning_outer = 2;
                                $winning_inner_arr = ($winning)?[1,2,3,6]:[4,5,7,8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'THREE':
                                $winning_outer = 3;
                                $winning_inner_arr = ($winning)?[2,3,4,7]:[1,5,6,8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'FOUR':
                                $winning_outer = 4;
                                $winning_inner_arr = ($winning)?[3,4,5,8]:[1,2,6,7];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'FIVE':
                                $winning_outer = 5;
                                $winning_inner_arr = ($winning)?[1,4,5,6]:[2,3,7,8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'SIX':
                                $winning_outer = 6;
                                $winning_inner_arr = ($winning)?[2,5,6,7]:[1,3,4,8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'SEVEN':
                                $winning_outer = 7;
                                $winning_inner_arr = ($winning)?[3,6,7,8]:[1,2,4,5];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'EIGHT':
                                $winning_outer = 8;
                                $winning_inner_arr = ($winning)?[1,4,7,8]:[2,3,5,6];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;

                            default:
                                $winning_outer = '';
                                $winning_inner = '';
                                $odd_even_winner = 0;
                                break;
                        }

                        $this->GoldenWheel_model->CreateMap($game_data[0]->id, $winning_outer);

                        $comission = $this->Setting_model->Setting()->admin_commission;
                        
                        if($winning){
                            // Give winning Amount to Number user
                            $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $winning_outer);
                            if ($bets) {
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount*$into;
                                    $TotalWinningAmount += $amount;
                                    $this->GoldenWheel_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given".PHP_EOL;
                            } else {
                                echo "No Winning Bet Found".PHP_EOL;
                            }
                        }

                        // Give winning Amount to Odd Even user
                        $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $odd_even_winner);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$odd_even_multiply;
                                $TotalWinningAmount += $amount;
                                $this->GoldenWheel_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                $this->Users_model->UpdateStar($value->user_id,'+1');
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        // Take Star From User
                        $odd_even_loss = ($odd_even_winner==GOLDEN_ODD)?GOLDEN_EVEN:GOLDEN_ODD;
                        $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $odd_even_loss);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $this->Users_model->UpdateStar($value->user_id,'-1');
                            }
                            echo "Star Lossing Bet Found".PHP_EOL;
                        } else {
                            echo "No Star Lossing Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $number;
                        $update_data['winning_outer'] = $winning_outer;
                        $update_data['winning_inner'] = $winning_inner;
                        $update_data['into'] = $into;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.TARGET_TIME_FOR_START_NEW_GAME.' seconds'));
                        $update_data['random'] = $random;
                        $this->GoldenWheel_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'golden_wheel_room_id');
                        if ($count>0) {
                            $this->GoldenWheel_model->Create($room->id);

                            echo 'Golden Wheel Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function golden_wheel_create_Socket()
    {
        $room_data = $this->GoldenWheel_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->GoldenWheel_model->getActiveGameOnTable($room->id);

                if (!$game_data || $game_data[0]->status==1) {
                    $card = '';
                    $this->GoldenWheel_model->Create($room->id, $card);

                    echo 'First Golden Wheel Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function golden_wheel_winner_socket()
    {
        $room_data = $this->GoldenWheel_model->getRoom();

        if ($room_data) {
            foreach ($room_data as $key => $room) {
                $game_data = $this->GoldenWheel_model->getActiveGameOnTable($room->id);

                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date) + TARGET_TIME_FOR_BET) <= time()) {
                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id);

                        $OneAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 1);
                        $TwoAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 2);
                        $ThreeAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 3);
                        $FourAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 4);
                        $FiveAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 5);
                        $SixAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 6);
                        $SevenAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 7);
                        $EightAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, 8);

                        $OddAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, GOLDEN_ODD);
                        $EvenAmount = $this->GoldenWheel_model->TotalBetAmount($game_data[0]->id, GOLDEN_EVEN);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->golden_wheel_random;
                        if ($random == RANDOM) {
                            $arr_into = [10, 15, 20, 25, 30, 50, 100, 200, 1000];
                            $into = $arr_into[array_rand($arr_into)];

                            $arr = ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT'];
                            $min = $arr[array_rand($arr)];
                        } elseif ($random == LEAST) {

                            $arr_into = [10, 15, 20, 25, 30, 50, 100, 200, 1000];
                            $into = $arr_into[array_rand($arr_into)];

                            $arr['ONE'] = $OneAmount;
                            $arr['TWO'] = $TwoAmount;
                            $arr['THREE'] = $ThreeAmount;
                            $arr['FOUR'] = $FourAmount;
                            $arr['FIVE'] = $FiveAmount;
                            $arr['SIX'] = $SixAmount;
                            $arr['SEVEN'] = $SevenAmount;
                            $arr['EIGHT'] = $EightAmount;

                            $min_arr = array_keys($arr, min($arr));
                            $min = $min_arr[0];
                        } else {
                            $arr_into = [10, 15, 20, 25, 30, 50, 100, 200, 1000];
                            $into = $arr_into[array_rand($arr_into)];

                            $percentile = $setting->goldenwheel_set_percentage;
                            // echo $percentile . '%';
                            // echo '<br>';
                            // print_r($TotalBetAmount);
                            // echo '<br>';
                            $arr = ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT'];

                            $i = 0;
                            do {
                                $min = $arr[array_rand($arr)];

                                $CommissionAmount = $TotalBetAmount * ($percentile / 100);
                                // echo 'After percentile ' . $CommissionAmount;
                                // echo '<br>';

                                // echo 'win ' . $min;
                                // echo '<br>';

                                switch ($min) {
                                    case 'ONE':
                                        $result = $OneAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'TWO':
                                        $result = $TwoAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'THREE':
                                        $result = $ThreeAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'FOUR':
                                        $result = $FourAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'FIVE':
                                        $result = $FiveAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'SIX':
                                        $result = $SixAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'SEVEN':
                                        $result = $SevenAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    case 'EIGHT':
                                        $result = $EightAmount * GOLDEN_ODD_EVEN_MULTIPLY;
                                        break;
                                    default:
                                        $result = $CommissionAmount;
                                        break;
                                }
                                // echo 'One= ' . $OneAmount . '<br>two= ' . $TwoAmount . '<br>three= ' . $ThreeAmount . '<br>four= ' . $FourAmount . '<br>five= ' . $FiveAmount . '<br>six= ' . $SixAmount . '<br>seven= ' . $SevenAmount . '<br>eight= ' . $EightAmount;
                                // echo '<br>';
                                // echo 'Result after applying multiplier: ' . $result;
                                // echo '<br>';
                                // echo $result . '-' . $CommissionAmount;
                                if ($CommissionAmount == 0 || $result == 0 || $i >= 20) {
                                    break;
                                }
                                $i++;
                            } while ($result > $CommissionAmount);
                            // exit;
                        }
                        $number = 0;
                        // $into = 0;
                        $number_multiply = 0;
                        $odd_even_winner = 0;
                        $odd_even_multiply = GOLDEN_ODD_EVEN_MULTIPLY;

                        // $winning = rand(true, false);
                        $winning = true;

                        switch ($min) {
                            case 'ONE':
                                $winning_outer = 1;
                                $winning_inner_arr = ($winning) ? [1, 2, 5, 8] : [3, 4, 6, 7];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'TWO':
                                $winning_outer = 2;
                                $winning_inner_arr = ($winning) ? [1, 2, 3, 6] : [4, 5, 7, 8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'THREE':
                                $winning_outer = 3;
                                $winning_inner_arr = ($winning) ? [2, 3, 4, 7] : [1, 5, 6, 8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'FOUR':
                                $winning_outer = 4;
                                $winning_inner_arr = ($winning) ? [3, 4, 5, 8] : [1, 2, 6, 7];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'FIVE':
                                $winning_outer = 5;
                                $winning_inner_arr = ($winning) ? [1, 4, 5, 6] : [2, 3, 7, 8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'SIX':
                                $winning_outer = 6;
                                $winning_inner_arr = ($winning) ? [2, 5, 6, 7] : [1, 3, 4, 8];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;
                            case 'SEVEN':
                                $winning_outer = 7;
                                $winning_inner_arr = ($winning) ? [3, 6, 7, 8] : [1, 2, 4, 5];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_ODD;
                                break;
                            case 'EIGHT':
                                $winning_outer = 8;
                                $winning_inner_arr = ($winning) ? [1, 4, 7, 8] : [2, 3, 5, 6];
                                $winning_inner = $winning_inner_arr[array_rand($winning_inner_arr)];
                                $odd_even_winner = GOLDEN_EVEN;
                                break;

                            default:
                                $winning_outer = '';
                                $winning_inner = '';
                                $odd_even_winner = 0;
                                break;
                        }

                        $this->GoldenWheel_model->CreateMap($game_data[0]->id, $winning_outer);

                        $comission = $this->Setting_model->Setting()->admin_commission;

                        if ($winning) {
                            // Give winning Amount to Number user
                            $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $winning_outer);
                            if ($bets) {
                                foreach ($bets as $key => $value) {
                                    $amount = $value->amount * $into;
                                    $TotalWinningAmount += $amount;
                                    $this->GoldenWheel_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                }
                                echo "Winning Amount Given" . PHP_EOL;
                            } else {
                                echo "No Winning Bet Found" . PHP_EOL;
                            }
                        }

                        // Give winning Amount to Odd Even user
                        $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $odd_even_winner);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount * $odd_even_multiply;
                                $TotalWinningAmount += $amount;
                                $this->GoldenWheel_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                                $this->Users_model->UpdateStar($value->user_id, '+1');
                            }
                            echo "Winning Amount Given" . PHP_EOL;
                        } else {
                            echo "No Winning Bet Found" . PHP_EOL;
                        }

                        // Take Star From User
                        $odd_even_loss = ($odd_even_winner == GOLDEN_ODD) ? GOLDEN_EVEN : GOLDEN_ODD;
                        $bets = $this->GoldenWheel_model->ViewBet("", $game_data[0]->id, $odd_even_loss);
                        if ($bets) {
                            foreach ($bets as $key => $value) {
                                $this->Users_model->UpdateStar($value->user_id, '-1');
                            }
                            echo "Star Lossing Bet Found" . PHP_EOL;
                        } else {
                            echo "No Star Lossing Bet Found" . PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = ($winning)?$winning_outer:0;
                        $update_data['winning_outer'] = $winning_outer;
                        $update_data['winning_inner'] = $winning_inner;
                        $update_data['into'] = $into;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+' . TARGET_TIME_FOR_START_NEW_GAME . ' seconds'));
                        $update_data['random'] = $random;
                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(GT,$update_data['admin_profit'],$game_data[0]->id);
                        }

                        $this->GoldenWheel_model->Update($update_data, $game_data[0]->id);
                    // } else {
                    //     echo "No Game to Start" . PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function ander_bahar_plus()
    {
        echo 'Date '.date('Y-m-d H:i:s').PHP_EOL;
        $room_data = $this->AnderBaharPlus_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBaharPlus_model->getActiveGameOnTable($room->id);
                // print_r($game_data);
                if (!$game_data) {
                    // $card = $this->AnderBaharPlus_model->GetCards($limit)[0]->cards;
                    $this->AnderBaharPlus_model->Create($room->id, '');

                    echo 'First Ander Baher Plus Game Created Successfully'.PHP_EOL;
                    continue;
                }

                if ($game_data[0]->status==0) {
                    if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {

                        $min = 1;
                        $max = 30;

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id);

                        $AnderBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, ANDER);
                        $BaharBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, BAHAR);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->ander_bahar_plus_random;
                        if ($random==1) {
                            $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                        } else {
                            if ($AnderBetAmount>0 || $BaharBetAmount>0) {
                                $winning = ($AnderBetAmount>$BaharBetAmount) ? BAHAR : ANDER; //0=ander,1=bahar
                            } else {
                                $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                            }
                        }

                        $exit = false;
                        do {
                            $number = rand($min, $max);
                            if ($winning==BAHAR) {
                                $exit = ($number % 2 != 0);
                            } else {
                                $exit = ($number % 2 == 0);
                            }
                        } while (!$exit);

                        // Main Card Patch
                        $main_card = $this->AnderBaharPlus_model->GetCards($limit)[0]->cards;
                        $update_data['main_card'] = $main_card;

                        $card_num = substr($main_card, 2);
                        $middle_cards = $this->AnderBaharPlus_model->GetCards($number, $card_num);

                        $alt_card = $this->AnderBaharPlus_model->GetCards($limit, $main_card, $card_num)[0]->cards;
                        // Main Card Patch End

                        // AtoK Patch
                        // $AceAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, ACE);
                        // $TwoAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 2);
                        // $ThreeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 3);
                        // $FourAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 4);
                        // $FiveAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 5);
                        // $SixAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 6);
                        // $SevenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 7);
                        // $EightAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 8);
                        // $NineAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 9);
                        // $TenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 10);
                        // $JackAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, JACK);
                        // $QueenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, QUEEN);
                        // $KingAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, KING);

                        // $arr = array();
                        // $arr['ACE'] = $AceAmount*TARGET_MULTIPLE;
                        // $arr['TWO'] = $TwoAmount*TARGET_MULTIPLE;
                        // $arr['THREE'] = $ThreeAmount*TARGET_MULTIPLE;
                        // $arr['FOUR'] = $FourAmount*TARGET_MULTIPLE;
                        // $arr['FIVE'] = $FiveAmount*TARGET_MULTIPLE;
                        // $arr['SIX'] = $SixAmount*TARGET_MULTIPLE;
                        // $arr['SEVEN'] = $SevenAmount*TARGET_MULTIPLE;
                        // $arr['EIGHT'] = $EightAmount*TARGET_MULTIPLE;
                        // $arr['NINE'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['TEN'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['JACK'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['QUEEN'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['KING'] = $NineAmount*TARGET_MULTIPLE;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];

                        $updown_multiply = ABPLUS_UP_DOWN_MULTIPLY;
                        $updown = '';

                        $number_multiply = ABPLUS_NUMBER_MULTIPLY;

                        switch ($card_num) {
                            case 'A':
                                $number = ACE;
                                break;
                            case 'K':
                                $number = KING;
                                break;
                            case 'Q':
                                $number = QUEEN;
                                break;
                            case 'J':
                                $number = JACK;
                                break;
                            default:
                                $number = $card_num;
                                break;
                        }

                        if($number<=6){
                            $updown = A_6;
                        }else if($number==7){
                            $updown = AB_SEVEN;
                        }else{
                            $updown = AB_8_K;
                        }

                        $winning_ak = $number;
                        $number_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $number);
                        if ($number_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($number_bets);
                            foreach ($number_bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "AtoK Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No AtoK Winning Bet Found".PHP_EOL;
                        }
                        // AtoK Patch End

                        // UP_DOWN Patch
                        // $UpAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_8_K);
                        // $SevenAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_SEVEN);
                        // $DownAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, A_6);

                        // $arr = array();
                        // $arr['AB_8_K'] = $RedAmount*ABPLUS_UP_DOWN_MULTIPLY;
                        // $arr['AB_SEVEN'] = $BlackAmount*ABPLUS_UP_DOWN_MULTIPLY;
                        // $arr['A_6'] = $BlackAmount*ABPLUS_UP_DOWN_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];

                        $winning_up_down = $updown;
                        $updown_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $updown);
                        if ($updown_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($updown_bets);
                            foreach ($updown_bets as $key => $value) {
                                $amount = $value->amount*$updown_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Up Down Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Up Down Winning Bet Found".PHP_EOL;
                        }
                        // UP_DOWN Patch End

                        // SHAPE Patch
                        // $HeartAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_HEART);
                        // $SpadeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_SPADE);
                        // $DiamondAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_DIAMOND);
                        // $ClubAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_CLUB);

                        // $arr = array();
                        // $arr['AB_HEART'] = $HeartAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_SPADE'] = $SpadeAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_DIAMOND'] = $DiamondAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_CLUB'] = $ClubAmount*ABPLUS_SHAPE_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];
                        $card_shape = substr($main_card, 0, 2);
                        $shape_multiply = ABPLUS_SHAPE_MULTIPLY;
                        $shape = '';

                        switch ($card_shape) {
                            case 'RP':
                                $shape = AB_HEART;
                                break;
                            case 'BP':
                                $shape = AB_SPADE;
                                break;
                            case 'RS':
                                $shape = AB_DIAMOND;
                                break;
                            case 'BL':
                                $shape = AB_CLUB;
                                break;
                            default:
                                $shape = $card_shape;
                                break;
                        }

                        $winning_shape = $shape;
                        $shape_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $shape);
                        if ($shape_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($shape_bets);
                            foreach ($shape_bets as $key => $value) {
                                $amount = $value->amount*$shape_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Shape Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Shape Winning Bet Found".PHP_EOL;
                        }
                        // SHAPE Patch End

                        // COLOR Patch
                        $RedAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_RED);
                        $BlackAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_BLACK);

                        $arr = array();
                        $arr['AB_RED'] = $RedAmount*ABPLUS_COLOR_MULTIPLY;
                        $arr['AB_BLACK'] = $BlackAmount*ABPLUS_COLOR_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];
                        $card_color = substr($main_card, 0, 1);
                        $color_multiply = ABPLUS_COLOR_MULTIPLY;
                        $color = '';

                        switch ($card_color) {
                            case 'R':
                                $color = AB_RED;
                                break;
                            case 'B':
                                $color = AB_BLACK;
                                break;
                            default:
                                $color = AB_RED;
                                break;
                        }

                        $winning_red_black = $color;
                        $color_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $color);
                        if ($color_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($color_bets);
                            foreach ($color_bets as $key => $value) {
                                $amount = $value->amount*$color_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Color Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Color Winning Bet Found".PHP_EOL;
                        }
                        // COLOR Patch End

                        

                        foreach ($middle_cards as $key => $value) {
                            $this->AnderBaharPlus_model->CreateMap($game_data[0]->id, $value->cards);
                        }
                        $this->AnderBaharPlus_model->CreateMap($game_data[0]->id, $alt_card);

                        // Give winning Amount to user
                        $multiply = ABPLUS_WIN_MULTIPLY;
                        $bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($bets);
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            echo "Winning Amount Given".PHP_EOL;
                        } else {
                            echo "No Winning Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['winning_red_black'] = $winning_red_black;
                        $update_data['winning_shape'] = $winning_shape;
                        $update_data['winning_ak'] = $winning_ak;
                        $update_data['winning_up_down'] = $winning_up_down;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        // $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+ '.(count($middle_cards)+5).'seconds'));
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.(round(count($middle_cards)/5)+2).' seconds'));
                        $update_data['random'] = $random;
                        $this->AnderBaharPlus_model->Update($update_data, $game_data[0]->id);
                    } else {
                        echo "No Game to Start".PHP_EOL;
                    }
                } else {
                    if (strtotime($game_data[0]->end_datetime)<=time()) {
                        $count = $this->Users_model->getOnlineUsers($room->id, 'ander_bahar_plus_room_id');
                        if ($count>0) {
                            // $card = $this->AnderBaharPlus_model->GetCards($limit)[0]->cards;
                            $this->AnderBaharPlus_model->Create($room->id, '');

                            echo 'Ander Baher Plus Game Created Successfully'.PHP_EOL;
                        } else {
                            echo 'No Online User Found'.PHP_EOL;
                        }
                    } else {
                        echo "No Game to End".PHP_EOL;
                    }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function ander_bahar_plus_create_socket()
    {
        echo 'Date '.date('Y-m-d H:i:s').PHP_EOL;
        $room_data = $this->AnderBaharPlus_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBaharPlus_model->getActiveGameOnTable($room->id);
                // print_r($game_data);
                if (!$game_data || $game_data[0]->status==1) {
                    // $card = $this->AnderBaharPlus_model->GetCards($limit)[0]->cards;
                    $this->AnderBaharPlus_model->Create($room->id, '');

                    echo 'First Ander Baher Plus Game Created Successfully'.PHP_EOL;
                    continue;
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function ander_bahar_plus_winner_socket()
    {
        // echo 'Date '.date('Y-m-d H:i:s').PHP_EOL;
        $room_data = $this->AnderBaharPlus_model->getRoom();
        // print_r($room_data);
        if ($room_data) {
            $limit = 1;
            foreach ($room_data as $key => $room) {
                $game_data = $this->AnderBaharPlus_model->getActiveGameOnTable($room->id);
                // print_r($game_data);
                if (!$game_data) {
                    continue;
                }

                if ($game_data[0]->status==0) {
                    // if ((strtotime($game_data[0]->added_date)+DRAGON_TIME_FOR_BET)<=time()) {

                        $min = 1;
                        $max = 30;

                        $TotalWinningAmount = 0;
                        $TotalBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id);

                        $AnderBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, ANDER);
                        $BaharBetAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, BAHAR);

                        $setting = $this->Setting_model->Setting();
                        $random = $setting->ander_bahar_plus_random;
                        if ($random==1) {
                            $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                        } else {
                            if ($AnderBetAmount>0 || $BaharBetAmount>0) {
                                $winning = ($AnderBetAmount>$BaharBetAmount) ? BAHAR : ANDER; //0=ander,1=bahar
                            } else {
                                $winning = RAND(ANDER, BAHAR); //0=ander,1=bahar
                            }
                        }

                        $exit = false;
                        do {
                            $number = rand($min, $max);
                            if ($winning==BAHAR) {
                                $exit = ($number % 2 != 0);
                            } else {
                                $exit = ($number % 2 == 0);
                            }
                        } while (!$exit);

                        // Main Card Patch
                        $main_card = $this->AnderBaharPlus_model->GetCards($limit)[0]->cards;
                        $update_data['main_card'] = $main_card;

                        $card_num = substr($main_card, 2);
                        $middle_cards = $this->AnderBaharPlus_model->GetCards($number, $card_num);

                        $alt_card = $this->AnderBaharPlus_model->GetCards($limit, $main_card, $card_num)[0]->cards;
                        // Main Card Patch End

                        // AtoK Patch
                        // $AceAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, ACE);
                        // $TwoAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 2);
                        // $ThreeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 3);
                        // $FourAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 4);
                        // $FiveAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 5);
                        // $SixAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 6);
                        // $SevenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 7);
                        // $EightAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 8);
                        // $NineAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 9);
                        // $TenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, 10);
                        // $JackAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, JACK);
                        // $QueenAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, QUEEN);
                        // $KingAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, KING);

                        // $arr = array();
                        // $arr['ACE'] = $AceAmount*TARGET_MULTIPLE;
                        // $arr['TWO'] = $TwoAmount*TARGET_MULTIPLE;
                        // $arr['THREE'] = $ThreeAmount*TARGET_MULTIPLE;
                        // $arr['FOUR'] = $FourAmount*TARGET_MULTIPLE;
                        // $arr['FIVE'] = $FiveAmount*TARGET_MULTIPLE;
                        // $arr['SIX'] = $SixAmount*TARGET_MULTIPLE;
                        // $arr['SEVEN'] = $SevenAmount*TARGET_MULTIPLE;
                        // $arr['EIGHT'] = $EightAmount*TARGET_MULTIPLE;
                        // $arr['NINE'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['TEN'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['JACK'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['QUEEN'] = $NineAmount*TARGET_MULTIPLE;
                        // $arr['KING'] = $NineAmount*TARGET_MULTIPLE;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];

                        $updown_multiply = ABPLUS_UP_DOWN_MULTIPLY;
                        $updown = '';

                        $number_multiply = ABPLUS_NUMBER_MULTIPLY;

                        switch ($card_num) {
                            case 'A':
                                $number = ACE;
                                break;
                            case 'K':
                                $number = KING;
                                break;
                            case 'Q':
                                $number = QUEEN;
                                break;
                            case 'J':
                                $number = JACK;
                                break;
                            default:
                                $number = $card_num;
                                break;
                        }

                        if($number<=6){
                            $updown = A_6;
                        }else if($number==7){
                            $updown = AB_SEVEN;
                        }else{
                            $updown = AB_8_K;
                        }

                        $winning_ak = $number;
                        $number_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $number);
                        if ($number_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($number_bets);
                            foreach ($number_bets as $key => $value) {
                                $amount = $value->amount*$number_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "AtoK Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No AtoK Winning Bet Found".PHP_EOL;
                        }
                        // AtoK Patch End

                        // UP_DOWN Patch
                        // $UpAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_8_K);
                        // $SevenAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_SEVEN);
                        // $DownAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, A_6);

                        // $arr = array();
                        // $arr['AB_8_K'] = $RedAmount*ABPLUS_UP_DOWN_MULTIPLY;
                        // $arr['AB_SEVEN'] = $BlackAmount*ABPLUS_UP_DOWN_MULTIPLY;
                        // $arr['A_6'] = $BlackAmount*ABPLUS_UP_DOWN_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];

                        $winning_up_down = $updown;
                        $updown_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $updown);
                        if ($updown_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($updown_bets);
                            foreach ($updown_bets as $key => $value) {
                                $amount = $value->amount*$updown_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "Up Down Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No Up Down Winning Bet Found".PHP_EOL;
                        }
                        // UP_DOWN Patch End

                        // SHAPE Patch
                        // $HeartAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_HEART);
                        // $SpadeAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_SPADE);
                        // $DiamondAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_DIAMOND);
                        // $ClubAmount = $this->Target_model->TotalBetAmount($game_data[0]->id, AB_CLUB);

                        // $arr = array();
                        // $arr['AB_HEART'] = $HeartAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_SPADE'] = $SpadeAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_DIAMOND'] = $DiamondAmount*ABPLUS_SHAPE_MULTIPLY;
                        // $arr['AB_CLUB'] = $ClubAmount*ABPLUS_SHAPE_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];
                        $card_shape = substr($main_card, 0, 2);
                        $shape_multiply = ABPLUS_SHAPE_MULTIPLY;
                        $shape = '';

                        switch ($card_shape) {
                            case 'RP':
                                $shape = AB_HEART;
                                break;
                            case 'BP':
                                $shape = AB_SPADE;
                                break;
                            case 'RS':
                                $shape = AB_DIAMOND;
                                break;
                            case 'BL':
                                $shape = AB_CLUB;
                                break;
                            default:
                                $shape = $card_shape;
                                break;
                        }

                        $winning_shape = $shape;
                        $shape_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $shape);
                        if ($shape_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($shape_bets);
                            foreach ($shape_bets as $key => $value) {
                                $amount = $value->amount*$shape_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "Shape Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No Shape Winning Bet Found".PHP_EOL;
                        }
                        // SHAPE Patch End

                        // COLOR Patch
                        $RedAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_RED);
                        $BlackAmount = $this->AnderBaharPlus_model->TotalBetAmount($game_data[0]->id, AB_BLACK);

                        $arr = array();
                        $arr['AB_RED'] = $RedAmount*ABPLUS_COLOR_MULTIPLY;
                        $arr['AB_BLACK'] = $BlackAmount*ABPLUS_COLOR_MULTIPLY;

                        // $min_arr = array_keys($arr, min($arr));
                        // $min = $min_arr[0];
                        $card_color = substr($main_card, 0, 1);
                        $color_multiply = ABPLUS_COLOR_MULTIPLY;
                        $color = '';

                        switch ($card_color) {
                            case 'R':
                                $color = AB_RED;
                                break;
                            case 'B':
                                $color = AB_BLACK;
                                break;
                            default:
                                $color = AB_RED;
                                break;
                        }

                        $winning_red_black = $color;
                        $color_bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $color);
                        if ($color_bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($color_bets);
                            foreach ($color_bets as $key => $value) {
                                $amount = $value->amount*$color_multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "Color Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No Color Winning Bet Found".PHP_EOL;
                        }
                        // COLOR Patch End

                        

                        foreach ($middle_cards as $key => $value) {
                            $this->AnderBaharPlus_model->CreateMap($game_data[0]->id, $value->cards);
                        }
                        $this->AnderBaharPlus_model->CreateMap($game_data[0]->id, $alt_card);

                        // Give winning Amount to user
                        $multiply = ABPLUS_WIN_MULTIPLY;
                        $bets = $this->AnderBaharPlus_model->ViewBet("", $game_data[0]->id, $winning);
                        if ($bets) {
                            $comission = $this->Setting_model->Setting()->admin_commission;
                            // print_r($bets);
                            foreach ($bets as $key => $value) {
                                $amount = $value->amount*$multiply;
                                $TotalWinningAmount += $amount;
                                $this->AnderBaharPlus_model->MakeWinner($value->user_id, $value->id, $amount, $comission, $game_data[0]->id);
                            }
                            // echo "Winning Amount Given".PHP_EOL;
                        } else {
                            // echo "No Winning Bet Found".PHP_EOL;
                        }

                        $update_data['status'] = 1;
                        $update_data['winning'] = $winning;
                        $update_data['winning_red_black'] = $winning_red_black;
                        $update_data['winning_shape'] = $winning_shape;
                        $update_data['winning_ak'] = $winning_ak;
                        $update_data['winning_up_down'] = $winning_up_down;
                        $update_data['total_amount'] = $TotalBetAmount;
                        $update_data['admin_profit'] = $TotalBetAmount - $TotalWinningAmount;
                        $update_data['updated_date'] = date('Y-m-d H:i:s');
                        // $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+ '.(count($middle_cards)+5).'seconds'));
                        $seconds = round(count($middle_cards)/3)+2;
                        $update_data['end_datetime'] = date('Y-m-d H:i:s', strtotime('+'.$seconds.' seconds'));
                        $update_data['random'] = $random;

                        if(!empty($update_data['admin_profit'])){
                            direct_admin_profit_statement(ABP,$update_data['admin_profit'],$game_data[0]->id);
                        }

                        $this->AnderBaharPlus_model->Update($update_data, $game_data[0]->id);
                        echo $seconds;
                    // } else {
                    //     echo "No Game to Start".PHP_EOL;
                    // }
                }
            }
        } else {
            echo 'No Rooms Available'.PHP_EOL;
        }
    }

    public function check_payment_status()
    {
        $this->load->model(['Coin_plan_model','DepositBonus_model']);

        $order_details = $this->Coin_plan_model->GetAllPendingPayment();
        foreach ($order_details as $key => $value) {
            $order_id = $value->id;
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.nowpayments.io/v1/payment/'.$value->razor_payment_id,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'x-api-key: '.$_ENV["PAYMENTAPI_KEY"]
              ),
            ));
            $response = curl_exec($curl);
            $response_arr = json_decode($response,true);
            //  print_r($response_arr['payment_status']);
            if(isset($response_arr['payment_status']) && $response_arr['payment_status']=='finished'){
                $this->Coin_plan_model->UpdateOrderPaymentStatus($order_id);
                $this->Users_model->UpdateWalletOrder($value->coin,$value->user_id);
                  
            $user = $this->Users_model->UserProfile($value->user_id);
            $setting = $this->Setting_model->Setting();
            $purchase_count = $this->Users_model->getNumberOfPurchase($value->user_id);
            if (($purchase_count==1)&& !empty($user[0]->referred_by)) {
                $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount, $value->user_id);
                if($setting->referral_amount>0){
                    direct_admin_profit_statement(REFERRAL_BONUS,-$setting->referral_amount,$user[0]->referred_by);
                    log_statement ($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount,0,0);
                }
               
            }
            if(INCOME_DEPOSIT_BONUS){
            switch ($purchase_count) {
                case 1:
                    depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '1st Deposit Bonus',1);
                    break;
                case 2:
                    depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '2nd Deposit Bonus',2);
                    break;
                case 3:
                    depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '3rd Deposit Bonus',3);
                    break;
                case 4:
                    depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '4th Deposit Bonus',4);
                    break;
                case 5:
                    depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '5th Deposit Bonus',5);
                    break;
                default:
                    break;
            }
        }

           
            for ($i=1; $i <= 10; $i++) {
                if ($user[0]->referred_by!=0) {
                    $level = 'level_'.$i;
                    $coins = (($value->coin*$setting->$level)/100);
                    if(!empty($coins)){
                        $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);
                        $log_data = [
                            'user_id' => $user[0]->referred_by,
                            'purchase_id' => $value->id,
                            'purchase_user_id' => $value->user_id,
                            'coin' => $coins,
                            'purchase_amount' => $order_details[0]->coins,
                            'level' => $i,
                        ];
    
                        $this->Users_model->AddPurchaseReferLog($log_data);
                    }                   
                    $user = $this->Users_model->UserProfile($user[0]->referred_by);
                } else {
                    break;
                }
            }

            if ($value->extra>0) {
                $extra_amount = $value->coin*($value->extra/100);
                $this->Users_model->UpdateWalletOrder($extra_amount, $value->user_id, bonus: 1);
                $this->Users_model->ExtraWalletLog($value->user_id, $extra_amount, 0);
            }

            // if ($category_amount>0) {
            //     $this->Users_model->ExtraWalletLog($value->user_id, $category_amount, 1);
            // }
            }
        }
    }

    public function check_payformee_payment_status()
    {
        $this->load->model(['Coin_plan_model','DepositBonus_model']);

        $order_details = $this->Coin_plan_model->GetAllPendingPaymentFromPayformee();
        foreach ($order_details as $key => $value) {
            $order_id = $value->id;
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://Payformee.com/api/check-order-status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'user_token' => 'bedfad31f39edc90bc5e60c7c2550749',
                    'order_id' =>  $order_id
                )), // Encode POST data
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            $response_arr = json_decode($response, true);

            // Check for successful API response
            if (isset($response_arr['status']) && $response_arr['status'] === true) {
                // Parse transaction status
                if (isset($response_arr['result']['txnStatus']) && $response_arr['result']['txnStatus'] === 'SUCCESS') {
                    // Payment is successful
                    $this->Coin_plan_model->UpdateOrderPaymentStatus($order_id);
                    $this->Users_model->UpdateWalletOrder($value->coin, $value->user_id);

                    log_statement($value->user_id, DEPOSIT, $value->coin,$value->id, 0);

                    $user = $this->Users_model->UserProfile($value->user_id);
                    $setting = $this->Setting_model->Setting();
                    $purchase_count = $this->Users_model->getNumberOfPurchase($value->user_id);

                    // Handle referral bonuses
                    if ($purchase_count == 1 && !empty($user[0]->referred_by)) {
                        $this->Users_model->UpdateWallet($user[0]->referred_by, $setting->referral_amount, $value->user_id);

                        if ($setting->referral_amount > 0) {
                            direct_admin_profit_statement(REFERRAL_BONUS, -$setting->referral_amount, $user[0]->referred_by);
                            log_statement($user[0]->referred_by, REFERRAL_BONUS, $setting->referral_amount, 0, 0);
                        }
                    }

                    // Handle deposit bonuses
                    if (INCOME_DEPOSIT_BONUS) {
                        switch ($purchase_count) {
                            case 1:
                                depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '1st Deposit Bonus', 1);
                                break;
                            case 2:
                                depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '2nd Deposit Bonus', 2);
                                break;
                            case 3:
                                depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '3rd Deposit Bonus', 3);
                                break;
                            case 4:
                                depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '4th Deposit Bonus', 4);
                                break;
                            case 5:
                                depositBonus($value->coin, $value->id, $value->user_id, $user[0]->referred_by, '5th Deposit Bonus', 5);
                                break;
                            default:
                                break;
                        }
                    }

                    // Handle referral levels
                    for ($i = 1; $i <= 10; $i++) {
                        if ($user[0]->referred_by != 0) {
                            if($i==1){
                                $percent_user = $this->Users_model->UserProfile($user[0]->referred_by);
                                $coins = (($value->coin * $percent_user[0]->referral_precent) / 100);
                            }
                            else{
                                $level = 'level_' . $i;
                                $coins = (($value->coin * $setting->$level) / 100);
                            }
                            

                            if (!empty($coins)) {
                                $this->Users_model->UpdateWalletOrder($coins, $user[0]->referred_by, bonus: 1);

                                $log_data = [
                                    'user_id' => $user[0]->referred_by,
                                    'purchase_id' => $value->id,
                                    'purchase_user_id' => $value->user_id,
                                    'coin' => $coins,
                                    'purchase_amount' => $value->coin,
                                    'level' => $i,
                                ];

                                $this->Users_model->AddPurchaseReferLog($log_data);

                                log_statement($user[0]->referred_by, REFERRAL_BONUS, $coins,$value->user_id, 0);
                            }

                            $user = $this->Users_model->UserProfile($user[0]->referred_by);
                        } else {
                            break;
                        }
                    }

                    // Handle extra amount
                    if ($value->extra > 0) {
                        $extra_amount = $value->coin * ($value->extra / 100);
                        $this->Users_model->UpdateWalletOrder($extra_amount, $value->user_id, bonus: 1);
                        $this->Users_model->ExtraWalletLog($value->user_id, $extra_amount, 0);
                    }
                } elseif ($response_arr['result']['txnStatus'] === 'PENDING') {
                    // Payment is pending
                    echo 'Transaction is pending.';
                } elseif ($response_arr['result']['txnStatus'] === 'FAILURE') {
                    // Payment is pending
                    $this->Coin_plan_model->UpdateOrderPaymentStatus($order_id,2);
                    echo 'Transaction is Failed.';
                }
            } else {
                // Handle failed API response
                echo 'Error: '. '--'.$order_id.'--' . ($response_arr['message'] ?? 'Unknown error occurred.');
            }

            // Close the cURL session
            curl_close($curl);

        }
    }
}