<?php
require_once __DIR__ . '/../src/init.php';

$pageTitle = "Learning Center";

// Mock Data for Courses
$courses = [
    [
        'id' => 1,
        'title' => 'Introduction to Stock Market Investing',
        'description' => 'Learn the fundamentals of the stock market, including key terms and concepts.',
        'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?q=80&w=2070&auto=format&fit=crop',
        'category' => 'Beginner'
    ],
    [
        'id' => 2,
        'title' => 'Understanding Technical Analysis & Charts',
        'description' => 'An introduction to reading stock charts and using technical indicators to make decisions.',
        'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop',
        'category' => 'Intermediate'
    ],
    [
        'id' => 3,
        'title' => 'What Are ETFs and Mutual Funds?',
        'description' => 'Discover the difference between ETFs and mutual funds and how to invest in them.',
        'image' => 'https://images.unsplash.com/photo-1621928383614-3a693c95e843?q=80&w=1974&auto=format&fit=crop',
        'category' => 'Beginner'
    ],
    [
        'id' => 4,
        'title' => 'Advanced Options Trading Strategies',
        'description' => 'Explore complex options strategies to maximize your returns and manage risk.',
        'image' => 'https://images.unsplash.com/photo-1639389016139-50c566335206?q=80&w=2070&auto=format&fit=crop',
        'category' => 'Advanced'
    ],
];

// Mock Data for News
$news = [
    [
        'id' => 101,
        'title' => 'Global Markets Rally on Positive Economic Data',
        'source' => 'Reuters',
        'image' => 'https://images.unsplash.com/photo-1579532537598-459ecdaf39cc?q=80&w=2070&auto=format&fit=crop'
    ],
    [
        'id' => 102,
        'title' => 'Tech Stocks Surge as AI Innovation Continues',
        'source' => 'Bloomberg',
        'image' => 'https://images.unsplash.com/photo-1674027444485-cec3da58eef4?q=80&w=1932&auto=format&fit=crop'
    ],
    [
        'id' => 103,
        'title' => 'Federal Reserve Announces Interest Rate Decision',
        'source' => 'Associated Press',
        'image' => 'https://images.unsplash.com/photo-1664575198322-33495a42849a?q=80&w=2070&auto=format&fit=crop'
    ],
    [
        'id' => 104,
        'title' => 'Oil Prices Fluctuate Amid Geopolitical Tensions',
        'source' => 'Wall Street Journal',
        'image' => 'https://images.unsplash.com/photo-1611698282438-a492db75ae24?q=80&w=2070&auto=format&fit=crop'
    ],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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
        .learning-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .learning-header .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .learning-header .brand img {
            width: 32px;
            height: 32px;
        }
        .learning-header .brand h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        .kebab-menu-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        .kebab-menu-button .icon {
            font-size: 24px;
            color: var(--text-primary);
            transition: color 0.2s ease;
        }
        html[data-theme='dark'] .kebab-menu-button .icon {
            color: #ffffff;
        }
        .modal-menu {
            display: none;
            position: fixed;
            top: 60px;
            right: 1.5rem;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 100;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .modal-menu.visible {
            display: block;
        }
        .modal-menu ul {
            list-style: none;
            margin: 0;
            padding: 0.5rem;
        }
        .modal-menu ul li a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .modal-menu ul li a:hover {
            background-color: var(--bg-primary);
        }
        .container {
            padding: 2rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .course-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .course-card-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .course-card-content {
            padding: 1rem;
            flex-grow: 1;
        }
        .course-card-category {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        .course-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .course-card-description {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .news-section {
            margin-top: 4rem;
        }
        .news-carousel {
            display: flex;
            overflow-x: auto;
            gap: 1.5rem;
            padding-bottom: 1.5rem;
            scrollbar-width: none; /* Firefox */
        }
        .news-carousel::-webkit-scrollbar {
            display: none; /* Safari and Chrome */
        }
        .news-card {
            flex: 0 0 320px;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-decoration: none;
            color: var(--text-primary);
        }
        .news-card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .news-card-content {
            padding: 1rem;
        }
        .news-card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .news-card-source {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .theme-toggle-button {
            background-color: var(--bg-tertiary);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            margin: 0 auto; /* Center the button in the list item */
        }
        .theme-toggle-button:hover {
            transform: scale(1.1) rotate(15deg);
        }
        .theme-toggle-button .sun-icon,
        .theme-toggle-button .moon-icon {
            position: absolute;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .theme-toggle-button .sun-icon {
            opacity: 0;
            transform: scale(0);
        }
        .theme-toggle-button .moon-icon {
            opacity: 1;
            transform: scale(1);
        }
        html[data-theme='dark'] .theme-toggle-button .sun-icon {
            opacity: 1;
            transform: scale(1);
        }
        html[data-theme='dark'] .theme-toggle-button .moon-icon {
            opacity: 0;
            transform: scale(0);
        }
        html[data-theme='dark'] .theme-toggle-button .material-icons-outlined {
            color: #ffffff;
        }
    </style>
</head>
<body>

    <header class="learning-header">
        <div class="brand">
            <img src="/assets/images/logo.png" alt="Pennieshare Logo">
            <h1>Pennieshare Learning</h1>
        </div>
        <button class="kebab-menu-button" id="kebab-menu-btn" aria-label="Options menu">
            <span class="material-icons-outlined icon">more_vert</span>
        </button>
        <div class="modal-menu" id="modal-menu">
            <ul>
                <li><a href="/terms">Terms</a></li>
                <li><a href="/logout">Logout</a></li>
                <li>
                    <button id="theme-toggle-learning" class="theme-toggle-button">
                        <span class="material-icons-outlined sun-icon">light_mode</span>
                        <span class="material-icons-outlined moon-icon">dark_mode</span>
                    </button>
                </li>
            </ul>
        </div>
    </header>

    <main class="container">
        <section class="learning-section">
            <h2 class="section-title">Trending Courses</h2>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                    <a href="/learning_view?id=<?= $course['id'] ?>" class="course-card">
                        <img src="<?= htmlspecialchars($course['image']) ?>" alt="" class="course-card-image">
                        <div class="course-card-content">
                            <p class="course-card-category"><?= htmlspecialchars($course['category']) ?></p>
                            <h3 class="course-card-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="course-card-description"><?= htmlspecialchars($course['description']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="news-section">
            <h2 class="section-title">Latest News</h2>
            <div class="news-carousel">
                <?php foreach ($news as $news_item): ?>
                    <a href="/learning_view?id=<?= $news_item['id'] ?>" class="news-card">
                        <img src="<?= htmlspecialchars($news_item['image']) ?>" alt="" class="news-card-image">
                        <div class="news-card-content">
                            <h3 class="news-card-title"><?= htmlspecialchars($news_item['title']) ?></h3>
                            <p class="news-card-source"><?= htmlspecialchars($news_item['source']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kebabMenuBtn = document.getElementById('kebab-menu-btn');
            const modalMenu = document.getElementById('modal-menu');

            kebabMenuBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                modalMenu.classList.toggle('visible');
            });

            document.addEventListener('click', (event) => {
                if (!modalMenu.contains(event.target) && !kebabMenuBtn.contains(event.target)) {
                    modalMenu.classList.remove('visible');
                }
            });

            // --- Theme Toggle Logic ---
            const themeToggle = document.getElementById('theme-toggle-learning');
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

            themeToggle.addEventListener('click', () => {
              const currentTheme = html.getAttribute('data-theme');
              const newTheme = currentTheme === 'light' ? 'dark' : 'light';
              applyTheme(newTheme);
              localStorage.setItem('theme', newTheme);
            });
        });
    </script>

</body>
</html>
