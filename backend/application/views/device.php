<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Detection</title>
</head>

<body>
    <h1 id="device">Detecting device...</h1>

    <script>
        function detectDevice() {
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;

            if (/windows phone/i.test(userAgent)) {
                return "Windows Phone";
            }
            if (/android/i.test(userAgent)) {
                return "Android Phone";
            }
            if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                return "iPhone or iPad";
            }
            if (/Macintosh|MacIntel|MacPPC|Mac68K/.test(userAgent)) {
                return "MacBook";
            }
            if (/Win32|Win64|Windows|WinCE/.test(userAgent)) {
                return "Windows Laptop or Desktop";
            }
            if (/Linux/.test(userAgent)) {
                return "Linux Device";
            }

            return "Unknown Device";
        }

        // Run detection and update the page
        const device = detectDevice();
        // console.log(device);
        document.querySelector('#device').textContent = `You are using: ${device}`;
    </script>
</body>

</html>