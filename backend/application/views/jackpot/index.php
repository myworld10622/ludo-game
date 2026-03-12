<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <input type="hidden" id="sn_date" value="" name="start_date">
                        <input type="hidden" id="en_date" value="" name="end_date">
                        <label for="crm_attendence_date">Date filter</label>
                        <div id="report" class="form-control" style=" cursor: pointer; ">
                            <i class="fa fa-calendar"></i>&nbsp;
                            <span></span>
                            <i class="fa fa-caret-down"></i>
                        </div>
                    </div>
                </div>

                <div class="col-1">
                    <div class="form-group">
                        <button type="button" onclick="search()" class="btn btn-success mt-4">Search</button>
                    </div>
                </div>

                <div class="col-2">
                    <div class="form-group">
                        <button type="button" onclick="showAllData()" class="btn btn-primary mt-4">All Data</button>
                    </div>
                </div>

                <!-- Spacer to push the input field to the right -->
                <div class="col"></div>

                <!-- Minimum Wallet for Bet input field on the right side -->
                <!-- <div class="col-2">
                    <div class="form-group">
                        <label for="amount">Minimum Wallet for Bet</label>
                        <input type="number" id="amount" name="amount" class="form-control" onkeyup="submitAmount()"
                            value="<?= isset($setting) ? $setting : '' ?>" required>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</section>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Game Id</th>
                            <th>Date and Time</th>
                            <th>No. of bets</th>
                            <th>Total Bet</th>
                            <th>Winning Amount</th>
                            <th>User Amount</th>
                            <th>Commission Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($AllGames as $Games) {
                            $bet = 0;
                            $users = 0;
                            $amt = 0;
                            if (!empty($Games->details)) {
                                foreach ($Games->details as $game) {
                                    $bet += $game->amount;
                                    $users++;
                                    $amt += $game->winning_amount;
                                }
                            } ?>
                        <tr>
                            <td><?= $Games->id ?></td>
                            <td><?= date("d-m-Y H:i:s A", strtotime($Games->added_date)) ?></td>
                            <td><u><a href="<?= base_url('backend/Jackpot/jackpot_bet/' . $Games->id) ?>"> <?= $users ?>
                                    </a></u></td>
                            <td><?= $bet ?></td>
                            <td><?= $amt ?></td>
                            <td><?= $Games->user_amount ?></td>
                            <td><?= $Games->comission_amount ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function search() {
    dataTable.draw();
}

function submitAmount() {
    var amount = $('#amount').val();
    jQuery.ajax({
        type: 'POST',
        url: '<?= base_url('backend/Jackpot/UpdateWalletLimit') ?>',
        data: {
            amount: amount
        },
        success: function(response) {},
        error: function(e) {}
    });
}

$(document).ready(function() {
    var dataTable = $('.table').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [{
            "extend": 'excel',
            "titleAttr": 'User Excel',
            "action": newexportaction
        }, {
            "extend": 'pdf',
            "titleAttr": 'User PDF',
            "action": newexportaction
        }]
    });

    function newexportaction(e, dt, button, config) {
        var self = this;
        dt.one('preXhr', function(e, s, data) {
            data.start = 0;
            data.length = 2147483647;
            dt.one('preDraw', function() {
                if (button[0].className.includes('buttons-excel')) {
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button,
                        config);
                } else if (button[0].className.includes('buttons-pdf')) {
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button,
                        config);
                }
            });
        });
        dt.ajax.reload();
    }
});

function showAllData() {
    $('#sn_date').val('');
    $('#en_date').val('');

    if ($.fn.DataTable.isDataTable('.table')) {
        var table = $('.table').DataTable();
        table.search('').columns().search('').draw();
    }
}
</script>