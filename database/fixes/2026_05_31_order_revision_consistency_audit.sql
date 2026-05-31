-- DabbaDesk order revision consistency audit / cleanup
-- Run the SELECT first. Only run the UPDATE if the duplicate order-number rows are clearly superseded older revisions.

SELECT
    older.id AS older_order_id,
    older.order_number,
    older.status AS older_status,
    older.grand_total AS older_total,
    latest.latest_order_id,
    newer.status AS latest_status,
    newer.grand_total AS latest_total
FROM orders older
JOIN (
    SELECT order_number, MAX(id) AS latest_order_id
    FROM orders
    WHERE order_number IS NOT NULL
      AND order_number <> ''
      AND cancelled_at IS NULL
      AND status NOT IN ('cancelled', 'superseded')
    GROUP BY order_number
    HAVING COUNT(*) > 1
) latest ON latest.order_number = older.order_number
JOIN orders newer ON newer.id = latest.latest_order_id
WHERE older.id <> latest.latest_order_id
  AND older.cancelled_at IS NULL
  AND older.status NOT IN ('cancelled', 'superseded')
ORDER BY older.order_number, older.id;

-- Optional cleanup after reviewing the SELECT result.
-- This prevents old revisions from appearing in active order/anomaly lists.
/*
UPDATE orders older
JOIN (
    SELECT order_number, MAX(id) AS latest_order_id
    FROM orders
    WHERE order_number IS NOT NULL
      AND order_number <> ''
      AND cancelled_at IS NULL
      AND status NOT IN ('cancelled', 'superseded')
    GROUP BY order_number
    HAVING COUNT(*) > 1
) latest ON latest.order_number = older.order_number
SET
    older.status = 'superseded',
    older.cancel_reason = COALESCE(older.cancel_reason, CONCAT('Superseded by newer revision Order ID ', latest.latest_order_id)),
    older.updated_at = NOW()
WHERE older.id <> latest.latest_order_id
  AND older.cancelled_at IS NULL
  AND older.status NOT IN ('cancelled', 'superseded');
*/
