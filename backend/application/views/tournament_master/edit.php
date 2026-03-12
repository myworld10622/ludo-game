<b style="color:red">Note: If you edit tournament start date and start time then same date and time should be change in tournament round 1</b>.
<br>
<br>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/tournamentMaster/update', [
                    'autocomplete' => false,
                    'id' => 'edit_tournament_master',
                    'method' => 'post'
                ], [
                    'type' => $this->url_encrypt->encode('tbl_rummy_tournament_master'),
                    'id' => $TournamentMaster->id
                ])
                    ?>
                <div class="form-group row"><label for="registration_start_date"
                        class="col-sm-2 col-form-label">Registration Start Date*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="registration_start_date"
                            value="<?= $TournamentMaster->registration_start_date ?>" required
                            id="registration_start_date" onkeyup="updateValue(this.value)" required>
                    </div>
                </div>

                <div class="form-group row"><label for="registration_start_time"
                        class="col-sm-2 col-form-label">Registration Start time*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="registration_start_time"
                            value="<?= $TournamentMaster->registration_start_time ?>" id="registration_start_time"
                            required>
                    </div>
                </div>

                <div class="form-group row"><label for="start_date" class="col-sm-2 col-form-label">Start Date
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="start_date"
                            value="<?= $TournamentMaster->start_date ?>" id="start_date" required>
                    </div>
                </div>
                <div class="form-group row"><label for="start_time" class="col-sm-2 col-form-label">Start Time
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="start_time"
                            value="<?= $TournamentMaster->start_time ?>" id="start_time" required>
                    </div>
                </div>

                <!-- <div class="form-group row"><label for="max_entry_pass" class="col-sm-2 col-form-label">Total Pass
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="max_entry_pass"
                            value="<?= $TournamentMaster->max_entry_pass ?>" id="max_entry_pass" required>
                    </div>
                </div> -->
                <div class="form-group row"><label for="registration_fee" class="col-sm-2 col-form-label">Registration
                        Fee
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="registration_fee"
                            value="<?= $TournamentMaster->registration_fee ?>" id="registration_fee" required>
                    </div>
                </div>
                <!-- <div class="form-group row"><label for="registration_chips" class="col-sm-2 col-form-label">Registration Chips
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="registration_chips" value="<?= $TournamentMaster->registration_chips ?>"
                            id="registration_chips" required>
                    </div>
                </div> -->
                <div class="form-group row"><label for="winning_amount" class="col-sm-2 col-form-label">Winning Amount
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="winning_amount"
                            value="<?= $TournamentMaster->winning_amount ?>" id="winning_amount" required>
                    </div>
                </div>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Name
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" value="<?= $TournamentMaster->name ?>"
                            id="name" required>
                    </div>
                </div>
                <div class="form-group row"><label for="max_player" class="col-sm-2 col-form-label">Max Player
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="max_player"
                            value="<?= $TournamentMaster->max_player ?>" id="max_player" required>
                    </div>
                </div>
                <div class="form-group row"><label for="total_round" class="col-sm-2 col-form-label">Total Round
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="total_round"
                            value="<?= $TournamentMaster->total_round ?>" id="total_round" required>
                    </div>
                </div>
                <!-- Tournament Type Dropdown -->
                <div class="form-group row" id="tournament_type_id">
                    <label for="tournament_type_id" class="col-sm-2 col-form-label">Tournament Type*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <select class="form-control" id="tournament_type_id" name="tournament_type_id" required>
                            <option value="">Select Tournament Type</option>
                            <?php foreach ($AllTournamentTypes as $type): ?>
                                <option value="<?= $type->id ?>" <?= ($type->id == $TournamentMaster->tournament_type_id) ? 'selected' : '' ?>>
                                    <?= $type->name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- For is_mega_tournament -->
                <div class="form-group row">
                    <label for="is_mega_tournament" class="col-sm-2 col-form-label">Is Mega Tournament*</label>
                    <div class="col-sm-2 d-flex">
                        <input class="form-control" type="checkbox" name="is_mega_tournament" id="is_mega_tournament"
                            value="1" <?= $TournamentMaster->is_mega_tournament ? 'checked' : '' ?>>
                        <label for="is_mega_tournament" class="font-weight-bold">Yes</label>
                    </div>
                </div>

                <!-- For is_winner_get_pass -->
                <div class="form-group row">
                    <label for="is_winner_get_pass" class="col-sm-2 col-form-label">Is Winner Get Pass*</label>
                    <div class="col-sm-2 d-flex align-items-center">
                        <input class="form-control" type="checkbox" name="is_winner_get_pass" id="is_winner_get_pass"
                            value="1" <?= $TournamentMaster->is_winner_get_pass ? 'checked' : '' ?>>
                        <label for="is_winner_get_pass" class="font-weight-bold ml-2">Yes</label>
                    </div>
                </div>


                <!-- Dropdown for pass_of_tournament_id -->
                <div class="form-group row" id="inputContainer"
                    style="<?= $TournamentMaster->is_winner_get_pass ? 'display: flex;' : 'display: none;' ?>">
                    <label for="pass_of_tournament_id" class="col-sm-2 col-form-label">Pass Of Tournament Id*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <select class="form-control" id="pass_of_tournament_id" name="pass_of_tournament_id">
                            <option value="">Select Pass Tournament</option>
                            <option value="standard" <?= $TournamentMaster->pass_of_tournament_id == 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="vip" <?= $TournamentMaster->pass_of_tournament_id == 'vip' ? 'selected' : '' ?>>
                                VIP</option>
                            <option value="premium" <?= $TournamentMaster->pass_of_tournament_id == 'premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </div>
                </div>


                <!-- Input field for total_pass_count -->
                <div class="form-group row" id="inputContainer1"
                    style="<?= $TournamentMaster->is_winner_get_pass ? 'display: flex;' : 'display: none;' ?>">
                    <label for="total_pass_count" class="col-sm-2 col-form-label">Total Pass Count*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <input type="number" class="form-control" id="total_pass_count" name="total_pass_count"
                            value="<?= $TournamentMaster->total_pass_count ?>">
                    </div>
                </div>



                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/TournamentMaster') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkbox = document.getElementById('is_winner_get_pass');
            var inputContainer = document.getElementById('inputContainer');
            var inputContainer1 = document.getElementById('inputContainer1');

            // Show or hide input fields based on the initial state
            if (checkbox.checked) {
                inputContainer.style.display = 'flex';
                inputContainer1.style.display = 'flex';
            }

            checkbox.addEventListener('change', function () {
                if (checkbox.checked) {
                    inputContainer.style.display = 'flex';
                    inputContainer1.style.display = 'flex';
                } else {
                    inputContainer.style.display = 'none';
                    inputContainer1.style.display = 'none';
                }
            });
        });
    </script>