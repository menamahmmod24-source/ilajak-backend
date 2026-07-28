<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;">
    <div style="max-width: 500px; background: #ffffff; padding: 30px; border-radius: 8px; margin: 0 auto; text-align: center;">
        <h2 style="color: #0066cc;">3ilajak Health</h2>
        <p style="font-size: 16px; color: #333;">Your 6-digit password reset code is:</p>
        <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0066cc; margin: 20px 0;">
            {{ $otp }}
        </div>
        <p style="font-size: 14px; color: #777;">If you did not request this code, please ignore this email.</p>
    </div>
</body>
</html>
