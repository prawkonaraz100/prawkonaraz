<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Nowa wiadomość kontaktowa</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#12264a;font-family:Arial,sans-serif;">
    <div style="max-width:680px;margin:0 auto;padding:32px 16px;">
        <div style="border:1px solid #dce4ef;border-radius:18px;background:#ffffff;padding:28px;">
            <p style="margin:0 0 8px;color:#1268cb;font-size:13px;font-weight:700;text-transform:uppercase;">Formularz PrawkoNaRaz</p>
            <h1 style="margin:0 0 24px;font-size:24px;line-height:1.25;">Nowa wiadomość ze strony</h1>

            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:15px;line-height:1.55;">
                <tr>
                    <td style="width:130px;padding:8px 12px 8px 0;color:#60708a;vertical-align:top;">Imię</td>
                    <td style="padding:8px 0;font-weight:700;">{{ $messageData['name'] }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px 8px 0;color:#60708a;vertical-align:top;">E-mail</td>
                    <td style="padding:8px 0;"><a href="mailto:{{ $messageData['email'] }}" style="color:#1268cb;">{{ $messageData['email'] }}</a></td>
                </tr>
                <tr>
                    <td style="padding:8px 12px 8px 0;color:#60708a;vertical-align:top;">Temat</td>
                    <td style="padding:8px 0;">{{ $messageData['topic'] }}</td>
                </tr>
            </table>

            <div style="margin-top:22px;border-top:1px solid #e7edf4;padding-top:22px;">
                <p style="margin:0 0 8px;color:#60708a;font-size:13px;font-weight:700;text-transform:uppercase;">Treść wiadomości</p>
                <p style="margin:0;white-space:pre-wrap;font-size:15px;line-height:1.65;">{{ $messageData['message'] }}</p>
            </div>
        </div>
    </div>
</body>
</html>
