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
            <div style="display: flex; margin-left: auto;margin-right: 26px;margin-top: 15px;">
                <div class="row">
                    <div class="col-6 mt-2">
                        <label>Select Winning Type</label>
                    </div>
                    <div class="col-md-6 lp-0">
                        <select class="form-control w-100" name="random" id="random">
                            <option value="0" <?= ($RandomFlag == 0) ? 'selected' : '' ?>>Least</option>
                            <option value="1" <?= ($RandomFlag == 1) ? 'selected' : '' ?>>Random</option>
                            <option value="2" <?= ($RandomFlag == 2) ? 'selected' : '' ?>>Optimization</option>
                        </select>
                    </div>

                </div>
            </div>
            <br>
        </div>
        <div class="card-body">
            <table id="datatable" class="table table-bordered dt-responsive nowrap"
                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Sr no.</th>
                        <th>Date and Time</th>
                        <th>No. of bets</th>
                        <!-- <th>User ID</th> -->
                        <th>Total Bet</th>
                        <th>Admin Profit</th>
                        <th>Winning Type</th>
                        <th>Winnig Amount</th>
                        <th>User Amount</th>
                        <th>Commission Amount</th>
                    </tr>
                </thead>
                <!-- <tbody>
                        <?php
                        foreach ($AllGames as $key => $Games) {
                            ?>
                        <tr>
                            <td><?= $Games->id ?></td>
                            <td><?= date("H:i", strtotime($Games->added_date)) ?></td>
                            <td><u><a href="<?= base_url('backend/AnderBahar/ander_baher_bet/' . $Games->id) ?>">
                                        <?= $Games->total_users ?> </a></u></td>
                            <td><?= $Games->total_amount ?></td>
                            <td><?= $Games->admin_profit ?></td>
                            <td><?= $Games->winning_amount ?></td>
                            <td><?= $Games->user_amount ?></td>
                            <td><?= $Games->comission_amount ?></td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody> -->
            </table>
        </div>
    </div>
</div>
<!-- end col -->
</div>
<script>
// $(document).on('change', '.form-switch', function(e) {
//     e.preventDefault();
//     var game = $(this).attr("name")
//     var type = $(this).val()
//     if (type == 1) {
//         $(this).val(0)
//     } else {
//         $(this).val(1)
//     }
//     jQuery.ajax({
//         type: 'POST',
//         url: '<?= base_url('backend/AnderBahar/ChangeStatus') ?>',
//         data: {
//             type: type
//         },
//         beforeSend: function() {},
//         success: function(response) {},
//         error: function(e) {}
//     })
// });
$(document).on('change', '#random', function(e) {
    e.preventDefault();
    var type = $(this).val()
    //     if(type==1){
    //     $(this).val(0)
    //    }else{
    //    $(this).val(1)
    //     }
    jQuery.ajax({
        type: 'POST',
        url: '<?= base_url('backend/AnderBahar/ChangeStatus') ?>',
        data: {
            type: type
        },
        beforeSend: function() {},
        success: function(response) {},
        error: function(e) {}
    })
});
var dataTable;
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'throw';
    dataTable = $(".table").DataTable({
        // stateSave: true,
        searchDelay: 1000,
        processing: true,
        serverSide: true,
        scrollX: true,
        serverMethod: 'post',
        ajax: {
            "url": "<?= base_url('backend/AnderBahar/Gethistory') ?>",
            "type": "POST",
            "datatype": "json",
            data: function(d) {
                d.min = $('#sn_date').val();
                d.max = $('#en_date').val();

            },
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'added_date'
            },
            {
                data: 'total_users'
            },
            // {
            //     data: 'user_id'
            // },
            {
                data: 'total_amount'
            },
            {
                data: 'admin_profit'
            },
            {
                data: 'random'
            },
            {
                data: 'winning_amount'
            },
            {
                data: 'user_amount'
            },
            {
                data: 'comission_amount'
            },



        ],

        lengthMenu: [
            [10, 50, 100, 200, -1],
            [10, 50, 100, 200, "All"]
        ],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [{
            "extend": 'excel',
            "titleAttr": 'USerExcel',
            "action": newexportaction
        }, {
            "extend": 'pdf',
            "titleAttr": 'userpdf',
            "action": newexportaction
        }]

    });

    function newexportaction(e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
        dt.one('preXhr', function(e, s, data) {
            // Just this once, load all data from the server...
            data.start = 0;
            data.length = 2147483647;
            dt.one('preDraw', function(e, settings) {
                // Call the original action function
                if (button[0].className.indexOf('buttons-copy') >= 0) {
                    $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button,
                        config);
                } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                    $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button,
                            config) :
                        $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button,
                            config);
                } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                    $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button,
                            config) :
                        $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button,
                            config);
                } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                    $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                        $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button,
                            config) :
                        $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button,
                            config);
                } else if (button[0].className.indexOf('buttons-print') >= 0) {
                    $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
                }
                dt.one('preXhr', function(e, s, data) {
                    // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                    // Set the property to what it was before exporting.
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });
                // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                setTimeout(dt.ajax.reload, 0);
                // Prevent rendering of the full data to the DOM
                return false;
            });
        });
        // Requery the server with the new one-time export settings
        dt.ajax.reload();
    }

});

function search() {
    $('.table').DataTable().draw();
}
</script>
<script>
function submitAmountForm() {
    var amount = document.getElementById('amount').value;

    // Get the base URL from PHP
    var url = "<?= base_url('backend/AnderBahar/set_withdraw_amount') ?>";

    // Data to be sent to the server
    var formData = new FormData();
    formData.append('amount', amount);

    // AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                // Display the response from the server in the responseMessage div
                document.getElementById('responseMessage').innerText = 'Amount received: ' + response.amount;
            } catch (e) {
                document.getElementById('responseMessage').innerText = 'Invalid response from server';
            }
        } else {
            // Error message
            document.getElementById('responseMessage').innerText = 'An error occurred';
        }
    };

    xhr.send(formData);
}
</script>
<script>
var baseUrl = "<?= base_url('backend/AnderBahar/set_withdraw_amount') ?>";

function submitAmount() {
    var amount = document.getElementById('amount').value;

    // Log amount and URL for debugging
    console.log('Amount:', amount);
    console.log('URL:', baseUrl);

    // Data to be sent to the server
    var formData = new FormData();
    formData.append('amount', amount);

    // AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', baseUrl, true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                // Display the response from the server in the responseMessage div
                document.getElementById('responseMessage').innerText = 'Amount received: ' + response.amount;
            } catch (e) {
                document.getElementById('responseMessage').innerText = 'Invalid response from server';
            }
        } else {
            document.getElementById('responseMessage').innerText = 'An error occurred';
        }
    };

    xhr.send(formData);
}

function showAllData() {
    $('#sn_date').val('');
    $('#en_date').val('');
    $('.table').DataTable().search('').draw();
}
</script>