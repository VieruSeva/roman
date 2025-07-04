import requests
import sys
import os
from datetime import datetime

class PartnersPDFTester:
    def __init__(self, base_url="https://014f8e48-d857-4a02-8616-4eab2e30ac63.preview.emergentagent.com"):
        self.base_url = base_url
        self.tests_run = 0
        self.tests_passed = 0
        print(f"Testing against backend URL: {self.base_url}")

    def run_test(self, name, endpoint, expected_status=200, expected_content_type="application/pdf"):
        """Run a single API test for PDF download"""
        url = f"{self.base_url}/{endpoint}"
        
        self.tests_run += 1
        print(f"\n🔍 Testing {name}...")
        
        try:
            response = requests.get(url, stream=True)
            
            # Check status code
            status_success = response.status_code == expected_status
            if status_success:
                print(f"✅ Status Code: {response.status_code}")
            else:
                print(f"❌ Status Code: Expected {expected_status}, got {response.status_code}")
                return False
            
            # Check content type
            content_type = response.headers.get('Content-Type', '')
            content_type_success = expected_content_type in content_type
            if content_type_success:
                print(f"✅ Content Type: {content_type}")
            else:
                print(f"❌ Content Type: Expected {expected_content_type}, got {content_type}")
                return False
            
            # Check content length
            content_length = int(response.headers.get('Content-Length', 0))
            if content_length > 0:
                print(f"✅ Content-Length: {content_length} bytes")
            else:
                print(f"❌ Content-Length: No content length or zero")
                return False
            
            # Check if file starts with PDF signature (%PDF)
            first_bytes = next(response.iter_content(4))
            is_pdf = first_bytes.startswith(b'%PDF')
            if is_pdf:
                print(f"✅ File signature: Valid PDF")
            else:
                print(f"❌ File signature: Not a valid PDF")
                return False
            
            self.tests_passed += 1
            return True

        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            return False

    def test_all_pdfs(self):
        """Test all PDF download endpoints"""
        pdfs = [
            {
                "name": "Produse Lactate PDF",
                "endpoint": "api/download/oferta-lactate-ro.pdf"
            },
            {
                "name": "Tehnologii Băuturi PDF",
                "endpoint": "api/download/industria-bauturilor.pdf"
            },
            {
                "name": "Industria Ouălor PDF",
                "endpoint": "api/download/oferta-carne-si-oua-ro.pdf"
            }
        ]
        
        for pdf in pdfs:
            self.run_test(pdf["name"], pdf["endpoint"])

def main():
    # Setup
    tester = PartnersPDFTester()
    
    # Run tests
    tester.test_all_pdfs()
    
    # Print results
    print(f"\n📊 Tests passed: {tester.tests_passed}/{tester.tests_run}")
    return 0 if tester.tests_passed == tester.tests_run else 1

if __name__ == "__main__":
    sys.exit(main())