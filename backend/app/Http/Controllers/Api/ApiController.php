<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StatusCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

class ApiController extends Controller
{
    public function index()
    {
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com');
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>API and Preview Links</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                }
                h1, h2 {
                    color: #4a5568;
                }
                h1 {
                    border-bottom: 2px solid #edf2f7;
                    padding-bottom: 10px;
                }
                .section {
                    background-color: #f8fafc;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .link {
                    display: block;
                    margin: 10px 0;
                    padding: 8px 16px;
                    background-color: #e2e8f0;
                    border-radius: 4px;
                    text-decoration: none;
                    color: #4a5568;
                }
                .link:hover {
                    background-color: #cbd5e0;
                }
                .updated-link {
                    background-color: #c6f6d5;
                    font-weight: bold;
                    color: #2c7a7b;
                }
                .updated-link:hover {
                    background-color: #9ae6b4;
                }
                .description {
                    margin-top: 5px;
                    font-size: 14px;
                    color: #718096;
                }
            </style>
        </head>
        <body>
            <h1>API and Preview Links</h1>
            
            <div class="section">
                <h2>Updated Site Links</h2>
                <a href="' . $previewUrl . '" class="link updated-link" target="_blank">
                    Updated Site Preview
                    <div class="description">Direct link to your updated site with the cursor fix</div>
                </a>
                
                <a href="/api/links" class="link updated-link" target="_blank">
                    /api/links
                    <div class="description">A page showing all available preview links and details</div>
                </a>
            </div>
            
            <div class="section">
                <h2>API Endpoints</h2>
                <a href="/api/demo" class="link" target="_blank">
                    /api/demo
                    <div class="description">Interactive demo of the API functionality</div>
                </a>
                
                <a href="/api/preview" class="link" target="_blank">
                    /api/preview
                    <div class="description">Page that redirects to your updated site</div>
                </a>
                
                <a href="/api/new-preview" class="link" target="_blank">
                    /api/new-preview
                    <div class="description">Direct redirect to your updated site</div>
                </a>
                
                <a href="/api/status" class="link" target="_blank">
                    /api/status
                    <div class="description">API endpoint for retrieving status checks</div>
                </a>
            </div>
        </body>
        </html>';

        return response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function createStatusCheck(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_name' => 'required|string|max:255'
            ]);

            $statusCheck = StatusCheck::create([
                'client_name' => $validated['client_name']
            ]);

            return response()->json([
                'id' => $statusCheck->id,
                'client_name' => $statusCheck->client_name,
                'timestamp' => $statusCheck->timestamp->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating status check: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create status check'], 500);
        }
    }

    public function getStatusChecks()
    {
        try {
            $statusChecks = StatusCheck::orderBy('timestamp', 'desc')->take(1000)->get();
            
            return response()->json($statusChecks->map(function ($check) {
                return [
                    'id' => $check->id,
                    'client_name' => $check->client_name,
                    'timestamp' => $check->timestamp->toISOString()
                ];
            }));
        } catch (\Exception $e) {
            Log::error('Error retrieving status checks: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve status checks'], 500);
        }
    }

    public function getStatusCheck($statusId)
    {
        try {
            $status = StatusCheck::where('id', $statusId)->first();
            
            if (!$status) {
                return response()->json(['error' => 'Status not found'], 404);
            }

            return response()->json([
                'id' => $status->id,
                'client_name' => $status->client_name,
                'timestamp' => $status->timestamp->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving status check: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve status check'], 500);
        }
    }

    public function fetchNewsImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'url' => 'required|url'
            ]);

            $url = $validated['url'];
            $result = $this->extractImageFromUrl($url);

            return response()->json([
                'url' => $url,
                'image_url' => $result['image_url'] ?? null,
                'title' => $result['title'] ?? null,
                'description' => $result['description'] ?? null,
                'error' => $result['error'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching news image: ' . $e->getMessage());
            return response()->json([
                'url' => $request->input('url'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchMultipleNewsImages(Request $request)
    {
        try {
            $urls = $request->json()->all();
            
            if (!is_array($urls)) {
                return response()->json(['error' => 'Invalid input format'], 400);
            }

            $results = [];
            foreach ($urls as $url) {
                $result = $this->extractImageFromUrl($url);
                $results[] = [
                    'url' => $url,
                    'image_url' => $result['image_url'] ?? null,
                    'title' => $result['title'] ?? null,
                    'description' => $result['description'] ?? null,
                    'error' => $result['error'] ?? null
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error fetching multiple news images: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function extractImageFromUrl($url)
    {
        try {
            // Special handling for agora.md bakery article
            if (strpos($url, 'agora.md') !== false && strpos($url, 'cel-mai-mare-producator-din-industria-de-panificatie') !== false) {
                return [
                    'image_url' => 'https://images.pexels.com/photos/6291408/pexels-photo-6291408.jpeg',
                    'title' => 'Cel mai mare producător din industria de panificație din Moldova înregistrează un profit record',
                    'description' => 'Cel mai mare producător din industria de panificație din Moldova înregistrează un profit record pentru 2024'
                ];
            }

            // Special handling for MDED.gov.md
            if (strpos($url, 'mded.gov.md') !== false && strpos($url, 'ajutor-de-stat-regional-pentru-investitii') !== false) {
                return [
                    'image_url' => 'https://images.unsplash.com/photo-1551295022-de5522c94e08',
                    'title' => 'Schema de ajutor de stat regional pentru investiții',
                    'description' => 'Citește despre programul de ajutor de stat pentru investiții regionale pe site-ul oficial al MDED.'
                ];
            }

            $client = new Client(['timeout' => 10]);
            $response = $client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                return ['error' => 'HTTP ' . $response->getStatusCode()];
            }

            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $imageUrl = null;
            $title = null;
            $description = null;

            // Try to get Open Graph image
            $ogImage = $crawler->filter('meta[property="og:image"]')->first();
            if ($ogImage->count() > 0) {
                $imageUrl = $ogImage->attr('content');
                
                // Make sure it's an absolute URL
                if (strpos($imageUrl, '//') === 0) {
                    $imageUrl = 'https:' . $imageUrl;
                } elseif (strpos($imageUrl, '/') === 0) {
                    $parsed = parse_url($url);
                    $imageUrl = $parsed['scheme'] . '://' . $parsed['host'] . $imageUrl;
                }
            } else {
                // Fallback: look for the first large image
                $images = $crawler->filter('img[src]');
                foreach ($images as $img) {
                    $src = $img->getAttribute('src');
                    if ($src && !preg_match('/logo|icon|avatar|ad/i', $src)) {
                        if (strpos($src, '//') === 0) {
                            $src = 'https:' . $src;
                        } elseif (strpos($src, '/') === 0) {
                            $parsed = parse_url($url);
                            $src = $parsed['scheme'] . '://' . $parsed['host'] . $src;
                        }
                        $imageUrl = $src;
                        break;
                    }
                }
            }

            // Get title
            $ogTitle = $crawler->filter('meta[property="og:title"]')->first();
            if ($ogTitle->count() > 0) {
                $title = $ogTitle->attr('content');
            } else {
                $titleTag = $crawler->filter('title')->first();
                if ($titleTag->count() > 0) {
                    $title = trim($titleTag->text());
                }
            }

            // Get description
            $ogDesc = $crawler->filter('meta[property="og:description"]')->first();
            if ($ogDesc->count() > 0) {
                $description = $ogDesc->attr('content');
            } else {
                $metaDesc = $crawler->filter('meta[name="description"]')->first();
                if ($metaDesc->count() > 0) {
                    $description = $metaDesc->attr('content');
                }
            }

            return [
                'image_url' => $imageUrl,
                'title' => $title,
                'description' => $description
            ];

        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadIndustriaBauturilor()
    {
        // Try multiple possible locations
        $possiblePaths = [
            '/app/frontend/public/industria-bauturilor.pdf',
            '/app/frontend/src/documents/Industria Băuturilor .pdf', // Note the spaces
        ];
        
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                return response()->download($filePath, 'industria-bauturilor.pdf', [
                    'Content-Type' => 'application/pdf'
                ]);
            }
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadOfertaLactate()
    {
        $possiblePaths = [
            '/app/frontend/public/oferta-lactate-ro.pdf',
            '/app/frontend/src/documents/Ofertă Lactate RO .pdf', // Note the spaces
        ];
        
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                return response()->download($filePath, 'oferta-lactate-ro.pdf', [
                    'Content-Type' => 'application/pdf'
                ]);
            }
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadOfertaCarne()
    {
        $possiblePaths = [
            '/app/frontend/public/oferta-carne-si-oua-ro.pdf',
            '/app/frontend/src/documents/Ofertă Carne și Ouă RO .pdf', // Note the spaces
        ];
        
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                return response()->download($filePath, 'oferta-carne-si-oua-ro.pdf', [
                    'Content-Type' => 'application/pdf'
                ]);
            }
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist1()
    {
        $filePath = public_path('../frontend/src/documents/minist1.pdf');
        
        if (file_exists($filePath)) {
            return response()->download($filePath, 'minist1.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist2()
    {
        $filePath = public_path('../frontend/src/documents/minist2.pdf');
        
        if (file_exists($filePath)) {
            return response()->download($filePath, 'minist2.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist3()
    {
        $filePath = public_path('../frontend/src/documents/minist3.docx');
        
        if (file_exists($filePath)) {
            return response()->download($filePath, 'minist3.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist4()
    {
        $filePath = public_path('../frontend/src/documents/minist4.pdf');
        
        if (file_exists($filePath)) {
            return response()->download($filePath, 'minist4.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function getNewsTicker()
    {
        try {
            $latestNews = [
                [
                    'id' => 9,
                    'title' => 'Tranziția Verde a Republicii Moldova: Motor al Integrării Europene și Dezvoltării Durabile',
                    'date' => '24 iunie 2025',
                    'url' => '#',
                    'type' => 'news',
                    'hasImages' => true,
                    'category' => 'sustainability',
                    'author' => 'ANIPM',
                    'readTime' => '10 min',
                    'summary' => 'Pe 24 iunie, la Maib Park, a avut loc conferința națională "Tranziția verde a Republicii Moldova: Un motor al integrării europene și al creșterii durabile".',
                    'fullContent' => 'Pe 24 iunie, la Maib Park, a avut loc conferința națională "Tranziția verde a Republicii Moldova: Un motor al integrării europene și al creșterii durabile". Evenimentul a fost organizat de Uniunea Europeană și Programul Națiunilor Unite pentru Dezvoltare (PNUD), în parteneriat cu Ministerul Dezvoltării Economice și Digitalizării, în cadrul proiectului "Facilitarea unei tranziții verzi incluzive în Republica Moldova".\n\nTemele principale ale conferinței:\n\nEconomia verde și circulară: Au fost discutate subiecte precum principiile ESG (mediu, sociale, guvernanță), sistemele de management și audit ecologic.\n\nParticipare diversă: Conferința a reunit peste 100 de participanți, printre care oficiali guvernamentali de rang înalt, antreprenori, parteneri de dezvoltare și reprezentanți ai societății civile.\n\nMesaj esențial: Dezvoltarea unei economii mai verzi este esențială nu doar pentru sustenabilitate, ci și pentru consolidarea prosperității și rezilienței pe termen lung a Republicii Moldova.\n\nDeclarații oficiale:\n\n"Agenda verde stă la baza eforturilor Republicii Moldova către un viitor rezilient și incluziv. Prin promovarea principiilor ESG, susținerea practicilor economiei circulare și consolidarea responsabilității extinse a producătorului, Moldova pune bazele unei economii durabile care aduce beneficii tuturor oamenilor. Împreună cu partenerii noștri, vom continua să sprijinim tranziția verde a țării, asigurând că protecția mediului merge în tandem cu creșterea economică și echitatea socială." — Daniela Gasparikova, Reprezentantă rezidentă PNUD în Republica Moldova.\n\nDespre proiect:\n\nProiectul "Facilitarea unei tranziții verzi incluzive în Republica Moldova" este finanțat de Uniunea Europeană și implementat de PNUD. Printre prioritățile acestuia se numără:\n\nImplementarea legislației privind responsabilitatea extinsă a producătorului.\nConsolidarea capacităților instituționale ale autorităților relevante.\nAvansarea agendei de tranziție verde.\n\nConferința, desfășurată în contextul Săptămânii Europene a Energiei Durabile (EUSEW) și al Săptămânii Antreprenoriatului, subliniază angajamentul Republicii Moldova de a-și alinia dezvoltarea economică la principiile sustenabilității, asigurând un viitor mai verde și mai prosper pentru cetățenii săi.',
                    'images' => [
                        '/images/trans1.jpg',
                        '/images/trans2.jpg',
                        '/images/trans3.jpg',
                        '/images/trans4.jpg',
                        '/images/trans5.jpg',
                        '/images/trans6.jpg',
                        '/images/trans7.jpg'
                    ]
                ],
                [
                    'id' => 8,
                    'title' => 'Ministerul Agriculturii vine cu o reacție în urma demersului scris de ANIPM',
                    'date' => '5 aprilie 2025',
                    'url' => '#',
                    'type' => 'news',
                    'hasDocuments' => true,
                    'category' => 'official'
                ],
                [
                    'id' => 7,
                    'title' => 'Schema de ajutor de stat regional pentru investiții',
                    'date' => '30 martie 2025',
                    'url' => 'https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/',
                    'type' => 'news'
                ],
                [
                    'id' => 6,
                    'title' => 'R. Moldova exportă mai multă făină, dar la un preț mult mai mic',
                    'date' => '29 martie 2025',
                    'url' => 'https://agroexpert.md/rom/novosti/r-moldova-exporta-mai-multa-faina-dar-la-un-pret-mult-mai-mic',
                    'type' => 'news'
                ],
                [
                    'id' => 1,
                    'title' => 'Tot mai mulți pasionați de panificație descoperă farmecul pâinii cu maia',
                    'date' => '15 ianuarie 2025',
                    'url' => 'https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/',
                    'type' => 'news'
                ],
                [
                    'id' => 2,
                    'title' => 'În Transnistria se vor scumpi făina și pâinea',
                    'date' => '12 ianuarie 2025',
                    'url' => 'https://stiri.md/article/economic/in-transnistria-se-vor-scumpi-faina-si-painea/',
                    'type' => 'news'
                ],
                [
                    'id' => 3,
                    'title' => 'Ion Perju: Prețurile s-au majorat nejustificat, grâu în țară este',
                    'date' => '10 ianuarie 2025',
                    'url' => 'https://stiri.md/article/economic/ion-perju-preturile-s-au-majorat-nejustificat-grau-in-tara-este/',
                    'type' => 'news'
                ],
                [
                    'id' => 4,
                    'title' => 'Cel mai mare producător din industria de panificație din Moldova înregistrează un profit record',
                    'date' => '21 februarie 2025',
                    'url' => 'https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record',
                    'type' => 'news'
                ],
                [
                    'id' => 5,
                    'title' => 'De ce brutarii din Chișinău coc din ce în ce mai puțină pâine',
                    'date' => '15 decembrie 2024',
                    'url' => 'https://moldova.europalibera.org/a/27188328.html',
                    'type' => 'news'
                ]
            ];

            $events = [
                [
                    'id' => 106,
                    'title' => 'ENERGY TRANSITION AGENDA - 4th edition / AGENDA TRANZIȚIEI ENERGETICE - ediția a 4-a',
                    'title_en' => 'ENERGY TRANSITION AGENDA - 4th edition',
                    'title_ro' => 'AGENDA TRANZIȚIEI ENERGETICE - ediția a 4-a',
                    'date' => '15 Iunie 2025',
                    'url' => 'https://eba-md.translate.goog/eng/news/energy-transition-agenda---4th-edition?_x_tr_sl=en&_x_tr_tl=ro&_x_tr_hl=ro&_x_tr_pto=sc',
                    'type' => 'event',
                    'hasVideo' => true,
                    'image' => '/images/eta2025.jpg',
                    'video' => '/images/energy2025.mp4',
                    'description' => 'Asociația Businessului European (EBA Moldova) are deosebita onoare și plăcere să vă lanseze invitația la evenimentul Energy Transition Agenda 2025.',
                    'bilingual' => true
                ],
                [
                    'id' => 102,
                    'title' => 'Expoziția IPAC IMA – 27-30 mai 2025, Milano, Italia',
                    'date' => '27 Mai 2025',
                    'url' => 'https://www.ipackima.com/about/la-fiera.html',
                    'type' => 'event'
                ],
                [
                    'id' => 103,
                    'title' => 'UNIDO și UE lansează un program de consolidare a capacităților pentru întreprinderile mici și mijlocii',
                    'date' => '20 Mai 2025',
                    'url' => 'https://euneighbourseast.eu/ro/opportunities/unido-si-ue-lanseaza-un-program-de-consolidare-a-capacitatilor-pentru-imm-uri-operatori-din-sectorul-alimentar-si-fermieri/',
                    'type' => 'event'
                ],
                [
                    'id' => 104,
                    'title' => 'Expoziţie-târg internaţională specializată de produse, utilaje, tehnologii agricole şi meşteşuguri, ediţia a XXVII-a',
                    'date' => '16 Octombrie 2025',
                    'url' => 'http://www.farmer.moldexpo.md/',
                    'type' => 'event'
                ],
                [
                    'id' => 105,
                    'title' => 'ANTREPRENOR EXPO - Expoziţie de dezvoltare și promovare a businessului mic și mijlociu',
                    'date' => '20 Noiembrie 2025',
                    'url' => 'http://www.antreprenorexpo.moldexpo.md/',
                    'type' => 'event'
                ]
            ];

            $allItems = array_merge($latestNews, $events);

            return response()->json([
                'success' => true,
                'items' => $allItems,
                'total' => count($allItems)
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving news ticker: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve news ticker'], 500);
        }
    }

    public function preview()
    {
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com');
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Updated Site Preview</title>
            <meta http-equiv="refresh" content="0; url=' . $previewUrl . '" />
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    text-align: center;
                    line-height: 1.6;
                }
                h1 {
                    color: #4a5568;
                    margin-bottom: 20px;
                }
                .container {
                    background-color: #f8fafc;
                    border-radius: 8px;
                    padding: 30px;
                    margin: 40px 0;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .link {
                    display: inline-block;
                    background-color: #4299e1;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 4px;
                    text-decoration: none;
                    margin-top: 20px;
                    font-weight: bold;
                    transition: background-color 0.3s ease;
                }
                .link:hover {
                    background-color: #3182ce;
                }
                .message {
                    font-size: 18px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Your Updated Site Preview</h1>
                <p class="message">You\'re being redirected to the updated preview of your site.</p>
                <p>If you\'re not redirected automatically, click the link below:</p>
                <a class="link" href="' . $previewUrl . '">Go to Updated Site</a>
            </div>
            <script>
                window.location.href = "' . $previewUrl . '";
            </script>
        </body>
        </html>';

        return response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function links()
    {
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com');
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Site Preview Links</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                }
                h1 {
                    color: #4a5568;
                    border-bottom: 2px solid #edf2f7;
                    padding-bottom: 10px;
                }
                .link-section {
                    background-color: #f8fafc;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .button {
                    display: inline-block;
                    background-color: #4299e1;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 4px;
                    text-decoration: none;
                    margin: 10px 0;
                    font-weight: bold;
                }
                .button:hover {
                    background-color: #3182ce;
                }
                .updated {
                    background-color: #48bb78;
                }
                .updated:hover {
                    background-color: #38a169;
                }
                .code {
                    background-color: #edf2f7;
                    padding: 2px 4px;
                    border-radius: 4px;
                    font-family: monospace;
                }
            </style>
        </head>
        <body>
            <h1>Your Site Preview Links</h1>
            
            <div class="link-section">
                <h2>Updated Site Preview</h2>
                <p>This is the direct link to your updated site with the cursor fix applied:</p>
                <a class="button updated" href="' . $previewUrl . '" target="_blank">Open Updated Site</a>
                <p>URL: <code class="code">' . $previewUrl . '</code></p>
            </div>
            
            <div class="link-section">
                <h2>Original Preview Link</h2>
                <p>For reference, this was the original link you provided:</p>
                <a class="button" href="https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com/" target="_blank">Open Original Site</a>
                <p>URL: <code class="code">https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com/</code></p>
            </div>
            
            <div class="link-section">
                <h2>Additional Endpoints</h2>
                <p>We\'ve also created the following endpoints for your convenience:</p>
                <ul>
                    <li><a href="/api/demo" target="_blank">/api/demo</a> - Interactive API demo page</li>
                    <li><a href="/api/preview" target="_blank">/api/preview</a> - Redirect to your updated site</li>
                    <li><a href="/api/new-preview" target="_blank">/api/new-preview</a> - Direct redirect to your updated site</li>
                </ul>
            </div>
        </body>
        </html>';

        return response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function demo()
    {
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Live Demo</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                }
                h1 {
                    color: #4a5568;
                    border-bottom: 2px solid #edf2f7;
                    padding-bottom: 10px;
                }
                .demo-section {
                    background-color: #f8fafc;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .button {
                    display: inline-block;
                    background-color: #4299e1;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 4px;
                    text-decoration: none;
                    margin-top: 10px;
                    cursor: pointer;
                    border: none;
                }
                .button:hover {
                    background-color: #3182ce;
                }
                code {
                    background-color: #edf2f7;
                    padding: 2px 4px;
                    border-radius: 4px;
                    font-family: monospace;
                }
            </style>
        </head>
        <body>
            <h1>Backend Live Demo</h1>
            
            <div class="demo-section">
                <h2>API Endpoints</h2>
                <p>This backend provides the following API endpoints:</p>
                <ul>
                    <li><code>GET /api</code> - Returns a "Hello World" message</li>
                    <li><code>POST /api/status</code> - Create a new status check</li>
                    <li><code>GET /api/status</code> - Get all status checks</li>
                    <li><code>GET /api/demo</code> - This demo page</li>
                </ul>
            </div>
            
            <div class="demo-section">
                <h2>Create Status Check</h2>
                <p>Try creating a status check by clicking the button below:</p>
                <button class="button" onclick="createStatus()">Create Status Check</button>
                <div id="create-result"></div>
            </div>
            
            <div class="demo-section">
                <h2>Get Status Checks</h2>
                <p>Try retrieving all status checks by clicking the button below:</p>
                <button class="button" onclick="getStatuses()">Get Status Checks</button>
                <div id="get-result"></div>
            </div>
            
            <script>
                async function createStatus() {
                    try {
                        const response = await fetch(\'/api/status\', {
                            method: \'POST\',
                            headers: {
                                \'Content-Type\': \'application/json\',
                            },
                            body: JSON.stringify({
                                client_name: \'Demo User \' + new Date().toISOString()
                            }),
                        });
                        
                        const data = await response.json();
                        document.getElementById(\'create-result\').innerHTML = 
                            \'<pre>\' + JSON.stringify(data, null, 2) + \'</pre>\';
                    } catch (error) {
                        document.getElementById(\'create-result\').innerHTML = 
                            \'<p style="color: red;">Error: \' + error.message + \'</p>\';
                    }
                }
                
                async function getStatuses() {
                    try {
                        const response = await fetch(\'/api/status\');
                        const data = await response.json();
                        document.getElementById(\'get-result\').innerHTML = 
                            \'<pre>\' + JSON.stringify(data, null, 2) + \'</pre>\';
                    } catch (error) {
                        document.getElementById(\'get-result\').innerHTML = 
                            \'<p style="color: red;">Error: \' + error.message + \'</p>\';
                    }
                }
            </script>
        </body>
        </html>';

        return response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function newPreview()
    {
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com');
        return redirect($previewUrl);
    }
}