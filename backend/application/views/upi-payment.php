<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>Payment Page</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;

    }

    .header {
      background-color: #005aa7;
      color: white;
      text-align: center;
      padding: 15px;
    }

    .header img {
      height: 30px;
      vertical-align: middle;
    }

    .header h1 {
      display: inline;
      font-size: 20px;
      margin-left: 10px;
    }

    .container {
      text-align: center;
      padding: 20px;
    }

    .amount {
      font-size: 24px;
      font-weight: bold;
      color: #2d2d2d;
    }

    .amount span {
      color: #005aa7;
      font-size: 32px;
    }

    .methods {
      margin: 20px 0;
    }

    .method {
      display: flex;
      align-items: center;
      background: white;
      justify-content: space-between;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 8px;
      margin: 10px auto;
      width: 90%;
      max-width: 400px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .method img {
      width: 40px;
      margin-right: 10px;
    }

    .method:hover {
      background: #f0f8ff;
    }

    .utr-section {
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .utr-section input {
      width: 90%;
      padding: 16px 10px;
      border: 1px solid #f3f3f3;
      border-radius: 40px;
      margin: 10px auto;
      display: block;
      background-color: #f3f3f3;
    }

    .submit-btn {
      background-color: #005aa7;
      color: white;
      padding: 16px 20px;
      border: none;
      border-radius: 40px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s;
      width: 92%;
    }

    .submit-btn:hover {
      background-color: #003d7e;
    }

    .notice {
      margin-top: 20px;
      text-align: left;
      width: 90%;
      display: flex;
      gap: 7px;
      margin: 10px auto;
      background: #fefefe;
      padding: 0 10px;
      border: 1px solid #ddd;
      border-radius: 8px;
    }

    .notice strong {
      font-size: 16px;
    }

    .notice p {
      font-size: 14px;
      color: #555;
    }

    input::placeholder {
      color: #005aa7;
      font-size: 18px;
      font-weight: 600;
      text-align: center;
    }

    /* Medium Devices, Desktops */
    @media only screen and (min-width : 992px) {
      .dek-view {
        display: none;
      }
    }

    /* Large Devices, Wide Screens */
    @media only screen and (min-width : 1200px) {
      .dek-view {
        display: none;
      }
    }

    @media (min-width: 1900px) and (max-width: 2560px) {
      .dek-view {
        display: none;
      }

    }
  </style>
</head>

<body>
  <div class="header">
    <img src="<?= base_url('assets/images/Upi/upi.png') ?>" alt="UPI">
    <h1>Payment</h1>
  </div>
  <div class="container">
    <p>The amount you need to Payable</p>
    <div class="amount"><span>₹500</span></div>
    <p><b>Use Mobile Scan code to pay </b></p>
    <div class="dek-view">
      <p style="text-align: left;"><b>Choose a payment method to pay</b></p>
      <div class="methods">
        <div class="method">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <img src="<?= base_url('assets/images/Upi/paytm.png') ?>" alt="Paytm">
            <span>Paytm</span>
          </div>
          <div>
            <i class="fa fa-angle-right" aria-hidden="true"></i>
          </div>
        </div>
        <div class="method">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <img src="<?= base_url('assets/images/Upi/phonepay.png') ?>" alt="PhonePe">
            <span>PhonePe</span>
          </div>
          <div>
            <i class="fa fa-angle-right" aria-hidden="true"></i>
          </div>
        </div>
        <div class="method">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <img src="<?= base_url('assets/images/Upi/gpay.png') ?>" alt="GPay">
            <span>G Pay</span>
          </div>
          <div>
            <i class="fa fa-angle-right" aria-hidden="true"></i>
          </div>
        </div>
        <div class="method">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <img src="<?= base_url('assets/images/Upi/upi.png') ?>" alt="UPI">
            <span>UPI</span>
          </div>
          <div>
            <i class="fa fa-angle-right" aria-hidden="true"></i>
          </div>
        </div>

      </div>
    </div>
    <div class="utr-section">
      <label for="utr">UTR</label>
      <input type="text" id="utr" placeholder="Input 12-digit here">
      <button class="submit-btn">Submit Ref Number</button>
    </div>
    <p><strong>Notice</strong></p>
    <div class="notice">
      <p>1. </p>
      <p>Please select the payment method you need and make sure your phone has the corresponding wallet software
        installed.</p>
    </div>
  </div>
</body>

</html>