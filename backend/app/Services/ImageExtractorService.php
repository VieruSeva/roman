<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ImageExtractorService
{
    private $client;
    private $timeout = 30;
    private $retries = 3;
    private $cachePrefix = 'image_extract_';
    private $cacheDuration = 3600; // 1 hour

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ],
            'allow_redirects' => [
                'max' => 10,
                'strict' => true,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => true
            ],
            'verify' => false, // Allow self-signed certificates
        ]);
    }

    /**
     * Extract image from a single URL with caching
     */
    public function extractFromUrl($url)
    {
        try {
            // Input validation
            if (!$this->isValidUrl($url)) {
                return [
                    'success' => false,
                    'error' => 'Invalid URL format',
                    'url' => $url
                ];
            }

            // Check cache first
            $cacheKey = $this->cachePrefix . md5($url);
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                Log::info('Image extraction cache hit for URL: ' . $url);
                return array_merge($cached, ['cached' => true]);
            }

            // Extract with retry logic
            $result = $this->extractWithRetry($url);
            
            // Cache successful results
            if ($result['success']) {
                Cache::put($cacheKey, $result, $this->cacheDuration);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Image extraction failed for URL: ' . $url . ' - ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Extraction failed: ' . $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Extract images from multiple URLs
     */
    public function extractFromMultipleUrls($urls)
    {
        $results = [];
        $totalUrls = count($urls);
        
        foreach ($urls as $index => $url) {
            Log::info("Processing URL {$index + 1}/{$totalUrls}: {$url}");
            
            $result = $this->extractFromUrl($url);
            $results[] = $result;
            
            // Small delay between requests to be respectful
            if ($index < $totalUrls - 1) {
                usleep(500000); // 0.5 second delay
            }
        }

        return [
            'success' => true,
            'total' => $totalUrls,
            'results' => $results
        ];
    }

    /**
     * Extract with retry logic
     */
    private function extractWithRetry($url)
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                Log::info("Attempt {$attempt}/{$this->retries} for URL: {$url}");
                
                $result = $this->performExtraction($url);
                
                if ($result['success']) {
                    return $result;
                }
                
                $lastException = new Exception($result['error']);
                
            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("Attempt {$attempt} failed for URL {$url}: " . $e->getMessage());
                
                if ($attempt < $this->retries) {
                    // Exponential backoff
                    $delay = pow(2, $attempt - 1) * 1000000; // microseconds
                    usleep($delay);
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Failed after ' . $this->retries . ' attempts: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'),
            'url' => $url
        ];
    }

    /**
     * Perform the actual image extraction
     */
    private function performExtraction($url)
    {
        try {
            // Fetch page content
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $response->getStatusCode() . ' error'
                ];
            }

            $html = $response->getBody()->getContents();
            
            if (empty($html)) {
                return [
                    'success' => false,
                    'error' => 'Empty response body'
                ];
            }

            // Parse HTML
            $crawler = new Crawler($html);
            
            // Extract all possible data
            $imageData = $this->extractImageData($crawler, $url);
            $title = $this->extractTitle($crawler);
            $description = $this->extractDescription($crawler);
            $metadata = $this->extractMetadata($crawler);

            if ($imageData['success']) {
                return [
                    'success' => true,
                    'url' => $url,
                    'image_url' => $imageData['image_url'],
                    'image_alt' => $imageData['image_alt'] ?? null,
                    'image_width' => $imageData['image_width'] ?? null,
                    'image_height' => $imageData['image_height'] ?? null,
                    'title' => $title,
                    'description' => $description,
                    'metadata' => $metadata,
                    'extraction_method' => $imageData['method'],
                    'timestamp' => now()->toISOString()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $imageData['error'],
                    'url' => $url,
                    'title' => $title,
                    'description' => $description,
                    'metadata' => $metadata
                ];
            }

        } catch (ConnectException $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Request failed: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Parsing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract image data using multiple methods
     */
    private function extractImageData($crawler, $baseUrl)
    {
        $methods = [
            'og_image' => [$this, 'extractOgImage'],
            'twitter_image' => [$this, 'extractTwitterImage'],
            'schema_image' => [$this, 'extractSchemaImage'],
            'meta_image' => [$this, 'extractMetaImage'],
            'largest_image' => [$this, 'extractLargestImage'],
            'first_article_image' => [$this, 'extractFirstArticleImage']
        ];

        foreach ($methods as $method => $callable) {
            try {
                $result = call_user_func($callable, $crawler, $baseUrl);
                
                if ($result['success']) {
                    $result['method'] = $method;
                    return $result;
                }
            } catch (Exception $e) {
                Log::debug("Method {$method} failed: " . $e->getMessage());
                continue;
            }
        }

        return [
            'success' => false,
            'error' => 'No valid image found using any extraction method'
        ];
    }

    /**
     * Extract Open Graph image
     */
    private function extractOgImage($crawler, $baseUrl)
    {
        try {
            $ogImage = $crawler->filter('meta[property="og:image"]')->first();
            
            if ($ogImage->count() > 0) {
                $imageUrl = $ogImage->attr('content');
                
                if (!empty($imageUrl)) {
                    $absoluteUrl = $this->makeAbsoluteUrl($imageUrl, $baseUrl);
                    
                    if ($this->isValidImageUrl($absoluteUrl)) {
                        // Get additional OG image data
                        $width = null;
                        $height = null;
                        $alt = null;
                        
                        $ogImageWidth = $crawler->filter('meta[property="og:image:width"]')->first();
                        if ($ogImageWidth->count() > 0) {
                            $width = $ogImageWidth->attr('content');
                        }
                        
                        $ogImageHeight = $crawler->filter('meta[property="og:image:height"]')->first();
                        if ($ogImageHeight->count() > 0) {
                            $height = $ogImageHeight->attr('content');
                        }
                        
                        $ogImageAlt = $crawler->filter('meta[property="og:image:alt"]')->first();
                        if ($ogImageAlt->count() > 0) {
                            $alt = $ogImageAlt->attr('content');
                        }
                        
                        return [
                            'success' => true,
                            'image_url' => $absoluteUrl,
                            'image_width' => $width,
                            'image_height' => $height,
                            'image_alt' => $alt
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug('OG image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid OG image found'];
    }

    /**
     * Extract Twitter image
     */
    private function extractTwitterImage($crawler, $baseUrl)
    {
        try {
            $twitterImage = $crawler->filter('meta[name="twitter:image"], meta[property="twitter:image"]')->first();
            
            if ($twitterImage->count() > 0) {
                $imageUrl = $twitterImage->attr('content');
                
                if (!empty($imageUrl)) {
                    $absoluteUrl = $this->makeAbsoluteUrl($imageUrl, $baseUrl);
                    
                    if ($this->isValidImageUrl($absoluteUrl)) {
                        return [
                            'success' => true,
                            'image_url' => $absoluteUrl
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug('Twitter image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid Twitter image found'];
    }

    /**
     * Extract Schema.org image
     */
    private function extractSchemaImage($crawler, $baseUrl)
    {
        try {
            $schemaScripts = $crawler->filter('script[type="application/ld+json"]');
            
            foreach ($schemaScripts as $script) {
                $json = $script->textContent;
                $data = json_decode($json, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $imageUrl = $this->findImageInSchema($data);
                    
                    if ($imageUrl) {
                        $absoluteUrl = $this->makeAbsoluteUrl($imageUrl, $baseUrl);
                        
                        if ($this->isValidImageUrl($absoluteUrl)) {
                            return [
                                'success' => true,
                                'image_url' => $absoluteUrl
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug('Schema image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid Schema image found'];
    }

    /**
     * Extract meta image
     */
    private function extractMetaImage($crawler, $baseUrl)
    {
        try {
            $metaImage = $crawler->filter('meta[name="image"], meta[itemprop="image"]')->first();
            
            if ($metaImage->count() > 0) {
                $imageUrl = $metaImage->attr('content');
                
                if (!empty($imageUrl)) {
                    $absoluteUrl = $this->makeAbsoluteUrl($imageUrl, $baseUrl);
                    
                    if ($this->isValidImageUrl($absoluteUrl)) {
                        return [
                            'success' => true,
                            'image_url' => $absoluteUrl
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug('Meta image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid meta image found'];
    }

    /**
     * Extract largest image from page
     */
    private function extractLargestImage($crawler, $baseUrl)
    {
        try {
            $images = $crawler->filter('img[src]');
            $validImages = [];
            
            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                
                if (!empty($src)) {
                    $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);
                    
                    if ($this->isValidImageUrl($absoluteUrl)) {
                        $width = $img->getAttribute('width');
                        $height = $img->getAttribute('height');
                        $alt = $img->getAttribute('alt');
                        
                        $validImages[] = [
                            'url' => $absoluteUrl,
                            'width' => $width ? intval($width) : null,
                            'height' => $height ? intval($height) : null,
                            'alt' => $alt,
                            'estimated_size' => $this->estimateImageSize($absoluteUrl, $width, $height)
                        ];
                    }
                }
            }
            
            if (!empty($validImages)) {
                // Sort by estimated size (largest first)
                usort($validImages, function($a, $b) {
                    return $b['estimated_size'] - $a['estimated_size'];
                });
                
                $largest = $validImages[0];
                
                return [
                    'success' => true,
                    'image_url' => $largest['url'],
                    'image_width' => $largest['width'],
                    'image_height' => $largest['height'],
                    'image_alt' => $largest['alt']
                ];
            }
        } catch (Exception $e) {
            Log::debug('Largest image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid large image found'];
    }

    /**
     * Extract first article image
     */
    private function extractFirstArticleImage($crawler, $baseUrl)
    {
        try {
            $articleSelectors = [
                'article img[src]',
                '.article img[src]',
                '.content img[src]',
                '.post img[src]',
                '.entry img[src]',
                'main img[src]'
            ];
            
            foreach ($articleSelectors as $selector) {
                $images = $crawler->filter($selector);
                
                if ($images->count() > 0) {
                    $firstImage = $images->first();
                    $src = $firstImage->attr('src');
                    
                    if (!empty($src)) {
                        $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);
                        
                        if ($this->isValidImageUrl($absoluteUrl)) {
                            return [
                                'success' => true,
                                'image_url' => $absoluteUrl,
                                'image_alt' => $firstImage->attr('alt')
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug('Article image extraction failed: ' . $e->getMessage());
        }

        return ['success' => false, 'error' => 'No valid article image found'];
    }

    /**
     * Extract title from page
     */
    private function extractTitle($crawler)
    {
        try {
            // Try OG title first
            $ogTitle = $crawler->filter('meta[property="og:title"]')->first();
            if ($ogTitle->count() > 0) {
                $title = trim($ogTitle->attr('content'));
                if (!empty($title)) {
                    return $title;
                }
            }

            // Try Twitter title
            $twitterTitle = $crawler->filter('meta[name="twitter:title"]')->first();
            if ($twitterTitle->count() > 0) {
                $title = trim($twitterTitle->attr('content'));
                if (!empty($title)) {
                    return $title;
                }
            }

            // Try HTML title
            $htmlTitle = $crawler->filter('title')->first();
            if ($htmlTitle->count() > 0) {
                $title = trim($htmlTitle->text());
                if (!empty($title)) {
                    return $title;
                }
            }

            // Try h1
            $h1Title = $crawler->filter('h1')->first();
            if ($h1Title->count() > 0) {
                $title = trim($h1Title->text());
                if (!empty($title)) {
                    return $title;
                }
            }

        } catch (Exception $e) {
            Log::debug('Title extraction failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract description from page
     */
    private function extractDescription($crawler)
    {
        try {
            // Try OG description first
            $ogDesc = $crawler->filter('meta[property="og:description"]')->first();
            if ($ogDesc->count() > 0) {
                $desc = trim($ogDesc->attr('content'));
                if (!empty($desc)) {
                    return $desc;
                }
            }

            // Try Twitter description
            $twitterDesc = $crawler->filter('meta[name="twitter:description"]')->first();
            if ($twitterDesc->count() > 0) {
                $desc = trim($twitterDesc->attr('content'));
                if (!empty($desc)) {
                    return $desc;
                }
            }

            // Try meta description
            $metaDesc = $crawler->filter('meta[name="description"]')->first();
            if ($metaDesc->count() > 0) {
                $desc = trim($metaDesc->attr('content'));
                if (!empty($desc)) {
                    return $desc;
                }
            }

        } catch (Exception $e) {
            Log::debug('Description extraction failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract additional metadata
     */
    private function extractMetadata($crawler)
    {
        $metadata = [];
        
        try {
            // Site name
            $siteName = $crawler->filter('meta[property="og:site_name"]')->first();
            if ($siteName->count() > 0) {
                $metadata['site_name'] = trim($siteName->attr('content'));
            }
            
            // Article author
            $author = $crawler->filter('meta[name="author"], meta[property="article:author"]')->first();
            if ($author->count() > 0) {
                $metadata['author'] = trim($author->attr('content'));
            }
            
            // Publication date
            $publishDate = $crawler->filter('meta[property="article:published_time"]')->first();
            if ($publishDate->count() > 0) {
                $metadata['published_time'] = trim($publishDate->attr('content'));
            }
            
            // Article type
            $type = $crawler->filter('meta[property="og:type"]')->first();
            if ($type->count() > 0) {
                $metadata['type'] = trim($type->attr('content'));
            }
            
        } catch (Exception $e) {
            Log::debug('Metadata extraction failed: ' . $e->getMessage());
        }

        return $metadata;
    }

    /**
     * Validate URL format
     */
    private function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false &&
               (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
    }

    /**
     * Validate image URL
     */
    private function isValidImageUrl($url)
    {
        if (!$this->isValidUrl($url)) {
            return false;
        }

        // Check if URL looks like an image
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        
        // If it has an image extension, it's likely an image
        if (in_array($extension, $imageExtensions)) {
            return true;
        }

        // If no extension, it could still be an image (many CDN URLs don't have extensions)
        // We'll accept it and let the browser/client handle validation
        return true;
    }

    /**
     * Make URL absolute
     */
    private function makeAbsoluteUrl($url, $baseUrl)
    {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'];
        $host = $parsedBase['host'];
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';

        if (strpos($url, '//') === 0) {
            return $scheme . ':' . $url;
        }

        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $port . $url;
        }

        $path = rtrim(dirname($parsedBase['path']), '/');
        return $scheme . '://' . $host . $port . $path . '/' . ltrim($url, '/');
    }

    /**
     * Find image in Schema.org data
     */
    private function findImageInSchema($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'image') {
                    if (is_string($value)) {
                        return $value;
                    } elseif (is_array($value) && isset($value['url'])) {
                        return $value['url'];
                    } elseif (is_array($value) && isset($value[0])) {
                        if (is_string($value[0])) {
                            return $value[0];
                        } elseif (is_array($value[0]) && isset($value[0]['url'])) {
                            return $value[0]['url'];
                        }
                    }
                } elseif (is_array($value)) {
                    $found = $this->findImageInSchema($value);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Estimate image size for prioritization
     */
    private function estimateImageSize($url, $width = null, $height = null)
    {
        if ($width && $height) {
            return intval($width) * intval($height);
        }

        // Estimate based on URL patterns
        if (strpos($url, 'thumbnail') !== false || strpos($url, 'thumb') !== false) {
            return 10000; // Small thumbnail
        }
        
        if (strpos($url, 'medium') !== false) {
            return 100000; // Medium size
        }
        
        if (strpos($url, 'large') !== false || strpos($url, 'big') !== false) {
            return 500000; // Large size
        }

        return 50000; // Default medium estimate
    }

    /**
     * Clear cache for a specific URL
     */
    public function clearCache($url)
    {
        $cacheKey = $this->cachePrefix . md5($url);
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all image extraction cache
     */
    public function clearAllCache()
    {
        // This would need to be implemented based on your cache driver
        // For now, we'll just log it
        Log::info('Image extraction cache clear requested');
        return true;
    }
}