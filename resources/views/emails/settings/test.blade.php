<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">Mail configuration works</h1>

    <p style="margin:0 0 16px;">Hi {{ $recipientName }},</p>

    <p style="margin:0 0 16px;">
        This is a test message sent from the settings screen of {{ config('app.name') }}.
        If you are reading it, your mail settings are configured correctly.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;width:160px;color:#71717a;">Mailer</td>
            <td style="padding:8px 0;">{{ config('mail.default') }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">From address</td>
            <td style="padding:8px 0;">{{ config('mail.from.address') }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Sent at</td>
            <td style="padding:8px 0;">{{ now()->format('d M Y, H:i') }}</td>
        </tr>
    </table>
</x-mail-layout>