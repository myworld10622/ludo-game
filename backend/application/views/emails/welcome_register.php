<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1d1d1d;">
    <h2>Welcome to <?= htmlspecialchars($project_name ?? PROJECT_NAME) ?></h2>
    <p>Your account has been created successfully. नीचे आपके login details हैं:</p>

    <table style="border-collapse: collapse; margin-top: 12px;">
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">Email / Mobile</td>
            <td style="padding: 6px 12px;">
                <?= htmlspecialchars($email ?? '') ?><?= (!empty($email) && !empty($mobile)) ? ' / ' : '' ?><?= htmlspecialchars($mobile ?? '') ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">User ID</td>
            <td style="padding: 6px 12px;"><?= htmlspecialchars($user_id ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">Username</td>
            <td style="padding: 6px 12px;"><?= htmlspecialchars($username ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">Password</td>
            <td style="padding: 6px 12px;"><?= htmlspecialchars($password ?? '') ?></td>
        </tr>
    </table>

    <p style="margin-top: 12px;">
        You can login with Email/Mobile, Username, or User ID.
    </p>

    <p>
        <a href="<?= htmlspecialchars($login_url ?? 'https://roxludo.com/login') ?>" style="color:#0d6efd;">Login on Web</a>
        &nbsp;|&nbsp;
        <a href="<?= htmlspecialchars($app_url ?? 'https://roxludo.com') ?>" style="color:#0d6efd;">Open App</a>
    </p>

    <p style="margin-top: 18px;">Thanks,<br><?= htmlspecialchars($project_name ?? PROJECT_NAME) ?> Team</p>
</div>
