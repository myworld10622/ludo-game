<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/user/update_user', [
                    'autocomplete' => false,
                    'id' => 'edit_user',
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_users')]);
                ?>

                <div class="row">
                    <!-- General Details Section -->
                    <div class="col-md-6 border-right">
                        <h6 class="mb-3">General Details</h6>
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input class="form-control" type="text" value="<?= $User[0]->name ?>" name="name" required
                                id="name">
                            <input type="hidden" value="<?= $User[0]->id ?>" name="user_id" id="user_id">
                        </div>

                        <div class="form-group">
                            <label for="mobile">Mobile *</label>
                            <input class="form-control" type="text" value="<?= $User[0]->mobile ?>" name="mobile"
                                required id="mobile" readonly>
                        </div>

                        <div class="form-group">
                            <label for="email">Email </label>
                            <input class="form-control" type="email" value="<?= $User[0]->email ?>" name="email"
                                id="email">
                        </div>

                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input class="form-control" type="text" value="<?= $User[0]->password ?>" name="password"
                                required id="password">
                        </div>

                        <div class="form-group">
                            <label for="password">Referral Percent *</label>
                            <input class="form-control" type="number" step="0.00" max="100" min="0" value="<?= $User[0]->referral_precent ?>" name="referral_precent"
                                required id="referral_precent">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <div class="form-inline">
                                <div class="form-check mr-5">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="m"
                                        <?= ($User[0]->gender == 'm') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="f"
                                        <?= ($User[0]->gender == 'f') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="profile_pic">Profile Pic *</label>
                            <?php if (!empty($User[0]->profile_pic)): ?>
                                <img src="<?= base_url('data/post/' . $User[0]->profile_pic) ?>" alt="Profile Picture"
                                    class="img-thumbnail mb-2" style="max-width: 100px;">
                            <?php endif; ?>
                            <input class="form-control" type="file" name="profile_pic" id="profile_pic">
                        </div>

                        <div class="form-group">
                            <label for="referral_code">Referral Code *</label>
                            <input class="form-control" type="text" value="<?= $User[0]->referral_code ?>"
                                name="referral_code" required id="referral_code" readonly>
                        </div>
                        <div class="form-group">
                            <label for="referred_by">Referred By *</label>
                            <input class="form-control" type="text" value="<?= ($Referred_User)?$Referred_User[0]->name:"Not Reffered" ?>"
                                name="referred_by" required id="referred_by" readonly>
                        </div>
                        <div class="form-group">
                            <label for="app_version">App Version *</label>
                            <input class="form-control" type="text" value="<?= $User[0]->app_version ?>"
                                name="app_version" required id="app_version" readonly>
                        </div>
                        <div class="form-group">
                            <label for="added_date">Added Date *</label>
                            <input class="form-control" type="text"
                                value="<?= date("d-m-Y h:i:s A", strtotime($User[0]->added_date)) ?>" name="added_date"
                                required id="added_date" readonly>
                        </div>


                    </div>

                    <!-- Bank Details Section -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Bank Details</h6>
                        <?php

                        // Ensuring all data is coming
                        if (!empty($BankDetails) && is_array($BankDetails)): ?>

                            <?php if (isset($BankDetails[0]->bank_name) && $BankDetails[0]->bank_name != ''): ?>
                                <div class="form-group">
                                    <label for="bank_name">Bank Name *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->bank_name ?>"
                                        name="bank_name" id="bank_name" readonly>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($BankDetails[0]->ifsc_code) && $BankDetails[0]->ifsc_code != ''): ?>
                                <div class="form-group">
                                    <label for="ifsc_code">IFSC Code *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->ifsc_code ?>"
                                        name="ifsc_code" id="ifsc_code" readonly>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($BankDetails[0]->acc_holder_name) && $BankDetails[0]->acc_holder_name != ''): ?>
                                <div class="form-group">
                                    <label for="acc_holder_name">Account Holder Name *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->acc_holder_name ?>"
                                        name="acc_holder_name" id="acc_holder_name" readonly>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($BankDetails[0]->acc_no) && $BankDetails[0]->acc_no != ''): ?>
                                <div class="form-group">
                                    <label for="acc_no">Account Number *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->acc_no ?>" name="acc_no"
                                        id="acc_no" readonly>
                                </div>
                            <?php endif; ?>

                            <?php // checking passbook img
                                $passbook_img_path = FCPATH . 'data/post/' . ($BankDetails[0]->passbook_img ?? '');
                                if (!empty($BankDetails[0]->passbook_img) && file_exists($passbook_img_path)): ?>
                                <div class="form-group">
                                    <label for="passbook_img">Passbook Image *</label>
                                    <img src="<?= base_url('data/post/' . $BankDetails[0]->passbook_img) ?>"
                                        alt="Passbook Image" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> Passbook Image is not available for this user.
                                </div>
                            <?php endif; ?>

                            <?php if (isset($BankDetails[0]->crypto_address) && $BankDetails[0]->crypto_address != ''): ?>
                                <div class="form-group">
                                    <label for="crypto_address">Crypto Address *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->crypto_address ?>"
                                        name="crypto_address" id="crypto_address" readonly>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($BankDetails[0]->crypto_wallet_type) && $BankDetails[0]->crypto_wallet_type != ''): ?>
                                <div class="form-group">
                                    <label for="crypto_wallet_type">Crypto Wallet Type *</label>
                                    <input class="form-control" type="text" value="<?= $BankDetails[0]->crypto_wallet_type ?>"
                                        name="crypto_wallet_type" id="crypto_wallet_type" readonly>
                                </div>
                            <?php endif; ?>

                            <?php 
                                // checking crypto QR
                                $crypto_qr_path = FCPATH . 'data/post/' . ($BankDetails[0]->crypto_qr ?? '');
                                if (!empty($BankDetails[0]->crypto_qr) && file_exists($crypto_qr_path)): ?>
                            <div class="form-group">
                                <label for="crypto_qr">Crypto QR *</label>
                                <img src="<?= base_url('data/post/' . $BankDetails[0]->crypto_qr) ?>" 
                                    alt="Crypto QR" class="img-thumbnail mt-2" style="max-width: 150px;">
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Crypto QR is not available for this user.
                            </div>
                        <?php endif; ?>

                        <?php else: ?>
                            <!-- Error Message -->
                            <div class="alert alert-danger" role="alert">
                                <strong>Error:</strong> This User does not have Bank details.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit and Cancel Buttons -->
                <div class="form-group mb-0 mt-4">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/User') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>