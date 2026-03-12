<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/SubAdmin/update_subadmin', [
                    'autocomplete' => false,
                    'id' => 'edit_subadmin',
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_admin')])
                ?>
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">First Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $subadmin->first_name ?>" name="first_name"
                            required id="name">
                        <input type="hidden" value="<?= $subadmin->id ?>" name="subadmin_id" id="subadmin_id">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Last Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $subadmin->last_name ?>" name="last_name"
                            required id="name">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="password" value="<?= $subadmin->password ?>" name="password"
                            required id="password">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="mobile" class="col-sm-2 col-form-label">Mobile *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $subadmin->mobile ?>" name="mobile" required
                            id="mobile" readonly>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email" class="col-sm-2 col-form-label">Email *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="email" value="<?= $subadmin->email_id ?>" name="email"
                            required id="email">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="subadmin" class="col-sm-2 col-form-label">Subadmin</label>
                    <div class="col-sm-10">
                        <select class="form-control" id="subadmin" name="subadmin[]" multiple>


                            <option value="APP_USER_MANAGEMENT"
                                <?= in_array('APP_USER_MANAGEMENT', $subadmin->subadmin) ? 'selected' : '' ?>>App User
                                Mgmt.</option>
                            <option value="ADMIN_USER_MANAGEMENT"
                                <?= in_array('ADMIN_USER_MANAGEMENT', $subadmin->subadmin) ? 'selected' : '' ?>>Admin
                                User Mgmt.</option>
                            <option value="PAYMENT_MANAGEMENT"
                                <?= in_array('PAYMENT_MANAGEMENT', $subadmin->subadmin) ? 'selected' : '' ?>>Payment
                                Mgmt.</option>
                            <option value="AVIATOR" <?= in_array('AVIATOR', $subadmin->subadmin) ? 'selected' : '' ?>>
                                Aviator</option>
                            <option value="ANDER_BAHAR"
                                <?= in_array('ANDER_BAHAR', $subadmin->subadmin) ? 'selected' : '' ?>>Andar Bahar
                            </option>
                            <option value="ANIMAL_ROULETTE"
                                <?= in_array('ANIMAL_ROULETTE', $subadmin->subadmin) ? 'selected' : '' ?>>Animal
                                Roullete</option>
                            <option value="BACCARAT" <?= in_array('BACCARAT', $subadmin->subadmin) ? 'selected' : '' ?>>
                                Baccarat</option>
                            <option value="CAR_ROULETTE"
                                <?= in_array('CAR_ROULETTE', $subadmin->subadmin) ? 'selected' : '' ?>>Car Roullete
                            </option>
                            <option value="COLOR_PREDICTION"
                                <?= in_array('COLOR_PREDICTION', $subadmin->subadmin) ? 'selected' : '' ?>>Color
                                Prediction</option>
                            <option value="DRAGON_TIGER"
                                <?= in_array('DRAGON_TIGER', $subadmin->subadmin) ? 'selected' : '' ?>>Dragon Tiger
                            </option>
                            <option value="HEAD_TAILS"
                                <?= in_array('HEAD_TAILS', $subadmin->subadmin) ? 'selected' : '' ?>>Head & Tail
                            </option>
                            <option value="JACKPOT_TEENPATTI"
                                <?= in_array('JACKPOT_TEENPATTI', $subadmin->subadmin) ? 'selected' : '' ?>>Jackpot
                                Teenpatti</option>
                            <option value="JHANDI_MUNDA"
                                <?= in_array('JHANDI_MUNDA', $subadmin->subadmin) ? 'selected' : '' ?>>Jhandi Munda
                            </option>
                            <option value="LUDO" <?= in_array('LUDO', $subadmin->subadmin) ? 'selected' : '' ?>>Ludo
                            </option>
                            <option value="POKER" <?= in_array('POKER', $subadmin->subadmin) ? 'selected' : '' ?>>Poker
                            </option>
                            <option value="RED_VS_BLACK"
                                <?= in_array('RED_VS_BLACK', $subadmin->subadmin) ? 'selected' : '' ?>>Red Vs Black
                            </option>
                            <option value="ROULETTE" <?= in_array('ROULETTE', $subadmin->subadmin) ? 'selected' : '' ?>>
                                Roullete</option>
                            <option value="RUMMY_POINT"
                                <?= in_array('RUMMY_POINT', $subadmin->subadmin) ? 'selected' : '' ?>>Rummy Point
                            </option>
                            <option value="RUMMY_POOL"
                                <?= in_array('RUMMY_POOL', $subadmin->subadmin) ? 'selected' : '' ?>>Rummy Pool</option>
                            <option value="RUMMY_DEAL"
                                <?= in_array('RUMMY_DEAL', $subadmin->subadmin) ? 'selected' : '' ?>>Rummy Deal</option>
                            <option value="RUMMY_TOURNAMENT"
                                <?= in_array('RUMMY_TOURNAMENT', $subadmin->subadmin) ? 'selected' : '' ?>>Rummy
                                Tournament</option>
                            <option value="SEVEN_UP_DOWN"
                                <?= in_array('SEVEN_UP_DOWN', $subadmin->subadmin) ? 'selected' : '' ?>>Seven Up Down
                            </option>
                            <option value="SLOT_GAME"
                                <?= in_array('SLOT_GAME', $subadmin->subadmin) ? 'selected' : '' ?>>Slot Game</option>
                            <option value="TEENPATTI"
                                <?= in_array('TEENPATTI', $subadmin->subadmin) ? 'selected' : '' ?>>Teenpatti</option>
                            <option value="MASTER_MANAGEMENT"
                                <?= in_array('MASTER_MANAGEMENT', $subadmin->subadmin) ? 'selected' : '' ?>>Master Mgmt.
                            </option>
                            <option value="REPORT_MANAGEMENT"
                                <?= in_array('REPORT_MANAGEMENT', $subadmin->subadmin) ? 'selected' : '' ?>>Report Mgmt.
                            </option>
                            <option value="NOTIFICATION"
                                <?= in_array('NOTIFICATION', $subadmin->subadmin) ? 'selected' : '' ?>>Notification
                            </option>
                            <option value="SETTING" <?= in_array('SETTING', $subadmin->subadmin) ? 'selected' : '' ?>>
                                Setting</option>
                            <option value="TICKET" <?= in_array('TICKET', $subadmin->subadmin) ? 'selected' : '' ?>>
                                Ticket</option>

                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="added_date" class="col-sm-2 col-form-label">Added Date *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text"
                            value="<?= date("d-m-Y h:i:s A", strtotime($subadmin->created_date)) ?>" name="added_date"
                            required id="added_date" readonly>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/SubAdmin') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#subadmin').select2();
});
</script>

<style>
.select2-container--default .select2-results__option--highlighted {
    background-color: #0056b3 !important;
    color: white !important;
}
</style>