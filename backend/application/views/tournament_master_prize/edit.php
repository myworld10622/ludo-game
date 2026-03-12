<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/TournamentMaster/updatePrize', ['autocomplete' => false, 'id' => 'update_tournament_master_prize'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_prizes')])
            ?>
                <!-- <div class="form-group row"><label for="tournament_id" class="col-sm-2 col-form-label">tournament_id</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="tournament_id" required value="<?= $PrizeMaster->tournament_id ?>"
                            id="tournament_id" onkeyup="updateValue(this.value)">
                    </div>
                </div> -->
                <input type="hidden" value="<?= $PrizeMaster->id ?>" name="id">
                <div class="form-group row"><label for="from_position" class="col-sm-2 col-form-label"> Winning From Position*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="from_position" value="<?= $PrizeMaster->from_position ?>"
                            id="from_position" required>
                        <input type="hidden" value="<?= $PrizeMaster->id ?>" name="id">
                    </div>
                </div>

                <div class="form-group row"><label for="to_position" class="col-sm-2 col-form-label">Winning To Position
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="to_position" value="<?= $PrizeMaster->to_position ?>"
                            id="to_position" required>
                    </div>
                </div>
                <div class="form-group row"><label for="players" class="col-sm-2 col-form-label">Players
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="players" value="<?= $PrizeMaster->players ?>"
                            id="players" required>
                    </div>
                </div>
                <div class="form-group row"><label for="winning_price" class="col-sm-2 col-form-label">Winning Price
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" step="0.01" name="winning_price" value="<?= $PrizeMaster->winning_price ?>"
                            id="winning_price" required>
                    </div>
                </div>
                <div class="form-group row"><label for="given_in_round" class="col-sm-2 col-form-label">Given in Round
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" step="0.01" name="given_in_round" value="<?= $PrizeMaster->given_in_round ?>"
                            id="given_in_round" required>
                    </div>
                </div>
            
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="javascript:history.back()" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>
