<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Message</th>
                            <th>Image</th>
                            <th>Url</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                <th>Action</th>
                            <?php } ?>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllNotification as $key => $Noti) {
                            $i++;
                            ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $Noti->msg ?></td>
                                <td>
                                    <?php if (!empty($Noti->image)) { ?>
                                        <img src="<?= base_url('./uploads/images/' . $Noti->image) ?>" alt="Notification Image"
                                            style="max-width: 100px; max-height: 100px;">
                                    <?php } else { ?>
                                        No Image
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($Noti->url, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                                        class="btn btn-link">
                                        <?= $Noti->url ?>
                                    </a>
                                </td>
                                <td><?= date("d-m-Y h:i:s A", strtotime($Noti->added_date)) ?></td>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>

                                    <td>| <a href="<?= base_url('backend/Notification/delete/' . $Noti->id) ?>"
                                            onclick="return confirm('Are You Sure Want To Remove This Image?')"
                                            class="btn btn-danger" data-toggle="tooltip" data-placement="top" title=""
                                            data-original-title="Delete"><span class="fa fa-trash"></span></a></td>
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