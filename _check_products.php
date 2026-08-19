<?php
$json = json_decode(file_get_contents('database/seeders/products.json'), true);
if ($json === null) { echo "INVALID JSON\n"; exit(1); }
echo "Total products: " . count($json) . "\n";
$cats = [];
foreach ($json as $p) { $cid = $p['category_id']; $cats[$cid] = ($cats[$cid] ?? 0) + 1; }
ksort($cats);
foreach ($cats as $cid => $cnt) { echo "Category $cid: $cnt\n"; }
$skus = array_column($json, 'sku');
echo "Unique SKUs: " . count(array_unique($skus)) . ' / ' . count($skus) . "\n";
