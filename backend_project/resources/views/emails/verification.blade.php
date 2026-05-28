{{-- <!DOCTYPE html>
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
</html> --}}


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DT.Teeth - Account Activation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9fafb;">
    <div style="max-width: 600px; margin: 0 auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

        <!-- Header with Logo/Brand -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #8b5cf6;">
            <h1 style="color: #25093c; margin: 0; font-size: 28px; font-weight: bold;">
                DT.Teeth
            </h1>
            <p style="color: #d6b0f7; margin: 5px 0 0 0; font-size: 16px;">Dental Clinic Center</p>
        </div>

        <h2 style="color: #d6b0f7; text-align: center;">Welcome to DT.Teeth!</h2>

        <p>Dear <strong>{{ $user->name }}</strong>,</p>

        <p>Your account has been created.</p>

        <h3 style="color: #25093c; text-align: center; margin: 25px 0 10px 0;">
            Your Verification Code is:
        </h3>

        <div style="background: linear-gradient(135deg, #f3e8ff, #e0e7ff);
                    padding: 20px;
                    text-align: center;
                    border-radius: 12px;
                    margin: 15px 0;">
            <h1 style="font-size: 48px;
                       letter-spacing: 12px;
                       color: #7cb6f4;
                       margin: 0;
                       font-weight: bold;">
                {{ $code }}
            </h1>
        </div>

        <p style="text-align: center; color: #444;">
            This code will expire in <strong>2 minutes</strong>.
        </p>

        <p>If you didn't request this, please ignore this email.</p>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

        <p style="font-size: 14px; color: #666; text-align: center;">
            Best regards,<br>
            <strong>DT.Teeth Team</strong>
        </p>
    </div>
</body>
</html>
