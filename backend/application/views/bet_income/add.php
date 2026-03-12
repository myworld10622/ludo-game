<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/Bet_income_master/insert', ['autocomplete' => false, 'id' => 'add_user'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
            ?>
                       <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Bonus in % *</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="text" name="bonus" required id="bonus">
                            </div>
                        </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/Bet_income_master') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>