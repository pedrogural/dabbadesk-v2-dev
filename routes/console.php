<?php

use App\Support\Text\TextNormalizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('text:repair-mojibake {--apply : Write repaired text back to the database} {--table= : Scan one configured table only} {--id= : Scan one row id only} {--limit=500 : Maximum candidate rows to inspect per table}', function (): int {
    $apply = (bool) $this->option('apply');
    $onlyTable = $this->option('table') ? trim((string) $this->option('table')) : null;
    $onlyId = $this->option('id') ? (int) $this->option('id') : null;
    $limit = max(1, (int) $this->option('limit'));

    $targets = [
        'order_request_items' => ['retailer_name', 'retailer_url', 'product_code', 'description', 'notes'],
        'draft_order_items' => ['retailer_name', 'retailer_url', 'product_url', 'product_code', 'description', 'notes', 'needs_attention_reason', 'reviewed_notes'],
        'order_items' => ['retailer_name', 'retailer_url', 'product_url', 'product_code', 'description', 'notes'],
        'order_item_purchases' => ['retailer_order_reference', 'tracking_number', 'purchase_notes', 'problem_notes', 'notes'],
        'purchase_arrival_assignments' => ['notes', 'retailer_order_reference', 'tracking_number'],
        'arrival_packages' => ['package_ref', 'tracking_number', 'carrier', 'supplier_label_ref', 'notes'],
        'activity_logs' => ['title', 'body'],
        'customer_ledger_entries' => ['reference', 'note'],
        'customer_credits' => ['notes'],
        'customer_wallet_ledger' => ['note'],
        'credit_notes' => ['reason'],
        'credit_note_items' => ['description'],
        'customer_release_notifications' => ['subject', 'body_text'],
        'customers' => ['first_name', 'last_name', 'company_name'],
        'addresses' => ['line1', 'line2', 'city', 'region', 'postcode'],
    ];

    if ($onlyTable !== null && ! array_key_exists($onlyTable, $targets)) {
        $this->error("Table [{$onlyTable}] is not configured for mojibake repair.");
        $this->line('Configured tables: '.implode(', ', array_keys($targets)));

        return self::FAILURE;
    }

    $patterns = ['â', 'Ã', 'Ãƒ', 'Ã¢', 'Â', '�'];
    $totalRowsChanged = 0;
    $totalFieldsChanged = 0;
    $totalSkippedUnsafe = 0;

    $this->newLine();
    $this->info($apply ? 'Mojibake repair: APPLY mode' : 'Mojibake repair: PREVIEW mode');
    $this->line($apply ? 'Changes will be written to the database.' : 'No database changes will be written. Re-run with --apply to repair.');
    $this->line('Unsafe repairs are skipped if the cleaned result still contains mojibake markers.');
    $this->newLine();

    foreach ($targets as $table => $candidateColumns) {
        if ($onlyTable !== null && $table !== $onlyTable) {
            continue;
        }

        if (! Schema::hasTable($table)) {
            continue;
        }

        $columns = array_values(array_filter($candidateColumns, fn (string $column): bool => Schema::hasColumn($table, $column)));

        if ($columns === [] || ! Schema::hasColumn($table, 'id')) {
            continue;
        }

        $query = DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id');

        if ($onlyId !== null) {
            $query->where('id', $onlyId);
        } else {
            $query->where(function ($outer) use ($columns, $patterns): void {
                foreach ($columns as $column) {
                    foreach ($patterns as $pattern) {
                        $outer->orWhere($column, 'like', '%'.$pattern.'%');
                    }
                }
            })->limit($limit);
        }

        $rows = $query->get();
        $tableRowsChanged = 0;
        $tableFieldsChanged = 0;
        $tableSkippedUnsafe = 0;
        $previewRows = [];
        $skippedRows = [];

        foreach ($rows as $row) {
            $updates = [];
            $rowHasChange = false;

            foreach ($columns as $column) {
                $original = $row->{$column};

                if (! is_string($original) || ! TextNormalizer::suspicious($original)) {
                    continue;
                }

                $cleaned = TextNormalizer::repairHistorical($original);

                if ($cleaned === null || $cleaned === $original) {
                    $tableSkippedUnsafe++;
                    $totalSkippedUnsafe++;

                    if (count($skippedRows) < 5) {
                        $skippedRows[] = [
                            'id' => $row->id,
                            'column' => $column,
                            'value' => Str::limit(str_replace("\n", ' ', $original), 90),
                        ];
                    }

                    continue;
                }

                $updates[$column] = $cleaned;
                $tableFieldsChanged++;
                $totalFieldsChanged++;
                $rowHasChange = true;

                if (count($previewRows) < 8) {
                    $previewRows[] = [
                        'id' => $row->id,
                        'column' => $column,
                        'before' => Str::limit(str_replace("\n", ' ', $original), 90),
                        'after' => Str::limit(str_replace("\n", ' ', $cleaned), 90),
                    ];
                }
            }

            if ($updates !== []) {
                $tableRowsChanged++;
                $totalRowsChanged++;

                if ($apply) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }

        if ($tableRowsChanged > 0) {
            $this->line("<fg=yellow>{$table}</>: {$tableRowsChanged} row(s), {$tableFieldsChanged} field(s)".($apply ? ' repaired' : ' would be repaired'));
            $this->table(['ID', 'Column', 'Before', 'After'], $previewRows);
        }

        if ($tableSkippedUnsafe > 0) {
            $this->line("<fg=gray>{$table}</>: {$tableSkippedUnsafe} unsafe candidate field(s) skipped");
            if (! empty($skippedRows)) {
                $this->table(['ID', 'Column', 'Value'], $skippedRows);
            }
        }
    }

    $this->newLine();

    if ($totalRowsChanged === 0 && $totalSkippedUnsafe === 0) {
        $this->info('No mojibake candidates found.');

        return self::SUCCESS;
    }

    $this->info(($apply ? 'Repaired' : 'Previewed')." {$totalRowsChanged} row(s) and {$totalFieldsChanged} field(s).");

    if ($totalSkippedUnsafe > 0) {
        $this->warn("Skipped {$totalSkippedUnsafe} unsafe candidate field(s). These should be reviewed manually or handled with a more specific mapping.");
    }

    if (! $apply) {
        $this->comment('Run again with --apply only if the previewed AFTER text is genuinely correct.');
        $this->comment('Example: php artisan text:repair-mojibake --apply');
    }

    return self::SUCCESS;
})->purpose('Preview or repair common UTF-8 mojibake in operational text fields');

Artisan::command('purchasing:migrate-legacy-problems {--apply : Write purchase_issues rows and mark legacy problem purchase rows as migrated} {--order= : Limit to one order id} {--purchase= : Limit to one order_item_purchases id} {--limit=500 : Maximum legacy rows to scan}', function (): int {
    $apply = (bool) $this->option('apply');
    $orderId = $this->option('order') ? (int) $this->option('order') : null;
    $purchaseId = $this->option('purchase') ? (int) $this->option('purchase') : null;
    $limit = max(1, (int) $this->option('limit'));

    if (! Schema::hasTable('order_item_purchases') || ! Schema::hasTable('purchase_issues')) {
        $this->error('Required tables are missing. Expected order_item_purchases and purchase_issues.');
        return self::FAILURE;
    }

    $legacyProblemStatuses = [
        'unfulfilled', 'failed', 'problem', 'supplier_problem', 'supplier_cancelled',
        'cancelled', 'refunded', 'retailer_refunded', 'lost', 'damaged', 'wrong_item', 'unavailable',
    ];

    $query = DB::table('order_item_purchases as oip')
        ->leftJoin('order_items as oi', 'oi.id', '=', 'oip.order_item_id')
        ->select([
            'oip.id',
            'oip.order_item_id',
            'oip.root_item_id',
            'oip.order_id',
            'oip.order_retailer_id',
            'oip.retailer_id',
            'oip.qty',
            'oip.status',
            'oip.problem_code',
            'oip.problem_notes',
            'oip.resolution_action',
            'oip.resolution_status',
            'oip.retailer_order_reference',
            'oip.created_by_user_id',
            'oip.updated_by_user_id',
            'oip.created_at',
            'oi.item_name',
        ])
        ->whereNull('oip.cancelled_at')
        ->where(function ($query) use ($legacyProblemStatuses) {
            $query->whereIn('oip.status', $legacyProblemStatuses)
                ->orWhereNotNull('oip.problem_code')
                ->orWhereNotNull('oip.problem_notes');
        })
        ->where(function ($query) {
            $query->whereNull('oip.resolution_status')
                ->orWhere('oip.resolution_status', '')
                ->orWhere('oip.resolution_status', 'pending');
        })
        ->orderBy('oip.id')
        ->limit($limit);

    if ($orderId !== null) {
        $query->where('oip.order_id', $orderId);
    }

    if ($purchaseId !== null) {
        $query->where('oip.id', $purchaseId);
    }

    $rows = $query->get();

    $this->newLine();
    $this->info($apply ? 'Legacy purchasing problem migration: APPLY mode' : 'Legacy purchasing problem migration: PREVIEW mode');
    $this->line($apply ? 'Rows will be inserted into purchase_issues and legacy purchase rows marked as resolved/migrated.' : 'No database changes will be written. Re-run with --apply to migrate.');
    $this->newLine();

    if ($rows->isEmpty()) {
        $this->info('No pending legacy purchasing problem rows found.');
        return self::SUCCESS;
    }

    $preview = [];
    $created = 0;
    $alreadyExists = 0;
    $marked = 0;

    foreach ($rows as $row) {
        $legacyId = (int) $row->id;
        $rootItemId = (int) ($row->root_item_id ?: $row->order_item_id);
        $issueType = trim((string) ($row->problem_code ?: $row->status ?: 'legacy_problem')) ?: 'legacy_problem';
        $issueType = Str::of($issueType)->lower()->replace([' ', '-'], '_')->substr(0, 50)->toString();
        $qty = max(1, (int) $row->qty);
        $notes = trim(implode("\n", array_filter([
            $row->problem_notes ? trim((string) $row->problem_notes) : null,
            $row->retailer_order_reference ? 'Retailer ref: '.trim((string) $row->retailer_order_reference) : null,
            'Migrated from legacy order_item_purchases #'.$legacyId.'.',
        ])));

        $exists = DB::table('purchase_issues')
            ->where('order_id', (int) $row->order_id)
            ->where('order_item_id', (int) $row->order_item_id)
            ->where('root_item_id', $rootItemId)
            ->where('issue_type', $issueType)
            ->where('notes', 'like', '%legacy order_item_purchases #'.$legacyId.'%')
            ->exists();

        $preview[] = [
            'legacy_purchase_id' => $legacyId,
            'order_id' => (int) $row->order_id,
            'item' => Str::limit((string) ($row->item_name ?: '#'.$row->order_item_id), 35),
            'qty' => $qty,
            'issue_type' => $issueType,
            'action' => $exists ? 'already exists; mark legacy migrated' : 'create purchase_issues row',
        ];

        if ($exists) {
            $alreadyExists++;
        }

        if ($apply) {
            DB::transaction(function () use ($row, $legacyId, $rootItemId, $issueType, $qty, $notes, $exists, &$created, &$marked): void {
                if (! $exists) {
                    DB::table('purchase_issues')->insert([
                        'order_item_id' => (int) $row->order_item_id,
                        'root_item_id' => $rootItemId,
                        'order_id' => (int) $row->order_id,
                        'order_retailer_id' => $row->order_retailer_id,
                        'retailer_id' => $row->retailer_id,
                        'qty' => $qty,
                        'issue_type' => $issueType,
                        'severity' => 'medium',
                        'status' => 'open',
                        'notes' => $notes,
                        'requires_customer_action' => 0,
                        'created_by_user_id' => $row->created_by_user_id,
                        'updated_by_user_id' => $row->updated_by_user_id ?: $row->created_by_user_id,
                        'created_at' => $row->created_at ?: now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                }

                DB::table('order_item_purchases')
                    ->where('id', $legacyId)
                    ->update([
                        'resolution_status' => 'resolved',
                        'resolution_action' => 'migrated_to_purchase_issues',
                        'updated_at' => now(),
                    ]);
                $marked++;
            });
        }
    }

    $this->table(['Legacy purchase', 'Order', 'Item', 'Qty', 'Issue type', 'Action'], $preview);
    $this->newLine();

    if ($apply) {
        $this->info("Created {$created} purchase_issues row(s). Marked {$marked} legacy row(s) as migrated/resolved. {$alreadyExists} issue row(s) already existed.");
    } else {
        $this->info('Previewed '.$rows->count().' pending legacy problem row(s). '.$alreadyExists.' already appear to have matching purchase_issues rows.');
        $this->comment('Run with --apply after checking the preview. Example: php artisan purchasing:migrate-legacy-problems --apply');
    }

    return self::SUCCESS;
})->purpose('Convert legacy order_item_purchases problem rows into purchase_issues and silence legacy counters');


Artisan::command('purchasing:migrate-legacy-item-problems {--apply : Write purchase_issues rows and clear legacy order_items problem fields} {--order= : Limit to one order id} {--item= : Limit to one order_items id} {--limit=500 : Maximum legacy item rows to scan}', function (): int {
    $apply = (bool) $this->option('apply');
    $orderId = $this->option('order') ? (int) $this->option('order') : null;
    $itemId = $this->option('item') ? (int) $this->option('item') : null;
    $limit = max(1, (int) $this->option('limit'));

    if (! Schema::hasTable('order_items') || ! Schema::hasTable('purchase_issues')) {
        $this->error('Required tables are missing. Expected order_items and purchase_issues.');
        return self::FAILURE;
    }

    $query = DB::table('order_items as oi')
        ->select([
            'oi.id',
            'oi.root_item_id',
            'oi.order_id',
            'oi.order_retailer_id',
            'oi.quantity',
            'oi.item_name',
            'oi.purchase_problem_reason',
            'oi.purchase_problem_note',
            'oi.created_by_user_id',
            'oi.updated_by_user_id',
            'oi.created_at',
            'oi.updated_at',
            'oi.retailer_order_reference',
            'or.retailer_id',
        ])
        ->leftJoin('order_retailers as or', 'or.id', '=', 'oi.order_retailer_id')
        ->where(function ($query): void {
            $query->whereNotNull('oi.purchase_problem_reason')
                ->orWhereNotNull('oi.purchase_problem_note');
        })
        ->orderBy('oi.id')
        ->limit($limit);

    if ($orderId !== null) {
        $query->where('oi.order_id', $orderId);
    }

    if ($itemId !== null) {
        $query->where('oi.id', $itemId);
    }

    $rows = $query->get();

    $this->newLine();
    $this->info($apply ? 'Legacy order item problem migration: APPLY mode' : 'Legacy order item problem migration: PREVIEW mode');
    $this->line($apply ? 'Rows will be inserted into purchase_issues and order_items legacy problem fields cleared.' : 'No database changes will be written. Re-run with --apply to migrate.');
    $this->newLine();

    if ($rows->isEmpty()) {
        $this->info('No legacy order_items problem rows found.');
        return self::SUCCESS;
    }

    $preview = [];
    $created = 0;
    $alreadyExists = 0;
    $cleared = 0;

    foreach ($rows as $row) {
        $itemId = (int) $row->id;
        $rootItemId = (int) ($row->root_item_id ?: $row->id);
        $issueType = trim((string) ($row->purchase_problem_reason ?: 'legacy_item_problem')) ?: 'legacy_item_problem';
        $issueType = Str::of($issueType)->lower()->replace([' ', '-'], '_')->substr(0, 50)->toString();
        $qty = max(1, (int) $row->quantity);

        $legacyPayload = null;
        $legacyAction = null;
        $legacyNote = null;
        $recordedAt = null;
        $recordedBy = null;

        if (is_string($row->purchase_problem_note) && trim($row->purchase_problem_note) !== '') {
            $decoded = json_decode($row->purchase_problem_note, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $legacyPayload = $decoded;
                $legacyAction = isset($decoded['action']) ? (string) $decoded['action'] : null;
                $legacyNote = isset($decoded['note']) ? (string) $decoded['note'] : null;
                $recordedAt = isset($decoded['recorded_at']) ? (string) $decoded['recorded_at'] : null;
                $recordedBy = isset($decoded['recorded_by_user_id']) ? (int) $decoded['recorded_by_user_id'] : null;
            } else {
                $legacyNote = trim((string) $row->purchase_problem_note);
            }
        }

        $requiresCustomerAction = $legacyAction === 'awaiting_customer_decision' ? 1 : 0;

        $notes = trim(implode("\n", array_filter([
            $legacyAction ? 'Legacy action: '.$legacyAction : null,
            $legacyNote ? $legacyNote : null,
            $row->retailer_order_reference ? 'Retailer ref: '.trim((string) $row->retailer_order_reference) : null,
            'Migrated from legacy order_items purchase_problem fields on item #'.$itemId.'.',
        ])));

        $exists = DB::table('purchase_issues')
            ->where('order_id', (int) $row->order_id)
            ->where('order_item_id', $itemId)
            ->where('root_item_id', $rootItemId)
            ->where('status', 'open')
            ->exists();

        $preview[] = [
            'item_id' => $itemId,
            'order_id' => (int) $row->order_id,
            'item' => Str::limit((string) ($row->item_name ?: '#'.$itemId), 42),
            'qty' => $qty,
            'issue_type' => $issueType,
            'legacy_action' => $legacyAction ?: '—',
            'action' => $exists ? 'already has open issue; clear legacy fields' : 'create purchase_issues row',
        ];

        if ($exists) {
            $alreadyExists++;
        }

        if ($apply) {
            DB::transaction(function () use ($row, $itemId, $rootItemId, $issueType, $qty, $notes, $requiresCustomerAction, $recordedAt, $recordedBy, $exists, &$created, &$cleared): void {
                if (! $exists) {
                    DB::table('purchase_issues')->insert([
                        'order_item_id' => $itemId,
                        'root_item_id' => $rootItemId,
                        'order_id' => (int) $row->order_id,
                        'order_retailer_id' => $row->order_retailer_id,
                        'retailer_id' => $row->retailer_id,
                        'qty' => $qty,
                        'issue_type' => $issueType,
                        'severity' => 'medium',
                        'status' => 'open',
                        'notes' => $notes,
                        'requires_customer_action' => $requiresCustomerAction,
                        'customer_contacted_at' => null,
                        'customer_replied_at' => null,
                        'resolution_type' => null,
                        'resolution_notes' => null,
                        'resolved_at' => null,
                        'created_by_user_id' => $recordedBy ?: $row->updated_by_user_id ?: $row->created_by_user_id,
                        'updated_by_user_id' => $row->updated_by_user_id ?: $recordedBy ?: $row->created_by_user_id,
                        'resolved_by_user_id' => null,
                        'created_at' => $recordedAt ?: $row->updated_at ?: $row->created_at ?: now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                }

                DB::table('order_items')
                    ->where('id', $itemId)
                    ->update([
                        'purchase_problem_reason' => null,
                        'purchase_problem_note' => null,
                        'updated_at' => now(),
                    ]);
                $cleared++;
            });
        }
    }

    $this->table(['Item ID', 'Order', 'Item', 'Qty', 'Issue type', 'Legacy action', 'Action'], $preview);
    $this->newLine();

    if ($apply) {
        $this->info("Created {$created} purchase_issues row(s). Cleared {$cleared} legacy order_items problem row(s). {$alreadyExists} row(s) already had an open issue.");
    } else {
        $this->info('Previewed '.$rows->count().' legacy order_items problem row(s). '.$alreadyExists.' already have open purchase_issues rows.');
        $this->comment('Run with --apply after checking the preview. Example: php artisan purchasing:migrate-legacy-item-problems --order=863 --apply');
    }

    return self::SUCCESS;
})->purpose('Convert legacy order_items purchase_problem fields into purchase_issues and silence legacy item counters');
