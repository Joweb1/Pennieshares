<?php 
    require_once __DIR__ . '/../src/functions.php';
    check_auth();
    $assetTypes = getAssetTypes($pdo);
    require_once __DIR__ . '/../assets/template/intro-template.php';
?>

  <style>
    :root {
      --bg-primary: #f9fafb;
      --bg-tertiary: #ffffff;
      --text-primary: #101518;
      --text-secondary: #5c748a;
      --border-color: #eaedf1;
      --accent-color: #007aff;
      --font-family: 'Inter', 'Noto Sans', sans-serif;
    }

    body { margin: 0; font-family: var(--font-family); background-color: var(--bg-primary); color: var(--text-primary); }
    a { text-decoration: none; color: inherit; }
    .main-content { padding: 1.25rem 1.5rem; }
    .content-wrapper { width: 100%; max-width: 960px; margin: auto; }
    .page-title { font-size: 2rem; font-weight: 700; padding: 1rem; }
    .market-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; padding: 1rem; }
    .stock-card { display: flex; flex-direction: column; gap: 0.75rem; }
    .stock-image { width: 100%; padding-bottom: 100%; background-size: cover; background-position: center; border-radius: 0.75rem; }
    .stock-info .name { font-weight: 500; }
    .stock-info .price { font-size: 0.875rem; color: var(--text-secondary); }
  </style>

<main class="main-content">
  <div class="content-wrapper">
    <h1 class="page-title">Markets</h1>
    <div class="market-grid">
        <?php foreach ($assetTypes as $asset): 
            $branding = getAssetBranding($asset['id']);
        ?>
          <a href="buy_shares.php?asset_type_id=<?php echo $asset['id']; ?>" class="stock-card">
            <div class="stock-image" style='background-image: url("<?php echo htmlspecialchars($branding['image']); ?>");'></div>
            <div class="stock-info">
              <p class="name"><?php echo htmlspecialchars($branding['name']); ?></p>
              <p class="price">₦<?php echo number_format($asset['price'], 2); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
    </div>
  </div>
</main>

<?php 
    require_once __DIR__ . '/../assets/template/end-template.php';
?>