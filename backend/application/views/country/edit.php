<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Country/update', [
                    'autocomplete' => false, 
                    'id' => 'edit_table_master',
                    'method' => 'post'
                ], [
                    'type' => $this->url_encrypt->encode('tbl_table_master'),
                    'id' => $TableMaster->id
                ]);
                ?>
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Country *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" required id="name" value="<?= $TableMaster->name ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="code" class="col-sm-2 col-form-label">Code *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="code" required id="code" value="<?= $TableMaster->code ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="flag" class="col-sm-2 col-form-label">Flag</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="flag" id="flag">
                        <?php if (!empty($TableMaster->flag)): ?>
                            <div class="mt-2">
                                <img src="<?= base_url('uploads/images/' . $TableMaster->image) ?>" alt="Flag" width="100">
                                <input type="hidden" name="existing_flag" value="<?= $TableMaster->image ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/tableMaster') ?>" class="btn btn-secondary waves-effect">Cancel</a>
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
</div>
