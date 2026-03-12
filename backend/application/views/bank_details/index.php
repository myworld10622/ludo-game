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
                            <button type="submit" class="btn btn-success mt-4">Search</button>
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
                <table id="datatable" class="table table-bordered nowrap">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Bank Name</th>
                            <th>IFSC Code</th>
                            <th>Account Holder Name</th>
                            <th>Account Number</th>
                            <th>Passbook</th>
                            <th>Crypto Wallet Type</th>
                            <th>Crypto Address </th>
                            <th>Crypto Qr</th>
                            <th>Added Date and Time</th>
                            <!-- <th>Action</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllBankDetails as $key => $bank) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $bank->user_name ?></td>
                            <td><?= $bank->user_id ?></td>
                            <td><?= $bank->bank_name ?></td>
                            <td><?= $bank->ifsc_code ?></td>
                            <td><?= $bank->acc_holder_name ?></td>
                            <td><?= $bank->acc_no ?></td>
                            <td><img src="<?= base_url('data/post/' . strtolower($bank->passbook_img)); ?>" height="160px" width="300px"></td>
                            <td><?= $bank->crypto_wallet_type ?></td>
                            <td><?= $bank->crypto_address ?></td>
                            <td><img src="<?= base_url('data/post/' . strtolower($bank->crypto_qr)); ?>" height="160px" width="300px"></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($bank->added_date)) ?></td>
                        </tr>
                        <?php }
                        ?>


                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>

<script>

function ChangeWithDrawalStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/Kyc/ChangeStatus') ?>",
        type: "POST",
        data: {
            'id': id,
            'status': status
        },
        success: function(data) {
            var response = JSON.parse(data)
            if (response == true) {
                toastr.success("Status Updated Successfully");
            } else {
                toastr.error("Something Went Wrnog.");
            }

            setTimeout(function() {
                location.reload()
            }, 1000);
        }
    });
}

    $(".back").on("click", function(event) {
        location.reload()
    })
    
    $(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'throw';
    $(".table").DataTable({
        dom: 'Bfrtip',
        buttons: [{
                        "extend": 'excel',
                            "titleAttr": 'USerExcel',
                            
                        },{
                        "extend": 'pdf',
                            "titleAttr": 'userpdf',
                         
                        }] 

    });
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
                     $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                     $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                     $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                     $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
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

});
</script>