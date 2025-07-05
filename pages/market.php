<?php 
    require_once __DIR__ . '/../src/functions.php';
    check_auth();
    $assetTypes = getAssetTypes($pdo);
    require_once __DIR__ . '/../assets/template/intro-template.php';
?>

  <style>
    /* CSS Variables for Theming */
    :root {
      --bg-primary: #f9fafb;
      --bg-secondary: #eaedf1;
      --bg-tertiary: #ffffff;
      --text-primary: #101518;
      --text-secondary: #5c748a;
      --border-color: #eaedf1;
      --border-color-active: #d4dce2;
      --accent-color: #007aff;
      --shadow-color: rgba(0, 0, 0, 0.1);
      --font-family: 'Inter', 'Noto Sans', sans-serif;
    }

    body.dark-theme {
      --bg-primary: #0d141c;
      --bg-secondary: #0d141c;
      --bg-tertiary: #2a2a2a;
      --text-primary: #e0e0e0;
      --text-secondary: #a0a0a0;
      --border-color: #2c2c2c;
      --border-color-active: #444444;
      --accent-color: #0a84ff;
      --shadow-color: rgba(255, 255, 255, 0.1);
    }

    /* General Resets and Body Styles */
    *, *::before, *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: var(--font-family);
      background-color: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      font-size: 16px;
      transition: background-color 0.3s ease, color 0.3s ease;
      overflow-x: hidden;
    }

    a {
      text-decoration: none;
      color: inherit;
      transition: color 0.2s ease;
    }

    button {
      border: none;
      background: none;
      cursor: pointer;
      padding: 0;
      color: inherit;
    }

    /* Main Layout */
    .page-container {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .main-content {
      flex-grow: 1;
      display: flex;
      justify-content: center;
      padding: 1.25rem 1.5rem; /* py-5 px-6 */
    }

    .content-wrapper {
      width: 100%;
      max-width: 960px;
    }

    /* Content Area */
    .page-title {
      font-size: 2rem;
      font-weight: 700;
      padding: 1rem;
    }

    .tabs {
      display: flex;
      border-bottom: 1px solid var(--border-color-active);
      padding: 0 1rem;
      gap: 2rem;
    }

    .tab-link {
      padding: 1rem 0;
      border-bottom: 3px solid transparent;
      font-size: 0.875rem;
      font-weight: 700;
      color: var(--text-secondary);
      transition: color 0.3s ease, border-color 0.3s ease;
    }

    .tab-link.active {
      color: var(--text-primary);
      border-bottom-color: var(--accent-color);
    }
    
    .tab-link:hover {
        color: var(--text-primary);
    }

    .filters {
      display: flex;
      gap: 0.75rem;
      padding: 1.25rem 1rem;
      overflow-x: auto;
    }

    .filter-tag {
      padding: 0.5rem 1rem;
      background-color: var(--bg-secondary);
      border-radius: 0.75rem;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .filter-tag.active {
      background-color: var(--accent-color);
      color: white;
    }

    /* Market Grid */
    .market-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1.5rem;
      padding: 1rem;
    }

    .stock-card {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .stock-image {
      width: 100%;
      padding-bottom: 100%; /* Aspect ratio 1:1 */
      background-size: cover;
      background-position: center;
      border-radius: 0.75rem;
    }

    .stock-info .name {
      font-weight: 500;
    }
    .stock-info .price {
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem;
      gap: 0.5rem;
    }

    .pagination a {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 50%;
      font-size: 0.875rem;
      transition: background-color 0.3s ease;
    }

    .pagination a.page-number:hover {
        background-color: var(--bg-secondary);
    }
    
    .pagination a.page-number.active {
        background-color: var(--accent-color);
        color: white;
        font-weight: 700;
    }
    
    .pagination-arrow {
        color: var(--text-primary);
    }
    
    .pagination-arrow svg {
        width: 18px;
        height: 18px;
    }

    /* Responsive Styles for Mobile */
    @media (max-width: 1024px) {

        .main-content {
            padding: 1.25rem 1rem;
        }

        .page-title {
            font-size: 1.75rem;
            padding-left: 0.5rem;
        }

        .tabs, .filters {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .market-grid {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            padding: 0.5rem;
        }
    }
  </style>

  <div class="page-container">
    <main class="main-content">
      <div class="content-wrapper">
        <h1 class="page-title">Markets</h1>
        <div class="tabs" id="market-tabs">
          <a class="tab-link active" href="#">Shares</a>
        </div>
        <div class="filters" id="category-filters">
          <div class="filter-tag active" data-category="All">All</div>
          <?php
            $categories = [];
            foreach ($assetTypes as $asset) {
                $category = $asset['category'] ?? 'General';
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
            foreach ($categories as $category) {
                echo '<div class="filter-tag" data-category="' . $category . '">' . $category . '</div>';
            }
          ?>
        </div>
        <div class="market-grid" id="market-grid">
        <?php foreach ($assetTypes as $asset): ?>
          <a href="buy_shares?asset_type_id=<?php echo $asset['id']; ?>" class="stock-card" data-category="<?php echo $asset['category'] ?? 'General'; ?>">
            <div class="stock-image" style='background-image: url("<?php echo htmlspecialchars($asset['image_link']); ?>");'></div>
            <div class="stock-info">
              <p class="name"><?php echo htmlspecialchars($asset['name']); ?></p>
              <p class="category"><?php echo $asset['category'] ?? 'General'; ?></p>
              <p class="price">SV<?php echo number_format($asset['price'], 2); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
        </div>
    <div class="pagination" id="pagination-controls">
      <a href="#" class="pagination-arrow" aria-label="Previous page">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M165.66,202.34a8,8,0,0,1-11.32,11.32l-80-80a8,8,0,0,1,0-11.32l80-80a8,8,0,0,1,11.32,11.32L91.31,128Z"></path></svg>
      </a>
      <a class="page-number active" href="#">1</a>
      <a href="#" class="pagination-arrow" aria-label="Next page">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M181.66,133.66l-80,80a8,8,0,0,1-11.32-11.32L164.69,128,90.34,53.66a8,8,0,0,1,11.32-11.32l80,80A8,8,0,0,1,181.66,133.66Z"></path></svg>
      </a>
    </div>
  </div>
</main>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const marketTabsContainer = document.getElementById('market-tabs');
      const categoryFiltersContainer = document.getElementById('category-filters');
      const marketGrid = document.getElementById('market-grid');
      const stockCards = marketGrid.querySelectorAll('.stock-card');

      // --- Active State Handler for Clickable Groups ---
      const handleActiveState = (container, selector, activeClass) => {
        if (!container) return;
        
        container.addEventListener('click', (e) => {
          // Find the parent element that matches the selector
          const targetElement = e.target.closest(selector);
          if (!targetElement) return;

          // Remove active class from all siblings
          container.querySelectorAll(selector).forEach(el => {
            el.classList.remove(activeClass);
          });
          
          // Add active class to the clicked element
          targetElement.classList.add(activeClass);
        });
      };

      handleActiveState(marketTabsContainer, '.tab-link', 'active');
      handleActiveState(categoryFiltersContainer, '.filter-tag', 'active');

      // --- Category Filtering ---
      categoryFiltersContainer.addEventListener('click', function(e) {
        const filterTag = e.target.closest('.filter-tag');
        if (!filterTag) return;

        const selectedCategory = filterTag.dataset.category;

        stockCards.forEach(card => {
          const cardCategory = card.dataset.category;
          if (selectedCategory === 'All' || cardCategory === selectedCategory) {
            card.style.display = ''; // Show the card
          } else {
            card.style.display = 'none'; // Hide the card
          }
        });
      });
    });
  </script>

<?php 
    require_once __DIR__ . '/../assets/template/end-template.php';
?>