{{-- Nette HTML-mail voor facturen/offertes die via de eigen mailverbinding
     van de gebruiker (Gmail / Microsoft 365) worden verstuurd. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
        <tr>
          <td style="background:white;border-radius:12px;padding:36px 40px;border:1px solid #e5e7eb;">
            <p style="margin:0 0 16px;color:#111827;font-size:15px;">Beste {{ $salutation }},</p>
            @foreach($lines as $line)
            <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">{!! $line !!}</p>
            @endforeach
            <p style="margin:20px 0 4px;color:#374151;font-size:15px;">Met vriendelijke groet,</p>
            <p style="margin:0;color:#111827;font-size:15px;font-weight:600;">{{ $sender }}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 8px;text-align:center;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">Het document is als PDF bijgevoegd.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
