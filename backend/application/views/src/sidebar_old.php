<?php
$actual_link = (($this->input->server('HTTPS') === 'on') ? "https" : "http") . "://" . $this->input->server('HTTP_HOST') . $this->input->server('REQUEST_URI');
$final_url = str_replace(strtolower(base_url()), '', strtolower($actual_link));
$permission = $this->session->userdata("subadmin") ? explode(",", $this->session->userdata("subadmin")) : [];
$role = $this->session->userdata("role");
?>
<!-- Top Bar End -->
<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">

                <li><a href="<?= base_url('backend/dashboard/admin') ?>" class="waves-effect"><i class="ti-home"></i>
                        <span>Dashboard</span></a></li>
                <li class="menu-title">Content</li>
                <?php if (USER_MANAGEMENT == true && ($role == 2 || $role == SUPERADMIN || in_array('USER_MANAGEMENT', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/user"), strpos($final_url, "backend/usercategory"), strpos($final_url, "backend/table"), strpos($final_url, "tablemaster/add"), strpos($final_url, "tablemaster/edit"), strpos($final_url, "backend/robotcards"), strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <?php if ($role != 2) { ?>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="ion ion-md-contact"></i>
                                <span>Users Management</span>
                            </a>
                            <ul class="sub-menu mm-collapse">

                                <!-- <li
                                    class="<?= (array_filter([strpos($final_url, "backend/AgentUser")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/AgentUser') ?>" class="waves-effect">
                                        <span>Agent Users</span></a>
                                </li> -->

                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/user")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/user') ?>" class="waves-effect">
                                        <span>Users</span></a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/usercategory")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/UserCategory') ?>" class="waves-effect">
                                        <span>User Category</span></a>
                                </li>

                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/kyc")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Kyc') ?>" class="waves-effect"></i>
                                        <span>Kyc</span></a>
                                </li>
                                <li
                                    class="<?= (array_filter([strpos($final_url, "backend/BankDetails")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/BankDetails') ?>" class="waves-effect"> <span>Bank/Crypto
                                            Details</span></a>
                                </li>

                            </ul>
                        <?php } ?>
                        <?php if ($role == 2) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/AgentUserPaymentLog")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="<?= base_url('backend/AgentUserPaymentLog') ?>" class="waves-effect">
                                <i class="ion ion-md-contact"></i>
                                <span>Payment Logs</span></a>
                        </li>
                    <?php } ?>
                    <?php if ($role == 2) { ?>
                        <li><a href="<?= base_url('backend/Purchase') ?>" class="waves-effect"><i
                                    class="ion ion-md-contact"></i> <span>Purchase History</span></a></li>

                    <?php } ?>
                    <?php if (WITHDRAWAL_LOG == true && ($role == 2)) { ?>
                        <li><a href="<?= base_url('backend/WithdrawalLog') ?>" class="waves-effect"><i
                                    class="ion ion-md-list-box"></i> <span>Withdrawal Log</span></a></li>
                    <?php } ?>
                    <?php if ($role == 2) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/chats")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="<?= base_url('backend/Chats') ?>" class="waves-effect">
                                <i class="ion ion-md-contact"></i>
                                <span>Chats</span></a>
                        </li>
                    <?php } ?>
                    <?php if ($role == 2) { ?>
                        <li
                            class="<?= (array_filter([strpos($final_url, "backend/agent/Settings")], 'is_numeric')) ? 'mm-active' : '' ?>">
                            <a href="<?= base_url('backend/agent/Settings') ?>" class="waves-effect">
                                <i class="ion ion-md-list-box"></i>
                                <span>Settings</span></a>
                        </li>
                    <?php } ?>
                    </li>


                <?php } ?>

                <?php if (SUB_ADMIN_MANAGEMENT == true && ($role == SUPERADMIN || in_array('SUB_ADMIN_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/SubAdmin') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i>
                            <span>SubAdmin Management</span></a></li>
                <?php } ?>

                <?php if (AGENT == true && ($role == SUPERADMIN || $role == DISTRIBUTOR || in_array('SUB_ADMIN_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Agent') ?>" class="waves-effect"><i class="ion ion-md-contact"></i>
                            <span>Agent Management</span></a></li>
                <?php } ?>
                <?php if ($role == DISTRIBUTOR) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/distributor/PaymentHistory")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/Distributor/PaymentHistory') ?>" class="waves-effect">
                            <span>Payment Logs</span></a>
                    </li>
                <?php } ?>
                <?php if (AGENT == true && ($role == SUPERADMIN || in_array('DISTRIBUTOR', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Distributor') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i>
                            <span>Dist. Management</span></a></li>
                <?php } ?>

                <?php if (WITHDRAWL_DASHBOARD == true && ($role == SUPERADMIN || in_array('WITHDRAWL_DASHBOARD', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "withdrawldashboard")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="<?= base_url('backend/WithdrawlDashboard') ?>" class="waves-effect"><i class="ti-home"></i>
                            <span>Withdrawl Dashboard</span></a>
                    </li>
                <?php } ?>

                <!-- <?php if (USER_CATEGORY == true) { ?>
              
                <?php } ?> -->


                <?php if (BANNER == true && ($role == SUPERADMIN || in_array('BANNER', $permission))) { ?>
                    <li><a href="<?= base_url('backend/banner') ?>" class="waves-effect"><i class="ion ion-md-contact"></i>
                            <span>Banner</span></a></li>
                <?php } ?>

                <?php if (APPBANNER == true && ($role == SUPERADMIN || in_array('APPBANNER', $permission))) { ?>
                    <li><a href="<?= base_url('backend/AppBanner') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i>
                            <span>App Banner</span></a></li>
                <?php } ?>

                <?php if (TEENPATTI == true && ($role == SUPERADMIN || in_array('TEENPATTI', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "tablemaster"), strpos($final_url, "backend/game"), strpos($final_url, "backend/table"), strpos($final_url, "tablemaster/add"), strpos($final_url, "tablemaster/edit"), strpos($final_url, "backend/robotcards"), strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Teen Patti Mgmt.</span>
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
                                class="<?= (array_filter([strpos($final_url, "backend/table")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Jackpot') ?>" class="waves-effect"> <span>Jackpot
                                        History</span></a>
                            </li>
                            <li
                                class="<?= (array_filter([strpos($final_url, "backend/robotcards")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/RobotCards') ?>" class="waves-effect"></i>
                                    <span>Robot Cards</span></a>
                            </li>
                        </ul>
                    </li>



                <?php } ?>

                <?php if (LUDO == true && ($role == SUPERADMIN || in_array('LUDO', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "ludotablemaster"), strpos($final_url, "ludohistory"), strpos($final_url, "ludotablemaster/add"), strpos($final_url, "ludotablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Ludo Management</span>
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

                <?php if (RUMMY_POOL == true && ($role == SUPERADMIN || in_array('RUMMY_POOL', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/rummypool"), strpos($final_url, "backend/rummypool"), strpos($final_url, "pooltablemaster/add"), strpos($final_url, "pooltablemaster/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Rummy Pool Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "backend/rummypool")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/PoolTableMaster') ?>" class="waves-effect"><i
                                        class="ion ion-md-contact"></i>
                                    <span>Pool Table Master</span></a>
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
                <?php if (POINT_RUMMY == true && ($role == SUPERADMIN || in_array('POINT_RUMMY', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/rummy")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Rummy Management</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "backend/rtummyablemaster")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/RummyTableMaster') ?>" class="waves-effect">
                                    <span>Point Table Master</span></a>
                            </li>
                            <li
                                class="<?= (array_filter([strpos($final_url, "rummy"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Rummy') ?>" class="waves-effect">
                                    <span>Rummy Point History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (RUMMY_DEAL == true && ($role == SUPERADMIN || in_array('RUMMY_DEAL', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/rummydeal")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Rummy Deal Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
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
                        class="<?= (array_filter([strpos($final_url, "backend/rummytournament")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Rummy Tournament</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "rummytournament"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/RummyTournaMent') ?>" class="waves-effect">
                                    <span>Tournament</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (ANDER_BAHAR == true && ($role == SUPERADMIN || in_array('ANDER_BAHAR', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/andarbahar")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Andar Bahar Mgmt.</span>
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
                <?php if (BACCARAT == true && ($role == SUPERADMIN || in_array('BACCARAT', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/baccarat")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Baccarat Mgmt.</span>
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
                <?php if (DRAGON_TIGER == true && ($role == SUPERADMIN || in_array('DRAGON_TIGER', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/dragontiger")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Dragon Tiger Mgmt.</span>
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

                <?php if ((AVIATOR == true || AVIATOR_VERTICAL == true) && ($role == SUPERADMIN || in_array('AVIATOR', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/dragontiger")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Aviator Mgmt.</span>
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
                <?php if (LOTTERY == true && ($role == SUPERADMIN || in_array('LOTTERY', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/dragontiger")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Lottery Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "dragontiger"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Lottery') ?>" class="waves-effect">
                                    <span>Lottery History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (TARGET == true && ($role == SUPERADMIN || in_array('TARGET', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/Target")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Target Management</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "Target"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Target') ?>" class="waves-effect">
                                    <span>Target History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (ANDER_BAHAR_PLUS == true && ($role == SUPERADMIN || in_array('ANDER_BAHAR_PLUS', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/AnderBaharPlus")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>AnderBaharPlus Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "AnderBaharPlus"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/AnderBaharPlus') ?>" class="waves-effect">
                                    <span>AnderBaharPlus History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (SEVEN_UP_DOWN == true && ($role == SUPERADMIN || in_array('SEVEN_UP_DOWN', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/sevenup")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Seven Up Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "sevenup"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/SevenUp') ?>" class="waves-effect">
                                    <span>Seven Up History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (CAR_ROULETTE == true && ($role == SUPERADMIN || in_array('CAR_ROULETTE', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/carroulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Car Roulette Mgmt.</span>
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
                            <span>Color Prediction 15 Sec Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "colorprediction"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/ColorPrediction') ?>" class="waves-effect">
                                    <span>Color Prediction 15 Sec History</span></a>
                            </li>
                        </ul>
                    </li>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/colorPrediction1Min")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Color Prediction 1 Min Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "colorPrediction1Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/colorPrediction1Min') ?>" class="waves-effect">
                                    <span>Color Prediction 1 Min History</span></a>
                            </li>
                        </ul>
                    </li>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/colorPrediction3Min")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Color Prediction 3 Min Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "colorPrediction3Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/colorPrediction3Min') ?>" class="waves-effect">
                                    <span>Color Prediction 3 Min History</span></a>
                            </li>
                        </ul>
                    </li>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/colorPrediction5Min")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Color Prediction 5 Min Mgmt.</span>
                        </a>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "colorPrediction5Min"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/colorPrediction5Min') ?>" class="waves-effect">
                                    <span>Color Prediction 5 Min History</span></a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (ANIMAL_ROULETTE == true && ($role == SUPERADMIN || in_array('ANIMAL_ROULETTE', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/animalroulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Animal Roulette Mgmt.</span>
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

                <?php if (HEAD_TAILS == true && ($role == SUPERADMIN || in_array('HEAD_TAILS', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/headtails")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Head & Tail Mgmt.</span>
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

                <?php if (RED_VS_BLACK == true && ($role == SUPERADMIN || in_array('RED_VS_BLACK', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/redblack")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Red Vs Black Mgmt.</span>
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

                <?php if (JHANDI_MUNDA == true && ($role == SUPERADMIN || in_array('JHANDI_MUNDA', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/jhandimunda")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Jhandi Munda Mgmt.</span>
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

                <?php if (ROULETTE == true && ($role == SUPERADMIN || in_array('ROULETTE', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/roulette")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Roulette Mgmt.</span>
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

                <?php if (POKER == true && ($role == SUPERADMIN || in_array('POKER', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/poker")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Poker Mgmt.</span>
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

                <?php if (CHIPS_MANAGEMENT == true && ($role == SUPERADMIN || in_array('CHIPS_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/chips') ?>" class="waves-effect"><i class="ion ion-md-contact"></i>
                            <span>Chips Management</span></a></li>
                <?php } ?>

                <!-- <?php if (GIFT_MANAGEMENT == true && ($role == SUPERADMIN || in_array('GIFT_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/gift') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i><span>Gift Management</span></a></li>
                <?php } ?> -->

                <?php if (PURCHASE_HISTORY == true && ($role == SUPERADMIN || in_array('PURCHASE_HISTORY', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Purchase') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i> <span>Purchase History</span></a></li>
                <?php } ?>

                <!-- <?php if (LEAD_BOARD == true && ($role == SUPERADMIN || in_array('LEAD_BOARD', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Game/Leaderboard') ?>" class="waves-effect"><i
                                class="ion ion-md-contact"></i> <span>Leadboard</span></a></li>
                <?php } ?> -->

                <?php if (NOTIFICATION == true && ($role == SUPERADMIN || in_array('NOTIFICATION', $permission))) { ?>
                    <li><a href="<?= base_url('backend/notification') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Notification</span></a></li>
                <?php } ?>


                <?php if (IMAGE_NOTIFICATION == true && ($role == SUPERADMIN || in_array('IMAGE_NOTIFICATION', $permission))) { ?>
                    <li><a href="<?= base_url('backend/ImageNotification') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Image Notification</span></a></li>
                <?php } ?>

                <?php if (WELCOME_BONUS == true && ($role == SUPERADMIN || in_array('WELCOME_BONUS', $permission))) { ?>
                    <li><a href="<?= base_url('backend/welcomebonus') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Welcome Bonus</span></a></li>
                <?php } ?>

                <?php if (($role == SUPERADMIN || in_array('REEDEM_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Tickets') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Tickets</span></a></li>
                <?php } ?>
                <?php if (REEDEM_MANAGEMENT == true && ($role == SUPERADMIN || in_array('REEDEM_MANAGEMENT', $permission))) { ?>
                    <li><a href="<?= base_url('backend/WithdrawalLog/ReedemNow') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Reedem Management</span></a></li>
                <?php } ?>

                <?php if (WITHDRAWAL_LOG == true && ($role == SUPERADMIN || in_array('WITHDRAWAL_LOG', $permission))) { ?>
                    <li><a href="<?= base_url('backend/WithdrawalLog') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Withdrawal Log</span></a></li>
                <?php } ?>

                <?php if (REPORT == true && ($role == SUPERADMIN || in_array('REPORT', $permission))) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/report")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Report Management</span>
                        </a>
                        <!-- <ul class="sub-menu mm-collapse"> -->
                            <!-- <li
                                class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Report') ?>" class="waves-effect">
                                    <span>Recharge</span></a>
                            </li> -->
                        <!-- </ul> -->
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/User/AllLadgerReports') ?>" class="waves-effect">
                                    <span>Ladger Report</span></a>
                            </li>
                        </ul>

                    </li>
                <?php } ?>

                <?php if ($role == SUPERADMIN) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/report")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Master Management</span>
                        </a>
                        <?php if (INCOME_DEPOSIT_BONUS == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "deposit_bonus"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/DepositBonus') ?>" class="waves-effect">
                                        <span>Deposit Bonus</span></a>
                                </li>
                            </ul>
                        <?php } ?>
                        <?php if (INCOME_BET_BONUS == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/Bet_income_master') ?>" class="waves-effect">
                                        <span>Bet Income Bonus</span></a>
                                </li>
                            </ul>
                        <?php } ?>
                        <?php if (INCOME_DAILY_SALARY_BONUS == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/DailySalaryBonusMaster') ?>" class="waves-effect">
                                        <span>Daily Salary Bonus</span></a>
                                </li>
                            </ul>
                        <?php } ?>
                        <?php if (INCOME_DAILY_ATTENDANCE_BONUS == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/DailyAttendenceBonusMaster') ?>" class="waves-effect">
                                        <span>Daily Attendence Bonus</span></a>
                                </li>
                            </ul>

                        <?php } ?>
                        <ul class="sub-menu mm-collapse">
                            <li
                                class="<?= (array_filter([strpos($final_url, "contry"), strpos($final_url, "country/add"), strpos($final_url, "country/edit")], 'is_numeric')) ? 'mm-active' : '' ?>">
                                <a href="<?= base_url('backend/Country') ?>">Country</a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <!-- <?php if (TOURNAMENT_MASTER == true && ($role == SUPERADMIN || in_array('TOURNAMENT_MASTER', $permission))) { ?>
                    <li><a href="<?= base_url('backend/TournamentMaster') ?>" class="waves-effect"><i class="ion ion-md-list-box"></i> <span>TOURNAMENT MASTER</span></a></li>
                <?php } ?> -->

                <?php if ($role == SUPERADMIN || in_array('TOURNAMENT_MANAGEMENT', $permission)) { ?>
                    <li
                        class="<?= (array_filter([strpos($final_url, "backend/TournamentManagement")], 'is_numeric')) ? 'mm-active' : '' ?>">
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="ti-layout-grid2-alt"></i>
                            <span>Tournament Management</span>
                        </a>
                        <?php if (TOURNAMENT_TYPES == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "deposit_bonus"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/TournamentTypes') ?>" class="waves-effect">
                                        <span>Tournament Types</span></a>
                                </li>
                            </ul>
                        <?php } ?>
                        <?php if (TOURNAMENT_MASTER == true) { ?>
                            <ul class="sub-menu mm-collapse">
                                <li
                                    class="<?= (array_filter([strpos($final_url, "report"),], 'is_numeric')) ? 'mm-active' : '' ?>">
                                    <a href="<?= base_url('backend/TournamentMaster') ?>" class="waves-effect">
                                        <span>Tournament Master</span></a>
                                </li>
                            </ul>
                        <?php } ?>
                    </li>
                <?php } ?>


                <!-- <?php if (ADD_CASH == true && ($role == SUPERADMIN || in_array('ADD_CASH', $permission))) { ?>
                    <li><a href="<?= base_url('backend/Addcash') ?>" class="waves-effect"><i class="ion ion-md-list-box"></i> <span>Add Cash</span></a></li>
                <?php } ?> -->

                <!-- <?php if (COMISSION == true && ($role == SUPERADMIN || in_array('COMISSION', $permission))) { ?>
                <li><a href="<?= base_url('backend/Comission') ?>" class="waves-effect"><i
                            class="ion ion-md-list-box"></i> <span>Comission</span></a></li>
                <?php } ?> -->



                <?php if (SETTING == true && ($role == SUPERADMIN || in_array('SETTING', $permission))) { ?>
                    <li><a href="<?= base_url('backend/setting') ?>" class="waves-effect"><i
                                class="ion ion-md-list-box"></i> <span>Setting</span></a></li>
                <?php } ?>
            </ul>
        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>
    </div>
    <!-- Sidebar -left -->

</div>
<!-- Left Sidebar End -->
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->
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