<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/DailyAttendenceBonusMaster/update', [
                    'autocomplete' => false, 'id' => 'edit_user', 'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_users')])
                ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Accumulated Amount *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $bonus_details->accumulated_amount ?>" name="accumulated_amount" required id="accumulated_amount">
                        <input type="hidden" value="<?= $bonus_details->id ?>" name="id" id="id">
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Attendence Bonus *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $bonus_details->attendenece_bonus ?>" name="attendenece_bonus" required id="attendenece_bonus">
                        <!-- <input type="hidden" value="<?= $bonus_details->id ?>" name="id" id="id"> -->
                    </div>
                </div>


                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/DailyAttendenceBonusMaster') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>