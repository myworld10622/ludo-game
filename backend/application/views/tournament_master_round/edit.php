<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/TournamentMaster/updateRound', [
                    'autocomplete' => false,
                    'id' => 'tbl_rummy_tournament_rounds'
                    ,
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_rounds')])
                    ?>
                <input type="hidden" value="<?= $RoundMaster->id ?>" name="id">
                <input type="hidden" value="<?= $RoundMaster->tournament_id ?>" name="tournament_id">
                <div class="form-group row"><label for="round" class="col-sm-2 col-form-label">Round*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="round" value="<?= $RoundMaster->round ?>"
                            id="round">
                    </div>
                </div>
                <div class="form-group row"><label for="start_date" class="col-sm-2 col-form-label">Start date
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="start_date" id="start_date"
                            value="<?= $RoundMaster->start_date ?>" required>
                    </div>
                </div>
                <div class="form-group row"><label for="start_time" class="col-sm-2 col-form-label">Start time
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="start_time" id="start_time"
                            value="<?= $RoundMaster->start_time ?>" required>
                    </div>
                </div>
                <div class="form-group row"><label for="winner_user_count" class="col-sm-2 col-form-label">Total Winner Each Table
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="winner_user_count"
                            value="<?= $RoundMaster->winner_user_count ?>" id="winner_user_count" required>
                    </div>
                </div>
                <div class="form-group row"><label for="deal_info" class="col-sm-2 col-form-label">Deal Info
                    </label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="deal_info" value="<?= $RoundMaster->deal_info ?>"
                            id="deal_info">
                    </div>
                </div>

                <div class="form-group row"><label for="table_players_info" class="col-sm-2 col-form-label">Table
                        Players info
                    </label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="table_players_info"
                            value="<?= $RoundMaster->table_players_info ?>" id="table_players_info">
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