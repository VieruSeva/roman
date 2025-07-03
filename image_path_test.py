import requests
import sys

def test_image_paths():
    """Test different image paths to determine the correct one"""
    base_url = "https://a9e898c1-ad61-4684-b17a-1c799a04aa0d.preview.emergentagent.com"
    
    # Test paths
    paths = [
        "/images/trans1.jpg",
        "/siteik/images/trans1.jpg",
        "/public/images/trans1.jpg",
        "/static/images/trans1.jpg"
    ]
    
    print("Testing image paths:")
    for path in paths:
        url = f"{base_url}{path}"
        try:
            response = requests.get(url)
            content_type = response.headers.get('Content-Type', '')
            
            if response.status_code == 200 and content_type.startswith('image/'):
                print(f"✅ {url} - SUCCESS: Returns image ({content_type})")
            else:
                print(f"❌ {url} - FAILURE: Returns {content_type} with status {response.status_code}")
        except Exception as e:
            print(f"❌ {url} - ERROR: {str(e)}")

if __name__ == "__main__":
    test_image_paths()