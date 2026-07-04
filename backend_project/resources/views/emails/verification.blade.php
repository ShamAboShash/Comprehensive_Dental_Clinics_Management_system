<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DT.Teeth - Account Activation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1A1C4E; background-color: #E9ECFB;">
    <div style="max-width: 600px; margin: 0 auto; padding: 30px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 4px solid #3E3BB6;">
            <h1 style="color: #1A1C4E; margin: 0; font-size: 32px; font-weight: bold;">
                DT.Teeth
            </h1>
            <p style="color: #3E3BB6; margin: 8px 0 0 0; font-size: 16px; font-weight: 500;">
                Dental Clinic Center
            </p>
        </div>

        <h2 style="color: #1A1C4E; text-align: center; margin: 25px 0 15px 0;">
            Welcome to DT.Teeth!
        </h2>

        <p style="color: #1A1C4E;">Dear <strong>{{ $user->name }}</strong>,</p>

        <p style="color: #1A1C4E;">Your account has been created successfully.</p>

        <h3 style="color: #1A1C4E; text-align: center; margin: 30px 0 12px 0;">
            Your Verification Code is:
        </h3>

        <!-- Code Box -->
        <div style="background: linear-gradient(135deg, #F1DAFE, #E9ECFB);
                    padding: 25px;
                    text-align: center;
                    border-radius: 16px;
                    border: 2px solid #3E3BB6;
                    margin: 15px 0;">
            <h1 style="font-size: 40px;
                       letter-spacing: 15px;
                       color: #1A1C4E;
                       margin: 0;
                       font-weight: bold;">
                {{ $code }}
            </h1>
        </div>

        <p style="text-align: center; color: #1A1C4E; font-weight: 500;">
            This code will expire in <strong>5 minutes</strong>.
        </p>

        <p style="color: #1A1C4E; text-align: center;">
            If you didn't request this, please ignore this email.
        </p>

        <hr style="border: none; border-top: 1px solid #BED8FA; margin: 35px 0;">

        <p style="font-size: 14px; color: #3E3BB6; text-align: center;">
            Best regards,<br>
            <strong>DT.Teeth Team</strong>
        </p>
    </div>
</body>
</html>
