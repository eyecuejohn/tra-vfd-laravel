<p>Dear {{ $order['customer_name'] ?? 'Valued Customer' }},</p>

<p>Thank you for your recent payment. Please find your official TRA Fiscal Receipt attached to this email as a PDF document.</p>

<p>
    <strong>Receipt Details:</strong><br>
    Receipt Number: {{ $order['receipt_number'] }}<br>
    Date: {{ $order['date'] }}
</p>

<p>You can also verify this receipt online by scanning the QR code inside the attached document.</p>

<p>Best Regards,<br>
{{ config('tra-vfd.company_name') }}</p>