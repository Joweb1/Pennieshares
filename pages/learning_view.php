<?php
require_once __DIR__ . '/../src/init.php';

$pageTitle = "Article View"; // This would be dynamically set from DB

// Mock data - in a real app, you'd fetch this from a database based on $_GET['id']
$article = [
    'title' => 'Introduction to Stock Market Investing',
    'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?q=80&w=2070&auto=format&fit=crop',
    'content' => '
        <p class="lead">The stock market can seem intimidating, but it\'s more accessible than you think. This guide will walk you through the fundamental concepts to get you started on your investment journey.</p>
        
        <h2>What is a Stock?</h2>
        <p>A stock (also known as equity) represents a share in the ownership of a company. When you buy a company\'s stock, you are buying a small piece of that company. As the company grows and becomes more profitable, the value of your stock can increase. Conversely, if the company performs poorly, the value of your stock can decrease.</p>

        <img src="https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?q=80&w=2070&auto=format&fit=crop" alt="Stock chart on a screen">

        <h2>Why Do Companies Issue Stock?</h2>
        <p>Companies issue stock for one primary reason: to raise capital. This capital can be used for various purposes, such as:</p>
        <ul>
            <li>Expanding operations</li>
            <li>Launching new products</li>
            <li>Paying off debt</li>
            <li>Funding research and development</li>
        </ul>

        <h2>How Are Stock Prices Determined?</h2>
        <p>A stock\'s price is determined by the law of supply and demand. If more people want to buy a stock (demand) than sell it (supply), the price goes up. If more people want to sell a stock than buy it, the price goes down.</p>
        <blockquote>
            Many factors can influence a stock\'s price, including company earnings, industry trends, economic news, and overall market sentiment.
        </blockquote>

        <h2>Key Terms to Know</h2>
        <p>Here are a few essential terms you\'ll encounter frequently:</p>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Definition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Market Capitalization (Market Cap)</strong></td>
                    <td>The total value of a company\'s shares. Calculated by multiplying the share price by the number of outstanding shares.</td>
                </tr>
                <tr>
                    <td><strong>Dividend</strong></td>
                    <td>A distribution of a portion of a company\'s earnings to its shareholders.</td>
                </tr>
                <tr>
                    <td><strong>Bull Market</strong></td>
                    <td>A market in which share prices are rising, encouraging buying.</td>
                </tr>
                <tr>
                    <td><strong>Bear Market</strong></td>
                    <td>A market in which prices are falling, encouraging selling.</td>
                </tr>
            </tbody>
        </table>

        <h2>Conclusion</h2>
        <p>Investing in the stock market is a long-term game. It requires patience, research, and a clear understanding of your financial goals. By starting with these basic concepts, you are building a strong foundation for making informed investment decisions.</p>
    '
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #f4f7fa;
            --bg-secondary: #ffffff;
            --text-primary: #111418;
            --text-secondary: #5a6470;
            --accent-color: #0c7ff2;
            --border-color: #dde3e9;
        }
        html[data-theme="dark"] {
            --bg-primary: #111418;
            --bg-secondary: #1b2127;
            --text-primary: #ffffff;
            --text-secondary: #9cabba;
            --border-color: #3b4754;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
        }
        .view-header {
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--bg-primary);
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1.5rem 2rem;
        }
        .article-header img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            margin-top: 1rem;
        }
        .article-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        .article-content h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-top: 2.5rem;
            margin-bottom: 1rem;
        }
        .article-content p {
            margin-bottom: 1.5rem;
        }
        .article-content .lead {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        .article-content img {
            max-width: 100%;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .article-content ul {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }
        .article-content blockquote {
            margin: 2rem 0;
            padding-left: 1.5rem;
            border-left: 4px solid var(--accent-color);
            font-style: italic;
            color: var(--text-primary);
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        .custom-table th, .custom-table td {
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .custom-table th {
            background-color: var(--bg-secondary);
        }
    </style>
</head>
<body>

    <header class="view-header">
        <a href="/learning" class="back-button">
            <span class="material-icons-outlined">chevron_left</span>
            Back to Learning
        </a>
    </header>

    <main class="container">
        <article>
            <div class="article-header">
                <h1><?= htmlspecialchars($article['title']) ?></h1>
                <img src="<?= htmlspecialchars($article['image']) ?>" alt="">
            </div>
            <div class="article-content">
                <?= $article['content'] ?>
            </div>
        </article>
    </main>

    <script>
        (function() {
            const html = document.documentElement;
            const applyTheme = (theme) => {
                html.setAttribute('data-theme', theme);
            };
            
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme) {
                applyTheme(savedTheme);
            } else if (prefersDark) {
                applyTheme('dark');
            } else {
                applyTheme('light');
            }
        })();
    </script>

</body>
</html>
