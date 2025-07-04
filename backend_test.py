
import requests
import sys
import json
import uuid
from datetime import datetime

class BackendTester:
    def __init__(self, base_url):
        self.base_url = base_url
        self.tests_run = 0
        self.tests_passed = 0
        self.created_status_id = None

    def run_test(self, name, endpoint, method="GET", data=None, expected_status=200, binary=False):
        """Run a single API test"""
        url = f"{self.base_url}/{endpoint}"
        headers = {'Content-Type': 'application/json'} if not binary else {}
        
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
            
            if binary:
                return True, response.content
            
            try:
                return True, response.json()
            except:
                return True, response.text
                
        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            return False, None

    # Status Check API Tests
    def test_create_status_check(self, client_name):
        """Test creating a status check"""
        success, response = self.run_test(
            f"Create status check for client: {client_name}",
            "api/status",
            method="POST",
            data={"client_name": client_name},
            expected_status=200
        )
        
        if success:
            print(f"Response: {json.dumps(response, indent=2)}")
            if 'id' in response:
                self.created_status_id = response['id']
                print(f"Created status check with ID: {self.created_status_id}")
            return response
        return None

    def test_get_status_checks(self):
        """Test retrieving all status checks"""
        success, response = self.run_test(
            "Get all status checks",
            "api/status",
            method="GET",
            expected_status=200
        )
        
        if success:
            if isinstance(response, list):
                print(f"Retrieved {len(response)} status checks")
                if len(response) > 0:
                    print(f"First status check: {json.dumps(response[0], indent=2)}")
            return response
        return None

    def test_get_status_check_by_id(self, status_id):
        """Test retrieving a specific status check by ID"""
        success, response = self.run_test(
            f"Get status check by ID: {status_id}",
            f"api/status/{status_id}",
            method="GET",
            expected_status=200
        )
        
        if success:
            print(f"Response: {json.dumps(response, indent=2)}")
            return response
        return None

    # News Image Extraction Tests
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

    # News Ticker API Tests
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

    # File Download Tests
    def test_download_pdf(self, pdf_name):
        """Test downloading a PDF file"""
        success, response = self.run_test(
            f"Download PDF: {pdf_name}",
            f"api/download/{pdf_name}",
            method="GET",
            expected_status=200,
            binary=True
        )
        
        if success:
            # Check if it's a PDF (starts with %PDF)
            if response[:4] == b'%PDF':
                print(f"✅ Successfully downloaded PDF file: {pdf_name} ({len(response)} bytes)")
                return True
            else:
                print(f"❌ Downloaded file is not a valid PDF: {pdf_name}")
                return False
        return False

    # HTML Response Tests
    def test_html_endpoint(self, endpoint, expected_title=None):
        """Test an endpoint that returns HTML"""
        success, response = self.run_test(
            f"Get HTML from endpoint: {endpoint}",
            endpoint,
            method="GET",
            expected_status=200
        )
        
        if success:
            if isinstance(response, str) and "<!DOCTYPE html>" in response:
                print(f"✅ Endpoint returned valid HTML ({len(response)} bytes)")
                
                if expected_title and f"<title>{expected_title}</title>" in response:
                    print(f"✅ HTML contains expected title: '{expected_title}'")
                elif expected_title:
                    print(f"❌ HTML does not contain expected title: '{expected_title}'")
                    return False
                
                return True
            else:
                print(f"❌ Endpoint did not return valid HTML")
                return False
        return False

def main():
    # Get backend URL from environment
    backend_url = "https://a8497826-ac6e-4e94-b4c8-4d942d87b11c.preview.emergentagent.com"
    
    print(f"Testing backend at: {backend_url}")
    tester = BackendTester(backend_url)
    
    # Test HTML endpoints
    print("\n=== Testing HTML Endpoints ===")
    tester.test_html_endpoint("api", "API and Preview Links")
    tester.test_html_endpoint("api/demo", "Live Demo")
    tester.test_html_endpoint("api/preview", "Updated Site Preview")
    tester.test_html_endpoint("api/links", "Site Preview Links")
    
    # Test Status Check API
    print("\n=== Testing Status Check API ===")
    client_name = f"Test User {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}"
    status_response = tester.test_create_status_check(client_name)
    
    if status_response and tester.created_status_id:
        # Test getting all status checks
        all_statuses = tester.test_get_status_checks()
        
        # Test getting specific status check by ID
        tester.test_get_status_check_by_id(tester.created_status_id)
        
        # Verify the created status is in the list
        if all_statuses and isinstance(all_statuses, list):
            found = False
            for status in all_statuses:
                if status.get('id') == tester.created_status_id:
                    found = True
                    break
            
            if found:
                print(f"✅ Created status check (ID: {tester.created_status_id}) found in the list of all status checks")
            else:
                print(f"❌ Created status check (ID: {tester.created_status_id}) not found in the list of all status checks")
    
    # Test News Image Extraction
    print("\n=== Testing News Image Extraction ===")
    # Test with the specific URL mentioned in the requirements
    agora_url = "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record"
    agora_result = tester.test_fetch_news_image(agora_url)
    
    if agora_result:
        expected_image = "https://images.pexels.com/photos/6291408/pexels-photo-6291408.jpeg"
        if agora_result.get("image_url") == expected_image:
            print(f"✅ Special handling for agora.md URL works correctly")
        else:
            print(f"❌ Special handling for agora.md URL failed. Expected: {expected_image}, Got: {agora_result.get('image_url')}")
    
    # Test multiple news images
    urls = [
        "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record",
        "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/"
    ]
    tester.test_fetch_multiple_news_images(urls)
    
    # Test News Ticker API
    print("\n=== Testing News Ticker API ===")
    news_ticker = tester.test_news_ticker()
    
    if news_ticker and news_ticker.get('items'):
        # Check for Romanian text encoding
        has_romanian_chars = False
        for item in news_ticker.get('items', []):
            title = item.get('title', '')
            if 'ă' in title or 'ș' in title or 'ț' in title or 'î' in title or 'â' in title:
                has_romanian_chars = True
                break
        
        if has_romanian_chars:
            print(f"✅ News ticker contains properly encoded Romanian characters")
        else:
            print(f"❌ News ticker does not contain properly encoded Romanian characters")
    
    # Test File Downloads
    print("\n=== Testing File Downloads ===")
    tester.test_download_pdf("industria-bauturilor.pdf")
    tester.test_download_pdf("oferta-lactate-ro.pdf")
    tester.test_download_pdf("oferta-carne-si-oua-ro.pdf")
    tester.test_download_pdf("minist1.pdf")
    tester.test_download_pdf("minist2.pdf")
    
    # Print summary
    print(f"\n📊 Tests passed: {tester.tests_passed}/{tester.tests_run}")
    return 0 if tester.tests_passed == tester.tests_run else 1

if __name__ == "__main__":
    sys.exit(main())
