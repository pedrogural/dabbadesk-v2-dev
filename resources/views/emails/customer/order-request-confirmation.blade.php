@extends('emails.layouts.dabba')

@section('title', 'Order request received ' . $reference)
@section('subtitle', 'Order request confirmation')
@section('reference', $reference)

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;line-height:1.25;color:#111827;">
        Thanks — we’ve received your request.
    </h1>

    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
        We’ll review your items and confirm any questions before we proceed. Please keep your reference handy:
        <strong>{{ $reference }}</strong>.
    </p>

    @if (!empty($customerDetails ?? []))
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
            style="margin:18px 0;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
            <tr>
                <td
                    style="background:#f8fafc;padding:12px 16px;font-size:11px;font-weight:800;color:#334155;text-transform:uppercase;">
                    Your details
                </td>
            </tr>
            <tr>
                <td style="padding:16px;">
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Name:</strong>
                        {{ $customerDetails['name'] ?? 'Not supplied' }}</p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Email:</strong>
                        {{ $customerDetails['email'] ?? 'Not supplied' }}</p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Phone:</strong>
                        {{ $customerDetails['phone'] ?? 'Not supplied' }}</p>
                    <p style="margin:0 0 8px;font-size:13px;"><strong>Address:</strong>
                        {{ $customerDetails['address'] ?? 'Not supplied' }}</p>
                    <p style="margin:0;font-size:13px;"><strong>Country:</strong>
                        {{ $customerDetails['country'] ?? 'Not supplied' }}</p>
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="margin:18px 0;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:12px;">
        <tr>
            <td style="padding:16px;">
                <div style="font-size:13px;font-weight:800;color:#166534;">What happens next?</div>
                <ol style="margin:10px 0 0;padding-left:20px;font-size:13px;line-height:1.7;color:#166534;">
                    <li>We check availability and details.</li>
                    <li>We confirm pricing and timing.</li>
                    <li>We contact you with the next step.</li>
                </ol>
            </td>
        </tr>
    </table>

    @foreach ($groups as $group)
        @include('emails.partials.retailer-group', [
            'retailerName' => $group['retailer_name'],
            'items' => $group['items'],
            'subtotal' => $group['subtotal'],
            'dabbaFee' => $group['dabba_fee'],
        ])
    @endforeach

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="margin-top:20px;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:16px;background:#f8fafc;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="font-size:13px;color:#475569;">Retail subtotal</td>
                        <td align="right" style="font-size:13px;font-weight:700;">£{{ number_format($retailSubtotal, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:8px;font-size:13px;color:#475569;">Dabba fees</td>
                        <td align="right" style="padding-top:8px;font-size:13px;font-weight:700;">
                            £{{ number_format($dabbaFees, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top:12px;border-top:1px solid #dbe3ef;font-size:16px;font-weight:800;">Estimated
                            total</td>
                        <td align="right"
                            style="padding-top:12px;border-top:1px solid #dbe3ef;font-size:18px;font-weight:900;">
                            £{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (($attachmentCount ?? 0) > 0)
        <div style="margin-top:20px;padding:16px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;">
            <div style="font-size:13px;font-weight:800;color:#111827;margin-bottom:8px;">
                Attachments received: {{ $attachmentCount }}
            </div>

            @foreach ($attachments ?? [] as $attachment)
                <div style="font-size:12px;color:#475569;line-height:1.7;">
                    • {{ $attachment['original_name'] ?? 'Attachment' }}
                </div>
            @endforeach
        </div>
    @endif

    <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#475569;">
        This email confirms we received your request. It is not yet an invoice or payment request.
    </p>
@endsection
