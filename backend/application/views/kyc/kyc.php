<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">
            <div class="card-body table-responsive">
            <ul class="nav nav-tabs">
                  
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#pending" role="tab" aria-selected="true">
                            <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                            <span class="d-none d-sm-block">Pending</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#approved" role="tab" aria-selected="false">
                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                            <span class="d-none d-sm-block">Approved</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#rejected" role="tab" aria-selected="false">
                            <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                            <span class="d-none d-sm-block">Rejected</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                <div class="tab-pane p-3 active" id="pending" role="tabpanel">
                <br>
                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Pan Number</th>
                            <th>AAdhar Number</th>
                            <th>Status</th>
                            <th>Added Date and Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllKyc as $key => $kyc) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $kyc->user_name ?></td>
                            <td><?= $kyc->user_id ?></td>
                            <td><?= $kyc->pan_no ?></td>
                            <td><?= $kyc->aadhar_no ?></td>
                            <td>
                                    <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $kyc->id ?>,this.value)">
                                            <option value="0" <?= (($kyc->status == 0) ? 'selected' : '') ?>>Pending
                                            </option>
                                            <option value="1" <?= (($kyc->status == 1) ? 'selected' : '') ?>>Approve
                                            </option>
                                            <option value="2" <?= (($kyc->status == 2) ? 'selected' : '') ?>>Reject
                                            </option>
                                        </select>
                                    </td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($kyc->added_date)) ?></td>
                            <td>
                                <a href="javascript:void(0)"
                                    class="btn btn-info" onclick="OpenImg('<?= base_url('data/post/' . strtolower($kyc->pan_img)); ?>','<?= base_url('data/post/' . strtolower($kyc->aadhar_img)); ?>')" data-toggle="tooltip" data-placement="top" title="View Document"><span
                                        class="fa fa-eye"></span></a>
                            </td>
                         
                        </tr>
                        <?php }
                        ?>


                    </tbody>
                </table>
                        </div>
                        <div class="tab-pane fade " id="approved">
                <br>
                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Pan Number</th>
                            <th>AAdhar Number</th>
                            <!-- <th>Status</th> -->
                            <th>Added Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllApproved as $key => $kyc) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $kyc->user_name ?></td>
                            <td><?= $kyc->user_id ?></td>
                            <td><?= $kyc->pan_no ?></td>
                            <td><?= $kyc->aadhar_no ?></td>
                            <!-- <td>
                                    <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $kyc->id ?>,this.value)">
                                            <option value="0" <?= (($kyc->status == 0) ? 'selected' : '') ?>>Pending
                                            </option>
                                            <option value="1" <?= (($kyc->status == 1) ? 'selected' : '') ?>>Approve
                                            </option>
                                            <option value="2" <?= (($kyc->status == 2) ? 'selected' : '') ?>>Reject
                                            </option>
                                        </select>
                                    </td> -->
                            <td><?= date("d-m-Y h:i A", strtotime($kyc->added_date)) ?></td>
                            <td>
                                <a href="javascript:void(0)"
                                    class="btn btn-info" onclick="OpenImg('<?= base_url('data/post/' . strtolower($kyc->pan_img)); ?>','<?= base_url('data/post/' . strtolower($kyc->aadhar_img)); ?>')" data-toggle="tooltip" data-placement="top" title="View Document"><span
                                        class="fa fa-eye"></span></a>
                            </td>
                         
                        </tr>
                        <?php }
                        ?>


                    </tbody>
                </table>
                        </div>
                        
                        <div id="rejected" class="tab-pan fade">
                <br>
                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Pan Number</th>
                            <th>AAdhar Number</th>
                            <th>Status</th>
                            <th>Added Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllRejected as $key => $kyc) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $kyc->user_name ?></td>
                            <td><?= $kyc->user_id ?></td>
                            <td><?= $kyc->pan_no ?></td>
                            <td><?= $kyc->aadhar_no ?></td>
                            <td>
                                    <select class="form-control"
                                            onchange="ChangeWithDrawalStatus(<?= $kyc->id ?>,this.value)">
                                            <option value="0" <?= (($kyc->status == 0) ? 'selected' : '') ?>>Pending
                                            </option>
                                            <option value="1" <?= (($kyc->status == 1) ? 'selected' : '') ?>>Approve
                                            </option>
                                            <option value="2" <?= (($kyc->status == 2) ? 'selected' : '') ?>>Reject
                                            </option>
                                        </select>
                                    </td>
                            <td><?= date("d-m-Y h:i A", strtotime($kyc->added_date)) ?></td>
                            <td>
                                <a href="javascript:void(0)"
                                    class="btn btn-info" onclick="OpenImg('<?= base_url('data/post/' . strtolower($kyc->pan_img)); ?>','<?= base_url('data/post/' . strtolower($kyc->aadhar_img)); ?>')" data-toggle="tooltip" data-placement="top" title="View Document"><span
                                        class="fa fa-eye"></span></a>
                            </td>
                         
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

  <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close back" data-dismiss="modal">&times;</button>
          <!-- <h4 class="modal-title">Reason</h4> -->
        </div>
        <div class="modal-body">
          <textarea placeholder="Reason..." cols="50" rows="3" id="reason"></textarea>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="record_id" >
          <button type="button" class="btn btn-primary back" data-dismiss="modal">Close</button>
          <button type="submit" id="submit" class="btn btn-success">Save</button>
        </div>
      </div>
      
    </div>
  </div>



  <!-- Modal -->
  <div class="modal fade" id="ImgModal" role="dialog">
    <div class="modal-dialog modal-xl">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <!-- <h4 class="modal-title">Reason</h4> -->
        </div>
        <div class="modal-body">
       
            <div class="row">
            <div class="col-md-12">
            <div class="row">
            <div class="col-md-6">
                <lable>Pan Card</lable><br>
        <img src="" id="pan_image" height="400px" width="100%">
                        </div>
                        <div class="col-md-6">
                <lable>Aadhar Card</lable>
        <img src="" id="aadhar_image" height="400px" width="100%">
                        </div>
                        </div>
                        </div>
                        </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="record_id" >
          <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>

<script>

function ChangeWithDrawalStatus(id, status) {
    if(status==2){
        $('#record_id').val(id);
        $('#myModal').modal('show')
        return false
    }
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



function OpenImg(pan_img, aadhar_img) {
    $('#pan_image').attr('src',pan_img);
    $('#aadhar_image').attr('src',aadhar_img);
        $('#ImgModal').modal('show')
}


$("#submit").on("click", function(event) {
		$.ajax({
			url: '<?= base_url('backend/Kyc/ReasonUpdate') ?>',
			method: 'post',
			data: {'reason':$('#reason').val(),'id':$('#record_id').val()},
			dataType: 'json',
			success: function(data) {
				if (data==true) {
                    toastr.success('Status Updated Successfully.');
                    setTimeout(function() {
                location.reload()
            }, 1000);
				}
			}
		});
	});
    $(".back").on("click", function(event) {
        location.reload()
    })
</script>