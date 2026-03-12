<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/TournamentMaster/insertRound', [
                    'autocomplete' => false,
                    'id' => 'add_tournament_master_round',
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_rounds')])
                ?>


                <div class="form-group row"><label for="round" class="col-sm-2 col-form-label">Round *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="round" min="1" id="round" value="<?= $tournament_round?>" readonly required>
                        <input type="hidden" value="<?= $tournament_id; ?>" name="tournament_id">
                    </div>
                </div>

                <div class="form-group row"><label for="start_date" class="col-sm-2 col-form-label">Start date
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="start_date" value="<?php 
                        if($tournament_round == 1) {
                            echo $tournamentDetails->start_date;
                        }
                        ?>"
                            id="start_date" required>
                    </div>
                </div>
                <div class="form-group row"><label for="start_time" class="col-sm-2 col-form-label">Start time
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="start_time" value="<?php 
                        if($tournament_round == 1) {
                            echo $tournamentDetails->start_time;
                        }
                        ?>"
                            id="start_time" required>
                    </div>
                </div>

                <div class="form-group row"><label for="winner_user_count" class="col-sm-2 col-form-label">Total Winner Each Table
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="winner_user_count"
                            id="winner_user_count" required>
                    </div>
                </div>

                <div class="form-group row"><label for="deal_info" class="col-sm-2 col-form-label">Deal Info
                    </label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="deal_info"
                            id="deal_info">
                    </div>
                </div>

                <div class="form-group row"><label for="table_players_info" class="col-sm-2 col-form-label">Table Players info
                    </label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="table_players_info"
                            id="table_players_info">
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
    <!-- <script>
        $(document).ready(function() {
            $.ajax({
                url: '<?= base_url("backend/TournamentMaster/get_latest_round") ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.latest_round !== undefined) {
                        const nextRound = parseInt(response.latest_round) + 1;
                        $('#round').val(nextRound);
                        console.log("Next round number set to: " + nextRound); // Log the value to verify
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching latest round: ", error);
                }
            });
        });
    </script> -->