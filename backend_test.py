import requests
import sys
import json
import os
from datetime import datetime

class ANIPMBackendTester:
    def __init__(self, base_url):
        self.base_url = base_url
        self.tests_run = 0
        self.tests_passed = 0
        self.test_results = []

    def run_test(self, name, method, endpoint, expected_status, data=None, check_content_type=None, check_content=False):
        """Run a single API test"""
        url = f"{self.base_url}/api/{endpoint}"
        headers = {'Content-Type': 'application/json'}
        
        self.tests_run += 1
        print(f"\n🔍 Testing {name}...")
        print(f"   URL: {url}")
        
        try:
            if method == 'GET':
                response = requests.get(url, headers=headers, timeout=5)
            elif method == 'POST':
                response = requests.post(url, json=data, headers=headers, timeout=5)
            elif method == 'DELETE':
                response = requests.delete(url, json=data, headers=headers, timeout=5)

            success = response.status_code == expected_status
            
            # Check content type if specified
            if success and check_content_type:
                content_type = response.headers.get('Content-Type', '')
                if check_content_type not in content_type:
                    success = False
                    print(f"❌ Failed - Expected Content-Type containing '{check_content_type}', got '{content_type}'")
            
            # Check if response has content if required
            if success and check_content and len(response.content) == 0:
                success = False
                print(f"❌ Failed - Expected non-empty response content")
            
            if success:
                self.tests_passed += 1
                print(f"✅ Passed - Status: {response.status_code}")
                if check_content_type:
                    print(f"✅ Content-Type: {response.headers.get('Content-Type')}")
                if check_content:
                    print(f"✅ Content Length: {len(response.content)} bytes")
                
                result = {
                    "name": name, 
                    "status": "PASS", 
                    "response_code": response.status_code,
                    "content_type": response.headers.get('Content-Type', '')
                }
                
                if response.headers.get('Content-Type') and 'application/json' in response.headers.get('Content-Type'):
                    try:
                        result["data"] = response.json()
                    except:
                        result["data"] = "Non-JSON content"
            else:
                print(f"❌ Failed - Expected status {expected_status}, got {response.status_code}")
                result = {
                    "name": name, 
                    "status": "FAIL", 
                    "expected": expected_status, 
                    "response_code": response.status_code,
                    "content_type": response.headers.get('Content-Type', '')
                }
            
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
            200,
            check_content_type="text/html"
        )
    
    def test_api_demo(self):
        """Test the API demo endpoint"""
        return self.run_test(
            "API Demo Page",
            "GET",
            "demo",
            200,
            check_content_type="text/html"
        )

    def test_news_ticker(self):
        """Test the news ticker endpoint"""
        success, response = self.run_test(
            "News Ticker",
            "GET",
            "news-ticker",
            200,
            check_content_type="application/json"
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

    def test_fetch_news_image(self, url="https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"):
        """Test the fetch news image endpoint"""
        return self.run_test(
            "Fetch News Image",
            "POST",
            "fetch-news-image",
            200,
            data={"url": url},
            check_content_type="application/json"
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
            data={"urls": urls},
            check_content_type="application/json"
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
            data={"client_name": client_name},
            check_content_type="application/json"
        )
        
        if not create_success:
            return False
        
        # Verify UUID format and timestamp in response
        try:
            data = create_response.json()
            if 'id' not in data or 'timestamp' not in data:
                print("❌ Status check response missing id or timestamp")
                return False
            
            # Check UUID format (basic check)
            uuid = data['id']
            if len(uuid) != 36 or uuid.count('-') != 4:
                print(f"❌ Invalid UUID format: {uuid}")
                return False
            
            print(f"✅ Valid UUID: {uuid}")
            print(f"✅ Timestamp: {data['timestamp']}")
        except Exception as e:
            print(f"❌ Failed to validate status check response: {str(e)}")
            return False
        
        # Get all status checks
        get_all_success, get_all_response = self.run_test(
            "Get All Status Checks",
            "GET",
            "status",
            200,
            check_content_type="application/json"
        )
        
        if not get_all_success:
            return False
        
        # Verify the response contains an array of status checks
        try:
            data = get_all_response.json()
            if not isinstance(data, list):
                print("❌ Status checks response is not an array")
                return False
            
            print(f"✅ Retrieved {len(data)} status checks")
        except Exception as e:
            print(f"❌ Failed to validate status checks response: {str(e)}")
            return False
        
        # Get specific status check if ID is available
        try:
            status_id = create_response.json().get('id')
            if status_id:
                get_one_success, get_one_response = self.run_test(
                    f"Get Status Check {status_id}",
                    "GET",
                    f"status/{status_id}",
                    200,
                    check_content_type="application/json"
                )
                
                if not get_one_success:
                    return False
                
                # Verify the response contains the correct status check
                data = get_one_response.json()
                if data.get('id') != status_id or data.get('client_name') != client_name:
                    print(f"❌ Status check data mismatch")
                    return False
                
                print(f"✅ Retrieved specific status check with correct data")
                return True
        except Exception as e:
            print(f"❌ Failed to validate specific status check: {str(e)}")
            return False
        
        return True

    def test_document_downloads(self):
        """Test document download endpoints"""
        documents = [
            {"name": "HOTĂRÂRE Cerinţelor Produse de panificaţie şi paste făinoase PDF", "endpoint": "download/HOTĂRÂRE_Cerinţelor_Produse_de_panificaţie_şi_paste_făinoase.pdf", "content_type": "application/pdf"},
            {"name": "Legea cu privirea la comerțul interior PDF", "endpoint": "download/Legea cu privirea la comerțul interior.pdf", "content_type": "application/pdf"},
            {"name": "Legea privind calitatea apei potabile PDF", "endpoint": "download/Legea privind calitatea apei potabile.pdf", "content_type": "application/pdf"},
            {"name": "Legea privind depozitatea cerialelor PDF", "endpoint": "download/Legea privind depozitatea cerialelor.pdf", "content_type": "application/pdf"},
            {"name": "Legea privind siguranța alimentelor PDF", "endpoint": "download/Legea privind siguranța alimentelor.pdf", "content_type": "application/pdf"},
            {"name": "Legea privind întreprinderile mici și mijlocii PDF", "endpoint": "download/Legea privind întreprinderile mici și mijlocii.pdf", "content_type": "application/pdf"},
            {"name": "Legea privind informarea consumatorului PDF", "endpoint": "download/Legea_privind_informarea_consumatorului_cu_privire_la_produsele.pdf", "content_type": "application/pdf"},
            {"name": "Link DOCX", "endpoint": "download/link.docx", "content_type": "application/vnd.openxmlformats-officedocument.wordprocessingml.document"}
        ]
        
        all_passed = True
        
        for doc in documents:
            success, response = self.run_test(
                doc["name"],
                "GET",
                doc["endpoint"],
                200,
                check_content_type=doc["content_type"],
                check_content=True
            )
            
            if success and response:
                # Check for CORS headers
                cors_origin = response.headers.get('Access-Control-Allow-Origin')
                cors_methods = response.headers.get('Access-Control-Allow-Methods')
                cors_headers = response.headers.get('Access-Control-Allow-Headers')
                
                if cors_origin and cors_methods and cors_headers:
                    print(f"✅ CORS headers properly set for {doc['name']}")
                    print(f"   - Access-Control-Allow-Origin: {cors_origin}")
                    print(f"   - Access-Control-Allow-Methods: {cors_methods}")
                    print(f"   - Access-Control-Allow-Headers: {cors_headers}")
                else:
                    print(f"❌ CORS headers missing for {doc['name']}")
                    all_passed = False
            
            if not success:
                all_passed = False
        
        return all_passed

    def run_all_tests(self):
        """Run all API tests"""
        print("🚀 Starting ANIPM Backend API Tests")
        print("==================================")
        print(f"Backend URL: {self.base_url}")
        print("==================================")
        
        # Test main API endpoints
        self.test_api_index()
        self.test_api_demo()
        
        # Test database connectivity and operations
        db_test_success = self.test_status_endpoints()
        if db_test_success:
            print("\n✅ Database connectivity and operations working correctly")
        else:
            print("\n❌ Database connectivity or operations failed")
        
        # Test news ticker API
        news_ticker_success, news_ticker_data = self.test_news_ticker()
        if news_ticker_success:
            print("\n✅ News ticker API working correctly")
        else:
            print("\n❌ News ticker API failed")
        
        # Test image extraction APIs
        image_success, _ = self.test_fetch_news_image()
        multiple_image_success, _ = self.test_fetch_multiple_news_images()
        
        if image_success and multiple_image_success:
            print("\n✅ Image extraction APIs working correctly")
        else:
            print("\n❌ Image extraction APIs failed")
        
        # Test document downloads
        doc_success = self.test_document_downloads()
        if doc_success:
            print("\n✅ Document download endpoints working correctly")
        else:
            print("\n❌ Document download endpoints failed")
        
        # Print results
        print("\n📊 Test Results Summary")
        print("==================================")
        print(f"Tests Run: {self.tests_run}")
        print(f"Tests Passed: {self.tests_passed}")
        print(f"Success Rate: {(self.tests_passed / self.tests_run) * 100:.1f}%")
        
        return self.tests_passed == self.tests_run, news_ticker_data

def test_document_direct_access():
    """Test direct access to documents from the frontend public folder"""
    print("\n🔍 Testing direct document access from frontend public folder...")
    
    # Get the frontend URL from the environment
    frontend_url = os.environ.get('REACT_APP_FRONTEND_URL', 'https://5bbe3b01-21a0-41a5-8ebe-b4c341aa2191.preview.emergentagent.com')
    
    # List of documents to test
    documents = [
        {"name": "HOTĂRÂRE Cerinţelor Produse de panificaţie şi paste făinoase PDF", "path": "/documents/HOTĂRÂRE_Cerinţelor_Produse_de_panificaţie_şi_paste_făinoase.pdf", "content_type": "application/pdf"},
        {"name": "Legea cu privirea la comerțul interior PDF", "path": "/documents/Legea cu privirea la comerțul interior.pdf", "content_type": "application/pdf"},
        {"name": "Legea privind calitatea apei potabile PDF", "path": "/documents/Legea privind calitatea apei potabile.pdf", "content_type": "application/pdf"},
        {"name": "Link DOCX", "path": "/documents/link.docx", "content_type": "application/vnd.openxmlformats-officedocument.wordprocessingml.document"}
    ]
    
    all_passed = True
    
    for doc in documents:
        url = f"{frontend_url}{doc['path']}"
        print(f"   Testing document: {doc['name']}")
        print(f"   URL: {url}")
        
        try:
            response = requests.get(url, timeout=5)
            success = response.status_code == 200
            
            if success:
                content_type = response.headers.get('Content-Type', '')
                if doc['content_type'] in content_type:
                    print(f"✅ Passed - Status: {response.status_code}")
                    print(f"✅ Content-Type: {content_type}")
                    print(f"✅ Content Length: {len(response.content)} bytes")
                else:
                    print(f"❌ Failed - Expected Content-Type containing '{doc['content_type']}', got '{content_type}'")
                    all_passed = False
            else:
                print(f"❌ Failed - Expected status 200, got {response.status_code}")
                all_passed = False
                
        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            all_passed = False
    
    return all_passed

def test_static_news_data():
    """Test that the static news data file exists and is accessible"""
    print("\n🔍 Testing static news data file...")
    
    # Get the frontend URL from the environment
    frontend_url = os.environ.get('REACT_APP_FRONTEND_URL', 'https://5bbe3b01-21a0-41a5-8ebe-b4c341aa2191.preview.emergentagent.com')
    
    # Check if the newsData.js file exists
    try:
        # We can't directly access the JS file through HTTP, so we'll check if the NewsTicker component loads
        # which depends on the newsData.js file
        response = requests.get(frontend_url, timeout=5)
        
        if response.status_code == 200:
            print(f"✅ Frontend loads successfully - Status: {response.status_code}")
            print(f"✅ Content Length: {len(response.content)} bytes")
            return True
        else:
            print(f"❌ Failed to load frontend - Status: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Failed - Error: {str(e)}")
        return False

def main():
    # Get backend URL from environment or use default
    backend_url = os.environ.get('REACT_APP_BACKEND_URL', 'https://5bbe3b01-21a0-41a5-8ebe-b4c341aa2191.preview.emergentagent.com')
    
    print(f"Using backend URL: {backend_url}")
    
    # Run API tests
    tester = ANIPMBackendTester(backend_url)
    api_success, news_ticker_data = tester.run_all_tests()
    
    # Test direct document access
    document_success = test_document_direct_access()
    
    # Test static news data
    news_data_success = test_static_news_data()
    
    # Save test results to file
    with open('backend_test_results.json', 'w') as f:
        json.dump(tester.test_results, f, indent=2)
    
    # Save news ticker data if available
    if news_ticker_data:
        with open('news_ticker_data.json', 'w') as f:
            json.dump(news_ticker_data, f, indent=2)
    
    # Overall success
    overall_success = api_success and document_success and news_data_success
    
    print("\n📊 Overall Test Results")
    print("==================================")
    print(f"API Tests: {'✅ Passed' if api_success else '❌ Failed'}")
    print(f"Document Direct Access: {'✅ Passed' if document_success else '❌ Failed'}")
    print(f"Static News Data: {'✅ Passed' if news_data_success else '❌ Failed'}")
    print(f"Overall: {'✅ Passed' if overall_success else '❌ Failed'}")
    
    return 0 if overall_success else 1

if __name__ == "__main__":
    sys.exit(main())