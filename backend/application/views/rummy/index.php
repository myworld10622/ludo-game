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
            <div class="card-body table-responsive">
                <div class="form-group">
                    <label for="game-category">Game Category:</label>
                    <select class="form-control" id="game-category">
                        <option value="0">Public</option>
                        <option value="1">Private</option>
                        <option value="2">Custom</option>
                    </select>
                </div>
                <table id="game-table" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr no.</th>
                            <th>Game Id</th>
                            <th>Date and Time</th>
                            <th>Game Type</th>
                            <th>Winner</th>
                            <th>Winner ID</th>
                            <th>Winning Amount</th>
                            <th>User Amount</th>
                            <th>Admin Commission</th>
                        </tr>
                    </thead>
                    <!-- Add table body rows here -->
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('game-category').addEventListener('change', function() {
    var table = document.getElementById('game-table');
    var rows = table.getElementsByTagName('tr');
    var selectedOption = this.value;

    for (var i = 1; i < rows.length; i++) {
        var gameTypeCell = rows[i].getElementsByTagName('td')[3];
        var gameType = parseInt(gameTypeCell.textContent || gameTypeCell.innerText);

        if (selectedOption === '0' && gameType === 0) {
            rows[i].style.display = '';
        } else if (selectedOption === '1' && gameType === 1) {
            rows[i].style.display = '';
        } else if (selectedOption === '2' && gameType === 2) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});

function ChangeStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/Rummy/ChangeStatus') ?>",
        type: "POST",
        data: {
            'id': id,
            'status': status
        },
        success: function(data) {
            if (data) {
                alert('Successfully Changed Status');
            }
            location.reload();
        }
    });
}

var dataTable;
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'throw';
    dataTable = $(".table").DataTable({
        searchDelay: 1000,
        processing: true,
        serverSide: true,
        scrollX: true,
        serverMethod: 'post',
        ajax: {
            "url": "<?= base_url('backend/Rummy/Gethistory') ?>",
            "type": "POST",
            "datatype": "json",
            data: function(d) {
                d.min = $('#sn_date').val();
                d.max = $('#en_date').val();
            }
        },
        columns: [
        { data: 'id' },
        { data: 'game_id' },
        { data: 'added_date' },
        { 
            data: 'private', // This is your 'game type' column
            render: function(data, type, row) {
                if (data == 0) {
                    return 'Public';
                } else if (data == 1) {
                    return 'Private';
                } else if (data == 2) {
                    return 'Custom';
                } else {
                    return 'Unknown';
                }
            }
        },
        { data: 'name' },
        { data: 'winner_id' },
        { data: 'amount' },
        { data: 'user_winning_amt' },
        { data: 'admin_winning_amt' },
    ],
    lengthMenu: [
            [10, 50, 100, 200, -1],
            [10, 50, 100, 200, "All"]
        ],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [{
                "extend": 'excel',
                "titleAttr": 'UserExcel',
                "action": newexportaction
            },
            {
                "extend": 'pdf',
                "titleAttr": 'UserPDF',
                "action": newexportaction
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

function search() {
    dataTable.draw();
}

function showAllData() {
    $('#sn_date').val('');
    $('#en_date').val('');

    if ($.fn.DataTable.isDataTable('.table')) {
        var table = $('.table').DataTable();
        table.search('').columns().search('').draw();
    }
}
</script>