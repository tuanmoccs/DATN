<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #4F46E5; }
        .otp-code { background: #4F46E5; color: white; font-size: 32px; font-weight: bold; padding: 20px; text-align: center; border-radius: 8px; margin: 30px 0; letter-spacing: 8px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e5e5; text-align: center; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #4F46E5; margin: 0;">Xác Nhận Đăng Ký</h1>
        </div>
        
        <div style="padding: 20px 0;">
            <p>Xin chào <strong>{{ $name }}</strong>,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản giáo viên. Để hoàn tất quá trình đăng ký, vui lòng sử dụng mã OTP dưới đây:</p>
            
            <div class="otp-code">{{ $otp }}</div>
            
            <p><strong>Lưu ý:</strong></p>
            <ul style="color: #666;">
                <li>Mã OTP này có hiệu lực trong 10 phút</li>
                <li>Không chia sẻ mã này với bất kỳ ai</li>
                <li>Nếu bạn không yêu cầu đăng ký, vui lòng bỏ qua email này</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
            <p>&copy; {{ date('Y') }} Education System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>