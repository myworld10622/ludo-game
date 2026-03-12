<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive">

                <table id="datatable" class="table table-bordered nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User Id</th>
                            <th>Total Coins Win</th>
                        </tr>
                    </thead>
                  <tbody>
                        <?php
                        $i = 0;
                        foreach ($LeaderBoard as $key => $Game) {
                            $i++;
                        ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $Game->name ?></td>
                                <td><?= $Game->winner_id ?></td>
                                <td><?= $Game->Total_Win ?></td>
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
<!-- <script>
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
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'throw';
    $(".table").DataTable({
        // stateSave: true,
        searchDelay: 1000,
        processing: true,
        serverSide: true,
        scrollX: true,
        serverMethod: 'post',
        ajax: {
            url: "<?= base_url('backend/Game/GetLeaderboard') ?>"
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'name'
            },
            {
                data: 'winner_id'
            },
            {
                data: 'Total_Win'
            },
        ],

        lengthMenu: [
            [10, 50, 100, 200, -1],
            [10, 50, 100, 200, "All"]
        ],
        pageLength: 10,
        dom: 'Bfrtip',
        "buttons": [
            'excel'
        ]

    });
});
</script> -->