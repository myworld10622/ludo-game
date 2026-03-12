<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Boot Value</th>
                            <th>Blind 1</th>
                            <th>Blind 2</th>
                            <th>City</th>
                            <th>Image</th>
                            <th>Image Bg</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllTableMaster as $key => $PokerMaster) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $PokerMaster->boot_value ?></td>
                            <td><?= $PokerMaster->blind_1 ?></td>
                            <td><?= $PokerMaster->blind_2 ?></td>
                            <td><?= $PokerMaster->city ?></td>
                            <td><img src="<?= base_url('data/post/' . $PokerMaster->image) ?>" width="100px"
                                    height="100px"></td>
                            <td><img src="<?= base_url('data/post/' . $PokerMaster->image_bg) ?>" width="100px"
                                    height="100px"></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($PokerMaster->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/pokerMaster/edit/' . $PokerMaster->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span>
                                </a>
                                |
                                <a href="<?= base_url('backend/pokerMaster/delete/' . $PokerMaster->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete">
                                    <span class="fa fa-trash"></span> <!-- Changed icon to fa-trash -->
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