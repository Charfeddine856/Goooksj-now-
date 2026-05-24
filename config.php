<?php
session_start();
define('DB_FILE', __DIR__ . '/data/data.db');
define('SITE_TITLE', 'AutoCar Niche');
define('PASSWORD_HASH', '$2y$12$iFCL8jqvoVMbZBcRy3wY..IUJNTqFcIfNAtUZRKiY4pFSspOevkHi'); // admin123

function db_connect() {
    if (!file_exists(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0777, true);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
    return $pdo;
}

// إنشاء الجداول عند أول تشغيل
$pdo = db_connect();
$pdo->exec("CREATE TABLE IF NOT EXISTS articles (
    id INTEGER PRIMARY KEY,
    title TEXT UNIQUE,
    slug TEXT UNIQUE,
    content TEXT,
    image TEXT,
    image2 TEXT,
    excerpt TEXT,
    published_at TEXT,
    category TEXT,
    niche_id INTEGER DEFAULT 1,
    translated_title TEXT,
    translated_content TEXT,
    orig_language TEXT
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS rss_sources (id INTEGER PRIMARY KEY, url TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS web_sources (id INTEGER PRIMARY KEY, url TEXT)");
// Niches support: separate niches and their sources
$pdo->exec("CREATE TABLE IF NOT EXISTS niches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT DEFAULT ''
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS niche_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    niche_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('rss','web')),
    url TEXT NOT NULL,
    UNIQUE(niche_id, type, url),
    FOREIGN KEY(niche_id) REFERENCES niches(id) ON DELETE CASCADE
)");

// Default niches (ensure required niches exist on every install/update)
$defaultNiches = [
    ['general', 'General Automotive', 'General car news and reviews.'],
    ['ev', 'Electric Vehicles', 'EV news, reviews and charging guides.'],
    ['motorcycles', 'Motorcycles', 'Motorcycle news and reviews.'],
    ['auto-mobile', 'Auto Mobile', 'Automotive mobile trends, cars and transport updates.'],
    ['cuisine', 'Cuisine', 'Food, recipes, and restaurant-related content.'],
    ['eran-money', 'Eran Money', 'Business, money and personal finance content.'],
];
$insertNicheStmt = $pdo->prepare("INSERT OR IGNORE INTO niches (slug, name, description) VALUES (?, ?, ?)");
foreach ($defaultNiches as [$slug, $name, $description]) {
    $insertNicheStmt->execute([$slug, $name, $description]);
}

$defaultNicheSources = [
    'general' => [
        'rss' => [
            'https://www.caranddriver.com/rss/all.xml',
            'https://www.autoblog.com/rss.xml',
            'https://www.motortrend.com/feeds/all/',
            'https://www.thetruthaboutcars.com/feed/',
            'https://www.carscoops.com/feed/',
            'https://www.autocar.co.uk/rss',
            'https://www.topgear.com/car-news/rss.xml',
            'https://www.carmagazine.co.uk/rss/',
            'https://www.roadandtrack.com/rss/all.xml',
            'https://www.thedrive.com/rss/all',
            'https://jalopnik.com/rss',
            'https://www.autoevolution.com/rss.xml',
            'https://www.cars.com/news/rss/',
            'https://www.edmunds.com/feeds/rss/reviews.xml',
            'https://www.whichcar.com.au/rss.xml'
        ],
        'web' => ['https://www.autoblog.com/news/'],
    ],
    'ev' => [
        'rss' => [
            'https://insideevs.com/rss',
            'https://electrek.co/feed/',
            'https://cleantechnica.com/tag/electric-vehicles/feed/',
            'https://evannex.com/blogs/news.atom',
            'https://chargedevs.com/feed/',
            'https://www.greencarreports.com/rss',
            'https://www.teslarati.com/feed/',
            'https://www.ev-database.org/rss',
            'https://www.autocar.co.uk/car-news/electric-cars/rss',
            'https://www.carscoops.com/tag/electric-cars/feed/',
            'https://electrive.com/feed/',
            'https://evmagz.com/feed/',
            'https://pluginamerica.org/blog/feed/',
            'https://chargedevs.com/newswire/feed/',
            'https://www.energy.gov/eere/electricvehicles/rss.xml'
        ],
        'web' => [
            'https://insideevs.com/news/',
            'https://electrek.co/',
            'https://www.greencarreports.com/news',
            'https://www.teslarati.com/',
            'https://www.autocar.co.uk/car-news/electric-cars',
            'https://www.carscoops.com/tag/electric-cars/',
            'https://cleantechnica.com/tag/electric-vehicles/',
            'https://chargedevs.com/newswire/',
            'https://electrive.com/',
            'https://evmagz.com/'
        ],
    ],
    'motorcycles' => [
        'rss' => [
            'https://www.motorcyclenews.com/rss/',
            'https://www.visordown.com/rss.xml',
            'https://www.rideapart.com/rss/',
            'https://www.cycleworld.com/arc/outboundfeeds/rss/',
            'https://www.motorcycle.com/feeds/all/',
            'https://www.bennetts.co.uk/bikesocial/rss',
            'https://www.advrider.com/feed/',
            'https://www.revzilla.com/common-tread/rss',
            'https://www.webbikeworld.com/feed/',
            'https://www.motousher.com/feed/',
            'https://www.motorcyclecruiser.com/feed/',
            'https://www.advpulse.com/feed/',
            'https://www.bikeexif.com/feed',
            'https://www.returnofthecaferacers.com/feed/',
            'https://www.totalmotorcycle.com/feed/'
        ],
        'web' => [
            'https://www.motorcyclenews.com/news/',
            'https://www.visordown.com/news',
            'https://www.rideapart.com/news/',
            'https://www.cycleworld.com/motorcycle-news/',
            'https://www.motorcycle.com/news',
            'https://www.bennetts.co.uk/bikesocial/news-and-views',
            'https://www.advrider.com/f/',
            'https://www.revzilla.com/common-tread',
            'https://www.webbikeworld.com/',
            'https://www.advpulse.com/'
        ],
    ],
    'auto-mobile' => [
        'rss' => [
            'https://www.autonews.com/section/rss',
            'https://www.carwow.co.uk/blog/rss.xml',
            'https://www.whatcar.com/news/rss',
            'https://www.driving.co.uk/feed/',
            'https://www.carsguide.com.au/news/rss',
            'https://www.autoexpress.co.uk/rss.xml',
            'https://www.cnet.com/roadshow/news/rss/',
            'https://www.arenaev.com/rss-news-reviews.php3',
            'https://www.techradar.com/rss/news/car-tech',
            'https://www.wired.com/feed/tag/transport/latest/rss',
            'https://www.theverge.com/rss/transportation/index.xml',
            'https://www.engadget.com/transportation/rss.xml',
            'https://www.digitaltrends.com/cars/feed/',
            'https://arstechnica.com/cars/feed/',
            'https://www.zdnet.com/topic/transportation/rss.xml'
        ],
        'web' => [
            'https://www.autonews.com/',
            'https://www.carwow.co.uk/news',
            'https://www.whatcar.com/news',
            'https://www.autoexpress.co.uk/car-news',
            'https://www.carsguide.com.au/car-news',
            'https://www.cnet.com/roadshow/',
            'https://www.digitaltrends.com/cars/',
            'https://www.theverge.com/transportation',
            'https://arstechnica.com/cars/',
            'https://www.techradar.com/news/car-tech'
        ]
    ],
    'cuisine' => [
        'rss' => [
            'https://www.seriouseats.com/rss',
            'https://www.bonappetit.com/feed/rss',
            'https://www.epicurious.com/services/rss/feeds/all',
            'https://www.foodnetwork.com/content/food-com/en/rss/all-content.rss',
            'https://www.simplyrecipes.com/feed/',
            'https://www.delish.com/rss/all.xml',
            'https://www.thekitchn.com/rss',
            'https://minimalistbaker.com/feed/',
            'https://cookieandkate.com/feed/',
            'https://www.smittenkitchen.com/feed/',
            'https://www.foodandwine.com/feed',
            'https://www.allrecipes.com/feed/',
            'https://www.loveandlemons.com/feed/',
            'https://www.feastingathome.com/feed/',
            'https://www.halfbakedharvest.com/feed/'
        ],
        'web' => [
            'https://www.seriouseats.com/',
            'https://www.bonappetit.com/',
            'https://www.epicurious.com/',
            'https://www.foodnetwork.com/',
            'https://www.simplyrecipes.com/',
            'https://www.delish.com/',
            'https://www.thekitchn.com/',
            'https://minimalistbaker.com/',
            'https://cookieandkate.com/',
            'https://www.foodandwine.com/'
        ]
    ],
    'eran-money' => [
        'rss' => [
            'https://www.investopedia.com/feedbuilder/feed/getfeed?feedName=rss_articles',
            'https://www.nerdwallet.com/blog/feed/',
            'https://www.marketwatch.com/rss/topstories',
            'https://www.cnbc.com/id/100003114/device/rss/rss.html',
            'https://www.ft.com/?format=rss',
            'https://www.economist.com/finance-and-economics/rss.xml',
            'https://www.fool.com/feeds/index.aspx',
            'https://www.kiplinger.com/rss.xml',
            'https://www.moneycrashers.com/feed/',
            'https://www.businessinsider.com/rss',
            'https://www.wsj.com/xml/rss/3_7031.xml',
            'https://www.bloomberg.com/feed/podcast/etf-report.xml',
            'https://feeds.a.dj.com/rss/RSSMarketsMain.xml',
            'https://www.forbes.com/money/feed/',
            'https://www.morningstar.com/feeds/rss/articles'
        ],
        'web' => [
            'https://www.investopedia.com/',
            'https://www.nerdwallet.com/',
            'https://www.marketwatch.com/',
            'https://www.cnbc.com/personal-finance/',
            'https://www.ft.com/markets',
            'https://www.economist.com/finance-and-economics',
            'https://www.fool.com/',
            'https://www.kiplinger.com/',
            'https://www.moneycrashers.com/',
            'https://www.forbes.com/money/'
        ]
    ],
];
$getNicheIdStmt = $pdo->prepare("SELECT id FROM niches WHERE slug = ? LIMIT 1");
$insertNicheSourceStmt = $pdo->prepare("INSERT OR IGNORE INTO niche_sources (niche_id, type, url) VALUES (?, ?, ?)");
foreach ($defaultNicheSources as $slug => $groups) {
    $getNicheIdStmt->execute([$slug]);
    $nicheId = (int)$getNicheIdStmt->fetchColumn();
    if ($nicheId <= 0) continue;
    foreach (['rss', 'web'] as $type) {
        foreach ($groups[$type] as $url) {
            $insertNicheSourceStmt->execute([$nicheId, $type, $url]);
        }
    }
}

$nicheAutoTitleDefaults = [
    'general' => [
        'auto_title_fixed_titles' => "Best Cars for Daily Driving in {year}
Top Family SUVs Worth Buying in {year}
Sedan vs SUV: Which One Fits You in {year}
Most Reliable Used Cars Guide for {year}
New Car Buying Checklist for First-Time Buyers",
        'auto_title_brands' => "Toyota
Honda
Ford
Chevrolet
Nissan
BMW
Mercedes-Benz
Audi
Kia
Hyundai",
        'auto_title_models' => "Sedan
SUV
Crossover
Truck
Hybrid
Electric Car
Luxury Sedan
Family SUV
Compact Car
Sports Car",
        'auto_title_modifiers' => "Review
Buying Guide
Specs Breakdown
Comparison
Ownership Cost",
        'auto_title_audiences' => "First-Time Buyers
Family Drivers
Commuters
Performance Enthusiasts
Budget Shoppers",
        'auto_title_angles' => "Real-World Performance
Fuel Economy Insights
Safety and Technology
Maintenance Planning
Value for Money",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
    'ev' => [
        'auto_title_fixed_titles' => "Best Electric SUVs with Long Range in {year}
Home EV Charging Setup Guide for Beginners
EV Battery Health Tips That Actually Work
Fast Charging Comparison: Which EV Wins in {year}
Used EV Buying Checklist for Smart Buyers",
        'auto_title_brands' => "Tesla
BYD
Hyundai
Kia
BMW
Mercedes-EQ
Rivian
Lucid
Volkswagen
Volvo",
        'auto_title_models' => "Electric Sedan
Electric SUV
Long-Range EV
City EV
Premium EV
Charging Setup
Battery Health Plan
Home Charging Guide
Fleet EV
Used EV",
        'auto_title_modifiers' => "Review
Charging Guide
Range Test
Comparison
Ownership Guide",
        'auto_title_audiences' => "EV Beginners
Daily Commuters
Road Trip Drivers
Fleet Managers
Tech-Savvy Buyers",
        'auto_title_angles' => "Charging Speed and Network
Range in Real Conditions
Battery Longevity
Software and Smart Features
Total Ownership Cost",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
    'motorcycles' => [
        'auto_title_fixed_titles' => "Best Beginner Motorcycles to Buy in {year}
Adventure Bike Comparison for Long Rides
Motorcycle Safety Gear Checklist for New Riders
City Commuter Bikes with Best Fuel Economy
Sport Bike vs Naked Bike: Complete {year} Guide",
        'auto_title_brands' => "Honda
Yamaha
Kawasaki
Suzuki
Ducati
BMW Motorrad
KTM
Triumph
Harley-Davidson
Royal Enfield",
        'auto_title_models' => "Sport Bike
Adventure Bike
Naked Bike
Touring Bike
Cruiser
Scooter
Beginner Bike
Commuter Bike
Dual-Sport
Retro Bike",
        'auto_title_modifiers' => "Review
Riding Guide
Comparison
Maintenance Plan
Buying Checklist",
        'auto_title_audiences' => "New Riders
Daily Riders
Weekend Riders
Long-Distance Riders
City Commuters",
        'auto_title_angles' => "Comfort and Ergonomics
Engine and Performance
Fuel Efficiency
Safety Gear Setup
Maintenance and Reliability",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
    'auto-mobile' => [
        'auto_title_fixed_titles' => "Top Car Tech Features You Should Use in {year}
Connected Car Apps That Improve Daily Driving
Smart Mobility Trends Reshaping Transportation
Best In-Car Infotainment Systems Compared
Vehicle Safety Tech Explained for Everyday Drivers",
        'auto_title_brands' => "Toyota
Honda
Hyundai
Kia
Ford
Chevrolet
Nissan
Mazda
BMW
Mercedes",
        'auto_title_models' => "Sedan
SUV
Crossover
Pickup
Hatchback
Hybrid SUV
Electric Sedan",
        'auto_title_modifiers' => "Review
Specs
Price
Comparison
Buying Guide",
        'auto_title_audiences' => "Daily Commuters
Family Drivers
First-Time Buyers
Tech Drivers",
        'auto_title_angles' => "Real-World Fuel Economy
Comfort and Daily Use
Technology and Safety
Maintenance and Ownership Cost",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
    'cuisine' => [
        'auto_title_fixed_titles' => "Easy Weeknight Dinner Plan for Busy Families
Healthy Meal Prep Guide for Beginners
Budget-Friendly Recipes You Can Cook Fast
Best Comfort Food Recipes to Try This Week
Step-by-Step Home Cooking Guide for New Cooks",
        'auto_title_brands' => "Italian
French
Japanese
Indian
Turkish
Mexican
Mediterranean",
        'auto_title_models' => "Home Recipe
Street Food
Healthy Meal
Quick Dinner
Dessert",
        'auto_title_modifiers' => "Recipe
Guide
Tips
Comparison
Beginner Guide",
        'auto_title_audiences' => "Home Cooks
Beginners
Busy Families
Food Lovers",
        'auto_title_angles' => "Step-by-Step Cooking Method
Ingredient Substitutions
Serving Ideas
Budget-Friendly Plan",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
    'eran-money' => [
        'auto_title_fixed_titles' => "Simple Budget Plan to Save More Every Month
Beginner Investing Roadmap for Long-Term Growth
Debt Payoff Strategy That Works in {year}
Side Hustle Ideas to Increase Monthly Income
Personal Finance Checklist for Financial Stability",
        'auto_title_brands' => "Personal Finance
Investing
Freelancing
Small Business
Side Hustle",
        'auto_title_models' => "Savings Plan
Budget Strategy
Income Plan
Investment Plan
Debt Plan",
        'auto_title_modifiers' => "Guide
Checklist
Comparison
Roadmap
Framework",
        'auto_title_audiences' => "Beginners
Young Professionals
Families
Freelancers",
        'auto_title_angles' => "Risk and Return Balance
Monthly Execution Plan
Long-Term Growth Strategy
Cashflow Optimization",
        'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}",
    ],
];
$insertSettingStmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
foreach ($nicheAutoTitleDefaults as $slug => $settings) {
    $insertSettingStmt->execute(['niche.' . $slug . '.auto_title_mode', 'template']);
    $insertSettingStmt->execute(['niche.' . $slug . '.auto_title_min_year_offset', '0']);
    $insertSettingStmt->execute(['niche.' . $slug . '.auto_title_max_year_offset', '1']);
    $insertSettingStmt->execute(['niche.' . $slug . '.auto_title_fixed_titles', '']);
    foreach ($settings as $k => $v) {
        $insertSettingStmt->execute(['niche.' . $slug . '.' . $k, $v]);
    }
}

// Tags system for better SEO and filtering
$pdo->exec("CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT DEFAULT '',
    post_count INTEGER DEFAULT 0
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS article_tags (
    article_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY(article_id, tag_id),
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE
)");

// Article ratings and engagement metrics
$pdo->exec("CREATE TABLE IF NOT EXISTS article_stats (
    article_id INTEGER PRIMARY KEY,
    views INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    avg_rating REAL DEFAULT 0,
    rating_count INTEGER DEFAULT 0,
    shares INTEGER DEFAULT 0,
    updated_at INTEGER DEFAULT 0,
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS article_ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    article_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    visitor_hash TEXT NOT NULL,
    created_at INTEGER DEFAULT 0,
    UNIQUE(article_id, visitor_hash),
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_article_tags_tag ON article_tags(tag_id)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_article_stats_views ON article_stats(views DESC)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_article_stats_avg_rating ON article_stats(avg_rating DESC)");
$pdo->exec("CREATE TABLE IF NOT EXISTS url_cache (
    url TEXT PRIMARY KEY,
    body TEXT,
    status_code INTEGER DEFAULT 0,
    fetched_at INTEGER DEFAULT 0,
    ttl_seconds INTEGER DEFAULT 900,
    fail_count INTEGER DEFAULT 0,
    blocked_until INTEGER DEFAULT 0
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS scrape_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow TEXT NOT NULL,
    source_url TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    attempts INTEGER DEFAULT 0,
    locked_until INTEGER DEFAULT 0,
    available_at INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0,
    updated_at INTEGER DEFAULT 0
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_scrape_queue_workflow_status_available ON scrape_queue(workflow, status, available_at, locked_until)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_scrape_queue_source_url ON scrape_queue(source_url)");
$pdo->exec("CREATE TABLE IF NOT EXISTS article_exports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    article_id INTEGER NOT NULL,
    slug TEXT NOT NULL,
    html_path TEXT NOT NULL,
    json_path TEXT NOT NULL,
    created_at TEXT NOT NULL,
    UNIQUE(article_id),
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS page_visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_key TEXT NOT NULL,
    page_label TEXT NOT NULL,
    visitor_hash TEXT NOT NULL,
    views INTEGER NOT NULL DEFAULT 1,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    UNIQUE(page_key, visitor_hash)
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_page_visits_page_key ON page_visits(page_key)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_page_visits_updated_at ON page_visits(updated_at)");
$pdo->exec("DELETE FROM rss_sources WHERE id NOT IN (SELECT MIN(id) FROM rss_sources GROUP BY url)");
$pdo->exec("DELETE FROM web_sources WHERE id NOT IN (SELECT MIN(id) FROM web_sources GROUP BY url)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_rss_sources_url ON rss_sources(url)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_web_sources_url ON web_sources(url)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_category_published_id ON articles(category, published_at, id)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_published_id ON articles(published_at, id)");

// Migration: add niche_id column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE articles ADD COLUMN niche_id INTEGER DEFAULT 1");
} catch (PDOException $e) {
    // Column already exists or migration not needed
}
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_niche_id ON articles(niche_id)");

// Migration: add secondary image and translation columns
try {
    $pdo->exec("ALTER TABLE articles ADD COLUMN image2 TEXT");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE articles ADD COLUMN translated_title TEXT");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE articles ADD COLUMN translated_content TEXT");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE articles ADD COLUMN orig_language TEXT");
} catch (PDOException $e) {}

// إعدادات افتراضية
$defaults = [
    'site_title' => SITE_TITLE,
    'min_words' => '3000',
    'auto_publish' => '1',
    'daily_limit' => '5',
    'auto_ai_enabled' => '1',
    'auto_publish_interval_minutes' => '180',
    'auto_publish_interval_seconds' => '10800',
    'auto_publish_last_run_at' => '1970-01-01 00:00:00',
    'content_workflow' => 'rss',
    'url_cache_ttl_seconds' => '900',
    'fetch_timeout_seconds' => '12',
    'fetch_retry_attempts' => '3',
    'fetch_retry_backoff_ms' => '350',
    'fetch_user_agent' => 'Mozilla/5.0 (compatible; VitoBot/1.0; +https://example.com/bot)',
    'workflow_batch_size' => '8',
    'queue_retry_delay_seconds' => '60',
    'queue_max_attempts' => '3',
    'queue_source_cooldown_seconds' => '180',
    'visit_excluded_ips' => '',
    // translation settings
    'auto_translate_enabled' => '0',
    'auto_translate_target_language' => '',
    'auto_title_mode' => 'template',
    'auto_title_min_year_offset' => '0',
    'auto_title_max_year_offset' => '1',
    'auto_title_brands' => "Toyota\nBMW\nMercedes\nAudi\nPorsche\nTesla\nHyundai\nKia\nFord\nNissan\nVolvo\nLexus",
    'auto_title_models' => "SUV\nSedan\nCoupe\nEV Crossover\nHybrid SUV\nPerformance Hatchback\nElectric Sedan\nLuxury Wagon\nPremium Crossover",
    'auto_title_modifiers' => "Review\nSpecs\nPrice\nComparison\nBuying Guide\nOwnership Cost",
    'auto_title_audiences' => "Smart Buyers\nFirst-Time Premium Buyers\nTech-Focused Drivers\nFamily Buyers",
    'auto_title_angles' => "Full Review and Buyer Guide\nLong-Term Ownership Analysis\nReal-World Efficiency Test\nDaily Driving Impression\nSmart Technology Deep Dive\nComparison and Value Breakdown\nReliability, Resale, and Total Cost Breakdown",
    'auto_title_templates' => "{year} {brand} {model} {modifier}: {angle} for {audience}\n{year} {brand} {model} {modifier} — {angle} ({audience})\n{year} {brand} {model}: {modifier} + {angle}",
    'auto_title_fixed_titles' => '',
    'seo_home_title' => SITE_TITLE,
    'seo_home_description' => 'Automotive reviews, guides, and practical car ownership tips.',
    'seo_article_title_suffix' => SITE_TITLE,
    'seo_default_robots' => 'index,follow',
    'seo_default_og_image' => '',
    'seo_twitter_site' => '',
    'seo_image_alt_suffix' => ' - car image',
    'seo_image_title_suffix' => ' - photo',
    'seo_auto_link_rules' => '',
    'seo_auto_link_auto_internal' => '1',
    'seo_auto_link_max_per_article' => '3',
    'google_analytics_id' => '',
    'google_tag_manager_id' => '',
    'google_site_verification' => '',
    'bing_site_verification' => '',
    'meta_pixel_id' => '',
    'custom_head_scripts' => '',
    'custom_body_scripts' => '',
    'ads_enabled' => '0',
    'ads_injection_mode' => 'smart',
    'ads_paragraph_interval' => '4',
    'ads_max_units_per_article' => '2',
    'ads_min_words_before_first_injection' => '180',
    'ads_min_article_words' => '420',
    'ads_blocked_title_keywords' => '',
    'ads_label_text' => 'Sponsored',
    'ads_html_code' => '<div class="ad-unit-inner">Place your ad code here</div>',
    'ads_txt' => '',
];
foreach ($defaults as $k => $v) {
    $pdo->prepare("INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)")->execute([$k, $v]);
}

// one-time migration for installs created before the scalable pipeline defaults
$migrationKey = 'pipeline_defaults_v2_applied';
$migrationStmt = $pdo->prepare("SELECT value FROM settings WHERE key = ? LIMIT 1");
$migrationStmt->execute([$migrationKey]);
$migrationApplied = $migrationStmt->fetchColumn();
if ($migrationApplied === false) {
    $currentMinWordsStmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'min_words' LIMIT 1");
    $currentMinWordsStmt->execute();
    $currentMinWords = (int)$currentMinWordsStmt->fetchColumn();
    if ($currentMinWords <= 1200) {
        $pdo->prepare("UPDATE settings SET value = '3000' WHERE key = 'min_words'")->execute();
    }

    $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('fetch_timeout_seconds', '12')")->execute();
    $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('fetch_user_agent', 'Mozilla/5.0 (compatible; VitoBot/1.0; +https://example.com/bot)')")->execute();
    $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value")
        ->execute([$migrationKey, date('Y-m-d H:i:s')]);
}

// Default tags for better content organization and SEO
$default_tags = [
    ['Review', 'content insights for car buyers'],
    ['Maintenance', 'keep your vehicle running smoothly'],
    ['Safety', 'driving safety and crash prevention'],
    ['Buying Guide', 'everything to know before purchasing'],
    ['Performance', 'engine power and driving dynamics'],
    ['Electric Vehicles', 'EV charging, batteries, and efficiency'],
    ['SUV', 'sport utility vehicles and crossovers'],
    ['Sedan', 'luxury and practical four-door cars'],
    ['Comparison', 'head-to-head model analysis'],
    ['Technology', 'infotainment and automotive tech'],
];
foreach ($default_tags as [$name, $description]) {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $pdo->prepare("INSERT OR IGNORE INTO tags (name, slug, description) VALUES (?, ?, ?)")
        ->execute([$name, $slug, $description]);
}

// مصادر RSS افتراضية
$rss_defaults = [
    'https://www.caranddriver.com/rss/all.xml',
    'https://www.motor1.com/rss/news/all/',
    'https://www.autoblog.com/rss.xml'
];
foreach ($rss_defaults as $url) {
    $pdo->prepare("INSERT OR IGNORE INTO rss_sources (url) VALUES (?)")->execute([$url]);
}


$web_defaults = [
    'https://www.caranddriver.com/news/',
    'https://www.motor1.com/news/',
    'https://www.autoblog.com/news/'
];
foreach ($web_defaults as $url) {
    $pdo->prepare("INSERT OR IGNORE INTO web_sources (url) VALUES (?)")->execute([$url]);
}
?>
