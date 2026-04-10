<!DOCTYPE html>
<html lang="<?= current_language() ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0">
    <title><?= PROJECT_NAME ?> | <?= $title ?></title>
    <meta name="description"
        content="<?= t('meta_description') ?>">
    <meta property="og:title" content="<?= t('play_win_title') ?>" />
    <meta property="og:description"
        content="<?= t('meta_description') ?>" />
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

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 20px;
    }

    .preference-select {
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        padding: 8px 12px;
        font-size: 13px;
        min-width: 110px;
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

        .header {
            flex-wrap: wrap;
            gap: 12px;
        }

        .header-actions {
            width: 100%;
            justify-content: flex-end;
            margin-left: 0;
            padding-right: 54px;
            padding-bottom: 10px;
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
                <li><a href="<?= base_url() ?>"><?= t('nav_home') ?></a></li>
                <li><a href="<?= base_url('download') ?>" class="download_copy"><?= t('nav_download') ?></a></li>
                <li><a href="<?= base_url('faq') ?>"><?= t('nav_faq') ?></a></li>
                <li><a href="<?= base_url('about-us') ?>"><?= t('nav_about') ?></a></li>
                <li><a href="<?= base_url('privacy-policy') ?>"><?= t('nav_privacy') ?></a></li>
                <li><a href="<?= base_url('terms-conditions') ?>"><?= t('nav_terms') ?></a></li>
                <li><a href="<?= base_url('refund-policy') ?>"><?= t('nav_refund') ?></a></li>
                <li><a href="<?= base_url('security') ?>"><?= t('nav_security') ?></a></li>
                <li><a href="<?= base_url('contact-us') ?>"><?= t('nav_contact') ?></a></li>
            </ul>
        </div>
        <div class="header-actions">
            <select class="preference-select" onchange="if (this.value) { window.location.href = this.value; }" aria-label="<?= t('toggle_language') ?>">
                <?php foreach (supported_languages() as $code => $label) { ?>
                    <option value="<?= language_switch_url($code) ?>" <?= current_language() === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php } ?>
            </select>
            <select class="preference-select" onchange="if (this.value) { window.location.href = this.value; }" aria-label="<?= t('toggle_currency') ?>">
                <?php foreach (supported_currencies() as $code => $details) { ?>
                    <option value="<?= currency_switch_url($code) ?>" <?= current_currency() === $code ? 'selected' : '' ?>><?= $details['label'] ?></option>
                <?php } ?>
            </select>
        </div>
    </header>


</body>
