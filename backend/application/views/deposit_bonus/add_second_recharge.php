<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/Recharge/insert_second_recharge', ['autocomplete' => false, 'id' => 'add_chips'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_chips')])
            ?>

                <!-- <div class="form-group row"><label for="coin" class="col-sm-2 col-form-label">Coin Type *</label>
                    <div class="col-sm-10">
                    <select class="form-control" name="coin_type" required id="coin_type">
                        <option value="">Select Coin Type</option>
                        <option value="1">INR</option>
                        <option value="2">CRYPTO</option>
                    </select>
                    </div>
                </div> -->


                <div class="form-group row"><label for="coin" class="col-sm-2 col-form-label">Ttile *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="title" required id="title">
                    </div>
                </div>

                <div class="form-group row"><label for="min_recharge" class="col-sm-2 col-form-label">Min Recharge *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" min="0" name="min_recharge" required id="min_recharge">
                    </div>
                </div>

                <div class="form-group row"><label for="max_recharge" class="col-sm-2 col-form-label">Max Recharge *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="max_recharge" required id="max_recharge">
                    </div>
                </div>

                <div class="form-group row"><label for="bonus" class="col-sm-2 col-form-label">Bonus Percentage *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="bonus" required id="bonus">
                    </div>
                </div>
 
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                     
                        ?>
                        <a href="<?= base_url('backend/Recharge/first_recharge') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>