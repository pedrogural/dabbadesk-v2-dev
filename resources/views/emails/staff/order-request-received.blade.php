@extends('emails.layouts.dabba')

@section('title', 'New order request ' . $reference)
@section('subtitle', 'New web order received')
@section('reference', $reference)

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;line-height:1.25;color:#111827;">
        New web order request received
    </h1>

    <p style="margin:0 0 22px;font-size:14px;color:#475569;">
        A customer has submitted a new order request for team review.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
        <tr>
            <td
                style="background:#f8fafc;padding:12px 16px;font-size:11px;font-weight:800;color:#334155;text-transform:uppercase;">
                Customer details
            </td>
        </tr>
        <tr>
            <td style="padding:16px;">
                <p style="margin:0 0 8px;font-size:13px;"><strong>Name:</strong> {{ $customerName ?: 'Not supplied' }}</p>
                <p style="margin:0 0 8px;font-size:13px;"><strong>Email:</strong> {{ $customerEmail ?: 'Not supplied' }}</p>
                <p style="margin:0 0 8px;font-size:13px;"><strong>Phone:</strong> {{ $customerPhone ?: 'Not supplied' }}</p>
                <p style="margin:0;font-size:13px;"><strong>Address:</strong> {{ $customerAddress ?: 'Not supplied' }}</p>
            </td>
        </tr>
    </table>


    @if (!empty($requestNotes ?? ''))
        <div style="margin:20px 0;padding:16px;border:1px solid #fbbf24;border-radius:12px;background:#fffbeb;">
            <div style="font-size:13px;font-weight:800;color:#92400e;margin-bottom:8px;">Customer request notes</div>
            <div style="white-space:pre-wrap;font-size:13px;line-height:1.6;color:#78350f;">{{ $requestNotes }}</div>
        </div>
    @endif

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

    @if ($attachmentCount > 0)
        <div style="margin-top:20px;padding:16px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;">
            <div style="font-size:13px;font-weight:800;color:#111827;margin-bottom:10px;">
                Attachments received ({{ $attachmentCount }})
            </div>

            @foreach ($attachments as $attachment)
                <div style="font-size:12px;color:#475569;line-height:1.7;">
                    • {{ $attachment['original_name'] ?? 'Attachment' }}
                </div>
            @endforeach
        </div>
    @endif
@endsection
