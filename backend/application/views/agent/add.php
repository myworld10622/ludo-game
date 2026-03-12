<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/Agent/insert', ['autocomplete' => false, 'id' => 'add_user'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
            ?>
                <div class="form-group row"><label for="first_name" class="col-sm-2 col-form-label">First Name *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="first_name" required id="first_name">
                    </div>
                </div>

                 <div class="form-group row"><label for="last_name" class="col-sm-2 col-form-label">Last Name *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" name="last_name" required id="last_name">
                    </div>
                </div>

                <div class="form-group row"><label for="email" class="col-sm-2 col-form-label">Email *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="email" min="0" name="email" required id="email">
                    </div>
                </div>

                <div class="form-group row"><label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="text" min="0" name="password" required id="password">
                    </div>
                </div>
 
                 <div class="form-group row"><label for="mobile" class="col-sm-2 col-form-label">Mobile *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="mobile" min="0" name="mobile" required id="mobile">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/Agent') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>

