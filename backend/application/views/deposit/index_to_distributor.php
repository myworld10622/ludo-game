<div class="row">
    <div class="col-12">
        <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="manualPayment-tab" data-toggle="tab" href="#manualPayment" role="tab"
                    aria-controls="manualPayment" aria-selected="true">Deposits</a>
            </li>
        </ul>
        <div class="tab-content" id="paymentTabsContent">
            <div class="tab-pane fade show active" id="manualPayment" role="tabpanel"
                aria-labelledby="manualPayment-tab">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body table-responsive">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#pending" role="tab"
                                            aria-selected="true">
                                            <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                            <span class="d-none d-sm-block">Pending</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#approved" role="tab"
                                            aria-selected="false">
                                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                            <span class="d-none d-sm-block">Approved</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#rejected" role="tab"
                                            aria-selected="false">
                                            <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                            <span class="d-none d-sm-block">Rejected</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <br>
                                    <!-- Pending Tab -->
                                    <div class="tab-pane p-3 active" id="pending" role="tabpanel">
                                        <table class="table table-bordered" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Agent Name</th>
                                                    <th>Agent ID</th>
                                                    <th>Amounts</th>
                                                    <th>Transaction ID</th>
                                                    <th>Gatway</th>
                                                    <th>Status</th>
                                                    <th>Added Date and Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 0;
                                                foreach ($PendingManualPurchase as $key => $Data) {
                                                    $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $Data->agent ?></td>
                                                    <td><?= $Data->agent_id ?></td>
                                                    <td><?= $Data->amount ?></td>
                                                    <td><?= $Data->txn_id ?></td>
                                                    <td><?= $Data->name ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending</option>
                                                            <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve</option>
                                                            <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject</option>
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Approved Tab -->
                                    <div id="approved" class="tab-pane fade">
                                        <br>
                                        <table class="table table-bordered" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Agent Name</th>
                                                    <th>Agent ID</th>
                                                    <th>Amounts</th>
                                                    <th>Transaction ID</th>
                                                    <th>Gatway</th>
                                                    <th>Status</th>
                                                    <th>Added Date and Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 0;
                                                foreach ($ApprovedManualPurchase as $key => $Data) {
                                                    $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $Data->agent ?></td>
                                                    <td><?= $Data->agent_id ?></td>
                                                    <td><?= $Data->amount ?></td>
                                                    <td><?= $Data->txn_id ?></td>
                                                    <td><?= $Data->name ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve</option>
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Rejected Tab -->
                                    <div id="rejected" class="tab-pane fade">
                                        <br>
                                        <table class="table table-bordered" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Agent Name</th>
                                                    <th>Agent ID</th>
                                                    <th>Amounts</th>
                                                    <th>Transaction ID</th>
                                                    <th>Gatway</th>
                                                    <th>Status</th>
                                                    <th>Added Date and Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 0;
                                                foreach ($RejectedManualPurchase as $key => $Data) {
                                                    $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $Data->agent ?></td>
                                                    <td><?= $Data->agent_id ?></td>
                                                    <td><?= $Data->amount ?></td>
                                                    <td><?= $Data->txn_id ?></td>
                                                    <td><?= $Data->name ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject</option>
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->created_date)) ?></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div> <!-- tab-content -->
                            </div>
                        </div>
                    </div>
                </div> <!-- row -->
            </div> <!-- manualPayment tab-pane -->
        </div>
    </div>
</div>

<script>
// $(document).ready(function () {
//     $('.table').dataTable({
//         dom: 'Bfrtip',
//         buttons: ['excel']
//     });
// });

function ChangeWithDrawalStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/Deposit/ChangeStatusDistributor') ?>",
        type: "POST",
        data: {
            'id': id,
            'status': status
        },
        success: function (data) {
            var response = JSON.parse(data);
            if (response.class == "success") {
                toastr.success(response.msg);
            } else {
                toastr.error(response.msg);
            }
            setTimeout(function () {
                location.reload();
            }, 1000);
        }
    });
}
</script>
