<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/setting/update', [
                    'autocomplete' => false,
                    'id' => 'edit_setting',
                    'method' => 'post'
                ], [
                    'form_type' => 'App',
                ])
                    ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Referral Coins *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="referral_amount"
                            value="<?= $Setting->referral_amount ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_1" class="col-sm-2 col-form-label">Referral Level 1
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" name="level_1"
                            value="<?= $Setting->level_1 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_2" class="col-sm-2 col-form-label">Referral Level 2
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" name="level_2"
                            value="<?= $Setting->level_2 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_3" class="col-sm-2 col-form-label">Referral Level 3
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" name="level_3"
                            value="<?= $Setting->level_3 ?>" required>
                    </div>
                </div>
                <div class="form-group row"><label for="level_4" class="col-sm-2 col-form-label">Referral Level 4
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" name="level_4"
                            value="<?= $Setting->level_4 ?>" required>
                    </div>
                </div>
                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 5
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_5"
                            value="<?= $Setting->level_5 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 6
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_6"
                            value="<?= $Setting->level_6 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 7
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_7"
                            value="<?= $Setting->level_7 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 8
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_8"
                            value="<?= $Setting->level_8 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 9
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_9"
                            value="<?= $Setting->level_9 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="level_5" class="col-sm-2 col-form-label">Referral Level 10
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" max="100" step="0.01" name="level_10"
                            value="<?= $Setting->level_10 ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="referral_id" class="col-sm-2 col-form-label">Referral ID
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="referral_id" value="<?= $Setting->referral_id ?>"
                            required>
                    </div>
                </div>

                <div class="form-group row"><label for="referral_link" class="col-sm-2 col-form-label">Referral Link
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="referral_link"
                            value="<?= $Setting->referral_link ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="share_text" class="col-sm-2 col-form-label">Share Text
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="share_text" id="share_text"
                            value="<?= $Setting->share_text ?>" required>
                    </div>
                </div>


                <div class="form-group row"><label for="min_withdrawal" class="col-sm-2 col-form-label">Minimum
                        Withdrawal
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="min_withdrawal"
                            value="<?= $Setting->min_withdrawal ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="admin_commission" class="col-sm-2 col-form-label">Admin
                        Comission *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="admin_commission"
                            value="<?= $Setting->admin_commission ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="distribute_precent" class="col-sm-2 col-form-label">Distribute
                        Percentage *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="distribute_precent"
                            value="<?= $Setting->distribute_precent ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="bonus" class="col-sm-2 col-form-label">Bonus
                        *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="bonus">
                            <option value="0" <?= ($Setting->bonus == '0' ? 'selected' : '') ?>>No</option>
                            <option value="1" <?= ($Setting->bonus == '1' ? 'selected' : '') ?>>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row"><label for="bonus_amount" class="col-sm-2 col-form-label">Registration Bonus
                        Amount *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="bonus_amount"
                            value="<?= $Setting->bonus_amount ?>" required>
                    </div>
                </div>


                <div class="form-group row">
                    <label for="qr_image" class="col-sm-2 col-form-label">UPI QR Image</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="qr_image" accept="image/*">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>

                </div>

                <div class="form-group row"><label for="upi_id" class="col-sm-2 col-form-label">UPI ID
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="upi_id" id="upi_id"
                            value="<?= $Setting->upi_id ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="usdt_qr_image" class="col-sm-2 col-form-label">USDT QR
                        Image</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="usdt_qr_image" accept="image/*">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>

                <div class="form-group row"><label for="usdt_address" class="col-sm-2 col-form-label">USDT Address
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="usdt_address" id="usdt_address"
                            value="<?= $Setting->usdt_address ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="upi_gateway_key" class="col-sm-2 col-form-label">UPI Gateway API
                        Key
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="upi_gateway_key" id="upi_gateway_key"
                            value="<?= $Setting->upi_gateway_api_key ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="app_message" class="col-sm-2 col-form-label">INR To Dollar
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="dollar" id="dollar"
                            value="<?= $Setting->dollar ?>" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="daily_bonus_status" class="col-sm-2 col-form-label">Daily Bonus Status
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="daily_bonus_status" id="daily_bonus_status"
                            value="<?= $Setting->daily_bonus_status ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="app_popop_status" class="col-sm-2 col-form-label">App Popup
                        Status*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="app_popop_status" id="app_popop_status"
                            value="<?= $Setting->app_popop_status ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="fcm_server_key" class="col-sm-2 col-form-label">Fcm
                        token*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="fcm_server_key" id="fcm_server_key"
                            value="<?= $Setting->fcm_server_key ?>" required>
                    </div>
                </div>

                <div>
                    <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>

                    <?php } else { ?>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        echo form_reset(['class' => 'btn btn-secondary waves-effect', 'value' => 'Cancel']);
                        ?>
                    <?php } ?>

                </div>
            </div>
            <?php
            echo form_close();
            ?>
        </div>
    </div><!-- end col -->
</div>