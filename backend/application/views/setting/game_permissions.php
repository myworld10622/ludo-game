<h4>Games Permission</h4>

<div class="row">
    <?php if (TEENPATTI==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">3 Patti</h5>
                    <input class="form-check form-switch" type="checkbox" name="teen_patti" id="teen_patti"
                        <?= $Permission->teen_patti ? 'checked' : ''?> value="<?= $Permission->teen_patti ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="teen_patti" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
     <?php if (AVIATOR==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Aviator</h5>
                    <input class="form-check form-switch" type="checkbox" name="aviator" id="aviator"
                        <?= $Permission->Aviator ? 'checked' : ''?> value="<?= $Permission->Aviator ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="aviator" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if (AVIATOR_VERTICAL==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Aviator Vertical</h5>
                    <input class="form-check form-switch" type="checkbox" id="aviator_vertical" name="aviator_vertical"
                        <?= $Permission->aviator_vertical ? 'checked' : ''?>
                        value="<?= $Permission->aviator_vertical ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="aviator_vertical" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    
     <?php if (LOTTERY==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Lottery</h5>
                    <input class="form-check form-switch" type="checkbox" name="lottery" id="lottery"
                        <?= $Permission->Lottery ? 'checked' : ''?> value="<?= $Permission->Lottery ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="lottery" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (DRAGON_TIGER==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Dragon And Tiger</h5>
                    <input class="form-check form-switch" type="checkbox" id="dragon_tiger" name="dragon_tiger"
                        <?= $Permission->dragon_tiger ? 'checked' : ''?>
                        value="<?= $Permission->dragon_tiger ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="dragon_tiger" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (ANDER_BAHAR==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Andar Bahar</h5>
                    <input class="form-check form-switch" type="checkbox" id="andar_bahar" name="andar_bahar"
                        <?= $Permission->andar_bahar ? 'checked' : ''?> value="<?= $Permission->andar_bahar ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="andar_bahar" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (POINT_RUMMY==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Point Rummy</h5>
                    <input class="form-check form-switch" type="checkbox" id="point_rummy" name="point_rummy"
                        <?= $Permission->point_rummy ? 'checked' : ''?> value="<?= $Permission->point_rummy ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="point_rummy" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (POINT_RUMMY==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Private Rummy</h5>
                    <input class="form-check form-switch" type="checkbox" id="private_rummy" name="private_rummy"
                        <?= $Permission->private_rummy ? 'checked' : ''?>
                        value="<?= $Permission->private_rummy ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="private_rummy" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (RUMMY_POOL==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Pool Rummy</h5>
                    <input class="form-check form-switch" type="checkbox" id="pool_rummy"
                        <?= $Permission->pool_rummy ? 'checked' : ''?> value="<?= $Permission->pool_rummy ? 0 : 1 ?>"
                        name="pool_rummy" switch="none">
                    <label class="form-label" for="pool_rummy" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (RUMMY_DEAL==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Deal Rummy</h5>
                    <input class="form-check form-switch" type="checkbox" id="deal_rummy" name="deal_rummy"
                        <?= $Permission->deal_rummy ? 'checked' : ''?> value="<?= $Permission->deal_rummy ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="deal_rummy" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (TEENPATTI==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Private Table</h5>
                    <input class="form-check form-switch" type="checkbox" id="private_table" name="private_table"
                        <?= $Permission->private_table ? 'checked' : ''?>
                        value="<?= $Permission->private_table ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="private_table" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (TEENPATTI==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Custom Boot</h5>
                    <input class="form-check form-switch" type="checkbox" id="custom_boot" name="custom_boot"
                        <?= $Permission->custom_boot ? 'checked' : ''?> value="<?= $Permission->custom_boot ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="custom_boot" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (SEVEN_UP_DOWN==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">7 Up Down</h5>
                    <input class="form-check form-switch" type="checkbox" id="seven_up_down" name="seven_up_down"
                        <?= $Permission->seven_up_down ? 'checked' : ''?>
                        value="<?= $Permission->seven_up_down ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="seven_up_down" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (CAR_ROULETTE==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Car Roulette</h5>
                    <input class="form-check form-switch" type="checkbox" id="car_roulette" name="car_roulette"
                        <?= $Permission->car_roulette ? 'checked' : ''?>
                        value="<?= $Permission->car_roulette ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="car_roulette" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (JACKPOT==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Jackpot 3 Patti</h5>
                    <input class="form-check form-switch" type="checkbox" id="jackpot_teen_patti"
                        name="jackpot_teen_patti" <?= $Permission->jackpot_teen_patti ? 'checked' : ''?>
                        value="<?= $Permission->jackpot_teen_patti ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="jackpot_teen_patti" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (ANIMAL_ROULETTE==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Animal Roulette</h5>
                    <input class="form-check form-switch" type="checkbox" id="animal_roulette" name="animal_roulette"
                        <?= $Permission->animal_roulette ? 'checked' : ''?>
                        value="<?= $Permission->animal_roulette ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="animal_roulette" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (COLOR_PREDICTION==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Color Prediction Horizontal</h5>
                    <input class="form-check form-switch" type="checkbox" id="color_prediction" name="color_prediction"
                        <?= $Permission->color_prediction ? 'checked' : ''?>
                        value="<?= $Permission->color_prediction ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="color_prediction" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (COLOR_PREDICTION_VERTICAL==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Color Prediction Vertical</h5>
                    <input class="form-check form-switch" type="checkbox" id="color_prediction_vertical" name="color_prediction_vertical"
                        <?= $Permission->color_prediction_vertical ? 'checked' : ''?>
                        value="<?= $Permission->color_prediction_vertical ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="color_prediction_vertical" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (POKER==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Poker</h5>
                    <input class="form-check form-switch" type="checkbox" id="poker" name="poker"
                        <?= $Permission->poker ? 'checked' : ''?> value="<?= $Permission->poker ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="poker" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (HEAD_TAILS==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Head & Tails</h5>
                    <input class="form-check form-switch" type="checkbox" id="head_tails" name="head_tails"
                        <?= $Permission->head_tails ? 'checked' : ''?> value="<?= $Permission->head_tails ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="head_tails" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (RED_VS_BLACK==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Red Vs Black</h5>
                    <input class="form-check form-switch" type="checkbox" id="red_vs_black" name="red_vs_black"
                        <?= $Permission->red_vs_black ? 'checked' : ''?>
                        value="<?= $Permission->red_vs_black ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="red_vs_black" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (LUDO_LOCAL==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Ludo Local</h5>
                    <input class="form-check form-switch" type="checkbox" id="ludo_local" name="ludo_local"
                        <?= $Permission->ludo_local ? 'checked' : ''?> value="<?= $Permission->ludo_local ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="ludo_local" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (LUDO==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Ludo Online</h5>
                    <input class="form-check form-switch" type="checkbox" id="ludo_online" name="ludo_online"
                        <?= $Permission->ludo_online ? 'checked' : ''?> value="<?= $Permission->ludo_online ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="ludo_online" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (LUDO_COMPUTER==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Ludo Computer</h5>
                    <input class="form-check form-switch" type="checkbox" id="ludo_computer" name="ludo_computer"
                        <?= $Permission->ludo_computer ? 'checked' : ''?>
                        value="<?= $Permission->ludo_computer ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="ludo_computer" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (BACCARAT==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Bacarate</h5>
                    <input class="form-check form-switch" type="checkbox" id="bacarate" name="bacarate"
                        <?= $Permission->bacarate ? 'checked' : ''?> value="<?= $Permission->bacarate ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="bacarate" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>
    <?php if (JHANDI_MUNDA==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Jhandi Munda</h5>
                    <input class="form-check form-switch" type="checkbox" id="jhandi_munda" name="jhandi_munda"
                        <?= $Permission->jhandi_munda ? 'checked' : ''?>
                        value="<?= $Permission->jhandi_munda ? 0 : 1 ?>" switch="none">
                    <label class="form-label" for="jhandi_munda" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>

    <?php if (ROULETTE==true) { ?>
    <div class="col-xl-2 col-md-1">
        <div class="card ">
            <div class="card-body">
                <div class="mb-">
                    <h5 class="font-14 text-uppercase mt-0">Roulette</h5>
                    <input class="form-check form-switch" type="checkbox" id="roulette" name="roulette"
                        <?= $Permission->roulette ? 'checked' : ''?> value="<?= $Permission->roulette ? 0 : 1 ?>"
                        switch="none">
                    <label class="form-label" for="roulette" data-on-label="On" data-off-label="Off"></label>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>
</div>


<script>
$(document).on('change', '.form-switch', function(e) {
    e.preventDefault();
    var game = $(this).attr("name")
    var type = $(this).val()
    if (type == 1) {
        $(this).val(0)
    } else {
        $(this).val(1)
    }
    console.log(type)
    jQuery.ajax({
        type: 'POST',
        url: '<?= base_url('backend/Setting/ChangeGameStatus') ?>',
        data: {
            name: game,
            type: type
        },
        beforeSend: function() {},
        success: function(response) {},
        error: function(e) {}
    })
});
</script>