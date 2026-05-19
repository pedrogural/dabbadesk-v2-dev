@php
    $retailerName = $retailerName ?? 'Unknown retailer';
    $items = $items ?? [];
    $subtotal = (float) ($subtotal ?? 0);
    $dabbaFee = (float) ($dabbaFee ?? 0);
    $total = $subtotal + $dabbaFee;
@endphp

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:14px 16px;background:#f8fafc;border-left:5px solid #a21caf;">
            <div style="font-size:15px;font-weight:800;color:#111827;">{{ $retailerName }}</div>
            <div style="font-size:12px;color:#475569;margin-top:8px;">
                Retail subtotal: <strong>£{{ number_format($subtotal, 2) }}</strong>
                &nbsp;|&nbsp;
                Dabba fee: <strong>£{{ number_format($dabbaFee, 2) }}</strong>
                &nbsp;|&nbsp;
                Total: <strong>£{{ number_format($total, 2) }}</strong>
            </div>
        </td>
    </tr>

    @foreach ($items as $item)
        <tr>
            <td style="padding:14px 16px;border-top:1px solid #e5e7eb;">
                <div style="font-size:13px;font-weight:700;line-height:1.45;color:#111827;">
                    {{ $item['description'] ?? $item['product_code'] ?? 'Item' }}
                </div>

                <div style="margin-top:8px;font-size:12px;color:#475569;">
                    Qty: <strong>{{ $item['qty'] ?? 1 }}</strong>
                    &nbsp;|&nbsp;
                    Unit: <strong>£{{ number_format((float) ($item['estimated_price'] ?? 0), 2) }}</strong>
                    &nbsp;|&nbsp;
                    Line: <strong>£{{ number_format(((int) ($item['qty'] ?? 1)) * ((float) ($item['estimated_price'] ?? 0)), 2) }}</strong>
                </div>

                @if (!empty($item['retailer_url']))
                    <div style="margin-top:8px;">
                        <a href="{{ $item['retailer_url'] }}" style="color:#1d4ed8;font-size:12px;font-weight:700;">View product</a>
                    </div>
                @endif
            </td>
        </tr>
    @endforeach
</table>
