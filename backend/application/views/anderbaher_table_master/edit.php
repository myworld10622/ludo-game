<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/anderbaharTableMaster/update', ['autocomplete' => false, 'id' => 'edit_rummy_table_master'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_rummy_table_master'),
                'id'=> $AnderbaharTableMaster->id])
                ?>
                <div class="form-group row"><label for="min_coin" class="col-sm-2 col-form-label">Min Coin
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="min_coin" required
                            id="min_coin" value="<?= $AnderbaharTableMaster->min_coin?>"
                            onkeyup="updateValue(this.value)">
                    </div>
                </div>

                <div class="form-group row"><label for="max_coin" class="col-sm-2 col-form-label">Max Coin *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="max_coin" id="max_coin"
                            value="<?= $AnderbaharTableMaster->max_coin?>" >
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
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
    <script>
    function updateValue(x) {
        $('#point_value').val(x * 80);
    }
    </script>