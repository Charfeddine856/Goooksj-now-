<?php

namespace App;

class NicheManager
{
    protected string $tableNiches = 'niches';
    protected string $tableSources = 'niche_sources';

    public static function listNiches(): array
    {
        $pdo = \db_connect();
        $stmt = $pdo->query("SELECT id, slug, name, description FROM niches ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getNicheBySlug(string $slug): ?array
    {
        $pdo = \db_connect();
        $stmt = $pdo->prepare("SELECT id, slug, name, description FROM niches WHERE slug = ? LIMIT 1");
        $stmt->execute([trim($slug)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function createNiche(string $slug, string $name, string $description = ''): int
    {
        $pdo = \db_connect();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO niches (slug, name, description) VALUES (?, ?, ?)");
        $stmt->execute([trim($slug), trim($name), trim($description)]);
        $id = (int)$pdo->lastInsertId();
        if ($id === 0) {
            // fetch existing id
            $existing = self::getNicheBySlug($slug);
            return $existing['id'] ?? 0;
        }
        return $id;
    }

    public static function addSource(int $nicheId, string $type, string $url): bool
    {
        $type = $type === 'web' ? 'web' : 'rss';
        $pdo = \db_connect();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO niche_sources (niche_id, type, url) VALUES (?, ?, ?)");
        return $stmt->execute([$nicheId, $type, trim($url)]);
    }

    public static function getSourcesForNiche(int $nicheId, string $type = ''): array
    {
        $pdo = \db_connect();
        if ($type === '') {
            $stmt = $pdo->prepare("SELECT type, url FROM niche_sources WHERE niche_id = ? ORDER BY id");
            $stmt->execute([$nicheId]);
        } else {
            $stmt = $pdo->prepare("SELECT type, url FROM niche_sources WHERE niche_id = ? AND type = ? ORDER BY id");
            $stmt->execute([$nicheId, $type]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function seedDefaults(): void
    {
        // small set of sample niches and sources
        $defaults = [
            'general' => [
                'name' => 'General Automotive',
                'description' => 'General car news and reviews.',
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
                'web' => [
                    'https://www.autoblog.com/news/',
                    'https://www.caranddriver.com/news/',
                    'https://www.motortrend.com/news/',
                    'https://www.roadandtrack.com/news/',
                    'https://www.autocar.co.uk/car-news',
                    'https://www.topgear.com/car-news',
                    'https://www.carscoops.com/',
                    'https://www.thetruthaboutcars.com/',
                    'https://www.carmagazine.co.uk/car-news/',
                    'https://www.thedrive.com/news'
                ]
            ],
            'ev' => [
                'name' => 'Electric Vehicles',
                'description' => 'EV news, reviews and charging guides.',
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
                ]
            ],
            'motorcycles' => [
                'name' => 'Motorcycles',
                'description' => 'Motorcycle news and reviews.',
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
                ]
            ],

            'auto-mobile' => [
                'name' => 'Auto Mobile',
                'description' => 'Automotive mobile trends, cars and transport updates.',
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
                'name' => 'Cuisine',
                'description' => 'Food, recipes, and restaurant-related content.',
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
                'name' => 'Eran Money',
                'description' => 'Business, money and personal finance content.',
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
            ]
        ];

        foreach ($defaults as $slug => $cfg) {
            $id = self::createNiche($slug, $cfg['name'], $cfg['description']);
            foreach ($cfg['rss'] as $r) {
                if (trim($r) !== '') self::addSource($id, 'rss', $r);
            }
            foreach ($cfg['web'] as $w) {
                if (trim($w) !== '') self::addSource($id, 'web', $w);
            }
        }
    }
}
