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

                </div>
            </form>
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
                            <th>Date and Time</th>
                            <th>Game Id</th>
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
    <!-- end col -->
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
</script>
<script>
function ChangeStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/Game/ChangeStatus') ?>",
        type: "POST",
        data: {
            'id': id,
            'status': status
        },
        success: function(data) {
            if (data) {
                alert('Successfully Change status');
            }
            location.reload();
        }
    });
}
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
            "url": "<?= base_url('backend/Game/Gethistory') ?>",
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
                data: 'game_id'
            },
            {
                data: 'added_date'
            },
            {
                data: 'private'
            },
            {
                data: 'name'
            },
            {
                data: 'winner_id'
            },
            {
                data: 'amount'
            },
            {
                data: 'user_winning_amt'
            },
            {
                data: 'admin_winning_amt'
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
    $('.table').DataTable().ajax.reload();
}

// var userTypeFilterValue = $('#user_type_filter').val();

// // Make the AJAX request with the additional data
// $.ajax({
//     url: "<?= base_url('backend/Game/Gethistory') ?>",
//     method: 'POST',
//     data: {
//          : draw,
//         start: start,
//         length: length,
//         order: order,
//         columns: columns,
//         search: search,
//         user_type_filter: userTypeFilterValue // Add the user type filter value
//     },
//     // ...
// });

function showAllData() {
    $('#sn_date').val('');
    $('#en_date').val('');
    $('.table').DataTable().search('').draw();
}
</script>