<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/Recharge/update_first_recharge', ['autocomplete' => false, 'id' => 'edit_chips'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_coin_plan'),
                'id'=> $recharge->id])
                ?>

                <!-- <div class="form-group row"><label for="coin" class="col-sm-2 col-form-label">Coin Type *</label>
                    <div class="col-sm-10">
                    <select class="form-control" name="coin_type" required id="coin_type">
                        <option value="">Select Coin Type</option>
                        <option value="1" <?= $Chips->coin_type==1?'selected':'' ?>>INR</option>
                        <option value="2" <?= $Chips->coin_type==2?'selected':'' ?>>CRYPTO</option>
                    </select>
                    </div>
                </div> -->

                <div class="form-group row"><label for="title" class="col-sm-2 col-form-label">Title *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="title" required id="title" value="<?= $recharge->title?>">
                    </div>
                </div>

                <div class="form-group row"><label for="min_range" class="col-sm-2 col-form-label">Min Recharge *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" min="0" name="min_range" required id="min_range" value="<?= $recharge->min_range?>">
                    </div>
                </div>

                <div class="form-group row"><label for="max_range" class="col-sm-2 col-form-label">Max Recharge *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" name="max_range" required id="max_range" value="<?= $recharge->max_range?>">
                    </div>
                </div>

                <div class="form-group row"><label for="bonus" class="col-sm-2 col-form-label">Bonus *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" name="bonus" required id="bonus" value="<?= $recharge->bonus?>">
                    </div>
                </div>
 
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        echo form_reset(['class' => 'btn btn-secondary waves-effect', 'value' => 'Cancel']);
                        ?>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>