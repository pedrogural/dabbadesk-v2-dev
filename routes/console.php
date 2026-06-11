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
