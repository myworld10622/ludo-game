<div>
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success">
            <?= $this->session->flashdata('success') ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <?= $this->session->flashdata('error') ?>
        </div>
    <?php endif; ?>
</div>

<?php
if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
    ?>
    <div class="col-md-12">
        <h4 style="color:green; text-align:center">In case of more clarification/support, please connect on this number
            <br>Mob/Whatsapp-
            <?= ($this->session->id != 1) ? '<span style="color:red">' . $this->session->mobile_text . ' <br>or <br>' . $this->session->email_text . '<br></span>' : CONTACT_DETAILS ?>
            Otherwise, we will not be responsible for the fraud.<br>
        </h4>
    </div>
    <?php
}
?>

<div class="row">

    <?php if ($role == ROLEAGENT) { ?>
        <div class="col-xl-3 col-md-6">
            <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>"
                                alt=""></div>
                        <h5 class="font-14 text-uppercase mt-0 text-white-50"> Total My Coins</h5>
                        <h4 class="font-500"><?= number_format($AdminCoins) ?></h4>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/artisan.png") ?>"
                                alt=""></div>
                        <h5 class="font-14 text-uppercase mt-0 text-white-50">Active User</h5>
                        <h4 class="font-500"><?= number_format(count($ActiveUser)) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    <?php } else if ($role == DISTRIBUTOR) { ?>
            <div class="col-xl-3 col-md-6">
                <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>"
                                    alt=""></div>
                            <h5 class="font-14 text-uppercase mt-0 text-white-50"> Total My Coins</h5>
                            <h4 class="font-500"><?= number_format($AdminCoins) ?></h4>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/artisan.png") ?>"
                                    alt=""></div>
                            <h5 class="font-14 text-uppercase mt-0 text-white-50">Active Agent</h5>
                            <h4 class="font-500"><?= number_format(count($AllAgent)) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
    <?php } else if ($role == 0) { ?>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white"
                        style="border-radius: 8px; padding: 12px 12px; height: auto;">
                        <a href="<?= base_url("backend/Setting/AdminCoin_log") ?>" style="text-decoration: none; color: inherit;">
                            <div class="card-body text-center" style="padding: 12px;">
                                <div class=" mini-stat-img mb-2" style="width: 40px; height: 40px; margin: 0 auto;">
                                    <img src="<?= base_url("assets/images/coin.png") ?>" alt="Admin Commission" style="width: 100%; height:
                            auto;">
                                </div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50"
                                    style="font-size: 1rem; margin: 0; line-height: 1.2;">
                                    Admin Wallet</h5>
                                <h4 class="font-500" style="font-size: 1.5rem; margin: 5px 0;">
                            <?= number_format($AdminCoins) ?>
                                </h4>
                            </div>
                        </a>
                        <!-- Buttons Section -->
                        <div class="d-flex justify-content-center" style="gap: 5px; padding: 10px 0;">
                            <button class="btn btn-danger btn-sm btn-rounded shadow-sm mr-4"
                                style="font-size: 0.8rem; padding: 4px 10px;" data-toggle="modal" data-target="#deductModal">
                                <i class="fa fa-minus"></i> Deduct
                            </button>
                            <button class="btn btn-success btn-sm btn-rounded shadow-sm"
                                style="font-size: 0.8rem; padding: 4px 10px;" data-toggle="modal" data-target="#addModal">
                                <i class="fa fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal for Adding Coins -->
                <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalTitle"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="addModalTitle">Add to Admin Wallet</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Enter the amount to add to the admin wallet:</p>
                                <div class="form-group">
                                    <label for="addAmount">Amount</label>
                                    <input type="number" class="form-control" id="addAmount" placeholder="Enter amount">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" onclick="submitAdd()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal for Deducting Coins -->
                <div class="modal fade" id="deductModal" tabindex="-1" role="dialog" aria-labelledby="deductModalTitle"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="deductModalTitle">Deduct from Admin Wallet</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Enter the amount to deduct from the admin wallet:</p>
                                <div class="form-group">
                                    <label for="deductAmount">Amount</label>
                                    <input type="number" class="form-control" id="deductAmount" placeholder="Enter amount">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" onclick="submitDeduct()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- modal section finished -->
                <div class="col-xl-3 col-md-6">

                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/artisan.png") ?>"
                                        alt=""></div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Active User</h5>
                                <h4 class="font-500"><?= number_format(count($ActiveUser)) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url("backend/user") ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4"><img
                                            src="<?= base_url("assets/images/customer.png") ?>" alt=""></div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Total User</h5>
                                    <h4 class="font-500"><?= number_format(count($AllUserList)) ?></h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url("backend/Purchase") ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url("assets/images/money-bag.png") ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Total Deposit</h5>
                                    <h4 class="font-500"><?= number_format((float) $TotalCoins) ?></h4>

                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Today Deposit :
                                        <span class="font-500 text-white" style="font-size: 1.5rem;">
                                    <?= number_format((float) $TodayCoins) ?></span>
                                    </h5>

                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url("backend/Purchase") ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url("assets/images/money-bag.png") ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Pending Deposit</h5>
                                    <h7 class="font-500"><?= $formatted = preg_replace('/\B(?=(\d{3})+(?!\d))/u', ',', (string) ($PendingCoins ?? 0235424)); ?></h7>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url("backend/Purchase") ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url("assets/images/money-bag.png") ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Rejected Deposit</h5>
                                    <h4 class="font-500"><?= number_format((float) $RejectedCoins) ?></h4>

                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!-- Total Withdraw (copy of Total Purchase block) -->
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url('backend/WithdrawalLog') ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url('assets/images/money-bag.png') ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Total Withdraw</h5>
                                    <h4 class="font-500"><?= number_format((float) $TotalWithdraw) ?></h4>

                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Today Withdraw :
                                        <span class="font-500 text-white" style="font-size: 1.5rem;">
                                    <?= number_format((float) $TodayWithdraw) ?>
                                        </span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url('backend/WithdrawalLog') ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url('assets/images/money-bag.png') ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Pending Withdraw</h5>
                                    <h4 class="font-500"><?= number_format((float) $PendingWithdraw) ?></h4>

                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <a href="<?= base_url('backend/WithdrawalLog') ?>">
                        <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="float-left mini-stat-img mr-4">
                                        <img src="<?= base_url('assets/images/money-bag.png') ?>" alt="">
                                    </div>
                                    <h5 class="font-14 text-uppercase mt-0 text-white-50">Rejected Withdraw</h5>
                                    <h4 class="font-500"><?= number_format((float) $RejectedWithdraw) ?></h4>

                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4">
                                    <img src="<?= base_url("assets/images/customer.png") ?>" alt="">
                                </div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Total Bot Balance</h5>
                                <h4 class="font-500"><?= number_format((float) $BotBalance) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="float-left mini-stat-img mr-4">
                                    <img src="<?= base_url("assets/images/artisan.png") ?>" alt="">
                                </div>
                                <h5 class="font-14 text-uppercase mt-0 text-white-50">Today New Users</h5>
                                <h4 class="font-500"><?= number_format((int) $TodayNewUsers) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
        <?php if (JACKPOT == true) { ?>
                    <div class="col-xl-3 col-md-6">
                        <label>Jackpot Status</label>
                        <select class="form-control" onchange="ChangeStatus(this.value)">
                            <option value="0" <?= (($JackpotStatus == 0) ? 'selected' : '') ?>>OFF</option>
                            <option value="1" <?= (($JackpotStatus == 1) ? 'selected' : '') ?>>ON</option>
                        </select><br>
                        <a href="#">
                            <div class="card bg_dasbord_box mini-stat bg-primary text-white">
                                <div class="card-body">
                                    <div class="mb-4">
                                        <div class="float-left mini-stat-img mr-4"><img src="<?= base_url("assets/images/coin.png") ?>"
                                                alt=""></div>
                                        <h5 class="font-14 text-uppercase mt-0 text-white-50">Jackpot Coin</h5>
                                        <h4 class="font-500"><?= number_format($JackpotCoins) ?></h4>
                                        <!-- <div class="mini-stat-label bg-success">
                                        <p class="mb-0">+ 12%</p>
                                    </div>  -->
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
        <?php } ?>
        <?php if (POINT_RUMMY == true) { ?>
                    <div class="col-xl-3 col-md-3">
                        <label>Rummy Bot</label>
                        <select class="form-control" onchange="ChangeRummyBotStatus(this.value)">
                            <option value="0" <?= (($RummyBotStatus == 0) ? 'selected' : '') ?>>ON</option>
                            <option value="1" <?= (($RummyBotStatus == 1) ? 'selected' : '') ?>>OFF</option>
                        </select><br><br>
                    </div>
        <?php } ?>

        <?php if (TEENPATTI == true) { ?>
                    <div class="col-xl-3 col-md-3">
                        <label>Teenpatti Bot</label>
                        <select class="form-control" onchange="ChangeTeenpattiBotStatus(this.value)">
                            <option value="0" <?= (($TeenpattiBotStatus == 0) ? 'selected' : '') ?>>ON</option>
                            <option value="1" <?= (($TeenpattiBotStatus == 1) ? 'selected' : '') ?>>OFF</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-3">
                        <form id="searchUserForm">
                            <div class="text-center">
                                <label>Search User by Mobile Number</label>
                            </div>
                            <div class="input-group">
                                <!-- <div class="input-group-prepend">
                    <select class="form-control" name="country_code" id="country_code">
                        <option value="+91">+91 🇮🇳</option>
                    </select>
                </div> -->
                                <input type="text" class="form-control" name="mobile" id="mobile" maxlength="10"
                                    onkeypress="return isNumberKey(event);" required
                                    value="<?= $search_user ? $search_user->mobile : '' ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" onclick="return checkMobile();">
                                        <i class=" fa fa-search"></i>
                                </div>
                            </div>
                            <small id="mobile-error" style="color: red; display: none;">Mobile number must be 10 digits.</small>
                        </form>
                        <br>
                        <div class="input-group mt-2">
                            <input type="text" class="form-control" name="user_name" id="user_name" placeholder="User Name"
                                value="<?= $search_user ? $search_user->name : '' ?>">
                            <input type="text" class="form-control" name="user_id" id="user_id" placeholder="User ID"
                                value="<?= $search_user ? $search_user->id : '' ?>">
                        </div><br>
                        <div class="text-center">
                            <a href="<?= !empty($search_user) && isset($search_user->id) ? base_url('backend/user/view/' . $search_user->id) : 'javascript:void(0);' ?>"
                                class="btn btn-info" id="view_user" data-toggle="tooltip" data-placement="top" title="View Logs">
                                <span class="fa fa-eye"></span>
                            </a>

                            | <a href="<?= !empty($search_user) && isset($search_user->id) ? base_url('backend/user/LadgerReports/' . $search_user->id) : 'javascript:void(0);' ?>"
                                class="btn btn-info" id="ledger_report" data-toggle="tooltip" data-placement="top"
                                title="View Ledger Report">
                                <span class="ti-wallet"></span>
                            </a>

                            | <a href="<?= !empty($search_user) && isset($search_user->id) ? base_url('backend/user/edit/' . $search_user->id) : 'javascript:void(0);' ?>"
                                class="btn btn-info" id="add_wallet" data-toggle="tooltip" data-placement="top" title="Add Wallet">
                                <span class="fa fa-credit-card"></span>
                            </a>

                            | <a href="<?= !empty($search_user) && isset($search_user->id) ? base_url('backend/user/edit_wallet/' . $search_user->id) : 'javascript:void(0);' ?>"
                                class="btn btn-danger" id="deduct_wallet" data-toggle="tooltip" data-placement="top"
                                title="Deduct Wallet">
                                <span class="fa fa-credit-card"></span>
                            </a>

                            | <a href="<?= !empty($search_user) && isset($search_user->id) ? base_url('backend/user/edit_user/' . $search_user->id) : 'javascript:void(0);' ?>"
                                class="btn btn-info" id="edit_user" data-toggle="tooltip" data-placement="top" title="Edit">
                                <span class="fa fa-edit"></span>
                            </a>
                        </div>
                    </div>
                </div>
    <?php } ?>
<?php } ?>

<!-- end row -->
</div>
<!-- container-fluid -->
</div>
<script>
    function ChangeStatus(status) {
        jQuery.ajax({
            url: "<?= base_url('backend/setting/ChangeJackpotStatus') ?>",
            type: "POST",
            data: {
                'status': status
            },
            success: function (data) {
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
            success: function (data) {
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
            success: function (data) {
                if (data) {
                    alert('Successfully Change status');
                }
                location.reload();
            }
        });
    }
</script>
<script>
    // Function to add admin commission
    function submitAdd() {
        const adminCommission = parseFloat($('#addAmount').val());

        if (isNaN(adminCommission) || adminCommission <= 0) {
            alert('Please enter a valid amount greater than 0.');
            return;
        }

        $.ajax({
            url: "<?= base_url('backend/Setting/add_admin_commission') ?>",
            type: "POST",
            data: {
                admin_commission: adminCommission
            },
            success: function () {
                $('#addModal').modal('hide');
                location.reload(); // Page reload will display flash message
            },
            error: function () {
                alert("Something went wrong. Please try again.");
            },
        });
    }

    // Function to deduct admin commission
    function submitDeduct() {
        const adminCommission = parseFloat($('#deductAmount').val());

        if (isNaN(adminCommission) || adminCommission <= 0) {
            alert('Please enter a valid amount greater than 0.');
            return;
        }

        $.ajax({
            url: "<?= base_url('backend/Setting/deduct_admin_commission') ?>",
            type: "POST",
            data: {
                admin_commission: adminCommission
            },
            success: function () {
                $('#deductModal').modal('hide');
                location.reload(); // Page reload will display flash message
            },
            error: function () {
                alert("Something went wrong. Please try again.");
            },
        });
    }

    function searchUser() {
        <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
            alert('This Feature Is Not Available in Demo');
        <?php } else { ?>
            var mobile = document.getElementById('mobile').value;

            if (mobile === '') {
                alert('Please enter a mobile number');
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?= base_url('backend/dashboard/searchUser') ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);

                    if (response.status) {
                        document.getElementById('user_name').value = response.data.name;
                        document.getElementById('user_id').value = response.data.id;

                        document.getElementById('view_user').href = '<?= base_url('backend/user/view/') ?>' + response.data
                            .id;
                        document.getElementById('ledger_report').href = '<?= base_url('backend/user/LadgerReports/') ?>' +
                            response.data.id;
                        document.getElementById('add_wallet').href = '<?= base_url('backend/user/edit/') ?>' + response.data
                            .id;
                        document.getElementById('deduct_wallet').href = '<?= base_url('backend/user/edit_wallet/') ?>' +
                            response.data.id;
                        document.getElementById('edit_user').href = '<?= base_url('backend/user/edit_user/') ?>' + response
                            .data.id;

                    } else {
                        alert('User not found');
                    }
                }
            };

            xhr.send('mobile=' + encodeURIComponent(mobile));
        <?php } ?>
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        function isNumberKey(evt) {
            var charCode = evt.which || evt.keyCode;
            if (charCode < 48 || charCode > 57) { // Allow only numbers (0-9)
                return false;
            }
            return true;
        }

        function checkMobile() {
            var mobile = document.getElementById("mobile").value;
            var errorMsg = document.getElementById("mobile-error");

            if (mobile.length < 10) {
                errorMsg.style.display = "block";
                return false;
            } else {
                searchUser();
                errorMsg.style.display = "none";
                return true;
            }
        }

        // Assign function globally so HTML can access it
        window.isNumberKey = isNumberKey;
        window.checkMobile = checkMobile;
    });
</script>