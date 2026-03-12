<section class="section">
    <div class="card">
        <div class="card-body">
            <form id="withdraw-from">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <input type="hidden" id="sn_date" value="" name="start_date">
                            <input type="hidden" id="en_date" value="" name="end_date">
                            <input type="hidden" id="tab_value" value="" name="tab_active">
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
                <ul class="nav nav-tabs">
                    <!-- <li class="active"><a data-toggle="tab" href="#pending">Pending</a></li>
                    <li><a data-toggle="tab" href="#approved">Approved</a></li>
                    <li><a data-toggle="tab" href="#rejected">Rejected</a></li> -->
                    <li class="nav-item">
                        <a class="nav-link <?= (empty($tab_active) || $tab_active==1)?'active':''?>" data-toggle="tab"
                            href="#pending" role="tab" id="tab_pending" aria-selected="true">
                            <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                            <span class="d-none d-sm-block">Pending</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (!empty($tab_active) && $tab_active=='2')?'active':''?>"
                            data-toggle="tab" id="tab_approved" href="#approved" role="tab" aria-selected="false">
                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                            <span class="d-none d-sm-block">Approved</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (!empty($tab_active) && $tab_active=='3')?'active':''?>"
                            id="tab_rejected" data-toggle="tab" href="#rejected" role="tab" aria-selected="false">
                            <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                            <span class="d-none d-sm-block">Rejected</span>
                        </a>
                    </li>
                </ul>



                <div class="tab-content">
                    <br>
                    <div class="tab-pane p-3  <?= (empty($tab_active) || $tab_active==1)?'active':''?>" id="pending"
                        role="tabpanel">
                        <!-- <div id="pending" class="tab-pane fade in active"> -->
                        <table class="table table-bordered"
                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>User Name</th>
                                    <th>User ID</th>
                                    <th>Type</th>
                                    <th>Bank Name</th>
                                    <th>IFSC Code</th>
                                    <th>Account Number</th>
                                    <th>Crypto Address</th>
                                    <th>Cryto Wallet Type</th>
                                    <th>Crypto QR</th>
                                    <th>Price</th>
                                    <th>Coins</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                    <th>Added Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($Pending as $key => $Data) {
                                    $i++;
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $Data->user_name ?></td>
                                    <td><?= $Data->user_id ?></td>
                                    <td><?= ($Data->type==0)?'Bank':'Crypto'; ?></td>
                                    <td><?= $Data->bank_name ?></td>
                                    <td><?= $Data->ifsc_code ?></td>
                                    <td><?= $Data->acc_no ?></td>
                                    <td><?= $Data->crypto_address ?></td>
                                    <td><?= $Data->crypto_wallet_type ?></td>
                                    <td><img src="<?= base_url() ?>data/post/<?= $Data->crypto_qr ?>" alt="QR Code"
                                            style="width: 100px; height: 100px;"></td>
                                    <td><?= $Data->price ?></td>
                                    <td><?= $Data->coin ?></td>
                                    <td>
                                        <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                            <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending
                                            </option>
                                            <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve
                                            </option>
                                            <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <a href="<?= base_url('backend/user/LadgerReports/' . $Data->user_id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="View Ledger Report">
                                            <span class="ti-wallet"></span>
                                        </a>
                                    </td>

                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                </tr>
                                <?php }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="approved" class="tab-pane  <?= (!empty($tab_active) && $tab_active=='2')?'active':''?>">
                        <br>
                        <table class="table table-bordered"
                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>User Name</th>
                                    <th>User ID</th>
                                    <th>Bank Name</th>
                                    <th>IFSC Code</th>
                                    <th>Account Number</th>
                                    <th>Crypto Address</th>
                                    <th>Cryto Wallet Type</th>
                                    <th>Crypto QR</th>
                                    <th>Mobile</th>
                                    <th>Coin</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                    <th>Added Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($Approved as $key => $Data) {
                                    $i++;
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $Data->user_name ?></td>
                                    <td><?= $Data->user_id ?></td>
                                    <td><?= $Data->bank_name ?></td>
                                    <td><?= $Data->ifsc_code ?></td>
                                    <td><?= $Data->acc_no ?></td>
                                    <td><?= $Data->crypto_address ?></td>
                                    <td><?= $Data->crypto_wallet_type ?></td>
                                    <td><img src="<?= base_url() ?>data/post/<?= $Data->crypto_qr ?>" alt="QR Code"
                                            style="width: 100px; height: 100px;"></td>
                                    <td><?= $Data->mobile ?></td>
                                    <td><?= $Data->coin ?></td>
                                    <td>
                                        <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                            <!--   <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending</option> -->
                                            <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approved
                                            </option>
                                            <!--  <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject</option> -->
                                        </select>
                                    </td>

                                    <td>
                                        <a href="<?= base_url('backend/user/LadgerReports/' . $Data->user_id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="View Ledger Report">
                                            <span class="ti-wallet"></span>
                                        </a>
                                    </td>

                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                </tr>
                                <?php }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="rejected" class="tab-pane  <?= (!empty($tab_active) && $tab_active=='3')?'active':''?>">
                        <br>
                        <table class="table table-bordered"
                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>User Name</th>
                                    <th>User ID</th>
                                    <th>Bank Name</th>
                                    <th>IFSC Code</th>
                                    <th>Account Number</th>
                                    <th>Crypto Address</th>
                                    <th>Cryto Wallet Type</th>
                                    <th>Crypto QR</th>
                                    <th>Mobile</th>
                                    <th>Coin</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                    <th>Added Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($Rejected as $key => $Data) {
                                    $i++;
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $Data->user_name ?></td>
                                    <td><?= $Data->user_id ?></td>
                                    <td><?= $Data->bank_name ?></td>
                                    <td><?= $Data->ifsc_code ?></td>
                                    <td><?= $Data->acc_no ?></td>
                                    <td><?= $Data->crypto_address ?></td>
                                    <td><?= $Data->crypto_wallet_type ?></td>
                                    <td><img src="<?= base_url() ?>data/post/<?= $Data->crypto_qr ?>" alt="QR Code"
                                            style="width: 100px; height: 100px;"></td>
                                    <td><?= $Data->mobile ?></td>
                                    <td><?= $Data->coin ?></td>
                                    <td>
                                        <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                            <!--                                         <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending</option>
                                        <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve</option>
 -->
                                            <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Rejected
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <a href="<?= base_url('backend/user/LadgerReports/' . $Data->user_id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="View Ledger Report">
                                            <span class="ti-wallet"></span>
                                        </a>
                                    </td>


                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                </tr>
                                <?php }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>

<script>
var dataTable
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'throw';
    dataTable = $('.table').dataTable({
        dom: 'Bfrtip',
        buttons: [{
            "extend": 'excel',
            "titleAttr": 'USerExcel',
        }, {
            "extend": 'pdf',
            "titleAttr": 'userpdf',
        }]
    });
})

// $(document).ready(function() {
//     $('.table').dataTable();
// })

function ChangeWithDrawalStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/WithdrawalLog/ChangeStatus') ?>",
        type: "POST",
        data: {
            'id': id,
            'status': status
        },
        success: function(data) {
            var response = JSON.parse(data)
            if (response.class == "success") {
                toastr.success(response.msg);
            } else {
                toastr.error(response.msg);
            }

            setTimeout(function() {
                location.reload()
            }, 1000);
        }
    });
}

$(document).ready(function() {
    $("#tab_pending").on("click", function(e) {
        $('#tab_value').val(1)
    });
    $("#tab_approved").on("click", function(e) {
        $('#tab_value').val(2)
    });
    $("#tab_rejected").on("click", function(e) {
        $('#tab_value').val(3)
    });
});

function showAllData() {

    $('#sn_date').val(''); // Clear start date
    $('#en_date').val(''); // Clear end date
    $("#withdraw-from").submit();
    // $('.table').DataTable().draw(false);
    // $('.table').DataTable();
    // dataTable.ajax.reload(); // Reload DataTable without filters
}
</script>