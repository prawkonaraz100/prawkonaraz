@php
    // CID images stay inside the message; no external image download is needed.
    $icon = fn (string $name): string => $message->embed(public_path('images/email/verification/'.$name.'.png'));
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Potwierdź swój adres e-mail</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-outer { padding: 16px 10px !important; }
            .email-card { width: 100% !important; }
            .email-content { padding: 28px 20px 18px !important; }
            .email-heading { font-size: 27px !important; line-height: 34px !important; }
            .email-lead { font-size: 17px !important; line-height: 25px !important; }
            .email-button-table { width: 100% !important; }
            .email-button { padding: 18px 12px !important; font-size: 18px !important; }
            .email-help { padding: 16px !important; }
            .email-help-icon { width: 48px !important; padding-right: 12px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f3f6f9;font-family:Arial,Helvetica,sans-serif;color:#102347;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Jeszcze jeden krok — potwierdź adres e-mail i rozpocznij naukę w PrawkoNaRaz.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f3f6f9" style="width:100%;background-color:#f3f6f9;">
        <tr>
            <td class="email-outer" align="center" style="padding:24px 16px;">
                <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
                <table role="presentation" class="email-card" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:16px;box-shadow:0 16px 48px rgba(26,48,82,0.10);">
                    <tr>
                        <td class="email-content" style="padding:30px 28px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td valign="middle" style="padding-right:14px;">
                                        <a href="{{ $homeUrl }}" style="font-size:26px;line-height:32px;font-weight:700;letter-spacing:-1px;text-decoration:none;color:#143462;">prawko<span style="color:#f7ad27;">naraz</span>.pl</a>
                                    </td>
                                    <td valign="middle"><img src="{{ $icon('brand-shield') }}" width="58" height="58" alt="" style="display:block;border:0;"></td>
                                </tr>
                            </table>
                            <h1 class="email-heading" style="margin:30px 0 16px;font-size:32px;line-height:40px;letter-spacing:-0.8px;text-align:center;color:#102347;font-weight:700;">Potwierdź swój adres e-mail</h1>
                            <p class="email-lead" style="margin:0 auto;text-align:center;font-size:19px;line-height:26px;color:#66758e;max-width:460px;">Dziękujemy za rejestrację w PrawkoNaRaz.<br>Kliknij przycisk poniżej, aby aktywować konto<br>i rozpocząć naukę.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:22px auto 18px;">
                                <tr>
                                    <td width="64" style="width:64px;"><div style="height:1px;background-color:#d9dfe7;font-size:1px;line-height:1px;">&nbsp;</div></td>
                                    <td style="padding:0 14px;"><img src="{{ $icon('graduation') }}" width="28" height="28" alt="" style="display:block;border:0;"></td>
                                    <td width="64" style="width:64px;"><div style="height:1px;background-color:#d9dfe7;font-size:1px;line-height:1px;">&nbsp;</div></td>
                                </tr>
                            </table>
                            <table role="presentation" class="email-button-table" width="404" cellpadding="0" cellspacing="0" align="center" style="width:404px;max-width:100%;margin:0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#ffc344" style="background-color:#ffc344;border-radius:13px;box-shadow:0 6px 18px rgba(247,173,39,0.18);">
                                        <a class="email-button" href="{{ $verificationUrl }}" style="display:block;padding:19px 20px;border:1px solid #ffc344;border-radius:13px;text-decoration:none;color:#102347;font-size:21px;line-height:24px;font-weight:700;mso-padding-alt:0;">
                                            <!--[if mso]><i style="mso-font-width:100%;mso-text-raise:24pt;">&nbsp;</i><![endif]-->
                                            <img src="{{ $icon('envelope') }}" width="26" height="26" alt="" style="display:inline-block;vertical-align:middle;border:0;margin-right:12px;">
                                            <span style="vertical-align:middle;">Potwierdź adres e-mail</span>
                                            <span aria-hidden="true" style="margin-left:12px;vertical-align:middle;">→</span>
                                            <!--[if mso]><i style="mso-font-width:100%;">&nbsp;</i><![endif]-->
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:14px 0 26px;text-align:center;font-size:14px;line-height:20px;color:#8592aa;"><img src="{{ $icon('clock') }}" width="16" height="16" alt="" style="display:inline-block;vertical-align:middle;border:0;margin-right:5px;"> Link wygasa po {{ $expiryLabel }}.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f6f9" style="width:100%;background-color:#f4f6f9;border-radius:13px;">
                                <tr>
                                    <td class="email-help" style="padding:18px 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td class="email-help-icon" width="72" valign="top" style="width:72px;padding-right:24px;"><img src="{{ $icon('shield') }}" width="48" height="48" alt="" style="display:block;border:0;"></td>
                                                <td valign="middle" style="font-size:14px;line-height:22px;color:#66758e;"><strong style="font-size:16px;color:#102347;">Jeśli to nie Ty,</strong><br>zignoruj tę wiadomość.</td>
                                            </tr>
                                            <tr><td colspan="2" style="padding:16px 0;"><div style="height:1px;background-color:#e1e6ed;font-size:1px;line-height:1px;">&nbsp;</div></td></tr>
                                            <tr>
                                                <td class="email-help-icon" width="72" valign="top" style="width:72px;padding-right:24px;"><img src="{{ $icon('link') }}" width="48" height="48" alt="" style="display:block;border:0;"></td>
                                                <td valign="top" style="font-size:14px;line-height:22px;color:#66758e;">
                                                    <strong style="font-size:16px;color:#102347;">Masz problem z przyciskiem?</strong><br>Skopiuj link poniżej do przeglądarki:
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;width:100%;margin-top:8px;">
                                                        <tr><td bgcolor="#e9edf3" style="padding:12px 14px;background-color:#e9edf3;border-radius:8px;word-break:break-all;overflow-wrap:anywhere;font-family:Consolas,'Courier New',monospace;font-size:11px;line-height:17px;"><a href="{{ $verificationUrl }}" style="color:#102347;text-decoration:none;word-break:break-all;overflow-wrap:anywhere;">{{ $verificationUrl }}</a></td></tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;margin-top:18px;">
                                <tr><td style="padding:16px 8px 0;border-top:1px solid #e1e6ed;font-size:14px;line-height:22px;color:#66758e;">Pozdrawiamy,<br><strong style="color:#102347;">zespół PrawkoNaRaz</strong></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td align="center" style="padding:15px 20px;border-top:1px solid #e7ecf2;font-size:11px;line-height:17px;color:#98a3b6;">© {{ $year }} prawkonaraz.pl. Wszelkie prawa zastrzeżone.</td></tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
