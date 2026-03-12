<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/TournamentMaster/insertPrize', ['autocomplete' => false, 'id' => 'add_tournament_master_prize'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_prizes')])
            ?>
                <!-- <div class="form-group row"><label for="tournament_id" class="col-sm-2 col-form-label">tournament_id</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="tournament_id" required
                            id="tournament_id" onkeyup="updateValue(this.value)">
                    </div>
                </div> -->

                <div class="form-group row"><label for="from_position" class="col-sm-2 col-form-label"> Winning From Position *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="from_position" 
                            id="from_position" required min="1" placeholder="Start Position (must be 1 or greater)" >
                            <input type="hidden" value="<?= $tournament_id; ?>" name="tournament_id">
 
                    </div>
                </div>

                <div class="form-group row"><label for="to_position" class="col-sm-2 col-form-label"> Winning To Position
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="to_position" 
                            id="to_position" required>
                    </div>
                </div>
                <div class="form-group row"><label for="players" class="col-sm-2 col-form-label">Players
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="players" 
                            id="players" required readonly >
                    </div>
                </div>
                <div class="form-group row"><label for="winning_price" class="col-sm-2 col-form-label">Winning Price
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="winning_price" 
                            id="winning_price" required >
                    </div>
                </div>
                <div class="form-group row"><label for="given_in_round" class="col-sm-2 col-form-label">Given in Round*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="given_in_round" 
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
    <script>
        $(document).ready(function() {
            // Real-time update for Players field based on Winning From and Winning To Positions
            function calculatePlayers() {
                // Get values from the input fields
                const winningFrom = parseInt($('#from_position').val()) || 0;
                const winningTo = parseInt($('#to_position').val()) || 0;

                // Enforce "Winning From" position to start from 1
                if (winningFrom < 1) {
                    alert("Winning From Position must be 1 or greater.");
                    $('#from_position').val(1);
                }

                // Calculate the number of players
                let players = 0;
                if (winningTo >= winningFrom) {
                    players = (winningTo - winningFrom) + 1;
                }

                // Set the calculated value in the Players field
                $('#players').val(players);
            }

            // Trigger calculatePlayers function on input events
            $('#from_position, #to_position').on('input', calculatePlayers);

        });
    </script>

