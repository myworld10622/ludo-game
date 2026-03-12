<div class="row">

    <div class="col-12">
        <div class="card">
            <div style="display: flex; margin-left: auto;margin-right: 26px;margin-top: 15px;">
                <div class="row">
                    <div class="col-md-4">
                        <label>Select Winner</label>
                    </div>
                    <div class="col">
                        <select class="form-control" name="random" id="random">
                            <option value="1" <?= ($RandomFlag == 1) ? 'selected' : '' ?>>Random</option>
                            <option value="2" <?= ($RandomFlag == 2) ? 'selected' : '' ?>>Optimization</option>
                        </select>
                    </div>
                </div>
                <br>
            </div>
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Point Value</th>
                            <th>Boot Value</th>
                            <th>Added Date and Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllRummyTableMaster as $key => $RummyTableMaster) {
                            $i++;
                            ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $RummyTableMaster->point_value ?></td>
                            <td><?= $RummyTableMaster->boot_value ?></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($RummyTableMaster->added_date)) ?></td>
                            <td>
                                <a href="<?= base_url('backend/rummyTableMaster/edit/' . $RummyTableMaster->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span>
                                </a>
                                |
                                <a href="<?= base_url('backend/rummyTableMaster/delete/' . $RummyTableMaster->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete">
                                    <span class="fa fa-trash"></span>
                                </a>
                            </td>

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
        url: '<?= base_url('backend/RummyTableMaster/ChangeStatus') ?>',
        data: {
            type: type
        },
        beforeSend: function() {},
        success: function(response) {},
        error: function(e) {}
    })
});
</script>