<?php
$role = $this->session->userdata('role');
?>
<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">

                    <table class="table table-bordered nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <?php if ($role == SUPERADMIN) { ?>
                                    <th>Distributer Name</th>
                                <?php } ?>
                                <!-- <th>Employee ID</th> -->
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Wallet</th>
                                <th>Password</th>
                                <th>Added Date and Time</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($AllAgent as $agent) { ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= $agent->first_name ?></td>
                                    <td><?= $agent->last_name ?></td>
                                    <?php if ($role == SUPERADMIN) { ?>
                                        <td><?= $agent->distributor_fname . ' ' . $agent->distributor_lname ?></td>
                                    <?php } ?>
                                    <!-- <td><?= $agent->id ?></td> -->
                                    <td><?= $agent->email_id ?></td>
                                    <td><?= $agent->mobile ?></td>
                                    <td><?= $agent->wallet ?></td>
                                    <td><?= $agent->password ?></td>
                                    <td><?= $agent->created_date ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>

                                            <?php if ($this->session->role != DISTRIBUTOR) { ?>
                                                |<a href="<?= base_url('backend/Agent/users/' . $agent->id) ?>" class="btn btn-info"
                                                    data-toggle="tooltip" data-placement="top" title="View Users"><span
                                                        class="fa fa-eye"></span></a>
                                            <?php } ?>
                                            |<a href="<?= base_url('backend/Agent/view/' . $agent->id) ?>" class="btn btn-info"
                                                data-toggle="tooltip" data-placement="top" title="View Logs"><span
                                                    class="fa fa-eye"></span></a>
                                            |<a href="<?= base_url('backend/Agent/payment_method/' . $agent->id) ?>" class="btn btn-info"
                                                data-toggle="tooltip" data-placement="top" title="View Payment Methods"><span
                                                    class="fa fa-list"></span></a>
                                            <?php if ($role != SUPERADMIN) { ?>
                                                |<a href="<?= base_url('backend/Agent/edit_wallet/' . $agent->id) ?>"
                                                    class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                                    title="Add Wallet"><span class="fa fa-credit-card"></span></a>

                                                |<a href="<?= base_url('backend/Agent/deduct_wallet/' . $agent->id) ?>"
                                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top"
                                                    title="Deduct Wallet"><span class="fa fa-credit-card"></span></a>

                                                <a href="<?= base_url('backend/Agent/edit_Agent/' . $agent->id) ?>"
                                                    class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                                    title="Edit"><span class="fa fa-edit"></span></a>

                                                <!-- |<a href="javascript:void(0);" class="btn btn-danger" data-toggle="tooltip"
                                                    data-placement="top" title="Delete"
                                                    onclick="showBootstrapModal('<?= $agent->id ?>', '<?= $agent->first_name ?>')">
                                                    <span class="fa fa-trash"></span>
                                                </a> -->
                                            <?php } ?>
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
<!-- Bootstrap Modal for Confirmation -->
<!-- <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p id="deleteModalMessage">
                    If you delete this agent, all users associated with <strong class="text-warning"
                        style="font-weight: bold;">[Agent Name]</strong> will also be deleted. Are you sure you want to
                    proceed?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Yes, Delete</button>
            </div>
        </div>
    </div>
</div> -->

<!-- <script>
    let deleteAgentId = null; // To store the agent ID temporarily
    function showBootstrapModal(agentId, agentName) {
        // Set the agent ID for later use
        deleteAgentId = agentId;
        // Update the modal message with bold and highlighted agent name
        const message = `If you delete this agent, all users associated with <strong class="text-danger" style="font-weight: bold;">${agentName}</strong> will also be deleted. Are you sure you want to proceed?`;
        document.getElementById('deleteModalMessage').innerHTML = message;

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        modal.show();
    }
    // Handle the confirmation button click
    document.getElementById('confirmDeleteButton').addEventListener('click', function () {
        if (deleteAgentId) {
            // Redirect to the delete URL
            window.location.href = "<?= base_url('backend/Agent/delete/') ?>" + deleteAgentId;
        }
    });
</script> -->