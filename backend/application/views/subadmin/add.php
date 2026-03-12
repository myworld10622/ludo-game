<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/SubAdmin/insert', [
                    'autocomplete' => false,
                    'id' => 'add_user'
                    ,
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_users')])
                    ?>
                <div class="form-group row"><label for="first_name" class="col-sm-2 col-form-label">First Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="first_name" required id="first_name">
                    </div>
                </div>

                <div class="form-group row"><label for="last_name" class="col-sm-2 col-form-label">Last Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="last_name" required id="last_name">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email" class="col-sm-2 col-form-label">Email *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="email" name="email" required id="email" pattern=".+@.+\.com$"
                            title="Email must end with .com">
                    </div>
                </div>

                <div class="form-group row"><label for="password" class="col-sm-2 col-form-label">Password *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="password" min="0" name="password" required id="password">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="mobile" class="col-sm-2 col-form-label">Mobile *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="mobile" required id="mobile" maxlength="10"
                            pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number"
                            onkeypress="return isNumeric(event)" oninput="validateLength(this)">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="subadmin" class="col-sm-2 col-form-label">Sub Admin *</label>
                    <div class="col-sm-10">
                        <select class="form-control" id="subadmin" name="subadmin[]" multiple>
                            <option value="APP_USER_MANAGEMENT">App User Mgmt.</option>
                            <option value="ADMIN_USER_MANAGEMENT">Admin User Mgmt.</option>
                            <option value="PAYMENT_MANAGEMENT">Payment Mgmt.</option>
                            <option value="AVIATOR">Aviator</option>
                            <option value="ANDER_BAHAR">Andar Bahar</option>
                            <option value="ANIMAL_ROULETTE">Animal Roullete</option>
                            <option value="BACCARAT">Baccarat</option>
                            <option value="CAR_ROULETTE">Car Roullete</option>
                            <option value="COLOR_PREDICTION">Color Prediction</option>
                            <option value="DRAGON_TIGER">Dragon Tiger</option>
                            <option value="HEAD_TAILS">Head & Tail</option>
                            <option value="JACKPOT_TEENPATTI">Jackpot Teenpatti</option>
                            <option value="JHANDI_MUNDA">Jhandi Munda</option>
                            <option value="LUDO">Ludo</option>
                            <option value="POKER">Poker</option>
                            <option value="RED_VS_BLACK">Red Vs Black</option>
                            <option value="ROULETTE">Roullete</option>
                            <option value="RUMMY_POINT">Rummy Point</option>
                            <option value="RUMMY_POOL">Rummy Pool</option>
                            <option value="RUMMY_DEAL">Rummy Deal</option>
                            <option value="RUMMY_TOURNAMENT">Rummy Tournament</option>
                            <option value="SEVEN_UP_DOWN">Seven Up Down</option>
                            <option value="SLOT_GAME">Slot Game</option>
                            <option value="TEENPATTI">Teenpatti</option>
                            <option value="MASTER_MANAGEMENT">Master Mgmt.</option>
                            <option value="REPORT_MANAGEMENT">Report Mgmt.</option>
                            <option value="NOTIFICATION">Notification</option>
                            <option value="SETTING">Setting</option>
                            <option value="TICKET">Ticket</option>
                        </select>
                    </div>
                </div>


                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/User') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <style>
    .select2-container--default .select2-results__option--highlighted {
        background-color: #0056b3 !important;
        color: white !important;
    }
    </style>

    <script>
    $(document).ready(function() {
        $('#subadmin').select2();
    });
    </script>
    <script>
    function isNumeric(event) {
        var key = event.keyCode || event.which;
        var keyChar = String.fromCharCode(key);
        if (!/^[0-9]$/.test(keyChar)) {
            event.preventDefault();
            return false;
        }
    }

    function validateLength(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        s
        if (input.value.length > 10) {
            input.value = input.value.slice(0, 10);
        }
    }
    </script>

    <script>
    function validateEmail() {
        let email = document.getElementById("email").value.trim();
        let emailError = document.getElementById("emailError");

        if (email.includes("@") && email.endsWith(".com")) {
            emailError.style.display = "none";
            document.getElementById("email").setCustomValidity("");
        } else {
            emailError.style.display = "block";
            document.getElementById("email").setCustomValidity("Invalid email format");
        }
    }
    </script>

</div>