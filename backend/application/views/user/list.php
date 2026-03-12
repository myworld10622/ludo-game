<div class="row">
    <div class="col-12">
        <div class="card p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="hidden" id="sn_date" value="" name="start_date">
                        <input type="hidden" id="en_date" value="" name="end_date">
                        <label for="crm_attendence_date">Date Filter</label>
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
                        <button type="button" onclick="showAllUsers()" class="btn btn-primary mt-4">All Users</button>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <button type="button" onclick="showActiveUsers()" class="btn btn-primary mt-4">Active
                            Users</button>
                        <input type="hidden" id="active_filter" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
                                <th>Name</th>
                                <th>User ID</th>
                                <th>Image</th>
                                <th>Mobile</th>
                            <?php } else { ?>
                                <th>Name</th>
                                <th>User ID</th>
                                <th>Image</th>
                                <th><?= $Setting->bank_detail_field ?></th>
                                <th><?= $Setting->adhar_card_field ?></th>
                                <th><?= $Setting->upi_field ?></th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>User Type</th>
                                <?php if (USER_CATEGORY) { ?>
                                    <th>User Category</th>
                                <?php } ?>
                                <th>Total Wallet</th>
                                <th>Winning Wallet</th>
                                <th>Unutilized Wallet</th>
                                <th>Bonus Wallet</th>
                                <th>On Table</th>
                                <th>Status</th>
                                <th>Added Date and Time</th>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    var dataTable

    function ChangeStatus(id, status) {
        jQuery.ajax({
            url: "<?= base_url('backend/user/ChangeStatus') ?>",
            type: "POST",
            data: {
                'id': id,
                'status': status
            },
            success: function (data) {
                if (data) {
                    alert('Successfully Change status');
                }
                location.reload();
            }
        });
    }

    $(document).ready(function () {
        $.fn.dataTable.ext.errMode = 'throw';

        var columns;

        <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
            // Include all columns
            columns = [{
                data: 'id'
            },
            {
                data: 'name'
            },
            {
                data: 'ID'
            },
            {
                data: 'profile_pic'
            },
            {
                data: 'mobile'
            }
            ];



        <?php } else { ?>
            // Include only Name and Mobile columns

            columns = [{
                data: 'id'
            },
            {
                data: 'name'
            },
            {
                data: 'ID'
            },
            {
                data: 'profile_pic'
            },
            {
                data: 'bank_detail'
            },
            {
                data: 'adhar_card'
            },
            {
                data: 'upi'
            },
            {
                data: 'email'
            },
            {
                data: 'mobile'
            },
            {
                data: 'user_type'
            },
                <?php if (USER_CATEGORY) { ?> {
                    data: 'user_category'
                },
                <?php } ?> {
                data: 'wallet'
            },
            {
                data: 'winning_wallet'
            },
            {
                data: 'unutilized_wallet'
            },
            {
                data: 'bonus_wallet'
            },
            {
                data: 'on_table'
            },
            {
                data: 'status'
            },
            {
                data: 'added_date'
            },
            {
                data: 'action'
            }
            ];
        <?php } ?>

        dataTable = $(".table").DataTable({
            searchDelay: 1000,
            processing: true,
            serverSide: true,
            order: [
                [0, 'desc']
            ],
            scrollX: true,
            serverMethod: 'post',
            ajax: {
                url: "<?= base_url('backend/user/GetUsers') ?>",
                data: function (d) {
                    d.min = $('#sn_date').val();
                    d.max = $('#en_date').val();
                    d.active = $('#active_filter').val()
                },
            },
            columns: columns,

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
        //.fnAdjustColumnSizing(false);


        function newexportaction(e, dt, button, config) {
            var self = this;
            var oldStart = dt.settings()[0]._iDisplayStart;
            dt.one('preXhr', function (e, s, data) {
                // Just this once, load all data from the server...
                data.start = 0;
                data.length = 2147483647;
                dt.one('preDraw', function (e, settings) {
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
                    dt.one('preXhr', function (e, s, data) {
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

        setTimeout(function () {
            showAllUsers();
        }, 1000);
        // showAllUsers();
    });

    function showAllUsers() {
        $('#sn_date').val(''); // Clear start date
        $('#en_date').val(''); // Clear end date
        $('#active_filter').val('0');
        dataTable.ajax.reload(); // Reload DataTable without filters
    }

    function showActiveUsers() {
        $('#active_filter').val('1');
        dataTable.ajax.reload();
    }

    function search() {
        dataTable.draw();
    }
</script>