
import requests
import sys
import webbrowser
import tempfile
import os

def test_pdf_endpoint(url, description):
    """Test if a PDF endpoint returns a PDF that can be viewed in browser"""
    print(f"\n🔍 Testing {description}...")
    
    try:
        # Make the request
        response = requests.get(url, stream=True)
        
        # Check status code
        if response.status_code == 200:
            print(f"✅ Status Code: {response.status_code}")
        else:
            print(f"❌ Status Code: {response.status_code}")
            return False
        
        # Check content type
        content_type = response.headers.get('Content-Type', '')
        if 'application/pdf' in content_type:
            print(f"✅ Content-Type: {content_type}")
        else:
            print(f"❌ Content-Type: Expected application/pdf, got {content_type}")
            return False
        
        # Check Content-Disposition header
        content_disposition = response.headers.get('Content-Disposition', '')
        if 'attachment' in content_disposition:
            print(f"❌ Content-Disposition indicates download: {content_disposition}")
            return False
        else:
            print(f"✅ Content-Disposition does not force download: {content_disposition or 'Not set (good)'}")
        
        # Check if there's content
        content_length = int(response.headers.get('Content-Length', 0))
        if content_length > 0:
            print(f"✅ Content-Length: {content_length} bytes")
        else:
            print("❌ Content-Length: No content received")
            return False
        
        # Print all headers for debugging
        print("\nAll response headers:")
        for header, value in response.headers.items():
            print(f"  {header}: {value}")
        
        return True
        
    except Exception as e:
        print(f"❌ Failed - Error: {str(e)}")
        return False

def main():
    # Base URL
    base_url = "https://1a9a88ed-adf2-4970-936f-f3e05b243feb.preview.emergentagent.com"
    
    # Test all three PDF endpoints
    results = []
    
    results.append(test_pdf_endpoint(
        f"{base_url}/api/download/industria-bauturilor.pdf",
        "Industria Băuturilor PDF"
    ))
    
    results.append(test_pdf_endpoint(
        f"{base_url}/api/download/oferta-lactate-ro.pdf",
        "Ofertă Lactate RO PDF"
    ))
    
    results.append(test_pdf_endpoint(
        f"{base_url}/api/download/oferta-carne-si-oua-ro.pdf",
        "Ofertă Carne și Ouă RO PDF"
    ))
    
    # Print summary
    passed = sum(results)
    total = len(results)
    print(f"\n📊 Tests passed: {passed}/{total}")
    
    if passed == total:
        print("\n✅ All PDFs are configured to open in browser (not download)")
    else:
        print("\n❌ Some PDFs are still configured to download instead of open in browser")
    
    return 0 if passed == total else 1

if __name__ == "__main__":
    sys.exit(main())
