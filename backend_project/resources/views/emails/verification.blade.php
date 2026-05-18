<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Activation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Welcome to {{ config('app.name') }}!</h2>

        <p>Dear <strong>{{ $user->name }}</strong>,</p>

        <p>Your account has been created by the administrator.</p>

        <h3 style="color: #2563eb;">Your Verification Code is:</h3>

        <h1 style="font-size: 42px; letter-spacing: 8px; color: #1e40af; background: #f1f5f9; padding: 15px; text-align: center; border-radius: 8px;">
            {{ $code }}
        </h1>

        <p>This code will expire in <strong>15 minutes</strong>.</p>

        <p>If you didn't request this, please ignore this email.</p>

        <hr>
        <p style="font-size: 12px; color: #666;">
            Best regards,<br>
            {{ config('app.name') }} Team
        </p>
    </div>
</body>
</html>
