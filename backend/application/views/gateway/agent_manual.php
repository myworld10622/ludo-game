<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive table-bordered nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Gatway Name</th>
                                <th>Number</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($AgentManualGatway as $key => $agentmanualgatway) {
                                $i++;
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $agentmanualgatway->name ?></td>
                                    <td><?= $agentmanualgatway->number ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                            <a href="<?= base_url('backend/Gateway/editAgentManual/' . $agentmanualgatway->id) ?>"
                                                class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                                <span class="fa fa-edit" title="Edit"></span>
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
    </div>
    <!-- end col -->
</div>