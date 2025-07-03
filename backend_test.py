
import requests
import sys
import json

class NewsImageTester:
    def __init__(self, base_url):
        self.base_url = base_url
        self.tests_run = 0
        self.tests_passed = 0

    def run_test(self, name, endpoint, method="GET", data=None, expected_status=200):
        """Run a single API test"""
        url = f"{self.base_url}/{endpoint}"
        headers = {'Content-Type': 'application/json'}
        
        self.tests_run += 1
        print(f"\n🔍 Testing {name}...")
        
        try:
            if method == "GET":
                response = requests.get(url, headers=headers)
            elif method == "POST":
                response = requests.post(url, json=data, headers=headers)
            
            if response.status_code != expected_status:
                print(f"❌ Failed - Expected status {expected_status}, got {response.status_code}")
                return False, None
            
            self.tests_passed += 1
            print(f"✅ Passed - Status: {response.status_code}")
            
            try:
                return True, response.json()
            except:
                return True, response.text
                
        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            return False, None

    def test_fetch_news_image(self, url):
        """Test the fetch-news-image endpoint with a specific URL"""
        success, response = self.run_test(
            f"Fetch image for URL: {url}",
            "api/fetch-news-image",
            method="POST",
            data={"url": url},
            expected_status=200
        )
        
        if success:
            print(f"Response: {json.dumps(response, indent=2)}")
            return response
        return None

    def test_fetch_multiple_news_images(self, urls):
        """Test the fetch-multiple-news-images endpoint with a list of URLs"""
        success, response = self.run_test(
            f"Fetch multiple images",
            "api/fetch-multiple-news-images",
            method="POST",
            data=urls,
            expected_status=200
        )
        
        if success:
            print(f"Response: {json.dumps(response, indent=2)}")
            return response
        return None

    def test_news_ticker(self):
        """Test the news-ticker endpoint"""
        success, response = self.run_test(
            "Get news ticker items",
            "api/news-ticker",
            method="GET",
            expected_status=200
        )
        
        if success:
            print(f"Total items: {response.get('total', 0)}")
            return response
        return None
        
    def test_latest_news_section(self):
        """Test the news-ticker endpoint specifically for the 'Ultimele Noutăți' section"""
        success, response = self.run_test(
            "Get news for 'Ultimele Noutăți' section",
            "api/news-ticker",
            method="GET",
            expected_status=200
        )
        
        if not success:
            return None
            
        # Check if we have news items
        if not response.get("success"):
            print("❌ Failed - API response indicates failure")
            return None
            
        items = response.get("items", [])
        if not items:
            print("❌ Failed - No news items returned")
            return None
            
        # Filter for news items only (not events)
        news_items = [item for item in items if item.get("type") == "news"]
        
        if len(news_items) < 3:
            print(f"❌ Failed - Expected at least 3 news items, but got {len(news_items)}")
            return None
            
        print(f"✅ Found {len(news_items)} news items for 'Ultimele Noutăți' section")
        
        # Check if each news item has required fields
        valid_items = 0
        for item in news_items[:6]:  # Check first 6 items as shown on main page
            has_title = "title" in item and item["title"]
            has_date = "date" in item and item["date"]
            has_url = "url" in item
            
            if has_title and has_date and has_url:
                valid_items += 1
                print(f"✅ News item {item.get('id')} has all required fields")
            else:
                missing = []
                if not has_title: missing.append("title")
                if not has_date: missing.append("date")
                if not has_url: missing.append("url")
                print(f"❌ News item {item.get('id')} is missing fields: {', '.join(missing)}")
        
        if valid_items >= 3:
            print(f"✅ At least 3 valid news items found ({valid_items})")
            return news_items
        else:
            print(f"❌ Failed - Expected at least 3 valid news items, but got {valid_items}")
            return None
            
    def test_selective_link_behavior(self):
        """Test the selective link behavior for news items"""
        success, response = self.run_test(
            "Test selective link behavior for news items",
            "api/news-ticker",
            method="GET",
            expected_status=200
        )
        
        if not success:
            return None
            
        # Check if we have news items
        if not response.get("success"):
            print("❌ Failed - API response indicates failure")
            return None
            
        items = response.get("items", [])
        if not items:
            print("❌ Failed - No news items returned")
            return None
            
        # Filter for news items only (not events)
        news_items = [item for item in items if item.get("type") == "news"]
        
        # Find the document news item (Ministry of Agriculture)
        document_news = None
        for item in news_items:
            if "Ministerul Agriculturii" in item.get("title", ""):
                document_news = item
                break
                
        if not document_news:
            print("❌ Failed - Could not find Ministry of Agriculture news item")
            return None
            
        # Check if it has the hasDocuments flag
        if document_news.get("hasDocuments", False):
            print(f"✅ SUCCESS: Ministry news item has hasDocuments=true")
        else:
            print(f"❌ FAILURE: Ministry news item does not have hasDocuments flag")
            
        # Find regular news items (without hasDocuments flag)
        regular_news = [item for item in news_items if not item.get("hasDocuments", False)]
        
        if len(regular_news) < 1:
            print("❌ Failed - Could not find any regular news items")
            return None
            
        print(f"✅ SUCCESS: Found {len(regular_news)} regular news items")
        
        # Check if regular news items have URLs
        news_with_urls = [item for item in regular_news if item.get("url") and item.get("url") != "#"]
        
        if len(news_with_urls) > 0:
            print(f"✅ SUCCESS: Found {len(news_with_urls)} regular news items with URLs")
        else:
            print("❌ FAILURE: No regular news items have URLs")
            
        return {
            "document_news": document_news,
            "regular_news": regular_news
        }

def main():
    # Get backend URL from environment
    backend_url = "https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com"
    
    print(f"Testing backend at: {backend_url}")
    tester = NewsImageTester(backend_url)
    
    # Test 1: Test the news ticker to verify the "Ultimele Noutăți" section
    print("\n=== Testing news ticker for 'Ultimele Noutăți' section ===")
    news_items = tester.test_latest_news_section()
    
    # Test 2: Test the selective link behavior
    print("\n=== Testing selective link behavior for news items ===")
    selective_link_results = tester.test_selective_link_behavior()
    
    # Flag to track if we found the specific article we're looking for
    found_transition_article = False
    
    # Test for the specific requirements in the request
    print("\n=== Testing specific requirements for 'Tranziția Verde a Republicii Moldova' article ===")
    
    # Get the news ticker data to check all locations
    news_ticker_data = tester.test_news_ticker()
    
    if news_ticker_data and news_ticker_data.get('items'):
        # Find the target article
        target_article = None
        for item in news_ticker_data.get('items', []):
            if (item.get('title') == "Tranziția Verde a Republicii Moldova: Motor al Integrării Europene și Dezvoltării Durabile" and 
                item.get('date') == "24 iunie 2025"):
                target_article = item
                found_transition_article = True
                break
        
        if target_article:
            print(f"\n=== FOUND TARGET ARTICLE: 'Tranziția Verde a Republicii Moldova' ===")
            print(f"  Title: {target_article.get('title')}")
            print(f"  Date: {target_article.get('date')}")
            print(f"  URL: {target_article.get('url')}")
            print(f"  Author: {target_article.get('author', 'Not specified')}")
            print(f"  Category: {target_article.get('category', 'N/A')}")
            print(f"  Has Images: {target_article.get('hasImages', False)}")
            
            # Check requirements
            requirements_met = True
            
            # 1. Check date
            if target_article.get('date') != "24 iunie 2025":
                print(f"❌ FAILURE: Date should be '24 iunie 2025' but got: {target_article.get('date')}")
                requirements_met = False
            else:
                print(f"✅ SUCCESS: Date is correct: '24 iunie 2025'")
            
            # 2. Check URL (should be "#" - no external link)
            if target_article.get('url') != "#":
                print(f"❌ FAILURE: URL should be '#' but got: {target_article.get('url')}")
                requirements_met = False
            else:
                print(f"✅ SUCCESS: URL is correct: '#' (no external link)")
            
            # 3. Check author
            if target_article.get('author') != "ANIPM":
                print(f"❌ FAILURE: Author should be 'ANIPM' but got: {target_article.get('author', 'Not specified')}")
                requirements_met = False
            else:
                print(f"✅ SUCCESS: Author is correct: 'ANIPM'")
            
            # 4. Check images
            if not target_article.get('hasImages', False):
                print(f"❌ FAILURE: Article should have images")
                requirements_met = False
            else:
                print(f"✅ SUCCESS: Article has images")
                
                # Check for specific image
                images = target_article.get('images', [])
                print(f"  Images: {images}")
                
                # Check for specific image - both possible paths
                images = target_article.get('images', [])
                print(f"  Images: {images}")
                
                if "/siteik/images/trans1.jpg" in images:
                    print(f"✅ SUCCESS: Article includes the image: '/siteik/images/trans1.jpg'")
                    
                    # Check if it's trying to use both paths
                    if "/images/trans1.jpg" in images:
                        print(f"⚠️ WARNING: Article is using both '/siteik/images/trans1.jpg' and '/images/trans1.jpg'")
                elif "/images/trans1.jpg" in images:
                    print(f"✅ SUCCESS: Article includes the image: '/images/trans1.jpg'")
                else:
                    print(f"❌ FAILURE: Article does not include either '/siteik/images/trans1.jpg' or '/images/trans1.jpg'")
                    requirements_met = False
            
            if requirements_met:
                print(f"\n✅ SUCCESS: All requirements for the 'Tranziția Verde a Republicii Moldova' article are met in the API!")
            else:
                print(f"\n❌ FAILURE: Some requirements for the 'Tranziția Verde a Republicii Moldova' article are not met in the API")
        
    if not found_transition_article:
        print(f"❌ FAILURE: Could not find the 'Tranziția Verde a Republicii Moldova' article in the news feed")
    
    # Test 3: Test the specific MDED URL that should use the professional image
    mded_url = "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/"
    print("\n=== Testing specific MDED URL with ajutor-de-stat-regional-pentru-investitii ===")
    mded_result = tester.test_fetch_news_image(mded_url)
    
    if mded_result:
        image_url = mded_result.get("image_url")
        expected_image = "https://images.unsplash.com/photo-1551295022-de5522c94e08"
        
        if image_url == expected_image:
            print(f"✅ SUCCESS: Image URL matches expected professional image: {image_url}")
        else:
            print(f"❌ FAILURE: Image URL does not match expected. Got: {image_url}")
            print(f"   Expected: {expected_image}")
    
    # Test 4: Test multiple news images to verify batch processing
    print("\n=== Testing multiple news images including MDED URL ===")
    urls = [
        "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/",
        "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"
    ]
    
    multiple_results = tester.test_fetch_multiple_news_images(urls)
    
    if multiple_results:
        for result in multiple_results:
            if "ajutor-de-stat-regional-pentru-investitii" in result.get("url", ""):
                image_url = result.get("image_url")
                expected_image = "https://images.unsplash.com/photo-1551295022-de5522c94e08"
                
                if image_url == expected_image:
                    print(f"✅ SUCCESS: Multiple images - MDED image URL matches expected: {image_url}")
                else:
                    print(f"❌ FAILURE: Multiple images - MDED image URL does not match expected. Got: {image_url}")
                    print(f"   Expected: {expected_image}")
    
    # Print summary
    print(f"\n📊 Tests passed: {tester.tests_passed}/{tester.tests_run}")
    return 0 if tester.tests_passed == tester.tests_run else 1

if __name__ == "__main__":
    sys.exit(main())
