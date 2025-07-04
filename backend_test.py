import requests
import sys
import json
from datetime import datetime

class ANIPMBackendTester:
    def __init__(self, base_url="http://localhost:8001"):
        self.base_url = base_url
        self.tests_run = 0
        self.tests_passed = 0
        self.test_results = []

    def run_test(self, name, method, endpoint, expected_status, data=None):
        """Run a single API test"""
        url = f"{self.base_url}/api/{endpoint}"
        headers = {'Content-Type': 'application/json'}
        
        self.tests_run += 1
        print(f"\n🔍 Testing {name}...")
        
        try:
            if method == 'GET':
                response = requests.get(url, headers=headers)
            elif method == 'POST':
                response = requests.post(url, json=data, headers=headers)

            success = response.status_code == expected_status
            if success:
                self.tests_passed += 1
                print(f"✅ Passed - Status: {response.status_code}")
                result = {"name": name, "status": "PASS", "response_code": response.status_code}
                if response.headers.get('Content-Type') and 'application/json' in response.headers.get('Content-Type'):
                    result["data"] = response.json()
            else:
                print(f"❌ Failed - Expected {expected_status}, got {response.status_code}")
                result = {"name": name, "status": "FAIL", "expected": expected_status, "response_code": response.status_code}
            
            self.test_results.append(result)
            return success, response

        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            self.test_results.append({"name": name, "status": "ERROR", "error": str(e)})
            return False, None

    def test_api_index(self):
        """Test the API index endpoint"""
        return self.run_test(
            "API Index",
            "GET",
            "",
            200
        )

    def test_news_ticker(self):
        """Test the news ticker endpoint"""
        success, response = self.run_test(
            "News Ticker",
            "GET",
            "news-ticker",
            200
        )
        
        if success:
            try:
                data = response.json()
                if 'success' in data and data['success'] and 'items' in data:
                    print(f"✅ News ticker returned {len(data['items'])} items")
                    return True, data
                else:
                    print("❌ News ticker response format is incorrect")
                    return False, data
            except Exception as e:
                print(f"❌ Failed to parse news ticker response: {str(e)}")
                return False, None
        
        return False, None

    def test_fetch_news_image(self, url="https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record"):
        """Test the fetch news image endpoint"""
        return self.run_test(
            "Fetch News Image",
            "POST",
            "fetch-news-image",
            200,
            data={"url": url}
        )

    def test_fetch_multiple_news_images(self):
        """Test the fetch multiple news images endpoint"""
        urls = [
            "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record",
            "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"
        ]
        
        return self.run_test(
            "Fetch Multiple News Images",
            "POST",
            "fetch-multiple-news-images",
            200,
            data=urls
        )

    def test_status_endpoints(self):
        """Test the status check endpoints"""
        # Create a status check
        client_name = f"test_client_{datetime.now().strftime('%H%M%S')}"
        create_success, create_response = self.run_test(
            "Create Status Check",
            "POST",
            "status",
            200,
            data={"client_name": client_name}
        )
        
        if not create_success:
            return False
        
        # Get all status checks
        get_all_success, _ = self.run_test(
            "Get All Status Checks",
            "GET",
            "status",
            200
        )
        
        if not get_all_success:
            return False
        
        # Get specific status check if ID is available
        try:
            status_id = create_response.json().get('id')
            if status_id:
                get_one_success, _ = self.run_test(
                    f"Get Status Check {status_id}",
                    "GET",
                    f"status/{status_id}",
                    200
                )
                return get_one_success
        except Exception:
            pass
        
        return True

    def run_all_tests(self):
        """Run all API tests"""
        print("🚀 Starting ANIPM Backend API Tests")
        print("==================================")
        
        self.test_api_index()
        news_ticker_success, news_ticker_data = self.test_news_ticker()
        self.test_fetch_news_image()
        self.test_fetch_multiple_news_images()
        self.test_status_endpoints()
        
        # Print results
        print("\n📊 Test Results Summary")
        print("==================================")
        print(f"Tests Run: {self.tests_run}")
        print(f"Tests Passed: {self.tests_passed}")
        print(f"Success Rate: {(self.tests_passed / self.tests_run) * 100:.1f}%")
        
        return self.tests_passed == self.tests_run, news_ticker_data

def main():
    # Get backend URL from environment or use default
    backend_url = "http://localhost:8001"
    
    # Run tests
    tester = ANIPMBackendTester(backend_url)
    success, news_ticker_data = tester.run_all_tests()
    
    # Save test results to file
    with open('backend_test_results.json', 'w') as f:
        json.dump(tester.test_results, f, indent=2)
    
    # Save news ticker data if available
    if news_ticker_data:
        with open('news_ticker_data.json', 'w') as f:
            json.dump(news_ticker_data, f, indent=2)
    
    return 0 if success else 1

if __name__ == "__main__":
    sys.exit(main())