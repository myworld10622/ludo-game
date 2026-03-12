<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/AgentUser/insert', [
                    'autocomplete' => false,
                    'id' => 'add_user'
                    ,
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_users')])
                    ?>
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" required id="name">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="password" required id="password">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email" class="col-sm-2 col-form-label">Email </label>
                    <div class="col-sm-10">
                        <input class="form-control" type="email" name="email" id="email">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="mobile" class="col-sm-2 col-form-label">Mobile/User Id *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="mobile" required id="mobile">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="gender" class="col-sm-2 col-form-label">Gender</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="gender" id="gender">
                            <option value="m">Male</option>
                            <option value="f">Female</option>
                        </select>
                    </div>
                </div>

                <!-- 
                <div class="form-group row"><label for="wallet" class="col-sm-2 col-form-label">Wallet *</label>
                    <div class="col-sm-10">
                    <input class="form-control" type="number" min="0" name="wallet" required id="wallet">
                    </div>
                </div> -->


                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/AgentUser') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>