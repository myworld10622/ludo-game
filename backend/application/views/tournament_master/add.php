<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/TournamentMaster/insert', [
                    'autocomplete' => false,
                    'id' => 'add_tournament_master',
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_master')])
                    ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Name
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" id="name" required>
                    </div>
                </div>

                <div class="form-group row"><label for="registration_start_date"
                        class="col-sm-2 col-form-label">Registration start date*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="registration_start_date" required
                            id="registration_start_date" onkeyup="updateValue(this.value)" required>
                    </div>
                </div>

                <div class="form-group row"><label for="registration_start_time"
                        class="col-sm-2 col-form-label">Registration start time*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="registration_start_time"
                            id="registration_start_time" required>
                    </div>
                </div>

                <div class="form-group row"><label for="start_date" class="col-sm-2 col-form-label">Start Date*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="date" name="start_date" id="start_date" required>
                    </div>
                </div>
                <div class="form-group row"><label for="start_time" class="col-sm-2 col-form-label">Start time*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="time" name="start_time" id="start_time" required>
                    </div>
                </div>
                <!-- <div class="form-group row"><label for="max_entry_pass" class="col-sm-2 col-form-label">Total
                        Pass*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="max_entry_pass" id="max_entry_pass" required>
                    </div>
                </div> -->
                <div class="form-group row"><label for="registration_fee" class="col-sm-2 col-form-label">Registration
                        Fee*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="registration_fee" id="registration_fee"
                            required>
                    </div>
                </div>
                <!-- <div class="form-group row"><label for="registration_chips" class="col-sm-2 col-form-label">Registration Chips*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number"  name="registration_chips" 
                            id="registration_chips" required>
                    </div>
                </div> -->
                <div class="form-group row"><label for="winning_amount" class="col-sm-2 col-form-label">Winning
                        Amount*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="winning_amount" id="winning_amount" required>
                    </div>
                </div>
                <div class="form-group row"><label for="max_player" class="col-sm-2 col-form-label">Max player*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" step="1" name="max_player" id="max_player" required>
                    </div>
                </div>
                <div class="form-group row"><label for="total_round" class="col-sm-2 col-form-label">Total
                        Round*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="total_round" id="total_round" required>
                    </div>
                </div>
                <div class="form-group row" id="tournament_type_id">
                    <!-- Tournament Type Field -->
                    <label for="tournament_type_id" class="col-sm-2 col-form-label">Tournament Type*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <select class="form-control" id="tournament_type_id" name="tournament_type_id" required>
                            <option value="">Select Tournament Type</option>
                            <?php foreach ($AllTournamentTypes as $type): ?>
                                <option value="<?= $type->id ?>"> <?= $type->name ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row"><label for="is_mega_tournament" class="col-sm-2 col-form-label">Is Mega
                        Tournament*</label>
                    <div class="col-sm-2 d-flex">
                        <input class="form-control" type="checkbox" name="is_mega_tournament" id="is_mega_tournament"
                            value="1">
                        <label for="is_mega_tournament" class="font-weight-bold">Yes</label>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="is_winner_get_pass" class="col-sm-2 col-form-label">Is Winner Get Pass*</label>
                    <div class="col-sm-2 d-flex align-items-center">
                        <input class="form-control" type="checkbox" name="is_winner_get_pass" id="is_winner_get_pass"
                            value="1">
                        <label for="is_winner_get_pass" class="font-weight-bold ml-2">Yes</label>
                    </div>
                </div>

                <!-- Hidden container that will show dropdown and input field when checkbox is selected -->
                <div class="form-group row" id="inputContainer" style="display: none;">
                    <!-- Ticket Type Field -->
                    <label for="pass_of_tournament_id" class="col-sm-2 col-form-label">Pass Of Tournament
                        Id*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <select class="form-control" id="pass_of_tournament_id" name="pass_of_tournament_id">
                            <option value="">Select Paas Tournament</option>
                            <option value="standard">Standard</option>
                            <option value="vip">VIP</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row" id="inputContainer1" style="display: none;">
                    <!-- Ticket Max User Field -->
                    <label for="total_pass_count" class="col-sm-2 col-form-label">Total Pass Count*</label>
                    <div class="col-sm-10 d-flex align-items-center">
                        <input type="number" class="form-control" id="total_pass_count" name="total_pass_count">
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
        // Wait for the DOM to load
        document.addEventListener('DOMContentLoaded', function () {
            // Get the checkbox and the input container
            var checkbox = document.getElementById('is_winner_get_pass');
            var inputContainer = document.getElementById('inputContainer');
            var inputContainer1 = document.getElementById('inputContainer1');

            // Add an event listener to toggle visibility of the input container
            checkbox.addEventListener('change', function () {
                if (checkbox.checked) {
                    inputContainer.style.display = 'flex'; // Show dropdown and input field, using flex for alignment
                    inputContainer1.style.display = 'flex'; // Show dropdown and input field, using flex for alignment
                } else {
                    inputContainer.style.display = 'none'; // Hide dropdown and input field
                }
            });
        });
    </script>