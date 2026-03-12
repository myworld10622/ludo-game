<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/Agent/update_agent', ['autocomplete' => false, 'id' => 'edit_agent'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_admin')])
            ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">First Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $agent->first_name ?>" name="first_name" 
                            required id="name">
                        <input type="hidden" value="<?= $agent->id ?>" name="agent_id" id="agent_id">
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Last Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $agent->last_name ?>" name="last_name" 
                            required id="name">
                    </div>
                </div>

              

                <div class="form-group row"><label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $agent->password ?>" name="password" 
                            required id="password">
                    </div>
                </div>
                
                <div class="form-group row"><label for="mobile" class="col-sm-2 col-form-label">Mobile *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $agent->mobile ?>" name="mobile" 
                            required id="mobile" readonly>
                    </div>
                </div>

                <div class="form-group row"><label for="email_id" class="col-sm-2 col-form-label">Email *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="email_id" value="<?= $agent->email_id ?>" name="email_id" 
                            required id="email_id">
                    </div>
                </div>

               
                <div class="form-group row"><label for="added_date" class="col-sm-2 col-form-label">Added Date *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= date("d-m-Y h:i:s A", strtotime($agent->created_date)) ?>" name="added_date" 
                            required id="added_date" readonly>
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