<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0">
    <title><?= PROJECT_NAME ?> | <?= $title ?></title>
    <meta name="description"
        content="Download and Play <?= PROJECT_NAME ?>, classic Indian Cards Games online anytime, anywhere. Free daily bonus chips, win real money cash in rupees. Winners Take All!">
    <meta property="og:title" content="Play <?= PROJECT_NAME ?>, Win Unlimited Rupees" />
    <meta property="og:description"
        content="Download and Play <?= PROJECT_NAME ?>, classic Indian Cards Games online anytime, anywhere. Free daily bonus chips, win real money cash in rupees. Winners Take All!" />
    <link href="<?= base_url(LOGO.$Setting->logo) ?>" rel="shortcut icon">
    <link href="https://fonts.googleapis.com/css?family=Anton|Titan+One" async rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/website/css/style.css') ?>" async>
    <script src="<?= base_url('assets/website/js/jquery-3.4.1.min.js') ?>" async type="text/javascript">
    </script>
    <style>
        <?php if(isset($banner)){ ?>
    .banner-bg {
        background-image: url(<?= BANNER_URL.$banner->banner ?>);
        background-repeat: no-repeat;
        background-size: cover;
    }
    <?php } ?>

    .menu_icon {
        font-size: 30px;
        cursor: pointer;
        display: block;
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
        /* Ensure it's above other elements */
    }

    .navigation ul li {
        padding: 10px;
    }

    /* Show menu icon only on mobile */
    @media (max-width: 768px) {
        .menu_icon {
            display: block;
        }

        .navigation ul {
            display: none;
            /* Ensure it's hidden initially */
        }

        /* Hide menu initially */
        .navigation ul {
            display: none;
            list-style: none;
            padding: 0;
            margin: 0;
            background: #333;
            position: absolute;
            top: 60px;
            left: 0;
            width: 100%;
            text-align: center;
            opacity: 0;
            /* Initially hidden */
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        /* Show menu when active */
        .navigation ul.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Style menu items */
        .navigation ul li {
            padding: 10px;
            border-bottom: 1px solid #555;
        }

        .navigation ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
        }
    }
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const menuIcon = document.querySelector(".menu_icon");
        const menu = document.getElementById("menu");

        if (menuIcon && menu) {
            menuIcon.addEventListener("click", function() {
                menu.classList.toggle("active");
            });
        } else {
            console.error("Menu icon or navigation menu not found!");
        }
    });
    </script>



</head>

<body>
           <header class="header">
        <a class="logo"
            style="background: url(<?= LOGO.$Setting->logo ?>) left center no-repeat; background-size: 120px;"
            href="<?= base_url() ?>">
        </a>

        <div class="navigation">
            <div class="menu_icon"></div> <!-- Hamburger Menu -->
            <ul id="menu">
                <li><a href="<?= base_url() ?>">Home</a></li>
                <li><a href="<?= base_url('download') ?>" class="download_copy">Download</a></li>
                <li><a href="<?= base_url('faq') ?>">FAQ</a></li>
                <li><a href="<?= base_url('about-us') ?>">About us</a></li>
                <li><a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a></li>
                <li><a href="<?= base_url('terms-conditions') ?>">Terms & Conditions</a></li>
                <li><a href="<?= base_url('refund-policy') ?>">Refund Policy</a></li>
                <li><a href="<?= base_url('security') ?>">Security</a></li>
                <li><a href="<?= base_url('contact-us') ?>">Contact us</a></li>
            </ul>
        </div>
    </header>


</body>