 <!-- Begin page -->
 <?php
$role = $this->session->userdata("role");
?>
 <div id="wrapper">
     <!-- Top Bar Start -->
     <div class="topbar">
         <!-- LOGO -->
         <div class="topbar-left"><a href="#" class="logo"><span style="color: white">
                     <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
                     <?= ($this->session->id!=1)?$this->session->name:PROJECT_NAME ?></span><i>
                     <?php if($this->session->id!=1){ ?>
                     <img src="https://demosales.androappstech.in/<?= LOGO.$this->session->logo ?>" alt="" height="50">
                     <?php }else{ ?>
                     <img src="<?= base_url(LOGO.$logo->logo) ?>" alt="" height="50">
                     <?php } ?>

                 </i></a>
             <?php }else{ ?>
             <?= PROJECT_NAME ?></span><i><img src="<?= base_url(LOGO.$logo->logo) ?>" alt="" height="50"></i></a>
             <?php } ?>
         </div>
         <nav class="navbar-custom">
             <ul class="navbar-right list-inline float-right mb-0">
                 <li class="dropdown notification-list list-inline-item d-none d-md-inline-block"><a
                         class="nav-link waves-effect" href="#" id="btn-fullscreen"><b><?php if($role == 1) {
                            echo "SUB ADMIN";
                         } else if($role == 2) {
                            echo "AGENT";
                         } ?></b></a></li>
                 <!-- full screen -->
                 <!-- Full Screen -->
                 <li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
                     <a class="nav-link waves-effect" href="#" id="toggle-fullscreen">
                         <i class="mdi mdi-fullscreen noti-icon"></i>
                     </a>
                 </li>

                 <script>
                 document.getElementById("toggle-fullscreen").addEventListener("click", function(event) {
                     event.preventDefault();
                     document.fullscreenElement ? document.exitFullscreen() : document.documentElement
                         .requestFullscreen();
                 });
                 </script>

                 <li class="dropdown notification-list list-inline-item">
                     <div class="dropdown notification-list nav-pro-img">
                         <a class="dropdown-toggle nav-link arrow-none waves-effect nav-user" data-toggle="dropdown"
                             href="#" role="button" aria-haspopup="false" aria-expanded="false">
                             <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
                             <?php if($this->session->id!=1){ ?>
                             <img src="https://demosales.androappstech.in/<?=LOGO.$this->session->logo ?>" alt="user"
                                 class="rounded-circle">
                             <?php }else{ ?>
                             <img src="<?= base_url(LOGO.$logo->logo) ?>" alt="user" class="rounded-circle">
                             <?php } ?>
                             <?php }else { ?>
                          <img src="<?= base_url(LOGO.$logo->logo) ?>" alt="user" class="rounded-circle"> <?php } ?>
                         </a>
                         <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                             <a class="dropdown-item" href="<?= base_url('backend/Profile/add'); ?>"><i
                                     class="mdi mdi-account-circle m-r-5"></i> Profile</a>
                             <div class="dropdown-divider"></div>
                             <a class="dropdown-item text-danger" href="<?= base_url('backend/auth/logout')?>"><i
                                     class="mdi mdi-power text-danger"></i> Logout</a>
                         </div>
                     </div>
                 </li>
             </ul>
             <ul class="list-inline menu-left mb-0">
                 <li class="float-left"><button class="button-menu-mobile open-left waves-effect"><i
                             class="mdi mdi-menu"></i></button></li>

             </ul>
         </nav>
     </div>