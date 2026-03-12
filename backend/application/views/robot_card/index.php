<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Card1</th>
                            <th>Card2</th>
                            <th>Card3</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllRobotCards as $key => $Card) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><img src="<?= base_url('data/cards/' . strtolower($Card->card1) . '.png'); ?>"></td>
                            <td><img src="<?= base_url('data/cards/' . strtolower($Card->card2) . '.png'); ?>"></td>
                            <td><img src="<?= base_url('data/cards/' . strtolower($Card->card3) . '.png'); ?>"></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($Card->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/RobotCards/edit/' . $Card->id) ?>" class="btn btn-info"
                                    data-toggle="tooltip" data-placement="top" title="Edit"><span
                                        class="fa fa-edit"></span></a>
                                | <a href="<?= base_url('backend/RobotCards/delete/' . $Card->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top"
                                    title="Delete"><span class="fa fa-trash"></span></a>
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