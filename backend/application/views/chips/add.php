<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <?php
            echo form_open_multipart('backend/chips/insert', ['autocomplete' => false, 'id' => 'add_chips'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_chips')])
            ?>
                <div class="form-group row">
                <label for="coin" class="col-sm-2 col-form-label">Unit *</label>
                <div class="col-sm-10">
                    <input class="form-control" type="number" name="coin" required id="coin" min="0" max="50000">
                </div>
            </div>


              <div class="form-group row">
            <label for="price" class="col-sm-2 col-form-label">Price *</label>
            <div class="col-sm-10">
                <input class="form-control" type="number" name="price" required id="price" min="0" max="50000">
            </div>
        </div>


               <div class="form-group row">
            <label for="title" class="col-sm-2 col-form-label">Title *</label>
            <div class="col-sm-10">
                <input class="form-control" type="text" name="title" required id="title" maxlength="50">
            </div>
        </div>

 
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                     
                        ?>
                        <a href="<?= base_url('backend/Chips') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
            <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>