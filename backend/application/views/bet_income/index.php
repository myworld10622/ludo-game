<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">
                    <table class="table table-bordered nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Bonus in %</th>
                                <th>Added Date</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($Bet_income_master as $master) { ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= $master->bonus ?></td>
                                    <td><?= $master->added_date ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                            <a href="<?= base_url('backend/Bet_income_master/edit/' . $master->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                                <span class="fa fa-edit"></span></a>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>