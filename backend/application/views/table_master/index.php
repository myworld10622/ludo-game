<div class="row">

    <div class="col-12">
        <div class="card">
            <div style="display: flex; margin-left: auto;margin-right: 26px;margin-top: 15px;">
            </div>
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Boot Value</th>
                            <th>Chaal Limit</th>
                            <th>Pot Limit</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllTableMaster as $key => $TableMaster) {
                            $i++;
                            ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $TableMaster->boot_value ?></td>
                            <td><?= $TableMaster->chaal_limit ?></td>
                            <td><?= $TableMaster->pot_limit ?></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($TableMaster->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/tableMaster/edit/' . $TableMaster->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span>
                                </a>
                                |
                                <a href="<?= base_url('backend/tableMaster/delete/' . $TableMaster->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete">
                                    <span class="fa fa-trash"></span> <!-- Updated icon -->
                                </a>
                            </td>

                            <?php } ?>
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
        url: '<?= base_url('backend/TableMaster/ChangeStatus') ?>',
        data: {
            type: type
        },
        beforeSend: function() {},
        success: function(response) {},
        error: function(e) {}
    })
});
</script>