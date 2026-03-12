<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/DepositPercentage/store', ['autocomplete' => false, 'id' => 'add_chips'
                ,'method'=>'post'], 
                ['type' => $this->url_encrypt->encode('tbl_deposit_percentage_master')])
            ?>

                <div class="form-group row">
                    <label for="user_type" class="col-sm-2 col-form-label">User Type *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="user_type" id="user_type" required>
                            <option value="">-- Select User Type --</option>
                            <option value="0">Admin</option>
                            <option value="2">Agent</option>
                            <option value="3">Distributer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row"><label for="percentage" class="col-sm-2 col-form-label">Percentage *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="percentage" required id="percentage">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                     
                        ?>
                        <a href="<?= base_url('backend/DepositPercentage') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>