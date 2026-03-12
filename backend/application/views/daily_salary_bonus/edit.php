<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/DailySalaryBonusMaster/update', [
                    'autocomplete' => false, 'id' => 'edit_user', 'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_users')])
                ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Active Users *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $bonus_details->active_users ?>" name="active_users" required id="active_users">
                        <input type="hidden" value="<?= $bonus_details->id ?>" name="id" id="id">
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Daily Salary Bonus *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $bonus_details->daily_salary_bonus ?>" name="daily_salary_bonus" required id="daily_salary_bonus">
                        <!-- <input type="hidden" value="<?= $bonus_details->id ?>" name="id" id="id"> -->
                    </div>
                </div>


                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/DailySalaryBonusMaster') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>