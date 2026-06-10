-- DabbaDesk mojibake repair helper.
-- Run on a backup first. This fixes common UTF-8-as-Latin1/Windows-1252 artefacts.

SET @tables_checked = 'draft_order_items, order_request_items, order_items, invoice_version_items, activity_logs';

UPDATE draft_order_items
SET
  description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  product_code = REPLACE(REPLACE(REPLACE(REPLACE(product_code, 'Ã—', '×'), 'Â£', '£'), 'â€“', '–'), 'Â', ''),
  sku = REPLACE(REPLACE(REPLACE(REPLACE(sku, 'Ã—', '×'), 'Â£', '£'), 'â€“', '–'), 'Â', '')
WHERE description REGEXP 'â|Ã|Â' OR product_code REGEXP 'â|Ã|Â' OR sku REGEXP 'â|Ã|Â';

UPDATE order_request_items
SET
  retailer_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(retailer_name, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  product_code = REPLACE(REPLACE(REPLACE(REPLACE(product_code, 'Ã—', '×'), 'Â£', '£'), 'â€“', '–'), 'Â', ''),
  description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', '')
WHERE retailer_name REGEXP 'â|Ã|Â' OR product_code REGEXP 'â|Ã|Â' OR description REGEXP 'â|Ã|Â' OR notes REGEXP 'â|Ã|Â';

UPDATE order_items
SET
  item_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(item_name, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  product_code = REPLACE(REPLACE(REPLACE(REPLACE(product_code, 'Ã—', '×'), 'Â£', '£'), 'â€“', '–'), 'Â', '')
WHERE item_name REGEXP 'â|Ã|Â' OR description REGEXP 'â|Ã|Â' OR product_code REGEXP 'â|Ã|Â';

UPDATE invoice_version_items
SET
  description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  sku = REPLACE(REPLACE(REPLACE(REPLACE(sku, 'Ã—', '×'), 'Â£', '£'), 'â€“', '–'), 'Â', '')
WHERE description REGEXP 'â|Ã|Â' OR sku REGEXP 'â|Ã|Â';

UPDATE activity_logs
SET
  title = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', ''),
  body = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(body, 'â€“', '–'), 'â€”', '—'), 'â€˜', '‘'), 'â€™', '’'), 'â€œ', '“'), 'â€�', '”'), 'Ã—', '×'), 'Â£', '£'), 'Â', '')
WHERE title REGEXP 'â|Ã|Â' OR body REGEXP 'â|Ã|Â';
