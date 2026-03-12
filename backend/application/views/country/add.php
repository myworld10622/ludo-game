<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/Country/insert', ['autocomplete' => false, 'id' => 'add_table_master'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_country')])
            ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text"  name="name" required
                            id="name">
                    </div>
                </div>
                <div class="form-group row"><label for="code" class="col-sm-2 col-form-label">Code *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text"  name="code" required
                            id="code">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="flag" class="col-sm-2 col-form-label">Flag *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="flag" required id="flag">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/Country') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>
    <script>
    function updateValue(x) {
        $('#chaal_limit').val(x * 128);
        $('#pot_limit').val(x * 1024);
    }
    </script>