<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0,minimal-ui">
   <title><?= $title . ' | ' . PROJECT_NAME ?></title>
   <meta content="Admin Dashboard" name="description">
   <meta content="Themesbrand" name="author">
   <link rel="shortcut icon" href="<?= base_url(LOGO.$logo->logo)?>">
   <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
   <link href="<?= base_url('assets/css/metismenu.min.css') ?>" rel="stylesheet" type="text/css">
   <link href="<?= base_url('assets/css/icons.css') ?>" rel="stylesheet" type="text/css">
   <?php if($_ENV['ENVIRONMENT']=='fame'){ ?>
   <link href="<?= base_url('assets/css/stylenew.css') ?>" rel="stylesheet" type="text/css">
   <?php }else{ ?>
   <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet" type="text/css">
   <?php } ?>
   <link href="<?= base_url('assets/css/app.min.css') ?>" rel="stylesheet" type="text/css">
   <!-- DataTables -->
   <link href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap4.min.css')?>" rel="stylesheet" type="text/css">
   <link href="<?= base_url('assets/plugins/datatables/buttons.bootstrap4.min.css')?>" rel="stylesheet" type="text/css">
   <!-- Responsive datatable examples -->
   <link href="<?= base_url('assets/css/toastr.css') ?>" rel="stylesheet" type="text/css">
   <link href="<?= base_url('assets/plugins/datatables/responsive.bootstrap4.min.css')?>" rel="stylesheet" type="text/css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.1/css/bootstrap-datepicker.min.css" >
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
   <script src="<?= base_url('assets/js/jquery.min.js')?>"></script>
   <script src="<?= base_url('assets/js/toastr.min.js') ?>"></script>
   <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

</head>
<script>
   const BASE_URL = '<?= base_url()?>';
</script>
<body>