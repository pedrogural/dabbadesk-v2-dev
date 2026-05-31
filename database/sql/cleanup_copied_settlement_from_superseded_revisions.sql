-- DabbaDesk cleanup for replacement order revisions whose settlement events were copied
-- from a superseded parent order.
--
-- What it does:
-- 1) Creates a real wallet credit for the superseded parent order's settled balance,
--    if that credit does not already exist.
-- 2) Removes copied order_transactions from the replacement child order, so the child
--    order starts with its own true financial position.
--
-- Safe selector: only child transactions with the old carried-forward note suffix are removed.

START TRANSACTION;

INSERT INTO customer_credits (
    customer_id,
    order_id,
    source_type,
    source_id,
    source_invoice_version_id,
    amount,
    remaining_amount,
    status,
    notes,
    currency,
    created_by_user_id,
    created_at,
    updated_at
)
SELECT
    parent_draft.customer_id,
    parent_order.id,
    'superseded_order_balance',
    parent_order.id,
    NULL,
    ROUND(LEAST(parent_order.grand_total, GREATEST(0, SUM(parent_tx.amount))), 2) AS credit_amount,
    ROUND(LEAST(parent_order.grand_total, GREATEST(0, SUM(parent_tx.amount))), 2) AS remaining_amount,
    'open',
    CONCAT('Balance moved from superseded order #', parent_order.order_number, ' after removing copied settlement events from replacement revision.'),
    'GBP',
    child_order.created_by_user_id,
    NOW(),
    NOW()
FROM orders AS child_order
JOIN orders AS parent_order
    ON parent_order.id = child_order.parent_order_id
JOIN draft_orders AS parent_draft
    ON parent_draft.id = parent_order.draft_order_id
JOIN order_transactions AS copied_child_tx
    ON copied_child_tx.order_id = child_order.id
   AND copied_child_tx.note LIKE '%Carried forward from superseded Order ID #%'
JOIN order_transactions AS parent_tx
    ON parent_tx.order_id = parent_order.id
   AND parent_tx.status = 'recorded'
   AND parent_tx.type IN (
        'payment',
        'credit_application',
        'payment_void',
        'credit_application_void',
        'refund',
        'refund_void'
   )
WHERE child_order.parent_order_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM customer_credits AS existing_credit
      WHERE existing_credit.source_type = 'superseded_order_balance'
        AND existing_credit.source_id = parent_order.id
  )
GROUP BY
    child_order.id,
    parent_order.id,
    parent_order.order_number,
    parent_order.grand_total,
    parent_draft.customer_id,
    child_order.created_by_user_id
HAVING credit_amount > 0;

DELETE copied_child_tx
FROM order_transactions AS copied_child_tx
JOIN orders AS child_order
    ON child_order.id = copied_child_tx.order_id
WHERE child_order.parent_order_id IS NOT NULL
  AND copied_child_tx.note LIKE '%Carried forward from superseded Order ID #%';

COMMIT;
