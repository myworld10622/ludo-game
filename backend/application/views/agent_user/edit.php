<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/AgentUser/update_user', ['autocomplete' => false, 'id' => 'edit_user'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
            ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $User[0]->name ?>" name="name" 
                            required id="name">
                        <input type="hidden" value="<?= $User[0]->id ?>" name="user_id" id="user_id">
                    </div>
                </div>

                <div class="form-group row"><label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $User[0]->password ?>"  name="password" 
                            required id="password">
                    </div>
                </div>
                
                <div class="form-group row"><label for="mobile" class="col-sm-2 col-form-label">Mobile *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $User[0]->mobile ?>" name="mobile" 
                            required id="mobile" readonly>
                    </div>
                </div>

                <!-- <div class="form-group row"><label for="email" class="col-sm-2 col-form-label">Email *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="email" value="<?= $User[0]->email ?>" name="email" 
                            required id="email">
                    </div>
                </div> -->

                <!-- <div class="form-group row">
                    <label for="gender" class="col-sm-2 col-form-label">Gender</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="gender" id="gender">
                            <option <?= $User[0]->gender == "m" ? 'selected' : '' ?> value="m">Male</option>
                            <option <?= $User[0]->gender == "f" ? 'selected' : '' ?> value="f">Female</option>
                        </select>
                    </div>
                </div> -->

                <div class="form-group row"><label for="added_date" class="col-sm-2 col-form-label">Added Date *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= date("d-m-Y h:i:s A", strtotime($User[0]->added_date)) ?>" name="added_date" 
                            required id="added_date" readonly>
                    </div>
                </div>

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