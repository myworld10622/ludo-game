<h2>Enter Mobile For Deleting Acoount</h2>
<div class="col-md-6"> 
<?php if (null !== $this->session->flashdata('msg')) {
   $message = $this->session->flashdata('msg');
  ?>
<div class="alert alert-<?= $message['class']?>">
 <?= $message['message']?>
</div>
<?php } ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
<form action="<?= base_url('Home/deleteAccount')?>" method="post" >
<div class="row"> 
<div class="col-md-3">
  <div class="form-group">
    <label for="exampleInputEmail1">Mobile No.</label>
    <input type="text" class="form-control" name="mobile" required  placeholder="Enter Mobile No.">
  </div>
  <button type="submit" class="btn btn-primary">Submit</button>
</div>
</div>
</form>
</div>