<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/pokerMaster/update', [
                    'autocomplete' => false, 'id' => 'edit_table_master', 'method' => 'post'
                ], [
                    'type' => $this->url_encrypt->encode('tbl_poker_table_master'),
                    'id' => $PokerMaster->id
                ])
                ?>
                <div class="form-group row"><label for="boot_value" class="col-sm-2 col-form-label">Boot Value *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" step="0.01" name="boot_value" required id="boot_value" value="<?= $PokerMaster->boot_value ?>" onkeyup="updateValue(this.value)">
                    </div>
                </div>

                <div class="form-group row"><label for="blind_1" class="col-sm-2 col-form-label">Blind 1 Value *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" step="1" name="blind_1" required
                            id="blind_1" value="<?= $PokerMaster->blind_1 ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="blind_2" class="col-sm-2 col-form-label">Blind 2 Value *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" step="1" name="blind_2" required
                            id="blind_2" value="<?= $PokerMaster->blind_2 ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="city" class="col-sm-2 col-form-label">City *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="city" required id="city" value="<?= $PokerMaster->city ?>">
                    </div>
                </div>

                <div class="form-group row"><label for="image" class="col-sm-2 col-form-label">Image *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image" id="image">
                    </div>
                </div>

                <div class="form-group row"><label for="image_bg" class="col-sm-2 col-form-label">Image Bg *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image_bg" id="image_bg">
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
            $('#chaal_limit').val(x * 128);
            $('#pot_limit').val(x * 1024);
        }
    </script>