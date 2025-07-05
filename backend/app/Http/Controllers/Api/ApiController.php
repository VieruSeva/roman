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
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com');
        
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

    /**
     * Fetch image from news article URL - COMPLETELY REWRITTEN
     * New robust implementation with multiple extraction methods and caching
     */
    public function fetchNewsImage(Request $request)
    {
        try {
            // Enhanced validation
            $validated = $request->validate([
                'url' => 'required|url|max:2048',
                'force_refresh' => 'boolean',
                'include_metadata' => 'boolean'
            ]);

            $url = $validated['url'];
            $forceRefresh = $validated['force_refresh'] ?? false;
            $includeMetadata = $validated['include_metadata'] ?? true;
            
            // Use new ImageExtractor service
            $extractor = app(\App\Services\ImageExtractorService::class);
            
            // Clear cache if force refresh requested
            if ($forceRefresh) {
                $extractor->clearCache($url);
            }
            
            // Extract image data
            $result = $extractor->extractFromUrl($url);

            // Enhance response format
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'url' => $url,
                    'image_url' => $result['image_url'],
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'extraction_method' => $result['extraction_method'],
                    'cached' => $result['cached'] ?? false,
                    'timestamp' => $result['timestamp'] ?? now()->toISOString()
                ];
                
                // Add optional metadata
                if ($includeMetadata) {
                    $response['image_alt'] = $result['image_alt'] ?? null;
                    $response['image_width'] = $result['image_width'] ?? null;
                    $response['image_height'] = $result['image_height'] ?? null;
                    $response['metadata'] = $result['metadata'] ?? [];
                }
                
                return response()->json($response);
            } else {
                return response()->json([
                    'success' => false,
                    'url' => $url,
                    'error' => $result['error'],
                    'title' => $result['title'] ?? null,
                    'description' => $result['description'] ?? null,
                    'metadata' => $result['metadata'] ?? [],
                    'timestamp' => now()->toISOString()
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'timestamp' => now()->toISOString()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in fetchNewsImage: ' . $e->getMessage(), [
                'url' => $request->input('url'),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Failed to extract image data',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Fetch images from multiple news article URLs - COMPLETELY REWRITTEN
     * New robust batch processing with progress tracking and better error handling
     */
    public function fetchMultipleNewsImages(Request $request)
    {
        try {
            // Enhanced validation
            $validated = $request->validate([
                'urls' => 'required|array|min:1|max:50',
                'urls.*' => 'required|url|max:2048',
                'force_refresh' => 'boolean',
                'include_metadata' => 'boolean',
                'parallel_processing' => 'boolean'
            ]);

            $urls = $validated['urls'];
            $forceRefresh = $validated['force_refresh'] ?? false;
            $includeMetadata = $validated['include_metadata'] ?? true;
            $parallelProcessing = $validated['parallel_processing'] ?? false;
            
            // Validate URL count
            if (count($urls) > 50) {
                return response()->json([
                    'success' => false,
                    'error' => 'Maximum 50 URLs allowed per request',
                    'provided_count' => count($urls)
                ], 400);
            }

            // Use new ImageExtractor service
            $extractor = app(\App\Services\ImageExtractorService::class);
            
            // Clear cache if force refresh requested
            if ($forceRefresh) {
                foreach ($urls as $url) {
                    $extractor->clearCache($url);
                }
            }
            
            $startTime = microtime(true);
            
            // Extract images from multiple URLs
            $extractionResult = $extractor->extractFromMultipleUrls($urls);
            
            $endTime = microtime(true);
            $processingTime = round($endTime - $startTime, 2);
            
            // Process results
            $results = [];
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($extractionResult['results'] as $result) {
                if ($result['success']) {
                    $successCount++;
                    $processedResult = [
                        'success' => true,
                        'url' => $result['url'],
                        'image_url' => $result['image_url'],
                        'title' => $result['title'],
                        'description' => $result['description'],
                        'extraction_method' => $result['extraction_method'],
                        'cached' => $result['cached'] ?? false
                    ];
                    
                    // Add optional metadata
                    if ($includeMetadata) {
                        $processedResult['image_alt'] = $result['image_alt'] ?? null;
                        $processedResult['image_width'] = $result['image_width'] ?? null;
                        $processedResult['image_height'] = $result['image_height'] ?? null;
                        $processedResult['metadata'] = $result['metadata'] ?? [];
                    }
                    
                    $results[] = $processedResult;
                } else {
                    $errorCount++;
                    $results[] = [
                        'success' => false,
                        'url' => $result['url'],
                        'error' => $result['error'],
                        'title' => $result['title'] ?? null,
                        'description' => $result['description'] ?? null,
                        'metadata' => $result['metadata'] ?? []
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_urls' => count($urls),
                'successful_extractions' => $successCount,
                'failed_extractions' => $errorCount,
                'success_rate' => round(($successCount / count($urls)) * 100, 1),
                'processing_time_seconds' => $processingTime,
                'results' => $results,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'timestamp' => now()->toISOString()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching multiple news images: ' . $e->getMessage(), [
                'urls_count' => count($request->input('urls', [])),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Failed to process multiple URLs',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * NEW: Clear image extraction cache for a specific URL
     */
    public function clearImageCache(Request $request)
    {
        try {
            $validated = $request->validate([
                'url' => 'required|url|max:2048'
            ]);

            $extractor = app(\App\Services\ImageExtractorService::class);
            $result = $extractor->clearCache($validated['url']);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared for URL',
                'url' => $validated['url'],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error clearing image cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * NEW: Clear all image extraction cache
     */
    public function clearAllImageCache()
    {
        try {
            $extractor = app(\App\Services\ImageExtractorService::class);
            $result = $extractor->clearAllCache();

            return response()->json([
                'success' => true,
                'message' => 'All image extraction cache cleared',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing all image cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear all cache'
            ], 500);
        }
    }

    public function downloadIndustriaBauturilor()
    {
        // Debug file paths
        $possiblePaths = [
            '/app/frontend/public/industria-bauturilor.pdf',
            '/app/frontend/src/documents/Industria Băuturilor .pdf', // Note the spaces
        ];
        
        $debug = [];
        foreach ($possiblePaths as $filePath) {
            $debug[] = [
                'path' => $filePath,
                'exists' => file_exists($filePath),
                'readable' => is_readable($filePath)
            ];
            
            if (file_exists($filePath)) {
                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="industria-bauturilor.pdf"'
                ]);
            }
        }
        
        return response()->json([
            'error' => 'File not found',
            'debug' => $debug
        ], 404);
    }

    public function downloadOfertaLactate()
    {
        $possiblePaths = [
            '/app/frontend/public/oferta-lactate-ro.pdf',
            '/app/frontend/src/documents/Ofertă Lactate RO .pdf', // Note the spaces
        ];
        
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="oferta-lactate-ro.pdf"'
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
                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="oferta-carne-si-oua-ro.pdf"'
                ]);
            }
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist1()
    {
        $filePath = '/app/frontend/src/documents/minist1.pdf';
        
        if (file_exists($filePath)) {
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="minist1.pdf"'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist2()
    {
        $filePath = '/app/frontend/src/documents/minist2.pdf';
        
        if (file_exists($filePath)) {
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="minist2.pdf"'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist3()
    {
        $filePath = '/app/frontend/src/documents/minist3.docx';
        
        if (file_exists($filePath)) {
            return response()->download($filePath, 'minist3.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadMinist4()
    {
        $filePath = '/app/frontend/src/documents/minist4.pdf';
        
        if (file_exists($filePath)) {
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="minist4.pdf"'
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
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com');
        
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
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com');
        
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
                <a class="button" href="https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com/" target="_blank">Open Original Site</a>
                <p>URL: <code class="code">https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com/</code></p>
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

    public function testImageExtraction()
    {
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Advanced Image Extraction Testing</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                    background-color: #f8fafc;
                }
                h1 {
                    color: #2d3748;
                    border-bottom: 3px solid #4299e1;
                    padding-bottom: 10px;
                    margin-bottom: 30px;
                }
                .test-section {
                    background-color: white;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 25px 0;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                    border: 1px solid #e2e8f0;
                }
                .form-group {
                    margin: 20px 0;
                }
                label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #2d3748;
                }
                input[type="url"], input[type="text"] {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e2e8f0;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.2s;
                }
                input[type="url"]:focus, input[type="text"]:focus {
                    outline: none;
                    border-color: #4299e1;
                }
                .checkbox-group {
                    display: flex;
                    gap: 20px;
                    margin: 15px 0;
                }
                .checkbox-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                button {
                    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 600;
                    transition: all 0.2s;
                }
                button:hover {
                    background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
                    transform: translateY(-1px);
                }
                button:disabled {
                    background: #cbd5e0;
                    cursor: not-allowed;
                    transform: none;
                }
                .result {
                    margin-top: 20px;
                    padding: 20px;
                    border-radius: 8px;
                    background-color: #f7fafc;
                    border: 1px solid #e2e8f0;
                }
                .success {
                    background-color: #f0fff4;
                    border-color: #68d391;
                    color: #22543d;
                }
                .error {
                    background-color: #fed7d7;
                    border-color: #fc8181;
                    color: #742a2a;
                }
                .loading {
                    background-color: #ebf8ff;
                    border-color: #63b3ed;
                    color: #2c5aa0;
                }
                .image-preview {
                    max-width: 100%;
                    max-height: 300px;
                    border: 2px solid #e2e8f0;
                    border-radius: 8px;
                    margin: 15px 0;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                .metadata {
                    background-color: #edf2f7;
                    padding: 15px;
                    border-radius: 6px;
                    margin: 10px 0;
                    font-size: 14px;
                }
                .cache-controls {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                .cache-btn {
                    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
                    padding: 8px 16px;
                    font-size: 14px;
                }
                .cache-btn:hover {
                    background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
                }
                .progress-bar {
                    width: 100%;
                    height: 4px;
                    background-color: #e2e8f0;
                    border-radius: 2px;
                    margin: 10px 0;
                    overflow: hidden;
                }
                .progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #4299e1, #3182ce);
                    transition: width 0.3s ease;
                    width: 0%;
                }
                pre {
                    background-color: #1a202c;
                    color: #e2e8f0;
                    padding: 15px;
                    border-radius: 6px;
                    overflow-x: auto;
                    font-size: 13px;
                }
                .stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                }
                .stat-item {
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                    border: 1px solid #e2e8f0;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    color: #4299e1;
                }
                .stat-label {
                    font-size: 12px;
                    color: #718096;
                    text-transform: uppercase;
                }
            </style>
        </head>
        <body>
            <h1>🚀 Advanced Image Extraction Testing Suite</h1>
            
            <div class="test-section">
                <h2>📋 Single URL Testing</h2>
                <div class="form-group">
                    <label for="news-url">📰 Enter News Article URL:</label>
                    <input type="url" id="news-url" placeholder="https://example.com/news/article">
                </div>
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="force-refresh">
                        <label for="force-refresh">🔄 Force Refresh (Clear Cache)</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="include-metadata" checked>
                        <label for="include-metadata">📊 Include Metadata</label>
                    </div>
                </div>
                
                <button onclick="testSingleUrl()">🔍 Extract Image</button>
                
                <div class="cache-controls">
                    <button class="cache-btn" onclick="clearSingleCache()">🗑️ Clear Cache for URL</button>
                    <button class="cache-btn" onclick="clearAllCache()">🧹 Clear All Cache</button>
                </div>
                
                <div id="single-result"></div>
            </div>

            <div class="test-section">
                <h2>📚 Batch URL Testing</h2>
                <div class="form-group">
                    <label for="batch-urls">📝 Enter Multiple URLs (one per line):</label>
                    <textarea id="batch-urls" rows="6" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: monospace;" placeholder="https://example.com/news/article1
https://example.com/news/article2
https://example.com/news/article3"></textarea>
                </div>
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="batch-force-refresh">
                        <label for="batch-force-refresh">🔄 Force Refresh</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="batch-include-metadata" checked>
                        <label for="batch-include-metadata">📊 Include Metadata</label>
                    </div>
                </div>
                
                <button onclick="testBatchUrls()">⚡ Process Batch</button>
                <div id="batch-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div id="progress-text"></div>
                </div>
                <div id="batch-result"></div>
            </div>

            <div class="test-section">
                <h2>🎯 Quick Test Samples</h2>
                <p>Click to test with pre-defined sample URLs:</p>
                <button onclick="testSampleUrls()">🧪 Test Sample URLs</button>
                <div id="sample-results"></div>
            </div>
            
            <script>
                let currentBatchRequest = null;
                
                async function testSingleUrl() {
                    const url = document.getElementById("news-url").value;
                    const forceRefresh = document.getElementById("force-refresh").checked;
                    const includeMetadata = document.getElementById("include-metadata").checked;
                    const resultDiv = document.getElementById("single-result");
                    
                    if (!url) {
                        showResult(resultDiv, "error", "Please enter a URL");
                        return;
                    }
                    
                    showResult(resultDiv, "loading", "🔄 Extracting image data...");
                    
                    try {
                        const response = await fetch("/api/fetch-news-image", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ 
                                url: url,
                                force_refresh: forceRefresh,
                                include_metadata: includeMetadata
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.image_url) {
                            let html = `
                                <div class="result success">
                                    <h3>✅ Success!</h3>
                                    <p><strong>🌐 URL:</strong> ${data.url}</p>
                                    <p><strong>📰 Title:</strong> ${data.title || "Not found"}</p>
                                    <p><strong>📝 Description:</strong> ${data.description || "Not found"}</p>
                                    <p><strong>🔗 Image URL:</strong> <a href="${data.image_url}" target="_blank">${data.image_url}</a></p>
                                    <p><strong>🛠️ Method:</strong> ${data.extraction_method}</p>
                                    <p><strong>⚡ Cached:</strong> ${data.cached ? "Yes" : "No"}</p>
                                    <img src="${data.image_url}" alt="Extracted Image" class="image-preview">
                                    <p style="display:block; color:red;" id="img-error-${Date.now()}" class="hidden">⚠️ Image failed to load</p>
                            `;
                            
                            if (includeMetadata && data.metadata) {
                                html += `
                                    <div class="metadata">
                                        <strong>📊 Additional Metadata:</strong>
                                        <pre>${JSON.stringify(data, null, 2)}</pre>
                                    </div>
                                `;
                            }
                            
                            html += `</div>`;
                            resultDiv.innerHTML = html;
                        } else {
                            showResult(resultDiv, "error", `
                                <h3>❌ Error</h3>
                                <p><strong>🌐 URL:</strong> ${data.url}</p>
                                <p><strong>❗ Error:</strong> ${data.error}</p>
                                <p><strong>📰 Title:</strong> ${data.title || "Not found"}</p>
                                <p><strong>📝 Description:</strong> ${data.description || "Not found"}</p>
                            `);
                        }
                    } catch (error) {
                        showResult(resultDiv, "error", `
                            <h3>❌ Request Failed</h3>
                            <p><strong>❗ Error:</strong> ${error.message}</p>
                        `);
                    }
                }
                
                async function testBatchUrls() {
                    const urlsText = document.getElementById("batch-urls").value;
                    const forceRefresh = document.getElementById("batch-force-refresh").checked;
                    const includeMetadata = document.getElementById("batch-include-metadata").checked;
                    const resultDiv = document.getElementById("batch-result");
                    const progressDiv = document.getElementById("batch-progress");
                    const progressFill = document.getElementById("progress-fill");
                    const progressText = document.getElementById("progress-text");
                    
                    if (!urlsText.trim()) {
                        showResult(resultDiv, "error", "Please enter URLs");
                        return;
                    }
                    
                    const urls = urlsText.trim().split("\\n").filter(url => url.trim().length > 0);
                    
                    if (urls.length === 0) {
                        showResult(resultDiv, "error", "Please enter valid URLs");
                        return;
                    }
                    
                    if (urls.length > 50) {
                        showResult(resultDiv, "error", "Maximum 50 URLs allowed");
                        return;
                    }
                    
                    progressDiv.style.display = "block";
                    progressFill.style.width = "0%";
                    progressText.textContent = "Starting batch processing...";
                    
                    showResult(resultDiv, "loading", `🔄 Processing ${urls.length} URLs...`);
                    
                    try {
                        const startTime = Date.now();
                        
                        const response = await fetch("/api/fetch-multiple-news-images", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ 
                                urls: urls,
                                force_refresh: forceRefresh,
                                include_metadata: includeMetadata
                            })
                        });
                        
                        const data = await response.json();
                        const endTime = Date.now();
                        
                        progressFill.style.width = "100%";
                        progressText.textContent = "Processing complete!";
                        
                        if (data.success) {
                            let html = `
                                <div class="result success">
                                    <h3>📊 Batch Processing Results</h3>
                                    <div class="stats">
                                        <div class="stat-item">
                                            <div class="stat-value">${data.total_urls}</div>
                                            <div class="stat-label">Total URLs</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">${data.successful_extractions}</div>
                                            <div class="stat-label">Successful</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">${data.failed_extractions}</div>
                                            <div class="stat-label">Failed</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">${data.success_rate}%</div>
                                            <div class="stat-label">Success Rate</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">${data.processing_time_seconds}s</div>
                                            <div class="stat-label">Processing Time</div>
                                        </div>
                                    </div>
                            `;
                            
                            data.results.forEach((result, index) => {
                                if (result.success && result.image_url) {
                                    html += `
                                        <div style="margin: 20px 0; padding: 15px; border: 1px solid #68d391; border-radius: 8px; background-color: #f0fff4;">
                                            <strong>URL ${index + 1}:</strong> ✅ Success<br>
                                            <strong>Title:</strong> ${result.title || "Not found"}<br>
                                            <strong>Method:</strong> ${result.extraction_method}<br>
                                            <strong>Cached:</strong> ${result.cached ? "Yes" : "No"}<br>
                                            <strong>Image:</strong> <a href="${result.image_url}" target="_blank">${result.image_url}</a><br>
                                            <img src="${result.image_url}" alt="Image ${index + 1}" style="max-width: 200px; max-height: 150px; margin: 5px 0; border-radius: 4px;">
                                        </div>
                                    `;
                                } else {
                                    html += `
                                        <div style="margin: 20px 0; padding: 15px; border: 1px solid #fc8181; border-radius: 8px; background-color: #fed7d7;">
                                            <strong>URL ${index + 1}:</strong> ❌ ${result.error}<br>
                                            <strong>URL:</strong> ${result.url}
                                        </div>
                                    `;
                                }
                            });
                            
                            html += `</div>`;
                            resultDiv.innerHTML = html;
                        } else {
                            showResult(resultDiv, "error", `
                                <h3>❌ Batch Processing Failed</h3>
                                <p><strong>❗ Error:</strong> ${data.error}</p>
                            `);
                        }
                        
                    } catch (error) {
                        showResult(resultDiv, "error", `
                            <h3>❌ Request Failed</h3>
                            <p><strong>❗ Error:</strong> ${error.message}</p>
                        `);
                    } finally {
                        setTimeout(() => {
                            progressDiv.style.display = "none";
                        }, 2000);
                    }
                }
                
                async function testSampleUrls() {
                    const sampleUrls = [
                        "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/",
                        "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record",
                        "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/"
                    ];
                    
                    document.getElementById("batch-urls").value = sampleUrls.join("\\n");
                    document.getElementById("batch-include-metadata").checked = true;
                    
                    await testBatchUrls();
                }
                
                async function clearSingleCache() {
                    const url = document.getElementById("news-url").value;
                    
                    if (!url) {
                        alert("Please enter a URL first");
                        return;
                    }
                    
                    try {
                        const response = await fetch("/api/clear-image-cache", {
                            method: "DELETE",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ url: url })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert("✅ Cache cleared for URL");
                        } else {
                            alert("❌ Failed to clear cache: " + data.error);
                        }
                    } catch (error) {
                        alert("❌ Error: " + error.message);
                    }
                }
                
                async function clearAllCache() {
                    if (!confirm("Are you sure you want to clear all image extraction cache?")) {
                        return;
                    }
                    
                    try {
                        const response = await fetch("/api/clear-all-image-cache", {
                            method: "DELETE"
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert("✅ All cache cleared");
                        } else {
                            alert("❌ Failed to clear cache: " + data.error);
                        }
                    } catch (error) {
                        alert("❌ Error: " + error.message);
                    }
                }
                
                function showResult(element, type, content) {
                    element.innerHTML = `<div class="result ${type}">${content}</div>`;
                }
            </script>
        </body>
        </html>';

        return response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function newPreview()
    {
        $previewUrl = env('PREVIEW_ENDPOINT', 'https://3e4b7750-8c2d-44cd-b9ef-1cfa2408fdde.preview.emergentagent.com');
        return redirect($previewUrl);
    }

    /**
     * Generic document download function
     * Serves any document from the frontend documents directory
     */
    public function downloadDocument($filename)
    {
        // Security: Clean the filename to prevent directory traversal
        $filename = basename($filename);
        
        // Define possible document paths
        $possiblePaths = [
            '/app/frontend/src/documents/' . $filename,
            '/app/frontend/public/documents/' . $filename,
            '/app/documents/' . $filename,
        ];
        
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                // Determine content type based on file extension
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                $contentType = 'application/octet-stream'; // Default
                if ($extension === 'pdf') {
                    $contentType = 'application/pdf';
                } elseif ($extension === 'docx') {
                    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                } elseif ($extension === 'doc') {
                    $contentType = 'application/msword';
                }
                
                // For DOCX files, force download
                if ($extension === 'docx' || $extension === 'doc') {
                    return response()->download($filePath, $filename, [
                        'Content-Type' => $contentType
                    ]);
                }
                
                // For PDF files, display inline
                return response()->file($filePath, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="' . $filename . '"'
                ]);
            }
        }
        
        return response()->json([
            'error' => 'Document not found',
            'filename' => $filename,
            'searched_paths' => $possiblePaths
        ], 404);
    }
}