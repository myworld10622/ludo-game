<?php
$actual_link = (($this->input->server('HTTPS') === 'on') ? "https" : "http") . "://" . $this->input->server('HTTP_HOST') . $this->input->server('REQUEST_URI');
$final_url = str_replace(strtolower(base_url()), '', strtolower($actual_link));
$permission = $this->session->userdata("subadmin") ? explode(",", $this->session->userdata("subadmin")) : [];
// print_r($permission);
$role = $this->session->userdata("role");
?>
<div class="left side-menu">
    <div class="slimscroll-menu">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">

                <li><a href="<?= base_url('backend/dashboard/admin') ?>" class="waves-effect"><i class="ti-home"></i>
                        <span>Dashboard</span></a></li>

                <?php if ($role == SUPERADMIN || $role == SUBADMIN) { ?>
                    <?php if ($role == SUPERADMIN || in_array('APP_USER_MANAGEMENT', $permission)) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/user"), strpos($final_url, "backend/usercategory"), strpos($final_url, "backend/table"), strpos($final_url, "tablemaster/add"), strpos($final_url, "tablemaster/edit"), strpos($final_url, "backend/robotcards"), strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">

                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="ion ion-md-contact"></i>
                                <span>App User Mgmt.</span>
                            </a>

                            <ul class="sub-menu mm-collapse">
                                <li class="<?= (strpos($final_url, "backend/user") !== false) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/user') ?>" class="waves-effect">
                                        <span>Users</span>
                                    </a>
                                </li>

                                <li class="<?= (strpos($final_url, "backend/kyc") !== false) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Kyc') ?>" class="waves-effect">
                                        <span>KYC</span>
                                    </a>
                                </li>

                                <!-- Uncomment if needed -->
                                <!-- 
            <li class="<?= (strpos($final_url, "backend/BankDetails") !== false) ? 'mm-active' : '' ?>">
                <a href="<?= base_url('backend/BankDetails') ?>" class="waves-effect">
                    <span>Bank/Crypto Details</span>
                </a>
            </li>
            -->
                            </ul>
                        </li>
                    <?php } ?>


                    <?php if ($role == SUPERADMIN || in_array('ADMIN_USER_MANAGEMENT', $permission)) { ?>
                        <li
                            class="<?= (strpos($final_url, "backend/SubAdmin") !== false || strpos($final_url, "backend/Distributor") !== false || strpos($final_url, "backend/Agent") !== false) ? 'mm-active' : '' ?>">
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="ion ion-md-contact"></i>
                                <span>Admin Users Mgmt.</span>
                            </a>
                            <ul class="sub-menu mm-collapse">

                                <?php if (defined('SUB_ADMIN_MANAGEMENT') && SUB_ADMIN_MANAGEMENT == true && ($role == SUPERADMIN || in_array('ADMIN_USER_MANAGEMENT', $permission))) { ?>
                                    <li class="<?= (strpos($final_url, "backend/SubAdmin") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/SubAdmin') ?>" class="waves-effect">
                                            <span>Sub-Admin Mgmt.</span>
                                        </a>
                                    </li>
                                <?php } ?>

                                <?php if (defined('AGENT') && AGENT == true && ($role == SUPERADMIN || in_array('ADMIN_USER_MANAGEMENT', $permission))) { ?>
                                    <li class="<?= (strpos($final_url, "backend/Distributor") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Distributor') ?>" class="waves-effect">
                                            <span>Dist. Management</span>
                                        </a>
                                    </li>
                                <?php } ?>

                                <?php if (defined('AGENT') && AGENT == true && ($role == SUPERADMIN || in_array('ADMIN_USER_MANAGEMENT', $permission))) { ?>
                                    <li class="<?= (strpos($final_url, "backend/Agent") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Agent') ?>" class="waves-effect">
                                            <span>Agent Management</span>
                                        </a>
                                    </li>
                                <?php } ?>

                            </ul>
                        </li>
                    <?php } ?>


                    <?php if ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission)) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/report")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Payment Mgmt.</span>
                            </a>

                            <ul class="sub-menu mm-collapse">
                                <?php if (WITHDRAWAL_LOG == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/DepositPercentage') ?>" class="waves-effect">
                                            <span>Deposit Percentage</span>
                                        </a></li>
                                <?php } ?>

                                <?php if (WITHDRAWAL_LOG == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/Gateway/manual') ?>" class="waves-effect"><span>Manual
                                                Gateway</span></a></li>
                                <?php } ?>

                                <?php if (WITHDRAWAL_LOG == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/Deposit') ?>" class="waves-effect"><span>Deposit</span></a></li>
                                <?php } ?>

                                <?php if (CHIPS_MANAGEMENT == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/chips') ?>" class="waves-effect">
                                            <span>Deposit Chips Mgmt.</span></a></li>
                                <?php } ?>

                                <?php if (PURCHASE_HISTORY == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/Purchase') ?>" class="waves-effect"><span>Deposit
                                                History</span></a></li>
                                <?php } ?>

                                <?php if (REEDEM_MANAGEMENT == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/WithdrawalLog/ReedemNow') ?>" class="waves-effect">
                                            <span>Withdraw Chips Mgmt.</span></a></li>
                                <?php } ?>

                                <?php if (WITHDRAWAL_LOG == true && ($role == SUPERADMIN || in_array('PAYMENT_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/WithdrawalLog') ?>" class="waves-effect"><span>Withdrawal
                                                History</span></a></li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>

                    <li class="menu-title" style="color:white">Games</li>

                    <?php if ((AVIATOR == true || AVIATOR_VERTICAL == true) && ($role == SUPERADMIN || in_array('AVIATOR', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/dragontiger")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Aviator</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "dragontiger"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Aviator') ?>" class="waves-effect">
                                        <span>Aviator History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (ANDER_BAHAR == true && ($role == SUPERADMIN || in_array('ANDER_BAHAR', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/andarbahar")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Andar Bahar</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "andarbahar"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/AnderBahar') ?>" class="waves-effect">
                                        <span>Andar Bahar History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (ANIMAL_ROULETTE == true && ($role == SUPERADMIN || in_array('ANIMAL_ROULETTE', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/animalroulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Animal Roulette</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "animalroulette"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/AnimalRoulette') ?>" class="waves-effect">
                                        <span>Animal Roulette History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (ICON_ROULETTE == true && ($role == SUPERADMIN || in_array('ICON_ROULETTE', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/iconroulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Icon Roulette</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "iconroulette"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/IconRoulette') ?>" class="waves-effect">
                                        <span>Icon Roulette History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (BACCARAT == true && ($role == SUPERADMIN || in_array('BACCARAT', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/baccarat")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Baccarat</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "baccarat"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Baccarat') ?>" class="waves-effect">
                                        <span>Baccarat History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (CAR_ROULETTE == true && ($role == SUPERADMIN || in_array('CAR_ROULETTE', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/carroulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Car Roulette</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "carroulette"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/CarRoulette') ?>" class="waves-effect">
                                        <span>Car Roulette History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if ((COLOR_PREDICTION == true || COLOR_PREDICTION_VERTICAL == true) && ($role == SUPERADMIN || in_array('COLOR_PREDICTION', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/ColorPrediction")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Color Prediction</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "colorprediction"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/ColorPrediction') ?>" class="waves-effect">
                                        <span>Color Prediction 15 Sec</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "colorPrediction1Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/colorPrediction1Min') ?>" class="waves-effect">
                                        <span>Color Prediction 1 Min</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "colorPrediction3Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/colorPrediction3Min') ?>" class="waves-effect">
                                        <span>Color Prediction 3 Min</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "colorPrediction5Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/colorPrediction5Min') ?>" class="waves-effect">
                                        <span>Color Prediction 5 Min</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (DRAGON_TIGER == true && ($role == SUPERADMIN || in_array('DRAGON_TIGER', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/dragontiger")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Dragon Tiger</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "dragontiger"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/DragonTiger') ?>" class="waves-effect">
                                        <span>Dragon Tiger History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (HEAD_TAILS == true && ($role == SUPERADMIN || in_array('HEAD_TAILS', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/headtails")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Head & Tail</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "headtails"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/HeadTails') ?>" class="waves-effect">
                                        <span>Head & Tail History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (JACKPOT == true && ($role == SUPERADMIN || in_array('JACKPOT_TEENPATTI', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/Jackpot")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Jackpot TeenPatti</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Jackpot') ?>" class="waves-effect">
                                        <span>Jackpot TeenPatti History</span></a>
                                </li>
                            </ul>
                        </li>

                    <?php } ?>

                    <?php if (JHANDI_MUNDA == true && ($role == SUPERADMIN || in_array('JHANDI_MUNDA', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/jhandimunda")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Jhandi Munda</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "jhandimunda"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/JhandiMunda') ?>" class="waves-effect">
                                        <span>Jhandi Munda History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (LUDO == true && ($role == SUPERADMIN || in_array('LUDO', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "ludotablemaster"), strpos($final_url, "ludohistory"), strpos($final_url, "ludotablemaster/add"), strpos($final_url, "ludotablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Ludo</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "ludotablemaster"), strpos($final_url, "ludotablemaster/add"), strpos($final_url, "ludotablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/ludoTableMaster') ?>">Ludo Table Master</a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "ludohistory"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/LudoHistory') ?>">Ludo History</a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (POKER == true && ($role == SUPERADMIN || in_array('POKER', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/poker")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Poker</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "poker"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/PokerMaster') ?>" class="waves-effect">
                                        <span>Poker Master Table</span></a>
                                </li>
                            </ul>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "poker"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Pokers') ?>" class="waves-effect">
                                        <span>Poker History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (RED_VS_BLACK == true && ($role == SUPERADMIN || in_array('RED_VS_BLACK', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/redblack")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Red Vs Black</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "redblack"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/RedBlack') ?>" class="waves-effect">
                                        <span>Red Vs Black History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (ROULETTE == true && ($role == SUPERADMIN || in_array('ROULETTE', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/roulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Roulette</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "roulette"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Roulette') ?>" class="waves-effect">
                                        <span>Roulette History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (POINT_RUMMY == true && ($role == SUPERADMIN || in_array('RUMMY_POINT', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/rummy")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Rummy Point</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/rtummyablemaster")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/RummyTableMaster') ?>" class="waves-effect">
                                        <span>Rummy Point Table Master</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "rummy"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Rummy') ?>" class="waves-effect">
                                        <span>Rummy Point History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (RUMMY_POOL == true && ($role == SUPERADMIN || in_array('RUMMY_POOL', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/rummypool"), strpos($final_url, "backend/rummypool"), strpos($final_url, "pooltablemaster/add"), strpos($final_url, "pooltablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Rummy Pool</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/rummypool")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/PoolTableMaster') ?>" class="waves-effect"><i
                                            class="ion ion-md-contact"></i>
                                        <span>Rummy Pool Table Master</span></a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "rummypool"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/RummyPool') ?>" class="waves-effect"><i
                                            class="ion ion-md-list-box"></i>
                                        <span>Rummy Pool History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (RUMMY_DEAL == true && ($role == SUPERADMIN || in_array('RUMMY_DEAL', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/rummydeal")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Rummy Deal</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/rummydeal")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/DealTableMaster') ?>" class="waves-effect"><i
                                            class="ion ion-md-contact"></i>
                                        <span>Rummy Deal Table Master</span></a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "rummydeal"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/RummyDeal') ?>" class="waves-effect">
                                        <span>Rummy Deal History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (RUMMY_TOURNAMENT == true && ($role == SUPERADMIN || in_array('RUMMY_TOURNAMENT', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/TournamentManagement")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Rummy Tournament</span>
                            </a>
                            <ul class="sub-menu mm-collapse">

                                <li
                                    class="<?= (array_filter([strpos($final_url, "deposit_bonus"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/TournamentTypes') ?>" class="waves-effect">
                                        <span>Rummy Tournament Types</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/TournamentMaster') ?>" class="waves-effect">
                                        <span>Rummy Tournament Master</span></a>
                                </li>

                            </ul>
                        </li>
                    <?php } ?>


                    <?php if (SEVEN_UP_DOWN == true && ($role == SUPERADMIN || in_array('SEVEN_UP_DOWN', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/sevenup")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Seven Up Down</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "sevenup"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/SevenUp') ?>" class="waves-effect">
                                        <span>Seven Up Down History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (SLOT == true && ($role == SUPERADMIN || in_array('SLOT_GAME', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/slotgame")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Slot Game</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "slotgame"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/SlotGame') ?>" class="waves-effect">
                                        <span>Slot Game History</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>


                    <?php if (TEENPATTI == true && ($role == SUPERADMIN || in_array('TEENPATTI', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "tablemaster"), strpos($final_url, "backend/game"), strpos($final_url, "backend/table"), strpos($final_url, "tablemaster/add"), strpos($final_url, "tablemaster/edit"), strpos($final_url, "backend/robotcards"), strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>TeenPatti</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "tablemaster"), strpos($final_url, "tablemaster/add"), strpos($final_url, "tablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/tableMaster') ?>">Teen Patti Table Master</a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/game")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Game') ?>" class="waves-effect">
                                        <span>Teenpatti History</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/table') ?>" class="waves-effect"></i>
                                        <span>Watch Table Teenpatti</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/robotcards")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/RobotCards') ?>" class="waves-effect"></i>
                                        <span>Robot Cards</span></a>
                                </li>
                            </ul>
                        </li>

                    <?php } ?>

                    <li class="menu-title" style="color:white">Others</li>

                    <?php if (REPORT == true && ($role == SUPERADMIN || in_array('REPORT_MANAGEMENT', $permission))) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/report")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Report Mgmt.</span>
                            </a>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/User/AllLadgerReports') ?>" class="waves-effect">
                                        <span>Ladger Report</span></a>
                                </li>
                            </ul>

                        </li>
                    <?php } ?>

                    <?php if (NOTIFICATION == true && ($role == SUPERADMIN || in_array('NOTIFICATION', $permission))) { ?>
                        <li><a href="<?= base_url('backend/notification') ?>" class="waves-effect"><i
                                    class="ion ion-md-list-box"></i> <span>Notification</span></a></li>
                    <?php } ?>

                    <!-- <?php if (SETTING == true && ($role == SUPERADMIN || in_array('SETTING', $permission))) { ?>
                <li><a href="<?= base_url('backend/setting') ?>" class="waves-effect"><i
                            class="ion ion-md-list-box"></i> <span>Setting</span></a></li>
                <?php } ?> -->

                    <?php if ($role == SUPERADMIN || in_array('APPBANNER', $permission) || in_array('MASTER_MANAGEMENT', $permission) || in_array('IMAGE_NOTIFICATION', $permission) || in_array('INCOME_DEPOSIT_BONUS', $permission) || in_array('INCOME_BET_BONUS', $permission) || in_array('INCOME_DAILY_SALARY_BONUS', $permission) || in_array('INCOME_DAILY_ATTENDANCE_BONUS', $permission) || in_array('WELCOME_BONUS', $permission)) { ?>

                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/report")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ti-layout-grid2-alt"></i>
                                <span>Settings</span>
                            </a>

                            <ul class="sub-menu mm-collapse">
                                <?php if (SETTING == true && ($role == SUPERADMIN || in_array('SETTING', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/setting/appConfiguration') ?>" class="waves-effect">
                                            <span>Game Configuration</span>
                                        </a>
                                    </li>
                                    <li><a href="<?= base_url('backend/setting/systemConfiguration') ?>" class="waves-effect">
                                            <span>System Configuration</span>
                                        </a>
                                    </li>
                                    <li><a href="<?= base_url('backend/setting/gamePermissions') ?>" class="waves-effect">
                                            <span>Game Permission</span>
                                        </a>
                                    </li>
                                <?php } ?>
                                <?php if (APPBANNER == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/AppBanner') ?>" class="waves-effect">
                                            <span>App Banner</span></a></li>
                                <?php } ?>
                                <?php if (BANNER == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/banner') ?>" class="waves-effect">
                                            <span>Website Banner</span></a></li>
                                <?php } ?>
                                <?php if (IMAGE_NOTIFICATION == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/ImageNotification') ?>" class="waves-effect"> <span>Welcome
                                                App Image</span></a></li>
                                    <?php if (INCOME_DEPOSIT_BONUS == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                        <li
                                            class="<?= (array_filter([strpos($final_url, "deposit_bonus"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                            <a href="<?= base_url('backend/DepositBonus') ?>" class="waves-effect">
                                                <span>Deposit Bonus</span></a>
                                        </li>
                                    <?php } ?>
                                    <?php if (INCOME_BET_BONUS == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                        <li
                                            class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                            <a href="<?= base_url('backend/Bet_income_master') ?>" class="waves-effect">
                                                <span>Bet Income Bonus</span></a>
                                        </li>
                                    <?php } ?>

                                    <?php if (INCOME_DAILY_SALARY_BONUS == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                        <li
                                            class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                            <a href="<?= base_url('backend/DailySalaryBonusMaster') ?>" class="waves-effect">
                                                <span>Daily Salary Bonus</span></a>
                                        </li>
                                    <?php } ?>
                                    <?php if (INCOME_DAILY_ATTENDANCE_BONUS == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                        <li
                                            class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                            <a href="<?= base_url('backend/DailyAttendenceBonusMaster') ?>" class="waves-effect">
                                                <span>Daily Attendance Bonus</span></a>
                                        </li>

                                    <?php } ?>

                                <?php } ?>
                                <?php if (WELCOME_BONUS == true && ($role == SUPERADMIN || in_array('MASTER_MANAGEMENT', $permission))) { ?>
                                    <li><a href="<?= base_url('backend/welcomebonus') ?>" class="waves-effect"> <span>Welcome
                                                Bonus</span></a></li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>

                    <?php if (($role == SUPERADMIN || in_array('TICKET', $permission))) {
                        $CI = &get_instance();
                        $CI->load->model('Support_model');
                        $ticket_total = $CI->Support_model->TicketCount();
                    ?>
                        <li><a href="<?= base_url('backend/Tickets') ?>" class="waves-effect"><i
                                    class="ion ion-md-list-box"></i> <span>Tickets</span><span
                                    class="badge rounded-pill bg-danger float-end"><?= $ticket_total ?></span></a></li>
                    <?php } ?>
                <?php } else if ($role == DISTRIBUTOR) { ?>
                    <li><a href="<?= base_url('backend/Agent') ?>" class="waves-effect"><i class="ion ion-md-contact"></i>
                            <span>Agent Management</span></a></li>
                    <li>
                        <a href="<?= base_url('backend/Distributor/PaymentHistory') ?>" class="waves-effect"> <i
                                class="ion ion-md-list-box"></i>
                            <span>Payment Logs</span></a>
                    </li>
                    <!-- Payment Gateways -->
                            <li class="<?= (strpos($final_url, "backend/Gateway/distributorGateway") !== false) ? 'mm-active' : '' ?>">
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="ti-layout-grid2-alt"></i>
                                    <span>Payment Gateways</span>
                                </a>
                                <ul class="sub-menu mm-collapse">
                                    <li
                                        class="<?= (strpos($final_url, "backend/Gateway/distributorGateway") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Gateway/distributorGateway') ?>" class="waves-effect">
                                            <span>All Gateways</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- Withdraw Channel -->
                            <li
                                class="<?= (strpos($final_url, "backend/Gateway/distributorGatewayWithdraw") !== false) ? 'mm-active' : '' ?>">
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="ti-layout-grid2-alt"></i>
                                    <span>Withdraw Channel</span>
                                </a>
                                <ul class="sub-menu mm-collapse">
                                    <li
                                        class="<?= (strpos($final_url, "backend/Gateway/distributorGatewayWithdraw") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Gateway/distributorGatewayWithdraw') ?>" class="waves-effect">
                                            <span>All Gateways Withdraw</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="<?= (strpos($final_url, "backend/Deposit/add") !== false) ? 'mm-active' : '' ?>">
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="ti-layout-grid2-alt"></i>
                                    <span>Deposits </span>
                                </a>
                                <ul class="sub-menu mm-collapse">
                                    <li
                                        class="<?= (strpos($final_url, "backend/Deposit/add") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Deposit/add') ?>" class="waves-effect">
                                            <span>Request Deposit</span>
                                        </a>
                                    </li>
                                    <li
                                        class="<?= (strpos($final_url, "backend/Deposit/indexDistributor") !== false) ? 'mm-active' : '' ?>">
                                        <a href="<?= base_url('backend/Deposit/indexDistributor') ?>" class="waves-effect">
                                            <span>Deposit From Agent</span>
                                        </a>
                                    </li>
                                </ul>
                                
                            </li>

                <?php } else if ($role == ROLEAGENT) { ?>
                    <!-- ################### Agent login Sidebar ############# -->
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/AgentUser")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/AgentUser') ?>" class="waves-effect">
                            <i class="ion ion-md-contact"></i>
                            <span>Users</span></a>
                    </li>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/chats")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/Chats') ?>" class="waves-effect">
                            <i class="ion ion-md-contact"></i>
                            <span>Chats</span></a>
                    </li>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/AgentUserPaymentLog")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/AgentUserPaymentLog') ?>" class="waves-effect">
                            <i class="ion ion-md-contact"></i>
                            <span>Payment Logs</span></a>
                    </li>
                    <li><a href="<?= base_url('backend/Purchase') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i> <span>Purchase History</span></a></li>
                    <li><a href="<?= base_url('backend/WithdrawalLog') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Withdrawal Log</span></a></li>

                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/agent/Settings")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/agent/Settings') ?>" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span>Settings</span></a>
                    </li>
                    <!-- Payment Gateways -->
                    <li class="<?= (strpos($final_url, "backend/Gateway/agentGateway") !== false) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Payment Gateways</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (strpos($final_url, "backend/Gateway/agentGateway") !== false) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Gateway/agentGateway') ?>" class="waves-effect">
                                    <span>All Gateways</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Withdraw Channel -->
                    <li
                        class="<?= (strpos($final_url, "backend/Gateway/agentGatewayWithdraw") !== false) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Withdraw Channel</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (strpos($final_url, "backend/Gateway/agentGatewayWithdraw") !== false) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Gateway/agentGatewayWithdraw') ?>" class="waves-effect">
                                    <span>All Gateways Withdraw</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="<?= (strpos($final_url, "backend/Deposit/addToDistributor") !== false) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Deposits </span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (strpos($final_url, "backend/Deposit/addToDistributor") !== false) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Deposit/addToDistributor') ?>" class="waves-effect">
                                    <span>Request Deposit</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>

<div class="content-page">
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title"><?= $title ?></h4>

                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-md-block">
                            <?php

                            if (isset($SideBarbutton) && isset($SideBarbutton[1])) {
                            ?>

                                <a href="<?= base_url($SideBarbutton[0]) ?>"
                                    class="btn btn-primary btn-lg btn-dashboard custom-btn">
                                    <?= $SideBarbutton[1] ?></a>

                            <?php
                            } ?>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->