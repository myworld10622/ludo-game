 <div class="container-main">
     <div class="banner banner_small">
         <div class="text">
             <h1><?= t('nav_contact') ?></h1>
             <p><?= t('contact_page_subtitle') ?></p>
         </div>
     </div>
     <div class="container" style="width: auto">
         <div class="lift_box">
             <div class="lift_down">
                 <div class="icon">
                 <img src="<?= base_url(LOGO.$Setting->logo) ?>" alt="">
                 </div>
                 <div class="text">
                     <p><?= PROJECT_NAME ?></p>
                     <p><?= t('common_cta') ?></p>
                 </div>
                 <div class="down">
                     <a href="<?= base_url('game.apk') ?>" download><?= t('download_apk') ?></a>
                 </div>
             </div>
         </div>
         <!-- <div class="right_box">
             <div class="box">
                 <h3>Contact Us</h3><br>
                 <div class="col-md-12">

                     <div class="left text-center">
                         <div class=""><b>Email</b> : care@54decks.com</div>

                     </div>
                     <div class="right text-center">
                         <b>Address </b>: No.87, First Floor, 4th Cross Street
                         Thirumala Nagar 1st Main Road,
                         Perungudi, Chennai – 600096
                     </div>
                     <div class=" text-center">
                         <div class="">
                             <b>Contact Number </b> : +91 8925290666
                         </div>
                     </div>

                 </div>


             </div>
         </div> -->
         <div class="right_box">
             <div class="box">
                 <h3><?= t('nav_contact') ?></h3>
                 <?= localize_content($Setting->contact_us) ?>
             </div>
         </div>
         <div class="clear"></div>
     </div>
 </div>
