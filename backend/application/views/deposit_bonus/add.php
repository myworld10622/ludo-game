<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/DepositBonus/store', ['autocomplete' => false, 'id' => 'add_chips'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_chips')])
            ?>

                <div class="form-group row"><label for="min" class="col-sm-2 col-form-label">Min *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" min="0" name="min" required id="min">
                    </div>
                </div>

                <div class="form-group row"><label for="max" class="col-sm-2 col-form-label">Max *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="max" required id="max">
                    </div>
                </div>

                <div class="form-group row"><label for="self_bonus" class="col-sm-2 col-form-label">Self Bonus Amount *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="self_bonus" required id="self_bonus">
                    </div>
                </div>
                <div class="form-group row"><label for="upline_bonus" class="col-sm-2 col-form-label">Upline Bonus Amount *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="upline_bonus" required id="upline_bonus">
                    </div>
                </div>

                <div class="form-group row"><label for="deposit_count" class="col-sm-2 col-form-label">No Of Deposit Count *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="deposit_count" required id="deposit_count">
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