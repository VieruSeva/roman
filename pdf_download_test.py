import requests
import sys
import os
from pathlib import Path

class PDFDownloadTester:
    """Test suite for PDF download functionality"""
    
    def __init__(self):
        # Get the backend URL from environment variable or use a default
        self.base_url = os.environ.get('REACT_APP_BACKEND_URL', 'https://014f8e48-d857-4a02-8616-4eab2e30ac63.preview.emergentagent.com')
        print(f"Using base URL: {self.base_url}")
        self.tests_run = 0
        self.tests_passed = 0
        self.download_dir = Path("/tmp/pdf_downloads")
        self.download_dir.mkdir(exist_ok=True)
    
    def test_pdf_download_api(self, endpoint, expected_filename):
        """Test if a PDF file can be downloaded via API endpoint"""
        self.tests_run += 1
        print(f"\n🔍 Testing download of {endpoint}...")
        
        try:
            # Make the request to download the PDF
            url = f"{self.base_url}/api/{endpoint}"
            print(f"Requesting: {url}")
            response = requests.get(url, stream=True)
            
            # Check if the request was successful
            if response.status_code == 200:
                # Check if the content type is PDF
                content_type = response.headers.get('Content-Type', '')
                if 'application/pdf' in content_type:
                    print(f"✅ Content-Type is correct: {content_type}")
                    
                    # Check Content-Disposition header
                    content_disposition = response.headers.get('Content-Disposition', '')
                    if 'attachment' in content_disposition:
                        print(f"✅ Content-Disposition is correct: {content_disposition}")
                    else:
                        print(f"⚠️ Content-Disposition missing attachment directive: {content_disposition}")
                    
                    # Save the file to verify it's a valid PDF
                    file_path = self.download_dir / Path(endpoint).name
                    
                    with open(file_path, 'wb') as f:
                        for chunk in response.iter_content(chunk_size=8192):
                            f.write(chunk)
                    
                    # Check file size to ensure it's not empty
                    file_size = file_path.stat().st_size
                    if file_size > 1000:  # Arbitrary minimum size for a valid PDF
                        print(f"✅ Downloaded file size: {file_size} bytes")
                        self.tests_passed += 1
                        return True
                    else:
                        print(f"❌ Downloaded file is too small: {file_size} bytes")
                        return False
                else:
                    print(f"❌ Failed - Content-Type is not PDF: {content_type}")
                    return False
            else:
                print(f"❌ Failed - Status: {response.status_code}")
                if response.status_code == 404:
                    print("   This could indicate the file doesn't exist or the endpoint is incorrect")
                return False
        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            return False

def main():
    # Setup
    tester = PDFDownloadTester()
    
    # Test PDF file downloads via API endpoints
    pdf_endpoints = [
        ("download/industria-bauturilor.pdf", "Industria Băuturilor.pdf"),
        ("download/oferta-lactate-ro.pdf", "Ofertă Lactate RO.pdf"),
        ("download/oferta-carne-si-oua-ro.pdf", "Ofertă Carne și Ouă RO.pdf")
    ]
    
    pdf_results = []
    for endpoint, filename in pdf_endpoints:
        result = tester.test_pdf_download_api(endpoint, filename)
        pdf_results.append((endpoint, result))
    
    # Print results
    print("\n📊 Test Results Summary:")
    print(f"Tests passed: {tester.tests_passed}/{tester.tests_run}")
    
    print("\nPDF API Endpoint Results:")
    for endpoint, result in pdf_results:
        status = "✅ Working" if result else "❌ Not working"
        print(f"{endpoint}: {status}")
    
    return 0 if tester.tests_passed == tester.tests_run else 1

if __name__ == "__main__":
    sys.exit(main())