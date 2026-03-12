<section class="section">
    <div class="card">
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <input type="hidden" id="sn_date" value="" name="start_date">
                            <input type="hidden" id="en_date" value="" name="end_date">
                            <label for="crm_attendence_date">Date filter</label>
                            <div id="report" class="form-control" style="cursor: pointer;">
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
        </form>
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
                            <th>Sr no.</th>
                            <th>Game Id</th>
                            <th>Date and Time</th>
                            <th>Winner</th>
                            <th>Winner ID</th>
                            <th>Winning Amount</th>
                            <th>User Amount</th>
                            <th>Admin Comission</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var dataTable;
$(document).ready(function() {
    var dataTable = $('.table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "<?= base_url('backend/Baccarat/Gethistory') ?>",
            type: "POST",
            data: function(d) {
                d.min = $('#sn_date').val();
                d.max = $('#en_date').val();
            }
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
            }
        ],
        lengthMenu: [
            [10, 50, 100, 200, -1],
            [10, 50, 100, 200, "All"]
        ],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [{
                extend: 'excel',
                titleAttr: 'UserExcel',
                action: newexportaction
            },
            {
                extend: 'pdf',
                titleAttr: 'UserPDF',
                action: newexportaction
            }
        ]
    });

    function newexportaction(e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
        dt.one('preXhr', function(e, s, data) {
            data.start = 0;
            data.length = 2147483647;
            dt.one('preDraw', function(e, settings) {
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
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });
                setTimeout(dt.ajax.reload, 0);
                return false;
            });
        });
        dt.ajax.reload();
    }
});

$('#searchBtn').on('click', function() {
    dataTable.ajax.reload();
});

function submitAmount() {
    console.log("Amount entered:", $("#amount").val());
}

function showAllData() {
    $('#sn_date').val('');
    $('#en_date').val('');
    $('.table').DataTable().search('').draw();
}
</script>