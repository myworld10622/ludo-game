<div class="card">
    <div class="card-body">
        <h4>Total Coin In Market (<?= ($PurchaseOnline + $PurchaseOffline + $WelcomeBonus + $RefferalBonus + $WelcomeReferralBonus) ?>)</h4>
        <br>

        <h4>Wallet</h4>
        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                            <h5 class="font-14 text-uppercase mt-0 text-white-50">Sum of Wallet</h5>
                            <h4 class="font-500"><?= ($TotalWallet) ? round($TotalWallet) : 0 ?></h4>
                            <!-- <div class="mini-stat-label bg-success">
                                        <p class="mb-0">+ 12%</p>
                                    </div>  -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Sum of Winning Wallet</h5>
                                <h4 class="font-500"><?= ($TotalWinning) ? round($TotalWinning) : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                     <p class="mb-0">+ 12%</p>
                                 </div>  -->
                            </div>
                        </div>
                    </div>
            </div>


        </div>

        <h4>Purchase (<?= $PurchaseOnline + $PurchaseOffline ?>)</h4>
        <div class="row">




            <div class="col-xl-3 col-md-6">

                <a href="<?= base_url("backend/Purchase") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Online</h5>
                                <h4 class="font-500"><?= ($PurchaseOnline) ? $PurchaseOnline : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                        <p class="mb-0">+ 12%</p>
                                    </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="<?= base_url("backend/Purchase/offline") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Offline</h5>
                                <h4 class="font-500"><?= ($PurchaseOffline) ? $PurchaseOffline : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                     <p class="mb-0">+ 12%</p>
                                 </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="<?= base_url("backend/Purchase/Robot") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Robot Coin</h5>
                                <h4 class="font-500"><?= ($RobotCoin) ? $RobotCoin : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                     <p class="mb-0">+ 12%</p>
                                 </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>
        <h4>Bonus (<?= ($WelcomeBonus + $RefferalBonus + $WelcomeReferralBonus + $PurchaseBonus) ?>)</h4>
        <div class="row">

            <div class="col-xl-3">
                <a href="<?= base_url("backend/Bonus/Welcomebonus") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Welcome Bonus</h5>
                                <h4 class="font-500"><?= ($WelcomeBonus) ? $WelcomeBonus : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                        <p class="mb-0">+ 12%</p>
                                    </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 ">
                <a href="<?= base_url("backend/Bonus/Welcomerefferalbonus") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Welcome Referral Bonus</h5>
                                <h4 class="font-500"><?= ($WelcomeReferralBonus) ? $WelcomeReferralBonus : 0 ?></h4>

                                <!-- <div class="mini-stat-label bg-success">
                                        <p class="mb-0">+ 12%</p>
                                    </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 ">


                <a href="<?= base_url("backend/Bonus/Refferalbonus") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Referral Bonus</h5>
                                <h4 class="font-500"><?= ($RefferalBonus) ? $RefferalBonus : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                     <p class="mb-0">+ 12%</p>
                                 </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 ">


                <a href="<?= base_url("backend/Bonus/Purchasebonus") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Purchase Bonus</h5>
                                <h4 class="font-500"><?= ($PurchaseBonus) ? $PurchaseBonus : 0 ?></h4>
                                <!-- <div class="mini-stat-label bg-success">
                                      <p class="mb-0">+ 12%</p>
                                  </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4>Withdrawl (<?= ($ApprovedCoins + $PendingCoins) ?>)</h4>
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <a href="<?= base_url("backend/WithdrawalLog") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Approved</h5>
                                <h4 class="font-500">
                                    <?= ($ApprovedCoins) ? $ApprovedCoins : 0 ?>
                                </h4>
                                <!-- <div class="mini-stat-label bg-success">
                                    <p class="mb-0">+ 12%</p>
                                 </div>  -->
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="<?= base_url("backend/WithdrawalLog") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Pending</h5>
                                <h4 class="font-500"><?= ($PendingCoins) ? $PendingCoins : 0 ?></h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="<?= base_url("backend/WithdrawalLog") ?>">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>" alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Rejected</h5>
                                <h4 class="font-500"><?= ($RejectedCoins) ? $RejectedCoins : 0 ?></h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <!-- end row -->
</div>

</div>
<script>
    function ChangeStatus(status) {
        jQuery.ajax({
            url: "<?= base_url('backend/setting/ChangeJackpotStatus') ?>",
            type: "POST",
            data: {
                'status': status
            },
            success: function(data) {
                if (data) {
                    alert('Successfully Change status');
                }
                location.reload();
            }
        });
    }

    function ChangeRummyBotStatus(status) {
        jQuery.ajax({
            url: "<?= base_url('backend/setting/ChangeRummyBotStatus') ?>",
            type: "POST",
            data: {
                'status': status
            },
            success: function(data) {
                if (data) {
                    alert('Successfully Change status');
                }
                location.reload();
            }
        });
    }

    function ChangeTeenpattiBotStatus(status) {
        jQuery.ajax({
            url: "<?= base_url('backend/setting/ChangeTeenpattiBotStatus') ?>",
            type: "POST",
            data: {
                'status': status
            },
            success: function(data) {
                if (data) {
                    alert('Successfully Change status');
                }
                location.reload();
            }
        });
    }
</script>