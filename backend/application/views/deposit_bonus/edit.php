<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/DepositBonus/update', ['autocomplete' => false, 'id' => 'edit_user'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
            ?>
                <!-- <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Bonus *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $deposit->min ?>" name="bonus" 
                            required id="bonus">
                        <input type="hidden" value="<?= $deposit->id ?>" name="id" id="id">
                    </div>
                </div> -->

                 <div class="form-group row"><label for="min" class="col-sm-2 col-form-label">Min *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number"  value="<?= $deposit->min ?>" name="min" min="0" name="min" required id="min">
                    <input type="hidden" value="<?= $deposit->id ?>" name="id" id="id">
                    </div>
                </div>

                <div class="form-group row"><label for="max" class="col-sm-2 col-form-label">Max *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" value="<?= $deposit->max ?>" name="max" required id="max">
                    </div>
                </div>

                <div class="form-group row"><label for="self_bonus" class="col-sm-2 col-form-label">Self Bonus Amount *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" value="<?= $deposit->self_bonus ?>" name="self_bonus" required id="self_bonus">
                    </div>
                </div>
                <div class="form-group row"><label for="upline_bonus" class="col-sm-2 col-form-label">Upline Bonus Amount *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" value="<?= $deposit->upline_bonus ?>" name="upline_bonus" required id="upline_bonus">
                    </div>
                </div>

                <div class="form-group row"><label for="deposit_count" class="col-sm-2 col-form-label">No Of Deposit Count *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" value="<?= $deposit->deposit_count ?>" name="deposit_count" required id="deposit_count">
                    </div>
                </div>

                
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/DepositBonus') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>