# URL cleaner smoke tests

Run in either Laravel app with `php artisan tinker`:

```php
app(\DabbaDirect\IntakeTools\ProductUrlResolver::class)
    ->resolve('https://www.amazon.co.uk/Nestware-Kitchen-Shredder-Reinforced-Interchangeable/dp/B0DPBCKVL5/ref=sr_1_6?crid=34WIF7WBAHVNI&keywords=cheese%2Bgrater&sr=8-6&th=1')
    ->toArray();
```

Expected `final_url`:

```text
https://www.amazon.co.uk/dp/B0DPBCKVL5
```

```php
app(\DabbaDirect\IntakeTools\ProductUrlResolver::class)
    ->resolve('https://www.ebay.co.uk/itm/Test-Product/235123456789?hash=item123&mkcid=1')
    ->toArray();
```

Expected `final_url`:

```text
https://www.ebay.co.uk/itm/235123456789
```

For eBay variations, `var=` is intentionally preserved because it can identify the selected size/colour variation.
