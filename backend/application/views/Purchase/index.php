<!-- <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Plan ID</th>
                            <th>Coins</th>
                            <th>Price</th> -->
<!-- <th>Payment Status</th> -->
<!-- <th>Added Date and Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllPurchase as $key => $Purchase) {
                            $i++;
                            ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $Purchase->name ?></td>
                            <td><?= $Purchase->user_id ?></td>
                            <td><?= $Purchase->plan_id ?></td>
                            <td><?= $Purchase->coin ?></td>
                            <td><?= $Purchase->price ?></td> -->
<!-- <td><?= ($Purchase->payment == 0) ? 'Pending' : 'Done' ?></td> -->
<!-- <td><?= date("d-m-Y h:i:s A", strtotime($Purchase->added_date)) ?></td>
                        </tr>
                        <?php }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> -->
<!-- end col -->
<!-- </div>
<script>
$(document).ready(function() {
    $('.table').dataTable({
        dom: 'Bfrtip',
        "buttons": [
            'excel'
        ]
    });
})
</script> -->

<div class="row">
    <div class="col-12">
        <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link" id="autoPayment-tab" data-toggle="tab" href="#autoPayment" role="tab"
                    aria-controls="autoPayment" aria-selected="true">Auto Payment</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" id="manualPayment-tab" data-toggle="tab" href="#manualPayment" role="tab"
                    aria-controls="manualPayment" aria-selected="false">Manual Payment</a>
            </li>
        </ul>
        <div class="tab-content" id="paymentTabsContent">
            <div class="tab-pane fade" id="autoPayment" role="tabpanel" aria-labelledby="autoPayment-tab">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered dt-responsive nowrap"
                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>User Name</th>
                                    <th>User ID</th>
                                    <th>Plan ID</th>
                                    <th>Payment Type</th>
                                    <th>Payment Status</th>
                                    <th>Coins</th>
                                    <th>Price</th>
                                    <th>Added Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($AllPurchase as $key => $Purchase) {
                                    $i++;
                                    ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $Purchase->name ?></td>
                                    <td><?= $Purchase->user_id ?></td>
                                    <td><?= $Purchase->plan_id ?></td>
                                    <td><?php if($Purchase->transaction_type==1){
                                            echo "Manual";
                                        }else if($Purchase->transaction_type==2){
                                            echo "Crypto";
                                        }else{
                                            echo "INR";
                                        } ?></td>
                                    <td><?php if($Purchase->status== 0){
                                            echo "Pending";
                                        }else if($Purchase->status==1){
                                            echo "Sucessful";
                                        }else{
                                            echo "Failed";
                                        }?>
                                    </td>
                                    <td><?= $Purchase->coin ?></td>
                                    <td><?= $Purchase->price ?></td>
                                    <td><?= date("d-m-Y h:i:s A", strtotime($Purchase->added_date)) ?></td>
                                </tr>
                                <?php }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade show active" id="manualPayment" role="tabpanel"
                aria-labelledby="manualPayment-tab">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body table-responsive">
                                <ul class="nav nav-tabs">
                                    <!-- <li class="active"><a data-toggle="tab" href="#pending">Pending</a></li>
                                    <li><a data-toggle="tab" href="#approved">Approved</a></li>
                                    <li><a data-toggle="tab" href="#rejected">Rejected</a></li> -->
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
                                    <div class="tab-pane p-3 active" id="pending" role="tabpanel">
                                        <!-- <div id="pending" class="tab-pane fade in active"> -->
                                        <table class="table table-bordered"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>User Name</th>
                                                    <th>User ID</th>
                                                    <th>Transaction ID</th>
                                                    <th>transacation Photo</th>
                                                    <th>Coins</th>
                                                    <th>Price</th>
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
                                                    <td><?= $Data->name ?></td>
                                                    <td><?= $Data->user_id ?></td>
                                                    <td><?= $Data->utr ?></td>
                                                    <td><img src="<?= base_url('data/post/' . $Data->photo) ?>"
                                                            alt="Transaction Photo" width="200" height="200"></td>
                                                    <td><?= $Data->coin ?></td>
                                                    <td><?= $Data->price ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <option value="0"
                                                                <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending
                                                            </option>
                                                            <option value="1"
                                                                <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve
                                                            </option>
                                                            <option value="2"
                                                                <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->added_date)) ?></td>
                                                </tr>
                                                <?php }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div id="approved" class="tab-pane fade">
                                        <br>
                                        <table class="table table-bordered"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>User Name</th>
                                                    <th>User ID</th>
                                                    <th>Transaction ID</th>
                                                    <th>transacation Photo</th>
                                                    <th>Coins</th>
                                                    <th>Price</th>
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
                                                    <td><?= $Data->name ?></td>
                                                    <td><?= $Data->user_id ?></td>
                                                    <td><?= $Data->utr ?></td>
                                                    <td><img src="<?= base_url('data/post/' . $Data->photo) ?>"
                                                            alt="Transaction Photo" width="200" height="200"></td>
                                                    <td><?= $Data->coin ?></td>
                                                    <td><?= $Data->price ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <!--   <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending</option> -->
                                                            <option value="1"
                                                                <?= (($Data->status == 1) ? 'selected' : '') ?>>Approved
                                                            </option>
                                                            <!--  <option value="2" <?= (($Data->status == 2) ? 'selected' : '') ?>>Reject</option> -->
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->added_date)) ?></td>
                                                </tr>
                                                <?php }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="rejected" class="tab-pane fade">
                                        <br>
                                        <table class="table table-bordered"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>User Name</th>
                                                    <th>User ID</th>
                                                    <th>Transaction ID</th>
                                                    <th>transacation Photo</th>
                                                    <th>Coins</th>
                                                    <th>Price</th>
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
                                                    <td><?= $Data->name ?></td>
                                                    <td><?= $Data->user_id ?></td>
                                                    <td><?= $Data->utr ?></td>
                                                    <td><img src="<?= base_url('data/post/' . $Data->photo) ?>"
                                                            alt="Transaction Photo" width="200" height="200"></td>
                                                    <td><?= $Data->coin ?></td>
                                                    <td><?= $Data->price ?></td>
                                                    <td>
                                                        <select class="form-control"
                                                            onchange="ChangeWithDrawalStatus(<?= $Data->id ?>,this.value)">
                                                            <!--                                         <option value="0" <?= (($Data->status == 0) ? 'selected' : '') ?>>Pending</option>
                                                        <option value="1" <?= (($Data->status == 1) ? 'selected' : '') ?>>Approve</option>
                -->
                                                            <option value="2"
                                                                <?= (($Data->status == 2) ? 'selected' : '') ?>>Rejected
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td><?= date("d-m-Y h:i:s A", strtotime($Data->added_date)) ?></td>
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
            </div>
        </div>
    </div>
    <!-- end col -->
</div>

<script>
$(document).ready(function() {
    $('.table').dataTable({
        dom: 'Bfrtip',
        "buttons": [
            'excel'
        ]
    });
})
</script>

<script>
$(document).ready(function() {
    $('.table').dataTable();
})

function ChangeWithDrawalStatus(id, status) {
    jQuery.ajax({
        url: "<?= base_url('backend/Purchase/ChangeStatus') ?>",
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
</script>