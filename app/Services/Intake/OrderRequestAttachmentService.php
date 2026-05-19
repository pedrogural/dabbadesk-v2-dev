<?php

namespace App\Services\Intake;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderRequestAttachmentService
{
    public function storeForRequest(
        int $orderRequestId,
        string $requestRef,
        array $files
    ): int {
        $storedCount = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

            if ($safeName === '') {
                $safeName = 'attachment';
            }

            $filename = $safeName . '-' . Str::random(10);

            if ($extension !== '') {
                $filename .= '.' . strtolower($extension);
            }

            $path = $file->storeAs(
                'order-requests/' . $requestRef,
                $filename
            );

            DB::table('order_request_attachments')->insert([
                'order_request_id' => $orderRequestId,
                'path' => $path,
                'original_name' => $originalName,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $storedCount++;
        }

        return $storedCount;
    }
}